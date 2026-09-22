<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    /**
     * Display a listing of orders with filters and search.
     */
    public function index(Request $request)
    {
        $status = $request->input('status');
        $type = $request->input('type');
        $search = $request->input('search');

        $orders = Order::query()
            ->with(['customer', 'items'])
            ->when($status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($type, function ($query, $type) {
                $query->where('order_type', $type);
            })
            ->when($search, function ($query, $search) {
                $query->where('order_number', 'like', "%{$search}%")
                      ->orWhereHas('customer', function ($q) use ($search) {
                          $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('contact_number', 'like', "%{$search}%");
                      });
            })
            ->orderBy('scheduled_date', 'asc')
            ->paginate(10)
            ->withQueryString();

        $statusCounts = [
            'all' => Order::count(),
            'pending' => Order::where('status', 'pending')->count(),
            'confirmed' => Order::where('status', 'confirmed')->count(),
            'in_production' => Order::where('status', 'in_production')->count(),
            'ready_for_release' => Order::where('status', 'ready_for_release')->count(),
            'out_for_delivery' => Order::where('status', 'out_for_delivery')->count(),
            'completed' => Order::where('status', 'completed')->count(),
        ];

        return view('orders.index', compact('orders', 'status', 'type', 'search', 'statusCounts'));
    }

    /**
     * Show the form for creating a new order.
     */
    public function create(Request $request)
    {
        $customers = Customer::orderBy('last_name')->orderBy('first_name')->get();
        $selectedCustomerId = $request->query('customer_id');

        return view('orders.create', compact('customers', 'selectedCustomerId'));
    }

    /**
     * Store a newly created order and its itemized line items.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'scheduled_date' => ['required', 'date'],
            'order_type' => ['required', 'in:pickup,delivery'],
            'delivery_address' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_type' => ['required', 'in:cake,pastry,souvenir'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.flavor' => ['nullable', 'string', 'max:100'],
            'items.*.size' => ['nullable', 'string', 'max:100'],
            'items.*.design_theme' => ['nullable', 'string', 'max:255'],
            'items.*.custom_names' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.reference_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        return DB::transaction(function () use ($request, $validated) {
            // Compute financial totals
            $totalAmount = 0;
            foreach ($validated['items'] as $item) {
                $totalAmount += (float)$item['quantity'] * (float)$item['unit_price'];
            }

            $downPaymentRequired = round($totalAmount * 0.50, 2);

            // Generate human-friendly sequential order number
            $orderCountThisYear = Order::whereYear('created_at', now()->year)->count() + 1;
            $orderNumber = 'SN-' . now()->year . '-' . str_pad($orderCountThisYear, 4, '0', STR_PAD_LEFT);

            // If delivery is chosen and address is empty, copy customer's default address
            $deliveryAddress = $validated['delivery_address'];
            if ($validated['order_type'] === 'delivery' && empty($deliveryAddress)) {
                $customer = Customer::find($validated['customer_id']);
                $deliveryAddress = $customer?->address;
            }

            // Create Order
            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_id' => $validated['customer_id'],
                'created_by' => Auth::id(),
                'order_date' => now()->toDateString(),
                'scheduled_date' => $validated['scheduled_date'],
                'order_type' => $validated['order_type'],
                'delivery_address' => $deliveryAddress,
                'status' => 'pending',
                'total_amount' => $totalAmount,
                'down_payment_required' => $downPaymentRequired,
                'total_paid' => 0.00,
                'payment_status' => 'unpaid',
                'notes' => $validated['notes'] ?? null,
            ]);

            // Create Order Line Items
            foreach ($validated['items'] as $index => $itemData) {
                $imagePath = null;
                if ($request->hasFile("items.{$index}.reference_image")) {
                    $file = $request->file("items.{$index}.reference_image");
                    $imagePath = $file->store('order_references', 'public');
                }

                $subtotal = (float)$itemData['quantity'] * (float)$itemData['unit_price'];

                OrderItem::create([
                    'order_id' => $order->id,
                    'item_type' => $itemData['item_type'],
                    'item_name' => $itemData['item_name'],
                    'flavor' => $itemData['flavor'] ?? null,
                    'size' => $itemData['size'] ?? null,
                    'design_theme' => $itemData['design_theme'] ?? null,
                    'custom_names' => $itemData['custom_names'] ?? null,
                    'reference_image' => $imagePath,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'subtotal' => $subtotal,
                ]);
            }

            return redirect()->route('orders.show', $order)
                ->with('success', "Order {$order->order_number} has been recorded successfully. 50% down payment required: ₱" . number_format($downPaymentRequired, 2));
        });
    }

    /**
     * Display the specified order details, invoices, and production status.
     */
    public function show(Order $order)
    {
        $order->load(['customer', 'creator', 'items', 'payments.receiver', 'productions.baker']);

        return view('orders.show', compact('order'));
    }

    /**
     * Show the form for editing basic order details.
     */
    public function edit(Order $order)
    {
        $customers = Customer::orderBy('last_name')->orderBy('first_name')->get();

        return view('orders.edit', compact('order', 'customers'));
    }

    /**
     * Update the specified order.
     */
    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'scheduled_date' => ['required', 'date'],
            'order_type' => ['required', 'in:pickup,delivery'],
            'delivery_address' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:pending,confirmed,in_production,ready_for_release,out_for_delivery,completed,cancelled'],
            'notes' => ['nullable', 'string'],
        ]);

        $order->update($validated);

        return redirect()->route('orders.show', $order)
            ->with('success', 'Order information updated successfully.');
    }

    /**
     * Quick status update for order workflow.
     */
    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,confirmed,in_production,ready_for_release,out_for_delivery,completed,cancelled'],
        ]);

        $order->update(['status' => $validated['status']]);

        return back()->with('success', "Order status updated to " . strtoupper(str_replace('_', ' ', $validated['status'])));
    }

    /**
     * Remove the specified order if no payments have been recorded.
     */
    public function destroy(Order $order)
    {
        if ($order->payments()->exists()) {
            return back()->with('error', 'Cannot delete an order that has recorded financial transactions. Please mark it as Cancelled instead.');
        }

        $order->delete();

        return redirect()->route('orders.index')
            ->with('success', 'Order record deleted successfully.');
    }
}
