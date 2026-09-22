<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\InventoryAudit;
use App\Models\InventoryAuditItem;
use App\Models\InventoryTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryAuditController extends Controller
{
    /**
     * Display a listing of completed physical inventory count audits.
     */
    public function index()
    {
        $audits = InventoryAudit::with(['conductedBy', 'items.ingredient'])
            ->latest('audit_date')
            ->latest('created_at')
            ->paginate(15);

        return view('inventory.audits.index', compact('audits'));
    }

    /**
     * Show form to conduct a new physical count audit (Morning or Afternoon shift).
     */
    public function create()
    {
        $ingredients = Ingredient::where('is_active', true)->orderBy('name')->get();

        // Default shift based on current hour
        $defaultShift = (now()->hour < 13) ? 'morning' : 'afternoon';

        return view('inventory.audits.create', compact('ingredients', 'defaultShift'));
    }

    /**
     * Store completed physical count audit, record variances, and optionally reconcile stock.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'audit_date' => ['required', 'date'],
            'audit_shift' => ['required', 'in:morning,afternoon'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'sync_system_stock' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.ingredient_id' => ['required', 'exists:ingredients,id'],
            'items.*.physical_stock' => ['required', 'numeric', 'min:0'],
        ]);

        $audit = DB::transaction(function () use ($validated, $request) {
            $audit = InventoryAudit::create([
                'conducted_by' => Auth::id(),
                'audit_date' => $validated['audit_date'],
                'audit_shift' => $validated['audit_shift'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $syncStock = $request->boolean('sync_system_stock');

            foreach ($validated['items'] as $itemData) {
                $ingredient = Ingredient::find($itemData['ingredient_id']);
                if (!$ingredient) {
                    continue;
                }

                $systemStock = (float)$ingredient->current_stock;
                $physicalStock = (float)$itemData['physical_stock'];
                $variance = round($physicalStock - $systemStock, 2);

                InventoryAuditItem::create([
                    'audit_id' => $audit->id,
                    'ingredient_id' => $ingredient->id,
                    'system_stock' => $systemStock,
                    'physical_stock' => $physicalStock,
                    'variance' => $variance,
                ]);

                // If sync checkbox is checked and variance is non-zero, reconcile inventory
                if ($syncStock && $variance != 0) {
                    InventoryTransaction::create([
                        'ingredient_id' => $ingredient->id,
                        'created_by' => Auth::id(),
                        'transaction_type' => 'audit_adjustment',
                        'quantity' => $variance,
                        'reference' => "Physical Audit #{$audit->id} (" . ucfirst($audit->audit_shift) . ")",
                        'remarks' => "Reconciled stock to match physical count of {$physicalStock} {$ingredient->unit}",
                    ]);

                    $ingredient->current_stock = $physicalStock;
                    $ingredient->save();
                }
            }

            return $audit;
        });

        return redirect()->route('audits.show', $audit)
            ->with('success', ucfirst($audit->audit_shift) . " shift physical inventory count recorded successfully.");
    }

    /**
     * Display a detailed report of an audit, including ingredient variance breakdown.
     */
    public function show(InventoryAudit $audit)
    {
        $audit->load(['conductedBy', 'items.ingredient']);

        $discrepancyCount = $audit->items->filter(function ($item) {
            return (float)$item->variance != 0;
        })->count();

        return view('inventory.audits.show', compact('audit', 'discrepancyCount'));
    }
}
