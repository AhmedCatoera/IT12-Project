@extends('layouts.app')

@section('title', 'Inventory & Stock Management')

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

    .nav-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--primary), var(--primary-hover));
        color: white;
        text-decoration: none;
        padding: 8px 16px;
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
        display: inline-flex;
        align-items: center;
        gap: 6px;
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

    .badge-stock {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.72rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        display: inline-block;
    }
    .badge-low-stock { background-color: #fee2e2; color: #b91c1c; animation: pulse 2s infinite; }
    .badge-ok { background-color: #d1fae5; color: #047857; }
    .badge-inactive { background-color: #f1f5f9; color: #64748b; }

    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.7; }
        100% { opacity: 1; }
    }

    .btn-action {
        font-size: 0.78rem;
        font-weight: 700;
        padding: 4px 8px;
        border-radius: 6px;
        text-decoration: none;
        display: inline-block;
        border: 1px solid var(--border);
        color: var(--text-main);
        background: white;
    }
    .btn-action:hover {
        background-color: #f8fafc;
    }
</style>
@endsection

@section('content')
<div class="page-header">
    <div class="page-title">
        <h2>Inventory Management</h2>
        <p>Monitor raw ingredient levels, track purchases, and manage physical counts</p>
    </div>
    <div class="nav-actions">
        <a href="{{ route('inventory.stockIn') }}" class="btn-primary">
            📥 + Record Stock-In
        </a>
        <a href="{{ route('audits.create') }}" class="btn-secondary" style="border-color: #f59e0b; color: #b45309; font-weight: 700;">
            ⚖️ Conduct Shift Audit
        </a>
        <a href="{{ route('audits.index') }}" class="btn-secondary">
            📋 Past Audits
        </a>
        <a href="{{ route('inventory.transactions') }}" class="btn-secondary">
            📜 Transaction Ledger
        </a>
        <a href="{{ route('inventory.create') }}" class="btn-secondary">
            ➕ New Ingredient
        </a>
    </div>
</div>

<!-- Key Performance Indicators -->
<div class="stats-row">
    <div class="stat-card">
        <div class="stat-label">Tracked Ingredients</div>
        <div class="stat-value">{{ $stats['total_ingredients'] }}</div>
    </div>

    <div class="stat-card" style="{{ $stats['low_stock_count'] > 0 ? 'background: #fff1f2; border-color: #fecdd3;' : '' }}">
        <div class="stat-label" style="{{ $stats['low_stock_count'] > 0 ? 'color: #be123c;' : '' }}">
            ⚠️ Low Stock / Reorder Needed
        </div>
        <div class="stat-value" style="color: {{ $stats['low_stock_count'] > 0 ? '#e11d48' : '#10b981' }};">
            {{ $stats['low_stock_count'] }}
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Purchases Received (30 Days)</div>
        <div class="stat-value" style="color: #2563eb;">{{ $stats['recent_stock_ins'] }}</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Twice-Daily Audits Conducted</div>
        <div class="stat-value" style="color: #059669;">{{ $stats['total_audits'] }}</div>
    </div>
</div>

<div class="card">
    <!-- Filter and Search Bar -->
    <form method="GET" action="{{ route('inventory.index') }}" class="filter-bar">
        <input 
            type="text" 
            name="search" 
            value="{{ $search }}" 
            placeholder="Search ingredient by name or unit..." 
            class="search-input"
        >
        <button type="submit" class="btn-primary" style="padding: 8px 16px;">Search</button>

        @if($filter === 'low_stock')
            <a href="{{ route('inventory.index') }}" class="btn-secondary" style="background-color: #fee2e2; color: #b91c1c; font-weight: 700;">
                Showing Low Stock Only (Clear Filter)
            </a>
        @else
            <a href="{{ route('inventory.index', ['filter' => 'low_stock']) }}" class="btn-secondary" style="color: #be123c; font-weight: 700;">
                ⚠️ Filter Low Stock Only
            </a>
        @endif

        @if($search || $filter)
            <a href="{{ route('inventory.index') }}" class="btn-secondary">Reset</a>
        @endif
    </form>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Ingredient Name</th>
                    <th>Unit</th>
                    <th style="text-align: right;">Current Stock</th>
                    <th style="text-align: right;">Reorder Level</th>
                    <th>Stock Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ingredients as $ingredient)
                <tr style="{{ $ingredient->isLowStock() ? 'background-color: #fffaf0;' : '' }}">
                    <td>
                        <strong style="font-size: 0.95rem; color: var(--text-main);">{{ $ingredient->name }}</strong>
                    </td>
                    <td>
                        <span style="font-weight: 600; color: var(--text-muted);">{{ $ingredient->unit }}</span>
                    </td>
                    <td style="text-align: right; font-weight: 800; font-size: 1.05rem; color: {{ $ingredient->isLowStock() ? '#e11d48' : '#059669' }};">
                        {{ number_format($ingredient->current_stock, 2) }} {{ $ingredient->unit }}
                    </td>
                    <td style="text-align: right; color: var(--text-muted); font-weight: 600;">
                        {{ number_format($ingredient->reorder_level, 2) }} {{ $ingredient->unit }}
                    </td>
                    <td>
                        @if(!$ingredient->is_active)
                            <span class="badge-stock badge-inactive">Inactive</span>
                        @elseif($ingredient->isLowStock())
                            <span class="badge-stock badge-low-stock">⚠️ Reorder Needed</span>
                        @else
                            <span class="badge-stock badge-ok">✓ In Stock</span>
                        @endif
                    </td>
                    <td style="text-align: right;">
                        <a href="{{ route('inventory.stockIn', ['ingredient_id' => $ingredient->id]) }}" class="btn-action" style="color: #2563eb; font-weight: 700;">
                            📥 Stock In
                        </a>
                        <a href="{{ route('inventory.adjust', ['ingredient_id' => $ingredient->id]) }}" class="btn-action">
                            ⚖️ Adjust
                        </a>
                        <a href="{{ route('inventory.edit', $ingredient) }}" class="btn-action">
                            ✏️ Edit
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2.5rem;">
                        No ingredients found matching the criteria.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1.5rem;">
        {{ $ingredients->links() }}
    </div>
</div>
@endsection
