@extends('layouts.app')

@section('title', 'Count stock')

@section('content')
@php $shift = old('shift', $defaultShift); $allDone = count($doneShifts) >= count(\App\Models\PhysicalCount::SHIFTS); @endphp
<a href="{{ route('counts.index') }}" class="mb-2.5 inline-flex items-center gap-1 text-sm font-medium text-ink-2 hover:text-brand hover:no-underline"><x-icon name="back" /> Physical Counts</a>
<div class="mb-[22px] flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold tracking-[-0.02em] max-md:text-[21px]">Count stock</h1>
        <p class="mt-1 text-ink-2">Enter what is actually on the shelf. The system amount is hidden so the count stays fair.</p>
    </div>
</div>

@if ($allDone)
    <div class="alert alert-info"><div class="grow">Both shifts were already counted today. You can count again tomorrow.</div></div>
@endif

<form method="POST" action="{{ route('counts.store') }}">
    @csrf
    <div class="card">
        <div class="card-body">
            <div class="grid gap-x-4 md:grid-cols-2">
                <div class="field">
                    <span class="label">Date</span>
                    <div class="py-[9px] font-semibold">{{ today()->format('l, F j, Y') }}</div>
                    <div class="hint">Counts are for today, so they are compared with today's stock.</div>
                </div>
                <x-field name="shift" label="Shift" for="shift_morning">
                    <div class="flex flex-wrap gap-2.5">
                        @foreach (\App\Models\PhysicalCount::SHIFTS as $key => $label)
                            @php $done = in_array($key, $doneShifts, true); @endphp
                            <label class="choice {{ $done ? 'is-disabled' : '' }}"><input id="shift_{{ $key }}" type="radio" name="shift" value="{{ $key }}" @checked($shift === $key && ! $done) @disabled($done)> {{ $label }}@if ($done) <span class="text-xs text-ink-2">(done)</span>@endif</label>
                        @endforeach
                    </div>
                </x-field>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h2 class="card-title">Counted quantities</h2><span class="card-sub">Enter 0 if there is none</span></div>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Ingredient</th><th class="num">Counted</th></tr></thead>
                <tbody>
                    @foreach ($ingredients as $ingredient)
                        <tr>
                            <td><label for="count_{{ $ingredient->id }}" class="font-semibold">{{ $ingredient->name }}</label></td>
                            <td class="num">
                                <div class="qty-input justify-end">
                                    <input id="count_{{ $ingredient->id }}" type="number" name="counts[{{ $ingredient->id }}]" value="{{ old('counts.' . $ingredient->id) }}" min="0" step="0.01" inputmode="decimal" class="input ml-auto max-w-[150px] text-right max-md:max-w-[110px] {{ $errors->has('counts.' . $ingredient->id) ? 'is-invalid' : '' }}" required>
                                    <span class="unit w-[60px]">{{ $ingredient->unit }}</span>
                                </div>
                                @error('counts.' . $ingredient->id)<div class="error-text">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-body">
            <x-field name="notes" label="Notes" optional>
                <textarea id="notes" name="notes" rows="2" maxlength="1000" class="textarea" placeholder="e.g. Two eggs were cracked">{{ old('notes') }}</textarea>
            </x-field>
        </div>
    </div>

    <div class="mt-5 flex justify-end gap-2">
        <a href="{{ route('counts.index') }}" class="btn">Cancel</a>
        <button type="submit" class="btn btn-primary" @disabled($allDone)>Save count</button>
    </div>
</form>
@endsection
