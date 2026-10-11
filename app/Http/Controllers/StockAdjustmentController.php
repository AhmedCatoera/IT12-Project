<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\StockAdjustment;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Owner only: spoilage, expired or damaged stock, and fixes. */
class StockAdjustmentController extends Controller
{
    /** Count corrections are created only from a physical count. */
    public const MANUAL_REASONS = ['spoilage', 'expired', 'damaged', 'entry_correction', 'other'];

    public function index()
    {
        $adjustments = StockAdjustment::with(['adjuster', 'items.ingredient', 'physicalCount'])
            ->orderByDesc('adjusted_date')
            ->orderByDesc('id')
            ->paginateOrLast(15);

        return view('adjustments.index', compact('adjustments'));
    }

    public function create(Request $request)
    {
        $ingredients = Ingredient::active()->withStock()->orderBy('name')->get();
        $preselect = $this->textFilter($request, 'ingredient_id');
        $reasons = array_intersect_key(StockAdjustment::REASONS, array_flip(self::MANUAL_REASONS));

        return view('adjustments.create', compact('ingredients', 'preselect', 'reasons'));
    }

    public function store(Request $request, InventoryService $inventory)
    {
        $data = $request->validate([
            'reason' => ['required', Rule::in(self::MANUAL_REASONS)],
            'adjusted_date' => ['required', 'date', 'before_or_equal:today'],
            'remarks' => ['required', 'string', 'max:500'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.ingredient_id' => ['required', 'distinct', Rule::exists('ingredients', 'id')->where('is_active', true)],
            'lines.*.direction' => ['required', Rule::in(['add', 'remove'])],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0', 'max:99999'],
        ], [
            'lines.required' => 'Add at least one ingredient.',
            'lines.*.ingredient_id.distinct' => 'Each ingredient can appear only once.',
            'remarks.required' => 'Explain what happened (for example, which items spoiled and why).',
        ], [
            'lines.*.ingredient_id' => 'ingredient',
            'lines.*.quantity' => 'quantity',
        ]);

        $lossReasons = ['spoilage', 'expired', 'damaged'];
        $lines = [];

        foreach ($data['lines'] as $key => $line) {
            if (in_array($data['reason'], $lossReasons, true) && $line['direction'] === 'add') {
                throw ValidationException::withMessages([
                    "lines.{$key}.direction" => StockAdjustment::REASONS[$data['reason']] . ' can only remove stock.',
                ]);
            }

            $lines[$key] = [
                'ingredient_id' => (int) $line['ingredient_id'],
                'quantity' => ($line['direction'] === 'remove' ? -1 : 1) * (float) $line['quantity'],
            ];
        }

        $adjustment = $inventory->recordAdjustment($data, $lines, $request->user());

        return redirect()->route('adjustments.index')->with('success', "Adjustment {$adjustment->number} saved.");
    }
}
