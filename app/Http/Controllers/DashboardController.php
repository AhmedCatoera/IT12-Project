<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\Order;
use App\Models\Production;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Display the role-based dashboard.
     */
    public function index()
    {
        $user = Auth::user();

        $stats = [
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'in_production' => Order::where('status', 'in_production')->count(),
            'ready_orders' => Order::where('status', 'ready_for_release')->count(),
            'low_stock_count' => Ingredient::whereColumn('current_stock', '<=', 'reorder_level')->count(),
        ];

        $recentOrders = Order::with('customer')
            ->latest()
            ->take(5)
            ->get();

        return view('dashboard', compact('user', 'stats', 'recentOrders'));
    }
}
