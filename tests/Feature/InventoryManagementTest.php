<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\InventoryAudit;
use App\Models\InventoryTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InventoryManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $staff;
    protected User $baker;
    protected User $delivery;

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
    }

    public function test_staff_and_baker_can_view_inventory_dashboard_and_low_stock_alerts(): void
    {
        Ingredient::create([
            'name' => 'All-Purpose Flour',
            'unit' => 'kg',
            'current_stock' => 4.00,
            'reorder_level' => 10.00, // Low stock!
            'is_active' => true,
        ]);

        Ingredient::create([
            'name' => 'Granulated Sugar',
            'unit' => 'kg',
            'current_stock' => 50.00,
            'reorder_level' => 10.00, // Healthy stock
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->staff)->get(route('inventory.index'));
        $response->assertStatus(200);
        $response->assertSee('Inventory Management');
        $response->assertSee('All-Purpose Flour');
        $response->assertSee('Reorder Needed');

        // Test filter low stock only
        $filterResponse = $this->actingAs($this->baker)->get(route('inventory.index', ['filter' => 'low_stock']));
        $filterResponse->assertStatus(200);
        $filterResponse->assertSee('All-Purpose Flour');
        $filterResponse->assertDontSee('Granulated Sugar');
    }

    public function test_staff_can_record_stock_in_purchase(): void
    {
        $flour = Ingredient::create([
            'name' => 'All-Purpose Flour',
            'unit' => 'kg',
            'current_stock' => 10.00,
            'reorder_level' => 15.00,
            'is_active' => true,
        ]);

        $stockInData = [
            'ingredient_id' => $flour->id,
            'quantity' => 40.00,
            'unit_cost' => 55.00,
            'expiration_date' => now()->addMonths(6)->toDateString(),
            'reference' => 'Supplier Delivery Receipt #9981',
            'remarks' => 'Fresh 25kg sacks delivered by supplier',
        ];

        $response = $this->actingAs($this->staff)->post(route('inventory.storeStockIn'), $stockInData);
        $response->assertRedirect(route('inventory.index'));
        $response->assertSessionHas('success');

        // Stock incremented from 10 to 50
        $flour->refresh();
        $this->assertEquals(50.00, (float)$flour->current_stock);

        // Transaction logged
        $this->assertDatabaseHas('inventory_transactions', [
            'ingredient_id' => $flour->id,
            'created_by' => $this->staff->id,
            'transaction_type' => 'stock_in',
            'quantity' => 40.00,
            'unit_cost' => 55.00,
            'total_cost' => 2200.00,
            'reference' => 'Supplier Delivery Receipt #9981',
        ]);
    }

    public function test_staff_can_record_spoilage_adjustment(): void
    {
        $milk = Ingredient::create([
            'name' => 'Fresh Whole Milk',
            'unit' => 'liters',
            'current_stock' => 12.00,
            'reorder_level' => 4.00,
            'is_active' => true,
        ]);

        $spoilageData = [
            'ingredient_id' => $milk->id,
            'transaction_type' => 'spoilage',
            'adjustment_type' => 'deduct',
            'quantity' => 3.00,
            'remarks' => '3 liters spoiled due to refrigerator temperature fluctuation',
        ];

        $response = $this->actingAs($this->staff)->post(route('inventory.storeAdjust'), $spoilageData);
        $response->assertRedirect(route('inventory.index'));

        // Stock decremented from 12 to 9
        $milk->refresh();
        $this->assertEquals(9.00, (float)$milk->current_stock);

        $this->assertDatabaseHas('inventory_transactions', [
            'ingredient_id' => $milk->id,
            'transaction_type' => 'spoilage',
            'quantity' => -3.00,
        ]);
    }

    public function test_twice_daily_physical_count_audit_records_variances_and_syncs_stock(): void
    {
        $flour = Ingredient::create([
            'name' => 'All-Purpose Flour',
            'unit' => 'kg',
            'current_stock' => 50.00,
            'reorder_level' => 10.00,
            'is_active' => true,
        ]);

        $sugar = Ingredient::create([
            'name' => 'White Sugar',
            'unit' => 'kg',
            'current_stock' => 20.00,
            'reorder_level' => 5.00,
            'is_active' => true,
        ]);

        // Conduct Morning Shift Physical Audit:
        // Flour has actual physical count of 47.50 kg (shortage of -2.50 kg)
        // Sugar has actual physical count of 20.00 kg (exact match: variance 0)
        $auditData = [
            'audit_date' => now()->toDateString(),
            'audit_shift' => 'morning',
            'notes' => 'Opening morning physical inventory count.',
            'sync_system_stock' => '1',
            'items' => [
                [
                    'ingredient_id' => $flour->id,
                    'physical_stock' => 47.50,
                ],
                [
                    'ingredient_id' => $sugar->id,
                    'physical_stock' => 20.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->baker)->post(route('audits.store'), $auditData);

        $audit = InventoryAudit::first();
        $this->assertNotNull($audit);
        $this->assertEquals('morning', $audit->audit_shift);
        $this->assertEquals($this->baker->id, $audit->conducted_by);

        $response->assertRedirect(route('audits.show', $audit));

        // Audit Items created
        $this->assertDatabaseHas('inventory_audit_items', [
            'audit_id' => $audit->id,
            'ingredient_id' => $flour->id,
            'system_stock' => 50.00,
            'physical_stock' => 47.50,
            'variance' => -2.50,
        ]);

        $this->assertDatabaseHas('inventory_audit_items', [
            'audit_id' => $audit->id,
            'ingredient_id' => $sugar->id,
            'system_stock' => 20.00,
            'physical_stock' => 20.00,
            'variance' => 0.00,
        ]);

        // Stock was synchronized to physical count
        $flour->refresh();
        $this->assertEquals(47.50, (float)$flour->current_stock);

        // Discrepancy transaction logged
        $this->assertDatabaseHas('inventory_transactions', [
            'ingredient_id' => $flour->id,
            'transaction_type' => 'audit_adjustment',
            'quantity' => -2.50,
        ]);
    }

    public function test_staff_can_view_transaction_audit_trail(): void
    {
        $flour = Ingredient::create([
            'name' => 'All-Purpose Flour',
            'unit' => 'kg',
            'current_stock' => 30.00,
            'reorder_level' => 10.00,
            'is_active' => true,
        ]);

        InventoryTransaction::create([
            'ingredient_id' => $flour->id,
            'created_by' => $this->staff->id,
            'transaction_type' => 'stock_in',
            'quantity' => 30.00,
            'reference' => 'PO-TEST-1234',
        ]);

        $response = $this->actingAs($this->staff)->get(route('inventory.transactions'));
        $response->assertStatus(200);
        $response->assertSee('PO-TEST-1234');
        $response->assertSee('All-Purpose Flour');
    }

    public function test_unauthorized_delivery_role_cannot_access_inventory_or_audits(): void
    {
        $this->actingAs($this->delivery)->get(route('inventory.index'))->assertStatus(403);
        $this->actingAs($this->delivery)->get(route('inventory.stockIn'))->assertStatus(403);
        $this->actingAs($this->delivery)->get(route('audits.index'))->assertStatus(403);
        $this->actingAs($this->delivery)->get(route('audits.create'))->assertStatus(403);
        $this->actingAs($this->delivery)->post(route('inventory.storeStockIn'), [])->assertStatus(403);

        $this->app['auth']->logout();
        $this->get(route('inventory.index'))->assertRedirect(route('login'));
    }
}
