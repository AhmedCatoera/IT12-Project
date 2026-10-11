<?php

namespace App\Http\Controllers;

use App\Models\JobOrder;
use App\Models\StockOutItem;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Stock out = the ingredients used on a job order. */
class StockOutController extends Controller
{
    public function store(Request $request, JobOrder $jobOrder, InventoryService $inventory)
    {
        $data = $request->validate([
            'out_date' => ['required', 'date', 'before_or_equal:today'],
            'remarks' => ['nullable', 'string', 'max:500'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.ingredient_id' => ['required', 'distinct', Rule::exists('ingredients', 'id')->where('is_active', true)],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0', 'max:99999'],
        ], [
            'lines.required' => 'Add at least one ingredient.',
            'lines.*.ingredient_id.distinct' => 'Each ingredient can appear only once.',
            'out_date.before_or_equal' => 'The date cannot be in the future.',
        ], [
            'lines.*.ingredient_id' => 'ingredient',
            'lines.*.quantity' => 'quantity',
        ]);

        $inventory->recordStockOut($jobOrder, $data['lines'], $data['out_date'], $data['remarks'] ?? null, $request->user());

        return redirect()->route('jobOrders.show', $jobOrder)->with('success', 'Ingredients used were recorded and deducted from stock.');
    }

    /** Owner only: fix a wrong amount. Lowering it puts the difference back in stock. */
    public function updateItem(Request $request, StockOutItem $item, InventoryService $inventory)
    {
        $data = $request->validateWithBag('editUsage', [
            'quantity' => ['required', 'numeric', 'gt:0', 'max:99999'],
        ]);

        $inventory->updateStockOutItem($item, (float) $data['quantity']);

        return back()->with('success', "Corrected the amount of {$item->ingredient->name} used.");
    }

    /** Owner only: remove a line logged by mistake. */
    public function destroyItem(StockOutItem $item, InventoryService $inventory)
    {
        $name = $item->ingredient->name;
        $inventory->deleteStockOutItem($item);

        return back()->with('success', "Removed {$name} from this job order. Its stock was restored.");
    }
}
