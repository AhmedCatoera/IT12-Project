@extends('layouts.app')

@section('title', 'Create New Order')

@section('styles')
<style>
    .order-form-container {
        max-width: 960px;
        margin: 0 auto;
    }

    .form-card {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 2rem;
        box-shadow: var(--shadow);
        margin-bottom: 1.5rem;
    }

    .card-title {
        font-size: 1.25rem;
        font-weight: 800;
        margin-bottom: 0.25rem;
        color: var(--text-main);
    }

    .card-subtitle {
        font-size: 0.85rem;
        color: var(--text-muted);
        margin-bottom: 1.5rem;
        border-bottom: 1px solid var(--border);
        padding-bottom: 0.75rem;
    }

    .form-grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.25rem;
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
        transition: all 0.2s;
        background-color: white;
    }

    .form-input:focus, .form-select:focus, .form-textarea:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(225, 29, 72, 0.15);
    }

    .items-toolbar {
        display: flex;
        gap: 8px;
        margin-bottom: 1.25rem;
        flex-wrap: wrap;
    }

    .btn-add-item {
        background-color: white;
        border: 1px dashed var(--primary);
        color: var(--primary);
        padding: 8px 14px;
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.82rem;
        cursor: pointer;
        transition: all 0.15s;
    }

    .btn-add-item:hover {
        background-color: var(--primary-light);
    }

    .item-block {
        background-color: #f8fafc;
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 1.25rem;
        margin-bottom: 1rem;
        position: relative;
    }

    .item-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }

    .item-badge {
        font-size: 0.75rem;
        font-weight: 800;
        padding: 3px 8px;
        border-radius: 6px;
        text-transform: uppercase;
    }
    .badge-cake { background: #fee2e2; color: #b91c1c; }
    .badge-pastry { background: #fef3c7; color: #b45309; }
    .badge-souvenir { background: #e0e7ff; color: #4338ca; }

    .btn-remove-item {
        background: none;
        border: none;
        color: var(--danger);
        font-size: 0.8rem;
        font-weight: 700;
        cursor: pointer;
    }

    .totals-box {
        background: linear-gradient(135deg, #fff1f2, #ffe4e6);
        border: 1px solid #fecdd3;
        border-radius: var(--radius);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .total-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
        font-size: 0.95rem;
        color: var(--text-main);
    }

    .grand-total {
        font-size: 1.4rem;
        font-weight: 800;
        color: var(--primary);
        border-top: 1px solid #fecdd3;
        padding-top: 0.75rem;
        margin-top: 0.75rem;
    }

    .downpayment-alert {
        font-size: 0.85rem;
        color: #9f1239;
        font-weight: 600;
        margin-top: 0.5rem;
    }

    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
    }

    .btn-submit {
        background: linear-gradient(135deg, var(--primary), var(--primary-hover));
        color: white;
        border: none;
        padding: 12px 28px;
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.95rem;
        cursor: pointer;
    }

    .btn-cancel {
        background: white;
        border: 1px solid var(--border);
        color: var(--text-muted);
        text-decoration: none;
        padding: 12px 20px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.95rem;
    }

    @media (max-width: 768px) {
        .form-grid-2 {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection

@section('content')
<div class="order-form-container">
    <div style="margin-bottom: 1.5rem;">
        <h2 style="font-size: 1.5rem; font-weight: 800;">Create New Customer Order</h2>
        <p style="font-size: 0.85rem; color: var(--text-muted);">Record customer custom specifications, delivery schedule, and calculate 50% deposit</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-error">
            <div>
                <strong>Please correct the following errors:</strong>
                <ul style="margin-top: 6px; padding-left: 20px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('orders.store') }}" enctype="multipart/form-data" id="orderForm">
        @csrf

        <!-- 1. Customer & Schedule Information -->
        <div class="form-card">
            <div class="card-title">1. Customer & Scheduling Details</div>
            <div class="card-subtitle">Select customer profile and target delivery/pickup timeline</div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="customer_id">Select Customer *</label>
                    <select id="customer_id" name="customer_id" class="form-select" required onchange="fillDefaultAddress(this)">
                        <option value="">-- Choose Customer --</option>
                        @foreach($customers as $c)
                            <option 
                                value="{{ $c->id }}" 
                                data-address="{{ $c->address }}"
                                {{ (old('customer_id', $selectedCustomerId) == $c->id) ? 'selected' : '' }}
                            >
                                {{ $c->name }} ({{ $c->contact_number }})
                            </option>
                        @endforeach
                    </select>
                    <div style="margin-top: 4px; font-size: 0.78rem;">
                        Customer not listed? <a href="{{ route('customers.create') }}" target="_blank" style="color: var(--primary); font-weight: 700;">+ Add New Customer</a>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="scheduled_date">Target Completion Date & Time *</label>
                    <input 
                        type="datetime-local" 
                        id="scheduled_date" 
                        name="scheduled_date" 
                        value="{{ old('scheduled_date', now()->addDays(2)->format('Y-m-d\T14:00')) }}" 
                        class="form-input" 
                        required
                    >
                </div>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">Fulfillment Type *</label>
                    <div style="display: flex; gap: 20px; margin-top: 8px;">
                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 0.9rem;">
                            <input type="radio" name="order_type" value="pickup" {{ old('order_type', 'pickup') === 'pickup' ? 'checked' : '' }} onchange="toggleAddress(false)">
                            🏪 Shop Pickup
                        </label>
                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 0.9rem;">
                            <input type="radio" name="order_type" value="delivery" {{ old('order_type') === 'delivery' ? 'checked' : '' }} onchange="toggleAddress(true)">
                            🛵 Home Delivery
                        </label>
                    </div>
                </div>

                <div class="form-group" id="deliveryAddressGroup" style="{{ old('order_type') === 'delivery' ? '' : 'display: none;' }}">
                    <label class="form-label" for="delivery_address">Delivery Address</label>
                    <textarea 
                        id="delivery_address" 
                        name="delivery_address" 
                        rows="2" 
                        class="form-textarea" 
                        placeholder="Complete street, barangay, and landmark..."
                    >{{ old('delivery_address') }}</textarea>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="notes">General Order Notes / Special Instructions</label>
                <textarea 
                    id="notes" 
                    name="notes" 
                    rows="2" 
                    class="form-textarea" 
                    placeholder="e.g. Include 12 birthday candles, cake knife, call 30 mins before arrival..."
                >{{ old('notes') }}</textarea>
            </div>
        </div>

        <!-- 2. Itemized Products Section -->
        <div class="form-card">
            <div class="card-title">2. Order Items & Custom Specifications</div>
            <div class="card-subtitle">Add customized cakes, pastries, or personalized souvenir giveaways</div>

            <div class="items-toolbar">
                <button type="button" class="btn-add-item" onclick="addItem('cake')">
                    🎂 + Add Custom Cake
                </button>
                <button type="button" class="btn-add-item" onclick="addItem('pastry')">
                    🧁 + Add Pastry Box
                </button>
                <button type="button" class="btn-add-item" onclick="addItem('souvenir')">
                    🎁 + Add Personalized Souvenir
                </button>
            </div>

            <div id="itemsContainer">
                <!-- Items dynamically injected here -->
            </div>
        </div>

        <!-- 3. Financial Summary -->
        <div class="totals-box">
            <div class="total-row">
                <span>Total Items Ordered:</span>
                <span id="summaryTotalQty" style="font-weight: 700;">0</span>
            </div>
            <div class="total-row grand-total">
                <span>Grand Total:</span>
                <span>₱<span id="summaryGrandTotal">0.00</span></span>
            </div>
            <div class="downpayment-alert">
                💳 Required 50% Down Payment to Confirm Booking: <strong>₱<span id="summaryDownpayment">0.00</span></strong>
                <div style="font-size: 0.78rem; opacity: 0.85; margin-top: 2px;">
                    (Remaining 50% balance will be due before release/delivery).
                </div>
            </div>
        </div>

        <div class="form-actions">
            <a href="{{ route('orders.index') }}" class="btn-cancel">Cancel</a>
            <button type="submit" class="btn-submit">Confirm & Book Order</button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
    let itemIndex = 0;

    function toggleAddress(isDelivery) {
        document.getElementById('deliveryAddressGroup').style.display = isDelivery ? 'block' : 'none';
    }

    function fillDefaultAddress(select) {
        const selectedOption = select.options[select.selectedIndex];
        const address = selectedOption.getAttribute('data-address');
        const deliveryAddressInput = document.getElementById('delivery_address');
        if (address && deliveryAddressInput && !deliveryAddressInput.value) {
            deliveryAddressInput.value = address;
        }
    }

    function addItem(type) {
        const container = document.getElementById('itemsContainer');
        const idx = itemIndex++;

        let itemHTML = '';

        if (type === 'cake') {
            itemHTML = `
                <div class="item-block" id="itemBlock_${idx}">
                    <div class="item-header">
                        <span class="item-badge badge-cake">Customized Cake</span>
                        <button type="button" class="btn-remove-item" onclick="removeItem(${idx})">✕ Remove Item</button>
                    </div>
                    <input type="hidden" name="items[${idx}][item_type]" value="cake">
                    
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label">Cake Description / Name *</label>
                            <input type="text" name="items[${idx}][item_name]" value="Custom Celebration Cake" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Flavor *</label>
                            <input type="text" name="items[${idx}][flavor]" placeholder="e.g. Chocolate Moist, Red Velvet, Vanilla" class="form-input" required>
                        </div>
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label">Size / Tiers</label>
                            <input type="text" name="items[${idx}][size]" placeholder="e.g. 8x4 inch, 2-Tier 8x6" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Design / Theme</label>
                            <input type="text" name="items[${idx}][design_theme]" placeholder="e.g. Pastel Floral, Spider-Man, Minimalist" class="form-input">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Dedication / Inscription Text</label>
                        <input type="text" name="items[${idx}][custom_names]" placeholder="e.g. Happy 18th Birthday Chloe!" class="form-input">
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label">Quantity</label>
                            <input type="number" name="items[${idx}][quantity]" value="1" min="1" class="form-input item-qty" oninput="calculateTotals()" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Price per Cake (₱) *</label>
                            <input type="number" step="0.01" name="items[${idx}][unit_price]" value="1200.00" min="0" class="form-input item-price" oninput="calculateTotals()" required>
                        </div>
                    </div>
                </div>
            `;
        } else if (type === 'pastry') {
            itemHTML = `
                <div class="item-block" id="itemBlock_${idx}">
                    <div class="item-header">
                        <span class="item-badge badge-pastry">Pastries</span>
                        <button type="button" class="btn-remove-item" onclick="removeItem(${idx})">✕ Remove Item</button>
                    </div>
                    <input type="hidden" name="items[${idx}][item_type]" value="pastry">
                    
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label">Pastry Item Name *</label>
                            <input type="text" name="items[${idx}][item_name]" placeholder="e.g. Box of 12 Cupcakes, Brownies" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Flavor / Variant</label>
                            <input type="text" name="items[${idx}][flavor]" placeholder="e.g. Red Velvet Cream Cheese, Fudge" class="form-input">
                        </div>
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label">Quantity</label>
                            <input type="number" name="items[${idx}][quantity]" value="1" min="1" class="form-input item-qty" oninput="calculateTotals()" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Price per Box / Item (₱) *</label>
                            <input type="number" step="0.01" name="items[${idx}][unit_price]" value="450.00" min="0" class="form-input item-price" oninput="calculateTotals()" required>
                        </div>
                    </div>
                </div>
            `;
        } else if (type === 'souvenir') {
            itemHTML = `
                <div class="item-block" id="itemBlock_${idx}">
                    <div class="item-header">
                        <span class="item-badge badge-souvenir">Personalized Souvenir</span>
                        <button type="button" class="btn-remove-item" onclick="removeItem(${idx})">✕ Remove Item</button>
                    </div>
                    <input type="hidden" name="items[${idx}][item_type]" value="souvenir">
                    
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label">Souvenir Item Name *</label>
                            <input type="text" name="items[${idx}][item_name]" placeholder="e.g. Sintra Board Standee, Souvenir Mug, Keychain" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Theme / Event Names</label>
                            <input type="text" name="items[${idx}][custom_names]" placeholder="e.g. Sophia's 7th Birthday, Gabriel Christening" class="form-input">
                        </div>
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label">Pieces / Quantity *</label>
                            <input type="number" name="items[${idx}][quantity]" value="20" min="1" class="form-input item-qty" oninput="calculateTotals()" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Unit Price (₱) *</label>
                            <input type="number" step="0.01" name="items[${idx}][unit_price]" value="50.00" min="0" class="form-input item-price" oninput="calculateTotals()" required>
                        </div>
                    </div>
                </div>
            `;
        }

        container.insertAdjacentHTML('beforeend', itemHTML);
        calculateTotals();
    }

    function removeItem(idx) {
        const el = document.getElementById(`itemBlock_${idx}`);
        if (el) {
            el.remove();
            calculateTotals();
        }
    }

    function calculateTotals() {
        let totalQty = 0;
        let grandTotal = 0;

        const qtyInputs = document.querySelectorAll('.item-qty');
        const priceInputs = document.querySelectorAll('.item-price');

        for (let i = 0; i < qtyInputs.length; i++) {
            const qty = parseFloat(qtyInputs[i].value) || 0;
            const price = parseFloat(priceInputs[i].value) || 0;
            totalQty += qty;
            grandTotal += (qty * price);
        }

        document.getElementById('summaryTotalQty').innerText = totalQty;
        document.getElementById('summaryGrandTotal').innerText = grandTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('summaryDownpayment').innerText = (grandTotal * 0.50).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // Initialize with a cake item on page load if no items exist
    document.addEventListener('DOMContentLoaded', function () {
        addItem('cake');
    });
</script>
@endsection
