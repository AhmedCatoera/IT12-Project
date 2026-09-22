<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\InventoryAudit;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Production;
use App\Models\ProductionIngredient;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Display the Owner's Executive Reports Dashboard.
     * Fulfills SweetNest Document Chapter 3, Objective #5 & Figure 6.
     */
    public function index()
    {
        // Monthly sales summary for current month
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $monthlyOrders = Order::whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->where('status', '!=', 'cancelled')
            ->count();

        $monthlyRevenue = Order::whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');

        // All-time metrics
        $totalOrdersCount = Order::where('status', '!=', 'cancelled')->count();
        $totalRevenue = Order::where('status', '!=', 'cancelled')->sum('total_amount');

        $totalPaymentsReceived = Payment::sum('amount');
        $totalDownPayments = Payment::where('payment_type', 'down_payment')->sum('amount');
        $totalFinalPayments = Payment::where('payment_type', 'balance_payment')->sum('amount');

        // Total inventory stock metrics
        $totalIngredientsCount = Ingredient::count();
        $totalUnitsInStock = Ingredient::sum('current_stock');
        $lowStockCount = Ingredient::whereColumn('current_stock', '<=', 'reorder_level')->count();

        // Recent top consumed ingredients this month
        $topIngredientsThisMonth = ProductionIngredient::query()
            ->join('productions', 'production_ingredients.production_id', '=', 'productions.id')
            ->join('ingredients', 'production_ingredients.ingredient_id', '=', 'ingredients.id')
            ->whereDate('productions.production_date', '>=', $startOfMonth->toDateString())
            ->whereDate('productions.production_date', '<=', $endOfMonth->toDateString())
            ->select(
                'ingredients.name as ingredient_name',
                'ingredients.unit as unit',
                DB::raw('SUM(production_ingredients.quantity_used) as total_used')
            )
            ->groupBy('ingredients.id', 'ingredients.name', 'ingredients.unit')
            ->orderByDesc('total_used')
            ->limit(5)
            ->get();

        // Recent 5 shift audits
        $recentAudits = InventoryAudit::with('conductedBy')
            ->latest('audit_date')
            ->latest('id')
            ->limit(5)
            ->get();

        return view('reports.index', compact(
            'monthlyOrders',
            'monthlyRevenue',
            'totalOrdersCount',
            'totalRevenue',
            'totalPaymentsReceived',
            'totalDownPayments',
            'totalFinalPayments',
            'totalIngredientsCount',
            'totalUnitsInStock',
            'lowStockCount',
            'topIngredientsThisMonth',
            'recentAudits'
        ));
    }

    /**
     * Order & Sales Report.
     * Fulfills SweetNest Document Chapter 3, Objective #5: Order information & sales reporting.
     */
    public function orders(Request $request)
    {
        $period = $request->input('period', 'this_month');
        $status = $request->input('status');
        $orderType = $request->input('order_type');
        $startDateInput = $request->input('start_date');
        $endDateInput = $request->input('end_date');

        $query = Order::with(['customer', 'items', 'payments']);

        // Date filtering
        switch ($period) {
            case 'today':
                $query->whereDate('created_at', Carbon::today());
                break;
            case 'this_week':
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'this_month':
                $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
                break;
            case 'last_month':
                $query->whereBetween('created_at', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()]);
                break;
            case 'this_year':
                $query->whereBetween('created_at', [now()->startOfYear(), now()->endOfYear()]);
                break;
            case 'custom':
                if ($startDateInput) {
                    $query->whereDate('created_at', '>=', $startDateInput);
                }
                if ($endDateInput) {
                    $query->whereDate('created_at', '<=', $endDateInput);
                }
                break;
            case 'all':
            default:
                break;
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($orderType) {
            $query->where('order_type', $orderType);
        }

        $orders = $query->latest('created_at')->paginate(20)->withQueryString();

        // Summary calculations for the filtered set (unpaginated)
        $summaryQuery = clone $query;
        $allMatchingOrders = $summaryQuery->get();

        $summary = [
            'count' => $allMatchingOrders->count(),
            'total_sales' => $allMatchingOrders->where('status', '!=', 'cancelled')->sum('total_amount'),
            'total_paid' => $allMatchingOrders->sum(function ($order) {
                return $order->payments->sum('amount');
            }),
            'pending_balance' => $allMatchingOrders->where('status', '!=', 'cancelled')->sum(function ($order) {
                $paid = $order->payments->sum('amount');
                return max(0, $order->total_amount - $paid);
            }),
            'completed_count' => $allMatchingOrders->where('status', 'completed')->count(),
            'cancelled_count' => $allMatchingOrders->where('status', 'cancelled')->count(),
        ];

        return view('reports.orders', compact('orders', 'summary', 'period', 'status', 'orderType', 'startDateInput', 'endDateInput'));
    }

    /**
     * Ingredient Consumption Report.
     * Fulfills SweetNest Document Chapter 3, Objective #5 & Figure 6: "View Order and Consumption Reports".
     */
    public function consumption(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        $ingredientId = $request->input('ingredient_id');

        // Aggregated consumption per ingredient
        $aggregatedQuery = ProductionIngredient::query()
            ->join('productions', 'production_ingredients.production_id', '=', 'productions.id')
            ->join('ingredients', 'production_ingredients.ingredient_id', '=', 'ingredients.id')
            ->whereDate('productions.production_date', '>=', $startDate)
            ->whereDate('productions.production_date', '<=', $endDate);

        if ($ingredientId) {
            $aggregatedQuery->where('ingredients.id', $ingredientId);
        }

        $consumptionSummary = $aggregatedQuery
            ->select(
                'ingredients.id as ingredient_id',
                'ingredients.name as ingredient_name',
                'ingredients.unit as unit',
                DB::raw('COUNT(DISTINCT productions.id) as batch_count'),
                DB::raw('SUM(production_ingredients.quantity_used) as total_quantity_used')
            )
            ->groupBy('ingredients.id', 'ingredients.name', 'ingredients.unit')
            ->orderByDesc('total_quantity_used')
            ->get();

        // Detailed itemized log
        $detailedQuery = ProductionIngredient::query()
            ->with(['production.order.customer', 'production.baker', 'ingredient'])
            ->whereHas('production', function ($q) use ($startDate, $endDate) {
                $q->whereDate('production_date', '>=', $startDate)
                  ->whereDate('production_date', '<=', $endDate);
            });

        if ($ingredientId) {
            $detailedQuery->where('ingredient_id', $ingredientId);
        }

        $detailedLogs = $detailedQuery->latest('created_at')->paginate(20)->withQueryString();

        $ingredients = Ingredient::orderBy('name', 'asc')->get();

        $totalConsumptionEntries = $consumptionSummary->count();

        return view('reports.consumption', compact(
            'consumptionSummary',
            'detailedLogs',
            'ingredients',
            'startDate',
            'endDate',
            'ingredientId',
            'totalConsumptionEntries'
        ));
    }

    /**
     * Unified Inventory History Report.
     * Fulfills SweetNest Document Chapter 3, Objective #5 & Figure 6: "View Inventory History".
     */
    public function inventoryHistory(Request $request)
    {
        $type = $request->input('type');
        $ingredientId = $request->input('ingredient_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = InventoryTransaction::with(['ingredient', 'creator']);

        if ($type) {
            $query->where('transaction_type', $type);
        }

        if ($ingredientId) {
            $query->where('ingredient_id', $ingredientId);
        }

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $transactions = $query->latest('id')->paginate(25)->withQueryString();

        // Movement KPIs for selected filter
        $kpiQuery = clone $query;
        $allFiltered = $kpiQuery->get();

        $movementStats = [
            'total_stock_in_cost' => $allFiltered->where('transaction_type', 'stock_in')->sum(function ($t) {
                return $t->total_cost ?? ($t->quantity * ($t->unit_cost ?? 0));
            }),
            'total_stock_in_count' => $allFiltered->where('transaction_type', 'stock_in')->count(),
            'total_production_usage_count' => $allFiltered->where('transaction_type', 'production_usage')->count(),
            'total_spoilage_count' => $allFiltered->where('transaction_type', 'spoilage')->count(),
            'total_audit_adjustments_count' => $allFiltered->where('transaction_type', 'audit_adjustment')->count(),
        ];

        $ingredients = Ingredient::orderBy('name', 'asc')->get();

        return view('reports.inventory_history', compact('transactions', 'movementStats', 'ingredients', 'type', 'ingredientId', 'startDate', 'endDate'));
    }
}
