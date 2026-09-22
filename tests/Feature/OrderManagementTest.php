<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OrderManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $staff;
    protected User $baker;
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

        $this->customer = Customer::create([
            'first_name' => 'Maria',
            'middle_name' => 'Clara',
            'last_name' => 'Santos',
            'contact_number' => '0917-123-4567',
            'address' => 'Tugbok, Davao City',
            'facebook_name' => 'Maria Santos',
        ]);
    }

    public function test_staff_can_view_orders_list(): void
    {
        Order::create([
            'order_number' => 'SN-TEST-0001',
            'customer_id' => $this->customer->id,
            'order_date' => now(),
            'scheduled_date' => now()->addDays(2),
            'order_type' => 'delivery',
            'delivery_address' => 'Tugbok, Davao City',
            'status' => 'pending',
            'total_amount' => 1200,
            'down_payment_required' => 600,
        ]);

        $response = $this->actingAs($this->staff)->get(route('orders.index'));

        $response->assertStatus(200);
        $response->assertSee('SN-TEST-0001');
        $response->assertSee('Santos');
    }

    public function test_staff_can_create_order_with_multiple_custom_items(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'scheduled_date' => now()->addDays(3)->format('Y-m-d H:i:s'),
            'order_type' => 'delivery',
            'delivery_address' => 'Phase 1, Tugbok, Davao City',
            'notes' => 'Call before delivery',
            'items' => [
                [
                    'item_type' => 'cake',
                    'item_name' => 'Custom Celebration Cake',
                    'flavor' => 'Chocolate Moist',
                    'size' => '8x4 inch',
                    'design_theme' => 'Pink Floral',
                    'custom_names' => 'Happy Birthday Sofia',
                    'quantity' => 1,
                    'unit_price' => 1500.00,
                ],
                [
                    'item_type' => 'souvenir',
                    'item_name' => 'Sintra Board Standee',
                    'flavor' => null,
                    'size' => 'A5',
                    'design_theme' => 'Floral Sofia',
                    'custom_names' => 'Sofia @ 7',
                    'quantity' => 20,
                    'unit_price' => 50.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->staff)->post(route('orders.store'), $payload);

        $order = Order::where('customer_id', $this->customer->id)->latest()->first();

        $this->assertNotNull($order);
        $this->assertEquals(2500.00, (float)$order->total_amount);
        $this->assertEquals(1250.00, (float)$order->down_payment_required);
        $this->assertCount(2, $order->items);

        $response->assertRedirect(route('orders.show', $order));
    }

    public function test_order_creation_fails_without_items(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'scheduled_date' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'order_type' => 'pickup',
            'items' => [],
        ];

        $response = $this->actingAs($this->staff)->post(route('orders.store'), $payload);

        $response->assertSessionHasErrors('items');
    }

    public function test_staff_can_view_order_details(): void
    {
        $order = Order::create([
            'order_number' => 'SN-TEST-0002',
            'customer_id' => $this->customer->id,
            'order_date' => now(),
            'scheduled_date' => now()->addDays(2),
            'order_type' => 'pickup',
            'status' => 'confirmed',
            'total_amount' => 800,
            'down_payment_required' => 400,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'item_type' => 'pastry',
            'item_name' => 'Red Velvet Cupcakes',
            'quantity' => 12,
            'unit_price' => 50,
            'subtotal' => 600,
        ]);

        $response = $this->actingAs($this->staff)->get(route('orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee('SN-TEST-0002');
        $response->assertSee('Red Velvet Cupcakes');
        $response->assertSee('400.00');
    }

    public function test_staff_can_update_order_workflow_status(): void
    {
        $order = Order::create([
            'order_number' => 'SN-TEST-0003',
            'customer_id' => $this->customer->id,
            'order_date' => now(),
            'scheduled_date' => now()->addDays(2),
            'order_type' => 'pickup',
            'status' => 'pending',
            'total_amount' => 1000,
            'down_payment_required' => 500,
        ]);

        $response = $this->actingAs($this->staff)->patch(route('orders.updateStatus', $order), [
            'status' => 'confirmed',
        ]);

        $order->refresh();
        $this->assertEquals('confirmed', $order->status);
        $response->assertSessionHas('success');
    }

    public function test_order_with_recorded_payments_cannot_be_deleted(): void
    {
        $order = Order::create([
            'order_number' => 'SN-TEST-0004',
            'customer_id' => $this->customer->id,
            'order_date' => now(),
            'scheduled_date' => now()->addDays(2),
            'order_type' => 'pickup',
            'status' => 'confirmed',
            'total_amount' => 1000,
            'down_payment_required' => 500,
            'total_paid' => 500,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'payment_type' => 'down_payment',
            'amount' => 500,
            'payment_method' => 'cash',
            'payment_date' => now(),
        ]);

        $response = $this->actingAs($this->staff)->delete(route('orders.destroy', $order));

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
        $response->assertSessionHas('error');
    }

    public function test_staff_can_view_order_creation_form(): void
    {
        $response = $this->actingAs($this->staff)->get(route('orders.create'));

        $response->assertStatus(200);
        $response->assertSee('Create New Customer Order');
        $response->assertSee('Santos');
    }

    public function test_staff_can_view_order_edit_form(): void
    {
        $order = Order::create([
            'order_number' => 'SN-TEST-0005',
            'customer_id' => $this->customer->id,
            'order_date' => now(),
            'scheduled_date' => now()->addDays(2),
            'order_type' => 'pickup',
            'status' => 'pending',
            'total_amount' => 500,
            'down_payment_required' => 250,
        ]);

        $response = $this->actingAs($this->staff)->get(route('orders.edit', $order));

        $response->assertStatus(200);
        $response->assertSee('SN-TEST-0005');
        $response->assertSee('Santos');
    }

    public function test_baker_is_forbidden_from_creating_orders(): void
    {
        $response = $this->actingAs($this->baker)->get(route('orders.create'));

        $response->assertStatus(403);
    }
}
