<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\InventoryAudit;
use App\Models\InventoryTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    /**
     * Display the inventory stock dashboard and ingredient monitoring list.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $filter = $request->input('filter'); // 'low_stock' or null

        $query = Ingredient::query()
            ->when($search, function ($q, $search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('unit', 'like', "%{$search}%");
            })
            ->when($filter === 'low_stock', function ($q) {
                $q->whereColumn('current_stock', '<=', 'reorder_level');
            })
            ->orderByRaw('CASE WHEN current_stock <= reorder_level THEN 0 ELSE 1 END')
            ->orderBy('name', 'asc');

        $ingredients = $query->paginate(15)->withQueryString();

        $stats = [
            'total_ingredients' => Ingredient::where('is_active', true)->count(),
            'low_stock_count' => Ingredient::where('is_active', true)
                                    ->whereColumn('current_stock', '<=', 'reorder_level')
                                    ->count(),
            'recent_stock_ins' => InventoryTransaction::where('transaction_type', 'stock_in')
                                    ->where('created_at', '>=', now()->subDays(30))
                                    ->count(),
            'total_audits' => InventoryAudit::count(),
        ];

        return view('inventory.index', compact('ingredients', 'search', 'filter', 'stats'));
    }

    /**
     * Show form to add a new raw ingredient.
     */
    public function create()
    {
        return view('inventory.create');
    }

    /**
     * Store a newly created ingredient.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:ingredients,name'],
            'unit' => ['required', 'string', 'max:50'],
            'reorder_level' => ['required', 'numeric', 'min:0'],
            'initial_stock' => ['nullable', 'numeric', 'min:0'],
            'cost_per_unit' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($validated) {
            $initialStock = (float)($validated['initial_stock'] ?? 0);

            $ingredient = Ingredient::create([
                'name' => $validated['name'],
                'unit' => $validated['unit'],
                'current_stock' => $initialStock,
                'reorder_level' => $validated['reorder_level'],
                'is_active' => true,
            ]);

            if ($initialStock > 0) {
                $unitCost = isset($validated['cost_per_unit']) ? (float)$validated['cost_per_unit'] : null;
                $totalCost = $unitCost ? ($initialStock * $unitCost) : null;

                InventoryTransaction::create([
                    'ingredient_id' => $ingredient->id,
                    'created_by' => Auth::id(),
                    'transaction_type' => 'stock_in',
                    'quantity' => $initialStock,
                    'unit_cost' => $unitCost,
                    'total_cost' => $totalCost,
                    'reference' => 'Initial Stock Setup',
                    'remarks' => 'Initial stock registered upon ingredient creation',
                ]);
            }
        });

        return redirect()->route('inventory.index')
            ->with('success', "Ingredient '{$validated['name']}' added to inventory.");
    }

    /**
     * Show form to edit ingredient properties.
     */
    public function edit(Ingredient $ingredient)
    {
        return view('inventory.edit', compact('ingredient'));
    }

    /**
     * Update ingredient properties (name, unit, reorder level, active status).
     */
    public function update(Request $request, Ingredient $ingredient)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:ingredients,name,' . $ingredient->id],
            'unit' => ['required', 'string', 'max:50'],
            'reorder_level' => ['required', 'numeric', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ]);

        $ingredient->update($validated);

        return redirect()->route('inventory.index')
            ->with('success', "Ingredient '{$ingredient->name}' updated successfully.");
    }

    /**
     * Show form to record incoming stock purchase.
     */
    public function stockIn(Request $request)
    {
        $selectedIngredientId = $request->input('ingredient_id');
        $selectedIngredient = null;

        if ($selectedIngredientId) {
            $selectedIngredient = Ingredient::findOrFail($selectedIngredientId);
        }

        $ingredients = Ingredient::where('is_active', true)->orderBy('name')->get();

        return view('inventory.stock_in', compact('ingredients', 'selectedIngredient'));
    }

    /**
     * Process stock-in purchase receipt and increment stock level.
     */
    public function storeStockIn(Request $request)
    {
        $validated = $request->validate([
            'ingredient_id' => ['required', 'exists:ingredients,id'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'expiration_date' => ['nullable', 'date'],
            'reference' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $ingredient = Ingredient::findOrFail($validated['ingredient_id']);
        $quantity = (float)$validated['quantity'];
        $unitCost = isset($validated['unit_cost']) ? (float)$validated['unit_cost'] : null;
        $totalCost = ($unitCost && $quantity) ? ($unitCost * $quantity) : null;

        DB::transaction(function () use ($ingredient, $quantity, $unitCost, $totalCost, $validated) {
            // 1. Log inventory transaction
            InventoryTransaction::create([
                'ingredient_id' => $ingredient->id,
                'created_by' => Auth::id(),
                'transaction_type' => 'stock_in',
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'expiration_date' => $validated['expiration_date'] ?? null,
                'reference' => $validated['reference'] ?? 'Purchase Delivery',
                'remarks' => $validated['remarks'] ?? null,
            ]);

            // 2. Increment stock in ingredients table
            $ingredient->increment('current_stock', $quantity);
        });

        return redirect()->route('inventory.index')
            ->with('success', "Received {$quantity} {$ingredient->unit} of {$ingredient->name}. Stock updated.");
    }

    /**
     * Show form to record spoilage, waste, or manual inventory adjustment.
     */
    public function adjust(Request $request)
    {
        $selectedIngredientId = $request->input('ingredient_id');
        $selectedIngredient = null;

        if ($selectedIngredientId) {
            $selectedIngredient = Ingredient::findOrFail($selectedIngredientId);
        }

        $ingredients = Ingredient::where('is_active', true)->orderBy('name')->get();

        return view('inventory.adjust', compact('ingredients', 'selectedIngredient'));
    }

    /**
     * Process spoilage or manual adjustment.
     */
    public function storeAdjust(Request $request)
    {
        $validated = $request->validate([
            'ingredient_id' => ['required', 'exists:ingredients,id'],
            'transaction_type' => ['required', 'in:spoilage,audit_adjustment'],
            'adjustment_type' => ['required', 'in:deduct,add'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'remarks' => ['required', 'string', 'max:500'],
        ]);

        $ingredient = Ingredient::findOrFail($validated['ingredient_id']);
        $quantity = (float)$validated['quantity'];
        $isDeduct = $validated['adjustment_type'] === 'deduct';
        $signedQty = $isDeduct ? (-1 * $quantity) : $quantity;

        DB::transaction(function () use ($ingredient, $quantity, $signedQty, $isDeduct, $validated) {
            // 1. Log inventory transaction
            InventoryTransaction::create([
                'ingredient_id' => $ingredient->id,
                'created_by' => Auth::id(),
                'transaction_type' => $validated['transaction_type'],
                'quantity' => $signedQty,
                'reference' => ucfirst($validated['transaction_type']),
                'remarks' => $validated['remarks'],
            ]);

            // 2. Adjust stock
            if ($isDeduct) {
                $ingredient->decrement('current_stock', $quantity);
            } else {
                $ingredient->increment('current_stock', $quantity);
            }
        });

        return redirect()->route('inventory.index')
            ->with('success', "Stock adjustment for {$ingredient->name} recorded successfully.");
    }

    /**
     * Display the complete chronological audit trail of all inventory transactions.
     */
    public function transactions(Request $request)
    {
        $type = $request->input('type');
        $ingredientId = $request->input('ingredient_id');
        $search = $request->input('search');

        $transactions = InventoryTransaction::query()
            ->with(['ingredient', 'creator'])
            ->when($type, function ($q, $type) {
                $q->where('transaction_type', $type);
            })
            ->when($ingredientId, function ($q, $ingredientId) {
                $q->where('ingredient_id', $ingredientId);
            })
            ->when($search, function ($q, $search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhere('remarks', 'like', "%{$search}%")
                  ->orWhereHas('ingredient', function ($iq) use ($search) {
                      $iq->where('name', 'like', "%{$search}%");
                  });
            })
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        $ingredients = Ingredient::orderBy('name')->get();

        return view('inventory.transactions', compact('transactions', 'ingredients', 'type', 'ingredientId', 'search'));
    }
}
