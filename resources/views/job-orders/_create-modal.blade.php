{{-- Create a job order. Pass $forOrder to post to that order; with null, the opener button sets the action. --}}
@php
    $forOrder = $forOrder ?? null;
    $jobBag = $errors->jobOrder;
    $retryOrder = ! $forOrder && old('_order_id') ? \App\Models\Order::find(old('_order_id')) : null;
    $jobAction = $forOrder ? route('jobOrders.store', $forOrder) : ($retryOrder ? route('jobOrders.store', $retryOrder) : '#');
    $latestDate = ($forOrder ?? $retryOrder)?->latestProductionDate()->toDateString();
    $defaultDate = $latestDate ?? today()->toDateString();
    $jobTitle = $retryOrder ? 'Job order for ' . $retryOrder->order_number : 'Create job order';
@endphp
<x-modal id="createJobOrder" :title="$jobTitle" :open="$jobBag->any()">
    <form method="POST" action="{{ $jobAction }}">
        @csrf
        <input type="hidden" name="_order_id" value="{{ old('_order_id') }}">
        <div class="modal-body">
            <p class="text-ink-2 mb-3.5">The job order tells the kitchen what to make and when. Staff log the ingredients used on it.</p>
            <x-field name="production_date" label="Production date" bag="jobOrder" for="jo_date" hint="The day the kitchen will make it, on or before the due date.">
                <input id="jo_date" type="date" name="production_date" value="{{ $jobBag->any() ? old('production_date') : $defaultDate }}" @if ($latestDate) max="{{ $latestDate }}" @endif class="input {{ $jobBag->has('production_date') ? 'is-invalid' : '' }}" required>
            </x-field>
            <x-field name="assigned_to" label="Assigned to" bag="jobOrder" for="jo_assigned" optional>
                <select id="jo_assigned" name="assigned_to" class="select">
                    <option value="">Not assigned yet</option>
                    @foreach ($staff as $person)
                        <option value="{{ $person->id }}" @selected((string) old('assigned_to') === (string) $person->id)>{{ $person->name }} ({{ $person->role_label }})</option>
                    @endforeach
                </select>
            </x-field>
            <x-field name="notes" label="Notes for the kitchen" bag="jobOrder" for="jo_notes" optional>
                <textarea id="jo_notes" name="notes" rows="2" maxlength="1000" class="textarea">{{ $jobBag->any() ? old('notes') : '' }}</textarea>
            </x-field>
        </div>
        <div class="modal-foot">
            <button type="button" class="btn" data-modal-close>Cancel</button>
            <button type="submit" class="btn btn-primary">Create job order</button>
        </div>
    </form>
</x-modal>
