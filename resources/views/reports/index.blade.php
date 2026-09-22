@extends('layouts.app')

@section('title', 'Executive Reports - SweetNest OIMS')

@section('styles')
<style>
    .report-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 2rem;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .report-title h2 {
        font-size: 1.6rem;
        font-weight: 800;
        color: var(--text-main);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .report-title p {
        font-size: 0.9rem;
        color: var(--text-muted);
        margin-top: 4px;
    }

    .report-modules-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2.5rem;
    }

    .report-card {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 1.75rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition: all 0.25s ease;
        position: relative;
        overflow: hidden;
    }

    .report-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--card-accent, var(--primary));
    }

    .report-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 24px rgba(225, 29, 72, 0.08);
        border-color: #fca5a5;
    }

    .report-card-icon {
        font-size: 2.5rem;
        margin-bottom: 1rem;
        display: inline-block;
    }

    .report-card-title {
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--text-main);
        margin-bottom: 0.5rem;
    }

    .report-card-desc {
        font-size: 0.88rem;
        color: var(--text-muted);
        line-height: 1.6;
        margin-bottom: 1.5rem;
    }

    .report-card-action {
        align-self: flex-start;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 0.88rem;
        font-weight: 700;
        padding: 8px 18px;
        border-radius: 10px;
        background-color: var(--primary-light);
        color: var(--primary);
        text-decoration: none;
        transition: all 0.2s;
    }

    .report-card:hover .report-card-action {
        background-color: var(--primary);
        color: #ffffff;
    }

    .metrics-summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .metric-card {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 1.25rem 1.5rem;
    }

    .metric-label {
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--text-muted);
        margin-bottom: 6px;
    }

    .metric-val {
        font-size: 1.6rem;
        font-weight: 800;
        color: var(--text-main);
    }

    .metric-sub {
        font-size: 0.75rem;
        color: #10b981;
        font-weight: 600;
        margin-top: 4px;
    }

    .section-title {
        font-size: 1.15rem;
        font-weight: 800;
        color: var(--text-main);
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .two-col-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
    }

    @media (max-width: 900px) {
        .two-col-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection

@section('content')
<div class="report-header">
    <div class="report-title">
        <h2>📊 Executive Reports & Analytics</h2>
        <p>SweetNest Bakeshop Operational Intelligence — Document Chapter 3, Objective #5 & Figure 6</p>
    </div>
    <div>
        <span style="font-size: 0.82rem; font-weight: 700; color: #b91c1c; background: #fee2e2; padding: 6px 14px; border-radius: 20px; display: inline-flex; align-items: center; gap: 6px;">
            👑 Owner Confidential Access
        </span>
    </div>
</div>

<!-- High-Level Financial & Operational KPIs -->
<div class="metrics-summary-grid">
    <div class="metric-card">
        <div class="metric-label">Monthly Gross Revenue</div>
        <div class="metric-val" style="color: #059669;">₱{{ number_format($monthlyRevenue, 2) }}</div>
        <div class="metric-sub">{{ $monthlyOrders }} Orders this month</div>
    </div>

    <div class="metric-card">
        <div class="metric-label">Total Lifetime Revenue</div>
        <div class="metric-val">₱{{ number_format($totalRevenue, 2) }}</div>
        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">{{ $totalOrdersCount }} all-time valid orders</div>
    </div>

    <div class="metric-card">
        <div class="metric-label">Payments Received</div>
        <div class="metric-val" style="color: var(--primary);">₱{{ number_format($totalPaymentsReceived, 2) }}</div>
        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">Down: ₱{{ number_format($totalDownPayments, 0) }} | Final: ₱{{ number_format($totalFinalPayments, 0) }}</div>
    </div>

    <div class="metric-card">
        <div class="metric-label">Managed Ingredients</div>
        <div class="metric-val">{{ number_format($totalIngredientsCount) }} items</div>
        <div style="font-size: 0.75rem; color: {{ $lowStockCount > 0 ? '#dc2626' : '#10b981' }}; font-weight: 600; margin-top: 4px;">
            {{ $lowStockCount > 0 ? "⚠️ {$lowStockCount} items at reorder level" : '✅ Stock levels healthy' }}
        </div>
    </div>
</div>

<!-- Core Document-Specified Report Modules -->
<div class="section-title">
    📑 Formal Academic Report Modules
</div>

<div class="report-modules-grid">
    <!-- Module 1: Order & Sales Reports -->
    <div class="report-card" style="--card-accent: #e11d48;">
        <div>
            <div class="report-card-icon">🎂</div>
            <div class="report-card-title">Order & Sales Reports</div>
            <div class="report-card-desc">
                Comprehensive breakdown of customer order histories, custom cake volume, pickup vs. delivery distribution, down payments collected, and outstanding balances with print support.
            </div>
        </div>
        <a href="{{ route('reports.orders') }}" class="report-card-action">
            Open Orders Report &rarr;
        </a>
    </div>

    <!-- Module 2: Ingredient Consumption Reports -->
    <div class="report-card" style="--card-accent: #f59e0b;">
        <div>
            <div class="report-card-icon">🥣</div>
            <div class="report-card-title">Ingredient Consumption Reports</div>
            <div class="report-card-desc">
                Itemized analysis of raw baking ingredients consumed across kitchen batches. Tracks consumption frequency, batch linkage, and estimated ingredient costs.
            </div>
        </div>
        <a href="{{ route('reports.consumption') }}" class="report-card-action">
            Open Consumption Report &rarr;
        </a>
    </div>

    <!-- Module 3: Inventory Movement & History -->
    <div class="report-card" style="--card-accent: #3b82f6;">
        <div>
            <div class="report-card-icon">📦</div>
            <div class="report-card-title">Inventory History & Audits</div>
            <div class="report-card-desc">
                Chronological movement ledger capturing supplier purchase stock-ins, kitchen deductions, spoilage losses, and morning/afternoon shift audit discrepancies.
            </div>
        </div>
        <a href="{{ route('reports.inventoryHistory') }}" class="report-card-action">
            Open Inventory History &rarr;
        </a>
    </div>
</div>

<!-- Two-Column Operational Highlights -->
<div class="two-col-grid">
    <!-- Top Consumed Ingredients This Month -->
    <div class="card">
        <div class="section-title" style="margin-bottom: 1.25rem;">
            🧁 Top Consumed Ingredients (This Month)
        </div>

        @if($topIngredientsThisMonth->isEmpty())
            <p style="color: var(--text-muted); font-size: 0.9rem; text-align: center; padding: 2rem 0;">
                No ingredient consumption recorded yet this month.
            </p>
        @else
            <table class="table">
                <thead>
                    <tr>
                        <th>Ingredient</th>
                        <th>Unit</th>
                        <th style="text-align: right;">Quantity Used</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($topIngredientsThisMonth as $item)
                    <tr>
                        <td style="font-weight: 700; color: var(--text-main);">{{ $item->ingredient_name }}</td>
                        <td><span class="badge badge-info">{{ $item->unit }}</span></td>
                        <td style="text-align: right; font-weight: 800; color: var(--primary);">
                            {{ number_format($item->total_used, 2) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <!-- Recent Shift Count Audits -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
            <div class="section-title" style="margin-bottom: 0;">
                📋 Recent Shift Physical Audits
            </div>
            <a href="{{ route('audits.index') }}" style="font-size: 0.8rem; font-weight: 700; color: var(--primary);">View All &rarr;</a>
        </div>

        @if($recentAudits->isEmpty())
            <p style="color: var(--text-muted); font-size: 0.9rem; text-align: center; padding: 2rem 0;">
                No physical shift audits logged yet.
            </p>
        @else
            <table class="table">
                <thead>
                    <tr>
                        <th>Date / Shift</th>
                        <th>Auditor</th>
                        <th style="text-align: right;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentAudits as $audit)
                    <tr>
                        <td>
                            <div style="font-weight: 700;">{{ \Carbon\Carbon::parse($audit->audit_date)->format('M d, Y') }}</div>
                            <span class="badge {{ $audit->audit_shift === 'morning' ? 'badge-warning' : 'badge-info' }}" style="font-size: 0.7rem;">
                                {{ $audit->audit_shift === 'morning' ? '☀️ Morning' : '🌙 Afternoon' }}
                            </span>
                        </td>
                        <td>{{ $audit->conductedBy->name ?? 'Staff' }}</td>
                        <td style="text-align: right;">
                            <a href="{{ route('audits.show', $audit) }}" class="btn-primary" style="padding: 4px 10px; font-size: 0.75rem;">
                                View
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
