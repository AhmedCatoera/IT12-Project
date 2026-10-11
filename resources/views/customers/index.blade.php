@extends('layouts.app')

@section('title', 'Customers')

@section('content')
<div class="mb-[22px] flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold tracking-[-0.02em] max-md:text-[21px]">Customers</h1>
        <p class="mt-1 text-ink-2">Customers are never deleted. Deactivate them to hide them from new orders.</p>
    </div>
    <div class="flex flex-wrap gap-2 print:hidden">
        <button type="button" class="btn btn-primary" data-modal-open="createCustomer"><x-icon name="plus" /> Add customer</button>
    </div>
</div>

<div class="card">
    <nav class="tabs">
        @foreach (['active' => 'Active', 'inactive' => 'Deactivated', 'all' => 'All'] as $key => $label)
            <a href="{{ route('customers.index', ['status' => $key, 'search' => $search]) }}" class="tab {{ $status === $key ? 'active' : '' }}">
                {{ $label }}
                @if ($key !== 'all')<span class="n">{{ $counts[$key] }}</span>@endif
            </a>
        @endforeach
    </nav>
    <form method="GET" class="flex flex-wrap items-center gap-2 border-b border-line bg-surface px-5 py-3 max-md:px-4 print:hidden">
        <input type="hidden" name="status" value="{{ $status }}">
        <x-search name="search" value="{{ $search }}" placeholder="Search name, number or Facebook name" class="min-w-[200px] flex-1" />
        <button type="submit" class="btn">Search</button>
        @if ($search)
            <a href="{{ route('customers.index', ['status' => $status]) }}" class="btn btn-ghost">Clear</a>
        @endif
    </form>

    @if ($customers->isEmpty())
        <div class="empty">
            <strong>{{ $search ? 'No customers match your search' : 'No customers here yet' }}</strong>
            {{ $search ? 'Try a different name or number.' : 'Press "Add customer" above to save your first one.' }}
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="table rwd">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Contact number</th>
                        <th class="max-md:hidden">Address</th>
                        <th class="num">Orders</th>
                        @if ($status === 'all')<th>Status</th>@endif
                        <th class="actions"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($customers as $customer)
                        <tr class="{{ $customer->is_active ? '' : 'row-muted' }}">
                            <td class="primary">
                                <a href="{{ route('customers.show', $customer) }}" class="row-link">{{ $customer->name }}</a>
                                @if ($customer->facebook_name)<span class="cell-sub">FB: {{ $customer->facebook_name }}</span>@endif
                            </td>
                            <td class="whitespace-nowrap">{{ $customer->contact_display }}</td>
                            <td class="max-md:hidden">
                                @if ($customer->short_address)
                                    {{ $customer->short_address }}@if ($customer->has_pin) <x-icon name="pin" class="inline-block size-3.5 align-[-2px] text-brand" title="Pinned on the map" />@endif
                                @else
                                    <span class="text-ink-3">—</span>
                                @endif
                            </td>
                            <td class="num" data-label="Orders">{{ $customer->orders_count }}</td>
                            @if ($status === 'all')<td><x-status :status="$customer->is_active" type="active" /></td>@endif
                            <td class="actions">
                                <button type="button" class="btn btn-sm" data-modal-open="editCustomer"
                                    data-action="{{ route('customers.update', $customer) }}"
                                    data-title="Edit {{ $customer->name }}"
                                    data-fill="{{ json_encode($customer->only(['first_name', 'middle_name', 'last_name', 'contact_number', 'facebook_name', 'street', 'address_line2', 'barangay', 'postal_code', 'latitude', 'longitude']) + [
                                        'city' => $customer->city ?? \App\Support\Address::DEFAULT_CITY,
                                        'province' => $customer->province ?? \App\Support\Address::DEFAULT_PROVINCE,
                                        '_edit_id' => $customer->id,
                                    ]) }}">Edit</button>
                                <form method="POST" action="{{ route('customers.toggleStatus', $customer) }}" class="inline"
                                    @if ($customer->is_active)
                                        data-confirm="Deactivate {{ $customer->name }}? Their past orders stay on record, but they won't be listed when creating new orders." data-confirm-button="Deactivate"
                                    @endif>
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-sm {{ $customer->is_active ? 'btn-danger' : 'btn-success' }}">{{ $customer->is_active ? 'Deactivate' : 'Activate' }}</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $customers->links() }}
    @endif
</div>

@include('customers._create-modal')

<x-modal id="editCustomer" title="Edit customer" wide :open="$errors->editCustomer->any()">
    <form method="POST" action="{{ old('_edit_id') ? route('customers.update', old('_edit_id')) : '#' }}">
        @csrf @method('PUT')
        <input type="hidden" name="_edit_id" value="{{ old('_edit_id') }}">
        <div class="modal-body">
            @include('customers._fields', ['bag' => 'editCustomer', 'p' => 'ec_', 'v' => $errors->editCustomer->any() ? old() : []])
        </div>
        <div class="modal-foot">
            <button type="button" class="btn" data-modal-close>Cancel</button>
            <button type="submit" class="btn btn-primary">Save changes</button>
        </div>
    </form>
</x-modal>
@endsection
