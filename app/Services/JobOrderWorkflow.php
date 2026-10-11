<?php

namespace App\Services;

use App\Models\JobOrder;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The job order is the only thing that moves an order into "In production"
 * and "Ready for release", so the two statuses can never drift apart.
 */
class JobOrderWorkflow
{
    public const ACTIONS = [
        'start' => ['from' => ['pending'], 'label' => 'Start baking'],
        'complete' => ['from' => ['in_progress'], 'label' => 'Mark as done'],
        'cancel' => ['from' => ['pending', 'in_progress'], 'label' => 'Cancel job order'],
        'reopen' => ['from' => ['cancelled'], 'label' => 'Reopen'],
    ];

    public function create(Order $order, array $data): JobOrder
    {
        return DB::transaction(function () use ($order, $data) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->status !== 'confirmed') {
                throw ValidationException::withMessages([
                    'production_date' => 'Only confirmed orders (down payment received) can get a job order.',
                ])->errorBag('jobOrder');
            }

            if (JobOrder::where('order_id', $order->id)->exists()) {
                throw ValidationException::withMessages([
                    'production_date' => 'This order already has a job order.',
                ])->errorBag('jobOrder');
            }

            return JobOrder::create([
                'order_id' => $order->id,
                'assigned_to' => $data['assigned_to'] ?? null,
                'production_date' => $data['production_date'],
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    /** Actions a person can take on this job order right now. */
    public static function availableActions(JobOrder $job): array
    {
        $actions = [];

        foreach (self::ACTIONS as $key => $action) {
            if (! in_array($job->status, $action['from'], true)) {
                continue;
            }

            if ($key === 'reopen' && $job->order?->status !== 'confirmed') {
                continue;
            }

            $actions[$key] = $action['label'];
        }

        return $actions;
    }

    public function apply(JobOrder $job, string $action): void
    {
        DB::transaction(function () use ($job, $action) {
            // Re-read and lock both rows, then check again: the order may have been cancelled
            // (or the job started) by someone else since the page was opened.
            $job = JobOrder::whereKey($job->id)->lockForUpdate()->firstOrFail();
            $order = Order::whereKey($job->order_id)->lockForUpdate()->firstOrFail();
            $job->setRelation('order', $order);

            if (! array_key_exists($action, self::availableActions($job))) {
                throw ValidationException::withMessages([
                    'action' => 'This job order is "' . $job->status_label . '", so that step is not allowed.',
                ]);
            }

            switch ($action) {
                case 'start':
                    $job->update(['status' => 'in_progress', 'started_at' => now()]);
                    if ($order->status === 'confirmed') {
                        $order->update(['status' => 'in_production']);
                    }
                    break;

                case 'complete':
                    $job->update(['status' => 'completed', 'completed_at' => now()]);
                    if ($order->status === 'in_production') {
                        $order->update(['status' => 'ready_for_release']);
                    }
                    break;

                case 'cancel':
                    $job->update(['status' => 'cancelled']);
                    if (in_array($order->status, ['in_production', 'confirmed'], true)) {
                        $order->update(['status' => 'confirmed']);
                    }
                    break;

                case 'reopen':
                    $job->update(['status' => 'pending', 'started_at' => null, 'completed_at' => null]);
                    break;
            }
        });
    }
}
