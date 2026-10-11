{{-- A stat tile: a number with its label, an optional icon and note. Links when given an href. --}}
@props(['label', 'value', 'note' => null, 'icon' => null, 'tone' => 'brand', 'href' => null, 'alert' => false, 'warn' => false])
@php
    $tone = $alert ? 'danger' : ($warn ? 'warning' : $tone);
    $iconTone = [
        'brand' => 'bg-brand-soft text-brand', 'info' => 'bg-info-soft text-info', 'success' => 'bg-success-soft text-success',
        'warning' => 'bg-warning-soft text-warning', 'danger' => 'bg-danger-soft text-danger', 'violet' => 'bg-violet-soft text-violet', 'gold' => 'bg-gold-soft text-gold',
    ][$tone] ?? 'bg-brand-soft text-brand';
    $classes = 'relative flex items-start gap-3.5 rounded-lg border border-line bg-surface px-[18px] py-4 text-ink shadow-card max-md:gap-2.5 max-md:p-3.5'
        . ($href ? ' transition-[transform,box-shadow] duration-150 hover:-translate-y-0.5 hover:no-underline hover:shadow-lift' : '');
    $valueColor = $alert ? 'text-danger' : ($warn ? 'text-warning' : '');
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
@else
    <div {{ $attributes->merge(['class' => $classes]) }}>
@endif
    @if ($icon)
        <span class="grid size-[42px] shrink-0 place-items-center rounded-[12px] max-md:size-9 max-md:rounded-[10px] [&_.icon]:size-[21px] {{ $iconTone }}"><x-icon :name="$icon" /></span>
    @endif
    <span class="min-w-0">
        <span class="block text-xs font-medium text-ink-2">{{ $label }}</span>
        <span class="mt-0.5 block text-[26px] leading-[1.15] font-bold tracking-[-0.02em] tabular-nums max-md:text-[22px] {{ $valueColor }}">{{ $value }}</span>
        @if ($note)<span class="mt-[3px] block text-[12px] text-ink-2">{{ $note }}</span>@endif
    </span>
@if ($href)
    </a>
@else
    </div>
@endif
