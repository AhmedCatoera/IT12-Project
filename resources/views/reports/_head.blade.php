<a href="{{ route('reports.index') }}" class="mb-2.5 inline-flex items-center gap-1 text-sm font-medium text-ink-2 hover:text-brand hover:no-underline print:hidden"><x-icon name="back" /> Reports</a>
<div class="hidden print:block"><strong>SweetNest</strong> · {{ $title }} · {{ $from->format('M j, Y') }} to {{ $to->format('M j, Y') }}</div>
<div class="mb-[22px] flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold tracking-[-0.02em] max-md:text-[21px]">{{ $title }}</h1>
        <p class="mt-1 text-ink-2">{{ $from->format('M j, Y') }} to {{ $to->format('M j, Y') }}@isset($note) · {{ $note }}@endisset</p>
    </div>
    <div class="flex flex-wrap gap-2 print:hidden">
        <button type="button" class="btn btn-ghost" onclick="window.print()"><x-icon name="print" /> Print</button>
    </div>
</div>
