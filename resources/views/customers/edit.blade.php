@extends('layouts.app')

@section('title', 'Edit Customer - ' . $customer->name)

@section('styles')
<style>
    .form-card {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 2rem;
        max-width: 640px;
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

    .form-header p {
        font-size: 0.85rem;
        color: var(--text-muted);
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

    .form-input, .form-textarea {
        width: 100%;
        padding: 10px 14px;
        border-radius: 8px;
        border: 1px solid var(--border);
        font-family: inherit;
        font-size: 0.9rem;
        color: var(--text-main);
        transition: all 0.2s;
    }

    .form-input:focus, .form-textarea:focus {
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
        <h2>Edit Customer Profile</h2>
        <p>Update contact information for <strong>{{ $customer->name }}</strong></p>
    </div>

    <form method="POST" action="{{ route('customers.update', $customer) }}">
        @csrf
        @method('PUT')

        <div class="form-grid-2">
            <div class="form-group">
                <label class="form-label" for="first_name">First Name *</label>
                <input 
                    type="text" 
                    id="first_name" 
                    name="first_name" 
                    value="{{ old('first_name', $customer->first_name) }}" 
                    class="form-input @error('first_name') input-error @enderror" 
                    required
                >
                @error('first_name')
                    <p class="error-text">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="middle_name">Middle Name (Optional)</label>
                <input 
                    type="text" 
                    id="middle_name" 
                    name="middle_name" 
                    value="{{ old('middle_name', $customer->middle_name) }}" 
                    class="form-input @error('middle_name') input-error @enderror" 
                >
                @error('middle_name')
                    <p class="error-text">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="last_name">Last Name *</label>
            <input 
                type="text" 
                id="last_name" 
                name="last_name" 
                value="{{ old('last_name', $customer->last_name) }}" 
                class="form-input @error('last_name') input-error @enderror" 
                required
            >
            @error('last_name')
                <p class="error-text">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="contact_number">Contact Number *</label>
            <input 
                type="text" 
                id="contact_number" 
                name="contact_number" 
                value="{{ old('contact_number', $customer->contact_number) }}" 
                class="form-input @error('contact_number') input-error @enderror" 
                required
            >
            @error('contact_number')
                <p class="error-text">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="facebook_name">Facebook Account / Messenger Name</label>
            <input 
                type="text" 
                id="facebook_name" 
                name="facebook_name" 
                value="{{ old('facebook_name', $customer->facebook_name) }}" 
                class="form-input @error('facebook_name') input-error @enderror" 
            >
            @error('facebook_name')
                <p class="error-text">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="address">Default Delivery Address</label>
            <textarea 
                id="address" 
                name="address" 
                rows="3" 
                class="form-textarea @error('address') input-error @enderror" 
            >{{ old('address', $customer->address) }}</textarea>
            @error('address')
                <p class="error-text">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-actions">
            <a href="{{ route('customers.show', $customer) }}" class="btn-cancel">Cancel</a>
            <button type="submit" class="btn-submit">Update Customer</button>
        </div>
    </form>
</div>
@endsection
