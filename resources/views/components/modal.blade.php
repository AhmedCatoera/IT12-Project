@props(['id', 'title', 'wide' => false, 'open' => false])
{{-- data-open-on-load re-opens the pop-up after a failed save so the person sees what to fix. --}}
<dialog id="{{ $id }}" {{ $attributes->merge(['class' => 'modal' . ($wide ? ' wide' : '')]) }} aria-labelledby="{{ $id }}-title" @if ($open) data-open-on-load @endif>
    <div class="modal-head">
        <h2 class="modal-title" id="{{ $id }}-title" data-modal-title>{{ $title }}</h2>
        <button type="button" class="btn btn-ghost btn-sm" data-modal-close aria-label="Close">&times;</button>
    </div>
    {{ $slot }}
</dialog>
