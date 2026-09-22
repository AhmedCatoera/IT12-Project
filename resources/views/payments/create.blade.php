@extends('layouts.app')

@section('title', 'Record Payment - ' . $order->order_number)

@section('styles')
<style>
    .payment-form-container {
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

    .balance-summary {
        background: linear-gradient(135deg, #fff1f2, #ffe4e6);
        border: 1px solid #fecdd3;
        border-radius: 10px;
        padding: 1.25rem 1.5rem;
        margin-bottom: 1.75rem;
    }

    .summary-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }

    .summary-item {
        font-size: 0.85rem;
    }

    .summary-label {
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .summary-value {
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--text-main);
        margin-top: 2px;
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
<div class="payment-form-container">
    <div class="form-card">
        <div class="form-header">
            <h2>Record Payment</h2>
            <p>Order: <strong>{{ $order->order_number }}</strong> • Customer: <strong>{{ $order->customer->name }}</strong></p>
        </div>

        <!-- Balance Information Summary -->
        <div class="balance-summary">
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-label">Total Order Amount</div>
                    <div class="summary-value">₱{{ number_format($order->total_amount, 2) }}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">50% Down Payment Req.</div>
                    <div class="summary-value" style="color: #be123c;">₱{{ number_format($order->down_payment_required, 2) }}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Total Amount Paid</div>
                    <div class="summary-value" style="color: #10b981;">₱{{ number_format($order->total_paid, 2) }}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Remaining Balance Due</div>
                    <div class="summary-value" style="color: #e11d48; font-size: 1.35rem;">
                        ₱{{ number_format($order->balance_due, 2) }}
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('payments.store') }}">
            @csrf
            <input type="hidden" name="order_id" value="{{ $order->id }}">

            <div class="form-group">
                <label class="form-label" for="payment_type">Payment Type *</label>
                <select id="payment_type" name="payment_type" class="form-select @error('payment_type') input-error @enderror" required onchange="adjustAmount(this.value)">
                    <option value="down_payment" {{ old('payment_type', $defaultType) === 'down_payment' ? 'selected' : '' }}>
                        50% Down Payment (₱{{ number_format($order->down_payment_required, 2) }})
                    </option>
                    <option value="balance_payment" {{ old('payment_type', $defaultType) === 'balance_payment' ? 'selected' : '' }}>
                        Remaining Balance Settlement (₱{{ number_format($order->balance_due, 2) }})
                    </option>
                    <option value="full_payment" {{ old('payment_type', $defaultType) === 'full_payment' ? 'selected' : '' }}>
                        Full Order Payment (₱{{ number_format($order->total_amount, 2) }})
                    </option>
                </select>
                @error('payment_type')
                    <p class="error-text">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="amount">Payment Amount (₱) *</label>
                <input 
                    type="number" 
                    step="0.01" 
                    id="amount" 
                    name="amount" 
                    value="{{ old('amount', $suggestedAmount) }}" 
                    max="{{ $order->balance_due }}"
                    min="1"
                    class="form-input @error('amount') input-error @enderror" 
                    required
                >
                <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 4px;">
                    Maximum allowable payment: ₱{{ number_format($order->balance_due, 2) }}
                </div>
                @error('amount')
                    <p class="error-text">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="payment_method">Payment Method *</label>
                <select id="payment_method" name="payment_method" class="form-select @error('payment_method') input-error @enderror" required>
                    <option value="cash" {{ old('payment_method') === 'cash' ? 'selected' : '' }}>💵 Cash</option>
                    <option value="gcash" {{ old('payment_method', 'gcash') === 'gcash' ? 'selected' : '' }}>📱 GCash</option>
                    <option value="bank_transfer" {{ old('payment_method') === 'bank_transfer' ? 'selected' : '' }}>🏦 Bank Transfer</option>
                    <option value="other" {{ old('payment_method') === 'other' ? 'selected' : '' }}>Other</option>
                </select>
                @error('payment_method')
                    <p class="error-text">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="reference_number">Reference / Receipt Number</label>
                <input 
                    type="text" 
                    id="reference_number" 
                    name="reference_number" 
                    value="{{ old('reference_number') }}" 
                    placeholder="e.g. GCash Ref # 100293847291 or Cash Receipt # 0012"
                    class="form-input @error('reference_number') input-error @enderror" 
                >
                @error('reference_number')
                    <p class="error-text">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="payment_date">Payment Date & Time *</label>
                <input 
                    type="datetime-local" 
                    id="payment_date" 
                    name="payment_date" 
                    value="{{ old('payment_date', now()->format('Y-m-d\TH:i')) }}" 
                    class="form-input @error('payment_date') input-error @enderror" 
                    required
                >
                @error('payment_date')
                    <p class="error-text">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="notes">Notes / Remarks</label>
                <textarea 
                    id="notes" 
                    name="notes" 
                    rows="2" 
                    class="form-textarea" 
                    placeholder="Optional notes regarding this payment..."
                >{{ old('notes') }}</textarea>
            </div>

            <div class="form-actions">
                <a href="{{ route('orders.show', $order) }}" class="btn-cancel">Cancel</a>
                <button type="submit" class="btn-submit">Record Payment</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const downPaymentRequired = {{ (float)$order->down_payment_required }};
    const balanceDue = {{ (float)$order->balance_due }};
    const totalAmount = {{ (float)$order->total_amount }};

    function adjustAmount(type) {
        const amountInput = document.getElementById('amount');
        if (type === 'down_payment') {
            amountInput.value = Math.min(downPaymentRequired, balanceDue).toFixed(2);
        } else if (type === 'balance_payment') {
            amountInput.value = balanceDue.toFixed(2);
        } else if (type === 'full_payment') {
            amountInput.value = balanceDue.toFixed(2);
        }
    }
</script>
@endsection
