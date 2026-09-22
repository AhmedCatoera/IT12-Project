@extends('layouts.app')

@section('title', 'Ingredient Consumption Report - SweetNest OIMS')

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
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
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
    <h3 style="margin-top: 8px; font-size: 13pt;">INGREDIENT CONSUMPTION & USAGE REPORT</h3>
    <p>Reporting Range: {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} to {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}</p>
    <p>Generated on: {{ now()->format('F d, Y h:i A') }}</p>
</div>

<div class="no-print" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <a href="{{ route('reports.index') }}" style="font-size: 0.85rem; color: var(--text-muted); text-decoration: none; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 6px;">
            &larr; Back to Reports Hub
        </a>
        <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--text-main); margin: 0;">
            🥣 Ingredient Consumption Report
        </h2>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin: 4px 0 0 0;">
            Derived from kitchen baking logs and production records (Document Chapter 3, Objective #5 & Figure 6)
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
    <form method="GET" action="{{ route('reports.consumption') }}" class="filter-grid">
        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label" style="font-size: 0.8rem;">Start Production Date</label>
            <input type="date" name="start_date" class="form-input" value="{{ $startDate }}">
        </div>

        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label" style="font-size: 0.8rem;">End Production Date</label>
            <input type="date" name="end_date" class="form-input" value="{{ $endDate }}">
        </div>

        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label" style="font-size: 0.8rem;">Filter by Ingredient</label>
            <select name="ingredient_id" class="form-select">
                <option value="">All Ingredients</option>
                @foreach($ingredients as $ing)
                    <option value="{{ $ing->id }}" {{ $ingredientId == $ing->id ? 'selected' : '' }}>
                        {{ $ing->name }} ({{ $ing->unit }})
                    </option>
                @endforeach
            </select>
        </div>

        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn-primary" style="padding: 10px 16px; font-size: 0.85rem;">
                Filter
            </button>
            <a href="{{ route('reports.consumption') }}" class="btn-secondary" style="padding: 10px 14px; font-size: 0.85rem; text-decoration: none;">
                Reset
            </a>
        </div>
    </form>
</div>

<!-- KPI Cards -->
<div class="summary-cards-grid">
    <div class="summary-card">
        <div class="summary-label">Consumed Ingredients Types</div>
        <div class="summary-value" style="color: var(--primary);">
            {{ count($consumptionSummary) }}
        </div>
        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">Distinct raw materials</div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Consumption Entries</div>
        <div class="summary-value">{{ $totalConsumptionEntries }}</div>
        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">Within selected date window</div>
    </div>

    <div class="summary-card">
        <div class="summary-label">Total Kitchen Batch Records</div>
        <div class="summary-value" style="color: #059669;">{{ $detailedLogs->total() }}</div>
        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">Baking batch allocations</div>
    </div>
</div>

<!-- Aggregated Summary Table -->
<div class="card" style="margin-bottom: 2rem;">
    <div style="font-size: 1.15rem; font-weight: 800; margin-bottom: 1rem; color: var(--text-main);">
        📊 Consumption Summary by Ingredient
    </div>

    @if($consumptionSummary->isEmpty())
        <p style="color: var(--text-muted); font-size: 0.9rem; text-align: center; padding: 2rem 0;">
            No ingredient consumption recorded within this date range.
        </p>
    @else
        <table class="table">
            <thead>
                <tr>
                    <th>Ingredient Name</th>
                    <th>Unit</th>
                    <th style="text-align: right;">Batches Count</th>
                    <th style="text-align: right;">Total Consumed</th>
                </tr>
            </thead>
            <tbody>
                @foreach($consumptionSummary as $row)
                    <tr>
                        <td style="font-weight: 700; color: var(--text-main);">{{ $row->ingredient_name }}</td>
                        <td><span class="badge badge-info">{{ $row->unit }}</span></td>
                        <td style="text-align: right;">{{ $row->batch_count }} batches</td>
                        <td style="text-align: right; font-weight: 800; color: var(--primary);">
                            {{ number_format($row->total_quantity_used, 2) }} {{ $row->unit }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

<!-- Itemized Production Usage Ledger -->
<div class="card">
    <div style="font-size: 1.15rem; font-weight: 800; margin-bottom: 1rem; color: var(--text-main);">
        📝 Itemized Kitchen Deduction Log
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Batch #</th>
                <th>Order #</th>
                <th>Customer</th>
                <th>Ingredient</th>
                <th style="text-align: right;">Quantity</th>
                <th>Production Date</th>
                <th>Assigned Staff</th>
            </tr>
        </thead>
        <tbody>
            @forelse($detailedLogs as $log)
                <tr>
                    <td style="font-weight: 700; font-family: monospace;">
                        #{{ $log->production_id }}
                    </td>
                    <td style="font-weight: 800; color: var(--primary);">
                        {{ $log->production->order->order_number ?? 'N/A' }}
                    </td>
                    <td>
                        {{ $log->production->order->customer->name ?? 'Direct Kitchen Batch' }}
                    </td>
                    <td style="font-weight: 700;">
                        {{ $log->ingredient->name ?? 'Unknown' }}
                    </td>
                    <td style="text-align: right; font-weight: 800; color: #b91c1c;">
                        -{{ number_format($log->quantity_used, 2) }} {{ $log->ingredient->unit ?? '' }}
                    </td>
                    <td>
                        {{ \Carbon\Carbon::parse($log->production->production_date)->format('M d, Y') }}
                    </td>
                    <td>
                        {{ $log->production->baker->name ?? 'Staff' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2.5rem 0;">
                        No itemized baking consumption records found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="pagination no-print" style="margin-top: 1.5rem;">
        {{ $detailedLogs->links() }}
    </div>
</div>
@endsection
