@extends('layouts.app')

@section('title', 'Order & Sales Report - SweetNest OIMS')

@section('styles')
<style>
    .filter-panel {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 1.25rem 1.5rem;
        margin-bottom: 1.5rem;
    }

    .filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 1rem;
        align-items: flex-end;
    }

    .summary-cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .summary-card {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 1.25rem;
    }

    .summary-label {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--text-muted);
        letter-spacing: 0.04em;
        margin-bottom: 4px;
    }

    .summary-value {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--text-main);
    }

    .print-header {
        display: none;
    }

    @media print {
        body {
            background: #ffffff !important;
            font-size: 12pt;
        }

        .navbar, .filter-panel, .pagination, .no-print, .btn-action, .mobile-toggle {
            display: none !important;
        }

        .print-header {
            display: block !important;
            text-align: center;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .print-header h1 {
            font-size: 18pt;
            margin: 0;
            color: #000;
        }

        .print-header p {
            margin: 4px 0 0 0;
            font-size: 10pt;
            color: #555;
        }

        .card {
            box-shadow: none !important;
            border: 1px solid #ccc !important;
            padding: 0 !important;
        }

        .table th, .table td {
            padding: 6px 8px !important;
            font-size: 9pt !important;
        }
    }
</style>
@endsection

@section('content')
<!-- Print Only Header -->
<div class="print-header">
    <h1>SweetNest Homemade Cakes and Pastries</h1>
    <p>Brgy. Santo Niño Phase 1, Tugbok, Davao City</p>
    <h3 style="margin-top: 8px; font-size: 13pt;">ORDER & SALES SUMMARY REPORT</h3>
    <p>Generated on: {{ now()->format('F d, Y h:i A') }}</p>
</div>

<div class="no-print" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <a href="{{ route('reports.index') }}" style="font-size: 0.85rem; color: var(--text-muted); text-decoration: none; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 6px;">
            &larr; Back to Reports Hub
        </a>
        <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--text-main); margin: 0;">
            🎂 Order & Sales Report
        </h2>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin: 4px 0 0 0;">
            Sales volume, deposit collections, and fulfillment records
        </p>
    </div>

    <div style="display: flex; gap: 10px;">
        <button onclick="window.print()" class="btn-primary" style="display: inline-flex; align-items: center; gap: 6px; background-color: #334155;">
            🖨️ Print / Export PDF
        </button>
    </div>
</div>

<!-- Filter Panel (No Print) -->
<div class="filter-panel no-print">
    <form method="GET" action="{{ route('reports.orders') }}" class="filter-grid">
        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label" style="font-size: 0.8rem;">Time Period</label>
            <select name="period" class="form-select" onchange="toggleCustomDates(this.value)">
                <option value="this_month" {{ $period === 'this_month' ? 'selected' : '' }}>This Month</option>
                <option value="today" {{ $period === 'today' ? 'selected' : '' }}>Today</option>
                <option value="this_week" {{ $period === 'this_week' ? 'selected' : '' }}>This Week</option>
                <option value="last_month" {{ $period === 'last_month' ? 'selected' : '' }}>Last Month</option>
                <option value="this_year" {{ $period === 'this_year' ? 'selected' : '' }}>This Year</option>
                <option value="all" {{ $period === 'all' ? 'selected' : '' }}>All Time</option>
                <option value="custom" {{ $period === 'custom' ? 'selected' : '' }}>Custom Date Range</option>
            </select>
        </div>

        <div class="form-group custom-date-field" style="margin-bottom: 0; display: {{ $period === 'custom' ? 'block' : 'none' }};">
            <label class="form-label" style="font-size: 0.8rem;">Start Date</label>
            <input type="date" name="start_date" class="form-input" value="{{ $startDateInput }}">
        </div>

        <div class="form-group custom-date-field" style="margin-bottom: 0; display: {{ $period === 'custom' ? 'block' : 'none' }};">
            <label class="form-label" style="font-size: 0.8rem;">End Date</label>
            <input type="date" name="end_date" class="form-input" value="{{ $endDateInput }}">
        </div>

        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label" style="font-size: 0.8rem;">Order Status</label>
            <select name="status" class="form-select">
                <option value="">All Statuses</option>
                <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="confirmed" {{ $status === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                <option value="in_production" {{ $status === 'in_production' ? 'selected' : '' }}>In Production</option>
                <option value="ready_for_release" {{ $status === 'ready_for_release' ? 'selected' : '' }}>Ready for Release</option>
                <option value="out_for_delivery" {{ $status === 'out_for_delivery' ? 'selected' : '' }}>Out for Delivery</option>
                <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="cancelled" {{ $status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
        </div>

        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label" style="font-size: 0.8rem;">Order Type</label>
            <select name="order_type" class="form-select">
                <option value="">All Types</option>
                <option value="pickup" {{ $orderType === 'pickup' ? 'selected' : '' }}>🏪 Pickup</option>
                <option value="delivery" {{ $orderType === 'delivery' ? 'selected' : '' }}>🛵 Delivery</option>
            </select>
        </div>

        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn-primary" style="padding: 10px 16px; font-size: 0.85rem;">
                Filter
            </button>
            <a href="{{ route('reports.orders') }}" class="btn-secondary" style="padding: 10px 14px; font-size: 0.85rem; text-decoration: none;">
                Reset
            </a>
        </div>
    </form>
</div>

<!-- Aggregated KPI Summary Cards -->
<div class="summary-cards-grid">
    <div class="summary-card">
        <div class="summary-label">Total Orders</div>
        <div class="summary-value">{{ number_format($summary['count']) }}</div>
        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">
            {{ $summary['completed_count'] }} Completed | {{ $summary['cancelled_count'] }} Cancelled
        </div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Gross Sales Volume</div>
        <div class="summary-value" style="color: #059669;">₱{{ number_format($summary['total_sales'], 2) }}</div>
        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">Excludes cancelled orders</div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Total Collected</div>
        <div class="summary-value" style="color: var(--primary);">₱{{ number_format($summary['total_paid'], 2) }}</div>
        <div style="font-size: 0.75rem; color: #10b981; font-weight: 600; margin-top: 4px;">Confirmed Payments</div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Unpaid Balance</div>
        <div class="summary-value" style="color: {{ $summary['pending_balance'] > 0 ? '#d97706' : '#10b981' }};">
            ₱{{ number_format($summary['pending_balance'], 2) }}
        </div>
        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">Due before release</div>
    </div>
</div>

<!-- Detailed Orders Table -->
<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>Order #</th>
                <th>Order Date</th>
                <th>Scheduled Date</th>
                <th>Customer</th>
                <th>Type</th>
                <th>Status</th>
                <th style="text-align: right;">Total</th>
                <th style="text-align: right;">Paid</th>
                <th style="text-align: right;">Balance</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
                @php
                    $paid = $order->payments->sum('amount');
                    $balance = max(0, $order->total_amount - $paid);
                @endphp
                <tr>
                    <td style="font-weight: 800; font-family: monospace; color: var(--primary);">
                        {{ $order->order_number }}
                    </td>
                    <td>{{ \Carbon\Carbon::parse($order->order_date)->format('M d, Y') }}</td>
                    <td style="font-weight: 600;">{{ \Carbon\Carbon::parse($order->scheduled_date)->format('M d, Y') }}</td>
                    <td>
                        <div style="font-weight: 700;">{{ $order->customer->name }}</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $order->customer->contact_number }}</div>
                    </td>
                    <td>
                        <span class="badge {{ $order->order_type === 'delivery' ? 'badge-info' : 'badge-warning' }}" style="font-size: 0.72rem;">
                            {{ $order->order_type === 'delivery' ? '🛵 Delivery' : '🏪 Pickup' }}
                        </span>
                    </td>
                    <td>
                        <span class="badge status-{{ $order->status }}" style="font-size: 0.72rem;">
                            {{ str_replace('_', ' ', ucfirst($order->status)) }}
                        </span>
                    </td>
                    <td style="text-align: right; font-weight: 700;">₱{{ number_format($order->total_amount, 2) }}</td>
                    <td style="text-align: right; color: #059669; font-weight: 600;">₱{{ number_format($paid, 2) }}</td>
                    <td style="text-align: right; font-weight: 800; color: {{ $balance > 0 ? '#d97706' : '#10b981' }};">
                        ₱{{ number_format($balance, 2) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align: center; color: var(--text-muted); padding: 2.5rem 0;">
                        No orders match the selected filters.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="pagination no-print" style="margin-top: 1.5rem;">
        {{ $orders->links() }}
    </div>
</div>
@endsection

@section('scripts')
<script>
    function toggleCustomDates(value) {
        const fields = document.querySelectorAll('.custom-date-field');
        fields.forEach(field => {
            field.style.display = (value === 'custom') ? 'block' : 'none';
        });
    }
</script>
@endsection
