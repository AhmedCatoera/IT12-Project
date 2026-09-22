@extends('layouts.app')

@section('title', 'Edit Ingredient - ' . $ingredient->name)

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

    .form-input, .form-select {
        width: 100%;
        padding: 10px 14px;
        border-radius: 8px;
        border: 1px solid var(--border);
        font-family: inherit;
        font-size: 0.9rem;
        color: var(--text-main);
        background-color: white;
    }

    .form-input:focus, .form-select:focus {
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
            <h2>Edit Ingredient: {{ $ingredient->name }}</h2>
            <p>Update measurement units, reorder threshold, or active status</p>
        </div>

        <form method="POST" action="{{ route('inventory.update', $ingredient) }}">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label class="form-label" for="name">Ingredient Name *</label>
                <input 
                    type="text" 
                    id="name" 
                    name="name" 
                    value="{{ old('name', $ingredient->name) }}" 
                    class="form-input @error('name') input-error @enderror" 
                    required
                >
                @error('name')
                    <p class="error-text">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label" for="unit">Unit of Measure *</label>
                    <select id="unit" name="unit" class="form-select @error('unit') input-error @enderror" required>
                        <option value="kg" {{ old('unit', $ingredient->unit) === 'kg' ? 'selected' : '' }}>kg (Kilograms)</option>
                        <option value="grams" {{ old('unit', $ingredient->unit) === 'grams' ? 'selected' : '' }}>grams</option>
                        <option value="pcs" {{ old('unit', $ingredient->unit) === 'pcs' ? 'selected' : '' }}>pcs (Pieces)</option>
                        <option value="liters" {{ old('unit', $ingredient->unit) === 'liters' ? 'selected' : '' }}>liters</option>
                        <option value="ml" {{ old('unit', $ingredient->unit) === 'ml' ? 'selected' : '' }}>ml (Milliliters)</option>
                        <option value="packs" {{ old('unit', $ingredient->unit) === 'packs' ? 'selected' : '' }}>packs</option>
                        <option value="tubs" {{ old('unit', $ingredient->unit) === 'tubs' ? 'selected' : '' }}>tubs</option>
                        <option value="cans" {{ old('unit', $ingredient->unit) === 'cans' ? 'selected' : '' }}>cans</option>
                    </select>
                    @error('unit')
                        <p class="error-text">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="reorder_level">Reorder Level Alert Threshold *</label>
                    <input 
                        type="number" 
                        step="0.01" 
                        min="0" 
                        id="reorder_level" 
                        name="reorder_level" 
                        value="{{ old('reorder_level', $ingredient->reorder_level) }}" 
                        class="form-input @error('reorder_level') input-error @enderror" 
                        required
                    >
                    @error('reorder_level')
                        <p class="error-text">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="is_active">Status *</label>
                <select id="is_active" name="is_active" class="form-select @error('is_active') input-error @enderror" required>
                    <option value="1" {{ old('is_active', $ingredient->is_active ? '1' : '0') === '1' ? 'selected' : '' }}>Active (Available for production)</option>
                    <option value="0" {{ old('is_active', $ingredient->is_active ? '1' : '0') === '0' ? 'selected' : '' }}>Inactive / Discontinued</option>
                </select>
                @error('is_active')
                    <p class="error-text">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-actions">
                <a href="{{ route('inventory.index') }}" class="btn-cancel">Cancel</a>
                <button type="submit" class="btn-submit">Update Ingredient</button>
            </div>
        </form>
    </div>
</div>
@endsection
