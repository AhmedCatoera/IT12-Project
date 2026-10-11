@extends('layouts.app')

@section('title', $jobOrder->number)

@section('content')
@php
    $order = $jobOrder->order;
    $canLog = $jobOrder->canLogUsage();
    $isOwner = auth()->user()->isOwner();
    $lines = $jobOrder->stockOuts->sortBy('out_date')->flatMap(fn ($so) => $so->items->map(fn ($item) => ['doc' => $so, 'item' => $item]));
    $usageFormFailed = $errors->any() && old('out_date') !== null;
@endphp

<a href="{{ route('jobOrders.index') }}" class="mb-2.5 inline-flex items-center gap-1 text-sm font-medium text-ink-2 hover:text-brand hover:no-underline print:hidden"><x-icon name="back" /> Job Orders</a>
<div class="hidden print:block"><strong>SweetNest</strong> · Job order sheet printed {{ now()->format('M j, Y g:i A') }}</div>

<div class="mb-[22px] flex flex-wrap items-end justify-between gap-4">
    <div>
        <div class="flex flex-wrap items-center gap-2.5">
            <h1 class="text-2xl font-bold tracking-[-0.02em] max-md:text-[21px]">Job order {{ $jobOrder->number }}</h1>
            <x-status :status="$jobOrder->status" type="job" />
        </div>
        <p class="mt-1 text-ink-2">
            For <a href="{{ route('orders.show', $order) }}">{{ $order->order_number }}</a> · {{ $order->customer->name }} · Due {{ $order->due_label }}
        </p>
    </div>
    <div class="flex flex-wrap gap-2 print:hidden">
        <button type="button" class="btn btn-ghost" onclick="window.print()"><x-icon name="print" /> Print</button>
        @foreach ($actions as $action => $label)
            <form method="POST" action="{{ route('jobOrders.transition', $jobOrder) }}" class="inline"
                @if ($action === 'cancel') data-confirm="Cancel {{ $jobOrder->number }}? The order goes back to &quot;Confirmed&quot;. Ingredients already logged stay deducted." data-confirm-button="Cancel job order" @endif
                @if ($action === 'complete') data-confirm="Mark {{ $jobOrder->number }} as done? The order becomes &quot;Ready for release&quot;, and the ingredients used can then be logged." data-confirm-button="Mark as done" @endif>
                @csrf @method('PATCH')
                <input type="hidden" name="action" value="{{ $action }}">
                <button type="submit" class="btn {{ $action === 'cancel' ? 'btn-danger' : 'btn-primary' }}">{{ $label }}</button>
            </form>
        @endforeach
    </div>
</div>

@if ($jobOrder->status === 'cancelled')
    <div class="alert alert-warning print:hidden">
        <div class="grow">This job order was cancelled. @if (isset($actions['reopen'])) Reopen it when the kitchen is ready to make the order. @endif</div>
    </div>
@endif

<div class="grid items-start gap-5 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
    <div>
        <div class="card">
            <div class="card-head"><h2 class="card-title">What to make</h2></div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Item</th><th class="num">Qty</th></tr></thead>
                    <tbody>
                        @foreach ($order->items as $item)
                            <tr>
                                <td>
                                    <span class="font-semibold">{{ $item->name }}</span>
                                    @if ($item->isCustom())<span class="cell-sub">Custom item · {{ $item->category_label }}</span>@endif
                                    @foreach ($item->specs as $label => $value)<span class="cell-sub">{{ $label }}: {{ $value }}</span>@endforeach
                                    @if ($item->inscription)<span class="cell-sub">Message: "{{ $item->inscription }}"</span>@endif
                                    @if ($item->reference_image_url)
                                        <div class="flex flex-wrap gap-2.5 [&_img]:size-[120px] [&_img]:rounded-[10px] [&_img]:border [&_img]:border-line [&_img]:object-cover mt-2.5"><a href="{{ $item->reference_image_url }}" target="_blank" rel="noopener"><img src="{{ $item->reference_image_url }}" alt="Reference photo for {{ $item->name }}"></a></div>
                                    @endif
                                </td>
                                <td class="num font-semibold">{{ $item->quantity }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-head">
                <h2 class="card-title">Ingredients used</h2>
                @if ($canLog)
                    <button type="button" class="btn btn-sm btn-primary print:hidden" data-modal-open="logUsage"><x-icon name="plus" /> Log ingredients used</button>
                @endif
            </div>
            @if ($lines->isEmpty())
                <div class="empty">
                    <strong>Nothing logged yet</strong>
                    {{ $canLog ? 'The job order is done: log what the kitchen used so stock stays accurate.' : ($jobOrder->status === 'cancelled' ? 'Nothing was logged on this cancelled job order.' : 'Ingredients are logged once the job order is marked as done.') }}
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="table rwd">
                        <thead><tr><th>Ingredient</th><th class="num">Amount</th><th class="max-md:hidden">Logged</th>@if ($isOwner)<th class="actions print:hidden"></th>@endif</tr></thead>
                        <tbody>
                            @foreach ($lines as $line)
                                @php $item = $line['item']; $doc = $line['doc']; @endphp
                                <tr>
                                    <td class="primary">{{ $item->ingredient->name }}@if ($doc->remarks)<span class="cell-sub">{{ $doc->remarks }}</span>@endif</td>
                                    <td class="num">@qty($item->quantity) {{ $item->ingredient->unit }}</td>
                                    <td class="max-md:hidden">{{ $doc->out_date->format('M j') }} by {{ $doc->recorder?->first_name ?? '—' }}</td>
                                    @if ($isOwner)
                                        <td class="actions print:hidden">
                                            <button type="button" class="btn btn-sm" data-modal-open="editUsage"
                                                data-action="{{ route('stockOuts.updateItem', $item) }}"
                                                data-title="Edit amount of {{ $item->ingredient->name }}"
                                                data-fill="{{ json_encode(['quantity' => rtrim(rtrim(number_format($item->quantity, 2, '.', ''), '0'), '.'), '_edit_id' => $item->id]) }}">Edit</button>
                                            <form method="POST" action="{{ route('stockOuts.destroyItem', $item) }}" class="inline" data-confirm="Remove {{ $item->ingredient->name }} from this job order? The amount goes back into stock." data-confirm-button="Remove">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-ghost">Remove</button>
                                            </form>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($usage->count() < $lines->count())
                    <div class="card-foot">
                        <div class="text-xs font-semibold mb-1.5">Total per ingredient</div>
                        <div class="text-xs text-ink-2">
                            @foreach ($usage as $u){{ $u->name }}: @qty($u->total) {{ $u->unit }}@if (! $loop->last) · @endif @endforeach
                        </div>
                    </div>
                @endif
            @endif
            @unless ($isOwner)
                @if ($lines->isNotEmpty())<div class="card-foot text-xs text-ink-2 print:hidden">Logged a wrong amount? Ask the Owner to fix it.</div>@endif
            @endunless
        </div>
    </div>

    <div>
        <div class="card">
            <div class="card-head"><h2 class="card-title">Details</h2></div>
            <div class="card-body">
                <dl class="kv">
                    <dt>Order</dt><dd><a href="{{ route('orders.show', $order) }}">{{ $order->order_number }}</a> <x-status :status="$order->status" /></dd>
                    <dt>Customer</dt><dd>{{ $order->customer->name }}</dd>
                    @if ($order->isRush())<dt>Type</dt><dd><span class="badge b-orange">Rush order</span></dd>@endif
                    <dt>{{ $order->order_type === 'delivery' ? 'Deliver on' : 'Pickup on' }}</dt><dd>{{ $order->scheduled_at->format('D, M j, g:i A') }}</dd>
                    @if ($order->order_type === 'delivery')
                        <dt>Deliver to</dt><dd>{{ $order->place_address ?: '—' }}</dd>
                    @elseif (! $order->picked_up_at_shop)
                        <dt>Pick up at</dt><dd>{{ $order->place_address }}<span class="cell-sub">Meet-up spot, not the shop.</span></dd>
                    @endif
                    <dt>Production date</dt><dd>{{ $jobOrder->production_date->format('D, M j, Y') }}</dd>
                    <dt>Assigned to</dt><dd>{{ $jobOrder->assignee?->name ?? 'Not assigned' }}</dd>
                    @if ($jobOrder->started_at)<dt>Started</dt><dd>{{ $jobOrder->started_at->format('M j, g:i A') }}</dd>@endif
                    @if ($jobOrder->completed_at)<dt>Finished</dt><dd>{{ $jobOrder->completed_at->format('M j, g:i A') }}</dd>@endif
                    @if ($jobOrder->notes)<dt>Kitchen notes</dt><dd>{{ $jobOrder->notes }}</dd>@endif
                    @if ($order->notes)<dt>Order notes</dt><dd>{{ $order->notes }}</dd>@endif
                </dl>
            </div>
        </div>
    </div>
</div>

@if ($canLog)
    <x-modal id="logUsage" title="Log ingredients used" wide :open="$usageFormFailed">
        <form method="POST" action="{{ route('stockOuts.store', $jobOrder) }}">
            @csrf
            <div class="modal-body">
                <x-react name="UsageForm" :props="[
                    'ingredients' => $ingredients->map(fn ($i) => ['id' => $i->id, 'name' => $i->name, 'unit' => $i->unit, 'stock' => (float) $i->stock_on_hand])->values(),
                    'old' => $usageFormFailed ? old() : null,
                    'errors' => collect($errors->toArray())->map(fn ($m) => $m[0])->all(),
                    'today' => today()->toDateString(),
                ]" />
            </div>
            <div class="modal-foot">
                <button type="button" class="btn" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Save and deduct from stock</button>
            </div>
        </form>
    </x-modal>
@endif

@if ($isOwner)
    @php $fixBag = $errors->editUsage; @endphp
    <x-modal id="editUsage" title="Edit amount used" :open="$fixBag->any()">
        <form method="POST" action="{{ old('_edit_id') ? route('stockOuts.updateItem', old('_edit_id')) : '#' }}">
            @csrf @method('PUT')
            <input type="hidden" name="_edit_id" value="{{ old('_edit_id') }}">
            <div class="modal-body">
                <x-field name="quantity" label="Correct amount used" bag="editUsage" for="fix_qty" hint="Lowering it puts the difference back into stock.">
                    <input id="fix_qty" type="number" name="quantity" min="0.01" step="0.01" value="{{ $fixBag->any() ? old('quantity') : '' }}" class="input {{ $fixBag->has('quantity') ? 'is-invalid' : '' }}" required>
                </x-field>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Save correction</button>
            </div>
        </form>
    </x-modal>
@endif
@endsection
