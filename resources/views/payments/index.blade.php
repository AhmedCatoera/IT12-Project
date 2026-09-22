@extends('layouts.app')

@section('title', 'Payments')

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
    }

    .filter-bar {
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

    .badge-method {
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 0.72rem;
        font-weight: 800;
        text-transform: uppercase;
    }
    .method-cash { background-color: #d1fae5; color: #047857; }
    .method-gcash { background-color: #dbeafe; color: #1d4ed8; }
    .method-bank_transfer { background-color: #fef3c7; color: #b45309; }
    .method-other { background-color: #f1f5f9; color: #475569; }

    .badge-type {
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: capitalize;
    }
</style>
@endsection

@section('content')
<div class="page-header">
    <div class="page-title">
        <h2>Payment Management</h2>
        <p>Record and monitor customer down payments and final balance settlements</p>
    </div>
</div>

<!-- Financial Summary Cards -->
<div class="stats-row">
    <div class="stat-card">
        <div class="stat-label">Total Revenue Collected</div>
        <div class="stat-value" style="color: #10b981;">₱{{ number_format($stats['total_collected'], 2) }}</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">GCash Receipts</div>
        <div class="stat-value" style="color: #2563eb;">₱{{ number_format($stats['gcash_collected'], 2) }}</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Cash Receipts</div>
        <div class="stat-value" style="color: #059669;">₱{{ number_format($stats['cash_collected'], 2) }}</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Total Transactions</div>
        <div class="stat-value">{{ $stats['total_transactions'] }}</div>
    </div>
</div>

<div class="card">
    <!-- Filter & Search Bar -->
    <form method="GET" action="{{ route('payments.index') }}" class="filter-bar">
        <input 
            type="text" 
            name="search" 
            value="{{ $search }}" 
            placeholder="Search by order #, customer name, or reference ID..." 
            class="search-input"
        >
        <select name="method" class="search-input" style="max-width: 160px;">
            <option value="">All Methods</option>
            <option value="cash" {{ $method === 'cash' ? 'selected' : '' }}>Cash</option>
            <option value="gcash" {{ $method === 'gcash' ? 'selected' : '' }}>GCash</option>
            <option value="bank_transfer" {{ $method === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
        </select>
        <select name="type" class="search-input" style="max-width: 170px;">
            <option value="">All Payment Types</option>
            <option value="down_payment" {{ $type === 'down_payment' ? 'selected' : '' }}>50% Down Payment</option>
            <option value="balance_payment" {{ $type === 'balance_payment' ? 'selected' : '' }}>Balance Settlement</option>
            <option value="full_payment" {{ $type === 'full_payment' ? 'selected' : '' }}>Full Payment</option>
        </select>
        <button type="submit" class="btn-primary">Filter</button>
        @if($search || $method || $type)
            <a href="{{ route('payments.index') }}" class="btn-secondary">Clear</a>
        @endif
    </form>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Payment Date</th>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Payment Type</th>
                    <th>Method</th>
                    <th>Reference / Receipt</th>
                    <th style="text-align: right;">Amount Paid</th>
                    <th>Received By</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                <tr>
                    <td>
                        <strong>{{ $payment->payment_date->format('M d, Y') }}</strong>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $payment->payment_date->format('h:i A') }}</div>
                    </td>
                    <td>
                        <a href="{{ route('orders.show', $payment->order) }}" style="font-weight: 700; color: var(--primary); text-decoration: none;">
                            {{ $payment->order->order_number }}
                        </a>
                    </td>
                    <td>
                        <strong>{{ $payment->order->customer->name }}</strong>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $payment->order->customer->contact_number }}</div>
                    </td>
                    <td>
                        <span class="badge-type">
                            {{ str_replace('_', ' ', $payment->payment_type) }}
                        </span>
                    </td>
                    <td>
                        <span class="badge-method method-{{ $payment->payment_method }}">
                            {{ $payment->payment_method }}
                        </span>
                    </td>
                    <td>
                        {{ $payment->reference_number ?: '—' }}
                    </td>
                    <td style="text-align: right; font-weight: 800; font-size: 0.95rem; color: #10b981;">
                        ₱{{ number_format($payment->amount, 2) }}
                    </td>
                    <td>
                        <span style="font-size: 0.8rem; color: var(--text-muted);">
                            {{ $payment->receiver?->name ?: 'Staff' }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 2.5rem;">
                        No payment records found matching the criteria.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1.5rem;">
        {{ $payments->links() }}
    </div>
</div>
@endsection
