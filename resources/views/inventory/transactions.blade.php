@extends('layouts.app')

@section('title', 'Inventory Transaction Ledger')

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
        min-width: 200px;
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
        vertical-align: middle;
    }

    .badge-type {
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 0.72rem;
        font-weight: 800;
        text-transform: uppercase;
        display: inline-block;
    }
    .type-stock_in { background-color: #d1fae5; color: #047857; }
    .type-production_usage { background-color: #dbeafe; color: #1d4ed8; }
    .type-audit_adjustment { background-color: #fef3c7; color: #b45309; }
    .type-spoilage { background-color: #fee2e2; color: #b91c1c; }
</style>
@endsection

@section('content')
<div class="page-header">
    <div class="page-title">
        <h2>Inventory Transaction Audit Ledger</h2>
        <p>Complete historical log of all purchases, baking consumption, and manual adjustments</p>
    </div>
    <div>
        <a href="{{ route('inventory.index') }}" class="btn-secondary">
            ← Back to Inventory
        </a>
    </div>
</div>

<div class="card">
    <!-- Filter Bar -->
    <form method="GET" action="{{ route('inventory.transactions') }}" class="filter-bar">
        <input 
            type="text" 
            name="search" 
            value="{{ $search }}" 
            placeholder="Search by ingredient, reference, or remarks..." 
            class="search-input"
        >

        <select name="type" class="search-input" style="max-width: 180px;">
            <option value="">All Movement Types</option>
            <option value="stock_in" {{ $type === 'stock_in' ? 'selected' : '' }}>Stock In (Purchases)</option>
            <option value="production_usage" {{ $type === 'production_usage' ? 'selected' : '' }}>Production Usage</option>
            <option value="audit_adjustment" {{ $type === 'audit_adjustment' ? 'selected' : '' }}>Audit Adjustment</option>
            <option value="spoilage" {{ $type === 'spoilage' ? 'selected' : '' }}>Spoilage / Loss</option>
        </select>

        <select name="ingredient_id" class="search-input" style="max-width: 200px;">
            <option value="">All Ingredients</option>
            @foreach($ingredients as $ing)
                <option value="{{ $ing->id }}" {{ $ingredientId == $ing->id ? 'selected' : '' }}>
                    {{ $ing->name }}
                </option>
            @endforeach
        </select>

        <button type="submit" class="btn-primary">Filter</button>
        @if($search || $type || $ingredientId)
            <a href="{{ route('inventory.transactions') }}" class="btn-secondary">Clear</a>
        @endif
    </form>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Ingredient</th>
                    <th>Transaction Type</th>
                    <th style="text-align: right;">Quantity</th>
                    <th>Reference / Source</th>
                    <th>Cost (₱)</th>
                    <th>Exp. Date</th>
                    <th>Logged By</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $txn)
                <tr>
                    <td>
                        <strong>{{ $txn->created_at->format('M d, Y') }}</strong>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $txn->created_at->format('h:i A') }}</div>
                    </td>
                    <td>
                        <strong style="font-size: 0.92rem;">{{ $txn->ingredient->name }}</strong>
                    </td>
                    <td>
                        <span class="badge-type type-{{ $txn->transaction_type }}">
                            {{ str_replace('_', ' ', $txn->transaction_type) }}
                        </span>
                    </td>
                    <td style="text-align: right; font-weight: 800; font-size: 0.95rem; color: {{ $txn->quantity > 0 ? '#10b981' : '#e11d48' }};">
                        {{ $txn->quantity > 0 ? '+' : '' }}{{ number_format($txn->quantity, 2) }} {{ $txn->ingredient->unit }}
                    </td>
                    <td>
                        <div style="font-weight: 600;">{{ $txn->reference ?: '—' }}</div>
                        @if($txn->remarks)
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">{{ $txn->remarks }}</div>
                        @endif
                    </td>
                    <td>
                        @if($txn->total_cost)
                            <strong>₱{{ number_format($txn->total_cost, 2) }}</strong>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">@ ₱{{ number_format($txn->unit_cost, 2) }}</div>
                        @else
                            <span style="color: var(--text-muted);">—</span>
                        @endif
                    </td>
                    <td>
                        {{ $txn->expiration_date ? $txn->expiration_date->format('M d, Y') : '—' }}
                    </td>
                    <td>
                        <span style="font-size: 0.8rem; color: var(--text-muted);">
                            {{ $txn->creator?->name ?: 'System' }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 2.5rem;">
                        No inventory transactions found matching the filter criteria.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1.5rem;">
        {{ $transactions->links() }}
    </div>
</div>
@endsection
