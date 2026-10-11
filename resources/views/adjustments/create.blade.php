@extends('layouts.app')

@section('title', 'New adjustment')

@section('content')
@php
    $formProps = [
        'ingredients' => $ingredients->map(fn ($i) => ['id' => $i->id, 'name' => $i->name, 'unit' => $i->unit, 'stock' => (float) $i->stock_on_hand])->values(),
        'reasons' => $reasons,
        'old' => $errors->any() ? old() : null,
        'errors' => collect($errors->toArray())->map(fn ($m) => $m[0])->all(),
        'preselect' => $preselect,
        'today' => today()->toDateString(),
    ];
@endphp
<a href="{{ route('adjustments.index') }}" class="mb-2.5 inline-flex items-center gap-1 text-sm font-medium text-ink-2 hover:text-brand hover:no-underline"><x-icon name="back" /> Adjustments</a>
<div class="mb-[22px] flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold tracking-[-0.02em] max-md:text-[21px]">New adjustment</h1>
        <p class="mt-1 text-ink-2">For stock that spoiled, expired or got damaged, or to fix a wrong entry. Count differences are applied from Physical Counts.</p>
    </div>
</div>

<form method="POST" action="{{ route('adjustments.store') }}" id="adjustForm">
    @csrf
    <x-react name="AdjustmentForm" :props="$formProps" />
    <div class="mt-5 flex justify-end gap-2">
        <a href="{{ route('adjustments.index') }}" class="btn">Cancel</a>
        <button type="submit" class="btn btn-primary">Save adjustment</button>
    </div>
</form>
@endsection
