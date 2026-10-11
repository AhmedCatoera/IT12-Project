<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Services\OrderWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $method = $this->textFilter($request, 'method');
        $method = array_key_exists((string) $method, Payment::METHODS) ? $method : null;
        $from = $this->dateFilter($request, 'from');
        $to = $this->dateFilter($request, 'to');
        $search = (string) $this->textFilter($request, 'search');

        $base = Payment::query()
            ->when($method, fn ($q) => $q->where('payment_method', $method))
            ->when($from, fn ($q) => $q->where('paid_at', '>=', $from->copy()->startOfDay()))
            ->when($to, fn ($q) => $q->where('paid_at', '<=', $to->copy()->endOfDay()))
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('reference_no', 'like', "%{$search}%")
                    ->orWhereHas('order', fn ($o) => $o->search($search));
            }));

        // Totals come from the whole filtered set, not just the page on screen.
        $summary = [
            'total' => (float) (clone $base)->sum('amount'),
            'count' => (clone $base)->count(),
            'by_method' => (clone $base)->selectRaw('payment_method, SUM(amount) as total')
                ->groupBy('payment_method')->pluck('total', 'payment_method'),
        ];

        $payments = (clone $base)->with(['order.customer', 'receiver'])
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->paginateOrLast(15)
            ->withQueryString();

        return view('payments.index', compact('payments', 'summary', 'method', 'from', 'to', 'search'));
    }

    public function store(Request $request, Order $order, OrderWorkflow $workflow)
    {
        // Cash needs no proof. GCash needs the reference number or a screenshot (or both).
        $data = $request->validateWithBag('payment', [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', Rule::in(array_keys(Payment::METHODS))],
            'reference_no' => ['exclude_unless:payment_method,gcash', 'nullable', 'required_without:proof_image', 'string', 'max:100'],
            'proof_image' => ['exclude_unless:payment_method,gcash', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:' . OrderItem::photoMaxKb()],
            'paid_at' => ['required', 'date', 'before_or_equal:now'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'reference_no.required_without' => 'Enter the GCash reference number or attach a screenshot of the payment.',
            'paid_at.before_or_equal' => 'The payment date cannot be in the future.',
        ], ['proof_image' => 'screenshot']);

        if ($request->hasFile('proof_image') && $data['payment_method'] === 'gcash') {
            $data['proof_image'] = $request->file('proof_image')->store('payment-proofs', 'public');
        } else {
            unset($data['proof_image']);
        }

        $wasPending = $order->status === 'pending';
        $payment = $workflow->recordPayment($order, $data, $request->user());
        $order->refresh();

        $message = 'Payment of ₱' . number_format($payment->amount, 2) . ' recorded.';
        if ($wasPending && $order->status === 'confirmed') {
            $message .= ' The down payment is complete, so the order is now confirmed.';
        }

        // Land on the order's Payments tab so the new payment is in view.
        return redirect()->to(route('orders.show', $order) . '#payments')->with('success', $message);
    }

    /** Shows a GCash screenshot without needing "php artisan storage:link". */
    public function proof(Payment $payment)
    {
        abort_unless($payment->proof_image && Storage::disk('public')->exists($payment->proof_image), 404);

        return Storage::disk('public')->response($payment->proof_image);
    }
}
