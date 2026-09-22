<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\Production;
use App\Models\ProductionIngredient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ReportManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::create([
            'first_name' => 'Maria',
            'last_name' => 'SweetNest',
            'email' => 'owner@sweetnest.com',
            'password' => Hash::make('password'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->staff = User::create([
            'first_name' => 'Anna',
            'last_name' => 'Reyes',
            'email' => 'staff@sweetnest.com',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'is_active' => true,
        ]);
    }

    public function test_owner_can_access_reports_hub(): void
    {
        $response = $this->actingAs($this->owner)->get(route('reports.index'));

        $response->assertSee('Executive Reports');
        $response->assertSee('Sales Reports');
        $response->assertSee('Ingredient Consumption Reports');
        $response->assertSee('Inventory History');
    }

    public function test_owner_can_view_orders_sales_report(): void
    {
        $customer = Customer::create([
            'first_name' => 'Elena',
            'last_name' => 'Torres',
            'contact_number' => '09181112233',
        ]);

        Order::create([
            'customer_id' => $customer->id,
            'order_number' => 'ORD-TEST-001',
            'order_date' => now()->toDateString(),
            'scheduled_date' => now()->addDays(2)->toDateString(),
            'order_type' => 'pickup',
            'status' => 'confirmed',
            'total_amount' => 1200.00,
        ]);

        $response = $this->actingAs($this->owner)->get(route('reports.orders', ['period' => 'this_month']));

        $response->assertStatus(200);
        $response->assertSee('Order & Sales Report');
        $response->assertSee('ORD-TEST-001');
        $response->assertSee('Elena Torres');
        $response->assertSee('1,200.00');
    }

    public function test_owner_can_view_ingredient_consumption_report(): void
    {
        $customer = Customer::create([
            'first_name' => 'Elena',
            'last_name' => 'Torres',
            'contact_number' => '09181112233',
        ]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'order_number' => 'ORD-TEST-002',
            'order_date' => now()->toDateString(),
            'scheduled_date' => now()->addDays(2)->toDateString(),
            'order_type' => 'pickup',
            'status' => 'in_production',
            'total_amount' => 800.00,
        ]);

        $flour = Ingredient::create([
            'name' => 'All-Purpose Flour',
            'unit' => 'kg',
            'current_stock' => 50,
            'reorder_level' => 10,
        ]);

        $production = Production::create([
            'order_id' => $order->id,
            'assigned_baker_id' => $this->staff->id,
            'production_date' => now()->toDateString(),
            'status' => 'in_progress',
        ]);

        ProductionIngredient::create([
            'production_id' => $production->id,
            'ingredient_id' => $flour->id,
            'quantity_used' => 3.5,
        ]);

        $response = $this->actingAs($this->owner)->get(route('reports.consumption', [
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->toDateString(),
        ]));

        $response->assertStatus(200);
        $response->assertSee('Ingredient Consumption Report');
        $response->assertSee('All-Purpose Flour');
        $response->assertSee('3.50');
    }

    public function test_owner_can_view_inventory_history_report(): void
    {
        $response = $this->actingAs($this->owner)->get(route('reports.inventoryHistory'));

        $response->assertStatus(200);
        $response->assertSee('Inventory Movement & History');
    }

    public function test_staff_is_forbidden_from_accessing_reports(): void
    {
        $response = $this->actingAs($this->staff)->get(route('reports.index'));
        $response->assertStatus(403);

        $response = $this->actingAs($this->staff)->get(route('reports.orders'));
        $response->assertStatus(403);

        $response = $this->actingAs($this->staff)->get(route('reports.consumption'));
        $response->assertStatus(403);

        $response = $this->actingAs($this->staff)->get(route('reports.inventoryHistory'));
        $response->assertStatus(403);
    }

    public function test_unauthenticated_guests_are_redirected_to_login(): void
    {
        $response = $this->get(route('reports.index'));
        $response->assertRedirect(route('login'));
    }
}
