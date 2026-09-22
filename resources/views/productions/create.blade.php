@extends('layouts.app')

@section('title', 'Schedule Production Batch')

@section('styles')
<style>
    .form-container {
        max-width: 680px;
        margin: 0 auto;
    }

    .form-card {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 2rem;
        box-shadow: var(--shadow);
    }

    .form-header {
        margin-bottom: 1.5rem;
        border-bottom: 1px solid var(--border);
        padding-bottom: 1rem;
    }

    .form-header h2 {
        font-size: 1.4rem;
        font-weight: 800;
        color: var(--text-main);
    }

    .order-specs-box {
        background-color: #f8fafc;
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 1.25rem;
        margin-bottom: 1.5rem;
    }

    .form-group {
        margin-bottom: 1.25rem;
    }

    .form-label {
        display: block;
        font-size: 0.82rem;
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    .form-input, .form-select, .form-textarea {
        width: 100%;
        padding: 10px 14px;
        border-radius: 8px;
        border: 1px solid var(--border);
        font-family: inherit;
        font-size: 0.9rem;
        color: var(--text-main);
        background-color: white;
    }

    .form-input:focus, .form-select:focus, .form-textarea:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(225, 29, 72, 0.15);
    }

    .input-error {
        border-color: var(--danger);
    }

    .error-text {
        font-size: 0.78rem;
        color: var(--danger);
        margin-top: 4px;
        font-weight: 600;
    }

    .checkbox-group {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-top: 0.5rem;
        background: #f1f5f9;
        padding: 10px 14px;
        border-radius: 8px;
    }

    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 1.75rem;
        border-top: 1px solid var(--border);
        padding-top: 1.25rem;
    }

    .btn-submit {
        background: linear-gradient(135deg, var(--primary), var(--primary-hover));
        color: white;
        border: none;
        padding: 11px 24px;
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.92rem;
        cursor: pointer;
    }

    .btn-cancel {
        background: white;
        border: 1px solid var(--border);
        color: var(--text-muted);
        text-decoration: none;
        padding: 10px 18px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.92rem;
    }
</style>
@endsection

@section('content')
<div class="form-container">
    <div class="form-card">
        <div class="form-header">
            <h2>Schedule Production Batch</h2>
            <p>Queue or immediately start a kitchen baking batch for an order</p>
        </div>

        <form method="POST" action="{{ route('productions.store') }}">
            @csrf

            <!-- Order Selection -->
            <div class="form-group">
                <label class="form-label" for="order_id">Target Customer Order *</label>
                @if($selectedOrder)
                    <input type="hidden" name="order_id" value="{{ $selectedOrder->id }}">
                    <div class="order-specs-box">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong style="font-size: 1.05rem; color: var(--primary);">{{ $selectedOrder->order_number }}</strong>
                                <span style="font-size: 0.85rem; color: var(--text-muted); margin-left: 8px;">
                                    Customer: <strong>{{ $selectedOrder->customer->name }}</strong>
                                </span>
                            </div>
                            <span style="font-size: 0.8rem; font-weight: 700; background: #fee2e2; color: #be123c; padding: 3px 8px; border-radius: 4px;">
                                Due: {{ $selectedOrder->scheduled_date->format('M d, h:i A') }}
                            </span>
                        </div>

                        <div style="margin-top: 10px; border-top: 1px dashed var(--border); padding-top: 8px;">
                            <span style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Items to produce:</span>
                            <ul style="margin: 4px 0 0 16px; font-size: 0.88rem;">
                                @foreach($selectedOrder->items as $item)
                                    <li>
                                        <strong>{{ $item->quantity }}x {{ $item->item_name }}</strong>
                                        @if($item->flavor) (Flavor: {{ $item->flavor }}) @endif
                                        @if($item->size) - Size: {{ $item->size }} @endif
                                        @if($item->custom_names) - Inscription: "{{ $item->custom_names }}" @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @else
                    <select id="order_id" name="order_id" class="form-select @error('order_id') input-error @enderror" required>
                        <option value="">-- Select Confirmed Order --</option>
                        @foreach($availableOrders as $order)
                            <option value="{{ $order->id }}" {{ old('order_id') == $order->id ? 'selected' : '' }}>
                                {{ $order->order_number }} - {{ $order->customer->name }} (Target: {{ $order->scheduled_date->format('M d, h:i A') }})
                            </option>
                        @endforeach
                    </select>
                    @error('order_id')
                        <p class="error-text">{{ $message }}</p>
                    @enderror
                @endif
            </div>

            <!-- Assigned Staff -->
            <div class="form-group">
                <label class="form-label" for="assigned_baker_id">Assigned Staff</label>
                <select id="assigned_baker_id" name="assigned_baker_id" class="form-select @error('assigned_baker_id') input-error @enderror">
                    <option value="">-- Select Staff (Optional) --</option>
                    @foreach($bakers as $baker)
                        <option value="{{ $baker->id }}" {{ old('assigned_baker_id', Auth::id()) == $baker->id ? 'selected' : '' }}>
                            👤 {{ $baker->name }} ({{ ucfirst($baker->role) }})
                        </option>
                    @endforeach
                </select>
                @error('assigned_baker_id')
                    <p class="error-text">{{ $message }}</p>
                @enderror
            </div>

            <!-- Production Date -->
            <div class="form-group">
                <label class="form-label" for="production_date">Production Date *</label>
                <input 
                    type="date" 
                    id="production_date" 
                    name="production_date" 
                    value="{{ old('production_date', now()->toDateString()) }}" 
                    class="form-input @error('production_date') input-error @enderror" 
                    required
                >
                @error('production_date')
                    <p class="error-text">{{ $message }}</p>
                @enderror
            </div>

            <!-- Auto-start Baking Immediately -->
            <div class="checkbox-group">
                <input type="checkbox" id="auto_start" name="auto_start" value="1" {{ old('auto_start', '1') == '1' ? 'checked' : '' }} style="width: 18px; height: 18px; cursor: pointer;">
                <label for="auto_start" style="font-size: 0.88rem; font-weight: 600; cursor: pointer; color: var(--text-main);">
                    🔥 Start baking immediately (set status to <em>In Progress</em> and update order to <em>In Production</em>)
                </label>
            </div>

            <!-- Notes / Kitchen Remarks -->
            <div class="form-group" style="margin-top: 1.25rem;">
                <label class="form-label" for="notes">Kitchen Notes / Special Instructions</label>
                <textarea 
                    id="notes" 
                    name="notes" 
                    rows="3" 
                    class="form-textarea @error('notes') input-error @enderror" 
                    placeholder="e.g. Ensure fondant flowers are prepped 1 hour before icing; keep strawberry filling chilled..."
                >{{ old('notes') }}</textarea>
                @error('notes')
                    <p class="error-text">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-actions">
                <a href="{{ route('productions.index') }}" class="btn-cancel">Cancel</a>
                <button type="submit" class="btn-submit">Confirm & Schedule Batch</button>
            </div>
        </form>
    </div>
</div>
@endsection
