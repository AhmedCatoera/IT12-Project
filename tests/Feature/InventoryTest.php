<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\PhysicalCount;
use App\Models\StockAdjustment;
use App\Models\StockIn;
use App\Models\StockOut;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\SweetNestData;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase, SweetNestData;

    // ---------------- Stock in (Owner only) ----------------

    public function test_one_receipt_can_hold_many_ingredients(): void
    {
        $owner = $this->owner();
        $flour = $this->ingredient();
        $sugar = $this->ingredient();

        $this->actingAs($owner)->post('/stock-ins', [
            'receipt_no' => 'OR-555',
            'supplier_name' => 'Davao Baking Supplies',
            'received_date' => today()->toDateString(),
            'lines' => [
                ['ingredient_id' => $flour->id, 'quantity' => 25, 'unit_cost' => 60],
                ['ingredient_id' => $sugar->id, 'quantity' => 10.5, 'unit_cost' => 80, 'expiration_date' => today()->addMonths(6)->toDateString()],
            ],
        ])->assertSessionHasNoErrors();

        $receipt = StockIn::with('items')->sole();
        $this->assertCount(2, $receipt->items);
        $this->assertSame($owner->id, $receipt->received_by);
        $this->assertSame(2340.0, $receipt->total_cost);
        $this->assertSame(25.0, $flour->fresh()->stock_on_hand);
        $this->assertSame(10.5, $sugar->fresh()->stock_on_hand);
    }

    public function test_stock_in_rejects_repeated_ingredients_and_bad_expiry(): void
    {
        $owner = $this->owner();
        $flour = $this->ingredient();

        $this->actingAs($owner)->post('/stock-ins', [
            'received_date' => today()->toDateString(),
            'lines' => [
                ['ingredient_id' => $flour->id, 'quantity' => 1],
                ['ingredient_id' => $flour->id, 'quantity' => 2, 'expiration_date' => today()->subDay()->toDateString()],
            ],
        ])->assertSessionHasErrors(['lines.1.ingredient_id', 'lines.1.expiration_date']);

        $this->assertSame(0, StockIn::count());
    }

    public function test_stock_in_is_owner_only(): void
    {
        $flour = $this->ingredient();

        $this->actingAs($this->staff())->post('/stock-ins', [
            'received_date' => today()->toDateString(),
            'lines' => [['ingredient_id' => $flour->id, 'quantity' => 5]],
        ])->assertForbidden();

        $this->assertSame(0.0, $flour->fresh()->stock_on_hand);
    }

    public function test_inactive_ingredients_cannot_be_received(): void
    {
        $old = $this->ingredient(0, ['is_active' => false]);

        $this->actingAs($this->owner())->post('/stock-ins', [
            'received_date' => today()->toDateString(),
            'lines' => [['ingredient_id' => $old->id, 'quantity' => 5]],
        ])->assertSessionHasErrors('lines.0.ingredient_id');
    }

    // ---------------- Stock out (ingredients used on a job order) ----------------

    public function test_staff_log_ingredients_used_on_a_finished_job_order(): void
    {
        $staff = $this->staff();
        $flour = $this->ingredient(10);
        $eggs = $this->ingredient(30, ['unit' => 'pcs']);
        $job = $this->doneJob($staff);

        $this->actingAs($staff)->post("/job-orders/{$job->id}/stock-outs", [
            'out_date' => today()->toDateString(),
            'lines' => [
                ['ingredient_id' => $flour->id, 'quantity' => 1.5],
                ['ingredient_id' => $eggs->id, 'quantity' => 6],
            ],
        ])->assertSessionHasNoErrors();

        $stockOut = StockOut::with('items')->sole();
        $this->assertSame($job->id, $stockOut->job_order_id);
        $this->assertCount(2, $stockOut->items);
        $this->assertSame(8.5, $flour->fresh()->stock_on_hand);
        $this->assertSame(24.0, $eggs->fresh()->stock_on_hand);
    }

    public function test_usage_needs_the_job_order_to_be_done(): void
    {
        $staff = $this->staff();
        $flour = $this->ingredient(10);
        $lines = ['out_date' => today()->toDateString(), 'lines' => [['ingredient_id' => $flour->id, 'quantity' => 1]]];

        // Not started yet, and still baking: too early. Ingredients are logged once it is marked done.
        $waiting = $this->jobOrder($this->confirmedOrder($staff));
        $this->actingAs($staff)->post("/job-orders/{$waiting->id}/stock-outs", $lines)->assertSessionHasErrors('lines');
        $baking = $this->startedJob($staff);
        $this->actingAs($staff)->post("/job-orders/{$baking->id}/stock-outs", $lines)->assertSessionHasErrors('lines');
        $this->assertSame(10.0, $flour->fresh()->stock_on_hand);

        $done = $this->doneJob($staff);
        $this->actingAs($staff)->post("/job-orders/{$done->id}/stock-outs", $lines)->assertSessionHasNoErrors();
        $this->assertSame(9.0, $flour->fresh()->stock_on_hand);
    }

    /** B5: stock can never go below zero. */
    public function test_cannot_use_more_than_what_is_in_stock(): void
    {
        $staff = $this->staff();
        $flour = $this->ingredient(10);
        $sugar = $this->ingredient(2);
        $job = $this->doneJob($staff);

        $this->actingAs($staff)->post("/job-orders/{$job->id}/stock-outs", [
            'out_date' => today()->toDateString(),
            'lines' => [
                3 => ['ingredient_id' => $flour->id, 'quantity' => 1],
                7 => ['ingredient_id' => $sugar->id, 'quantity' => 2.5],
            ],
        ])->assertSessionHasErrors('lines.7.quantity');

        $this->assertSame(0, StockOut::count());
        $this->assertSame(10.0, $flour->fresh()->stock_on_hand);
    }

    public function test_owner_can_fix_or_remove_a_wrong_usage_line(): void
    {
        $owner = $this->owner();
        $flour = $this->ingredient(10);
        $sugar = $this->ingredient(10);
        $job = $this->doneJob($owner);
        $stockOut = app(InventoryService::class)->recordStockOut($job, [
            ['ingredient_id' => $flour->id, 'quantity' => 5],
            ['ingredient_id' => $sugar->id, 'quantity' => 1],
        ], today()->toDateString(), null, $owner);
        [$flourLine, $sugarLine] = $stockOut->items()->orderBy('id')->get()->all();

        // Staff typed 5 kg but only 0.5 kg was used.
        $this->actingAs($owner)->put("/stock-out-items/{$flourLine->id}", ['quantity' => 0.5])->assertSessionHasNoErrors();
        $this->assertSame(9.5, $flour->fresh()->stock_on_hand);

        // Can't raise it above what is available.
        $this->actingAs($owner)->put("/stock-out-items/{$flourLine->id}", ['quantity' => 11])->assertSessionHasErrorsIn('editUsage', ['quantity']);

        // Remove both lines: stock is restored and the empty entry disappears.
        $this->actingAs($owner)->delete("/stock-out-items/{$sugarLine->id}")->assertSessionHasNoErrors();
        $this->actingAs($owner)->delete("/stock-out-items/{$flourLine->id}");
        $this->assertSame(10.0, $sugar->fresh()->stock_on_hand);
        $this->assertSame(10.0, $flour->fresh()->stock_on_hand);
        $this->assertSame(0, StockOut::count());
    }

    // ---------------- Adjustments (Owner only) ----------------

    public function test_spoilage_can_only_remove_stock(): void
    {
        $milk = $this->ingredient(10, ['unit' => 'L']);

        $this->actingAs($this->owner())->post('/stock-adjustments', [
            'reason' => 'spoilage',
            'adjusted_date' => today()->toDateString(),
            'remarks' => 'Left out overnight',
            'lines' => [['ingredient_id' => $milk->id, 'direction' => 'add', 'quantity' => 1]],
        ])->assertSessionHasErrors('lines.0.direction');

        $this->assertSame(10.0, $milk->fresh()->stock_on_hand);
    }

    public function test_spoilage_removes_stock_and_needs_remarks(): void
    {
        $owner = $this->owner();
        $milk = $this->ingredient(10, ['unit' => 'L']);
        $form = [
            'reason' => 'spoilage',
            'adjusted_date' => today()->toDateString(),
            'remarks' => '',
            'lines' => [['ingredient_id' => $milk->id, 'direction' => 'remove', 'quantity' => 1.5]],
        ];

        $this->actingAs($owner)->post('/stock-adjustments', $form)->assertSessionHasErrors('remarks');

        $form['remarks'] = 'Left out overnight';
        $this->actingAs($owner)->post('/stock-adjustments', $form)->assertSessionHasNoErrors();

        $this->assertSame(8.5, $milk->fresh()->stock_on_hand);
        $this->assertSame(-1.5, (float) StockAdjustment::sole()->items()->sole()->quantity);
    }

    public function test_adjustment_cannot_remove_more_than_on_hand(): void
    {
        $milk = $this->ingredient(1);

        $this->actingAs($this->owner())->post('/stock-adjustments', [
            'reason' => 'damaged',
            'adjusted_date' => today()->toDateString(),
            'remarks' => 'Dropped',
            'lines' => [['ingredient_id' => $milk->id, 'direction' => 'remove', 'quantity' => 2]],
        ])->assertSessionHasErrors('lines.0.quantity');

        $this->assertSame(1.0, $milk->fresh()->stock_on_hand);
    }

    public function test_entry_correction_can_add_stock_but_count_corrections_come_only_from_counts(): void
    {
        $owner = $this->owner();
        $flour = $this->ingredient(10);

        $this->actingAs($owner)->post('/stock-adjustments', [
            'reason' => 'entry_correction',
            'adjusted_date' => today()->toDateString(),
            'remarks' => 'Receipt said 12 kg, I typed 10',
            'lines' => [['ingredient_id' => $flour->id, 'direction' => 'add', 'quantity' => 2]],
        ])->assertSessionHasNoErrors();
        $this->assertSame(12.0, $flour->fresh()->stock_on_hand);

        $this->actingAs($owner)->post('/stock-adjustments', [
            'reason' => 'count_correction',
            'adjusted_date' => today()->toDateString(),
            'remarks' => 'Trying to skip the count',
            'lines' => [['ingredient_id' => $flour->id, 'direction' => 'add', 'quantity' => 2]],
        ])->assertSessionHasErrors('reason');
    }

    // ---------------- Physical count ----------------

    public function test_count_form_hides_the_system_quantity(): void
    {
        $this->ingredient(123.45, ['name' => 'Cocoa Powder']);

        $this->actingAs($this->staff())->get('/physical-counts/create')
            ->assertOk()
            ->assertSee('Cocoa Powder')
            ->assertDontSee('123.45');
    }

    public function test_staff_count_and_owner_applies_the_differences(): void
    {
        $owner = $this->owner();
        $staff = $this->staff();
        $eggs = $this->ingredient(30, ['unit' => 'pcs']);
        $flour = $this->ingredient(10);

        $this->actingAs($staff)->post('/physical-counts', [
            'count_date' => today()->toDateString(),
            'shift' => 'night',
            'counts' => [$eggs->id => 28, $flour->id => 10],
        ])->assertSessionHasNoErrors();

        $count = PhysicalCount::with('items')->sole();
        $this->assertSame(30.0, $count->items->firstWhere('ingredient_id', $eggs->id)->system_qty);
        $this->assertSame(30.0, $eggs->fresh()->stock_on_hand); // nothing changes until the Owner applies it

        // Staff see their counts but not the system numbers.
        $this->actingAs($staff)->get("/physical-counts/{$count->id}")->assertOk()->assertDontSee('Apply corrections')->assertDontSee('System');
        $this->actingAs($owner)->get("/physical-counts/{$count->id}")->assertOk()->assertSee('Apply corrections');

        $this->actingAs($owner)->post("/physical-counts/{$count->id}/apply")->assertSessionHas('success');

        $this->assertSame(28.0, $eggs->fresh()->stock_on_hand);
        $this->assertSame(10.0, $flour->fresh()->stock_on_hand);
        $adjustment = StockAdjustment::with('items')->sole();
        $this->assertSame('count_correction', $adjustment->reason);
        $this->assertSame($count->id, $adjustment->physical_count_id);
        $this->assertCount(1, $adjustment->items); // only the ingredient that differed

        // Applying twice does nothing.
        $this->actingAs($owner)->post("/physical-counts/{$count->id}/apply")->assertSessionHas('error');
        $this->assertSame(1, StockAdjustment::count());
    }

    /** A count is always for today, so its system numbers match the moment it was counted. */
    public function test_counts_are_always_dated_today(): void
    {
        $flour = $this->ingredient(10);

        $this->actingAs($this->staff())->post('/physical-counts', [
            'count_date' => today()->subDays(3)->toDateString(),
            'shift' => 'night',
            'counts' => [$flour->id => 10],
        ])->assertSessionHasNoErrors();

        $this->assertTrue(PhysicalCount::sole()->count_date->isToday());
    }

    /** Two pending counts showing the same shortage must not be applied twice. */
    public function test_only_the_newest_count_can_be_applied(): void
    {
        $owner = $this->owner();
        $staff = $this->staff();
        $flour = $this->ingredient(10);

        $this->actingAs($staff)->post('/physical-counts', ['shift' => 'morning', 'counts' => [$flour->id => 8]]);
        $this->actingAs($staff)->post('/physical-counts', ['shift' => 'night', 'counts' => [$flour->id => 8]]);
        [$morning, $night] = PhysicalCount::orderBy('id')->get()->all();

        $this->actingAs($owner)->post("/physical-counts/{$morning->id}/apply")->assertSessionHas('error');
        $this->actingAs($owner)->get("/physical-counts/{$morning->id}")->assertSee('a newer count')->assertDontSee('Apply corrections');

        $this->actingAs($owner)->post("/physical-counts/{$night->id}/apply")->assertSessionHas('success');
        $this->assertSame(8.0, $flour->fresh()->stock_on_hand);
        $this->assertSame(1, StockAdjustment::count());
    }

    public function test_owner_can_correct_usage_to_exactly_what_was_available(): void
    {
        $owner = $this->owner();
        $fondant = $this->ingredient(0.8);
        $job = $this->doneJob($owner);
        $line = app(InventoryService::class)->recordStockOut($job, [['ingredient_id' => $fondant->id, 'quantity' => 0.1]], today()->toDateString(), null, $owner)->items()->sole();

        $this->actingAs($owner)->put("/stock-out-items/{$line->id}", ['quantity' => 0.8])->assertSessionHasNoErrors();
        $this->assertSame(0.0, $fondant->fresh()->stock_on_hand);
    }

    public function test_same_shift_cannot_be_counted_twice_on_one_day(): void
    {
        $staff = $this->staff();
        $flour = $this->ingredient(10);
        $form = ['count_date' => today()->toDateString(), 'shift' => 'morning', 'counts' => [$flour->id => 10]];

        $this->actingAs($staff)->post('/physical-counts', $form)->assertSessionHasNoErrors();
        $this->actingAs($staff)->post('/physical-counts', $form)->assertSessionHasErrors('shift');

        $this->assertSame(1, PhysicalCount::count());
    }

    public function test_every_active_ingredient_must_be_counted(): void
    {
        $flour = $this->ingredient(10);
        $sugar = $this->ingredient(10);

        $this->actingAs($this->staff())->post('/physical-counts', [
            'count_date' => today()->toDateString(),
            'shift' => 'morning',
            'counts' => [$flour->id => 10],
        ])->assertSessionHasErrors("counts.{$sugar->id}");
    }

    public function test_a_count_that_matched_has_nothing_to_apply(): void
    {
        $owner = $this->owner();
        $flour = $this->ingredient(10);
        $count = app(InventoryService::class)->recordCount(['count_date' => today()->toDateString(), 'shift' => 'morning'], [$flour->id => 10], $owner);

        $this->actingAs($owner)->post("/physical-counts/{$count->id}/apply")->assertSessionHas('error');
        $this->assertSame(0, StockAdjustment::count());
    }

    // ---------------- Stock on hand ----------------

    public function test_stock_on_hand_is_always_in_minus_out_plus_adjustments(): void
    {
        $owner = $this->owner();
        $flour = $this->ingredient(20, ['reorder_level' => 15]);
        $job = $this->doneJob($owner);
        $inventory = app(InventoryService::class);

        $inventory->recordStockOut($job, [['ingredient_id' => $flour->id, 'quantity' => 4]], today()->toDateString(), null, $owner);
        $inventory->recordAdjustment(['reason' => 'spoilage', 'adjusted_date' => today()->toDateString(), 'remarks' => 'Wet'], [['ingredient_id' => $flour->id, 'quantity' => -2]], $owner);

        $this->assertSame(14.0, Ingredient::currentStock($flour->id));
        $this->assertSame(14.0, Ingredient::withStock()->find($flour->id)->stock_on_hand);
        $this->assertTrue(Ingredient::lowStock()->whereKey($flour->id)->exists());

        $this->actingAs($owner)->get("/inventory/{$flour->id}")->assertOk()->assertSee('Spoilage')->assertSee('+20');
    }

    public function test_ingredient_quantity_cannot_be_typed_in_directly(): void
    {
        $owner = $this->owner();
        $flour = $this->ingredient(10);

        $this->actingAs($owner)->put("/inventory/{$flour->id}", [
            'name' => $flour->name,
            'unit' => 'kg',
            'reorder_level' => 5,
            'is_active' => 1,
            'current_stock' => 999,
        ])->assertSessionHasNoErrors();

        $this->assertSame(10.0, $flour->fresh()->stock_on_hand);
        $this->assertSame(5.0, $flour->fresh()->reorder_level);
    }
}
