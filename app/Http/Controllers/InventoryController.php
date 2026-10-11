<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    public const UNITS = ['kg', 'g', 'L', 'mL', 'pcs', 'dozen', 'pack', 'box', 'bottle', 'can', 'sheet', 'roll'];

    public function index(Request $request)
    {
        $filter = $this->textFilter($request, 'filter');
        $filter = in_array($filter, ['low', 'inactive'], true) ? $filter : null;
        $search = (string) $this->textFilter($request, 'search');

        $ingredients = Ingredient::query()
            ->withStock()
            ->where('is_active', $filter !== 'inactive')
            ->when($filter === 'low', fn ($q) => $q->lowStock())
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")->orWhere('unit', 'like', "%{$search}%");
            }))
            ->orderByRaw('CASE WHEN ' . Ingredient::stockSql() . ' <= ingredients.reorder_level THEN 0 ELSE 1 END')
            ->orderBy('name')
            ->paginateOrLast(15)
            ->withQueryString();

        $stats = [
            'active' => Ingredient::active()->count(),
            'low' => Ingredient::active()->lowStock()->count(),
            'inactive' => Ingredient::where('is_active', false)->count(),
        ];

        $units = self::UNITS;

        return view('inventory.index', compact('ingredients', 'filter', 'search', 'stats', 'units'));
    }

    public function show(Ingredient $ingredient, InventoryService $inventory)
    {
        $movements = $inventory->movements()
            ->where('m.ingredient_id', $ingredient->id)
            ->orderByDesc('m.movement_date')
            ->orderByDesc('m.created_at')
            ->paginateOrLast(15);

        return view('inventory.show', compact('ingredient', 'movements'));
    }

    public function store(Request $request)
    {
        $data = $request->validateWithBag('createIngredient', [
            'name' => ['required', 'string', 'max:100', 'unique:ingredients,name'],
            'unit' => ['required', Rule::in(self::UNITS)],
            'reorder_level' => ['required', 'numeric', 'min:0', 'max:99999'],
        ]);

        $ingredient = Ingredient::create($data + ['is_active' => true]);

        return back()->with('success', "{$ingredient->name} was added. Record a stock in to give it a starting quantity.");
    }

    public function update(Request $request, Ingredient $ingredient)
    {
        $data = $request->validateWithBag('editIngredient', [
            'name' => ['required', 'string', 'max:100', Rule::unique('ingredients', 'name')->ignore($ingredient->id)],
            'unit' => ['required', Rule::in(self::UNITS)],
            'reorder_level' => ['required', 'numeric', 'min:0', 'max:99999'],
            'is_active' => ['required', 'boolean'],
        ]);

        $ingredient->update($data);

        return back()->with('success', "{$ingredient->name} was updated.");
    }
}
