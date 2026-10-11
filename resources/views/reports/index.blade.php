@extends('layouts.app')

@section('title', 'Reports')

@section('content')
<div class="mb-[22px] flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold tracking-[-0.02em] max-md:text-[21px]">Reports</h1>
        <p class="mt-1 text-ink-2">This month so far, {{ now()->startOfMonth()->format('M j') }} to {{ today()->format('M j, Y') }}</p>
    </div>
</div>

<div class="mb-5 grid grid-cols-4 gap-4 max-xl:grid-cols-2">
    <x-stat :href="route('reports.sales')" label="Sales booked" :value="'₱' . number_format($kpis['sales'], 2)" icon="chart" tone="brand"
        :note="$kpis['orders'] . ' ' . Str::plural('order', $kpis['orders']) . ', cancelled not counted'" />
    <x-stat :href="route('reports.collections')" label="Money collected" :value="'₱' . number_format($kpis['collected'], 2)" icon="cash" tone="success" note="Payments received this month" />
    <x-stat :href="route('reports.jobOrders')" label="Job orders done" :value="$kpis['job_orders_done']" icon="whisk" tone="violet" note="Finished this month" />
    <x-stat :href="route('reports.inventory')" label="Low stock" :value="$kpis['low_stock']" icon="stock" tone="info" note="Ingredients to reorder" :alert="$kpis['low_stock'] > 0" />
</div>

<div class="grid gap-4 md:grid-cols-2">
    <a href="{{ route('reports.sales') }}" class="flex items-start gap-3.5 rounded-lg border border-line bg-surface px-5 py-[18px] text-ink shadow-card transition-[transform,border-color] duration-150 hover:-translate-y-0.5 hover:border-brand hover:no-underline">
        <span class="mt-0.5 grid size-[42px] shrink-0 place-items-center rounded-[12px] [&_.icon]:size-[21px] bg-brand-soft text-brand"><x-icon name="chart" /></span>
        <span><strong class="mb-[3px] block text-md">Sales</strong><span class="text-sm text-ink-2">Orders booked in a period, by category and top products, with what is still unpaid.</span></span>
    </a>
    <a href="{{ route('reports.collections') }}" class="flex items-start gap-3.5 rounded-lg border border-line bg-surface px-5 py-[18px] text-ink shadow-card transition-[transform,border-color] duration-150 hover:-translate-y-0.5 hover:border-brand hover:no-underline">
        <span class="mt-0.5 grid size-[42px] shrink-0 place-items-center rounded-[12px] [&_.icon]:size-[21px] bg-success-soft text-success"><x-icon name="cash" /></span>
        <span><strong class="mb-[3px] block text-md">Collections</strong><span class="text-sm text-ink-2">Money actually received, by payment method and payment type.</span></span>
    </a>
    <a href="{{ route('reports.jobOrders') }}" class="flex items-start gap-3.5 rounded-lg border border-line bg-surface px-5 py-[18px] text-ink shadow-card transition-[transform,border-color] duration-150 hover:-translate-y-0.5 hover:border-brand hover:no-underline">
        <span class="mt-0.5 grid size-[42px] shrink-0 place-items-center rounded-[12px] [&_.icon]:size-[21px] bg-violet-soft text-violet"><x-icon name="whisk" /></span>
        <span><strong class="mb-[3px] block text-md">Job orders</strong><span class="text-sm text-ink-2">Production in a period: finished, on time, still open and cancelled.</span></span>
    </a>
    <a href="{{ route('reports.inventory') }}" class="flex items-start gap-3.5 rounded-lg border border-line bg-surface px-5 py-[18px] text-ink shadow-card transition-[transform,border-color] duration-150 hover:-translate-y-0.5 hover:border-brand hover:no-underline">
        <span class="mt-0.5 grid size-[42px] shrink-0 place-items-center rounded-[12px] [&_.icon]:size-[21px] bg-info-soft text-info"><x-icon name="stock" /></span>
        <span><strong class="mb-[3px] block text-md">Inventory</strong><span class="text-sm text-ink-2">Stock levels now, ingredients used, purchases and every stock movement.</span></span>
    </a>
</div>
@endsection
