@extends('layouts.app')

@section('title', $order->order_number)

@section('content')
@php
    $balance = $order->balance_due;
    $dpRemaining = max(0, round($order->down_payment_required - $order->total_paid, 2));
    $next = $order->manualNextStatuses();
    $job = $order->jobOrder;
    $isDelivery = $order->order_type === 'delivery';

    $steps = ['pending' => $order->isRush() ? 'Full payment' : 'Down payment', 'confirmed' => 'Confirmed', 'in_production' => 'In production', 'ready_for_release' => 'Ready'];
    if ($isDelivery) { $steps['out_for_delivery'] = 'Out for delivery'; }
    $steps['completed'] = 'Completed';
    $stepKeys = array_keys($steps);
    $currentStep = array_search($order->status, $stepKeys, true);

    $payBag = $errors->payment;
    $defaultAmount = $order->status === 'pending' && $dpRemaining > 0 ? $dpRemaining : $balance;

    // The page has two tabs: Order (items, job order, details) and Payments.
    // A failed payment form reopens on the Payments tab; "#payments" in the address
    // (used after saving a payment) also picks it, see app.js.
    $tab = $payBag->any() ? 'payments' : 'order';
@endphp

<a href="{{ route('orders.index') }}" class="mb-2.5 inline-flex items-center gap-1 text-sm font-medium text-ink-2 hover:text-brand hover:no-underline print:hidden"><x-icon name="back" /> Orders</a>
<div class="hidden print:block"><strong>SweetNest</strong> · Order slip printed {{ now()->format('M j, Y g:i A') }}</div>

<div class="mb-[22px] flex flex-wrap items-end justify-between gap-4">
    <div>
        <div class="flex flex-wrap items-center gap-2.5">
            <h1 class="text-2xl font-bold tracking-[-0.02em] max-md:text-[21px]">{{ $order->order_number }}</h1>
            <x-status :status="$order->status" />
            @if ($order->isRush())<span class="badge b-orange" title="Due within a day of the order date: {{ rtrim(rtrim(number_format($order->rush_fee_percent, 2), '0'), '.') }}% fee on top of the items, paid in full">Rush order</span>@endif
        </div>
        <p class="mt-1 text-ink-2">
            <a href="{{ route('customers.show', $order->customer) }}">{{ $order->customer->name }}</a>
            · Due <span class="{{ $order->isOverdue() ? 'text-danger font-semibold' : '' }}">{{ $order->due_label }}</span>
            · {{ $order->order_type_label }}
        </p>
    </div>
    <div class="flex flex-wrap gap-2 print:hidden">
        @if ($order->isOpen())
            <button type="button" class="btn" data-modal-open="editOrder"><x-icon name="edit" /> Edit details</button>
        @endif
        <div class="menu relative inline-block" data-menu>
            <button type="button" class="btn" data-menu-toggle aria-haspopup="true" aria-expanded="false">More <span class="ml-[3px] inline-block rotate-45 border-0 border-r-[1.5px] border-b-[1.5px] border-solid border-current p-[2.5px] -translate-y-0.5" aria-hidden="true"></span></button>
            <div class="menu-list" role="menu">
                <button type="button" role="menuitem" onclick="window.print()"><x-icon name="print" /> Print</button>
                @if (in_array('cancelled', $next, true))
                    <button type="button" role="menuitem" class="danger" data-modal-open="cancelOrder">Cancel order</button>
                @endif
            </div>
        </div>
    </div>
</div>

@php
    // Someone else may have changed the order while this page was open (for example it was
    // cancelled or fully paid), so the form that failed may no longer be on the page.
    $lostErrors = array_filter([
        $errors->orderStatus->first('status'),
        $errors->orderStatus->first('refund'),
        ! $order->canReceivePayment() ? $errors->payment->first() : null,
        ! ($order->status === 'confirmed' && ! $job) ? $errors->jobOrder->first() : null,
    ]);
@endphp
@foreach ($lostErrors as $message)
    <div class="alert alert-error"><div class="grow">{{ $message }}</div><button type="button" class="alert-close" aria-label="Close">&times;</button></div>
@endforeach

@if ($order->status === 'cancelled')
    <div class="alert alert-error">
        <div class="grow"><strong>This order was cancelled.</strong> Reason: {{ $order->cancel_reason ?: 'not given' }}
            @if ($order->total_paid > 0)
                <br>Payments received (@peso($order->total_paid)) stay on record.
                @if ($order->refund_amount > 0)
                    @if ($order->refunded_at)
                        <strong>Refund of @peso($order->refund_amount) given</strong> on {{ $order->refunded_at->format('M j, Y g:i A') }}.
                    @else
                        <strong>Refund due: @peso($order->refund_amount)</strong> ({{ config('sweetnest.refund_percent') }}% of the total, because it was fully paid).
                    @endif
                @endif
            @endif
        </div>
        @if ($order->needsRefund())
            <form method="POST" action="{{ route('orders.markRefunded', $order) }}" class="inline print:hidden" data-confirm="Mark the refund of ₱{{ number_format($order->refund_amount, 2) }} as given to the customer?">
                @csrf @method('PATCH')
                <button type="submit" class="btn btn-sm">Refund given</button>
            </form>
        @endif
    </div>
@else
    <div class="card">
        <div class="card-body">
            <div class="steps" aria-label="Order progress">
                @foreach ($steps as $key => $label)
                    @php $index = array_search($key, $stepKeys, true); @endphp
                    <div class="step {{ $index < $currentStep || $order->status === 'completed' ? 'done' : '' }} {{ $index === $currentStep ? 'current' : '' }}">{{ $label }}</div>
                @endforeach
            </div>
        </div>
        @if ($order->status !== 'completed')
            <div class="card-foot flex flex-wrap items-center justify-between gap-3 print:hidden">
                <div>
                    <strong>Next:</strong>
                    @switch($order->status)
                        @case('pending')
                            @if ($order->isRush())
                                Rush order: collect the full payment of @peso($dpRemaining). The order is confirmed automatically once it is paid.
                            @else
                                Collect the down payment of @peso($dpRemaining). The order is confirmed automatically once it is paid.
                            @endif
                            @break
                        @case('confirmed')
                            @if (! $job)
                                {{ $order->isRush() ? 'Paid in full' : 'Down payment received' }}. Create a job order so the kitchen can start.
                            @elseif ($job->status === 'cancelled')
                                The job order was cancelled. Reopen it when the kitchen is ready.
                            @else
                                Job order {{ $job->number }} is waiting for the kitchen to start.
                            @endif
                            @break
                        @case('in_production')
                            Being made in the kitchen ({{ $job?->number }}). It becomes "Ready" when the job order is marked done.
                            @break
                        @case('ready_for_release')
                            @if ($job && $job->status === 'completed' && $job->stockOuts()->doesntExist())
                                Log the ingredients used on <a href="{{ route('jobOrders.show', $job) }}">{{ $job->number }}</a>.
                            @endif
                            @if ($isDelivery)
                                Ready. Mark it as out for delivery when it leaves the shop.@if ($balance > 0) The rider should collect @peso($balance).@endif
                            @elseif ($balance > 0)
                                Ready for pickup. Collect the balance of @peso($balance), then mark it as completed.
                            @else
                                Ready for pickup. Mark it as completed when the customer picks it up.
                            @endif
                            @break
                        @case('out_for_delivery')
                            @if ($balance > 0)
                                On the way. Record the balance of @peso($balance) once collected, then mark it as completed.
                            @else
                                On the way. Mark it as completed once delivered.
                            @endif
                            @break
                    @endswitch
                </div>
                <div class="flex flex-wrap gap-2 print:hidden">
                    @php
                        // "Record payment" is the main button only when paying is the next step;
                        // otherwise it stays available as a plain button.
                        $payIsNext = $order->status === 'pending'
                            || ($balance > 0 && $order->status === 'out_for_delivery')
                            || ($balance > 0 && $order->status === 'ready_for_release' && ! $isDelivery);
                    @endphp
                    @if ($order->canReceivePayment())
                        <button type="button" class="btn {{ $payIsNext ? 'btn-primary' : '' }}" data-modal-open="recordPayment">Record payment</button>
                    @endif
                    @if ($order->status === 'confirmed' && ! $job)
                        <button type="button" class="btn btn-primary" data-modal-open="createJobOrder">Create job order</button>
                    @elseif ($job && in_array($order->status, ['confirmed', 'in_production'], true))
                        <a href="{{ route('jobOrders.show', $job) }}" class="btn">Open job order</a>
                    @endif
                    @if (in_array('out_for_delivery', $next, true))
                        <form method="POST" action="{{ route('orders.updateStatus', $order) }}" class="inline" data-confirm="Mark {{ $order->order_number }} as out for delivery?" data-confirm-button="Yes, it's out">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="out_for_delivery">
                            <button type="submit" class="btn btn-primary">Mark as out for delivery</button>
                        </form>
                    @endif
                    @if (in_array('completed', $next, true) && $balance <= 0)
                        <form method="POST" action="{{ route('orders.updateStatus', $order) }}" class="inline" data-confirm="Mark {{ $order->order_number }} as completed? This means the customer has received it." data-confirm-button="Mark as completed">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="completed">
                            <button type="submit" class="btn btn-primary">Mark as completed</button>
                        </form>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endif

{{-- Two tabs, like the Inventory tabs: Order and Payments. The header, progress bar and
     the "Next:" line above stay visible on both, so the next step is never hidden. --}}
<nav class="subnav print:hidden" role="tablist" aria-label="Order sections" data-tabs>
    <button type="button" class="{{ $tab === 'order' ? 'active' : '' }}" role="tab" id="tab-order" aria-controls="panel-order" aria-selected="{{ $tab === 'order' ? 'true' : 'false' }}" data-tab="order" @if ($tab !== 'order') tabindex="-1" @endif>Order</button>
    <button type="button" class="{{ $tab === 'payments' ? 'active' : '' }}" role="tab" id="tab-payments" aria-controls="panel-payments" aria-selected="{{ $tab === 'payments' ? 'true' : 'false' }}" data-tab="payments" @if ($tab !== 'payments') tabindex="-1" @endif>Payments</button>
</nav>

<section id="panel-order" role="tabpanel" aria-labelledby="tab-order" data-tab-panel="order" @if ($tab !== 'order') hidden @endif>
    <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
        <div>
            <div class="card">
                <div class="card-head">
                    <h2 class="card-title">Items</h2>
                    @if ($order->canEditItemDetails())<span class="card-sub print:hidden">Details can be changed until baking starts.</span>@endif
                </div>
                <div class="overflow-x-auto">
                    <table class="table rwd">
                        <thead><tr><th>Item</th><th class="num">Qty</th><th class="num">Price</th><th class="num">Subtotal</th>@if ($order->canEditItemDetails())<th class="actions print:hidden"></th>@endif</tr></thead>
                        <tbody>
                            @foreach ($order->items as $item)
                                <tr>
                                    <td class="primary">
                                        <div class="flex items-start gap-4">
                                            @if ($item->reference_image_url)
                                                <a href="{{ $item->reference_image_url }}" target="_blank" rel="noopener" title="Open reference photo"><img src="{{ $item->reference_image_url }}" alt="Reference photo for {{ $item->name }}" class="block size-[46px] shrink-0 rounded-[8px] border border-line object-cover"></a>
                                            @endif
                                            <div>
                                                <span class="font-semibold">{{ $item->name }}</span>
                                                @if ($item->isCustom())<span class="cell-sub">Custom item · {{ $item->category_label }}</span>@endif
                                                @if ($item->specs)
                                                    <span class="cell-sub">@foreach ($item->specs as $label => $value){{ $label }}: {{ $value }}@if (! $loop->last) · @endif @endforeach</span>
                                                @endif
                                                @if ($item->inscription)<span class="cell-sub">Message: "{{ $item->inscription }}"</span>@endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="num" data-label="Qty">{{ $item->quantity }}</td>
                                    <td class="num" data-label="Each">@peso($item->unit_price)</td>
                                    <td class="num">@peso($item->subtotal)</td>
                                    @if ($order->canEditItemDetails())
                                        <td class="actions print:hidden">
                                            @if (\App\Models\OrderItem::takesSpecsFor($item->category))
                                                <button type="button" class="btn btn-sm" data-modal-open="editItem"
                                                    data-action="{{ route('orderItems.update', $item) }}"
                                                    data-title="Edit {{ $item->name }}"
                                                    data-fill="{{ json_encode(['cake_type' => $item->cake_type, 'flavor' => $item->flavor, 'size' => $item->size, 'tiers' => $item->tiers, 'design_theme' => $item->design_theme, 'inscription' => $item->inscription, '_edit_id' => $item->id, 'has_image' => (bool) $item->reference_image, 'image_url' => $item->reference_image_url]) }}">Edit</button>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            @if ($order->isRush() || $order->delivery_fee_amount > 0)
                                <tr class="row-muted"><td colspan="3">Items</td><td class="num">@peso($order->items_total)</td>@if ($order->canEditItemDetails())<td class="print:hidden"></td>@endif</tr>
                            @endif
                            @if ($order->isRush())
                                <tr class="row-muted"><td colspan="3">Rush fee ({{ rtrim(rtrim(number_format($order->rush_fee_percent, 2), '0'), '.') }}%)</td><td class="num">@peso($order->rush_fee)</td>@if ($order->canEditItemDetails())<td class="print:hidden"></td>@endif</tr>
                            @endif
                            @if ($order->delivery_fee_amount > 0)
                                <tr class="row-muted"><td colspan="3">Delivery fee</td><td class="num">@peso($order->delivery_fee_amount)</td>@if ($order->canEditItemDetails())<td class="print:hidden"></td>@endif</tr>
                            @endif
                            <tr><td colspan="3">Total</td><td class="num">@peso($order->total_amount)</td>@if ($order->canEditItemDetails())<td class="print:hidden"></td>@endif</tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div>
            <div class="card">
                <div class="card-head"><h2 class="card-title">Job order</h2>@if ($job)<x-status :status="$job->status" type="job" />@endif</div>
                <div class="card-body">
                    @if ($job)
                        <dl class="kv">
                            <dt>Number</dt><dd><a href="{{ route('jobOrders.show', $job) }}">{{ $job->number }}</a></dd>
                            <dt>Production date</dt><dd>{{ $job->production_date->format('D, M j, Y') }}</dd>
                            <dt>Assigned to</dt><dd>{{ $job->assignee?->name ?? 'Not assigned' }}</dd>
                        </dl>
                    @elseif ($order->status === 'confirmed')
                        <p class="text-ink-2">No job order yet. Use "Create job order" above.</p>
                    @elseif ($order->status === 'pending')
                        <p class="text-ink-2">A job order can be created once the order is confirmed: {{ $order->isRush() ? 'a rush order is paid in full first' : 'the 50% down payment on a regular order' }}.</p>
                    @else
                        <p class="text-ink-2">None.</p>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-head"><h2 class="card-title">Details</h2></div>
                <div class="card-body">
                    <dl class="kv">
                        <dt>Customer</dt><dd><a href="{{ route('customers.show', $order->customer) }}">{{ $order->customer->name }}</a></dd>
                        <dt>Contact</dt><dd>{{ $order->customer->contact_display }}</dd>
                        @if ($order->customer->facebook_name)<dt>Facebook</dt><dd>{{ $order->customer->facebook_name }}</dd>@endif
                        <dt>Type</dt><dd>{{ $order->kind_label }} order{{ $order->isRush() ? ' (' . rtrim(rtrim(number_format($order->rush_fee_percent, 2), '0'), '.') . '% rush fee, paid in full)' : '' }}<span class="cell-sub">{{ $order->isRush() ? 'Due within a day of the order date.' : 'Due 2 or more days after the order date.' }}</span></dd>
                        <dt>{{ $isDelivery ? 'Deliver on' : 'Pickup on' }}</dt><dd>{{ $order->scheduled_at->format('D, M j, Y g:i A') }}</dd>
                        @if ($isDelivery)
                            <dt>Deliver to</dt><dd>{{ $order->place_address ?: '—' }}</dd>
                        @elseif ($order->picked_up_at_shop)
                            <dt>Pick up at</dt><dd>{{ $order->place_address }}<span class="cell-sub">The shop. @if (config('sweetnest.shop.pickup_note')){{ config('sweetnest.shop.pickup_note') }}@endif</span></dd>
                        @else
                            <dt>Pick up at</dt><dd>{{ $order->place_address ?: '—' }}<span class="cell-sub">Meet-up spot the customer asked for.</span></dd>
                        @endif
                        <dt>Ordered on</dt><dd>{{ $order->order_date->format('M j, Y') }}</dd>
                        <dt>Taken by</dt><dd>{{ $order->creator?->name ?? '—' }}</dd>
                        @if ($order->notes)<dt>Notes</dt><dd>{{ $order->notes }}</dd>@endif
                    </dl>
                    @if ($order->delivery_maps_url && ($isDelivery || ! $order->picked_up_at_shop))
                        <div class="mt-3.5 flex flex-wrap gap-2 print:hidden"><a href="{{ $order->delivery_maps_url }}" target="_blank" rel="noopener" class="btn btn-sm"><x-icon name="directions" /> {{ $isDelivery ? 'Directions for the rider' : 'Directions to the meet-up spot' }}</a></div>
                    @endif
                    @if ($order->has_delivery_pin && ($isDelivery || ! $order->picked_up_at_shop))
                        <details class="group mt-2.5 print:hidden">
                            <summary class="inline-flex cursor-pointer list-none items-center gap-[7px] py-1 text-[13.5px] font-semibold text-brand hover:underline [&::-webkit-details-marker]:hidden [&_.icon]:size-[15px]">
                                <x-icon name="pin" /> <span class="group-open:hidden">Show on map</span><span class="hidden group-open:inline">Hide map</span>
                            </summary>
                            <x-react name="PinMap" :props="['lat' => $order->delivery_latitude, 'lng' => $order->delivery_longitude, 'map' => \App\Support\Address::mapConfig(), 'label' => $isDelivery ? 'Map pin of the delivery address' : 'Map pin of the pickup spot']" />
                        </details>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>

<section id="panel-payments" role="tabpanel" aria-labelledby="tab-payments" data-tab-panel="payments" @if ($tab !== 'payments') hidden @endif>
    <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
        <div>
            <div class="card">
                <div class="card-head">
                    <h2 class="card-title">Payments</h2>
                    @if ($order->canReceivePayment())
                        <span class="card-sub print:hidden">Use "Record payment" above.</span>
                    @endif
                </div>
                @if ($order->payments->isEmpty())
                    <div class="empty"><strong>No payments yet</strong>{{ $order->status === 'cancelled' ? 'This order was cancelled before any payment.' : 'Record the down payment to confirm this order.' }}</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="table rwd">
                            <thead><tr><th>Date</th><th>Type</th><th>Method</th><th class="max-md:hidden">Received by</th><th class="num">Amount</th></tr></thead>
                            <tbody>
                                @foreach ($order->payments->sortBy('paid_at') as $payment)
                                    <tr>
                                        <td class="whitespace-nowrap">{{ $payment->paid_at->format('M j, Y') }}<span class="cell-sub">{{ $payment->paid_at->format('g:i A') }}</span></td>
                                        <td class="primary strong-sm">{{ $payment->type_label }}</td>
                                        <td>{{ $payment->method_label }}@include('payments._proof')</td>
                                        <td class="max-md:hidden">{{ $payment->receiver?->name ?? '—' }}</td>
                                        <td class="num">@peso($payment->amount)</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- On a phone the balance comes first. --}}
        <div class="max-xl:order-first">
            <div class="card">
                <div class="card-head"><h2 class="card-title">Payment</h2>@if ($order->status !== 'cancelled')<x-status :status="$order->payment_status" type="payment" />@endif</div>
                <div class="card-body money">
                    @if ($order->isRush() || $order->delivery_fee_amount > 0)
                        <div class="money-row"><span class="text-ink-2">Items</span><span>@peso($order->items_total)</span></div>
                    @endif
                    @if ($order->isRush())
                        <div class="money-row"><span class="text-ink-2">Rush fee ({{ rtrim(rtrim(number_format($order->rush_fee_percent, 2), '0'), '.') }}%)</span><span>@peso($order->rush_fee)</span></div>
                    @endif
                    @if ($order->delivery_fee_amount > 0)
                        <div class="money-row"><span class="text-ink-2">Delivery fee</span><span>@peso($order->delivery_fee_amount)</span></div>
                    @endif
                    <div class="money-row"><span class="text-ink-2">Order total</span><span>@peso($order->total_amount)</span></div>
                    @if ($order->status === 'cancelled')
                        <div class="money-row total"><span>Paid before cancelling</span><span>@peso($order->total_paid)</span></div>
                        @if ($order->refund_amount > 0)
                            <div class="money-row"><span class="text-ink-2">{{ $order->refunded_at ? 'Refund given' : 'Refund due' }} ({{ config('sweetnest.refund_percent') }}%)</span><span class="{{ $order->refunded_at ? '' : 'text-danger' }}">@peso($order->refund_amount)</span></div>
                        @endif
                    @else
                        <div class="money-row"><span class="text-ink-2">{{ $order->down_payment_label }}{{ $order->isRush() ? ' (rush order)' : '' }}</span><span>@peso($order->down_payment_required)</span></div>
                        <div class="money-row"><span class="text-ink-2">Paid so far</span><span>@peso($order->total_paid)</span></div>
                        <div class="money-row total"><span>Balance</span><span class="{{ $balance > 0 ? 'text-danger' : '' }}">@peso($balance)</span></div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Record payment --}}
@if ($order->canReceivePayment())
    <x-modal id="recordPayment" title="Record payment" :open="$payBag->any()">
        <form method="POST" action="{{ route('payments.store', $order) }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-body">
                <x-react name="PaymentForm" :props="[
                    'balance' => $balance,
                    'dpRemaining' => $dpRemaining,
                    'isPending' => $order->status === 'pending',
                    'isRush' => $order->isRush(),
                    'methods' => \App\Models\Payment::METHODS,
                    'old' => $payBag->any() ? old() : null,
                    'errors' => collect($payBag->toArray())->map(fn ($m) => $m[0])->all(),
                    'now' => now()->format('Y-m-d\TH:i'),
                    'defaultAmount' => $defaultAmount,
                    'photoMaxKb' => \App\Models\OrderItem::photoMaxKb(),
                    'photoMaxLabel' => \App\Models\OrderItem::photoMaxLabel(),
                ]" />
            </div>
            <div class="modal-foot">
                <button type="button" class="btn" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Save payment</button>
            </div>
        </form>
    </x-modal>
@endif

{{-- Edit order details --}}
@if ($order->isOpen())
    @php $editBag = $errors->editOrder; $editType = $editBag->any() ? old('order_type') : $order->order_type; @endphp
    <x-modal id="editOrder" title="Edit order details" wide :open="$editBag->any()">
        <form method="POST" action="{{ route('orders.update', $order) }}">
            @csrf @method('PUT')
            <div class="modal-body">
                <div class="grid gap-x-4 md:grid-cols-2">
                    <x-field name="scheduled_at" label="Due date and time" bag="editOrder" for="eo_at">
                        <input id="eo_at" type="datetime-local" name="scheduled_at" value="{{ $editBag->any() ? old('scheduled_at') : $order->scheduled_at->format('Y-m-d\TH:i') }}" class="input {{ $editBag->has('scheduled_at') ? 'is-invalid' : '' }}" required
                            data-rush-watch="#edit-rush" data-rush-label="#editOrder [data-kind-label]" data-order-date="{{ $order->order_date->toDateString() }}" data-rush-days="{{ config('sweetnest.rush_within_days') }}">
                    </x-field>
                    @if ($order->status === 'out_for_delivery')
                        <div class="field">
                            <span class="label">Pickup or delivery</span>
                            <div class="py-[9px] font-semibold">Delivery (already out)</div>
                            <input type="hidden" name="order_type" value="delivery">
                        </div>
                    @else
                        <x-field name="order_type" label="Pickup or delivery" bag="editOrder" for="eo_pickup">
                            <div class="flex flex-wrap gap-2.5">
                                <label class="choice"><input id="eo_pickup" type="radio" name="order_type" value="pickup" @checked($editType === 'pickup') data-shows="#edit-pickup-only"> Pickup</label>
                                <label class="choice"><input type="radio" name="order_type" value="delivery" @checked($editType === 'delivery') data-shows="#edit-delivery-only, #edit-delivery-fee"> Delivery</label>
                            </div>
                        </x-field>
                    @endif
                </div>
                @php
                    $oldAt = $editBag->any() && filled(old('scheduled_at')) && strtotime((string) old('scheduled_at')) !== false ? old('scheduled_at') : null;
                    $editRush = $oldAt ? \App\Models\Order::kindFor($oldAt, $order->order_date) === 'rush' : $order->isRush();
                    $editFee = $editBag->any() ? old('rush_fee_percent') : ($order->rush_fee_percent ?? config('sweetnest.rush_fee_default'));
                    $editFee = $editFee === null || $editFee === '' ? '' : rtrim(rtrim(number_format((float) $editFee, 2, '.', ''), '0'), '.');
                    $editDeliveryFee = $editBag->any() ? old('delivery_fee') : ($order->delivery_fee !== null ? rtrim(rtrim(number_format((float) $order->delivery_fee, 2, '.', ''), '0'), '.') : '');
                @endphp
                <div class="grid gap-x-4 md:grid-cols-2">
                    <div class="field">
                        <span class="label">Type of order</span>
                        <div class="py-[9px] font-semibold" data-kind-label>{{ $editRush ? 'Rush' : 'Regular' }}</div>
                        @if ($order->total_paid > 0)<div class="hint">Can't change any more because a payment was recorded.</div>@endif
                    </div>
                    <div id="edit-rush" @if (! $editRush) hidden @endif>
                        @if ($order->total_paid > 0 && $order->isRush())
                            <div class="field">
                                <span class="label">Rush fee</span>
                                <div class="py-[9px] font-semibold">{{ $editFee }}% of the items</div>
                                <div class="hint">Fixed once a payment was recorded, because the amount asked for was based on it.</div>
                                <input type="hidden" name="rush_fee_percent" value="{{ $editFee }}">
                            </div>
                        @else
                            <x-field name="rush_fee_percent" label="Rush fee (% of the items)" bag="editOrder" for="eo_fee" hint="Usually {{ implode('% or ', config('sweetnest.rush_fee_choices')) }}%. Added on top of the items; a rush order is paid in full.">
                                <input id="eo_fee" type="number" name="rush_fee_percent" value="{{ $editFee }}" min="0.5" max="100" step="0.5" class="input {{ $editBag->has('rush_fee_percent') ? 'is-invalid' : '' }}">
                            </x-field>
                        @endif
                    </div>
                </div>
                <div class="mt-[18px] mb-3.5" id="edit-delivery">
                    <div class="mb-2 flex flex-col gap-0.5">
                        <span class="label mb-0">Pickup spot or delivery address</span>
                        <span class="hint mt-0" id="edit-pickup-only" @if ($editType !== 'pickup') hidden @endif>Where the customer collects the order: the shop, or the meet-up spot they asked for.</span>
                        <span class="hint mt-0" id="edit-delivery-only" @if ($editType !== 'delivery') hidden @endif>Deliveries go around {{ config('sweetnest.delivery_city') }} only.</span>
                    </div>
                    <x-react name="AddressFields" :props="[
                        'prefix' => 'delivery_',
                        'idp' => 'eo_',
                        'values' => $editBag->any() ? old() : \App\Support\Address::withPrefix($order->delivery_parts, 'delivery_'),
                        'errors' => collect($editBag->toArray())->map(fn ($m) => $m[0])->all(),
                        'map' => \App\Support\Address::mapConfig(),
                        'defaults' => \App\Support\Address::defaults(),
                        'saved' => array_values(array_filter([
                            ['label' => 'Use the shop address', 'fields' => \App\Models\Order::shopAddress()],
                            $order->customer->has_address ? ['label' => 'Use ' . $order->customer->first_name . '\'s saved address', 'fields' => $order->customer->address_parts] : null,
                        ])),
                        // Switching Pickup/Delivery swaps the default place (shop / saved address) unless a spot was typed.
                        'defaultsByType' => ['pickup' => \App\Models\Order::shopAddress(), 'delivery' => $order->customer->has_address ? $order->customer->address_parts : null],
                        'typeField' => 'order_type',
                    ]" />
                    <div id="edit-delivery-fee" @if ($editType !== 'delivery') hidden @endif>
                        <x-field name="delivery_fee" label="Delivery fee" bag="editOrder" for="eo_delivery_fee" optional hint="What the delivery app (Maxim) quotes for this address. Added to the total; the balance follows.">
                            <input id="eo_delivery_fee" type="number" name="delivery_fee" value="{{ $editDeliveryFee }}" min="0" max="99999" step="0.01" class="input max-w-[160px] {{ $editBag->has('delivery_fee') ? 'is-invalid' : '' }}" placeholder="0.00">
                        </x-field>
                    </div>
                </div>
                <x-field name="notes" label="Notes" bag="editOrder" for="eo_notes" optional>
                    <textarea id="eo_notes" name="notes" rows="2" maxlength="1000" class="textarea">{{ $editBag->any() ? old('notes') : $order->notes }}</textarea>
                </x-field>
                <p class="hint">Items and prices are fixed once saved because the down payment is based on them. Flavor, size, design and message can be edited per item until baking starts.</p>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Save changes</button>
            </div>
        </form>
    </x-modal>
@endif

{{-- Edit one item's details --}}
@if ($order->canEditItemDetails())
    @php
        $itemBag = $errors->editItem;
        // After a failed save the pop-up reopens on the same item, so it still knows about the current photo.
        $editing = $itemBag->any() ? $order->items->firstWhere('id', (int) old('_edit_id')) : null;
    @endphp
    <x-modal id="editItem" title="Edit item" :open="$itemBag->any()">
        <form method="POST" action="{{ old('_edit_id') ? route('orderItems.update', old('_edit_id')) : '#' }}" enctype="multipart/form-data">
            @csrf @method('PUT')
            <input type="hidden" name="_edit_id" value="{{ old('_edit_id') }}">
            <div class="modal-body">
                <x-react name="ItemSpecsForm" :props="[
                    'values' => $itemBag->any() ? \Illuminate\Support\Arr::only(old(), ['cake_type', 'flavor', 'size', 'design_theme', 'inscription', 'remove_image']) + ['has_image' => (bool) $editing?->reference_image, 'image_url' => $editing?->reference_image_url] : null,
                    'errors' => collect($itemBag->toArray())->map(fn ($m) => $m[0])->all(),
                    'options' => \App\Models\OrderItem::specOptions(),
                    'photoMaxKb' => \App\Models\OrderItem::photoMaxKb(),
                    'photoMaxLabel' => \App\Models\OrderItem::photoMaxLabel(),
                ]" />
            </div>
            <div class="modal-foot">
                <button type="button" class="btn" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Save changes</button>
            </div>
        </form>
    </x-modal>
@endif

{{-- Cancel order --}}
@if (in_array('cancelled', $next, true))
    @php $cancelBag = $errors->orderStatus; @endphp
    <x-modal id="cancelOrder" title="Cancel {{ $order->order_number }}?" :open="$cancelBag->has('cancel_reason')">
        <form method="POST" action="{{ route('orders.updateStatus', $order) }}">
            @csrf @method('PATCH')
            <input type="hidden" name="status" value="cancelled">
            <div class="modal-body">
                <p class="mb-3.5">The order stays on record as cancelled. It can't be undone.
                    @if ($job && in_array($job->status, ['pending', 'in_progress'], true)) Its job order {{ $job->number }} will be cancelled too.@endif
                </p>
                @if ($order->refundDue() > 0)
                    <div class="alert alert-warning"><div class="grow"><strong>Refund due: @peso($order->refundDue())</strong>, {{ config('sweetnest.refund_percent') }}% of the total, because the order was paid in full. The payments stay on record; tick "Refund given" on the order once the money is handed back.</div></div>
                @elseif ($order->total_paid > 0)
                    <div class="alert alert-warning"><div class="grow"><strong>No refund.</strong> The order was not paid in full (@peso($order->total_paid) of @peso($order->total_amount)), and the shop keeps what was paid. Refunds are given only on fully paid orders ({{ config('sweetnest.refund_percent') }}% of the total).</div></div>
                @endif
                <x-field name="cancel_reason" label="Reason" bag="orderStatus" for="cancel_reason">
                    <textarea id="cancel_reason" name="cancel_reason" rows="2" maxlength="255" class="textarea {{ $cancelBag->has('cancel_reason') ? 'is-invalid' : '' }}" placeholder="e.g. Customer changed plans" required>{{ old('cancel_reason') }}</textarea>
                </x-field>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn" data-modal-close>Keep the order</button>
                <button type="submit" class="btn btn-danger">Cancel order</button>
            </div>
        </form>
    </x-modal>
@endif

@if ($order->status === 'confirmed' && ! $job)
    @include('job-orders._create-modal', ['forOrder' => $order])
@endif
@endsection
