<?php

namespace Tests\Feature;

use App\Models\PhysicalCount;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\SweetNestData;
use Tests\TestCase;

/** Owner and Staff are the only roles. Staff can't add or adjust stock, see reports or manage users. */
class AccessTest extends TestCase
{
    use RefreshDatabase, SweetNestData;

    public function test_only_owner_and_staff_roles_exist(): void
    {
        $this->assertSame(['owner', 'staff'], array_keys(\App\Models\User::ROLES));
    }

    public function test_staff_is_blocked_from_owner_pages_and_actions(): void
    {
        $owner = $this->owner();
        $staff = $this->staff();
        $ingredient = $this->ingredient(10);

        $job = $this->doneJob($owner);
        $stockOut = app(InventoryService::class)->recordStockOut($job, [['ingredient_id' => $ingredient->id, 'quantity' => 1]], today()->toDateString(), null, $staff);
        $line = $stockOut->items()->first();
        $count = app(InventoryService::class)->recordCount(['count_date' => today()->toDateString(), 'shift' => 'morning'], [$ingredient->id => 8], $staff);

        $blocked = [
            ['get', '/products'],
            ['post', '/products'],
            ['post', '/inventory'],
            ['put', "/inventory/{$ingredient->id}"],
            ['get', '/stock-ins'],
            ['get', '/stock-ins/create'],
            ['post', '/stock-ins'],
            ['get', '/stock-adjustments'],
            ['get', '/stock-adjustments/create'],
            ['post', '/stock-adjustments'],
            ['post', "/physical-counts/{$count->id}/apply"],
            ['put', "/stock-out-items/{$line->id}"],
            ['delete', "/stock-out-items/{$line->id}"],
            ['get', '/reports'],
            ['get', '/reports/sales'],
            ['get', '/reports/collections'],
            ['get', '/reports/job-orders'],
            ['get', '/reports/inventory'],
            ['get', '/users'],
            ['post', '/users'],
        ];

        foreach ($blocked as [$method, $uri]) {
            $this->actingAs($staff)->{$method}($uri)->assertForbidden();
        }

        // Nothing changed.
        $this->assertSame(9.0, $ingredient->fresh()->stock_on_hand);
        $this->assertNull(PhysicalCount::find($count->id)->adjustment);
    }

    public function test_staff_can_view_stock_log_usage_and_count(): void
    {
        $owner = $this->owner();
        $staff = $this->staff();
        $ingredient = $this->ingredient(10);
        $job = $this->doneJob($owner);

        $this->actingAs($staff)->get('/inventory')->assertOk()->assertSee($ingredient->name);
        $this->actingAs($staff)->get("/inventory/{$ingredient->id}")->assertOk();
        $this->actingAs($staff)->get('/physical-counts/create')->assertOk();

        $this->actingAs($staff)->post("/job-orders/{$job->id}/stock-outs", [
            'out_date' => today()->toDateString(),
            'lines' => [['ingredient_id' => $ingredient->id, 'quantity' => 2]],
        ])->assertRedirect(route('jobOrders.show', $job));

        $this->assertSame(8.0, $ingredient->fresh()->stock_on_hand);
    }

    /** Odd values typed into the address bar are ignored instead of showing an error page. */
    public function test_hand_edited_addresses_do_not_crash(): void
    {
        $owner = $this->owner();
        $this->order($owner);

        $pages = [
            '/payments?from=abc&to=2026-02-31', '/payments?method[]=x&search[]=y',
            '/reports/sales?from=abc', '/reports/collections?to=31-31-2026', '/reports/inventory?type[]=x',
            '/orders?status[]=x', '/orders?search[]=x&type[]=y', '/orders/create?customer_id[]=1',
            '/customers?search[]=x&status[]=y', '/job-orders?search[]=x', '/inventory?filter[]=x&search[]=y',
            '/products?category[]=x', '/stock-ins?search[]=x', '/stock-ins/create?ingredient_id[]=1',
            '/stock-adjustments/create?ingredient_id[]=1',
        ];

        foreach ($pages as $page) {
            $this->actingAs($owner)->get($page)->assertOk();
        }

        $this->actingAs($owner)->post('/customers', ['first_name' => 'A', 'last_name' => 'B', 'contact_number' => ['0917']])
            ->assertSessionHasErrorsIn('createCustomer', ['contact_number']);
    }

    public function test_staff_menu_hides_owner_pages(): void
    {
        $this->actingAs($this->staff())->get('/dashboard')
            ->assertOk()
            ->assertDontSee(route('stockIns.index'))
            ->assertDontSee(route('adjustments.index'))
            ->assertDontSee(route('reports.index'))
            ->assertDontSee(route('users.index'))
            ->assertDontSee(route('products.index'));
    }

    public function test_owner_menu_shows_everything(): void
    {
        $this->actingAs($this->owner())->get('/dashboard')
            ->assertOk()
            ->assertSee(route('reports.index'))
            ->assertSee(route('users.index'))
            ->assertSee(route('products.index'));
    }

    public function test_inventory_tabs_follow_the_role(): void
    {
        // Stock in and Adjustments are Owner-only, so their tabs only show to the Owner.
        $this->actingAs($this->owner())->get('/inventory')
            ->assertOk()
            ->assertSee(route('stockIns.index'))
            ->assertSee(route('adjustments.index'));

        $this->actingAs($this->staff())->get('/inventory')
            ->assertOk()
            ->assertDontSee(route('stockIns.index'))
            ->assertDontSee(route('adjustments.index'));
    }
}
