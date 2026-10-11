@extends('layouts.app')

@section('title', 'Adjustments')

@section('content')
@include('partials.inventory-subnav')
<div class="mb-[22px] flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold tracking-[-0.02em] max-md:text-[21px]">Adjustments</h1>
        <p class="mt-1 text-ink-2">Spoilage, expired or damaged stock, count corrections and entry fixes.</p>
    </div>
    <div class="flex flex-wrap gap-2 print:hidden">
        <a href="{{ route('adjustments.create') }}" class="btn btn-primary"><x-icon name="plus" /> New adjustment</a>
    </div>
</div>

<div class="card">
    @if ($adjustments->isEmpty())
        <div class="empty"><strong>No adjustments yet</strong>Press "New adjustment" when stock spoils or a number needs fixing.</div>
    @else
        <div class="overflow-x-auto">
            <table class="table rwd">
                <thead><tr><th>Adjustment</th><th>Reason</th><th>Changes</th><th class="max-md:hidden">Remarks</th><th class="max-md:hidden">By</th></tr></thead>
                <tbody>
                    @foreach ($adjustments as $adjustment)
                        <tr>
                            <td class="whitespace-nowrap primary"><span class="font-semibold">{{ $adjustment->number }}</span><span class="cell-sub">{{ $adjustment->adjusted_date->format('M j, Y') }}</span></td>
                            <td>
                                {{ $adjustment->reason_label }}
                                @if ($adjustment->physicalCount)<span class="cell-sub"><a href="{{ route('counts.show', $adjustment->physicalCount) }}">From {{ $adjustment->physicalCount->number }}</a></span>@endif
                            </td>
                            <td>
                                @foreach ($adjustment->items as $item)
                                    <span class="whitespace-nowrap {{ $item->quantity < 0 ? 'text-danger' : 'text-success' }}">{{ $item->ingredient->name }} {{ $item->quantity > 0 ? '+' : '−' }}{{ rtrim(rtrim(number_format(abs($item->quantity), 2), '0'), '.') }} {{ $item->ingredient->unit }}</span>@if (! $loop->last)<br>@endif
                                @endforeach
                            </td>
                            <td class="max-md:hidden"><span class="line-clamp-2 max-w-[320px]">{{ $adjustment->remarks }}</span></td>
                            <td class="max-md:hidden">{{ $adjustment->adjuster?->first_name ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $adjustments->links() }}
    @endif
</div>
@endsection
