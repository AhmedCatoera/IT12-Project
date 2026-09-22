@extends('layouts.app')

@section('title', 'Order ' . $order->order_number)

@section('styles')
<style>
    .order-container {
        max-width: 1080px;
        margin: 0 auto;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .card {
        background-color: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.75rem;
        box-shadow: var(--shadow);
        margin-bottom: 1.5rem;
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--primary), var(--primary-hover));
        color: white;
        text-decoration: none;
        padding: 8px 16px;
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
        padding: 8px 14px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.85rem;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .btn-print {
        background-color: #f1f5f9;
        border: 1px solid var(--border);
        color: var(--text-main);
        padding: 8px 14px;
        border-radius: 8px;
        font-weight: 700;
        cursor: pointer;
    }

    .grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
    }

    .info-label {
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 2px;
    }

    .info-value {
        font-size: 0.95rem;
        font-weight: 600;
        color: var(--text-main);
    }

    .badge-status {
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 800;
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

    .item-type-tag {
        font-size: 0.7rem;
        font-weight: 800;
        text-transform: uppercase;
        padding: 2px 6px;
        border-radius: 4px;
        margin-right: 4px;
    }
    .tag-cake { background: #fee2e2; color: #b91c1c; }
    .tag-pastry { background: #fef3c7; color: #b45309; }
    .tag-souvenir { background: #e0e7ff; color: #4338ca; }

    .financial-panel {
        background: #f8fafc;
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 1.25rem;
    }

    .finance-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
    }

    .finance-grand {
        font-size: 1.25rem;
        font-weight: 800;
        border-top: 1px solid var(--border);
        padding-top: 0.75rem;
        margin-top: 0.75rem;
    }

    @media print {
        .navbar, .btn-print, .btn-primary, .btn-secondary, .footer, .action-bar {
            display: none !important;
        }
        body {
            background-color: white !important;
        }
        .card {
            border: 1px solid #ddd !important;
            box-shadow: none !important;
        }
    }
</style>
@endsection

@section('content')
<div class="order-container">
    <div class="page-header action-bar">
        <div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <h2 style="font-size: 1.6rem; font-weight: 800;">Order {{ $order->order_number }}</h2>
                <span class="badge-status status-{{ $order->status }}">
                    {{ str_replace('_', ' ', $order->status) }}
                </span>
            </div>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 4px;">
                Created on {{ $order->created_at->format('F d, Y \a\t h:i A') }}
                @if($order->creator)
                    by {{ $order->creator->name }}
                @endif
            </p>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('orders.index') }}" class="btn-secondary">← Back to Orders</a>
            <button onclick="window.print()" class="btn-print">🖨️ Print Order Sheet</button>
            <a href="{{ route('orders.edit', $order) }}" class="btn-secondary">Edit Order</a>
        </div>
    </div>

    <!-- Quick Workflow Status Update Bar -->
    <div class="card action-bar" style="padding: 1rem 1.5rem; background-color: #f8fafc;">
        <form method="POST" action="{{ route('orders.updateStatus', $order) }}" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
            @csrf
            @method('PATCH')
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 0.85rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">
                    Update Workflow Status:
                </span>
                <select name="status" class="form-input" style="padding: 6px 12px; font-size: 0.85rem; font-weight: 600;">
                    <option value="pending" {{ $order->status === 'pending' ? 'selected' : '' }}>Pending Confirmation</option>
                    <option value="confirmed" {{ $order->status === 'confirmed' ? 'selected' : '' }}>Confirmed (Down Payment Received)</option>
                    <option value="in_production" {{ $order->status === 'in_production' ? 'selected' : '' }}>In Production (Baking)</option>
                    <option value="ready_for_release" {{ $order->status === 'ready_for_release' ? 'selected' : '' }}>Ready for Release / Pickup</option>
                    <option value="out_for_delivery" {{ $order->status === 'out_for_delivery' ? 'selected' : '' }}>Out for Delivery</option>
                    <option value="completed" {{ $order->status === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <button type="submit" class="btn-primary" style="padding: 7px 16px;">
                Update Status
            </button>
        </form>
    </div>

    <!-- Order & Customer Overview -->
    <div class="card">
        <div class="grid-2">
            <div>
                <div style="font-size: 1.1rem; font-weight: 800; margin-bottom: 1rem; color: var(--primary);">
                    Customer Information
                </div>
                <div style="margin-bottom: 0.75rem;">
                    <div class="info-label">Customer Name</div>
                    <div class="info-value">
                        <a href="{{ route('customers.show', $order->customer) }}" style="color: var(--text-main); text-decoration: none;">
                            {{ $order->customer->name }}
                        </a>
                    </div>
                </div>
                <div style="margin-bottom: 0.75rem;">
                    <div class="info-label">Contact Number</div>
                    <div class="info-value">{{ $order->customer->contact_number }}</div>
                </div>
                <div style="margin-bottom: 0.75rem;">
                    <div class="info-label">Facebook / Messenger</div>
                    <div class="info-value">{{ $order->customer->facebook_name ?: 'Not provided' }}</div>
                </div>
            </div>

            <div>
                <div style="font-size: 1.1rem; font-weight: 800; margin-bottom: 1rem; color: var(--primary);">
                    Fulfillment Schedule
                </div>
                <div style="margin-bottom: 0.75rem;">
                    <div class="info-label">Scheduled Target Date & Time</div>
                    <div class="info-value" style="color: #be123c; font-size: 1.05rem;">
                        📅 {{ $order->scheduled_date->format('F d, Y') }} at <strong>{{ $order->scheduled_date->format('h:i A') }}</strong>
                    </div>
                </div>
                <div style="margin-bottom: 0.75rem;">
                    <div class="info-label">Fulfillment Mode</div>
                    <div class="info-value">
                        {{ $order->order_type === 'delivery' ? '🛵 Home Delivery' : '🏪 Shop Pickup' }}
                    </div>
                </div>
                @if($order->order_type === 'delivery')
                <div style="margin-bottom: 0.75rem;">
                    <div class="info-label">Delivery Address</div>
                    <div class="info-value">{{ $order->delivery_address ?: 'No address specified' }}</div>
                </div>
                @endif
            </div>
        </div>

        @if($order->notes)
        <div style="margin-top: 1rem; padding: 0.85rem; background-color: #fffbeb; border: 1px solid #fef3c7; border-radius: 8px;">
            <div class="info-label" style="color: #b45309;">Special Instructions / Notes</div>
            <div style="font-size: 0.9rem; color: #92400e; margin-top: 2px;">{{ $order->notes }}</div>
        </div>
        @endif
    </div>

    <!-- Itemized Products Specifications -->
    <div class="card">
        <div style="font-size: 1.15rem; font-weight: 800; margin-bottom: 1rem;">
            Itemized Order Specifications
        </div>

        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Item & Custom Specifications</th>
                        <th>Type</th>
                        <th style="text-align: center;">Qty</th>
                        <th style="text-align: right;">Unit Price</th>
                        <th style="text-align: right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                    <tr>
                        <td>
                            <div style="font-weight: 700; font-size: 0.95rem;">{{ $item->item_name }}</div>
                            @if($item->flavor)
                                <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 2px;">
                                    <strong>Flavor:</strong> {{ $item->flavor }}
                                    @if($item->size) • <strong>Size:</strong> {{ $item->size }} @endif
                                </div>
                            @endif
                            @if($item->design_theme)
                                <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 2px;">
                                    <strong>Theme / Design:</strong> {{ $item->design_theme }}
                                </div>
                            @endif
                            @if($item->custom_names)
                                <div style="font-size: 0.82rem; color: var(--primary); font-weight: 600; margin-top: 2px;">
                                    <strong>Inscription / Names:</strong> "{{ $item->custom_names }}"
                                </div>
                            @endif
                        </td>
                        <td>
                            <span class="item-type-tag tag-{{ $item->item_type }}">
                                {{ $item->item_type }}
                            </span>
                        </td>
                        <td style="text-align: center; font-weight: 700;">{{ $item->quantity }}</td>
                        <td style="text-align: right;">₱{{ number_format($item->unit_price, 2) }}</td>
                        <td style="text-align: right; font-weight: 700;">₱{{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Financial Breakdown & Payment History -->
    <div class="grid-2">
        <!-- Financial Summary -->
        <div class="card">
            <div style="font-size: 1.15rem; font-weight: 800; margin-bottom: 1rem;">
                Financial Summary
            </div>

            <div class="financial-panel">
                <div class="finance-row">
                    <span>Total Order Amount:</span>
                    <span style="font-weight: 700;">₱{{ number_format($order->total_amount, 2) }}</span>
                </div>
                <div class="finance-row" style="color: #be123c;">
                    <span>Required 50% Down Payment:</span>
                    <span style="font-weight: 700;">₱{{ number_format($order->down_payment_required, 2) }}</span>
                </div>
                <div class="finance-row" style="color: #10b981;">
                    <span>Total Amount Paid:</span>
                    <span style="font-weight: 700;">₱{{ number_format($order->total_paid, 2) }}</span>
                </div>
                <div class="finance-row finance-grand" style="color: {{ $order->balance_due > 0 ? '#e11d48' : '#10b981' }};">
                    <span>Remaining Balance:</span>
                    <span>₱{{ number_format($order->balance_due, 2) }}</span>
                </div>
            </div>

            <div style="margin-top: 1rem; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <span style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">Payment Status:</span>
                    <span style="font-weight: 800; text-transform: uppercase; margin-left: 6px; color: {{ $order->payment_status === 'fully_paid' ? '#10b981' : '#f59e0b' }};">
                        {{ str_replace('_', ' ', $order->payment_status) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Payments Logged -->
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <div style="font-size: 1.15rem; font-weight: 800;">
                    Payment Transactions
                </div>
                @if($order->balance_due > 0)
                <a href="{{ route('payments.create', $order) }}" class="btn-primary" style="font-size: 0.8rem; padding: 6px 12px;">
                    💳 + Record Payment
                </a>
                @endif
            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Method</th>
                        <th style="text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($order->payments as $payment)
                    <tr>
                        <td>{{ $payment->payment_date->format('M d, Y') }}</td>
                        <td><span style="text-transform: capitalize; font-size: 0.8rem;">{{ str_replace('_', ' ', $payment->payment_type) }}</span></td>
                        <td>
                            <strong style="text-transform: uppercase; font-size: 0.8rem;">{{ $payment->payment_method }}</strong>
                            @if($payment->reference_number)
                                <div style="font-size: 0.72rem; color: var(--text-muted);">Ref: {{ $payment->reference_number }}</div>
                            @endif
                        </td>
                        <td style="text-align: right; font-weight: 700; color: #10b981;">₱{{ number_format($payment->amount, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">
                            No payment transactions recorded yet.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Kitchen Production & Baking Status -->
    <div class="card" style="margin-top: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 10px;">
            <div style="font-size: 1.15rem; font-weight: 800;">
                👩‍🍳 Kitchen Production & Baking Status
            </div>
            @if(Auth::user()->isOwner() || Auth::user()->isBaker() || Auth::user()->isStaff())
                @if($order->productions->isEmpty() || !$order->productions->contains('status', 'in_progress'))
                <a href="{{ route('productions.create', ['order_id' => $order->id]) }}" class="btn-primary" style="font-size: 0.8rem; padding: 6px 12px;">
                    ➕ Schedule Baking Batch
                </a>
                @endif
            @endif
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Batch #</th>
                    <th>Assigned Baker</th>
                    <th>Production Date</th>
                    <th>Status</th>
                    <th>Started</th>
                    <th>Completed</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($order->productions as $prod)
                <tr>
                    <td><strong>#PROD-{{ str_pad($prod->id, 4, '0', STR_PAD_LEFT) }}</strong></td>
                    <td>{{ $prod->baker?->name ?: 'Unassigned' }}</td>
                    <td>{{ $prod->production_date->format('M d, Y') }}</td>
                    <td>
                        <span style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; padding: 3px 8px; border-radius: 4px; background: #e0f2fe; color: #0369a1;">
                            {{ str_replace('_', ' ', $prod->status) }}
                        </span>
                    </td>
                    <td>{{ $prod->started_at ? $prod->started_at->format('M d, h:i A') : '—' }}</td>
                    <td>{{ $prod->completed_at ? $prod->completed_at->format('M d, h:i A') : '—' }}</td>
                    <td style="text-align: right;">
                        <a href="{{ route('productions.show', $prod) }}" style="font-weight: 700; color: var(--primary); text-decoration: none; font-size: 0.82rem;">
                            View Work Order →
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">
                        No production batches scheduled yet for this order.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
