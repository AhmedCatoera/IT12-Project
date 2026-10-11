@php $eb = $errors->getBag($bag); @endphp
<x-field name="name" label="Ingredient name" :bag="$bag" :for="$p.'name'">
    <input id="{{ $p }}name" name="name" maxlength="100" value="{{ $v['name'] ?? '' }}" class="input {{ $eb->has('name') ? 'is-invalid' : '' }}" required placeholder="e.g. All-purpose flour">
</x-field>
<div class="grid gap-x-4 md:grid-cols-2">
    <x-field name="unit" label="Unit" :bag="$bag" :for="$p.'unit'">
        <select id="{{ $p }}unit" name="unit" class="select {{ $eb->has('unit') ? 'is-invalid' : '' }}" required>
            <option value="">Choose...</option>
            @foreach ($units as $unit)
                <option value="{{ $unit }}" @selected(($v['unit'] ?? '') === $unit)>{{ $unit }}</option>
            @endforeach
        </select>
    </x-field>
    <x-field name="reorder_level" label="Reorder level" :bag="$bag" :for="$p.'reorder_level'" hint="Marked low stock at or below this amount.">
        <input id="{{ $p }}reorder_level" type="number" name="reorder_level" min="0" step="0.01" value="{{ $v['reorder_level'] ?? '' }}" class="input {{ $eb->has('reorder_level') ? 'is-invalid' : '' }}" required>
    </x-field>
</div>
@if ($withStatus ?? false)
    <x-field name="is_active" label="Status" :bag="$bag" :for="$p.'is_active'" hint="Inactive ingredients keep their history but can't be used on new entries.">
        <select id="{{ $p }}is_active" name="is_active" class="select">
            <option value="1" @selected(($v['is_active'] ?? '1') == '1')>Active</option>
            <option value="0" @selected(($v['is_active'] ?? '1') == '0')>Inactive</option>
        </select>
    </x-field>
@endif
