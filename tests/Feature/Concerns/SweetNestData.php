<?php

namespace Tests\Feature\Concerns;

use App\Models\Customer;
use App\Models\Ingredient;
use App\Models\JobOrder;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StockIn;
use App\Models\User;
use App\Services\JobOrderWorkflow;
use App\Services\OrderWorkflow;

/** Small helpers so each test reads like the shop's real steps. */
trait SweetNestData
{
    private static int $seq = 0;

    protected function owner(array $attributes = []): User
    {
        return User::factory()->create(['role' => 'owner'] + $attributes);
    }

    protected function staff(array $attributes = []): User
    {
        return User::factory()->create(['role' => 'staff'] + $attributes);
    }

    protected function customer(array $attributes = []): Customer
    {
        $n = ++self::$seq;

        return Customer::create($attributes + [
            'first_name' => 'Customer',
            'last_name' => 'Number' . $n,
            'contact_number' => '0917' . str_pad((string) $n, 7, '0', STR_PAD_LEFT),
            'street' => 'Purok ' . $n . ', Bayabas Rd.',
            'barangay' => 'Bayabas',
            'city' => 'Davao City',
            'province' => 'Davao del Sur',
            'postal_code' => '8025',
            'latitude' => 7.0302,
            'longitude' => 125.5233,
            'is_active' => true,
        ]);
    }

    protected function product(float $price = 1000, array $attributes = []): Product
    {
        $n = ++self::$seq;

        return Product::create($attributes + [
            'category' => 'cake',
            'name' => 'Test Cake ' . $n,
            'base_price' => $price,
            'is_active' => true,
        ]);
    }

    /** An ingredient with an opening stock in (if $stock > 0). */
    protected function ingredient(float $stock = 0, array $attributes = []): Ingredient
    {
        $n = ++self::$seq;

        $ingredient = Ingredient::create($attributes + [
            'name' => 'Ingredient ' . $n,
            'unit' => 'kg',
            'reorder_level' => 1,
            'is_active' => true,
        ]);

        if ($stock > 0) {
            $receipt = StockIn::create(['received_date' => today()->subDay(), 'receipt_no' => 'OR-' . $n]);
            $receipt->items()->create(['ingredient_id' => $ingredient->id, 'quantity' => $stock, 'unit_cost' => 10]);
        }

        return $ingredient;
    }

    /** A new order (Awaiting down payment) with one item of the given price. */
    protected function order(User $by, float $price = 1000, array $attributes = [], int $quantity = 1): Order
    {
        $customer = $attributes['customer'] ?? $this->customer();
        unset($attributes['customer']);

        $data = $attributes + [
            'customer_id' => $customer->id,
            'scheduled_at' => now()->addDays(3)->setTime(14, 0)->format('Y-m-d H:i:s'),
            'order_type' => 'pickup',
        ];

        // Deliveries go to the customer's saved address and pickups to the shop, unless the test gives a place.
        if (! isset($data['place'])) {
            $data['place'] = $data['order_type'] === 'delivery' ? $customer->address_parts : Order::shopAddress();
        }

        $item = OrderWorkflow::itemFromInput([
            'product_id' => $this->product($price)->id,
            'quantity' => $quantity,
            'unit_price' => $price,
        ], null);

        return app(OrderWorkflow::class)->create($data, [$item], $by)->fresh();
    }

    protected function pay(Order $order, float $amount, User $by, string $method = 'cash', ?string $paidAt = null): Payment
    {
        return app(OrderWorkflow::class)->recordPayment($order, [
            'amount' => $amount,
            'payment_method' => $method,
            'reference_no' => $method === 'gcash' ? 'REF-' . (++self::$seq) : null,
            'paid_at' => $paidAt ?? now()->format('Y-m-d H:i:s'),
        ], $by);
    }

    /** Order with what confirms it paid (50% down on a regular order, the whole total on a rush one). */
    protected function confirmedOrder(User $by, float $price = 1000, array $attributes = []): Order
    {
        $order = $this->order($by, $price, $attributes);
        $this->pay($order, Order::withTotals()->find($order->id)->down_payment_required, $by);

        return $order->fresh();
    }

    protected function jobOrder(Order $order, ?User $assignee = null): JobOrder
    {
        return app(JobOrderWorkflow::class)->create($order, [
            'production_date' => today()->toDateString(),
            'assigned_to' => $assignee?->id,
        ]);
    }

    /** Confirmed order whose job order has been started (baking). */
    protected function startedJob(User $by, float $price = 1000): JobOrder
    {
        $job = $this->jobOrder($this->confirmedOrder($by, $price));
        app(JobOrderWorkflow::class)->apply($job, 'start');

        return $job->fresh();
    }

    /** Job order marked as done: the point where the ingredients used get logged. */
    protected function doneJob(User $by, float $price = 1000): JobOrder
    {
        $job = $this->startedJob($by, $price);
        app(JobOrderWorkflow::class)->apply($job, 'complete');

        return $job->fresh();
    }
}
