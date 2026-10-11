<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\StockIn;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Owner only. One receipt (header) holds many ingredient lines. */
class StockInController extends Controller
{
    public function index(Request $request)
    {
        $search = (string) $this->textFilter($request, 'search');

        $stockIns = StockIn::query()
            ->with('receiver')
            ->withCount('items')
            ->addSelect(['total_cost' => \App\Models\StockInItem::selectRaw('COALESCE(SUM(quantity * COALESCE(unit_cost, 0)), 0)')
                ->whereColumn('stock_in_items.stock_in_id', 'stock_ins.id')])
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('receipt_no', 'like', "%{$search}%")->orWhere('supplier_name', 'like', "%{$search}%");
            }))
            ->orderByDesc('received_date')
            ->orderByDesc('id')
            ->paginateOrLast(15)
            ->withQueryString();

        return view('stock-ins.index', compact('stockIns', 'search'));
    }

    public function create(Request $request)
    {
        $ingredients = Ingredient::active()->withStock()->orderBy('name')->get();
        $preselect = $this->textFilter($request, 'ingredient_id');

        return view('stock-ins.create', compact('ingredients', 'preselect'));
    }

    public function store(Request $request, InventoryService $inventory)
    {
        $data = $request->validate([
            'receipt_no' => ['nullable', 'string', 'max:50'],
            'supplier_name' => ['nullable', 'string', 'max:100'],
            'received_date' => ['required', 'date', 'before_or_equal:today'],
            'remarks' => ['nullable', 'string', 'max:500'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.ingredient_id' => ['required', 'distinct', Rule::exists('ingredients', 'id')->where('is_active', true)],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0', 'max:99999'],
            'lines.*.unit_cost' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'lines.*.expiration_date' => ['nullable', 'date', 'after_or_equal:received_date'],
        ], [
            'lines.required' => 'Add at least one ingredient.',
            'lines.*.ingredient_id.distinct' => 'Each ingredient can appear only once per receipt.',
            'received_date.before_or_equal' => 'The received date cannot be in the future.',
            'lines.*.expiration_date.after_or_equal' => 'The expiry date must be after the received date.',
        ], [
            'lines.*.ingredient_id' => 'ingredient',
            'lines.*.quantity' => 'quantity',
            'lines.*.unit_cost' => 'unit cost',
        ]);

        $stockIn = $inventory->recordStockIn($data, $data['lines'], $request->user());

        return redirect()->route('stockIns.show', $stockIn)
            ->with('success', "Stock in {$stockIn->number} saved: " . count($data['lines']) . ' ingredient(s) added to stock.');
    }

    public function show(StockIn $stockIn)
    {
        $stockIn->load(['items.ingredient', 'receiver']);

        return view('stock-ins.show', compact('stockIn'));
    }
}
