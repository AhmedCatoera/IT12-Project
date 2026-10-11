@extends('layouts.app')

@section('title', 'Products')

@section('content')
<div class="mb-[22px] flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold tracking-[-0.02em] max-md:text-[21px]">Products</h1>
        <p class="mt-1 text-ink-2">The price list used when taking orders.</p>
    </div>
    <div class="flex flex-wrap gap-2 print:hidden">
        <button type="button" class="btn btn-primary" data-modal-open="createProduct"><x-icon name="plus" /> Add product</button>
    </div>
</div>

<div class="card">
    <nav class="tabs">
        <a href="{{ route('products.index') }}" class="tab {{ $category ? '' : 'active' }}">All</a>
        @foreach (\App\Models\Product::CATEGORIES as $key => $label)
            <a href="{{ route('products.index', ['category' => $key]) }}" class="tab {{ $category === $key ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </nav>

    @if ($products->isEmpty())
        <div class="empty"><strong>No products yet</strong>Press "Add product" to build the price list of cakes, pastries and souvenirs.</div>
    @else
        <div class="overflow-x-auto">
            <table class="table rwd">
                <thead>
                    <tr><th>Product</th><th>Category</th><th class="num">Base price</th><th class="num max-md:hidden">Times ordered</th><th>Status</th><th class="actions"></th></tr>
                </thead>
                <tbody>
                    @foreach ($products as $product)
                        <tr class="{{ $product->is_active ? '' : 'row-muted' }}">
                            <td class="font-semibold primary">{{ $product->name }}</td>
                            <td>{{ $product->category_label }}</td>
                            <td class="num">@peso($product->base_price)</td>
                            <td class="num max-md:hidden">{{ $product->order_items_count }}</td>
                            <td><x-status :status="$product->is_active" type="active" /></td>
                            <td class="actions">
                                <button type="button" class="btn btn-sm" data-modal-open="editProduct"
                                    data-action="{{ route('products.update', $product) }}"
                                    data-title="Edit {{ $product->name }}"
                                    data-fill="{{ json_encode(['name' => $product->name, 'category' => $product->category, 'base_price' => number_format($product->base_price, 2, '.', ''), 'is_active' => $product->is_active ? '1' : '0', '_edit_id' => $product->id]) }}">Edit</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $products->links() }}
    @endif
</div>

<x-modal id="createProduct" title="Add product" :open="$errors->createProduct->any()">
    <form method="POST" action="{{ route('products.store') }}">
        @csrf
        <div class="modal-body">
            @include('products._fields', ['bag' => 'createProduct', 'p' => 'cp_', 'v' => $errors->createProduct->any() ? old() : []])
        </div>
        <div class="modal-foot">
            <button type="button" class="btn" data-modal-close>Cancel</button>
            <button type="submit" class="btn btn-primary">Save product</button>
        </div>
    </form>
</x-modal>

<x-modal id="editProduct" title="Edit product" :open="$errors->editProduct->any()">
    <form method="POST" action="{{ old('_edit_id') ? route('products.update', old('_edit_id')) : '#' }}">
        @csrf @method('PUT')
        <input type="hidden" name="_edit_id" value="{{ old('_edit_id') }}">
        <div class="modal-body">
            @include('products._fields', ['bag' => 'editProduct', 'p' => 'ep_', 'withStatus' => true, 'v' => $errors->editProduct->any() ? old() : []])
            <p class="hint">Changing the price only affects new orders. Saved orders keep the price agreed with the customer.</p>
        </div>
        <div class="modal-foot">
            <button type="button" class="btn" data-modal-close>Cancel</button>
            <button type="submit" class="btn btn-primary">Save changes</button>
        </div>
    </form>
</x-modal>
@endsection
