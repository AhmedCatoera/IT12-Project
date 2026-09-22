@extends('layouts.app')

@section('title', 'Inventory Movement & History - SweetNest OIMS')

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
        grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
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
        font-size: 1.4rem;
        font-weight: 800;
        color: var(--text-main);
    }

    .badge-stock_in { background-color: #d1fae5; color: #065f46; }
    .badge-production_usage { background-color: #fee2e2; color: #991b1b; }
    .badge-audit_adjustment { background-color: #e0e7ff; color: #3730a3; }
    .badge-spoilage { background-color: #fef3c7; color: #92400e; }

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
    <h3 style="margin-top: 8px; font-size: 13pt;">INVENTORY MOVEMENT & AUDIT HISTORY REPORT</h3>
    <p>Generated on: {{ now()->format('F d, Y h:i A') }}</p>
</div>

<div class="no-print" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <a href="{{ route('reports.index') }}" style="font-size: 0.85rem; color: var(--text-muted); text-decoration: none; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 6px;">
            &larr; Back to Reports Hub
        </a>
        <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--text-main); margin: 0;">
            📦 Inventory Movement & History
        </h2>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin: 4px 0 0 0;">
            Audit trail of all purchases, deductions, and shift balance adjustments (Document Chapter 3, Objective #5 & Figure 6)
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
    <form method="GET" action="{{ route('reports.inventoryHistory') }}" class="filter-grid">
        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label" style="font-size: 0.8rem;">Movement Type</label>
            <select name="type" class="form-select">
                <option value="">All Movement Types</option>
                <option value="stock_in" {{ $type === 'stock_in' ? 'selected' : '' }}>📥 Purchase Stock-In</option>
                <option value="production_usage" {{ $type === 'production_usage' ? 'selected' : '' }}>🥣 Kitchen Production</option>
                <option value="audit_adjustment" {{ $type === 'audit_adjustment' ? 'selected' : '' }}>📋 Shift Count Adjustment</option>
                <option value="spoilage" {{ $type === 'spoilage' ? 'selected' : '' }}>⚠️ Spoilage / Damage</option>
            </select>
        </div>

        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label" style="font-size: 0.8rem;">Ingredient</label>
            <select name="ingredient_id" class="form-select">
                <option value="">All Ingredients</option>
                @foreach($ingredients as $ing)
                    <option value="{{ $ing->id }}" {{ $ingredientId == $ing->id ? 'selected' : '' }}>
                        {{ $ing->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label" style="font-size: 0.8rem;">Start Date</label>
            <input type="date" name="start_date" class="form-input" value="{{ $startDate }}">
        </div>

        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label" style="font-size: 0.8rem;">End Date</label>
            <input type="date" name="end_date" class="form-input" value="{{ $endDate }}">
        </div>

        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn-primary" style="padding: 10px 16px; font-size: 0.85rem;">
                Filter
            </button>
            <a href="{{ route('reports.inventoryHistory') }}" class="btn-secondary" style="padding: 10px 14px; font-size: 0.85rem; text-decoration: none;">
                Reset
            </a>
        </div>
    </form>
</div>

<!-- Movement KPI Cards -->
<div class="summary-cards-grid">
    <div class="summary-card">
        <div class="summary-label">Total Purchase Inflow</div>
        <div class="summary-value" style="color: #059669;">
            ₱{{ number_format($movementStats['total_stock_in_cost'], 2) }}
        </div>
        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">{{ $movementStats['total_stock_in_count'] }} deliveries</div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Production Usages</div>
        <div class="summary-value" style="color: var(--primary);">
            {{ $movementStats['total_production_usage_count'] }}
        </div>
        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">Kitchen deductions</div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Shift Adjustments</div>
        <div class="summary-value" style="color: #4f46e5;">
            {{ $movementStats['total_audit_adjustments_count'] }}
        </div>
        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">Physical audit reconciliations</div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Spoilage Incidents</div>
        <div class="summary-value" style="color: #d97706;">
            {{ $movementStats['total_spoilage_count'] }}
        </div>
        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">Expired or damaged goods</div>
    </div>
</div>

<!-- Ledger Table -->
<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>Date / Time</th>
                <th>Ingredient</th>
                <th>Movement Type</th>
                <th style="text-align: right;">Quantity Change</th>
                <th style="text-align: right;">Cost</th>
                <th>Reference / Reason</th>
                <th>Recorded By</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $t)
                <tr>
                    <td>
                        <div style="font-weight: 700;">{{ \Carbon\Carbon::parse($t->transaction_date)->format('M d, Y') }}</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">{{ \Carbon\Carbon::parse($t->created_at)->format('h:i A') }}</div>
                    </td>
                    <td>
                        <div style="font-weight: 700; color: var(--text-main);">{{ $t->ingredient->name ?? 'Deleted Item' }}</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $t->ingredient->unit ?? '' }}</div>
                    </td>
                    <td>
                        <span class="badge badge-{{ $t->transaction_type }}" style="font-size: 0.72rem;">
                            @if($t->transaction_type === 'stock_in')
                                📥 Stock In
                            @elseif($t->transaction_type === 'production_usage')
                                🥣 Baking Usage
                            @elseif($t->transaction_type === 'audit_adjustment')
                                📋 Audit Reconcile
                            @elseif($t->transaction_type === 'spoilage')
                                ⚠️ Spoilage / Damage
                            @else
                                {{ ucfirst($t->transaction_type) }}
                            @endif
                        </span>
                    </td>
                    <td style="text-align: right; font-weight: 800; color: {{ $t->quantity > 0 ? '#059669' : '#b91c1c' }};">
                        {{ $t->quantity > 0 ? '+' : '' }}{{ number_format($t->quantity, 2) }} {{ $t->ingredient->unit ?? '' }}
                    </td>
                    <td style="text-align: right;">
                        @if($t->total_cost)
                            <div style="font-weight: 700;">₱{{ number_format($t->total_cost, 2) }}</div>
                            <div style="font-size: 0.72rem; color: var(--text-muted);">@ ₱{{ number_format($t->unit_cost ?? 0, 2) }}/{{ $t->ingredient->unit ?? '' }}</div>
                        @else
                            <span style="color: var(--text-muted); font-size: 0.8rem;">—</span>
                        @endif
                    </td>
                    <td>
                        <div style="font-size: 0.85rem; font-weight: 600;">{{ $t->reference ?: 'Internal movement' }}</div>
                        @if($t->remarks)
                            <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $t->remarks }}</div>
                        @endif
                    </td>
                    <td>
                        <span style="font-size: 0.82rem; font-weight: 600;">{{ $t->creator->name ?? 'System' }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2.5rem 0;">
                        No inventory transactions found for the selected filters.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="pagination no-print" style="margin-top: 1.5rem;">
        {{ $transactions->links() }}
    </div>
</div>
@endsection
