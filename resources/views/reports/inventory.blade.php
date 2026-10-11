@extends('layouts.app')

@section('title', 'Inventory report')

@section('content')
@include('reports._head', ['title' => 'Inventory report'])

<div class="card">@include('reports._range')</div>

<div class="mb-5 grid grid-cols-4 gap-4 max-xl:grid-cols-2">
    <x-stat label="Purchases (stock in)" :value="'₱' . number_format($purchases, 2)" note="Cost entered on receipts" />
    <x-stat label="Ingredients used" :value="$usage->total()" note="Different ingredients logged" />
    <x-stat label="Low stock now" :value="$lowCount" :note="'Of ' . $levels->total() . ' active ingredients'" :alert="$lowCount > 0" />
    <x-stat label="Movements" :value="$movements->total()" :note="$type ? 'Of the selected type' : 'In, used and adjusted'" />
</div>

<div class="grid items-start gap-5 md:grid-cols-2">
    <div class="card" id="levels">
        <div class="card-head"><h2 class="card-title">Stock levels now</h2></div>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Ingredient</th><th class="num">On hand</th><th class="num">Reorder at</th></tr></thead>
                <tbody>
                    @foreach ($levels as $ingredient)
                        @php $low = $ingredient->stock_on_hand <= $ingredient->reorder_level; @endphp
                        <tr>
                            <td>{{ $ingredient->name }}@if ($low) <span class="badge b-danger plain">Low</span>@endif</td>
                            <td class="num {{ $low ? 'text-danger font-semibold' : '' }}">@qty($ingredient->stock_on_hand) {{ $ingredient->unit }}</td>
                            <td class="num text-ink-2">@qty($ingredient->reorder_level)</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $levels->links() }}
    </div>
    <div class="card" id="used">
        <div class="card-head"><h2 class="card-title">Used in this period</h2></div>
        @if ($usage->isEmpty())
            <div class="empty">No ingredients were logged as used.</div>
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Ingredient</th><th class="num">Used</th><th class="num">Job orders</th></tr></thead>
                    <tbody>
                        @foreach ($usage as $row)
                            <tr><td>{{ $row->name }}</td><td class="num">@qty($row->total) {{ $row->unit }}</td><td class="num">{{ $row->job_orders }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $usage->links() }}
        @endif
    </div>
</div>

<div class="card" id="movements">
    <div class="card-head"><h2 class="card-title">Stock movements</h2></div>
    <nav class="tabs print:hidden">
        @foreach ([null => 'All', 'stock_in' => 'Stock in', 'stock_out' => 'Used', 'adjustment' => 'Adjustments'] as $key => $label)
            {{-- Filtering the movements keeps the other two tables where they are. --}}
            <a href="{{ route('reports.inventory', array_filter(['from' => $from->toDateString(), 'to' => $to->toDateString(), 'type' => $key ?: null, 'levels' => $levels->currentPage() > 1 ? $levels->currentPage() : null, 'used' => $usage->currentPage() > 1 ? $usage->currentPage() : null])) }}#movements" class="tab {{ ($type ?? '') === $key ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </nav>
    @if ($movements->isEmpty())
        <div class="empty">No stock movements in this period.</div>
    @else
        <div class="overflow-x-auto">
            <table class="table rwd">
                <thead><tr><th>Date</th><th>Ingredient</th><th>Type</th><th>Reference</th><th class="max-md:hidden">By</th><th class="num">Change</th></tr></thead>
                <tbody>
                    @foreach ($movements as $m)
                        @include('inventory._movement-row', ['m' => $m, 'showIngredient' => true])
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $movements->links() }}
    @endif
</div>
@endsection
