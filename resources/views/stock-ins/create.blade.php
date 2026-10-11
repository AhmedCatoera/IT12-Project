@extends('layouts.app')

@section('title', 'New stock in')

@section('content')
@php
    $formProps = [
        'ingredients' => $ingredients->map(fn ($i) => ['id' => $i->id, 'name' => $i->name, 'unit' => $i->unit, 'stock' => (float) $i->stock_on_hand])->values(),
        'old' => $errors->any() ? old() : null,
        'errors' => collect($errors->toArray())->map(fn ($m) => $m[0])->all(),
        'preselect' => $preselect,
        'today' => today()->toDateString(),
    ];
@endphp
<a href="{{ route('stockIns.index') }}" class="mb-2.5 inline-flex items-center gap-1 text-sm font-medium text-ink-2 hover:text-brand hover:no-underline"><x-icon name="back" /> Stock In</a>
<div class="mb-[22px] flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold tracking-[-0.02em] max-md:text-[21px]">New stock in</h1>
        <p class="mt-1 text-ink-2">Enter one receipt with every ingredient on it. Saving adds them to stock.</p>
    </div>
</div>

<form method="POST" action="{{ route('stockIns.store') }}" id="stockInForm">
    @csrf
    <x-react name="StockInForm" :props="$formProps" />
    <div class="mt-5 flex justify-end gap-2">
        <a href="{{ route('stockIns.index') }}" class="btn">Cancel</a>
        <button type="submit" class="btn btn-primary">Save stock in</button>
    </div>
</form>
@endsection
