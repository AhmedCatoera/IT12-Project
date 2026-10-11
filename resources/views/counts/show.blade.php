@extends('layouts.app')

@section('title', 'Count ' . $count->number)

@section('content')
@php
    $isOwner = auth()->user()->isOwner();
    $differences = $items->filter(fn ($i) => abs($i->variance) >= 0.01);
@endphp
<a href="{{ route('counts.index') }}" class="mb-2.5 inline-flex items-center gap-1 text-sm font-medium text-ink-2 hover:text-brand hover:no-underline"><x-icon name="back" /> Physical Counts</a>
<div class="mb-[22px] flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold tracking-[-0.02em] max-md:text-[21px]">{{ $count->shift_label }} count, {{ $count->count_date->format('M j, Y') }}</h1>
        <p class="mt-1 text-ink-2">{{ $count->number }} · Counted by {{ $count->counter?->name ?? '—' }}</p>
    </div>
</div>

@if ($differences->isEmpty())
    <div class="alert alert-success"><div class="grow">Everything matched the system. Nothing to correct.</div></div>
@elseif ($count->adjustment)
    <div class="alert alert-info"><div class="grow">Corrected by {{ $count->adjustment->adjuster?->name ?? 'the Owner' }} on {{ $count->adjustment->adjusted_date->format('M j, Y') }} ({{ $count->adjustment->number }}). Stock now matches this count.</div></div>
@elseif ($newer)
    <div class="alert alert-info"><div class="grow">{{ $differences->count() }} {{ Str::plural('ingredient', $differences->count()) }} didn't match, but a newer count (<a href="{{ route('counts.show', $newer) }}">{{ $newer->number }}</a>) was saved after this one. Only the newest count can be applied, so this one is kept for the record.</div></div>
@elseif ($isOwner)
    <div class="alert alert-warning">
        <div class="grow">{{ $differences->count() }} {{ Str::plural('ingredient', $differences->count()) }} didn't match. Check them, then apply the count to correct stock.</div>
        <form method="POST" action="{{ route('counts.apply', $count) }}" class="inline" data-confirm="Correct stock for {{ $differences->count() }} {{ Str::plural('ingredient', $differences->count()) }} to match this count? This records a count correction adjustment." data-confirm-button="Apply corrections">
            @csrf
            <button type="submit" class="btn btn-primary btn-sm">Apply corrections</button>
        </form>
    </div>
@else
    <div class="alert alert-info"><div class="grow">Saved. {{ $differences->count() }} {{ Str::plural('ingredient', $differences->count()) }} didn't match the system. The Owner will review them.</div></div>
@endif

<div class="card">
    <div class="overflow-x-auto">
        <table class="table rwd">
            <thead>
                <tr>
                    <th>Ingredient</th>
                    @if ($isOwner)<th class="num">System</th>@endif
                    <th class="num">Counted</th>
                    @if ($isOwner)<th class="num">Difference</th>@endif
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    @php $variance = $item->variance; @endphp
                    <tr>
                        <td class="primary">{{ $item->ingredient->name }}</td>
                        @if ($isOwner)<td class="num text-ink-2" data-label="System">@qty($item->system_qty) {{ $item->ingredient->unit }}</td>@endif
                        <td class="num" data-label="Counted">@qty($item->counted_qty) {{ $item->ingredient->unit }}</td>
                        @if ($isOwner)
                            <td class="num font-semibold {{ abs($variance) < 0.01 ? 'text-ink-3' : ($variance < 0 ? 'text-danger' : 'text-success') }}">
                                {{ abs($variance) < 0.01 ? 'Matched' : ($variance > 0 ? '+' : '−') . rtrim(rtrim(number_format(abs($variance), 2), '0'), '.') . ' ' . $item->ingredient->unit }}
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if ($count->notes)<div class="card-foot"><span class="font-semibold">Notes:</span> {{ $count->notes }}</div>@endif
</div>
@endsection
