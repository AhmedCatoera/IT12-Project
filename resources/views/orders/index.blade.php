@extends('layouts.app')

@section('title', 'Orders')

@section('content')
@include('partials.sales-subnav')
<div class="mb-[22px] flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold tracking-[-0.02em] max-md:text-[21px]">Orders</h1>
        <p class="mt-1 text-ink-2">Open orders are sorted by due date, nearest first.</p>
    </div>
    <div class="flex flex-wrap gap-2 print:hidden">
        <a href="{{ route('orders.create') }}" class="btn btn-primary"><x-icon name="plus" /> New order</a>
    </div>
</div>

<div class="card">
    <nav class="tabs">
        @foreach ($tabs as $key => $label)
            <a href="{{ route('orders.index', array_filter(['status' => $key, 'type' => $type, 'search' => $search])) }}" class="tab {{ $tab === $key ? 'active' : '' }}">
                {{ $key === 'pending' ? 'Awaiting payment' : $label }} <span class="n">{{ $counts[$key] ?? 0 }}</span>
            </a>
        @endforeach
    </nav>
    <form method="GET" class="flex flex-wrap items-center gap-2 border-b border-line bg-surface px-5 py-3 max-md:px-4 print:hidden">
        <input type="hidden" name="status" value="{{ $tab }}">
        <x-search name="search" value="{{ $search }}" placeholder="Search order number or customer" class="min-w-[200px] flex-1" />
        <select name="type" class="select w-auto max-md:w-full" aria-label="Pickup or delivery">
            <option value="">Pickup and delivery</option>
            <option value="pickup" @selected($type === 'pickup')>Pickup only</option>
            <option value="delivery" @selected($type === 'delivery')>Delivery only</option>
        </select>
        <button type="submit" class="btn">Filter</button>
        @if ($search || $type)
            <a href="{{ route('orders.index', ['status' => $tab]) }}" class="btn btn-ghost">Clear</a>
        @endif
    </form>

    @if ($orders->isEmpty())
        <div class="empty">
            <strong>{{ $search || $type ? 'No orders match your filters' : 'No orders here' }}</strong>
            {{ $tab === 'open' && ! $search && ! $type ? 'Start with the "New order" button above.' : 'Try another tab or clear the filters.' }}
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="table rwd">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Due</th>
                        <th>Status</th>
                        <th class="num max-md:hidden">Total</th>
                        <th class="num">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                        <tr class="{{ $order->status === 'cancelled' ? 'row-muted' : '' }}">
                            <td class="primary">
                                <a href="{{ route('orders.show', $order) }}" class="row-link">{{ $order->customer->name }}</a>
                                <span class="cell-sub">{{ $order->order_number }}@if ($order->isRush()) · <span class="font-semibold text-orange">Rush</span>@endif</span>
                            </td>
                            <td class="whitespace-nowrap">
                                <span class="{{ $order->isOverdue() ? 'text-danger font-semibold' : '' }}">{{ $order->due_label }}</span>
                                <span class="cell-sub">{{ $order->order_type_label }}@if ($order->isOverdue()) · <span class="text-danger">Overdue</span>@endif</span>
                            </td>
                            <td><x-status :status="$order->status" /></td>
                            <td class="num max-md:hidden">@peso($order->total_amount)</td>
                            <td class="num" @if ($order->status !== 'cancelled' && $order->balance_due > 0) data-label="Balance" @endif>
                                @if ($order->status === 'cancelled')
                                    <span class="text-ink-3">—</span>
                                @elseif ($order->balance_due > 0)
                                    <span class="text-danger">@peso($order->balance_due)</span>
                                @else
                                    <span class="text-success">Paid</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $orders->links() }}
    @endif
</div>
@endsection
