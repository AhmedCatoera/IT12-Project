@extends('layouts.app')

@section('title', 'Login')

@section('styles')
<style>
    .login-wrapper {
        min-height: calc(100vh - 180px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }

    .login-card {
        background-color: var(--card-bg);
        border-radius: 16px;
        box-shadow: var(--shadow-md);
        border: 1px solid var(--border);
        width: 100%;
        max-width: 440px;
        overflow: hidden;
    }

    .login-header {
        background: linear-gradient(135deg, #fff1f2, #ffe4e6);
        padding: 2.25rem 2rem 1.75rem;
        text-align: center;
        border-bottom: 1px solid #fecdd3;
    }

    .login-icon {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, var(--primary), #fb7185);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 30px;
        margin: 0 auto 12px;
        box-shadow: 0 8px 16px rgba(225, 29, 72, 0.25);
    }

    .login-header h2 {
        color: var(--text-main);
        font-size: 1.5rem;
        font-weight: 800;
        margin-bottom: 4px;
    }

    .login-header p {
        color: var(--text-muted);
        font-size: 0.85rem;
        font-weight: 500;
    }

    .login-body {
        padding: 2rem;
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

    .form-input {
        width: 100%;
        padding: 11px 14px;
        border-radius: 10px;
        border: 1px solid var(--border);
        font-family: inherit;
        font-size: 0.92rem;
        color: var(--text-main);
        transition: all 0.2s;
    }

    .form-input:focus {
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

    .remember-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.5rem;
        font-size: 0.85rem;
    }

    .remember-label {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--text-muted);
        cursor: pointer;
    }

    .btn-submit {
        width: 100%;
        background: linear-gradient(135deg, var(--primary), var(--primary-hover));
        color: white;
        border: none;
        padding: 12px;
        border-radius: 10px;
        font-size: 0.95rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: 0 4px 12px rgba(225, 29, 72, 0.3);
    }

    .btn-submit:hover {
        opacity: 0.95;
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(225, 29, 72, 0.4);
    }

    .test-accounts-box {
        margin-top: 1.75rem;
        background-color: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 10px;
        padding: 1rem;
        font-size: 0.78rem;
    }

    .test-accounts-title {
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 6px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .account-pill {
        display: inline-block;
        padding: 3px 8px;
        background-color: white;
        border: 1px solid var(--border);
        border-radius: 6px;
        margin: 2px 4px 2px 0;
        cursor: pointer;
        transition: all 0.15s;
    }

    .account-pill:hover {
        border-color: var(--primary);
        color: var(--primary);
    }
</style>
@endsection

@section('content')
<div class="login-wrapper">
    <div class="login-card">
        <div class="login-header">
            <div class="login-icon">🎂</div>
            <h2>SweetNest OIMS</h2>
            <p>Order & Inventory Management System</p>
        </div>

        <div class="login-body">
            <form method="POST" action="{{ route('login.post') }}">
                @csrf

                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="{{ old('email', 'owner@sweetnest.com') }}" 
                        class="form-input @error('email') input-error @enderror" 
                        required 
                        autofocus
                    >
                    @error('email')
                        <p class="error-text">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        value="password"
                        class="form-input @error('password') input-error @enderror" 
                        required
                    >
                    @error('password')
                        <p class="error-text">{{ $message }}</p>
                    @enderror
                </div>

                <div class="remember-row">
                    <label class="remember-label">
                        <input type="checkbox" name="remember" value="1" checked>
                        <span>Remember me</span>
                    </label>
                </div>

                <button type="submit" class="btn-submit">
                    Sign In
                </button>
            </form>

            <div class="test-accounts-box">
                <div class="test-accounts-title">
                    <span>Demo Accounts (Password: <code>password</code>)</span>
                </div>
                <div style="color: var(--text-muted); line-height: 1.6;">
                    Click to quick-fill:
                    <br>
                    <span class="account-pill" onclick="fillCreds('owner@sweetnest.com')">👑 Owner</span>
                    <span class="account-pill" onclick="fillCreds('staff@sweetnest.com')">📋 Staff</span>
                    <span class="account-pill" onclick="fillCreds('baker@sweetnest.com')">🧑‍🍳 Baker</span>
                    <span class="account-pill" onclick="fillCreds('delivery@sweetnest.com')">🛵 Delivery</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function fillCreds(email) {
        document.getElementById('email').value = email;
        document.getElementById('password').value = 'password';
    }
</script>
@endsection
