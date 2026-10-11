@extends('layouts.app')

@section('title', 'Payments')

@section('content')
@include('partials.sales-subnav')
<div class="mb-[22px] flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold tracking-[-0.02em] max-md:text-[21px]">Payments</h1>
        <p class="mt-1 text-ink-2">All payments received. To record a payment, open the order.</p>
    </div>
</div>

<div class="card">
    <form method="GET" class="flex flex-wrap items-center gap-2 border-b border-line bg-surface px-5 py-3 max-md:px-4 print:hidden">
        <x-search name="search" value="{{ $search }}" placeholder="Search order, customer or reference number" class="min-w-[200px] flex-1" />
        <select name="method" class="select w-auto max-md:w-full" aria-label="Method">
            <option value="">All methods</option>
            @foreach (\App\Models\Payment::METHODS as $key => $label)
                <option value="{{ $key }}" @selected($method === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <label class="text-xs text-ink-2" for="pay_from">From</label>
        <input id="pay_from" type="date" name="from" value="{{ $from?->toDateString() }}" class="input w-auto max-md:w-full">
        <label class="text-xs text-ink-2" for="pay_to">to</label>
        <input id="pay_to" type="date" name="to" value="{{ $to?->toDateString() }}" class="input w-auto max-md:w-full">
        <button type="submit" class="btn">Filter</button>
        @if ($search || $method || $from || $to)<a href="{{ route('payments.index') }}" class="btn btn-ghost">Clear</a>@endif
    </form>
    <div class="flex flex-wrap items-baseline gap-x-[22px] gap-y-2 border-b border-line bg-surface-2 px-5 py-3 max-md:px-4">
        <div><span class="text-ink-2">Total</span> <strong>@peso($summary['total'])</strong> <span class="text-ink-2">from {{ $summary['count'] }} {{ Str::plural('payment', $summary['count']) }}</span></div>
        @foreach ($summary['by_method'] as $key => $total)
            <div class="text-ink-2">{{ \App\Models\Payment::METHODS[$key] ?? $key }}: <span class="text-body">@peso($total)</span></div>
        @endforeach
    </div>

    @if ($payments->isEmpty())
        <div class="empty"><strong>No payments found</strong>Try different filters.</div>
    @else
        <div class="overflow-x-auto">
            <table class="table rwd">
                <thead><tr><th>Date</th><th>Order</th><th>Type</th><th>Method</th><th class="max-md:hidden">Received by</th><th class="num">Amount</th></tr></thead>
                <tbody>
                    @foreach ($payments as $payment)
                        <tr>
                            <td class="whitespace-nowrap">{{ $payment->paid_at->format('M j, Y') }}<span class="cell-sub">{{ $payment->paid_at->format('g:i A') }}</span></td>
                            <td class="primary"><a href="{{ route('orders.show', $payment->order) }}" class="row-link">{{ $payment->order->customer->name }}</a><span class="cell-sub">{{ $payment->order->order_number }}</span></td>
                            <td>{{ $payment->type_label }}</td>
                            <td>{{ $payment->method_label }}@include('payments._proof')</td>
                            <td class="max-md:hidden">{{ $payment->receiver?->name ?? '—' }}</td>
                            <td class="num font-semibold">@peso($payment->amount)</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $payments->links() }}
    @endif
</div>
@endsection
