@extends('layouts.app')

@section('title', 'Stock Levels')

@section('content')
@include('partials.inventory-subnav')
<div class="mb-[22px] flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold tracking-[-0.02em] max-md:text-[21px]">Stock Levels</h1>
        <p class="mt-1 text-ink-2">Stock on hand is calculated from stock in, ingredients used and adjustments.</p>
    </div>
    <div class="flex flex-wrap gap-2 print:hidden">
        @owner
            <button type="button" class="btn" data-modal-open="createIngredient"><x-icon name="plus" /> Add ingredient</button>
            <a href="{{ route('stockIns.create') }}" class="btn btn-primary"><x-icon name="stock-in" /> Record stock in</a>
        @else
            <a href="{{ route('counts.create') }}" class="btn btn-primary"><x-icon name="count" /> Count stock</a>
        @endowner
    </div>
</div>

<div class="card">
    <nav class="tabs">
        <a href="{{ route('inventory.index', array_filter(['search' => $search])) }}" class="tab {{ $filter === null ? 'active' : '' }}">Active <span class="n">{{ $stats['active'] }}</span></a>
        <a href="{{ route('inventory.index', array_filter(['filter' => 'low', 'search' => $search])) }}" class="tab {{ $filter === 'low' ? 'active' : '' }}">Low stock <span class="n">{{ $stats['low'] }}</span></a>
        <a href="{{ route('inventory.index', array_filter(['filter' => 'inactive', 'search' => $search])) }}" class="tab {{ $filter === 'inactive' ? 'active' : '' }}">Inactive <span class="n">{{ $stats['inactive'] }}</span></a>
    </nav>
    <form method="GET" class="flex flex-wrap items-center gap-2 border-b border-line bg-surface px-5 py-3 max-md:px-4 print:hidden">
        @if ($filter)<input type="hidden" name="filter" value="{{ $filter }}">@endif
        <x-search name="search" value="{{ $search }}" placeholder="Search ingredient" class="min-w-[200px] flex-1" />
        <button type="submit" class="btn">Search</button>
        @if ($search)<a href="{{ route('inventory.index', array_filter(['filter' => $filter])) }}" class="btn btn-ghost">Clear</a>@endif
    </form>

    @if ($ingredients->isEmpty())
        <div class="empty">
            <strong>{{ $search ? 'No ingredients match your search' : ($filter === 'low' ? 'Nothing is low on stock' : 'No ingredients here') }}</strong>
            @owner {{ $filter === null && ! $search ? 'Press "Add ingredient", then "Record stock in" to start tracking stock.' : '' }} @endowner
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="table rwd">
                <thead><tr><th>Ingredient</th><th class="num">On hand</th><th class="num max-md:hidden">Reorder at</th><th>Status</th>@owner<th class="actions"></th>@endowner</tr></thead>
                <tbody>
                    @foreach ($ingredients as $ingredient)
                        @php $low = $ingredient->stock_on_hand <= $ingredient->reorder_level; @endphp
                        <tr class="{{ $ingredient->is_active ? '' : 'row-muted' }}">
                            <td class="primary"><a href="{{ route('inventory.show', $ingredient) }}" class="row-link">{{ $ingredient->name }}</a></td>
                            <td class="num {{ $low && $ingredient->is_active ? 'text-danger font-semibold' : '' }}">@qty($ingredient->stock_on_hand) {{ $ingredient->unit }}</td>
                            <td class="num max-md:hidden">@qty($ingredient->reorder_level) {{ $ingredient->unit }}</td>
                            <td>
                                @if (! $ingredient->is_active)
                                    <x-status :status="false" type="active" />
                                @else
                                    <x-status :status="$low ? 'low' : 'ok'" type="stock" />
                                @endif
                            </td>
                            @owner
                                <td class="actions">
                                    <button type="button" class="btn btn-sm" data-modal-open="editIngredient"
                                        data-action="{{ route('inventory.update', $ingredient) }}"
                                        data-title="Edit {{ $ingredient->name }}"
                                        data-fill="{{ json_encode(['name' => $ingredient->name, 'unit' => $ingredient->unit, 'reorder_level' => rtrim(rtrim(number_format($ingredient->reorder_level, 2, '.', ''), '0'), '.'), 'is_active' => $ingredient->is_active ? '1' : '0', '_edit_id' => $ingredient->id]) }}">Edit</button>
                                </td>
                            @endowner
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $ingredients->links() }}
    @endif
</div>

@owner
    <x-modal id="createIngredient" title="Add ingredient" :open="$errors->createIngredient->any()">
        <form method="POST" action="{{ route('inventory.store') }}">
            @csrf
            <div class="modal-body">
                @include('inventory._fields', ['bag' => 'createIngredient', 'p' => 'ci_', 'v' => $errors->createIngredient->any() ? old() : []])
                <p class="hint">New ingredients start at 0. Record a stock in to add the first quantity.</p>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Save ingredient</button>
            </div>
        </form>
    </x-modal>

    <x-modal id="editIngredient" title="Edit ingredient" :open="$errors->editIngredient->any()">
        <form method="POST" action="{{ old('_edit_id') ? route('inventory.update', old('_edit_id')) : '#' }}">
            @csrf @method('PUT')
            <input type="hidden" name="_edit_id" value="{{ old('_edit_id') }}">
            <div class="modal-body">
                @include('inventory._fields', ['bag' => 'editIngredient', 'p' => 'ei_', 'withStatus' => true, 'v' => $errors->editIngredient->any() ? old() : []])
                <p class="hint">To change the quantity, use Stock in or Adjustments so there is a record of why.</p>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Save changes</button>
            </div>
        </form>
    </x-modal>
@endowner
@endsection
