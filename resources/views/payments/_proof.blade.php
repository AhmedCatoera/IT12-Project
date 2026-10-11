{{-- The reference number and GCash screenshot under a payment's method, if any --}}
@if ($payment->reference_no)<span class="cell-sub">Ref: {{ $payment->reference_no }}</span>@endif
@if ($payment->proof_image_url)
    <a href="{{ $payment->proof_image_url }}" target="_blank" rel="noopener" class="cell-sub inline-flex items-center gap-1 font-medium text-brand print:hidden" title="Open the GCash screenshot"><x-icon name="photo" class="size-3.5" /> Screenshot</a>
    <span class="cell-sub hidden print:block">Screenshot on file</span>
@endif
