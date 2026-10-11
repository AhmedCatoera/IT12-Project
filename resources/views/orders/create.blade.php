@extends('layouts.app')

@section('title', 'New order')

@section('content')
@php
    use App\Support\Address;

    // Everything the React order form needs, in plain arrays.
    $formProps = [
        'customers' => $customers->map(fn ($c) => [
            'id' => $c->id,
            'name' => $c->name,
            'firstName' => $c->first_name,
            'contact' => $c->contact_display,
            'address' => $c->has_address ? $c->address_parts : null,
        ])->values(),
        'products' => $products->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'category' => $p->category,
            'categoryLabel' => \App\Models\Product::CATEGORIES[$p->category] ?? ucfirst($p->category),
            'price' => (float) $p->base_price,
        ])->values(),
        'old' => $errors->any() ? old() : null,
        'errors' => collect($errors->toArray())->map(fn ($m) => $m[0])->all(),
        'selectedCustomerId' => $selectedCustomerId,
        'cake' => \App\Models\OrderItem::specOptions(),
        'categories' => \App\Models\Product::CATEGORIES,
        'rush' => [
            'defaultPercent' => config('sweetnest.rush_fee_default'),
            'choices' => config('sweetnest.rush_fee_choices'),
            'withinDays' => config('sweetnest.rush_within_days'),
            'orderDate' => today()->toDateString(),
            'deliveryCity' => config('sweetnest.delivery_city'),
            'shopAddress' => \App\Models\Order::shopAddress(),
        ],
        'photoMaxKb' => \App\Models\OrderItem::photoMaxKb(),
        'photoMaxLabel' => \App\Models\OrderItem::photoMaxLabel(),
        'minDate' => today()->format('Y-m-d\TH:i'),
        'map' => Address::mapConfig(),
        'defaults' => Address::defaults(),
    ];
@endphp
<a href="{{ route('orders.index') }}" class="mb-2.5 inline-flex items-center gap-1 text-sm font-medium text-ink-2 hover:text-brand hover:no-underline"><x-icon name="back" /> Orders</a>
<div class="mb-[22px] flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold tracking-[-0.02em] max-md:text-[21px]">New order</h1>
        <p class="mt-1 text-ink-2">Saved as "Awaiting payment" until the 50% down payment is in (a rush order, due within a day, is paid in full).</p>
    </div>
</div>

@if ($errors->any() && old('items'))
    <div class="alert alert-info"><div class="grow">If you attached reference photos, please choose them again.</div></div>
@endif

<form method="POST" action="{{ route('orders.store') }}" enctype="multipart/form-data" id="orderForm">
    @csrf
    <x-react name="OrderForm" :props="$formProps" />
</form>

@include('customers._create-modal', ['returnToOrder' => true])
@endsection
