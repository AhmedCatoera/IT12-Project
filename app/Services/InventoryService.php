<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\JobOrder;
use App\Models\PhysicalCount;
use App\Models\StockAdjustment;
use App\Models\StockIn;
use App\Models\StockOut;
use App\Models\StockOutItem;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Every stock movement is a document with lines:
 *   stock in (receipt), stock out (ingredients used on a job order),
 *   stock adjustment (Owner corrections, signed quantities).
 * Stock on hand is computed from those lines, so it can never drift.
 */
class InventoryService
{
    /**
     * @param  array<int, array{ingredient_id:int, quantity:float}>  $lines
     */
    public function recordStockOut(JobOrder $job, array $lines, string $date, ?string $remarks, User $by): StockOut
    {
        return DB::transaction(function () use ($job, $lines, $date, $remarks, $by) {
            $job = JobOrder::whereKey($job->id)->lockForUpdate()->firstOrFail();

            if (! $job->canLogUsage()) {
                throw ValidationException::withMessages([
                    'lines' => $job->status === 'cancelled'
                        ? 'This job order was cancelled, so ingredients can no longer be logged on it.'
                        : 'Mark the job order as done first, then log the ingredients used.',
                ]);
            }

            $this->assertAvailable($lines);

            $stockOut = StockOut::create([
                'job_order_id' => $job->id,
                'out_date' => $date,
                'recorded_by' => $by->id,
                'remarks' => $remarks,
            ]);

            foreach ($lines as $line) {
                $stockOut->items()->create([
                    'ingredient_id' => $line['ingredient_id'],
                    'quantity' => round((float) $line['quantity'], 2),
                ]);
            }

            return $stockOut;
        });
    }

    /** Owner fixes a wrong usage line. Lowering it gives the stock back automatically. */
    public function updateStockOutItem(StockOutItem $item, float $quantity): void
    {
        DB::transaction(function () use ($item, $quantity) {
            $this->lock([$item->ingredient_id]);
            $available = round(Ingredient::currentStock($item->ingredient_id) + $item->quantity, 2);

            if (round($quantity, 2) > $available + 0.0001) {
                throw ValidationException::withMessages([
                    'quantity' => 'Only ' . $this->qty($available) . ' ' . $item->ingredient->unit . ' can be used.',
                ])->errorBag('editUsage');
            }

            $item->update(['quantity' => round($quantity, 2)]);
        });
    }

    public function deleteStockOutItem(StockOutItem $item): void
    {
        DB::transaction(function () use ($item) {
            $stockOut = $item->stockOut;
            $item->delete();

            if (! $stockOut->items()->exists()) {
                $stockOut->delete();
            }
        });
    }

    public function recordStockIn(array $header, array $lines, User $by): StockIn
    {
        return DB::transaction(function () use ($header, $lines, $by) {
            $stockIn = StockIn::create([
                'receipt_no' => $header['receipt_no'] ?? null,
                'supplier_name' => $header['supplier_name'] ?? null,
                'received_date' => $header['received_date'],
                'received_by' => $by->id,
                'remarks' => $header['remarks'] ?? null,
            ]);

            foreach ($lines as $line) {
                $stockIn->items()->create([
                    'ingredient_id' => $line['ingredient_id'],
                    'quantity' => round((float) $line['quantity'], 2),
                    'unit_cost' => isset($line['unit_cost']) && $line['unit_cost'] !== '' ? round((float) $line['unit_cost'], 2) : null,
                    'expiration_date' => $line['expiration_date'] ?? null,
                ]);
            }

            return $stockIn;
        });
    }

    /**
     * @param  array<int, array{ingredient_id:int, quantity:float}>  $lines  signed quantities
     */
    public function recordAdjustment(array $header, array $lines, User $by, ?PhysicalCount $count = null): StockAdjustment
    {
        return DB::transaction(function () use ($header, $lines, $by, $count) {
            // Keep the original row keys so an error lands on the right form row.
            $removals = array_filter(
                array_map(fn ($l) => ['ingredient_id' => $l['ingredient_id'], 'quantity' => -1 * (float) $l['quantity']], $lines),
                fn ($l) => $l['quantity'] > 0
            );
            $this->assertAvailable($removals, 'lines');

            $adjustment = StockAdjustment::create([
                'physical_count_id' => $count?->id,
                'reason' => $header['reason'],
                'adjusted_date' => $header['adjusted_date'],
                'adjusted_by' => $by->id,
                'remarks' => $header['remarks'],
            ]);

            foreach ($lines as $line) {
                $adjustment->items()->create([
                    'ingredient_id' => $line['ingredient_id'],
                    'quantity' => round((float) $line['quantity'], 2),
                ]);
            }

            return $adjustment;
        });
    }

    /**
     * @param  array<int, float>  $counted  ingredient_id => counted quantity
     */
    public function recordCount(array $header, array $counted, User $by): PhysicalCount
    {
        return DB::transaction(function () use ($header, $counted, $by) {
            $count = PhysicalCount::create([
                'count_date' => $header['count_date'],
                'shift' => $header['shift'],
                'counted_by' => $by->id,
                'notes' => $header['notes'] ?? null,
            ]);

            foreach ($counted as $ingredientId => $qty) {
                $count->items()->create([
                    'ingredient_id' => $ingredientId,
                    'system_qty' => Ingredient::currentStock((int) $ingredientId),
                    'counted_qty' => round((float) $qty, 2),
                ]);
            }

            return $count;
        });
    }

    /** Owner applies the count: one adjustment with a line per difference. */
    public function applyCount(PhysicalCount $count, User $by): ?StockAdjustment
    {
        if ($count->adjustment()->exists()) {
            throw ValidationException::withMessages(['count' => 'This count has already been applied.']);
        }

        // The differences are measured against the stock when this count was saved. Once a
        // newer count exists, this one is out of date (applying both would correct twice).
        if ($newer = $count->newerCount()) {
            throw ValidationException::withMessages([
                'count' => "A newer count ({$newer->number}) was saved after this one, so this one can no longer be applied.",
            ]);
        }

        $lines = $count->items
            ->filter(fn ($item) => abs($item->variance) >= 0.01)
            ->map(fn ($item) => ['ingredient_id' => $item->ingredient_id, 'quantity' => $item->variance])
            ->values()
            ->all();

        if ($lines === []) {
            throw ValidationException::withMessages(['count' => 'Every count matched the system, so there is nothing to correct.']);
        }

        return $this->recordAdjustment([
            'reason' => 'count_correction',
            'adjusted_date' => now()->toDateString(),
            'remarks' => 'Correction from ' . $count->shift_label . ' count on ' . $count->count_date->format('M d, Y') . '.',
        ], $lines, $by, $count);
    }

    /**
     * Refuses quantities above stock on hand (stock can never go below zero).
     *
     * @param  array<int, array{ingredient_id:int, quantity:float}>  $lines
     */
    public function assertAvailable(array $lines, string $field = 'lines'): void
    {
        if ($lines === []) {
            return;
        }

        $ids = array_map(fn ($l) => (int) $l['ingredient_id'], $lines);
        $ingredients = $this->lock($ids);
        $errors = [];

        foreach ($lines as $index => $line) {
            $ingredient = $ingredients[(int) $line['ingredient_id']] ?? null;
            if (! $ingredient) {
                continue;
            }

            $available = Ingredient::currentStock($ingredient->id);

            if ((float) $line['quantity'] > $available + 0.0001) {
                $errors["{$field}.{$index}.quantity"] = 'Only ' . $this->qty($available) . ' ' . $ingredient->unit
                    . ' of ' . $ingredient->name . ' is in stock.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Stock movement history across all three document types.
     */
    public function movements(): Builder
    {
        $in = DB::table('stock_in_items as l')
            ->join('stock_ins as h', 'h.id', '=', 'l.stock_in_id')
            ->selectRaw("'stock_in' as movement, h.id as doc_id, h.received_date as movement_date, l.ingredient_id,
                l.quantity as quantity, l.unit_cost as unit_cost, h.received_by as user_id, h.receipt_no as reference,
                NULL as job_order_id, NULL as reason, l.created_at as created_at");

        $out = DB::table('stock_out_items as l')
            ->join('stock_outs as h', 'h.id', '=', 'l.stock_out_id')
            ->selectRaw("'stock_out' as movement, h.id as doc_id, h.out_date as movement_date, l.ingredient_id,
                (0 - l.quantity) as quantity, NULL as unit_cost, h.recorded_by as user_id, NULL as reference,
                h.job_order_id as job_order_id, NULL as reason, l.created_at as created_at");

        $adj = DB::table('stock_adjustment_items as l')
            ->join('stock_adjustments as h', 'h.id', '=', 'l.stock_adjustment_id')
            ->selectRaw("'adjustment' as movement, h.id as doc_id, h.adjusted_date as movement_date, l.ingredient_id,
                l.quantity as quantity, NULL as unit_cost, h.adjusted_by as user_id, NULL as reference,
                NULL as job_order_id, h.reason as reason, l.created_at as created_at");

        return DB::query()
            ->fromSub($in->unionAll($out)->unionAll($adj), 'm')
            ->join('ingredients as i', 'i.id', '=', 'm.ingredient_id')
            ->leftJoin('users as u', 'u.id', '=', 'm.user_id')
            ->leftJoin('job_orders as jo', 'jo.id', '=', 'm.job_order_id')
            ->leftJoin('orders as o', 'o.id', '=', 'jo.order_id')
            ->select('m.*', 'i.name as ingredient_name', 'i.unit as unit', 'u.first_name as user_first_name',
                'u.middle_name as user_middle_name', 'u.last_name as user_last_name', 'o.order_number as order_number');
    }

    /** @return array<int, Ingredient> keyed by id, rows locked for the transaction */
    private function lock(array $ids): array
    {
        return Ingredient::whereIn('id', array_unique($ids))->lockForUpdate()->get()->keyBy('id')->all();
    }

    private function qty(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ','), '0'), '.');
    }
}
