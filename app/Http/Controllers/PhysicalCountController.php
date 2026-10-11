<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\PhysicalCount;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Staff count what is on the shelf (blind count: the system number is hidden).
 * Only the Owner applies the differences to stock.
 */
class PhysicalCountController extends Controller
{
    public function index()
    {
        $counts = PhysicalCount::with(['counter', 'adjustment'])
            ->withCount(['items as mismatch_count' => fn ($q) => $q->whereColumn('counted_qty', '!=', 'system_qty')])
            ->orderByDesc('count_date')
            ->orderByDesc('id')
            ->paginateOrLast(15);

        // Only the newest count can be applied; older unapplied ones are replaced by it.
        $latestId = PhysicalCount::max('id');

        return view('counts.index', compact('counts', 'latestId'));
    }

    public function create()
    {
        $ingredients = Ingredient::active()->orderBy('name')->get();
        $doneShifts = PhysicalCount::where('count_date', today()->toDateString())->pluck('shift')->all();
        $defaultShift = now()->hour < 14 ? 'morning' : 'night';

        if (in_array($defaultShift, $doneShifts, true)) {
            $defaultShift = $defaultShift === 'morning' ? 'night' : 'morning';
        }

        return view('counts.create', compact('ingredients', 'doneShifts', 'defaultShift'));
    }

    public function store(Request $request, InventoryService $inventory)
    {
        $ingredientIds = Ingredient::active()->pluck('id');

        // A count is always for today: the system amount is saved at the moment the count is
        // saved, so a count typed in later would be compared with the wrong stock level.
        $today = today()->toDateString();

        $rules = [
            'shift' => ['required', Rule::in(array_keys(PhysicalCount::SHIFTS)),
                Rule::unique('physical_counts')->where('count_date', $today)],
            'notes' => ['nullable', 'string', 'max:1000'],
            'counts' => ['required', 'array'],
        ];

        foreach ($ingredientIds as $id) {
            $rules["counts.{$id}"] = ['required', 'numeric', 'min:0', 'max:99999'];
        }

        $data = $request->validate($rules, [
            'shift.unique' => 'This shift was already counted today.',
            'counts.*.required' => 'Enter the quantity you counted (0 if none).',
        ]);

        $counted = collect($data['counts'])->only($ingredientIds->all())->all();
        $count = $inventory->recordCount(['count_date' => $today] + $data, $counted, $request->user());

        return redirect()->route('counts.show', $count)->with('success', 'Count saved. The Owner will review any differences.');
    }

    public function show(PhysicalCount $count)
    {
        $count->load(['items.ingredient', 'counter', 'adjustment.adjuster']);
        $items = $count->items->sortBy(fn ($i) => [abs($i->variance) < 0.01 ? 1 : 0, $i->ingredient->name]);
        $newer = $count->newerCount();

        return view('counts.show', compact('count', 'items', 'newer'));
    }

    /** Owner only. */
    public function apply(Request $request, PhysicalCount $count, InventoryService $inventory)
    {
        try {
            $adjustment = $inventory->applyCount($count->load('items'), $request->user());
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        return back()->with('success', "Stock corrected to match the count ({$adjustment->number}).");
    }
}
