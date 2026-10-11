{{-- Shared customer fields. $bag = error bag, $v = values, $p = id prefix --}}
@php
    use App\Support\Address;
    $eb = $errors->getBag($bag);
@endphp
<div class="grid gap-x-4 md:grid-cols-2">
    <x-field name="first_name" label="First name" :bag="$bag" :for="$p.'first_name'">
        <input id="{{ $p }}first_name" name="first_name" class="input {{ $eb->has('first_name') ? 'is-invalid' : '' }}" value="{{ $v['first_name'] ?? '' }}" maxlength="50" required>
    </x-field>
    <x-field name="last_name" label="Last name" :bag="$bag" :for="$p.'last_name'">
        <input id="{{ $p }}last_name" name="last_name" class="input {{ $eb->has('last_name') ? 'is-invalid' : '' }}" value="{{ $v['last_name'] ?? '' }}" maxlength="50" required>
    </x-field>
    <x-field name="middle_name" label="Middle name" :bag="$bag" :for="$p.'middle_name'" optional>
        <input id="{{ $p }}middle_name" name="middle_name" class="input {{ $eb->has('middle_name') ? 'is-invalid' : '' }}" value="{{ $v['middle_name'] ?? '' }}" maxlength="50">
    </x-field>
    <x-field name="contact_number" label="Contact number" :bag="$bag" :for="$p.'contact_number'" hint="11 digits, mobile number.">
        <input id="{{ $p }}contact_number" name="contact_number" class="input {{ $eb->has('contact_number') ? 'is-invalid' : '' }}" value="{{ $v['contact_number'] ?? '' }}" inputmode="tel" placeholder="0917 123 4567" maxlength="20" required>
    </x-field>
    <x-field name="facebook_name" label="Facebook name" :bag="$bag" :for="$p.'facebook_name'" optional class="md:col-span-2">
        <input id="{{ $p }}facebook_name" name="facebook_name" class="input {{ $eb->has('facebook_name') ? 'is-invalid' : '' }}" value="{{ $v['facebook_name'] ?? '' }}" maxlength="100">
    </x-field>
</div>

<div class="mt-[18px]">
    <div class="mb-2 flex flex-col gap-0.5">
        <span class="label mb-0">Address <span class="opt">(optional)</span></span>
        <span class="hint mt-0">Used for deliveries unless the order gives another address.</span>
    </div>
    {{-- The map and address fields are a React component; "Edit" buttons fill it through a react:fill event. --}}
    <x-react name="AddressFields" :props="[
        'idp' => $p.'addr_',
        'values' => $v,
        'errors' => collect($eb->toArray())->map(fn ($m) => $m[0])->all(),
        'map' => Address::mapConfig(),
        'defaults' => Address::defaults(),
    ]" />
</div>
