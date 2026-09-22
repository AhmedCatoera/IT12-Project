@extends('layouts.app')

@section('title', 'Orders')

@section('styles')
<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .page-title h2 {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--text-main);
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--primary), var(--primary-hover));
        color: white;
        text-decoration: none;
        padding: 9px 18px;
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.88rem;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border: none;
        cursor: pointer;
    }

    .btn-secondary {
        background-color: white;
        border: 1px solid var(--border);
        color: var(--text-main);
        text-decoration: none;
        padding: 6px 12px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.82rem;
        display: inline-flex;
        align-items: center;
    }

    .btn-secondary:hover {
        background-color: #f1f5f9;
    }

    .card {
        background-color: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.5rem;
        box-shadow: var(--shadow);
    }

    .status-tabs {
        display: flex;
        gap: 8px;
        overflow-x: auto;
        padding-bottom: 0.75rem;
        margin-bottom: 1.25rem;
        border-bottom: 1px solid var(--border);
    }

    .status-tab {
        text-decoration: none;
        font-size: 0.82rem;
        font-weight: 700;
        padding: 6px 12px;
        border-radius: 20px;
        color: var(--text-muted);
        background-color: #f1f5f9;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.15s;
    }

    .status-tab:hover {
        background-color: #e2e8f0;
        color: var(--text-main);
    }

    .status-tab.active {
        background-color: var(--primary);
        color: white;
    }

    .status-pill-count {
        background-color: rgba(0,0,0,0.1);
        padding: 1px 6px;
        border-radius: 10px;
        font-size: 0.72rem;
    }

    .filter-row {
        display: flex;
        gap: 10px;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
    }

    .search-input {
        flex: 1;
        min-width: 250px;
        padding: 9px 14px;
        border-radius: 8px;
        border: 1px solid var(--border);
        font-family: inherit;
        font-size: 0.88rem;
    }

    .table-responsive {
        overflow-x: auto;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.88rem;
    }

    .table th {
        text-align: left;
        padding: 10px 14px;
        color: var(--text-muted);
        font-weight: 700;
        border-bottom: 1px solid var(--border);
        background-color: #f8fafc;
    }

    .table td {
        padding: 12px 14px;
        border-bottom: 1px solid #f1f5f9;
    }

    .badge-status {
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        display: inline-block;
    }

    .status-pending { background-color: #fef3c7; color: #b45309; }
    .status-confirmed { background-color: #dbeafe; color: #1d4ed8; }
    .status-in_production { background-color: #e0e7ff; color: #4338ca; }
    .status-ready_for_release { background-color: #d1fae5; color: #047857; }
    .status-out_for_delivery { background-color: #ffedd5; color: #c2410c; }
    .status-completed { background-color: #dcfce7; color: #15803d; }
    .status-cancelled { background-color: #fee2e2; color: #b91c1c; }

    .payment-badge {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
    }
    .payment-fully_paid { color: #10b981; }
    .payment-partially_paid { color: #f59e0b; }
    .payment-unpaid { color: #ef4444; }
</style>
@endsection

@section('content')
<div class="page-header">
    <div class="page-title">
        <h2>Order Management</h2>
        <p>Record and track customer orders, customized cakes, pastries, and souvenirs</p>
    </div>
    <a href="{{ route('orders.create') }}" class="btn-primary">
        + Create New Order
    </a>
</div>

<div class="card">
    <!-- Status Filter Tabs -->
    <div class="status-tabs">
        <a href="{{ route('orders.index') }}" class="status-tab {{ empty($status) ? 'active' : '' }}">
            All Orders <span class="status-pill-count">{{ $statusCounts['all'] }}</span>
        </a>
        <a href="{{ route('orders.index', ['status' => 'pending']) }}" class="status-tab {{ $status === 'pending' ? 'active' : '' }}">
            Pending <span class="status-pill-count">{{ $statusCounts['pending'] }}</span>
        </a>
        <a href="{{ route('orders.index', ['status' => 'confirmed']) }}" class="status-tab {{ $status === 'confirmed' ? 'active' : '' }}">
            Confirmed <span class="status-pill-count">{{ $statusCounts['confirmed'] }}</span>
        </a>
        <a href="{{ route('orders.index', ['status' => 'in_production']) }}" class="status-tab {{ $status === 'in_production' ? 'active' : '' }}">
            In Production <span class="status-pill-count">{{ $statusCounts['in_production'] }}</span>
        </a>
        <a href="{{ route('orders.index', ['status' => 'ready_for_release']) }}" class="status-tab {{ $status === 'ready_for_release' ? 'active' : '' }}">
            Ready for Release <span class="status-pill-count">{{ $statusCounts['ready_for_release'] }}</span>
        </a>
        <a href="{{ route('orders.index', ['status' => 'out_for_delivery']) }}" class="status-tab {{ $status === 'out_for_delivery' ? 'active' : '' }}">
            Out for Delivery <span class="status-pill-count">{{ $statusCounts['out_for_delivery'] }}</span>
        </a>
        <a href="{{ route('orders.index', ['status' => 'completed']) }}" class="status-tab {{ $status === 'completed' ? 'active' : '' }}">
            Completed <span class="status-pill-count">{{ $statusCounts['completed'] }}</span>
        </a>
    </div>

    <!-- Search & Type Filter -->
    <form method="GET" action="{{ route('orders.index') }}" class="filter-row">
        @if($status)
            <input type="hidden" name="status" value="{{ $status }}">
        @endif
        <input 
            type="text" 
            name="search" 
            value="{{ $search }}" 
            placeholder="Search by order #, customer name, or phone..." 
            class="search-input"
        >
        <select name="type" class="search-input" style="max-width: 160px;">
            <option value="">All Types</option>
            <option value="pickup" {{ $type === 'pickup' ? 'selected' : '' }}>Shop Pickup</option>
            <option value="delivery" {{ $type === 'delivery' ? 'selected' : '' }}>Delivery</option>
        </select>
        <button type="submit" class="btn-primary">Filter</button>
        @if($search || $type || $status)
            <a href="{{ route('orders.index') }}" class="btn-secondary">Reset</a>
        @endif
    </form>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Scheduled Date</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Total Amount</th>
                    <th>Paid / Balance</th>
                    <th>Payment</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                <tr>
                    <td>
                        <a href="{{ route('orders.show', $order) }}" style="font-weight: 700; color: var(--primary); text-decoration: none;">
                            {{ $order->order_number }}
                        </a>
                    </td>
                    <td>
                        <strong>{{ $order->customer->name }}</strong>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $order->customer->contact_number }}</div>
                    </td>
                    <td>
                        <strong>{{ $order->scheduled_date->format('M d, Y') }}</strong>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $order->scheduled_date->format('h:i A') }}</div>
                    </td>
                    <td>
                        <span style="text-transform: capitalize; font-size: 0.8rem; font-weight: 600;">
                            {{ $order->order_type === 'delivery' ? '🛵 Delivery' : '🏪 Pickup' }}
                        </span>
                    </td>
                    <td>
                        <span class="badge-status status-{{ $order->status }}">
                            {{ str_replace('_', ' ', $order->status) }}
                        </span>
                    </td>
                    <td style="font-weight: 700;">₱{{ number_format($order->total_amount, 2) }}</td>
                    <td>
                        <div style="color: #10b981; font-weight: 600; font-size: 0.82rem;">Paid: ₱{{ number_format($order->total_paid, 2) }}</div>
                        <div style="color: {{ $order->balance_due > 0 ? '#e11d48' : '#64748b' }}; font-size: 0.78rem;">
                            Bal: ₱{{ number_format($order->balance_due, 2) }}
                        </div>
                    </td>
                    <td>
                        <span class="payment-badge payment-{{ $order->payment_status }}">
                            {{ str_replace('_', ' ', $order->payment_status) }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('orders.show', $order) }}" class="btn-secondary" style="margin-right: 4px;">
                            View
                        </a>
                        <a href="{{ route('orders.edit', $order) }}" class="btn-secondary">
                            Edit
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="text-align: center; color: var(--text-muted); padding: 2.5rem;">
                        No orders match the selected filters. Click <strong>"+ Create New Order"</strong> to book an order.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1.5rem;">
        {{ $orders->links() }}
    </div>
</div>
@endsection
