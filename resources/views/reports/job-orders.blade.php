@extends('layouts.app')

@section('title', 'Job orders report')

@section('content')
@include('reports._head', ['title' => 'Job orders report', 'note' => 'by production date'])

<div class="card">@include('reports._range')</div>

<div class="mb-5 grid grid-cols-4 gap-4 max-xl:grid-cols-2">
    <x-stat label="Job orders" :value="$summary['total']" />
    <x-stat label="Completed" :value="$summary['completed']" :note="$summary['on_time'] . ' finished before the due time'" />
    <x-stat label="Still open" :value="$summary['open']" note="Pending or in progress" :warn="$summary['open'] > 0" />
    <x-stat label="Cancelled" :value="$summary['cancelled']" />
</div>

<div class="card" id="list">
    @if ($jobOrders->isEmpty())
        <div class="empty"><strong>No job orders in this period</strong>Pick another date range.</div>
    @else
        <div class="overflow-x-auto">
            <table class="table rwd">
                <thead><tr><th>Production date</th><th>Job order</th><th class="max-md:hidden">Assigned to</th><th>Status</th><th class="num max-md:hidden">Ingredients logged</th><th class="max-md:hidden">Finished</th></tr></thead>
                <tbody>
                    @foreach ($jobOrders as $job)
                        <tr>
                            <td class="whitespace-nowrap">{{ $job->production_date->format('M j, Y') }}</td>
                            <td class="primary"><a href="{{ route('jobOrders.show', $job) }}" class="row-link">{{ $job->number }} · {{ $job->order->customer->name }}</a><span class="cell-sub">{{ $job->order->order_number }} · due {{ $job->order->scheduled_at->format('M j, g:i A') }}</span></td>
                            <td class="max-md:hidden">{{ $job->assignee?->name ?? '—' }}</td>
                            <td><x-status :status="$job->status" type="job" /></td>
                            <td class="num max-md:hidden">{{ $job->stock_out_items_count }}</td>
                            <td class="max-md:hidden whitespace-nowrap">
                                @if ($job->completed_at)
                                    {{ $job->completed_at->format('M j, g:i A') }}
                                    @if ($job->completed_at->gt($job->order->scheduled_at))<span class="cell-sub text-danger">Late</span>@endif
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $jobOrders->links() }}
    @endif
</div>
@endsection
