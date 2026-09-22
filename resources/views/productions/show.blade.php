@extends('layouts.app')

@section('title', 'Work Order #PROD-' . str_pad($production->id, 4, '0', STR_PAD_LEFT))

@section('styles')
<style>
    .prod-container {
        max-width: 1050px;
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

    .badge-prod-status {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        display: inline-block;
    }
    .status-pending { background-color: #fef3c7; color: #b45309; }
    .status-in_progress { background-color: #dbeafe; color: #1d4ed8; }
    .status-completed { background-color: #d1fae5; color: #047857; }
    .status-cancelled { background-color: #fee2e2; color: #b91c1c; }

    .card {
        background-color: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.5rem;
        box-shadow: var(--shadow);
        margin-bottom: 1.5rem;
    }

    .grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
    }

    @media (max-width: 768px) {
        .grid-2 {
            grid-template-columns: 1fr;
        }
    }

    .info-label {
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 2px;
    }

    .info-value {
        font-size: 0.95rem;
        font-weight: 600;
        color: var(--text-main);
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

    .btn-success {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        border: none;
        padding: 9px 18px;
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.88rem;
        cursor: pointer;
    }

    .btn-print {
        background-color: #f1f5f9;
        color: #334155;
        border: 1px solid var(--border);
        padding: 8px 14px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.82rem;
        cursor: pointer;
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

    .cake-spec-box {
        background-color: #fff1f2;
        border: 1px solid #fecdd3;
        border-radius: 8px;
        padding: 1.25rem;
        margin-bottom: 1rem;
    }

    .workflow-bar {
        background-color: #f8fafc;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.25rem 1.5rem;
        margin-bottom: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .form-input, .form-select {
        padding: 8px 12px;
        border-radius: 6px;
        border: 1px solid var(--border);
        font-family: inherit;
        font-size: 0.88rem;
    }

    @media print {
        .navbar, .action-bar, .workflow-bar, .footer, .ingredient-form {
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
<div class="prod-container">
    <div class="page-header action-bar">
        <div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <h2 style="font-size: 1.5rem; font-weight: 800;">
                    Work Order #PROD-{{ str_pad($production->id, 4, '0', STR_PAD_LEFT) }}
                </h2>
                <span class="badge-prod-status status-{{ $production->status }}">
                    {{ str_replace('_', ' ', $production->status) }}
                </span>
            </div>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 4px;">
                Target Order: <a href="{{ route('orders.show', $production->order) }}" style="font-weight: 700; color: var(--primary);">{{ $production->order->order_number }}</a> 
                • Scheduled Release: <strong>{{ $production->order->scheduled_date->format('F d, Y \a\t h:i A') }}</strong>
            </p>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('productions.index') }}" class="btn-secondary">← Back to Queue</a>
            <button onclick="window.print()" class="btn-print">🖨️ Print Recipe Sheet</button>
        </div>
    </div>

    <!-- Baking Workflow Controller Bar -->
    <div class="workflow-bar action-bar">
        <div>
            <span style="font-size: 0.78rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); display: block;">
                Current Baking Status
            </span>
            <span style="font-weight: 800; font-size: 1.1rem; color: var(--text-main); text-transform: capitalize;">
                {{ str_replace('_', ' ', $production->status) }}
            </span>
            @if($production->started_at)
                <span style="font-size: 0.78rem; color: var(--text-muted); margin-left: 8px;">
                    Started: {{ $production->started_at->format('M d, h:i A') }}
                </span>
            @endif
            @if($production->completed_at)
                <span style="font-size: 0.78rem; color: #047857; margin-left: 8px; font-weight: 700;">
                    ✓ Finished: {{ $production->completed_at->format('M d, h:i A') }}
                </span>
            @endif
        </div>

        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            @if($production->status === 'pending')
                <form method="POST" action="{{ route('productions.updateStatus', $production) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="in_progress">
                    <button type="submit" class="btn-primary">
                        🔥 Start Baking (In-Progress)
                    </button>
                </form>
            @elseif($production->status === 'in_progress')
                <form method="POST" action="{{ route('productions.updateStatus', $production) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="completed">
                    <button type="submit" class="btn-success">
                        ✅ Mark Baking Completed
                    </button>
                </form>
            @endif

            <form method="POST" action="{{ route('productions.updateStatus', $production) }}" style="display: inline;">
                @csrf
                @method('PATCH')
                <select name="status" class="form-select" onchange="this.form.submit()" style="font-size: 0.8rem;">
                    <option value="">Change Status...</option>
                    <option value="pending" {{ $production->status === 'pending' ? 'selected' : '' }}>Set to Pending</option>
                    <option value="in_progress" {{ $production->status === 'in_progress' ? 'selected' : '' }}>Set to In Progress</option>
                    <option value="completed" {{ $production->status === 'completed' ? 'selected' : '' }}>Set to Completed</option>
                    <option value="cancelled" {{ $production->status === 'cancelled' ? 'selected' : '' }}>Cancel Batch</option>
                </select>
            </form>
        </div>
    </div>

    <!-- Production & Order Information Overview -->
    <div class="card">
        <div class="grid-2">
            <div>
                <div style="font-size: 1.05rem; font-weight: 800; margin-bottom: 0.75rem; color: var(--primary);">
                    Batch & Kitchen Details
                </div>
                <div style="margin-bottom: 0.6rem;">
                    <div class="info-label">Assigned Baker</div>
                    <div class="info-value">
                        {{ $production->baker?->name ?: 'Unassigned' }}
                    </div>
                </div>
                <div style="margin-bottom: 0.6rem;">
                    <div class="info-label">Scheduled Production Date</div>
                    <div class="info-value">
                        {{ $production->production_date->format('F d, Y') }}
                    </div>
                </div>
                @if($production->notes)
                <div style="margin-top: 0.75rem; background: #fffbeb; border: 1px solid #fde68a; border-radius: 6px; padding: 10px;">
                    <div class="info-label" style="color: #b45309;">Kitchen Instructions</div>
                    <div style="font-size: 0.88rem; color: #78350f; margin-top: 2px;">{{ $production->notes }}</div>
                </div>
                @endif
            </div>

            <div>
                <div style="font-size: 1.05rem; font-weight: 800; margin-bottom: 0.75rem; color: var(--primary);">
                    Customer & Fulfillment Information
                </div>
                <div style="margin-bottom: 0.6rem;">
                    <div class="info-label">Customer Name</div>
                    <div class="info-value">{{ $production->order->customer->name }}</div>
                </div>
                <div style="margin-bottom: 0.6rem;">
                    <div class="info-label">Target Fulfillment Mode</div>
                    <div class="info-value">
                        {{ $production->order->order_type === 'delivery' ? '🛵 Delivery' : '🏪 Shop Pickup' }} 
                        at <strong>{{ $production->order->scheduled_date->format('h:i A, M d') }}</strong>
                    </div>
                </div>
                @if($production->order->notes)
                <div style="margin-top: 0.75rem; background: #f8fafc; border: 1px solid var(--border); border-radius: 6px; padding: 10px;">
                    <div class="info-label">Order Special Notes</div>
                    <div style="font-size: 0.88rem; color: var(--text-main); margin-top: 2px;">{{ $production->order->notes }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Baking Specifications Sheet (Custom Cake Details) -->
    <div class="card">
        <h3 style="font-size: 1.15rem; font-weight: 800; margin-bottom: 1rem;">
            🎂 Items to Bake & Custom Specifications
        </h3>

        @foreach($production->order->items as $item)
        <div class="cake-spec-box">
            <div style="display: flex; justify-content: space-between; align-items: baseline; flex-wrap: wrap;">
                <div>
                    <span style="font-size: 1.15rem; font-weight: 800; color: #9f1239;">
                        {{ $item->quantity }}x {{ $item->item_name }}
                    </span>
                    <span style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; background: white; padding: 2px 8px; border-radius: 4px; margin-left: 8px; border: 1px solid #fecdd3;">
                        {{ $item->item_type }}
                    </span>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin-top: 10px; font-size: 0.88rem;">
                @if($item->flavor)
                <div>
                    <span style="font-weight: 700; color: #881337;">Flavor:</span>
                    <span>{{ $item->flavor }}</span>
                </div>
                @endif

                @if($item->size)
                <div>
                    <span style="font-weight: 700; color: #881337;">Size / Tier:</span>
                    <span>{{ $item->size }}</span>
                </div>
                @endif

                @if($item->design_theme)
                <div>
                    <span style="font-weight: 700; color: #881337;">Theme / Decoration:</span>
                    <span>{{ $item->design_theme }}</span>
                </div>
                @endif
            </div>

            @if($item->custom_names)
            <div style="margin-top: 8px; background: white; border: 1px dashed #f43f5e; border-radius: 6px; padding: 8px 12px;">
                <span style="font-size: 0.75rem; font-weight: 800; color: #e11d48; text-transform: uppercase; display: block;">
                    Lettering / Inscription on Cake:
                </span>
                <span style="font-size: 1rem; font-weight: 700; color: #be123c;">
                    "{{ $item->custom_names }}"
                </span>
            </div>
            @endif
        </div>
        @endforeach
    </div>

    <!-- Ingredient Consumption Logging & Inventory Deduction -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 10px;">
            <div>
                <h3 style="font-size: 1.15rem; font-weight: 800;">
                    📦 Ingredient Usage & Stock Deduction Ledger
                </h3>
                <p style="font-size: 0.82rem; color: var(--text-muted);">
                    Logged ingredients are automatically deducted from the inventory stock ledger.
                </p>
            </div>
        </div>

        <!-- Log New Ingredient Form -->
        <form method="POST" action="{{ route('productions.logIngredient', $production) }}" class="ingredient-form" style="background: #f8fafc; border: 1px solid var(--border); border-radius: 8px; padding: 1rem; margin-bottom: 1.25rem;">
            @csrf
            <div style="display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap;">
                <div style="flex: 2; min-width: 220px;">
                    <label class="info-label" for="ingredient_id">Select Ingredient *</label>
                    <select id="ingredient_id" name="ingredient_id" class="form-select" style="width: 100%;" required>
                        <option value="">-- Choose Ingredient to Deduct --</option>
                        @foreach($availableIngredients as $ing)
                            <option value="{{ $ing->id }}">
                                {{ $ing->name }} (In Stock: {{ number_format($ing->current_stock, 2) }} {{ $ing->unit }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div style="flex: 1; min-width: 140px;">
                    <label class="info-label" for="quantity_used">Quantity Used *</label>
                    <input 
                        type="number" 
                        step="0.01" 
                        min="0.01" 
                        id="quantity_used" 
                        name="quantity_used" 
                        placeholder="e.g. 2.50" 
                        class="form-input" 
                        style="width: 100%;" 
                        required
                    >
                </div>

                <div>
                    <button type="submit" class="btn-primary" style="padding: 8px 16px;">
                        📉 Deduct from Inventory
                    </button>
                </div>
            </div>
        </form>

        <!-- Logged Ingredients Table -->
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Ingredient</th>
                        <th>Unit</th>
                        <th style="text-align: right;">Quantity Used</th>
                        <th>Logged At</th>
                        <th style="text-align: right;" class="action-bar">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($production->productionIngredients as $item)
                    <tr>
                        <td>
                            <strong>{{ $item->ingredient->name }}</strong>
                        </td>
                        <td>
                            {{ $item->ingredient->unit }}
                        </td>
                        <td style="text-align: right; font-weight: 800; color: #be123c;">
                            {{ number_format($item->quantity_used, 2) }} {{ $item->ingredient->unit }}
                        </td>
                        <td style="font-size: 0.8rem; color: var(--text-muted);">
                            {{ $item->updated_at->format('M d, Y h:i A') }}
                        </td>
                        <td style="text-align: right;" class="action-bar">
                            <form method="POST" action="{{ route('productions.removeIngredient', [$production, $item]) }}" onsubmit="return confirm('Restore stock and remove this entry?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="background: none; border: none; color: #e11d48; cursor: pointer; font-size: 0.78rem; font-weight: 700;">
                                    ✕ Reverse
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                            No ingredient consumption logged yet for this batch.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
