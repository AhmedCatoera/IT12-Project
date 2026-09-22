@extends('layouts.app')

@section('title', 'Access Denied - 403')

@section('styles')
<style>
    .error-wrapper {
        min-height: calc(100vh - 220px);
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 2rem;
    }

    .error-card {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 3rem 2rem;
        max-width: 500px;
        box-shadow: var(--shadow-md);
    }

    .error-icon {
        font-size: 50px;
        margin-bottom: 1rem;
    }

    .error-code {
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--primary);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.5rem;
    }

    .error-title {
        font-size: 1.5rem;
        font-weight: 800;
        margin-bottom: 1rem;
        color: var(--text-main);
    }

    .error-message {
        font-size: 0.95rem;
        color: var(--text-muted);
        margin-bottom: 2rem;
        line-height: 1.6;
    }

    .btn-home {
        display: inline-block;
        background: linear-gradient(135deg, var(--primary), var(--primary-hover));
        color: white;
        text-decoration: none;
        padding: 10px 24px;
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.9rem;
        transition: all 0.2s;
    }

    .btn-home:hover {
        opacity: 0.9;
        transform: translateY(-1px);
    }
</style>
@endsection

@section('content')
<div class="error-wrapper">
    <div class="error-card">
        <div class="error-icon">🚫</div>
        <div class="error-code">403 Forbidden</div>
        <h2 class="error-title">Access Restricted</h2>
        <p class="error-message">
            {{ $exception->getMessage() ?: 'You do not have the required permissions to access this page or perform this action.' }}
        </p>
        <a href="{{ route('dashboard') }}" class="btn-home">
            Return to Dashboard
        </a>
    </div>
</div>
@endsection
