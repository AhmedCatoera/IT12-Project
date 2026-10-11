@extends('layouts.app')

@section('title', $customer->name)

@section('content')
<a href="{{ route('customers.index') }}" class="mb-2.5 inline-flex items-center gap-1 text-sm font-medium text-ink-2 hover:text-brand hover:no-underline"><x-icon name="back" /> Customers</a>

<div class="mb-[22px] flex flex-wrap items-end justify-between gap-4">
    <div>
        <div class="flex flex-wrap items-center gap-2.5">
            <h1 class="text-2xl font-bold tracking-[-0.02em] max-md:text-[21px]">{{ $customer->name }}</h1>
            @unless ($customer->is_active)<x-status :status="false" type="active" />@endunless
        </div>
        <p class="mt-1 text-ink-2">{{ $customer->contact_display }}@if ($customer->facebook_name) · FB: {{ $customer->facebook_name }}@endif</p>
    </div>
    <div class="flex flex-wrap gap-2 print:hidden">
        <form method="POST" action="{{ route('customers.toggleStatus', $customer) }}" class="inline"
            @if ($customer->is_active)
                data-confirm="Deactivate {{ $customer->name }}? Their past orders stay on record, but they won't be listed when creating new orders." data-confirm-button="Deactivate"
            @endif>
            @csrf @method('PATCH')
            <button type="submit" class="btn {{ $customer->is_active ? 'btn-danger' : 'btn-success' }}">{{ $customer->is_active ? 'Deactivate' : 'Activate' }}</button>
        </form>
        <button type="button" class="btn" data-modal-open="editCustomer"><x-icon name="edit" /> Edit</button>
        @if ($customer->is_active)
            <a href="{{ route('orders.create', ['customer_id' => $customer->id]) }}" class="btn btn-primary"><x-icon name="plus" /> New order</a>
        @endif
    </div>
</div>

@unless ($customer->is_active)
    <div class="alert alert-warning"><div class="grow">This customer is deactivated, so they can't be picked for new orders. Activate them to take a new order.</div></div>
@endunless

<div class="grid items-start gap-5 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
    <div class="card">
        <div class="card-head"><h2 class="card-title">Orders</h2><span class="card-sub">{{ $orders->total() }} total</span></div>
        @if ($orders->isEmpty())
            <div class="empty"><strong>No orders yet</strong>Orders for this customer will be listed here.</div>
        @else
            <div class="overflow-x-auto">
                <table class="table rwd">
                    <thead>
                        <tr><th>Order</th><th>Due</th><th>Status</th><th class="num max-md:hidden">Total</th><th class="num">Balance</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr>
                                <td class="primary">
                                    <a href="{{ route('orders.show', $order) }}" class="row-link">{{ $order->order_number }}</a>
                                    <span class="cell-sub">{{ $order->items->first()?->name }}@if ($order->items->count() > 1) + {{ $order->items->count() - 1 }} more @endif</span>
                                </td>
                                <td class="whitespace-nowrap">{{ $order->scheduled_at->format('M j, Y') }}</td>
                                <td><x-status :status="$order->status" /></td>
                                <td class="num max-md:hidden">@peso($order->total_amount)</td>
                                <td class="num {{ $order->balance_due > 0 && $order->status !== 'cancelled' ? 'text-danger' : 'text-ink-3' }}" @if ($order->status !== 'cancelled') data-label="Balance" @endif>{{ $order->status === 'cancelled' ? '—' : '₱' . number_format($order->balance_due, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $orders->links() }}
        @endif
    </div>

    <div>
        <div class="card">
            <div class="card-head"><h2 class="card-title">Details</h2></div>
            <div class="card-body">
                <dl class="kv">
                    <dt>Contact</dt><dd>{{ $customer->contact_display }}</dd>
                    <dt>Facebook</dt><dd>{{ $customer->facebook_name ?: '—' }}</dd>
                    <dt>Address</dt><dd>{{ $customer->full_address ?: '—' }}</dd>
                    <dt>Customer since</dt><dd>{{ $customer->created_at->format('M j, Y') }}</dd>
                </dl>
                @if ($customer->maps_url)
                    <div class="mt-3.5 flex flex-wrap gap-2"><a href="{{ $customer->maps_url }}" target="_blank" rel="noopener" class="btn btn-sm"><x-icon name="directions" /> Directions in Google Maps</a></div>
                @endif
                @if ($customer->has_pin)
                    <details class="group mt-2.5 print:hidden">
                        <summary class="inline-flex cursor-pointer list-none items-center gap-[7px] py-1 text-[13.5px] font-semibold text-brand hover:underline [&::-webkit-details-marker]:hidden [&_.icon]:size-[15px]">
                            <x-icon name="pin" /> <span class="group-open:hidden">Show on map</span><span class="hidden group-open:inline">Hide map</span>
                        </summary>
                        <x-react name="PinMap" :props="['lat' => $customer->latitude, 'lng' => $customer->longitude, 'map' => \App\Support\Address::mapConfig(), 'label' => 'Map pin of the customer\'s address']" />
                    </details>
                @endif
            </div>
        </div>
        <div class="card">
            <div class="card-head"><h2 class="card-title">Totals</h2><span class="card-sub">Cancelled orders not counted</span></div>
            <div class="card-body money">
                <div class="money-row"><span class="text-ink-2">Orders</span><span>{{ $summary['orders'] }}</span></div>
                <div class="money-row"><span class="text-ink-2">Total ordered</span><span>@peso($summary['total'])</span></div>
                <div class="money-row"><span class="text-ink-2">Paid</span><span>@peso($summary['paid'])</span></div>
                <div class="money-row total"><span>Balance</span><span class="{{ $summary['balance'] > 0 ? 'text-danger' : '' }}">@peso($summary['balance'])</span></div>
            </div>
        </div>
    </div>
</div>

<x-modal id="editCustomer" title="Edit customer" wide :open="$errors->editCustomer->any()">
    <form method="POST" action="{{ route('customers.update', $customer) }}">
        @csrf @method('PUT')
        <div class="modal-body">
            @include('customers._fields', ['bag' => 'editCustomer', 'p' => 'ec_', 'v' => $errors->editCustomer->any() ? old() : $customer->toArray()])
        </div>
        <div class="modal-foot">
            <button type="button" class="btn" data-modal-close>Cancel</button>
            <button type="submit" class="btn btn-primary">Save changes</button>
        </div>
    </form>
</x-modal>
@endsection
