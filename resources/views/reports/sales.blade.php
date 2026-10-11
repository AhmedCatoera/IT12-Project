@extends('layouts.app')

@section('title', 'Sales report')

@section('content')
@include('reports._head', ['title' => 'Sales report', 'note' => 'cancelled orders not counted'])

<div class="card">@include('reports._range')</div>

<div class="mb-5 grid grid-cols-4 gap-4 max-xl:grid-cols-2">
    <x-stat label="Sales booked" :value="'₱' . number_format($summary['total'], 2)" :note="$summary['orders'] . ' ' . Str::plural('order', $summary['orders']) . (($fees = array_filter(['rush fees' => $summary['rush_fees'], 'delivery fees' => $summary['delivery_fees']])) ? ', incl. ' . implode(' and ', array_map(fn ($v, $k) => '₱' . number_format($v, 2) . ' ' . $k, $fees, array_keys($fees))) : '')" />
    <x-stat label="Paid on these orders" :value="'₱' . number_format($summary['collected'], 2)" />
    <x-stat label="Still unpaid" :value="'₱' . number_format($summary['outstanding'], 2)" :warn="$summary['outstanding'] > 0" />
    <x-stat label="Cancelled" :value="$summary['cancelled']" note="Orders in this period" />
</div>

<div class="grid items-start gap-5 md:grid-cols-2">
    <div class="card">
        <div class="card-head"><h2 class="card-title">By category</h2></div>
        @if ($byCategory->isEmpty())
            <div class="empty">No sales in this period.</div>
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Category</th><th class="num">Qty</th><th class="num">Sales</th></tr></thead>
                    <tbody>
                        @foreach ($byCategory as $row)
                            <tr><td>{{ \App\Models\Product::CATEGORIES[$row->category] ?? $row->category }}</td><td class="num">{{ (int) $row->qty }}</td><td class="num">@peso($row->total)</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    <div class="card">
        <div class="card-head"><h2 class="card-title">Top products</h2></div>
        @if ($topProducts->isEmpty())
            <div class="empty">No sales in this period.</div>
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Product</th><th class="num">Qty</th><th class="num">Sales</th></tr></thead>
                    <tbody>
                        @foreach ($topProducts as $row)
                            <tr><td>{{ $row->product_name }}@if ($row->is_custom)<span class="cell-sub">Custom item</span>@endif</td><td class="num">{{ (int) $row->qty }}</td><td class="num">@peso($row->total)</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<div class="card" id="list">
    <div class="card-head"><h2 class="card-title">Orders</h2><span class="card-sub">By order date</span></div>
    @if ($orders->isEmpty())
        <div class="empty"><strong>No orders in this period</strong>Pick another date range.</div>
    @else
        <div class="overflow-x-auto">
            <table class="table rwd">
                <thead><tr><th>Ordered</th><th>Order</th><th>Status</th><th class="num">Total</th><th class="num max-md:hidden">Paid</th><th class="num">Balance</th></tr></thead>
                <tbody>
                    @foreach ($orders as $order)
                        <tr>
                            <td class="whitespace-nowrap">{{ $order->order_date->format('M j, Y') }}</td>
                            <td class="primary"><a href="{{ route('orders.show', $order) }}" class="row-link">{{ $order->customer->name }}</a><span class="cell-sub">{{ $order->order_number }}</span></td>
                            <td><x-status :status="$order->status" /></td>
                            <td class="num" data-label="Total">@peso($order->total_amount)</td>
                            <td class="num max-md:hidden">@peso($order->total_paid)</td>
                            <td class="num {{ $order->balance_due > 0 ? 'text-danger' : 'text-ink-3' }}" data-label="Balance">@peso($order->balance_due)</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $orders->links() }}
    @endif
</div>
@endsection
