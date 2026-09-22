@extends('layouts.app')

@section('title', 'Edit Order ' . $order->order_number)

@section('styles')
<style>
    .form-card {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 2rem;
        max-width: 680px;
        margin: 0 auto;
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
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.9rem;
        cursor: pointer;
    }

    .btn-cancel {
        background: white;
        border: 1px solid var(--border);
        color: var(--text-muted);
        text-decoration: none;
        padding: 9px 18px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.9rem;
    }
</style>
@endsection

@section('content')
<div class="form-card">
    <div class="form-header">
        <h2>Edit Order {{ $order->order_number }}</h2>
        <p>Update fulfillment timeline, delivery address, notes, or workflow status</p>
    </div>

    <form method="POST" action="{{ route('orders.update', $order) }}">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label class="form-label">Customer Name</label>
            <input type="text" value="{{ $order->customer->name }} ({{ $order->customer->contact_number }})" class="form-input" disabled style="background-color: #f8fafc;">
        </div>

        <div class="form-group">
            <label class="form-label" for="scheduled_date">Target Scheduled Date & Time *</label>
            <input 
                type="datetime-local" 
                id="scheduled_date" 
                name="scheduled_date" 
                value="{{ old('scheduled_date', $order->scheduled_date->format('Y-m-d\TH:i')) }}" 
                class="form-input" 
                required
            >
        </div>

        <div class="form-group">
            <label class="form-label" for="order_type">Fulfillment Mode *</label>
            <select id="order_type" name="order_type" class="form-select" required onchange="toggleAddress(this.value === 'delivery')">
                <option value="pickup" {{ old('order_type', $order->order_type) === 'pickup' ? 'selected' : '' }}>🏪 Shop Pickup</option>
                <option value="delivery" {{ old('order_type', $order->order_type) === 'delivery' ? 'selected' : '' }}>🛵 Home Delivery</option>
            </select>
        </div>

        <div class="form-group" id="deliveryAddressGroup" style="{{ old('order_type', $order->order_type) === 'delivery' ? '' : 'display: none;' }}">
            <label class="form-label" for="delivery_address">Delivery Address</label>
            <textarea 
                id="delivery_address" 
                name="delivery_address" 
                rows="2" 
                class="form-textarea" 
            >{{ old('delivery_address', $order->delivery_address) }}</textarea>
        </div>

        <div class="form-group">
            <label class="form-label" for="status">Workflow Status *</label>
            <select id="status" name="status" class="form-select" required>
                <option value="pending" {{ old('status', $order->status) === 'pending' ? 'selected' : '' }}>Pending Confirmation</option>
                <option value="confirmed" {{ old('status', $order->status) === 'confirmed' ? 'selected' : '' }}>Confirmed (Down Payment Received)</option>
                <option value="in_production" {{ old('status', $order->status) === 'in_production' ? 'selected' : '' }}>In Production (Baking)</option>
                <option value="ready_for_release" {{ old('status', $order->status) === 'ready_for_release' ? 'selected' : '' }}>Ready for Release / Pickup</option>
                <option value="out_for_delivery" {{ old('status', $order->status) === 'out_for_delivery' ? 'selected' : '' }}>Out for Delivery</option>
                <option value="completed" {{ old('status', $order->status) === 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="cancelled" {{ old('status', $order->status) === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label" for="notes">Special Notes / Instructions</label>
            <textarea 
                id="notes" 
                name="notes" 
                rows="3" 
                class="form-textarea" 
            >{{ old('notes', $order->notes) }}</textarea>
        </div>

        <div class="form-actions">
            <a href="{{ route('orders.show', $order) }}" class="btn-cancel">Cancel</a>
            <button type="submit" class="btn-submit">Update Order</button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
    function toggleAddress(isDelivery) {
        document.getElementById('deliveryAddressGroup').style.display = isDelivery ? 'block' : 'none';
    }
</script>
@endsection
