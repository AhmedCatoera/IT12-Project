{{-- Date range filter shared by the reports. --}}
@php
    $route = request()->route()->getName();
    // A new period starts every table on its first page ("used" and "levels" are the
    // Inventory report's own page numbers).
    $keep = request()->except(['from', 'to', 'page', 'used', 'levels']);
    $ranges = [
        'Today' => [today(), today()],
        'This week' => [now()->startOfWeek(), today()],
        'This month' => [now()->startOfMonth(), today()],
        'Last month' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
        'This year' => [now()->startOfYear(), today()],
    ];
@endphp
<form method="GET" class="flex flex-wrap items-center gap-2 border-b border-line bg-surface px-5 py-3 max-md:px-4 print:hidden print:hidden">
    @foreach ($keep as $key => $value)
        @if (is_string($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
    @endforeach
    <label class="text-xs text-ink-2" for="from">From</label>
    <input id="from" type="date" name="from" value="{{ $from->toDateString() }}" class="input w-auto max-md:w-full">
    <label class="text-xs text-ink-2" for="to">to</label>
    <input id="to" type="date" name="to" value="{{ $to->toDateString() }}" class="input w-auto max-md:w-full">
    <button type="submit" class="btn">Apply</button>
    <div class="ml-auto flex flex-wrap gap-1.5 max-md:ml-0">
        @foreach ($ranges as $label => [$a, $b])
            @php $active = $a->toDateString() === $from->toDateString() && $b->toDateString() === $to->toDateString(); @endphp
            <a href="{{ route($route, array_merge($keep, ['from' => $a->toDateString(), 'to' => $b->toDateString()])) }}" class="chip {{ $active ? 'chip-active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>
</form>
