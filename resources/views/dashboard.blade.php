@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@php
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
@endphp
<div class="mb-[22px] flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold tracking-[-0.02em] max-md:text-[21px]">{{ $greeting }}, {{ auth()->user()->first_name }}</h1>
        <p class="mt-1 text-ink-2">{{ now()->format('l, F j, Y') }}</p>
    </div>
    <div class="flex flex-wrap gap-2 print:hidden">
        <a href="{{ route('orders.create') }}" class="btn btn-primary"><x-icon name="plus" /> New order</a>
    </div>
</div>

<div class="grid items-start gap-5 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
    <div class="card">
        <div class="card-head">
            <h2 class="card-title">Due today and tomorrow</h2>
            <a href="{{ route('orders.index') }}" class="text-xs">All open orders</a>
        </div>
        @if ($dueSoon->isEmpty())
            <div class="empty"><strong>Nothing due today or tomorrow</strong>New orders will show up here as their dates get close.</div>
        @else
            <div class="overflow-x-auto">
                <table class="table rwd">
                    <thead><tr><th>Due</th><th>Order</th><th>Status</th><th class="num">Balance</th></tr></thead>
                    <tbody>
                        @foreach ($dueSoon as $order)
                            <tr>
                                <td class="whitespace-nowrap">
                                    <span class="{{ $order->isOverdue() ? 'text-danger font-semibold' : '' }}">{{ $order->due_label }}</span>
                                    <span class="cell-sub">{{ $order->order_type_label }}@if ($order->isOverdue()) · <span class="text-danger">Overdue</span>@endif</span>
                                </td>
                                <td class="primary">
                                    <a href="{{ route('orders.show', $order) }}" class="row-link">{{ $order->customer->name }}</a>
                                    <span class="cell-sub">{{ $order->order_number }}@if ($order->isRush()) · <span class="font-semibold text-orange">Rush</span>@endif</span>
                                </td>
                                <td><x-status :status="$order->status" /></td>
                                <td class="num {{ $order->balance_due > 0 ? 'text-danger' : 'text-ink-3' }}" @if ($order->balance_due > 0) data-label="Balance" @endif>{{ $order->balance_due > 0 ? '₱' . number_format($order->balance_due, 2) : 'Paid' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $dueSoon->links() }}
        @endif
    </div>

    <div class="card">
        <div class="card-head">
            <h2 class="card-title">Low stock</h2>
            <a href="{{ route('inventory.index', ['filter' => 'low']) }}" class="text-xs">See all</a>
        </div>
        @if ($lowStock->isEmpty())
            <div class="empty"><strong>All stocked up</strong>No ingredient is at its reorder level.</div>
        @else
            <ul class="m-0 list-none p-0">
                @foreach ($lowStock as $ingredient)
                    <li class="flex items-center justify-between gap-3 border-b border-line px-5 py-3 last:border-b-0 max-md:px-4">
                        <a href="{{ route('inventory.show', $ingredient) }}" class="row-link">{{ $ingredient->name }}</a>
                        <span class="text-right tabular-nums">
                            <span class="text-danger font-semibold">@qty($ingredient->stock_on_hand) {{ $ingredient->unit }}</span>
                            <span class="cell-sub">Reorder at @qty($ingredient->reorder_level)</span>
                        </span>
                    </li>
                @endforeach
            </ul>
            @owner
                <div class="card-foot"><a href="{{ route('stockIns.create') }}" class="btn btn-sm"><x-icon name="stock-in" /> Record stock in</a></div>
            @else
                <div class="card-foot text-xs text-ink-2">Tell the Owner so they can restock.</div>
            @endowner
        @endif
    </div>
</div>

@if ($isOwner)
    @php $twoWeeks = $salesDays->sum('total'); @endphp
    <div class="card">
        <div class="card-head">
            <div><h2 class="card-title">Sales booked, last 14 days</h2><span class="card-sub">@peso($twoWeeks) in total, cancelled orders left out</span></div>
            <a href="{{ route('reports.sales') }}" class="text-xs">Sales report</a>
        </div>
        <div class="card-body">
            <x-react name="SalesChart" :props="['days' => $salesDays->map(fn ($day) => [
                'date' => $day['date']->toDateString(),
                'label' => $day['date']->format('M j'),
                'short' => $day['date']->format('j'),
                'total' => $day['total'],
                'isToday' => $day['date']->isToday(),
                'url' => route('reports.sales', ['from' => $day['date']->toDateString(), 'to' => $day['date']->toDateString()]),
            ])->values()]" />
        </div>
    </div>
@endif
@endsection
