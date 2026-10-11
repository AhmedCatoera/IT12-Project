@extends('layouts.app')

@section('title', 'Job Orders')

@section('content')
<div class="mb-[22px] flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold tracking-[-0.02em] max-md:text-[21px]">Job Orders</h1>
        <p class="mt-1 text-ink-2">Kitchen work for confirmed orders. Staff log the ingredients used on each one.</p>
    </div>
</div>

@if ($waiting->isNotEmpty())
    <div class="card">
        <div class="card-head">
            <h2 class="card-title">Confirmed orders without a job order</h2>
            <span class="badge b-warning plain">{{ $waiting->total() }} waiting</span>
        </div>
        <div class="overflow-x-auto">
            <table class="table rwd">
                <thead><tr><th>Order</th><th>Due</th><th class="actions"></th></tr></thead>
                <tbody>
                    @foreach ($waiting as $order)
                        <tr>
                            <td class="primary"><a href="{{ route('orders.show', $order) }}" class="row-link">{{ $order->customer->name }}</a><span class="cell-sub">{{ $order->order_number }}</span></td>
                            <td class="whitespace-nowrap"><span class="{{ $order->isOverdue() ? 'text-danger font-semibold' : '' }}">{{ $order->due_label }}</span><span class="cell-sub">{{ $order->order_type_label }}</span></td>
                            <td class="actions">
                                <button type="button" class="btn btn-sm btn-primary" data-modal-open="createJobOrder"
                                    data-action="{{ route('jobOrders.store', $order) }}"
                                    data-title="Job order for {{ $order->order_number }}"
                                    data-max="{{ json_encode(['production_date' => $order->latestProductionDate()->toDateString()]) }}"
                                    data-fill="{{ json_encode(['production_date' => $order->latestProductionDate()->toDateString(), 'assigned_to' => '', 'notes' => '', '_order_id' => $order->id]) }}">Create job order</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $waiting->links() }}
    </div>
@endif

<div class="card">
    <nav class="tabs">
        @foreach ($tabs as $key => $label)
            <a href="{{ route('jobOrders.index', array_filter(['status' => $key, 'search' => $search])) }}" class="tab {{ $tab === $key ? 'active' : '' }}">
                {{ $label }} <span class="n">{{ $counts[$key] ?? 0 }}</span>
            </a>
        @endforeach
    </nav>
    <form method="GET" class="flex flex-wrap items-center gap-2 border-b border-line bg-surface px-5 py-3 max-md:px-4 print:hidden">
        <input type="hidden" name="status" value="{{ $tab }}">
        <x-search name="search" value="{{ $search }}" placeholder="Search order number or customer" class="min-w-[200px] flex-1" />
        <button type="submit" class="btn">Search</button>
        @if ($search)<a href="{{ route('jobOrders.index', ['status' => $tab]) }}" class="btn btn-ghost">Clear</a>@endif
    </form>

    @if ($jobOrders->isEmpty())
        <div class="empty">
            <strong>No job orders here</strong>
            {{ $tab === 'active' ? 'Open a confirmed order and press "Create job order".' : 'Try another tab.' }}
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="table rwd">
                <thead><tr><th>Job order</th><th>Production date</th><th class="max-md:hidden">Due</th><th class="max-md:hidden">Assigned to</th><th>Status</th></tr></thead>
                <tbody>
                    @foreach ($jobOrders as $job)
                        <tr class="{{ $job->status === 'cancelled' ? 'row-muted' : '' }}">
                            <td class="primary">
                                <a href="{{ route('jobOrders.show', $job) }}" class="row-link">{{ $job->number }} · {{ $job->order->customer->name }}</a>
                                <span class="cell-sub">{{ $job->order->order_number }}</span>
                            </td>
                            <td class="whitespace-nowrap" data-label="Make on">{{ $job->production_date->isToday() ? 'Today' : $job->production_date->format('D, M j') }}</td>
                            <td class="whitespace-nowrap max-md:hidden">{{ $job->order->due_label }}</td>
                            <td class="max-md:hidden">{{ $job->assignee?->name ?? '—' }}</td>
                            <td><x-status :status="$job->status" type="job" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $jobOrders->links() }}
    @endif
</div>

@include('job-orders._create-modal', ['forOrder' => null])
@endsection
