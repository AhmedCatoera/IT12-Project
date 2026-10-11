@props(['name', 'label' => null, 'bag' => 'default', 'optional' => false, 'hint' => null, 'for' => null])
@php
    $message = $errors->getBag($bag)->first($name);
@endphp
<div {{ $attributes->merge(['class' => 'field']) }}>
    @if ($label)
        <label class="label" for="{{ $for ?? str_replace(['.', '[', ']'], ['_', '_', ''], $name) }}">{{ $label }} @if ($optional)<span class="opt">(optional)</span>@endif</label>
    @endif
    {{ $slot }}
    @if ($message)
        <div class="error-text" data-clearable>{{ $message }}</div>
    @elseif ($hint)
        <div class="hint">{{ $hint }}</div>
    @endif
</div>
