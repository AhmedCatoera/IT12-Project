@extends('layouts.app')

@section('title', 'Users')

@section('content')
@php $me = auth()->user(); $createBag = $errors->createUser; $resetBag = $errors->resetPassword; @endphp
<div class="mb-[22px] flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold tracking-[-0.02em] max-md:text-[21px]">Users</h1>
        <p class="mt-1 text-ink-2">Owner and Staff accounts. Accounts are deactivated, never deleted.</p>
    </div>
    <div class="flex flex-wrap gap-2 print:hidden">
        <button type="button" class="btn btn-primary" data-modal-open="createUser"><x-icon name="plus" /> Add user</button>
    </div>
</div>

<div class="card">
    <div class="overflow-x-auto">
        <table class="table rwd">
            <thead><tr><th>Name</th><th>Role</th><th>Status</th><th class="actions"></th></tr></thead>
            <tbody>
                @foreach ($users as $user)
                    <tr class="{{ $user->is_active ? '' : 'row-muted' }}">
                        <td class="primary"><span class="font-semibold">{{ $user->name }}</span>@if ($user->is($me)) <span class="text-xs text-ink-2">(you)</span>@endif<span class="cell-sub">{{ $user->email }}</span></td>
                        <td>{{ $user->role_label }}</td>
                        <td><x-status :status="$user->is_active" type="active" /></td>
                        <td class="actions">
                            <button type="button" class="btn btn-sm" data-modal-open="resetPassword"
                                data-action="{{ route('users.resetPassword', $user) }}"
                                data-title="Reset password for {{ $user->name }}"
                                data-fill="{{ json_encode(['new_password' => '', 'new_password_confirmation' => '', '_edit_id' => $user->id]) }}">Reset password</button>
                            @unless ($user->is($me))
                                <form method="POST" action="{{ route('users.toggleStatus', $user) }}" class="inline"
                                    @if ($user->is_active) data-confirm="Deactivate {{ $user->name }}? They will be signed out and won't be able to log in." data-confirm-button="Deactivate" @endif>
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-sm {{ $user->is_active ? 'btn-danger' : 'btn-success' }}">{{ $user->is_active ? 'Deactivate' : 'Activate' }}</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $users->links() }}
</div>

<x-modal id="createUser" title="Add user" :open="$createBag->any()">
    <form method="POST" action="{{ route('users.store') }}">
        @csrf
        <div class="modal-body">
            <div class="grid gap-x-4 md:grid-cols-2">
                <x-field name="first_name" label="First name" bag="createUser" for="cu_first">
                    <input id="cu_first" name="first_name" value="{{ $createBag->any() ? old('first_name') : '' }}" maxlength="50" class="input {{ $createBag->has('first_name') ? 'is-invalid' : '' }}" required>
                </x-field>
                <x-field name="last_name" label="Last name" bag="createUser" for="cu_last">
                    <input id="cu_last" name="last_name" value="{{ $createBag->any() ? old('last_name') : '' }}" maxlength="50" class="input {{ $createBag->has('last_name') ? 'is-invalid' : '' }}" required>
                </x-field>
                <x-field name="middle_name" label="Middle name" bag="createUser" for="cu_middle" optional>
                    <input id="cu_middle" name="middle_name" value="{{ $createBag->any() ? old('middle_name') : '' }}" maxlength="50" class="input">
                </x-field>
                <x-field name="role" label="Role" bag="createUser" for="cu_role">
                    <select id="cu_role" name="role" class="select" required>
                        <option value="staff" @selected(old('role', 'staff') === 'staff')>Staff</option>
                        <option value="owner" @selected(old('role') === 'owner')>Owner</option>
                    </select>
                </x-field>
                <x-field name="email" label="Email" bag="createUser" for="cu_email" class="md:col-span-2">
                    <input id="cu_email" type="email" name="email" value="{{ $createBag->any() ? old('email') : '' }}" maxlength="255" class="input {{ $createBag->has('email') ? 'is-invalid' : '' }}" required autocomplete="off">
                </x-field>
                <x-field name="password" label="Password" bag="createUser" for="cu_password" hint="At least 8 characters.">
                    <input id="cu_password" type="password" name="password" minlength="8" class="input {{ $createBag->has('password') ? 'is-invalid' : '' }}" required autocomplete="new-password">
                </x-field>
                <x-field name="password_confirmation" label="Repeat password" bag="createUser" for="cu_password2">
                    <input id="cu_password2" type="password" name="password_confirmation" minlength="8" class="input" required autocomplete="new-password">
                </x-field>
            </div>
            <p class="hint">Staff can take orders, record payments, run job orders, log ingredients used and count stock. Only Owners can add stock, adjust stock, and see products, reports and users.</p>
        </div>
        <div class="modal-foot">
            <button type="button" class="btn" data-modal-close>Cancel</button>
            <button type="submit" class="btn btn-primary">Create account</button>
        </div>
    </form>
</x-modal>

<x-modal id="resetPassword" title="Reset password" :open="$resetBag->any()">
    <form method="POST" action="{{ old('_edit_id') ? route('users.resetPassword', old('_edit_id')) : '#' }}">
        @csrf @method('PATCH')
        <input type="hidden" name="_edit_id" value="{{ old('_edit_id') }}">
        <div class="modal-body">
            <x-field name="new_password" label="New password" bag="resetPassword" for="rp_password" hint="At least 8 characters. Share it with the person privately.">
                <input id="rp_password" type="password" name="new_password" minlength="8" class="input {{ $resetBag->has('new_password') ? 'is-invalid' : '' }}" required autocomplete="new-password">
            </x-field>
            <x-field name="new_password_confirmation" label="Repeat new password" bag="resetPassword" for="rp_password2">
                <input id="rp_password2" type="password" name="new_password_confirmation" minlength="8" class="input" required autocomplete="new-password">
            </x-field>
        </div>
        <div class="modal-foot">
            <button type="button" class="btn" data-modal-close>Cancel</button>
            <button type="submit" class="btn btn-primary">Reset password</button>
        </div>
    </form>
</x-modal>
@endsection
