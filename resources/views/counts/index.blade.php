@extends('layouts.app')

@section('title', 'Physical Counts')

@section('content')
@include('partials.inventory-subnav')
<div class="mb-[22px] flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold tracking-[-0.02em] max-md:text-[21px]">Physical Counts</h1>
        <p class="mt-1 text-ink-2">Staff count the shelf each shift. @owner Review the differences and apply corrections. @else The Owner reviews any differences. @endowner</p>
    </div>
    <div class="flex flex-wrap gap-2 print:hidden">
        <a href="{{ route('counts.create') }}" class="btn btn-primary"><x-icon name="count" /> Count stock</a>
    </div>
</div>

<div class="card">
    @if ($counts->isEmpty())
        <div class="empty"><strong>No counts yet</strong>Press "Count stock" above to do the first shelf count.</div>
    @else
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Count</th><th>Counted by</th><th>Result</th></tr></thead>
                <tbody>
                    @foreach ($counts as $count)
                        <tr>
                            <td>
                                <a href="{{ route('counts.show', $count) }}" class="row-link">{{ $count->count_date->format('D, M j, Y') }} · {{ $count->shift_label }}</a>
                                <span class="cell-sub">{{ $count->number }}</span>
                            </td>
                            <td>{{ $count->counter?->name ?? '—' }}</td>
                            <td>
                                @if ($count->mismatch_count === 0)
                                    <span class="badge b-neutral">All matched</span>
                                @elseif ($count->adjustment)
                                    <span class="badge b-success">Corrected</span>
                                    <span class="cell-sub">{{ $count->mismatch_count }} {{ Str::plural('difference', $count->mismatch_count) }}</span>
                                @elseif ($count->id !== $latestId)
                                    <span class="badge b-neutral">Replaced</span>
                                    <span class="cell-sub">A newer count was saved</span>
                                @else
                                    <span class="badge b-warning">Needs review</span>
                                    <span class="cell-sub">{{ $count->mismatch_count }} {{ Str::plural('difference', $count->mismatch_count) }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $counts->links() }}
    @endif
</div>
@endsection
