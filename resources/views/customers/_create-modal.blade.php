{{-- "Add customer" modal. Set $returnToOrder to continue an order after saving. --}}
<x-modal id="createCustomer" title="Add customer" wide :open="$errors->createCustomer->any()">
    <form method="POST" action="{{ route('customers.store') }}">
        @csrf
        @if (! empty($returnToOrder))
            <input type="hidden" name="return_to_order" value="1">
        @endif
        <div class="modal-body">
            @include('customers._fields', ['bag' => 'createCustomer', 'p' => 'cc_', 'v' => $errors->createCustomer->any() ? old() : []])
        </div>
        <div class="modal-foot">
            <button type="button" class="btn" data-modal-close>Cancel</button>
            <button type="submit" class="btn btn-primary">Save customer</button>
        </div>
    </form>
</x-modal>
