@extends('layouts.app')

@section('title', 'Customers')

@section('styles')
<style>
.filter-bar {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
    margin-bottom: 1.25rem;
}
.search-wrap {
    position: relative;
    flex: 1;
    max-width: 380px;
}
.search-wrap input {
    padding-left: 36px;
}
.search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 14px;
    color: var(--text-faint);
    pointer-events: none;
}
.customer-avatar {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: linear-gradient(135deg, #fecdd3, #fda4af);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 800;
    color: #be123c;
    flex-shrink: 0;
}
.customer-info { display: flex; align-items: center; gap: 10px; }
.customer-name-link {
    font-weight: 700;
    color: var(--text-main);
    text-decoration: none;
    display: block;
    font-size: 0.875rem;
}
.customer-name-link:hover { color: var(--primary); }
</style>
@endsection

@section('content')
<div class="page-header">
    <div>
        <div class="page-title">👥 Customer Management</div>
        <div class="page-subtitle">Manage customer profiles, contact info, and order history</div>
    </div>
    <a href="{{ route('customers.create') }}" class="btn-primary">
        + Add New Customer
    </a>
</div>

<div class="card">
    <form method="GET" action="{{ route('customers.index') }}" class="filter-bar">
        <div class="search-wrap">
            <span class="search-icon">🔍</span>
            <input
                type="text"
                name="search"
                value="{{ $search }}"
                placeholder="Search name, phone, or Facebook..."
                class="form-input"
            >
        </div>
        <button type="submit" class="btn-primary btn-sm">Search</button>
        @if($search)
            <a href="{{ route('customers.index') }}" class="btn-secondary btn-sm">✕ Clear</a>
        @endif
    </form>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Contact</th>
                    <th>Facebook</th>
                    <th>Address</th>
                    <th style="text-align:center;">Orders</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $customer)
                <tr>
                    <td>
                        <div class="customer-info">
                            <div class="customer-avatar">{{ strtoupper(substr($customer->first_name, 0, 1)) }}</div>
                            <div>
                                <a href="{{ route('customers.show', $customer) }}" class="customer-name-link">{{ $customer->name }}</a>
                                <div style="font-size: 0.72rem; color: var(--text-muted);">ID #{{ $customer->id }}</div>
                            </div>
                        </div>
                    </td>
                    <td style="font-weight: 600; font-size: 0.875rem;">{{ $customer->contact_number }}</td>
                    <td style="font-size: 0.82rem; color: var(--text-muted);">{{ $customer->facebook_name ?: '—' }}</td>
                    <td style="max-width: 220px; font-size: 0.82rem; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        {{ $customer->address ?: '—' }}
                    </td>
                    <td style="text-align: center;">
                        <span class="badge {{ $customer->orders_count > 0 ? 'badge-success' : 'badge-neutral' }}">
                            {{ $customer->orders_count }} {{ Str::plural('order', $customer->orders_count) }}
                        </span>
                    </td>
                    <td style="text-align: right;">
                        <div style="display: flex; gap: 6px; justify-content: flex-end;">
                            <a href="{{ route('customers.show', $customer) }}" class="btn-secondary btn-sm">View</a>
                            <a href="{{ route('customers.edit', $customer) }}" class="btn-secondary btn-sm">Edit</a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <div class="empty-state-icon">👥</div>
                            <h3>No customers found</h3>
                            <p>{{ $search ? "No results for \"$search\". Try a different search term." : "Start by adding your first customer." }}</p>
                            @if(!$search)
                                <a href="{{ route('customers.create') }}" class="btn-primary btn-sm">Add First Customer</a>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $customers->links() }}
</div>
@endsection
