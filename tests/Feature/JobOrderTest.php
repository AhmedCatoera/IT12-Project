<?php

namespace Tests\Feature;

use App\Models\JobOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\SweetNestData;
use Tests\TestCase;

class JobOrderTest extends TestCase
{
    use RefreshDatabase, SweetNestData;

    private function create($user, $order)
    {
        return $this->actingAs($user)->post("/orders/{$order->id}/job-order", [
            'production_date' => today()->toDateString(),
            'assigned_to' => $user->id,
        ]);
    }

    public function test_job_order_needs_the_down_payment_first(): void
    {
        $staff = $this->staff();
        $order = $this->order($staff);

        $this->create($staff, $order)->assertSessionHasErrorsIn('jobOrder', ['production_date']);
        $this->assertSame(0, JobOrder::count());
    }

    public function test_production_date_cannot_be_after_the_due_date(): void
    {
        $staff = $this->staff();
        $order = $this->confirmedOrder($staff);           // due in 3 days
        $due = $order->scheduled_at->toDateString();

        // The pop-up offers the due date and caps the date box at it.
        $this->actingAs($staff)->get("/orders/{$order->id}")->assertOk()->assertSee('max="' . $due . '"', false);

        $this->actingAs($staff)->post("/orders/{$order->id}/job-order", ['production_date' => $order->scheduled_at->copy()->addDay()->toDateString()])
            ->assertSessionHasErrorsIn('jobOrder', ['production_date']);
        $this->assertSame(0, JobOrder::count());

        // The due date itself is fine, and so is any day before it.
        $this->actingAs($staff)->post("/orders/{$order->id}/job-order", ['production_date' => $due])->assertSessionHasNoErrors();
        $this->assertSame($due, JobOrder::first()->production_date->toDateString());

        // A late order can still be baked today.
        $late = $this->confirmedOrder($staff, 1000, ['scheduled_at' => now()->subDays(2)->format('Y-m-d H:i:s')]);
        $this->actingAs($staff)->post("/orders/{$late->id}/job-order", ['production_date' => today()->toDateString()])->assertSessionHasNoErrors();
        $this->actingAs($staff)->post("/orders/{$late->id}/job-order", ['production_date' => today()->addDay()->toDateString()])
            ->assertSessionHasErrorsIn('jobOrder', ['production_date']);
    }

    public function test_due_date_cannot_move_before_the_job_order(): void
    {
        $staff = $this->staff();
        $order = $this->confirmedOrder($staff);           // due in 3 days
        $this->actingAs($staff)->post("/orders/{$order->id}/job-order", ['production_date' => $order->scheduled_at->toDateString()])->assertSessionHasNoErrors();

        $this->actingAs($staff)->put("/orders/{$order->id}", [
            'scheduled_at' => now()->addDay()->setTime(10, 0)->format('Y-m-d\TH:i'), 'order_type' => 'pickup',
        ])->assertSessionHasErrorsIn('editOrder', ['scheduled_at']);

        $this->actingAs($staff)->put("/orders/{$order->id}", [
            'scheduled_at' => now()->addDays(6)->setTime(10, 0)->format('Y-m-d\TH:i'), 'order_type' => 'pickup',
        ])->assertSessionHasNoErrors();
    }

    /** B8: one job order per order. */
    public function test_only_one_job_order_per_order(): void
    {
        $staff = $this->staff();
        $order = $this->confirmedOrder($staff);

        $this->create($staff, $order)->assertSessionHasNoErrors();
        $this->create($staff, $order)->assertSessionHasErrorsIn('jobOrder', ['production_date']);
        $this->assertSame(1, JobOrder::count());
    }

    public function test_job_order_moves_the_order_along(): void
    {
        $staff = $this->staff();
        $order = $this->confirmedOrder($staff);
        $this->create($staff, $order);
        $job = JobOrder::first();

        $this->actingAs($staff)->patch("/job-orders/{$job->id}/status", ['action' => 'start'])->assertSessionHasNoErrors();
        $this->assertSame('in_progress', $job->fresh()->status);
        $this->assertNotNull($job->fresh()->started_at);
        $this->assertSame('in_production', $order->fresh()->status);

        $this->actingAs($staff)->patch("/job-orders/{$job->id}/status", ['action' => 'complete'])->assertSessionHasNoErrors();
        $this->assertSame('completed', $job->fresh()->status);
        $this->assertNotNull($job->fresh()->completed_at);
        $this->assertSame('ready_for_release', $order->fresh()->status);
    }

    public function test_a_job_order_must_start_before_it_is_done(): void
    {
        $staff = $this->staff();
        $job = $this->jobOrder($this->confirmedOrder($staff));

        $this->actingAs($staff)->patch("/job-orders/{$job->id}/status", ['action' => 'complete'])->assertSessionHasErrors('action');
        $this->assertSame('pending', $job->fresh()->status);
    }

    /** B9: cancelling the job order puts the order back to Confirmed, and it can be reopened. */
    public function test_cancelled_job_order_returns_the_order_to_confirmed(): void
    {
        $staff = $this->staff();
        $job = $this->startedJob($staff);

        $this->actingAs($staff)->patch("/job-orders/{$job->id}/status", ['action' => 'cancel']);
        $this->assertSame('cancelled', $job->fresh()->status);
        $this->assertSame('confirmed', $job->order->fresh()->status);

        $this->actingAs($staff)->patch("/job-orders/{$job->id}/status", ['action' => 'reopen'])->assertSessionHasNoErrors();
        $this->assertSame('pending', $job->fresh()->status);
    }

    public function test_job_order_of_a_cancelled_order_cannot_be_reopened(): void
    {
        $staff = $this->staff();
        $job = $this->startedJob($staff);
        $this->actingAs($staff)->patch("/orders/{$job->order_id}/status", ['status' => 'cancelled', 'cancel_reason' => 'Test']);

        $this->actingAs($staff)->patch("/job-orders/{$job->id}/status", ['action' => 'reopen'])->assertSessionHasErrors('action');
        $this->assertSame('cancelled', $job->fresh()->status);
    }

    /** After an error, the pop-up on the Job Orders page still points at the order that was picked. */
    public function test_job_order_popup_keeps_the_right_order_after_an_error(): void
    {
        $staff = $this->staff();
        $picked = $this->confirmedOrder($staff);
        $listedLast = $this->confirmedOrder($staff, 1000, ['scheduled_at' => now()->addDays(9)->format('Y-m-d H:i:s')]);
        $inactive = $this->staff(['is_active' => false]);

        $this->actingAs($staff)->from('/job-orders')->followingRedirects()
            ->post("/orders/{$picked->id}/job-order", ['production_date' => today()->toDateString(), 'assigned_to' => $inactive->id, '_order_id' => $picked->id])
            ->assertOk()
            ->assertSee('<form method="POST" action="' . route('jobOrders.store', $picked) . '">', false)
            ->assertDontSee('<form method="POST" action="' . route('jobOrders.store', $listedLast) . '">', false)
            ->assertSee('Job order for ' . $picked->order_number);
    }

    public function test_start_is_refused_if_the_order_was_cancelled_meanwhile(): void
    {
        $staff = $this->staff();
        $job = $this->jobOrder($this->confirmedOrder($staff));
        $stale = JobOrder::find($job->id);

        $this->actingAs($staff)->patch("/orders/{$job->order_id}/status", ['status' => 'cancelled', 'cancel_reason' => 'Test']);
        $this->actingAs($staff)->patch("/job-orders/{$stale->id}/status", ['action' => 'start'])->assertSessionHasErrors('action');

        $this->assertSame('cancelled', $job->fresh()->status);
    }

    public function test_job_order_page_lists_confirmed_orders_waiting(): void
    {
        $staff = $this->staff();
        $waiting = $this->confirmedOrder($staff);

        $this->actingAs($staff)->get('/job-orders')->assertOk()->assertSee($waiting->order_number);
        $this->actingAs($staff)->get('/job-orders/' . $this->startedJob($staff)->id)->assertOk()->assertDontSee('Log ingredients used')->assertSee('marked as done');
        $this->actingAs($staff)->get('/job-orders/' . $this->doneJob($staff)->id)->assertOk()->assertSee('Log ingredients used');
    }
}
