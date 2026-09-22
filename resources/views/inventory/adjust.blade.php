@extends('layouts.app')

@section('title', 'Stock Adjustment & Spoilage')

@section('styles')
<style>
    .form-container {
        max-width: 650px;
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

    .grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
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
            <h2>Inventory Adjustment & Spoilage</h2>
            <p>Log spoiled ingredients, spills, damage, or manual corrections</p>
        </div>

        <form method="POST" action="{{ route('inventory.storeAdjust') }}">
            @csrf

            <!-- Ingredient Selection -->
            <div class="form-group">
                <label class="form-label" for="ingredient_id">Ingredient *</label>
                <select id="ingredient_id" name="ingredient_id" class="form-select @error('ingredient_id') input-error @enderror" required>
                    <option value="">-- Select Ingredient --</option>
                    @foreach($ingredients as $ing)
                        <option value="{{ $ing->id }}" {{ old('ingredient_id', $selectedIngredient?->id) == $ing->id ? 'selected' : '' }}>
                            {{ $ing->name }} (Current Stock: {{ number_format($ing->current_stock, 2) }} {{ $ing->unit }})
                        </option>
                    @endforeach
                </select>
                @error('ingredient_id')
                    <p class="error-text">{{ $message }}</p>
                @enderror
            </div>

            <!-- Reason & Action -->
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label" for="transaction_type">Adjustment Reason *</label>
                    <select id="transaction_type" name="transaction_type" class="form-select @error('transaction_type') input-error @enderror" required>
                        <option value="spoilage" {{ old('transaction_type') === 'spoilage' ? 'selected' : '' }}>🥀 Spoilage / Expired / Damaged</option>
                        <option value="audit_adjustment" {{ old('transaction_type') === 'audit_adjustment' ? 'selected' : '' }}>⚖️ Manual Audit Adjustment</option>
                    </select>
                    @error('transaction_type')
                        <p class="error-text">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="adjustment_type">Action on Stock *</label>
                    <select id="adjustment_type" name="adjustment_type" class="form-select @error('adjustment_type') input-error @enderror" required>
                        <option value="deduct" {{ old('adjustment_type', 'deduct') === 'deduct' ? 'selected' : '' }}>📉 Deduct from Stock (Loss)</option>
                        <option value="add" {{ old('adjustment_type') === 'add' ? 'selected' : '' }}>📈 Add to Stock (Found / Correction)</option>
                    </select>
                    @error('adjustment_type')
                        <p class="error-text">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Quantity -->
            <div class="form-group">
                <label class="form-label" for="quantity">Quantity *</label>
                <input 
                    type="number" 
                    step="0.01" 
                    min="0.01" 
                    id="quantity" 
                    name="quantity" 
                    value="{{ old('quantity') }}" 
                    placeholder="e.g. 1.50"
                    class="form-input @error('quantity') input-error @enderror" 
                    required
                >
                @error('quantity')
                    <p class="error-text">{{ $message }}</p>
                @enderror
            </div>

            <!-- Remarks / Reason explanation -->
            <div class="form-group">
                <label class="form-label" for="remarks">Explanation / Remarks *</label>
                <textarea 
                    id="remarks" 
                    name="remarks" 
                    rows="3" 
                    class="form-textarea @error('remarks') input-error @enderror" 
                    placeholder="e.g. 2 packs of cream cheese expired on Sep 20; discarded per sanitary standards..."
                    required
                >{{ old('remarks') }}</textarea>
                @error('remarks')
                    <p class="error-text">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-actions">
                <a href="{{ route('inventory.index') }}" class="btn-cancel">Cancel</a>
                <button type="submit" class="btn-submit">Record Adjustment</button>
            </div>
        </form>
    </div>
</div>
@endsection
