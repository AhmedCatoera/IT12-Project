@extends('layouts.app')

@section('title', $stockIn->number)

@section('content')
<a href="{{ route('stockIns.index') }}" class="mb-2.5 inline-flex items-center gap-1 text-sm font-medium text-ink-2 hover:text-brand hover:no-underline print:hidden"><x-icon name="back" /> Stock In</a>
<div class="mb-[22px] flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold tracking-[-0.02em] max-md:text-[21px]">Stock in {{ $stockIn->number }}</h1>
        <p class="mt-1 text-ink-2">{{ $stockIn->receipt_no ? 'Receipt ' . $stockIn->receipt_no . ' · ' : '' }}{{ $stockIn->supplier_name ? $stockIn->supplier_name . ' · ' : '' }}{{ $stockIn->received_date->format('M j, Y') }}</p>
    </div>
    <div class="flex flex-wrap gap-2 print:hidden">
        <button type="button" class="btn btn-ghost" onclick="window.print()"><x-icon name="print" /> Print</button>
        <a href="{{ route('stockIns.create') }}" class="btn btn-primary"><x-icon name="plus" /> New stock in</a>
    </div>
</div>

<div class="grid items-start gap-5 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
    <div class="card">
        <div class="card-head"><h2 class="card-title">Ingredients received</h2><span class="card-sub">{{ $stockIn->items->count() }} {{ Str::plural('ingredient', $stockIn->items->count()) }}</span></div>
        <div class="overflow-x-auto">
            <table class="table rwd">
                <thead><tr><th>Ingredient</th><th class="num">Quantity</th><th class="num">Cost per unit</th><th class="max-md:hidden">Expiry</th><th class="num">Cost</th></tr></thead>
                <tbody>
                    @foreach ($stockIn->items as $item)
                        <tr>
                            <td class="primary"><a href="{{ route('inventory.show', $item->ingredient) }}" class="row-link">{{ $item->ingredient->name }}</a></td>
                            <td class="num">@qty($item->quantity) {{ $item->ingredient->unit }}</td>
                            <td class="num" data-label="Each">{{ $item->unit_cost !== null ? '₱' . number_format($item->unit_cost, 2) : '—' }}</td>
                            <td class="max-md:hidden">{{ $item->expiration_date?->format('M j, Y') ?? '—' }}</td>
                            <td class="num">{{ $item->unit_cost !== null ? '₱' . number_format($item->line_cost, 2) : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot><tr><td colspan="4">Total cost</td><td class="num">@peso($stockIn->total_cost)</td></tr></tfoot>
            </table>
        </div>
    </div>
    <div class="card">
        <div class="card-head"><h2 class="card-title">Details</h2></div>
        <div class="card-body">
            <dl class="kv">
                <dt>Receipt no.</dt><dd>{{ $stockIn->receipt_no ?: '—' }}</dd>
                <dt>Supplier</dt><dd>{{ $stockIn->supplier_name ?: '—' }}</dd>
                <dt>Received</dt><dd>{{ $stockIn->received_date->format('M j, Y') }}</dd>
                <dt>Recorded by</dt><dd>{{ $stockIn->receiver?->name ?? '—' }}</dd>
                <dt>Recorded on</dt><dd>{{ $stockIn->created_at->format('M j, Y g:i A') }}</dd>
                @if ($stockIn->remarks)<dt>Remarks</dt><dd>{{ $stockIn->remarks }}</dd>@endif
            </dl>
            <p class="hint mt-3.5">Found a mistake? Record an adjustment with the reason "Entry correction".</p>
        </div>
    </div>
</div>
@endsection
