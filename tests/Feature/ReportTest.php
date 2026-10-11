<?php

namespace Tests\Feature;

use App\Services\InventoryService;
use App\Services\JobOrderWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\SweetNestData;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase, SweetNestData;

    /** B3: totals come from the whole period, not the 20 rows on the page. */
    public function test_sales_totals_cover_every_order_in_the_period(): void
    {
        $owner = $this->owner();

        for ($i = 0; $i < 22; $i++) {
            $this->order($owner, 100);
        }
        $cancelled = $this->order($owner, 5000);
        $cancelled->update(['status' => 'cancelled', 'cancel_reason' => 'Test']);

        $this->actingAs($owner)->get('/reports/sales?from=' . today()->toDateString() . '&to=' . today()->toDateString())
            ->assertOk()
            ->assertSee('₱2,200.00')   // 22 orders x 100, cancelled one left out
            ->assertSee('22 orders')
            ->assertDontSee('₱7,200.00');
    }

    /** Orders taken today appear in a report that ends today (date-only columns compare correctly). */
    public function test_report_period_includes_its_last_day(): void
    {
        $owner = $this->owner();
        $order = $this->order($owner, 750);

        $this->actingAs($owner)->get('/reports/sales')->assertSee($order->order_number)->assertSee('₱750.00');
        $this->actingAs($owner)->get('/reports')->assertSee('₱750.00');
    }

    /** B12: collections are split by type, including full payments. */
    public function test_collections_are_split_by_method_and_type(): void
    {
        $owner = $this->owner();
        $this->pay($this->order($owner, 1000), 500, $owner, 'gcash');      // down payment
        $this->pay($this->order($owner, 800), 800, $owner, 'cash');        // full payment
        $partly = $this->order($owner, 600);
        $this->pay($partly, 300, $owner, 'cash');
        $this->pay($partly->fresh(), 300, $owner, 'gcash');                // balance payment

        $response = $this->actingAs($owner)->get('/reports/collections');
        $response->assertOk()->assertSee('₱1,900.00');

        $summary = $response->viewData('summary');
        $this->assertEquals(800.0, (float) $summary['by_type']['full_payment']->total);
        $this->assertEquals(800.0, (float) $summary['by_type']['down_payment']->total);
        $this->assertEquals(300.0, (float) $summary['by_type']['balance_payment']->total);
        $this->assertEquals(1100.0, (float) $summary['by_method']['cash']->total);
    }

    public function test_job_order_report_counts_each_status(): void
    {
        $owner = $this->owner();
        $done = $this->startedJob($owner);
        app(JobOrderWorkflow::class)->apply($done, 'complete');
        $this->startedJob($owner);
        $this->jobOrder($this->confirmedOrder($owner));

        $summary = $this->actingAs($owner)->get('/reports/job-orders')->assertOk()->viewData('summary');

        $this->assertSame(3, $summary['total']);
        $this->assertSame(1, $summary['completed']);
        $this->assertSame(1, $summary['on_time']);
        $this->assertSame(2, $summary['open']);
    }

    public function test_inventory_report_shows_usage_and_purchases(): void
    {
        $owner = $this->owner();
        $flour = $this->ingredient(10); // costs 10 each => 100
        $job = $this->doneJob($owner);
        app(InventoryService::class)->recordStockOut($job, [['ingredient_id' => $flour->id, 'quantity' => 3]], today()->toDateString(), null, $owner);

        $response = $this->actingAs($owner)->get('/reports/inventory?from=' . today()->subDays(3)->toDateString() . '&to=' . today()->toDateString());

        $response->assertOk()->assertSee('₱100.00');
        $usage = $response->viewData('usage');
        $this->assertSame($flour->name, $usage->first()->name);
        $this->assertEquals(3.0, (float) $usage->first()->total);
        $this->assertSame(2, $response->viewData('movements')->total()); // one stock in, one used
    }

    public function test_a_backwards_date_range_is_swapped(): void
    {
        $owner = $this->owner();

        $response = $this->actingAs($owner)->get('/reports/sales?from=' . today()->toDateString() . '&to=' . today()->subMonth()->toDateString());

        $response->assertOk();
        $this->assertTrue($response->viewData('from')->lte($response->viewData('to')));
    }
}
