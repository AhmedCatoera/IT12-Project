@extends('layouts.app')

@section('title', 'Stock In')

@section('content')
@include('partials.inventory-subnav')
<div class="mb-[22px] flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold tracking-[-0.02em] max-md:text-[21px]">Stock In</h1>
        <p class="mt-1 text-ink-2">Ingredients received. One receipt can list many ingredients.</p>
    </div>
    <div class="flex flex-wrap gap-2 print:hidden">
        <a href="{{ route('stockIns.create') }}" class="btn btn-primary"><x-icon name="plus" /> New stock in</a>
    </div>
</div>

<div class="card">
    <form method="GET" class="flex flex-wrap items-center gap-2 border-b border-line bg-surface px-5 py-3 max-md:px-4 print:hidden">
        <x-search name="search" value="{{ $search }}" placeholder="Search receipt number or supplier" class="min-w-[200px] flex-1" />
        <button type="submit" class="btn">Search</button>
        @if ($search)<a href="{{ route('stockIns.index') }}" class="btn btn-ghost">Clear</a>@endif
    </form>

    @if ($stockIns->isEmpty())
        <div class="empty">
            <strong>{{ $search ? 'No receipts match your search' : 'No stock in yet' }}</strong>
            @unless ($search)Press "New stock in" above to record your first receipt.@endunless
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="table rwd">
                <thead><tr><th>Stock in</th><th>Supplier</th><th>Received</th><th class="num">Ingredients</th><th class="num">Total cost</th><th class="max-md:hidden">Received by</th></tr></thead>
                <tbody>
                    @foreach ($stockIns as $stockIn)
                        <tr>
                            <td class="primary">
                                <a href="{{ route('stockIns.show', $stockIn) }}" class="row-link">{{ $stockIn->number }}</a>
                                <span class="cell-sub">{{ $stockIn->receipt_no ? 'Receipt ' . $stockIn->receipt_no : 'No receipt number' }}</span>
                            </td>
                            <td>{{ $stockIn->supplier_name ?: '—' }}</td>
                            <td class="whitespace-nowrap">{{ $stockIn->received_date->format('M j, Y') }}</td>
                            <td class="num" data-label="Items">{{ $stockIn->items_count }}</td>
                            <td class="num">{{ $stockIn->total_cost > 0 ? '₱' . number_format($stockIn->total_cost, 2) : '—' }}</td>
                            <td class="max-md:hidden">{{ $stockIn->receiver?->name ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $stockIns->links() }}
    @endif
</div>
@endsection
