<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $staff;
    protected User $baker;

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
    }

    public function test_staff_can_view_customers_list(): void
    {
        Customer::create([
            'first_name' => 'Maria',
            'middle_name' => 'Clara',
            'last_name' => 'Santos',
            'contact_number' => '0917-123-4567',
            'address' => 'Tugbok, Davao City',
        ]);

        $response = $this->actingAs($this->staff)->get(route('customers.index'));

        $response->assertStatus(200);
        $response->assertSee('Santos');
        $response->assertSee('Maria');
        $response->assertSee('0917-123-4567');
    }

    public function test_staff_can_search_customers(): void
    {
        Customer::create(['first_name' => 'Maria', 'last_name' => 'Santos', 'contact_number' => '0917-111-1111']);
        Customer::create(['first_name' => 'Juan', 'last_name' => 'Dela Cruz', 'contact_number' => '0920-222-2222']);

        $response = $this->actingAs($this->staff)->get(route('customers.index', ['search' => 'Santos']));

        $response->assertStatus(200);
        $response->assertSee('Santos');
        $response->assertDontSee('Dela Cruz');
    }

    public function test_staff_can_create_a_customer(): void
    {
        $response = $this->actingAs($this->staff)->post(route('customers.store'), [
            'first_name' => 'Clarissa',
            'middle_name' => 'Mae',
            'last_name' => 'Dizon',
            'contact_number' => '0919-888-7766',
            'facebook_name' => 'Clarissa Dizon FB',
            'address' => 'Mintal, Davao City',
        ]);

        $customer = Customer::where('last_name', 'Dizon')->first();

        $this->assertNotNull($customer);
        $this->assertEquals('Clarissa', $customer->first_name);
        $response->assertRedirect(route('customers.show', $customer));
    }

    public function test_customer_validation_fails_without_required_fields(): void
    {
        $response = $this->actingAs($this->staff)->post(route('customers.store'), [
            'first_name' => '',
            'last_name' => '',
            'contact_number' => '',
        ]);

        $response->assertSessionHasErrors(['first_name', 'last_name', 'contact_number']);
    }

    public function test_staff_can_view_customer_profile_and_history(): void
    {
        $customer = Customer::create([
            'first_name' => 'Lourdes',
            'last_name' => 'Tan',
            'contact_number' => '0918-333-4444',
            'address' => 'Matina, Davao City',
        ]);

        $response = $this->actingAs($this->staff)->get(route('customers.show', $customer));

        $response->assertStatus(200);
        $response->assertSee('Lourdes');
        $response->assertSee('Tan');
        $response->assertSee('0918-333-4444');
    }

    public function test_staff_can_update_customer_details(): void
    {
        $customer = Customer::create([
            'first_name' => 'Old',
            'last_name' => 'Name',
            'contact_number' => '0918-000-0000',
        ]);

        $response = $this->actingAs($this->staff)->put(route('customers.update', $customer), [
            'first_name' => 'Updated',
            'middle_name' => 'New',
            'last_name' => 'Person',
            'contact_number' => '0918-999-9999',
            'address' => 'New Address Davao City',
        ]);

        $customer->refresh();

        $this->assertEquals('Updated', $customer->first_name);
        $this->assertEquals('Person', $customer->last_name);
        $this->assertEquals('0918-999-9999', $customer->contact_number);
        $response->assertRedirect(route('customers.show', $customer));
    }

    public function test_customer_with_existing_orders_cannot_be_deleted(): void
    {
        $customer = Customer::create([
            'first_name' => 'Customer',
            'last_name' => 'WithOrders',
            'contact_number' => '0917-000-1111',
        ]);

        Order::create([
            'order_number' => 'SN-TEST-0001',
            'customer_id' => $customer->id,
            'order_date' => now(),
            'scheduled_date' => now()->addDays(2),
            'order_type' => 'pickup',
            'status' => 'confirmed',
            'total_amount' => 500,
        ]);

        $response = $this->actingAs($this->staff)->delete(route('customers.destroy', $customer));

        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
        $response->assertSessionHas('error');
    }

    public function test_baker_is_forbidden_from_accessing_customer_management(): void
    {
        $response = $this->actingAs($this->baker)->get(route('customers.index'));

        $response->assertStatus(403);
    }
}
