@php $eb = $errors->getBag($bag); @endphp
<x-field name="name" label="Product name" :bag="$bag" :for="$p.'name'">
    <input id="{{ $p }}name" name="name" class="input {{ $eb->has('name') ? 'is-invalid' : '' }}" value="{{ $v['name'] ?? '' }}" maxlength="100" required placeholder="e.g. Chocolate Moist Cake">
</x-field>
<div class="grid gap-x-4 md:grid-cols-2">
    <x-field name="category" label="Category" :bag="$bag" :for="$p.'category'">
        <select id="{{ $p }}category" name="category" class="select {{ $eb->has('category') ? 'is-invalid' : '' }}" required>
            <option value="">Choose...</option>
            @foreach (\App\Models\Product::CATEGORIES as $key => $label)
                <option value="{{ $key }}" @selected(($v['category'] ?? '') === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </x-field>
    <x-field name="base_price" label="Base price (₱)" :bag="$bag" :for="$p.'base_price'" hint="The starting price. It can be changed per order.">
        <input id="{{ $p }}base_price" name="base_price" type="number" step="0.01" min="0" class="input {{ $eb->has('base_price') ? 'is-invalid' : '' }}" value="{{ $v['base_price'] ?? '' }}" required>
    </x-field>
</div>
@if ($withStatus ?? false)
    <x-field name="is_active" label="Status" :bag="$bag" :for="$p.'is_active'" hint="Inactive products stay on old orders but can't be picked for new ones.">
        <select id="{{ $p }}is_active" name="is_active" class="select">
            <option value="1" @selected(($v['is_active'] ?? '1') == '1')>Active (on the price list)</option>
            <option value="0" @selected(($v['is_active'] ?? '1') == '0')>Inactive (hidden from new orders)</option>
        </select>
    </x-field>
@endif
