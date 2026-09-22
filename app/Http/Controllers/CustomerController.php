<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers with search and pagination.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $customers = Customer::query()
            ->withCount('orders')
            ->when($search, function ($query, $search) {
                $query->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('contact_number', 'like', "%{$search}%")
                      ->orWhere('facebook_name', 'like', "%{$search}%");
            })
            ->orderBy('last_name', 'asc')
            ->orderBy('first_name', 'asc')
            ->paginate(10)
            ->withQueryString();

        return view('customers.index', compact('customers', 'search'));
    }

    /**
     * Show the form for creating a new customer.
     */
    public function create()
    {
        return view('customers.create');
    }

    /**
     * Store a newly created customer in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:50'],
            'middle_name' => ['nullable', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'contact_number' => ['required', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
            'facebook_name' => ['nullable', 'string', 'max:100'],
        ]);

        $customer = Customer::create($validated);

        return redirect()->route('customers.show', $customer)
            ->with('success', 'Customer record created successfully.');
    }

    /**
     * Display the specified customer profile and order history.
     */
    public function show(Customer $customer)
    {
        $customer->load(['orders' => function ($query) {
            $query->latest();
        }, 'orders.items', 'orders.payments']);

        $totalSpent = $customer->orders->where('status', '!=', 'cancelled')->sum('total_amount');
        $totalPaid = $customer->orders->sum('total_paid');

        return view('customers.show', compact('customer', 'totalSpent', 'totalPaid'));
    }

    /**
     * Show the form for editing the specified customer.
     */
    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    /**
     * Update the specified customer in storage.
     */
    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:50'],
            'middle_name' => ['nullable', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'contact_number' => ['required', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
            'facebook_name' => ['nullable', 'string', 'max:100'],
        ]);

        $customer->update($validated);

        return redirect()->route('customers.show', $customer)
            ->with('success', 'Customer information updated successfully.');
    }

    /**
     * Remove the specified customer if they have no linked orders.
     */
    public function destroy(Customer $customer)
    {
        if ($customer->orders()->exists()) {
            return back()->with('error', 'Cannot delete this customer because they have existing order records. Historical data must be preserved.');
        }

        $customer->delete();

        return redirect()->route('customers.index')
            ->with('success', 'Customer record removed successfully.');
    }
}
