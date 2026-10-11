{{-- One stock movement row (from InventoryService::movements). --}}
@php
    $labels = ['stock_in' => ['b-success', 'Stock in'], 'stock_out' => ['b-violet', 'Used'], 'adjustment' => ['b-warning', 'Adjustment']];
    [$badge, $label] = $labels[$m->movement];
    $qty = (float) $m->quantity;
    $isOwner = auth()->user()->isOwner();
@endphp
<tr>
    <td class="whitespace-nowrap">{{ \Illuminate\Support\Carbon::parse($m->movement_date)->format('M j, Y') }}</td>
    @if ($showIngredient ?? false)
        <td class="primary"><a href="{{ route('inventory.show', $m->ingredient_id) }}" class="row-link">{{ $m->ingredient_name }}</a></td>
    @endif
    <td>
        <span class="badge {{ $badge }}">{{ $label }}</span>
        @if ($m->movement === 'adjustment')
            <span class="cell-sub">{{ \App\Models\StockAdjustment::REASONS[$m->reason] ?? $m->reason }}</span>
        @endif
    </td>
    <td class="{{ ($showIngredient ?? false) ? '' : 'primary' }}">
        @if ($m->movement === 'stock_in')
            @if ($isOwner)
                <a href="{{ route('stockIns.show', $m->doc_id) }}">SI-{{ str_pad($m->doc_id, 4, '0', STR_PAD_LEFT) }}</a>
            @else
                SI-{{ str_pad($m->doc_id, 4, '0', STR_PAD_LEFT) }}
            @endif
            @if ($m->reference)<span class="cell-sub">Receipt {{ $m->reference }}</span>@endif
        @elseif ($m->movement === 'stock_out')
            <a href="{{ route('jobOrders.show', $m->job_order_id) }}">JO-{{ str_pad($m->job_order_id, 4, '0', STR_PAD_LEFT) }}</a>
            @if ($m->order_number)<span class="cell-sub">{{ $m->order_number }}</span>@endif
        @else
            @if ($isOwner)
                <a href="{{ route('adjustments.index') }}">ADJ-{{ str_pad($m->doc_id, 4, '0', STR_PAD_LEFT) }}</a>
            @else
                ADJ-{{ str_pad($m->doc_id, 4, '0', STR_PAD_LEFT) }}
            @endif
        @endif
    </td>
    <td class="max-md:hidden">{{ trim(implode(' ', array_filter([$m->user_first_name, $m->user_middle_name, $m->user_last_name]))) ?: '—' }}</td>
    <td class="num font-semibold {{ $qty < 0 ? 'text-danger' : 'text-success' }}">{{ $qty > 0 ? '+' : '−' }}{{ rtrim(rtrim(number_format(abs($qty), 2), '0'), '.') }} {{ $m->unit }}</td>
</tr>
