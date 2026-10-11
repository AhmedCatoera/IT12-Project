<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\JobOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\StockInItem;
use App\Models\StockOutItem;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Owner reports. Every total is computed from the whole date range
 * with its own query, never from the rows on the current page.
 */
class ReportController extends Controller
{
    public function index()
    {
        [$from, $to] = [now()->startOfMonth(), now()->endOfMonth()];

        $kpis = [
            'sales' => $this->salesTotal($from, $to),
            'orders' => Order::whereBetween('order_date', [$from->toDateString(), $to->toDateString()])->where('status', '!=', 'cancelled')->count(),
            'collected' => (float) Payment::whereBetween('paid_at', [$from, $to])->sum('amount'),
            'job_orders_done' => JobOrder::where('status', 'completed')->whereBetween('completed_at', [$from, $to])->count(),
            'low_stock' => Ingredient::active()->lowStock()->count(),
        ];

        return view('reports.index', compact('kpis'));
    }

    /** Sales = value of orders booked in the period (cancelled orders excluded). */
    public function sales(Request $request)
    {
        [$from, $to] = $this->range($request);
        $inRange = fn ($q) => $q->whereBetween('orders.order_date', [$from->toDateString(), $to->toDateString()])
            ->where('orders.status', '!=', 'cancelled');

        $orders = Order::withTotals()->with('customer')
            ->tap($inRange)
            ->orderBy('order_date')->orderBy('id')
            ->paginateOrLast(15)->withQueryString()->fragment('list');

        $orderIds = Order::query()->tap($inRange)->select('id');

        // Custom items (no product row) count under the category typed for them.
        // Plain query builder: an OrderItem model would hide the "category" column behind its accessor.
        $byCategory = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->tap($inRange)
            ->groupBy(DB::raw('COALESCE(products.category, order_items.custom_category)'))
            ->selectRaw('COALESCE(products.category, order_items.custom_category) as category, SUM(order_items.quantity) as qty, SUM(order_items.quantity * order_items.unit_price) as total')
            ->orderByDesc('total')
            ->get();

        $topProducts = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->tap($inRange)
            ->groupBy(DB::raw('COALESCE(products.name, order_items.custom_name)'), DB::raw('order_items.product_id IS NULL'))
            ->selectRaw('COALESCE(products.name, order_items.custom_name) as product_name, (order_items.product_id IS NULL) as is_custom, SUM(order_items.quantity) as qty, SUM(order_items.quantity * order_items.unit_price) as total')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $booked = $this->bookedOrders($from, $to);
        $total = round($booked->sum('total_amount'), 2);
        $rushFees = round($booked->sum('rush_fee'), 2);
        $deliveryFees = round($booked->sum('delivery_fee_amount'), 2);
        $collected = (float) Payment::whereIn('order_id', $orderIds)->sum('amount');

        $summary = [
            'orders' => Order::query()->tap($inRange)->count(),
            'total' => $total,
            'rush_fees' => $rushFees,
            'delivery_fees' => $deliveryFees,
            'collected' => $collected,
            'outstanding' => max(0, round($total - $collected, 2)),
            'cancelled' => Order::whereBetween('order_date', [$from->toDateString(), $to->toDateString()])->where('status', 'cancelled')->count(),
        ];

        return view('reports.sales', compact('orders', 'summary', 'byCategory', 'topProducts', 'from', 'to'));
    }

    /** Collection = money actually received in the period, by payment date. */
    public function collections(Request $request)
    {
        [$from, $to] = $this->range($request);
        $base = Payment::whereBetween('paid_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);

        $refunds = Order::whereNotNull('refunded_at')
            ->whereBetween('refunded_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);

        $summary = [
            'total' => (float) (clone $base)->sum('amount'),
            'count' => (clone $base)->count(),
            'refunds' => (float) (clone $refunds)->sum('refund_amount'),
            'refund_count' => (clone $refunds)->count(),
            'by_method' => (clone $base)->selectRaw('payment_method, COUNT(*) as n, SUM(amount) as total')
                ->groupBy('payment_method')->get()->keyBy('payment_method'),
            'by_type' => (clone $base)->selectRaw('payment_type, COUNT(*) as n, SUM(amount) as total')
                ->groupBy('payment_type')->get()->keyBy('payment_type'),
        ];

        $payments = (clone $base)->with(['order.customer', 'receiver'])
            ->orderBy('paid_at')->orderBy('id')
            ->paginateOrLast(15)->withQueryString()->fragment('list');

        return view('reports.collections', compact('payments', 'summary', 'from', 'to'));
    }

    /** Job orders scheduled for production in the period. */
    public function jobOrders(Request $request)
    {
        [$from, $to] = $this->range($request);
        $base = JobOrder::whereBetween('production_date', [$from->toDateString(), $to->toDateString()]);

        $byStatus = (clone $base)->selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status');

        $onTime = (clone $base)->where('job_orders.status', 'completed')
            ->join('orders', 'orders.id', '=', 'job_orders.order_id')
            ->whereColumn('job_orders.completed_at', '<=', 'orders.scheduled_at')
            ->count();

        $jobOrders = (clone $base)->with(['order.customer', 'assignee'])
            ->withCount('stockOutItems')
            ->orderBy('production_date')->orderBy('id')
            ->paginateOrLast(15)->withQueryString()->fragment('list');

        $summary = [
            'total' => $byStatus->sum(),
            'completed' => $byStatus['completed'] ?? 0,
            'on_time' => $onTime,
            'open' => ($byStatus['pending'] ?? 0) + ($byStatus['in_progress'] ?? 0),
            'cancelled' => $byStatus['cancelled'] ?? 0,
        ];

        return view('reports.job-orders', compact('jobOrders', 'summary', 'from', 'to'));
    }

    /** Stock levels now, plus usage and movements in the period. */
    public function inventory(Request $request, InventoryService $inventory)
    {
        [$from, $to] = $this->range($request);
        $type = $this->textFilter($request, 'type');
        $type = in_array($type, ['stock_in', 'stock_out', 'adjustment'], true) ? $type : null;

        // Ten rows a page each; each table keeps its own page number ("levels", "used").
        $levels = Ingredient::active()->withStock()
            ->orderByRaw('CASE WHEN ' . Ingredient::stockSql() . ' <= ingredients.reorder_level THEN 0 ELSE 1 END')
            ->orderBy('name')
            ->paginateOrLast(10, 'levels')->withQueryString()->fragment('levels');
        $lowCount = Ingredient::active()->lowStock()->count();

        $usage = StockOutItem::query()
            ->join('stock_outs', 'stock_outs.id', '=', 'stock_out_items.stock_out_id')
            ->join('ingredients', 'ingredients.id', '=', 'stock_out_items.ingredient_id')
            ->whereBetween('stock_outs.out_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('ingredients.id', 'ingredients.name', 'ingredients.unit')
            ->selectRaw('ingredients.name, ingredients.unit, SUM(stock_out_items.quantity) as total, COUNT(DISTINCT stock_outs.job_order_id) as job_orders')
            ->orderByDesc('total')
            ->orderBy('ingredients.name')
            ->paginateOrLast(10, 'used')->withQueryString()->fragment('used');

        $purchases = (float) StockInItem::query()
            ->join('stock_ins', 'stock_ins.id', '=', 'stock_in_items.stock_in_id')
            ->whereBetween('stock_ins.received_date', [$from->toDateString(), $to->toDateString()])
            ->sum(DB::raw('stock_in_items.quantity * COALESCE(stock_in_items.unit_cost, 0)'));

        $movements = $inventory->movements()
            ->whereBetween('m.movement_date', [$from->toDateString(), $to->toDateString()])
            ->when($type, fn ($q) => $q->where('m.movement', $type))
            ->orderByDesc('m.movement_date')
            ->orderByDesc('m.created_at')
            ->paginateOrLast(15)->withQueryString()->fragment('movements');

        return view('reports.inventory', compact('levels', 'lowCount', 'usage', 'purchases', 'movements', 'from', 'to', 'type'));
    }

    /** Items plus rush and delivery fees of the orders booked in the period (the order total, worked out per order). */
    private function salesTotal(Carbon $from, Carbon $to): float
    {
        return round($this->bookedOrders($from, $to)->sum('total_amount'), 2);
    }

    private function bookedOrders(Carbon $from, Carbon $to)
    {
        return Order::withTotals()
            ->whereBetween('orders.order_date', [$from->toDateString(), $to->toDateString()])
            ->where('orders.status', '!=', 'cancelled')
            ->get();
    }

    /** @return array{0: Carbon, 1: Carbon} defaults to this month so far */
    private function range(Request $request): array
    {
        $from = $this->dateFilter($request, 'from') ?? now()->startOfMonth();
        $to = $this->dateFilter($request, 'to') ?? today();

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$from->copy()->startOfDay(), $to->copy()->endOfDay()];
    }
}
