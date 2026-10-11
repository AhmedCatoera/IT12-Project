<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Support\Address;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $status = $this->textFilter($request, 'status');
        $status = in_array($status, ['active', 'inactive', 'all'], true) ? $status : 'active';
        $search = $this->textFilter($request, 'search');

        $customers = Customer::query()
            ->withCount('orders')
            ->when($status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->search($search)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginateOrLast(15)
            ->withQueryString();

        $counts = [
            'active' => Customer::where('is_active', true)->count(),
            'inactive' => Customer::where('is_active', false)->count(),
        ];

        return view('customers.index', compact('customers', 'status', 'search', 'counts'));
    }

    public function store(Request $request)
    {
        $request->merge(['contact_number' => Customer::normalizeContact($request->input('contact_number'))]);
        $data = $this->withAddress($request->validateWithBag('createCustomer', $this->rules(), $this->messages(), Address::attributes()));

        $customer = Customer::create($data + ['is_active' => true]);

        if ($request->boolean('return_to_order')) {
            return redirect()->route('orders.create', ['customer_id' => $customer->id])
                ->with('success', "{$customer->name} was added. You can continue the order.");
        }

        return redirect()->route('customers.show', $customer)->with('success', 'Customer added.');
    }

    public function show(Customer $customer)
    {
        $orders = $customer->orders()
            ->withTotals()
            ->with('items.product')
            ->orderByDesc('order_date')
            ->orderByDesc('id')
            ->paginateOrLast(10);

        // The totals cover every order, not just the page on screen.
        $counted = $customer->orders()->withTotals()->where('status', '!=', 'cancelled')->get();

        $summary = [
            'orders' => $counted->count(),
            'total' => $counted->sum('total_amount'),
            'paid' => $counted->sum('total_paid'),
            'balance' => $counted->sum('balance_due'),
        ];

        return view('customers.show', compact('customer', 'orders', 'summary'));
    }

    public function update(Request $request, Customer $customer)
    {
        $request->merge(['contact_number' => Customer::normalizeContact($request->input('contact_number'))]);
        $data = $this->withAddress($request->validateWithBag('editCustomer', $this->rules($customer->id), $this->messages(), Address::attributes()));

        $customer->update($data);

        return redirect()->route('customers.show', $customer)->with('success', 'Customer details updated.');
    }

    /** Deactivate instead of delete: history stays, the customer just leaves new-order lists. */
    public function toggleStatus(Customer $customer)
    {
        $customer->update(['is_active' => ! $customer->is_active]);

        return back()->with('success', $customer->is_active
            ? "{$customer->name} is active again."
            : "{$customer->name} was deactivated. Their past orders are kept.");
    }

    private function rules(?int $ignoreId = null): array
    {
        return [
            'first_name' => ['required', 'string', 'max:50'],
            'middle_name' => ['nullable', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'contact_number' => ['required', 'regex:/^\d{11}$/', Rule::unique('customers', 'contact_number')->ignore($ignoreId)],
            'facebook_name' => ['nullable', 'string', 'max:100'],
        ] + Address::rules();
    }

    /** Clean address parts: an address with nothing but the default city and province is saved as no address. */
    private function withAddress(array $data): array
    {
        return Arr::except($data, Address::PARTS) + Address::fromInput($data);
    }

    private function messages(): array
    {
        return [
            'contact_number.regex' => 'The contact number must be 11 digits, like 0917 123 4567.',
            'contact_number.unique' => 'Another customer already uses this number.',
        ] + Address::messages();
    }
}
