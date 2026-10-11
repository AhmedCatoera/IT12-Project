<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Payment;
use App\Models\User;
use App\Support\Address;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * All order status changes go through here, so the rules live in one place:
 * - Confirmed is set only when the 50% down payment is reached.
 * - In production / Ready for release are set only by the job order.
 * - Completed needs the order to be fully paid.
 * - Cancelled needs a reason and also cancels an unfinished job order.
 */
class OrderWorkflow
{
    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function create(array $data, array $items, User $by): Order
    {
        return DB::transaction(function () use ($data, $items, $by) {
            $order = Order::create([
                'order_number' => 'TMP-' . Str::random(12),
                'customer_id' => $data['customer_id'],
                'created_by' => $by->id,
                'order_date' => now()->toDateString(),
                'scheduled_at' => $data['scheduled_at'],
                'order_type' => $data['order_type'],
                ...self::kindFields($data, today()),
                ...self::feeFields($data),
                ...Address::withPrefix($data['place'] ?? [], 'delivery_'),
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
            ]);

            // Built from the id, so two orders can never share a number.
            $order->update([
                'order_number' => sprintf('SN-%s-%04d', $order->order_date->format('Y'), $order->id),
            ]);

            foreach ($items as $item) {
                $order->items()->create($item);
            }

            return $order;
        });
    }

    public function recordPayment(Order $order, array $data, User $by): Payment
    {
        return DB::transaction(function () use ($order, $data, $by) {
            // Lock the order row so two quick clicks cannot both pass the balance check.
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $order->load('items', 'payments');

            if ($order->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'amount' => 'This order is cancelled, so payments can no longer be recorded.',
                ])->errorBag('payment');
            }

            $balance = $order->balance_due;
            $amount = round((float) $data['amount'], 2);

            if ($balance <= 0) {
                throw ValidationException::withMessages(['amount' => 'This order is already fully paid.'])->errorBag('payment');
            }

            if ($amount > $balance) {
                throw ValidationException::withMessages([
                    'amount' => 'The amount cannot be more than the balance of ₱' . number_format($balance, 2) . '.',
                ])->errorBag('payment');
            }

            // Money paid before the order reaches what confirms it (50% on a regular order, the
            // whole total on a rush order) is the down payment.
            $type = match (true) {
                $order->payments->isEmpty() && $amount >= $order->total_amount => 'full_payment',
                $order->total_paid < $order->down_payment_required => 'down_payment',
                default => 'balance_payment',
            };

            $payment = $order->payments()->create([
                'received_by' => $by->id,
                'payment_type' => $type,
                'payment_method' => $data['payment_method'],
                'amount' => $amount,
                'reference_no' => $data['reference_no'] ?? null,
                'proof_image' => $data['proof_image'] ?? null,
                'paid_at' => $data['paid_at'],
                'notes' => $data['notes'] ?? null,
            ]);

            $order->load('payments');

            if ($order->status === 'pending' && $order->total_paid >= $order->down_payment_required) {
                $order->update(['status' => 'confirmed']);
            }

            return $payment;
        });
    }

    public function changeStatus(Order $order, string $to, ?string $reason): void
    {
        DB::transaction(function () use ($order, $to, $reason) {
            // Re-read and lock the order, so two people changing it at once can't both pass the checks.
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $order->load('items', 'payments', 'jobOrder');

            $this->applyStatus($order, $to, $reason);
        });
    }

    private function applyStatus(Order $order, string $to, ?string $reason): void
    {
        if (! in_array($to, $order->manualNextStatuses(), true)) {
            throw ValidationException::withMessages([
                'status' => 'An order that is "' . $order->status_label . '" cannot be moved to "'
                    . (Order::STATUSES[$to] ?? $to) . '".',
            ])->errorBag('orderStatus');
        }

        if ($to === 'completed' && ! $order->isFullyPaid()) {
            throw ValidationException::withMessages([
                'status' => 'Collect the remaining balance of ₱' . number_format($order->balance_due, 2) . ' before completing this order.',
            ])->errorBag('orderStatus');
        }

        if ($to === 'cancelled') {
            if (trim((string) $reason) === '') {
                throw ValidationException::withMessages(['cancel_reason' => 'Give a reason for cancelling.'])->errorBag('orderStatus');
            }

            // A fully paid order gets half the total back (config refund_percent); one that only
            // paid its down payment gets nothing. The Owner or Staff tick "Refund given" later.
            $order->update([
                'status' => 'cancelled',
                'cancel_reason' => trim($reason),
                'refund_amount' => $order->refundDue(),
                'refunded_at' => null,
            ]);

            if ($order->jobOrder && in_array($order->jobOrder->status, ['pending', 'in_progress'], true)) {
                $order->jobOrder->update(['status' => 'cancelled']);
            }

            return;
        }

        $order->update(['status' => $to]);
    }

    /**
     * Regular or rush comes from the dates (due within a day of the order date = rush), never
     * from a choice on the form. A rush order keeps its fee percent; a regular one has none.
     */
    public static function kindFields(array $data, \DateTimeInterface|string $orderDate): array
    {
        $kind = Order::kindFor($data['scheduled_at'], $orderDate);

        return [
            'order_kind' => $kind,
            'rush_fee_percent' => $kind === 'rush' ? round((float) ($data['rush_fee_percent'] ?? config('sweetnest.rush_fee_default')), 2) : null,
        ];
    }

    /** The delivery fee typed from the delivery app; none on pickups. */
    public static function feeFields(array $data): array
    {
        $delivery = ($data['order_type'] ?? null) === 'delivery';

        return ['delivery_fee' => $delivery && filled($data['delivery_fee'] ?? null) ? round((float) $data['delivery_fee'], 2) : null];
    }

    /** Marks the cancelled order's refund as handed back. */
    public function markRefunded(Order $order): void
    {
        if (! $order->needsRefund()) {
            throw ValidationException::withMessages(['refund' => 'This order has no refund waiting.'])->errorBag('orderStatus');
        }

        $order->update(['refunded_at' => now()]);
    }

    /**
     * One item row from the form: a listed product (product_id) or a custom item
     * (custom_name + custom_category) with the price typed by the Owner or Staff.
     */
    public static function itemFromInput(array $row, ?string $imagePath, ?Product $product = null): array
    {
        if ($product === null && ! empty($row['product_id'])) {
            $product = Product::find($row['product_id']);
        }
        $custom = $product === null;
        $category = $custom ? ($row['custom_category'] ?? null) : $product->category;

        return OrderItem::cleanSpecs($row, $category) + [
            'product_id' => $product?->id,
            'custom_name' => $custom ? trim((string) ($row['custom_name'] ?? '')) : null,
            'custom_category' => $custom ? $category : null,
            'reference_image' => OrderItem::takesSpecsFor($category) ? $imagePath : null,
            'quantity' => (int) $row['quantity'],
            'unit_price' => round((float) $row['unit_price'], 2),
        ];
    }
}
