@extends('layouts.app')

@section('title', 'Production Management - Kitchen Baking Queue')

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

    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1.25rem;
        margin-bottom: 1.5rem;
    }

    .stat-card {
        background-color: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.25rem 1.5rem;
        box-shadow: var(--shadow);
    }

    .stat-label {
        font-size: 0.78rem;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 4px;
    }

    .stat-value {
        font-size: 1.7rem;
        font-weight: 800;
        color: var(--text-main);
    }

    .card {
        background-color: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.5rem;
        box-shadow: var(--shadow);
        margin-bottom: 1.5rem;
    }

    .filter-bar {
        display: flex;
        gap: 10px;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
    }

    .search-input {
        flex: 1;
        min-width: 240px;
        padding: 9px 14px;
        border-radius: 8px;
        border: 1px solid var(--border);
        font-family: inherit;
        font-size: 0.88rem;
        background-color: white;
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--primary), var(--primary-hover));
        color: white;
        text-decoration: none;
        padding: 9px 18px;
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.88rem;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .btn-secondary {
        background-color: white;
        border: 1px solid var(--border);
        color: var(--text-main);
        text-decoration: none;
        padding: 8px 14px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.82rem;
    }

    .btn-action {
        font-size: 0.78rem;
        font-weight: 700;
        padding: 5px 10px;
        border-radius: 6px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        border: 1px solid transparent;
        cursor: pointer;
    }
    .btn-action-start { background-color: #fef3c7; color: #92400e; border-color: #fde68a; }
    .btn-action-start:hover { background-color: #fde68a; }
    .btn-action-view { background-color: #f1f5f9; color: #334155; border-color: #cbd5e1; }
    .btn-action-view:hover { background-color: #e2e8f0; }

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
        vertical-align: middle;
    }

    .badge-prod-status {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.72rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        display: inline-block;
    }
    .status-pending { background-color: #fef3c7; color: #b45309; }
    .status-in_progress { background-color: #dbeafe; color: #1d4ed8; animation: pulse 2s infinite; }
    .status-completed { background-color: #d1fae5; color: #047857; }
    .status-cancelled { background-color: #fee2e2; color: #b91c1c; }

    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.65; }
        100% { opacity: 1; }
    }

    .item-pill {
        display: inline-block;
        background: #f1f5f9;
        border-radius: 4px;
        padding: 2px 8px;
        font-size: 0.75rem;
        font-weight: 600;
        margin: 2px 2px 2px 0;
        color: #334155;
    }

    .awaiting-box {
        background: #fffbeb;
        border: 1px solid #fef3c7;
        border-radius: var(--radius);
        padding: 1.25rem 1.5rem;
        margin-bottom: 1.5rem;
    }
</style>
@endsection

@section('content')
<div class="page-header">
    <div class="page-title">
        <h2>Production Management</h2>
        <p>Manage baking queues, custom cake work orders, and ingredient consumption</p>
    </div>
    <div>
        <a href="{{ route('productions.create') }}" class="btn-primary">
            <span>➕</span> Schedule Production Batch
        </a>
    </div>
</div>

<!-- Production Statistics -->
<div class="stats-row">
    <div class="stat-card">
        <div class="stat-label">Currently In Oven / Baking</div>
        <div class="stat-value" style="color: #2563eb;">{{ $stats['in_progress'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Pending Baking Queue</div>
        <div class="stat-value" style="color: #d97706;">{{ $stats['pending_batches'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Completed Today</div>
        <div class="stat-value" style="color: #10b981;">{{ $stats['completed_today'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Confirmed Orders Awaiting Batch</div>
        <div class="stat-value" style="color: #e11d48;">{{ $stats['awaiting_queue'] }}</div>
    </div>
</div>

<!-- Confirmed Orders Ready for Production -->
@if($pendingConfirmedOrders->count() > 0)
<div class="awaiting-box">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
        <div>
            <h3 style="font-size: 1.05rem; font-weight: 800; color: #92400e; margin-bottom: 2px;">
                🔔 Confirmed Orders Awaiting Kitchen Queue ({{ $pendingConfirmedOrders->count() }})
            </h3>
            <p style="font-size: 0.82rem; color: #b45309;">
                Down payment confirmed. These orders need baking production scheduled.
            </p>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table" style="background: white; border-radius: 8px;">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Scheduled Release</th>
                    <th>Items to Bake</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pendingConfirmedOrders as $unbatched)
                <tr>
                    <td>
                        <a href="{{ route('orders.show', $unbatched) }}" style="font-weight: 800; color: var(--primary); text-decoration: none;">
                            {{ $unbatched->order_number }}
                        </a>
                    </td>
                    <td>
                        <strong>{{ $unbatched->customer->name }}</strong>
                    </td>
                    <td>
                        <strong>{{ $unbatched->scheduled_date->format('M d, Y') }}</strong>
                        <span style="font-size: 0.78rem; color: var(--text-muted);">{{ $unbatched->scheduled_date->format('h:i A') }}</span>
                    </td>
                    <td>
                        @foreach($unbatched->items as $item)
                            <span class="item-pill">{{ $item->quantity }}x {{ $item->item_name }}</span>
                        @endforeach
                    </td>
                    <td style="text-align: right;">
                        <a href="{{ route('productions.create', ['order_id' => $unbatched->id]) }}" class="btn-action btn-action-start">
                            👩‍🍳 Queue for Baking
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<!-- Production Batches Ledger -->
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 10px;">
        <h3 style="font-size: 1.15rem; font-weight: 800;">Production Batches & Schedules</h3>
    </div>

    <!-- Filter & Search Bar -->
    <form method="GET" action="{{ route('productions.index') }}" class="filter-bar">
        <input 
            type="text" 
            name="search" 
            value="{{ $search }}" 
            placeholder="Search by order # or customer name..." 
            class="search-input"
        >
        <select name="status" class="search-input" style="max-width: 170px;">
            <option value="">All Statuses</option>
            <option value="in_progress" {{ $status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
            <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>Completed</option>
            <option value="cancelled" {{ $status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
        </select>
        <input 
            type="date" 
            name="date" 
            value="{{ $date }}" 
            class="search-input" 
            style="max-width: 170px;"
        >
        <button type="submit" class="btn-primary" style="padding: 8px 16px;">Filter</button>
        @if($search || $status || $date)
            <a href="{{ route('productions.index') }}" class="btn-secondary">Clear</a>
        @endif
    </form>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Batch #</th>
                    <th>Target Order</th>
                    <th>Customer</th>
                    <th>Production Date</th>
                    <th>Assigned Baker</th>
                    <th>Status</th>
                    <th>Ingredients Used</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($productions as $production)
                <tr>
                    <td>
                        <strong style="color: var(--text-main);">#PROD-{{ str_pad($production->id, 4, '0', STR_PAD_LEFT) }}</strong>
                    </td>
                    <td>
                        <a href="{{ route('orders.show', $production->order) }}" style="font-weight: 800; color: var(--primary); text-decoration: none;">
                            {{ $production->order->order_number }}
                        </a>
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">
                            Due: {{ $production->order->scheduled_date->format('M d, h:i A') }}
                        </div>
                    </td>
                    <td>
                        <strong>{{ $production->order->customer->name }}</strong>
                    </td>
                    <td>
                        {{ $production->production_date->format('M d, Y') }}
                    </td>
                    <td>
                        @if($production->baker)
                            <span style="font-weight: 600;">{{ $production->baker->name }}</span>
                        @else
                            <span style="color: var(--text-muted); font-style: italic;">Unassigned</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge-prod-status status-{{ $production->status }}">
                            {{ str_replace('_', ' ', $production->status) }}
                        </span>
                    </td>
                    <td>
                        <span style="font-weight: 700; color: #0284c7;">
                            {{ $production->productionIngredients->count() }} items logged
                        </span>
                    </td>
                    <td style="text-align: right;">
                        <a href="{{ route('productions.show', $production) }}" class="btn-action btn-action-view">
                            📋 Work Order
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 2.5rem;">
                        No production batches found matching the criteria.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1.5rem;">
        {{ $productions->links() }}
    </div>
</div>
@endsection
