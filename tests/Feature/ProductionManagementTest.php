<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Ingredient;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Production;
use App\Models\ProductionIngredient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProductionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $baker;
    protected User $staff;
    protected User $delivery;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::create([
            'first_name' => 'Lamber',
            'last_name' => 'Fernando',
            'email' => 'owner@sweetnest.com',
            'password' => Hash::make('password'),
            'role' => 'owner',
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

        $this->staff = User::create([
            'first_name' => 'Anna',
            'last_name' => 'Reyes',
            'email' => 'staff@sweetnest.com',
            'password' => Hash::make('password'),
            'role' => 'staff',
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
        ]);
    }

    private function createConfirmedOrder(): Order
    {
        $order = Order::create([
            'order_number' => 'SN-' . strtoupper(fake()->bothify('####??')),
            'customer_id' => $this->customer->id,
            'created_by' => $this->staff->id,
            'order_date' => now(),
            'scheduled_date' => now()->addDays(2),
            'order_type' => 'delivery',
            'status' => 'confirmed',
            'total_amount' => 1500.00,
            'down_payment_required' => 750.00,
            'total_paid' => 750.00,
            'payment_status' => 'partially_paid',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'item_name' => 'Chocolate Drip Birthday Cake',
            'item_type' => 'cake',
            'flavor' => 'Dark Chocolate',
            'size' => '8 inch 2-tier',
            'design_theme' => 'Floral Gold',
            'custom_names' => 'Happy 21st Birthday Maria',
            'quantity' => 1,
            'unit_price' => 1500.00,
            'subtotal' => 1500.00,
        ]);

        return $order;
    }

    public function test_baker_and_owner_can_view_production_queue_and_stats(): void
    {
        $order = $this->createConfirmedOrder();

        Production::create([
            'order_id' => $order->id,
            'assigned_baker_id' => $this->baker->id,
            'production_date' => now()->toDateString(),
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        // Baker access
        $response = $this->actingAs($this->baker)->get(route('productions.index'));
        $response->assertStatus(200);
        $response->assertSee('Production Management');
        $response->assertSee('Currently In Oven');
        $response->assertSee($order->order_number);

        // Owner access
        $this->actingAs($this->owner)->get(route('productions.index'))->assertStatus(200);
    }

    public function test_baker_can_schedule_production_batch_and_auto_start(): void
    {
        $order = $this->createConfirmedOrder();

        $postData = [
            'order_id' => $order->id,
            'assigned_baker_id' => $this->baker->id,
            'production_date' => now()->toDateString(),
            'notes' => 'Keep chocolate drip refrigerated before frosting.',
            'auto_start' => '1',
        ];

        $response = $this->actingAs($this->baker)->post(route('productions.store'), $postData);

        $this->assertDatabaseHas('productions', [
            'order_id' => $order->id,
            'assigned_baker_id' => $this->baker->id,
            'status' => 'in_progress',
            'notes' => 'Keep chocolate drip refrigerated before frosting.',
        ]);

        $production = Production::where('order_id', $order->id)->first();
        $this->assertNotNull($production->started_at);

        $response->assertRedirect(route('productions.show', $production));

        // Verifies order status transitioned to 'in_production'
        $order->refresh();
        $this->assertEquals('in_production', $order->status);
    }

    public function test_updating_production_status_to_completed_updates_order_to_ready_for_release(): void
    {
        $order = $this->createConfirmedOrder();

        $production = Production::create([
            'order_id' => $order->id,
            'assigned_baker_id' => $this->baker->id,
            'production_date' => now()->toDateString(),
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $response = $this->actingAs($this->baker)->patch(route('productions.updateStatus', $production), [
            'status' => 'completed',
        ]);

        $response->assertRedirect(route('productions.show', $production));

        $production->refresh();
        $this->assertEquals('completed', $production->status);
        $this->assertNotNull($production->completed_at);

        // Requirement verification: order status transitions to ready_for_release
        $order->refresh();
        $this->assertEquals('ready_for_release', $order->status);
    }

    public function test_logging_ingredient_consumption_deducts_stock_and_creates_inventory_transaction(): void
    {
        $order = $this->createConfirmedOrder();

        $production = Production::create([
            'order_id' => $order->id,
            'assigned_baker_id' => $this->baker->id,
            'production_date' => now()->toDateString(),
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $flour = Ingredient::create([
            'name' => 'All-Purpose Flour',
            'unit' => 'kg',
            'current_stock' => 50.00,
            'reorder_level' => 10.00,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->baker)->post(route('productions.logIngredient', $production), [
            'ingredient_id' => $flour->id,
            'quantity_used' => 3.50,
        ]);

        $response->assertRedirect(route('productions.show', $production));
        $response->assertSessionHas('success');

        // Check production_ingredients
        $this->assertDatabaseHas('production_ingredients', [
            'production_id' => $production->id,
            'ingredient_id' => $flour->id,
            'quantity_used' => 3.50,
        ]);

        // Check ingredient stock deducted
        $flour->refresh();
        $this->assertEquals(46.50, (float)$flour->current_stock);

        // Check inventory transaction audit record
        $this->assertDatabaseHas('inventory_transactions', [
            'ingredient_id' => $flour->id,
            'created_by' => $this->baker->id,
            'transaction_type' => 'production_usage',
            'quantity' => -3.50,
        ]);
    }

    public function test_removing_logged_ingredient_restores_stock_and_creates_adjustment_transaction(): void
    {
        $order = $this->createConfirmedOrder();

        $production = Production::create([
            'order_id' => $order->id,
            'assigned_baker_id' => $this->baker->id,
            'production_date' => now()->toDateString(),
            'status' => 'in_progress',
        ]);

        $sugar = Ingredient::create([
            'name' => 'Refined White Sugar',
            'unit' => 'kg',
            'current_stock' => 20.00, // Stock before logging was 22.00, after logging 2.00 it became 20.00
            'reorder_level' => 5.00,
            'is_active' => true,
        ]);

        $prodIngredient = ProductionIngredient::create([
            'production_id' => $production->id,
            'ingredient_id' => $sugar->id,
            'quantity_used' => 2.00,
        ]);

        // Remove / Reverse
        $response = $this->actingAs($this->baker)->delete(
            route('productions.removeIngredient', [$production, $prodIngredient])
        );

        $response->assertRedirect(route('productions.show', $production));
        $response->assertSessionHas('success');

        // Record removed
        $this->assertDatabaseMissing('production_ingredients', [
            'id' => $prodIngredient->id,
        ]);

        // Stock restored back from 20.00 to 22.00
        $sugar->refresh();
        $this->assertEquals(22.00, (float)$sugar->current_stock);

        // Adjustment audit transaction created
        $this->assertDatabaseHas('inventory_transactions', [
            'ingredient_id' => $sugar->id,
            'transaction_type' => 'audit_adjustment',
            'quantity' => 2.00,
        ]);
    }

    public function test_unauthorized_delivery_staff_cannot_access_production_module(): void
    {
        $order = $this->createConfirmedOrder();

        $production = Production::create([
            'order_id' => $order->id,
            'assigned_baker_id' => $this->baker->id,
            'production_date' => now()->toDateString(),
            'status' => 'pending',
        ]);

        // Delivery staff access blocked
        $this->actingAs($this->delivery)->get(route('productions.index'))->assertStatus(403);
        $this->actingAs($this->delivery)->get(route('productions.create'))->assertStatus(403);
        $this->actingAs($this->delivery)->get(route('productions.show', $production))->assertStatus(403);
        $this->actingAs($this->delivery)->post(route('productions.store'), [])->assertStatus(403);
        $this->actingAs($this->delivery)->patch(route('productions.updateStatus', $production), [])->assertStatus(403);

        // Guest access blocked
        $this->app['auth']->logout();
        $this->get(route('productions.index'))->assertRedirect(route('login'));
    }
}
