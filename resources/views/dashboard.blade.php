@extends('layouts.app')

@section('title', 'Dashboard')

@section('styles')
<style>
    .welcome-banner {
        background: linear-gradient(135deg, #be123c 0%, #e11d48 40%, #fb7185 100%);
        border-radius: 20px;
        padding: 2rem 2.25rem;
        color: white;
        margin-bottom: 1.75rem;
        box-shadow: 0 12px 32px -6px rgba(225,29,72,0.35);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1.25rem;
        position: relative;
        overflow: hidden;
    }
    .welcome-banner::before {
        content: '🍰';
        position: absolute;
        right: 2rem;
        top: 50%;
        transform: translateY(-50%);
        font-size: 6rem;
        opacity: 0.12;
        pointer-events: none;
    }
    .welcome-banner h2 {
        font-size: 1.6rem;
        font-weight: 900;
        letter-spacing: -0.03em;
        margin-bottom: 4px;
    }
    .welcome-banner p { font-size: 0.9rem; opacity: 0.88; font-weight: 500; }
    .welcome-chips { display: flex; gap: 8px; flex-wrap: wrap; }
    .welcome-chip {
        background: rgba(255,255,255,0.2);
        border: 1px solid rgba(255,255,255,0.3);
        border-radius: 999px;
        padding: 5px 14px;
        font-size: 0.78rem;
        font-weight: 700;
        backdrop-filter: blur(10px);
    }

    /* Stats grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
        gap: 1.1rem;
        margin-bottom: 1.75rem;
    }

    /* Quick actions */
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 0.9rem;
        margin-bottom: 1.75rem;
    }
    .action-card {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.25rem 1rem;
        text-align: center;
        text-decoration: none;
        color: var(--text-main);
        transition: all 0.2s ease;
        box-shadow: var(--shadow-sm);
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.6rem;
    }
    .action-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-md);
        border-color: var(--primary-mid);
        color: var(--primary);
    }
    .action-icon {
        width: 48px; height: 48px;
        border-radius: 13px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem;
        background: var(--icon-bg, var(--primary-light));
        transition: transform 0.2s;
    }
    .action-card:hover .action-icon { transform: scale(1.1) rotate(-5deg); }
    .action-label { font-size: 0.82rem; font-weight: 700; }
    .action-sub { font-size: 0.7rem; color: var(--text-muted); }

    /* Two col layout */
    .dashboard-grid {
        display: grid;
        grid-template-columns: 1fr 340px;
        gap: 1.25rem;
        align-items: start;
    }
    @media (max-width: 1100px) { .dashboard-grid { grid-template-columns: 1fr; } }

    /* Recent orders badge */
    .order-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 0.68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        white-space: nowrap;
    }

    /* Status list */
    .status-list { display: flex; flex-direction: column; gap: 10px; }
    .status-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 12px;
        border-radius: 10px;
        background: #f9fafb;
        border: 1px solid var(--border);
        transition: all 0.15s;
    }
    .status-row:hover { background: var(--primary-light); border-color: var(--primary-mid); }
    .status-row-left { display: flex; align-items: center; gap: 10px; font-size: 0.875rem; font-weight: 600; }
    .status-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    .status-row-count { font-size: 1.3rem; font-weight: 900; color: var(--text-main); }

    /* Section header */
    .section-hdr {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }
    .section-hdr-title { font-size: 1rem; font-weight: 800; color: var(--text-main); display: flex; align-items: center; gap: 8px; }
    .see-all { font-size: 0.78rem; font-weight: 700; color: var(--primary); text-decoration: none; }
    .see-all:hover { text-decoration: underline; }
</style>
@endsection

@section('content')

{{-- Welcome Banner --}}
<div class="welcome-banner">
    <div>
        <h2>Good {{ now()->hour < 12 ? 'Morning' : (now()->hour < 18 ? 'Afternoon' : 'Evening') }}, {{ $user->first_name ?? $user->name }}! 👋</h2>
        <p>You're logged in as <strong>{{ ucfirst($user->role) }}</strong> &nbsp;•&nbsp; {{ now()->format('l, F j, Y') }}</p>
    </div>
    <div class="welcome-chips">
        <span class="welcome-chip">🕐 {{ now()->format('h:i A') }}</span>
        <span class="welcome-chip">{{ ucfirst($user->role) }} Access</span>
    </div>
</div>

{{-- KPI Stats --}}
<div class="stats-grid">
    <div class="stat-card" style="--stat-accent: linear-gradient(90deg, #e11d48, #fb7185);">
        <div class="stat-icon" style="background: #fff1f2;">🎂</div>
        <div class="stat-label">Total Orders</div>
        <div class="stat-value">{{ $stats['total_orders'] }}</div>
        <div class="stat-change">All time</div>
    </div>

    <div class="stat-card" style="--stat-accent: linear-gradient(90deg, #f59e0b, #fbbf24);">
        <div class="stat-icon" style="background: #fef3c7;">⏳</div>
        <div class="stat-label">Pending Confirmation</div>
        <div class="stat-value" style="color: #d97706;">{{ $stats['pending_orders'] }}</div>
        <div class="stat-change">Needs attention</div>
    </div>

    <div class="stat-card" style="--stat-accent: linear-gradient(90deg, #7c3aed, #a78bfa);">
        <div class="stat-icon" style="background: #ede9fe;">👩‍🍳</div>
        <div class="stat-label">In Production</div>
        <div class="stat-value" style="color: #7c3aed;">{{ $stats['in_production'] }}</div>
        <div class="stat-change">Being baked now</div>
    </div>

    <div class="stat-card" style="--stat-accent: linear-gradient(90deg, #10b981, #34d399);">
        <div class="stat-icon" style="background: #d1fae5;">✅</div>
        <div class="stat-label">Ready for Release</div>
        <div class="stat-value" style="color: #059669;">{{ $stats['ready_orders'] }}</div>
        <div class="stat-change">Awaiting pickup/delivery</div>
    </div>

    <div class="stat-card" style="--stat-accent: linear-gradient(90deg, {{ $stats['low_stock_count'] > 0 ? '#ef4444, #fca5a5' : '#10b981, #6ee7b7' }});">
        <div class="stat-icon" style="background: {{ $stats['low_stock_count'] > 0 ? '#fee2e2' : '#d1fae5' }};">
            {{ $stats['low_stock_count'] > 0 ? '⚠️' : '📦' }}
        </div>
        <div class="stat-label">Low Stock Alerts</div>
        <div class="stat-value" style="color: {{ $stats['low_stock_count'] > 0 ? '#dc2626' : '#059669' }};">
            {{ $stats['low_stock_count'] }}
        </div>
        <div class="stat-change">{{ $stats['low_stock_count'] > 0 ? 'Items at reorder level' : 'Stock levels healthy' }}</div>
    </div>
</div>

{{-- Quick Actions --}}
<div class="card" style="padding: 1.25rem; margin-bottom: 1.75rem;">
    <div class="section-hdr">
        <div class="section-hdr-title">⚡ Quick Actions</div>
    </div>
    <div class="quick-actions" style="margin-bottom: 0;">
        <a href="{{ route('orders.create') }}" class="action-card">
            <div class="action-icon" style="--icon-bg: #fff1f2;">🎂</div>
            <div class="action-label">New Order</div>
            <div class="action-sub">Create customer order</div>
        </a>
        <a href="{{ route('customers.create') }}" class="action-card">
            <div class="action-icon" style="--icon-bg: #e0f2fe;">👥</div>
            <div class="action-label">Add Customer</div>
            <div class="action-sub">Register new customer</div>
        </a>
        <a href="{{ route('inventory.stockIn') }}" class="action-card">
            <div class="action-icon" style="--icon-bg: #d1fae5;">📥</div>
            <div class="action-label">Stock In</div>
            <div class="action-sub">Receive ingredients</div>
        </a>
        <a href="{{ route('productions.create') }}" class="action-card">
            <div class="action-icon" style="--icon-bg: #ede9fe;">👩‍🍳</div>
            <div class="action-label">Schedule Baking</div>
            <div class="action-sub">Start a batch</div>
        </a>
        <a href="{{ route('audits.create') }}" class="action-card">
            <div class="action-icon" style="--icon-bg: #fef3c7;">📋</div>
            <div class="action-label">Shift Audit</div>
            <div class="action-sub">Physical inventory count</div>
        </a>
        @if(Auth::user()->isOwner())
        <a href="{{ route('reports.index') }}" class="action-card">
            <div class="action-icon" style="--icon-bg: #fce7f3;">📊</div>
            <div class="action-label">View Reports</div>
            <div class="action-sub">Sales &amp; analytics</div>
        </a>
        @endif
    </div>
</div>

{{-- Main dashboard grid --}}
<div class="dashboard-grid">
    {{-- Recent Orders --}}
    <div class="card" style="margin-bottom: 0;">
        <div class="section-hdr">
            <div class="section-hdr-title">🧾 Recent Orders</div>
            <a href="{{ route('orders.index') }}" class="see-all">View all →</a>
        </div>

        @if($recentOrders->isEmpty())
            <div class="empty-state" style="padding: 2.5rem 1rem;">
                <div class="empty-state-icon">🎂</div>
                <h3>No orders yet</h3>
                <p>Start by creating your first customer order.</p>
                <a href="{{ route('orders.create') }}" class="btn-primary btn-sm">Create Order</a>
            </div>
        @else
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Scheduled</th>
                        <th>Status</th>
                        <th style="text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentOrders as $order)
                    <tr style="cursor: pointer;" onclick="window.location='{{ route('orders.show', $order) }}'">
                        <td>
                            <span style="font-weight: 800; font-family: monospace; font-size: 0.82rem; color: var(--primary);">{{ $order->order_number }}</span>
                        </td>
                        <td>
                            <div style="font-weight: 600;">{{ $order->customer->name }}</div>
                            <div style="font-size: 0.72rem; color: var(--text-muted); text-transform: capitalize;">{{ $order->order_type }}</div>
                        </td>
                        <td style="font-size: 0.82rem; color: var(--text-muted);">
                            {{ $order->scheduled_date->format('M d, Y') }}<br>
                            <span style="font-size: 0.72rem;">{{ $order->scheduled_date->format('h:i A') }}</span>
                        </td>
                        <td>
                            <span class="badge order-status-pill status-{{ $order->status }}">
                                {{ str_replace('_', ' ', $order->status) }}
                            </span>
                        </td>
                        <td style="text-align: right; font-weight: 800;">₱{{ number_format($order->total_amount, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- Order Status Summary --}}
    <div class="card" style="margin-bottom: 0;">
        <div class="section-hdr">
            <div class="section-hdr-title">📈 Order Pipeline</div>
        </div>
        <div class="status-list">
            <div class="status-row">
                <div class="status-row-left">
                    <div class="status-dot" style="background: #f59e0b;"></div>
                    Pending
                </div>
                <div class="status-row-count" style="color: #d97706;">{{ $stats['pending_orders'] }}</div>
            </div>
            <div class="status-row">
                <div class="status-row-left">
                    <div class="status-dot" style="background: #3b82f6;"></div>
                    Confirmed
                </div>
                <div class="status-row-count" style="color: #2563eb;">{{ \App\Models\Order::where('status','confirmed')->count() }}</div>
            </div>
            <div class="status-row">
                <div class="status-row-left">
                    <div class="status-dot" style="background: #7c3aed;"></div>
                    In Production
                </div>
                <div class="status-row-count" style="color: #7c3aed;">{{ $stats['in_production'] }}</div>
            </div>
            <div class="status-row">
                <div class="status-row-left">
                    <div class="status-dot" style="background: #10b981;"></div>
                    Ready
                </div>
                <div class="status-row-count" style="color: #059669;">{{ $stats['ready_orders'] }}</div>
            </div>
            <div class="status-row">
                <div class="status-row-left">
                    <div class="status-dot" style="background: #6b7280;"></div>
                    Completed
                </div>
                <div class="status-row-count" style="color: #374151;">{{ \App\Models\Order::where('status','completed')->count() }}</div>
            </div>
        </div>

        @if($stats['low_stock_count'] > 0)
        <div style="margin-top: 1.25rem; padding: 12px 14px; background: #fff1f2; border: 1px solid #fecdd3; border-radius: 10px;">
            <div style="font-size: 0.82rem; font-weight: 700; color: #9f1239; display: flex; align-items: center; gap: 6px;">
                ⚠️ Low Stock Warning
            </div>
            <div style="font-size: 0.78rem; color: #be123c; margin-top: 4px;">
                {{ $stats['low_stock_count'] }} ingredient{{ $stats['low_stock_count'] > 1 ? 's' : '' }} at or below reorder level.
            </div>
            <a href="{{ route('inventory.index', ['filter' => 'low_stock']) }}" style="display: inline-block; margin-top: 8px; font-size: 0.75rem; font-weight: 700; color: var(--primary); text-decoration: none;">
                View Inventory →
            </a>
        </div>
        @endif
    </div>
</div>

@endsection
