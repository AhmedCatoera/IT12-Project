@extends('layouts.app')

@section('title', 'Register New Staff Account - SweetNest OIMS')

@section('styles')
<style>
    .form-container {
        max-width: 680px;
        margin: 0 auto;
    }

    .form-card {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 2rem;
        box-shadow: 0 4px 16px rgba(0,0,0,0.04);
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.25rem;
    }

    @media (max-width: 640px) {
        .form-row {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection

@section('content')
<div class="form-container">
    <div style="margin-bottom: 1.5rem;">
        <a href="{{ route('users.index') }}" style="font-size: 0.85rem; color: var(--text-muted); text-decoration: none; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 6px;">
            &larr; Back to User Accounts
        </a>
        <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--text-main); margin: 0;">
            👤 Register Authorized Staff Account
        </h2>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin: 4px 0 0 0;">
            Creates new credentials for SweetNest bakeshop personnel (Document Chapter 3, Page 32)
        </p>
    </div>

    <div class="form-card">
        <form method="POST" action="{{ route('users.store') }}">
            @csrf

            <!-- Name Fields (Normalized) -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="first_name">First Name *</label>
                    <input 
                        type="text" 
                        id="first_name" 
                        name="first_name" 
                        class="form-input @error('first_name') input-error @enderror" 
                        value="{{ old('first_name') }}" 
                        required 
                        placeholder="e.g. Maria"
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
                        class="form-input @error('middle_name') input-error @enderror" 
                        value="{{ old('middle_name') }}" 
                        placeholder="e.g. Santos"
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
                    class="form-input @error('last_name') input-error @enderror" 
                    value="{{ old('last_name') }}" 
                    required 
                    placeholder="e.g. Dela Cruz"
                >
                @error('last_name')
                    <p class="error-text">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email Address -->
            <div class="form-group">
                <label class="form-label" for="email">Email Address (Login Username) *</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form-input @error('email') input-error @enderror" 
                    value="{{ old('email') }}" 
                    required 
                    placeholder="staff@sweetnest.com"
                >
                @error('email')
                    <p class="error-text">{{ $message }}</p>
                @enderror
            </div>

            <!-- System Role -->
            <div class="form-group">
                <label class="form-label" for="role">System Role *</label>
                <select id="role" name="role" class="form-select @error('role') input-error @enderror" required>
                    <option value="staff" {{ old('role') === 'staff' ? 'selected' : '' }}>📋 Staff (Encoding orders, payments, physical stock audits)</option>
                    <option value="owner" {{ old('role') === 'owner' ? 'selected' : '' }}>👑 Owner (Full administrative access, reports, user accounts)</option>
                </select>
                @error('role')
                    <p class="error-text">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password & Confirmation -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="password">Password *</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-input @error('password') input-error @enderror" 
                        required 
                        minlength="6"
                        placeholder="At least 6 characters"
                    >
                    @error('password')
                        <p class="error-text">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="password_confirmation">Confirm Password *</label>
                    <input 
                        type="password" 
                        id="password_confirmation" 
                        name="password_confirmation" 
                        class="form-input" 
                        required 
                        minlength="6"
                        placeholder="Repeat password"
                    >
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 2rem;">
                <a href="{{ route('users.index') }}" class="btn-secondary" style="padding: 10px 18px; text-decoration: none;">
                    Cancel
                </a>
                <button type="submit" class="btn-primary" style="padding: 10px 24px;">
                    Create Account
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
