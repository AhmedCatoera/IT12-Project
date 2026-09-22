<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * Display a listing of all payment transactions and financial collections.
     */
    public function index(Request $request)
    {
        $method = $request->input('method');
        $type = $request->input('type');
        $search = $request->input('search');

        $payments = Payment::query()
            ->with(['order.customer', 'receiver'])
            ->when($method, function ($query, $method) {
                $query->where('payment_method', $method);
            })
            ->when($type, function ($query, $type) {
                $query->where('payment_type', $type);
            })
            ->when($search, function ($query, $search) {
                $query->where('reference_number', 'like', "%{$search}%")
                      ->orWhereHas('order', function ($q) use ($search) {
                          $q->where('order_number', 'like', "%{$search}%")
                            ->orWhereHas('customer', function ($cq) use ($search) {
                                $cq->where('first_name', 'like', "%{$search}%")
                                   ->orWhere('last_name', 'like', "%{$search}%");
                            });
                      });
            })
            ->latest('payment_date')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total_collected' => Payment::sum('amount'),
            'gcash_collected' => Payment::where('payment_method', 'gcash')->sum('amount'),
            'cash_collected' => Payment::where('payment_method', 'cash')->sum('amount'),
            'total_transactions' => Payment::count(),
        ];

        return view('payments.index', compact('payments', 'method', 'type', 'search', 'stats'));
    }

    /**
     * Show the form for recording a new payment for an order.
     */
    public function create(Request $request, Order $order)
    {
        $order->load('customer');

        if ($order->balance_due <= 0) {
            return redirect()->route('orders.show', $order)
                ->with('error', "Order {$order->order_number} is already fully paid.");
        }

        // Determine suggested payment type and suggested amount
        if ($order->total_paid == 0) {
            $defaultType = 'down_payment';
            $suggestedAmount = $order->down_payment_required;
        } else {
            $defaultType = 'balance_payment';
            $suggestedAmount = $order->balance_due;
        }

        return view('payments.create', compact('order', 'defaultType', 'suggestedAmount'));
    }

    /**
     * Store a newly recorded payment transaction and update order payment status.
     */
    public function store(Request $request)
    {
        $order = Order::findOrFail($request->input('order_id'));

        $validated = $request->validate([
            'order_id' => ['required', 'exists:orders,id'],
            'payment_type' => ['required', 'in:down_payment,balance_payment,full_payment'],
            'amount' => ['required', 'numeric', 'min:1', 'max:' . ($order->balance_due + 0.01)],
            'payment_method' => ['required', 'in:cash,gcash,bank_transfer,other'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'payment_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'amount.max' => 'The payment amount cannot exceed the remaining balance due of ₱' . number_format($order->balance_due, 2),
        ]);

        DB::transaction(function () use ($validated, $order) {
            // 1. Create Payment Transaction Record
            Payment::create([
                'order_id' => $order->id,
                'received_by' => Auth::id(),
                'payment_type' => $validated['payment_type'],
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'reference_number' => $validated['reference_number'] ?? null,
                'payment_date' => $validated['payment_date'],
                'notes' => $validated['notes'] ?? null,
            ]);

            // 2. Recalculate total paid on order
            $newTotalPaid = $order->payments()->sum('amount');
            $order->total_paid = $newTotalPaid;

            // 3. Update payment status
            if ($newTotalPaid >= (float)$order->total_amount) {
                $order->payment_status = 'fully_paid';
            } elseif ($newTotalPaid > 0) {
                $order->payment_status = 'partially_paid';
            } else {
                $order->payment_status = 'unpaid';
            }

            // 4. If order was pending and at least 50% down payment is received, auto-confirm order
            if ($order->status === 'pending' && $newTotalPaid >= (float)$order->down_payment_required) {
                $order->status = 'confirmed';
            }

            $order->save();
        });

        return redirect()->route('orders.show', $order)
            ->with('success', 'Payment of ₱' . number_format($validated['amount'], 2) . ' recorded successfully.');
    }
}
