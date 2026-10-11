<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\Order;
use App\Models\OrderItem;

class DashboardController extends Controller
{
    /**
     * Staff see only what needs doing: what is due today and tomorrow, and what is low.
     * The Owner also gets the sales chart for the last 14 days.
     */
    public function index()
    {
        $today = today();
        $isOwner = auth()->user()->isOwner();

        // What needs attention first: open orders due today or tomorrow, plus overdue ones.
        // Five per page so the card never grows into a long scroll.
        $dueSoon = Order::withTotals()
            ->with(['customer', 'jobOrder'])
            ->open()
            ->where('scheduled_at', '<=', $today->copy()->addDay()->endOfDay())
            ->orderBy('scheduled_at')
            ->orderBy('id')
            ->paginateOrLast(5);

        $lowStock = Ingredient::active()->withStock()->lowStock()->orderBy('name')->limit(6)->get();

        $salesDays = collect();

        if ($isOwner) {
            // Sales booked per day for the last 14 days (cancelled orders left out).
            $from = $today->copy()->subDays(13);
            // Per order, so rush fees count as sales too.
            $byDay = Order::withTotals()
                ->whereBetween('orders.order_date', [$from->toDateString(), $today->toDateString()])
                ->where('orders.status', '!=', 'cancelled')
                ->get()
                ->groupBy(fn (Order $order) => $order->order_date->toDateString())
                ->map(fn ($orders) => round($orders->sum('total_amount'), 2));

            $salesDays = collect(range(0, 13))->map(function ($i) use ($from, $byDay) {
                $day = $from->copy()->addDays($i);

                return ['date' => $day, 'total' => (float) ($byDay[$day->toDateString()] ?? 0)];
            });
        }

        return view('dashboard', compact('dueSoon', 'lowStock', 'salesDays', 'isOwner'));
    }
}
