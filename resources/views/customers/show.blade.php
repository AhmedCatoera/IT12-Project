@extends('layouts.app')

@section('title', $customer->name . ' - Customer Details')

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

    .btn-primary {
        background: linear-gradient(135deg, var(--primary), var(--primary-hover));
        color: white;
        text-decoration: none;
        padding: 8px 16px;
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.88rem;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .btn-secondary {
        background-color: white;
        border: 1px solid var(--border);
        color: var(--text-main);
        text-decoration: none;
        padding: 8px 14px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.85rem;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .btn-danger {
        background-color: white;
        border: 1px solid #fca5a5;
        color: var(--danger);
        padding: 8px 14px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.85rem;
        cursor: pointer;
    }

    .btn-danger:hover {
        background-color: #fee2e2;
    }

    .profile-grid {
        display: grid;
        grid-template-columns: 320px 1fr;
        gap: 1.5rem;
    }

    .card {
        background-color: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.5rem;
        box-shadow: var(--shadow);
    }

    .profile-card {
        height: fit-content;
    }

    .profile-avatar {
        width: 64px;
        height: 64px;
        background: linear-gradient(135deg, #fb7185, var(--primary));
        border-radius: 16px;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        font-weight: 800;
        margin-bottom: 1rem;
    }

    .detail-item {
        margin-bottom: 1rem;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 0.5rem;
    }

    .detail-label {
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .detail-value {
        font-size: 0.95rem;
        font-weight: 600;
        color: var(--text-main);
        margin-top: 2px;
    }

    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .mini-stat {
        background-color: #f8fafc;
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 1rem;
    }

    .mini-stat-label {
        font-size: 0.72rem;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
    }

    .mini-stat-value {
        font-size: 1.3rem;
        font-weight: 800;
        color: var(--text-main);
        margin-top: 4px;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.88rem;
    }

    .table th {
        text-align: left;
        padding: 10px 12px;
        color: var(--text-muted);
        font-weight: 700;
        border-bottom: 1px solid var(--border);
        background-color: #f8fafc;
    }

    .table td {
        padding: 12px;
        border-bottom: 1px solid #f1f5f9;
    }

    .badge-status {
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
    }

    .status-pending { background-color: #fef3c7; color: #b45309; }
    .status-confirmed { background-color: #dbeafe; color: #1d4ed8; }
    .status-in_production { background-color: #e0e7ff; color: #4338ca; }
    .status-ready_for_release { background-color: #d1fae5; color: #047857; }
    .status-completed { background-color: #dcfce7; color: #15803d; }
    .status-cancelled { background-color: #fee2e2; color: #b91c1c; }

    @media (max-width: 900px) {
        .profile-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection

@section('content')
<div class="page-header">
    <div class="page-title">
        <h2>Customer Profile</h2>
        <p>Complete directory and order history records</p>
    </div>
    <div style="display: flex; gap: 8px;">
        <a href="{{ route('customers.index') }}" class="btn-secondary">← Back to Customers</a>
        <a href="{{ route('customers.edit', $customer) }}" class="btn-primary">Edit Profile</a>
    </div>
</div>

<div class="profile-grid">
    <!-- Left Column: Customer Details -->
    <div class="card profile-card">
        <div class="profile-avatar">
            {{ strtoupper(substr($customer->name, 0, 1)) }}
        </div>
        <h3 style="font-size: 1.3rem; font-weight: 800; margin-bottom: 1rem;">{{ $customer->name }}</h3>

        <div class="detail-item">
            <div class="detail-label">Contact Number</div>
            <div class="detail-value">{{ $customer->contact_number }}</div>
        </div>

        <div class="detail-item">
            <div class="detail-label">Facebook / Messenger Account</div>
            <div class="detail-value">{{ $customer->facebook_name ?: 'Not provided' }}</div>
        </div>

        <div class="detail-item">
            <div class="detail-label">Delivery Address</div>
            <div class="detail-value">{{ $customer->address ?: 'Not provided' }}</div>
        </div>

        <div class="detail-item">
            <div class="detail-label">Customer Since</div>
            <div class="detail-value">{{ $customer->created_at->format('F d, Y') }}</div>
        </div>

        <div style="margin-top: 1.5rem; pt: 1rem;">
            <form method="POST" action="{{ route('customers.destroy', $customer) }}" onsubmit="return confirm('Are you sure you want to remove this customer record?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-danger" style="width: 100%;">
                    Delete Customer
                </button>
            </form>
        </div>
    </div>

    <!-- Right Column: Order History -->
    <div class="card">
        <div style="margin-bottom: 1.25rem;">
            <h3 style="font-size: 1.15rem; font-weight: 800;">Order History</h3>
            <p style="font-size: 0.85rem; color: var(--text-muted);">All cake, pastry, and souvenir orders placed by this customer</p>
        </div>

        <div class="stats-row">
            <div class="mini-stat">
                <div class="mini-stat-label">Total Orders</div>
                <div class="mini-stat-value">{{ $customer->orders->count() }}</div>
            </div>
            <div class="mini-stat">
                <div class="mini-stat-label">Total Spent</div>
                <div class="mini-stat-value">₱{{ number_format($totalSpent, 2) }}</div>
            </div>
            <div class="mini-stat">
                <div class="mini-stat-label">Total Paid</div>
                <div class="mini-stat-value" style="color: #10b981;">₱{{ number_format($totalPaid, 2) }}</div>
            </div>
            <div class="mini-stat">
                <div class="mini-stat-label">Outstanding Balance</div>
                <div class="mini-stat-value" style="color: {{ ($totalSpent - $totalPaid) > 0 ? '#e11d48' : '#10b981' }};">
                    ₱{{ number_format(max(0, $totalSpent - $totalPaid), 2) }}
                </div>
            </div>
        </div>

        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Scheduled Date</th>
                        <th>Items Summary</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Payment</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customer->orders as $order)
                    <tr>
                        <td style="font-weight: 700;">{{ $order->order_number }}</td>
                        <td>{{ $order->scheduled_date->format('M d, Y h:i A') }}</td>
                        <td>
                            @foreach($order->items as $item)
                                <div style="font-size: 0.85rem;">
                                    <strong>{{ $item->quantity }}x</strong> {{ $item->item_name }}
                                    @if($item->flavor)
                                        <span style="color: var(--text-muted); font-size: 0.78rem;">({{ $item->flavor }})</span>
                                    @endif
                                </div>
                            @endforeach
                        </td>
                        <td>
                            <span class="badge-status status-{{ $order->status }}">
                                {{ str_replace('_', ' ', $order->status) }}
                            </span>
                        </td>
                        <td style="font-weight: 700;">₱{{ number_format($order->total_amount, 2) }}</td>
                        <td>
                            <span style="font-size: 0.8rem; font-weight: 700; color: {{ $order->payment_status === 'fully_paid' ? '#10b981' : '#f59e0b' }};">
                                {{ strtoupper(str_replace('_', ' ', $order->payment_status)) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2.5rem;">
                            This customer has not placed any orders yet.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
