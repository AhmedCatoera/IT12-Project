@extends('layouts.app')

@section('title', 'Customers')

@section('styles')
<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .page-title h2 {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--text-main);
    }

    .page-title p {
        font-size: 0.85rem;
        color: var(--text-muted);
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--primary), var(--primary-hover));
        color: white;
        text-decoration: none;
        padding: 9px 18px;
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.88rem;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
        border: none;
        cursor: pointer;
    }

    .btn-primary:hover {
        opacity: 0.95;
        transform: translateY(-1px);
    }

    .btn-secondary {
        background-color: white;
        border: 1px solid var(--border);
        color: var(--text-main);
        text-decoration: none;
        padding: 7px 12px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.82rem;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: all 0.15s;
    }

    .btn-secondary:hover {
        background-color: #f1f5f9;
        border-color: #cbd5e1;
    }

    .card {
        background-color: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.5rem;
        box-shadow: var(--shadow);
    }

    .filter-bar {
        display: flex;
        gap: 10px;
        margin-bottom: 1.5rem;
    }

    .search-input {
        flex: 1;
        max-width: 400px;
        padding: 9px 14px;
        border-radius: 8px;
        border: 1px solid var(--border);
        font-family: inherit;
        font-size: 0.88rem;
    }

    .search-input:focus {
        outline: none;
        border-color: var(--primary);
    }

    .table-responsive {
        overflow-x: auto;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.88rem;
    }

    .table th {
        text-align: left;
        padding: 10px 14px;
        color: var(--text-muted);
        font-weight: 700;
        border-bottom: 1px solid var(--border);
        background-color: #f8fafc;
    }

    .table td {
        padding: 12px 14px;
        border-bottom: 1px solid #f1f5f9;
    }

    .customer-name {
        font-weight: 700;
        color: var(--text-main);
        text-decoration: none;
    }

    .customer-name:hover {
        color: var(--primary);
    }

    .order-badge {
        background-color: var(--primary-light);
        color: var(--primary);
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.75rem;
    }

    .pagination-wrapper {
        margin-top: 1.5rem;
    }
</style>
@endsection

@section('content')
<div class="page-header">
    <div class="page-title">
        <h2>Customer Management</h2>
        <p>Manage customer profiles, contact info, and past order records</p>
    </div>
    <a href="{{ route('customers.create') }}" class="btn-primary">
        + Add New Customer
    </a>
</div>

<div class="card">
    <form method="GET" action="{{ route('customers.index') }}" class="filter-bar">
        <input 
            type="text" 
            name="search" 
            value="{{ $search }}" 
            placeholder="Search by customer name, phone, or Facebook..." 
            class="search-input"
        >
        <button type="submit" class="btn-primary">Search</button>
        @if($search)
            <a href="{{ route('customers.index') }}" class="btn-secondary">Clear</a>
        @endif
    </form>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Customer Name</th>
                    <th>Contact Number</th>
                    <th>Facebook Account</th>
                    <th>Delivery Address</th>
                    <th>Total Orders</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $customer)
                <tr>
                    <td>
                        <a href="{{ route('customers.show', $customer) }}" class="customer-name">
                            {{ $customer->name }}
                        </a>
                    </td>
                    <td><strong>{{ $customer->contact_number }}</strong></td>
                    <td>{{ $customer->facebook_name ?: '—' }}</td>
                    <td style="max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        {{ $customer->address ?: 'No address specified' }}
                    </td>
                    <td>
                        <span class="order-badge">{{ $customer->orders_count }} {{ Str::plural('order', $customer->orders_count) }}</span>
                    </td>
                    <td>
                        <a href="{{ route('customers.show', $customer) }}" class="btn-secondary" style="margin-right: 4px;">
                            View
                        </a>
                        <a href="{{ route('customers.edit', $customer) }}" class="btn-secondary">
                            Edit
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2.5rem;">
                        No customer records found. Click <strong>"+ Add New Customer"</strong> to create one.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-wrapper">
        {{ $customers->links() }}
    </div>
</div>
@endsection
