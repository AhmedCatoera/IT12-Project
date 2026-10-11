{{-- A search box with the magnifier icon inside it. --}}
@props(['name' => 'search', 'value' => '', 'placeholder' => 'Search'])
<div {{ $attributes->merge(['class' => 'relative']) }}>
    <x-icon name="search" class="pointer-events-none absolute top-1/2 left-[11px] size-4 -translate-y-1/2 text-ink-3" />
    <input type="search" name="{{ $name }}" value="{{ $value }}" class="input pl-[34px]" placeholder="{{ $placeholder }}" aria-label="{{ $placeholder }}">
</div>
