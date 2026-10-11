@extends('layouts.app')

@section('title', $ingredient->name)

@section('content')
@php $low = $ingredient->is_active && $ingredient->isLowStock(); @endphp
<a href="{{ route('inventory.index') }}" class="mb-2.5 inline-flex items-center gap-1 text-sm font-medium text-ink-2 hover:text-brand hover:no-underline"><x-icon name="back" /> Stock Levels</a>

<div class="mb-[22px] flex flex-wrap items-end justify-between gap-4">
    <div>
        <div class="flex flex-wrap items-center gap-2.5">
            <h1 class="text-2xl font-bold tracking-[-0.02em] max-md:text-[21px]">{{ $ingredient->name }}</h1>
            @if (! $ingredient->is_active)
                <x-status :status="false" type="active" />
            @else
                <x-status :status="$low ? 'low' : 'ok'" type="stock" />
            @endif
        </div>
        <p class="mt-1 text-ink-2">Measured in {{ $ingredient->unit }}</p>
    </div>
    @owner
        @if ($ingredient->is_active)
            <div class="flex flex-wrap gap-2 print:hidden">
                <a href="{{ route('adjustments.create', ['ingredient_id' => $ingredient->id]) }}" class="btn"><x-icon name="adjust" /> Adjust</a>
                <a href="{{ route('stockIns.create', ['ingredient_id' => $ingredient->id]) }}" class="btn btn-primary"><x-icon name="stock-in" /> Stock in</a>
            </div>
        @endif
    @endowner
</div>

<div class="mb-5 grid grid-cols-[repeat(2,minmax(0,260px))] gap-4 max-md:grid-cols-2">
    <x-stat label="On hand" :value="rtrim(rtrim(number_format($ingredient->stock_on_hand, 2), '0'), '.') . ' ' . $ingredient->unit" icon="stock" :tone="$low ? 'danger' : 'success'" :alert="$low" />
    <x-stat label="Reorder level" :value="rtrim(rtrim(number_format($ingredient->reorder_level, 2), '0'), '.') . ' ' . $ingredient->unit" icon="alert" tone="gold" />
</div>

<div class="card">
    <div class="card-head"><h2 class="card-title">Stock history</h2><span class="card-sub">Newest first</span></div>
    @if ($movements->isEmpty())
        <div class="empty"><strong>No movements yet</strong>Stock in, ingredients used and adjustments will be listed here.</div>
    @else
        <div class="overflow-x-auto">
            <table class="table rwd">
                <thead><tr><th>Date</th><th>Type</th><th>Reference</th><th class="max-md:hidden">By</th><th class="num">Change</th></tr></thead>
                <tbody>
                    @foreach ($movements as $m)
                        @include('inventory._movement-row', ['m' => $m])
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $movements->links() }}
    @endif
</div>
@endsection
