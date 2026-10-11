<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Ingredient;
use App\Models\JobOrder;
use App\Models\Order;
use App\Models\PhysicalCount;
use App\Models\StockIn;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Opens every page with the sample data, as the Owner and as Staff. */
class PagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function sharedPages(): array
    {
        return [
            '/dashboard', '/orders', '/orders?status=all', '/orders?status=pending', '/orders/create',
            '/customers', '/customers?status=all', '/payments', '/job-orders', '/job-orders?status=all',
            '/inventory', '/inventory?filter=low', '/physical-counts', '/physical-counts/create',
            ...Order::pluck('id')->map(fn ($id) => "/orders/{$id}")->all(),
            ...Customer::pluck('id')->map(fn ($id) => "/customers/{$id}")->all(),
            ...JobOrder::pluck('id')->map(fn ($id) => "/job-orders/{$id}")->all(),
            ...Ingredient::pluck('id')->map(fn ($id) => "/inventory/{$id}")->all(),
            ...PhysicalCount::pluck('id')->map(fn ($id) => "/physical-counts/{$id}")->all(),
        ];
    }

    private function ownerPages(): array
    {
        return [
            '/products', '/stock-ins', '/stock-ins/create', '/stock-adjustments', '/stock-adjustments/create',
            '/reports', '/reports/sales', '/reports/collections', '/reports/job-orders', '/reports/inventory',
            '/reports/inventory?type=adjustment', '/users',
            ...StockIn::pluck('id')->map(fn ($id) => "/stock-ins/{$id}")->all(),
        ];
    }

    public function test_owner_can_open_every_page(): void
    {
        $owner = User::where('role', 'owner')->first();

        foreach ([...$this->sharedPages(), ...$this->ownerPages()] as $page) {
            $this->actingAs($owner)->get($page)->assertOk();
        }
    }

    public function test_staff_can_open_their_pages_and_not_the_owner_ones(): void
    {
        $staff = User::where('role', 'staff')->first();

        foreach ($this->sharedPages() as $page) {
            $this->actingAs($staff)->get($page)->assertOk();
        }

        foreach ($this->ownerPages() as $page) {
            $this->actingAs($staff)->get($page)->assertForbidden();
        }
    }

    public function test_sample_data_is_consistent(): void
    {
        foreach (Ingredient::withStock()->get() as $ingredient) {
            $this->assertGreaterThanOrEqual(0, $ingredient->stock_on_hand, $ingredient->name);
        }

        foreach (Order::withTotals()->get() as $order) {
            $this->assertLessThanOrEqual($order->total_amount, $order->total_paid, $order->order_number);

            if (in_array($order->status, ['confirmed', 'in_production', 'ready_for_release', 'out_for_delivery', 'completed'], true)) {
                $this->assertGreaterThanOrEqual($order->down_payment_required, $order->total_paid, $order->order_number);
            }
        }
    }
}
