@extends('layouts.app')

@section('title', 'Collections report')

@section('content')
@include('reports._head', ['title' => 'Collections report', 'note' => 'by date received'])

<div class="card">@include('reports._range')</div>

<div class="mb-5 grid grid-cols-[repeat(3,minmax(0,260px))] gap-4 max-md:grid-cols-2">
    <x-stat label="Money collected" :value="'₱' . number_format($summary['total'], 2)" />
    <x-stat label="Payments" :value="$summary['count']" />
    <x-stat label="Refunds given" :value="'₱' . number_format($summary['refunds'], 2)" :note="$summary['refund_count'] . ' ' . Str::plural('cancelled order', $summary['refund_count']) . ' refunded in this period'" :alert="$summary['refunds'] > 0" />
</div>

<div class="grid items-start gap-5 md:grid-cols-2">
    <div class="card">
        <div class="card-head"><h2 class="card-title">By method</h2></div>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Method</th><th class="num">Payments</th><th class="num">Amount</th></tr></thead>
                <tbody>
                    @foreach (\App\Models\Payment::METHODS as $key => $label)
                        @php $row = $summary['by_method'][$key] ?? null; @endphp
                        <tr class="{{ $row ? '' : 'row-muted' }}"><td>{{ $label }}</td><td class="num">{{ $row->n ?? 0 }}</td><td class="num">@peso($row->total ?? 0)</td></tr>
                    @endforeach
                </tbody>
                <tfoot><tr><td>Total</td><td class="num">{{ $summary['count'] }}</td><td class="num">@peso($summary['total'])</td></tr></tfoot>
            </table>
        </div>
    </div>
    <div class="card">
        <div class="card-head"><h2 class="card-title">By payment type</h2></div>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Type</th><th class="num">Payments</th><th class="num">Amount</th></tr></thead>
                <tbody>
                    @foreach (\App\Models\Payment::TYPES as $key => $label)
                        @php $row = $summary['by_type'][$key] ?? null; @endphp
                        <tr class="{{ $row ? '' : 'row-muted' }}"><td>{{ $label }}</td><td class="num">{{ $row->n ?? 0 }}</td><td class="num">@peso($row->total ?? 0)</td></tr>
                    @endforeach
                </tbody>
                <tfoot><tr><td>Total</td><td class="num">{{ $summary['count'] }}</td><td class="num">@peso($summary['total'])</td></tr></tfoot>
            </table>
        </div>
    </div>
</div>

<div class="card" id="list">
    <div class="card-head"><h2 class="card-title">Payments</h2></div>
    @if ($payments->isEmpty())
        <div class="empty"><strong>No payments in this period</strong>Pick another date range.</div>
    @else
        <div class="overflow-x-auto">
            <table class="table rwd">
                <thead><tr><th>Received</th><th>Order</th><th>Type</th><th>Method</th><th class="max-md:hidden">Received by</th><th class="num">Amount</th></tr></thead>
                <tbody>
                    @foreach ($payments as $payment)
                        <tr>
                            <td class="whitespace-nowrap">{{ $payment->paid_at->format('M j, Y') }}<span class="cell-sub">{{ $payment->paid_at->format('g:i A') }}</span></td>
                            <td class="primary"><a href="{{ route('orders.show', $payment->order) }}" class="row-link">{{ $payment->order->customer->name }}</a><span class="cell-sub">{{ $payment->order->order_number }}</span></td>
                            <td>{{ $payment->type_label }}</td>
                            <td>{{ $payment->method_label }}@include('payments._proof')</td>
                            <td class="max-md:hidden">{{ $payment->receiver?->name ?? '—' }}</td>
                            <td class="num">@peso($payment->amount)</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $payments->links() }}
    @endif
</div>
@endsection
