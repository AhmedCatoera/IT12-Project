<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\Production;
use App\Models\ProductionIngredient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductionController extends Controller
{
    /**
     * Display the production queue and scheduled baking batches.
     */
    public function index(Request $request)
    {
        $status = $request->input('status');
        $date = $request->input('date');
        $search = $request->input('search');

        // Scheduled production batches
        $productions = Production::query()
            ->with(['order.customer', 'baker', 'productionIngredients.ingredient'])
            ->when($status, function ($q, $status) {
                $q->where('status', $status);
            })
            ->when($date, function ($q, $date) {
                $q->whereDate('production_date', $date);
            })
            ->when($search, function ($q, $search) {
                $q->whereHas('order', function ($oq) use ($search) {
                    $oq->where('order_number', 'like', "%{$search}%")
                       ->orWhereHas('customer', function ($cq) use ($search) {
                           $cq->where('first_name', 'like', "%{$search}%")
                              ->orWhere('last_name', 'like', "%{$search}%");
                       });
                });
            })
            ->orderByRaw("CASE status WHEN 'in_progress' THEN 1 WHEN 'pending' THEN 2 WHEN 'completed' THEN 3 WHEN 'cancelled' THEN 4 ELSE 5 END")
            ->latest('production_date')
            ->paginate(15)
            ->withQueryString();

        // Confirmed orders awaiting production assignment
        $pendingConfirmedOrders = Order::query()
            ->whereIn('status', ['confirmed'])
            ->whereDoesntHave('productions', function ($q) {
                $q->whereIn('status', ['pending', 'in_progress', 'completed']);
            })
            ->with(['customer', 'items'])
            ->orderBy('scheduled_date', 'asc')
            ->get();

        // High-level production metrics
        $stats = [
            'in_progress' => Production::where('status', 'in_progress')->count(),
            'pending_batches' => Production::where('status', 'pending')->count(),
            'completed_today' => Production::where('status', 'completed')
                                    ->whereDate('completed_at', now()->toDateString())
                                    ->count(),
            'awaiting_queue' => $pendingConfirmedOrders->count(),
        ];

        return view('productions.index', compact('productions', 'pendingConfirmedOrders', 'status', 'date', 'search', 'stats'));
    }

    /**
     * Show form to schedule/assign a production batch for an order.
     */
    public function create(Request $request)
    {
        $selectedOrderId = $request->input('order_id');
        $selectedOrder = null;

        if ($selectedOrderId) {
            $selectedOrder = Order::with(['customer', 'items'])->findOrFail($selectedOrderId);
        }

        // Available confirmed orders without active production
        $availableOrders = Order::query()
            ->whereIn('status', ['confirmed', 'pending'])
            ->whereDoesntHave('productions', function ($q) {
                $q->whereIn('status', ['pending', 'in_progress', 'completed']);
            })
            ->with('customer')
            ->orderBy('scheduled_date', 'asc')
            ->get();

        // Active bakers
        $bakers = User::where('role', 'baker')
            ->where('is_active', true)
            ->orderBy('first_name', 'asc')
            ->get();

        return view('productions.create', compact('selectedOrder', 'availableOrders', 'bakers'));
    }

    /**
     * Store a newly created production batch.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => ['required', 'exists:orders,id'],
            'assigned_baker_id' => ['nullable', 'exists:users,id'],
            'production_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'auto_start' => ['nullable', 'boolean'],
        ]);

        $order = Order::findOrFail($validated['order_id']);

        $production = DB::transaction(function () use ($validated, $order, $request) {
            $autoStart = $request->boolean('auto_start');

            $production = Production::create([
                'order_id' => $order->id,
                'assigned_baker_id' => $validated['assigned_baker_id'] ?? (Auth::user()->isBaker() ? Auth::id() : null),
                'production_date' => $validated['production_date'],
                'status' => $autoStart ? 'in_progress' : 'pending',
                'started_at' => $autoStart ? now() : null,
                'notes' => $validated['notes'] ?? null,
            ]);

            if ($autoStart) {
                $order->update(['status' => 'in_production']);
            }

            return $production;
        });

        return redirect()->route('productions.show', $production)
            ->with('success', "Production batch scheduled successfully for Order {$order->order_number}.");
    }

    /**
     * Display detailed production sheet, custom cake specs, and ingredient usage ledger.
     */
    public function show(Production $production)
    {
        $production->load([
            'order.customer',
            'order.items',
            'baker',
            'productionIngredients.ingredient',
        ]);

        // List of all active ingredients for consumption logging
        $availableIngredients = Ingredient::where('is_active', true)
            ->orderBy('name', 'asc')
            ->get();

        return view('productions.show', compact('production', 'availableIngredients'));
    }

    /**
     * Update the workflow status of the production batch (Start baking, Complete baking, Cancel).
     */
    public function updateStatus(Request $request, Production $production)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,in_progress,completed,cancelled'],
        ]);

        $newStatus = $validated['status'];

        DB::transaction(function () use ($production, $newStatus) {
            $order = $production->order;

            if ($newStatus === 'in_progress') {
                $production->status = 'in_progress';
                $production->started_at = $production->started_at ?? now();
                if ($order->status !== 'in_production') {
                    $order->update(['status' => 'in_production']);
                }
            } elseif ($newStatus === 'completed') {
                $production->status = 'completed';
                $production->completed_at = now();
                // When production is finished, order is ready for release/pickup/delivery
                if (in_array($order->status, ['confirmed', 'in_production'])) {
                    $order->update(['status' => 'ready_for_release']);
                }
            } elseif ($newStatus === 'cancelled') {
                $production->status = 'cancelled';
            } else {
                $production->status = 'pending';
            }

            $production->save();
        });

        return redirect()->route('productions.show', $production)
            ->with('success', "Production status updated to " . ucfirst(str_replace('_', ' ', $newStatus)) . ".");
    }

    /**
     * Log ingredient consumption for this production batch and automatically deduct stock.
     */
    public function logIngredient(Request $request, Production $production)
    {
        $validated = $request->validate([
            'ingredient_id' => ['required', 'exists:ingredients,id'],
            'quantity_used' => ['required', 'numeric', 'min:0.01'],
        ]);

        $ingredient = Ingredient::findOrFail($validated['ingredient_id']);
        $quantityUsed = (float)$validated['quantity_used'];

        DB::transaction(function () use ($production, $ingredient, $quantityUsed) {
            // 1. Check or update existing production_ingredients record
            $existing = ProductionIngredient::where('production_id', $production->id)
                ->where('ingredient_id', $ingredient->id)
                ->first();

            if ($existing) {
                $existing->quantity_used += $quantityUsed;
                $existing->save();
            } else {
                ProductionIngredient::create([
                    'production_id' => $production->id,
                    'ingredient_id' => $ingredient->id,
                    'quantity_used' => $quantityUsed,
                ]);
            }

            // 2. Create inventory audit transaction log (negative quantity for deduction)
            InventoryTransaction::create([
                'ingredient_id' => $ingredient->id,
                'created_by' => Auth::id(),
                'transaction_type' => 'production_usage',
                'quantity' => -1 * $quantityUsed,
                'reference' => "Prod #{$production->id} (Order {$production->order->order_number})",
                'remarks' => "Used in baking Order {$production->order->order_number}",
            ]);

            // 3. Atomically decrement stock in ingredients table
            $ingredient->decrement('current_stock', $quantityUsed);
        });

        return redirect()->route('productions.show', $production)
            ->with('success', "Logged {$quantityUsed} {$ingredient->unit} of {$ingredient->name}. Stock deducted automatically.");
    }

    /**
     * Remove or reverse a logged ingredient consumption record.
     */
    public function removeIngredient(Production $production, ProductionIngredient $productionIngredient)
    {
        if ($productionIngredient->production_id !== $production->id) {
            abort(403);
        }

        $ingredient = $productionIngredient->ingredient;
        $restoredQty = (float)$productionIngredient->quantity_used;

        DB::transaction(function () use ($production, $productionIngredient, $ingredient, $restoredQty) {
            // 1. Delete production ingredient entry
            $productionIngredient->delete();

            // 2. Create reversal inventory transaction
            InventoryTransaction::create([
                'ingredient_id' => $ingredient->id,
                'created_by' => Auth::id(),
                'transaction_type' => 'audit_adjustment',
                'quantity' => $restoredQty,
                'reference' => "Reversal Prod #{$production->id}",
                'remarks' => "Reversal of ingredient logged for Order {$production->order->order_number}",
            ]);

            // 3. Increment stock back
            $ingredient->increment('current_stock', $restoredQty);
        });

        return redirect()->route('productions.show', $production)
            ->with('success', "Reversed {$restoredQty} {$ingredient->unit} of {$ingredient->name}. Stock restored.");
    }
}
