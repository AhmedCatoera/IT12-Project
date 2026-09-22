<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PaymentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $staff;
    protected User $baker;
    protected User $delivery;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staff = User::create([
            'first_name' => 'Anna',
            'last_name' => 'Reyes',
            'email' => 'staff@sweetnest.com',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'is_active' => true,
        ]);

        $this->baker = User::create([
            'first_name' => 'Carlos',
            'last_name' => 'Mendoza',
            'email' => 'baker@sweetnest.com',
            'password' => Hash::make('password'),
            'role' => 'baker',
            'is_active' => true,
        ]);

        $this->delivery = User::create([
            'first_name' => 'Danilo',
            'last_name' => 'Torres',
            'email' => 'delivery@sweetnest.com',
            'password' => Hash::make('password'),
            'role' => 'delivery',
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'first_name' => 'Maria',
            'middle_name' => 'Clara',
            'last_name' => 'Santos',
            'contact_number' => '0917-123-4567',
            'address' => 'Tugbok, Davao City',
            'facebook_name' => 'Maria Santos',
        ]);
    }

    private function createSampleOrder(array $attributes = []): Order
    {
        return Order::create(array_merge([
            'order_number' => 'SN-' . strtoupper(fake()->bothify('####??')),
            'customer_id' => $this->customer->id,
            'created_by' => $this->staff->id,
            'order_date' => now(),
            'scheduled_date' => now()->addDays(3),
            'order_type' => 'pickup',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'total_amount' => 1500.00,
            'down_payment_required' => 750.00,
            'total_paid' => 0.00,
        ], $attributes));
    }

    public function test_staff_can_view_payments_list_and_stats(): void
    {
        $order = $this->createSampleOrder();

        Payment::create([
            'order_id' => $order->id,
            'received_by' => $this->staff->id,
            'payment_type' => 'down_payment',
            'amount' => 750.00,
            'payment_method' => 'gcash',
            'reference_number' => 'GCASH-998877',
            'payment_date' => now(),
        ]);

        $response = $this->actingAs($this->staff)->get(route('payments.index'));

        $response->assertStatus(200);
        $response->assertSee('Payment Management');
        $response->assertSee('Total Revenue Collected');
        $response->assertSee('750.00');
        $response->assertSee('GCASH-998877');
        $response->assertSee('Maria Clara Santos');
    }

    public function test_staff_can_view_record_payment_form(): void
    {
        $order = $this->createSampleOrder();

        $response = $this->actingAs($this->staff)->get(route('payments.create', $order));

        $response->assertStatus(200);
        $response->assertSee($order->order_number);
        $response->assertSee('50% Down Payment Req.');
        $response->assertSee('750.00');
    }

    public function test_cannot_view_record_payment_form_for_fully_paid_order(): void
    {
        $order = $this->createSampleOrder([
            'payment_status' => 'fully_paid',
            'total_paid' => 1500.00,
        ]);

        $response = $this->actingAs($this->staff)->get(route('payments.create', $order));

        $response->assertRedirect(route('orders.show', $order));
        $response->assertSessionHas('error', "Order {$order->order_number} is already fully paid.");
    }

    public function test_recording_50_percent_downpayment_auto_confirms_pending_order(): void
    {
        $order = $this->createSampleOrder([
            'status' => 'pending',
            'total_amount' => 2000.00,
            'down_payment_required' => 1000.00,
            'total_paid' => 0.00,
            'payment_status' => 'unpaid',
        ]);

        $paymentData = [
            'order_id' => $order->id,
            'payment_type' => 'down_payment',
            'amount' => 1000.00,
            'payment_method' => 'gcash',
            'reference_number' => 'GCASH-12345678',
            'payment_date' => now()->format('Y-m-d H:i:s'),
            'notes' => 'Received 50% deposit via GCash',
        ];

        $response = $this->actingAs($this->staff)->post(route('payments.store'), $paymentData);

        $response->assertRedirect(route('orders.show', $order));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'amount' => 1000.00,
            'payment_method' => 'gcash',
            'reference_number' => 'GCASH-12345678',
        ]);

        $order->refresh();
        $this->assertEquals(1000.00, (float)$order->total_paid);
        $this->assertEquals('partially_paid', $order->payment_status);
        // Requirement verification: 50% down payment transitions status from 'pending' to 'confirmed'
        $this->assertEquals('confirmed', $order->status);
    }

    public function test_recording_balance_settlement_marks_order_as_fully_paid(): void
    {
        $order = $this->createSampleOrder([
            'status' => 'confirmed',
            'total_amount' => 2000.00,
            'down_payment_required' => 1000.00,
            'total_paid' => 1000.00,
            'payment_status' => 'partially_paid',
        ]);

        // Pre-existing down payment
        Payment::create([
            'order_id' => $order->id,
            'received_by' => $this->staff->id,
            'payment_type' => 'down_payment',
            'amount' => 1000.00,
            'payment_method' => 'gcash',
            'reference_number' => 'GCASH-INIT',
            'payment_date' => now()->subDay(),
        ]);

        // Pay remaining balance of 1000
        $paymentData = [
            'order_id' => $order->id,
            'payment_type' => 'balance_payment',
            'amount' => 1000.00,
            'payment_method' => 'cash',
            'payment_date' => now()->format('Y-m-d H:i:s'),
            'notes' => 'Settled remaining balance upon release',
        ];

        $response = $this->actingAs($this->staff)->post(route('payments.store'), $paymentData);

        $response->assertRedirect(route('orders.show', $order));

        $order->refresh();
        $this->assertEquals(2000.00, (float)$order->total_paid);
        $this->assertEquals('fully_paid', $order->payment_status);
        $this->assertEquals(0.00, (float)$order->balance_due);
    }

    public function test_payment_exceeding_balance_due_is_rejected_by_validation(): void
    {
        $order = $this->createSampleOrder([
            'total_amount' => 1000.00,
            'total_paid' => 600.00,
            'payment_status' => 'partially_paid',
        ]);

        // Remaining balance is 400. Trying to record 500 should fail validation
        $paymentData = [
            'order_id' => $order->id,
            'payment_type' => 'balance_payment',
            'amount' => 500.00,
            'payment_method' => 'cash',
            'payment_date' => now()->format('Y-m-d H:i:s'),
        ];

        $response = $this->actingAs($this->staff)->post(route('payments.store'), $paymentData);

        $response->assertSessionHasErrors(['amount']);
        $this->assertDatabaseMissing('payments', [
            'order_id' => $order->id,
            'amount' => 500.00,
        ]);
    }

    public function test_unauthorized_roles_cannot_access_payment_module(): void
    {
        $order = $this->createSampleOrder();

        // Baker access
        $this->actingAs($this->baker)->get(route('payments.index'))->assertStatus(403);
        $this->actingAs($this->baker)->get(route('payments.create', $order))->assertStatus(403);
        $this->actingAs($this->baker)->post(route('payments.store'), [])->assertStatus(403);

        // Delivery access
        $this->actingAs($this->delivery)->get(route('payments.index'))->assertStatus(403);
        $this->actingAs($this->delivery)->get(route('payments.create', $order))->assertStatus(403);
        $this->actingAs($this->delivery)->post(route('payments.store'), [])->assertStatus(403);

        // Unauthenticated guest access
        $this->app['auth']->logout();
        $this->get(route('payments.index'))->assertRedirect(route('login'));
    }
}
