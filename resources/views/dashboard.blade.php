@extends('layouts.app')

@section('title', 'Dashboard')

@section('styles')
<style>
    .welcome-card {
        background: linear-gradient(135deg, #be123c, #fb7185);
        border-radius: var(--radius);
        padding: 2rem;
        color: white;
        margin-bottom: 2rem;
        box-shadow: 0 10px 25px -5px rgba(225, 29, 72, 0.3);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1.5rem;
    }

    .welcome-text h2 {
        font-size: 1.75rem;
        font-weight: 800;
        margin-bottom: 6px;
    }

    .welcome-text p {
        font-size: 0.95rem;
        opacity: 0.9;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1.25rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background-color: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.5rem;
        box-shadow: var(--shadow);
        transition: transform 0.15s;
    }

    .stat-card:hover {
        transform: translateY(-2px);
    }

    .stat-label {
        font-size: 0.8rem;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 6px;
    }

    .stat-value {
        font-size: 1.85rem;
        font-weight: 800;
        color: var(--text-main);
    }

    .stat-badge {
        font-size: 0.75rem;
        padding: 3px 8px;
        border-radius: 6px;
        font-weight: 600;
    }

    .section-card {
        background-color: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.5rem;
        box-shadow: var(--shadow);
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.25rem;
        border-bottom: 1px solid var(--border);
        padding-bottom: 0.75rem;
    }

    .section-title {
        font-size: 1.1rem;
        font-weight: 700;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.88rem;
    }

    .table th {
        text-align: left;
        padding: 10px 12px;
        color: var(--text-muted);
        font-weight: 700;
        border-bottom: 1px solid var(--border);
    }

    .table td {
        padding: 12px;
        border-bottom: 1px solid #f1f5f9;
    }

    .badge-status {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
    }

    .status-pending { background-color: #fef3c7; color: #b45309; }
    .status-confirmed { background-color: #dbeafe; color: #1d4ed8; }
    .status-in_production { background-color: #e0e7ff; color: #4338ca; }
    .status-ready_for_release { background-color: #d1fae5; color: #047857; }
</style>
@endsection

@section('content')
<div class="welcome-card">
    <div class="welcome-text">
        <h2>Welcome, {{ $user->name }}!</h2>
        <p>You are logged in as <strong>{{ ucfirst($user->role) }}</strong> • SweetNest Order & Inventory Management System</p>
    </div>
    <div>
        <span class="role-badge badge-{{ $user->role }}" style="font-size: 0.9rem; padding: 8px 16px;">
            Active Role: {{ strtoupper($user->role) }}
        </span>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Total Orders</div>
        <div class="stat-value">{{ $stats['total_orders'] }}</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Pending Confirmation</div>
        <div class="stat-value" style="color: #f59e0b;">{{ $stats['pending_orders'] }}</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">In Production (Baking)</div>
        <div class="stat-value" style="color: #6366f1;">{{ $stats['in_production'] }}</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Ready for Release / Delivery</div>
        <div class="stat-value" style="color: #10b981;">{{ $stats['ready_orders'] }}</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Low Stock Alerts</div>
        <div class="stat-value" style="color: {{ $stats['low_stock_count'] > 0 ? '#ef4444' : '#10b981' }};">
            {{ $stats['low_stock_count'] }}
        </div>
    </div>
</div>

<div class="section-card">
    <div class="section-header">
        <div class="section-title">Recent Orders Overview</div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Order #</th>
                <th>Customer</th>
                <th>Scheduled Date</th>
                <th>Type</th>
                <th>Status</th>
                <th>Total</th>
                <th>Payment</th>
            </tr>
        </thead>
        <tbody>
            @forelse($recentOrders as $order)
            <tr>
                <td style="font-weight: 700;">{{ $order->order_number }}</td>
                <td>{{ $order->customer->name }}</td>
                <td>{{ $order->scheduled_date->format('M d, Y h:i A') }}</td>
                <td><span style="text-transform: capitalize;">{{ $order->order_type }}</span></td>
                <td>
                    <span class="badge-status status-{{ $order->status }}">
                        {{ str_replace('_', ' ', $order->status) }}
                    </span>
                </td>
                <td style="font-weight: 700;">₱{{ number_format($order->total_amount, 2) }}</td>
                <td>
                    <span style="font-size: 0.8rem; font-weight: 600; color: {{ $order->payment_status === 'fully_paid' ? '#10b981' : '#f59e0b' }};">
                        {{ strtoupper(str_replace('_', ' ', $order->payment_status)) }}
                    </span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                    No orders recorded yet.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
