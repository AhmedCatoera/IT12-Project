<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderWorkflow;
use App\Support\Address;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $tabs = ['open' => 'Open'] + Order::STATUSES + ['all' => 'All'];
        $tab = $this->textFilter($request, 'status');
        $tab = array_key_exists((string) $tab, $tabs) ? $tab : 'open';
        $type = $this->textFilter($request, 'type');
        $type = in_array($type, ['pickup', 'delivery'], true) ? $type : null;
        $search = $this->textFilter($request, 'search');

        $query = Order::withTotals()
            ->with('customer')
            ->when($tab === 'open', fn ($q) => $q->open())
            ->when(array_key_exists($tab, Order::STATUSES), fn ($q) => $q->where('status', $tab))
            ->when($type, fn ($q) => $q->where('order_type', $type))
            ->search($search);

        // Open work: nearest due date first. Finished orders: newest first.
        in_array($tab, ['completed', 'cancelled', 'all'], true)
            ? $query->orderByDesc('scheduled_at')
            : $query->orderBy('scheduled_at');

        $orders = $query->paginateOrLast(15)->withQueryString();

        $counts = Order::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $counts['open'] = collect(Order::OPEN_STATUSES)->sum(fn ($s) => $counts[$s] ?? 0);
        $counts['all'] = $counts->only(array_keys(Order::STATUSES))->sum();

        return view('orders.index', compact('orders', 'tabs', 'tab', 'type', 'search', 'counts'));
    }

    public function create(Request $request)
    {
        $customers = Customer::active()->orderBy('last_name')->orderBy('first_name')->get();
        $products = Product::active()->orderBy('category')->orderBy('name')->get();
        $selectedCustomerId = old('customer_id', $this->textFilter($request, 'customer_id'));

        return view('orders.create', compact('customers', 'products', 'selectedCustomerId'));
    }

    public function store(Request $request, OrderWorkflow $workflow)
    {
        // A row with a listed product is that product; stray custom fields are ignored.
        $request->merge(['items' => collect($request->input('items', []))->map(function ($row) {
            if (is_array($row) && ! empty($row['product_id'])) {
                unset($row['custom_name'], $row['custom_category']);
            }

            return $row;
        })->all()]);

        $data = $request->validate([
            'customer_id' => ['required', Rule::exists('customers', 'id')->where('is_active', true)],
            'scheduled_at' => ['required', 'date', 'after_or_equal:today'],
            'order_type' => ['required', Rule::in(['pickup', 'delivery'])],
        ] + $this->kindRules($request, today()) + $this->feeRules() + [
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            // Each item is a listed product or a custom one typed in (name + category).
            'items.*.product_id' => ['nullable', 'required_without:items.*.custom_name', Rule::exists('products', 'id')->where('is_active', true)],
            'items.*.custom_name' => ['nullable', 'required_without:items.*.product_id', 'string', 'max:120'],
            'items.*.custom_category' => ['nullable', 'required_with:items.*.custom_name', Rule::in(array_keys(Product::CATEGORIES))],
        ] + $this->specRules('items.*.') + [
            'items.*.design_theme' => ['nullable', 'string', 'max:255'],
            'items.*.inscription' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:999999'],
            'items.*.reference_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:' . OrderItem::photoMaxKb()],
        ] + $this->placeRules(), $this->messages(), [
            'customer_id' => 'customer',
            'scheduled_at' => 'due date and time',
            'order_type' => 'pickup or delivery',
            'rush_fee_percent' => 'rush fee',
            'delivery_fee' => 'delivery fee',
            'items.*.tiers' => 'tiers',
            'items.*.product_id' => 'product',
            'items.*.custom_name' => 'item name',
            'items.*.custom_category' => 'kind of item',
            'items.*.cake_type' => 'type of cake',
            'items.*.quantity' => 'quantity',
            'items.*.unit_price' => 'price',
            'items.*.reference_image' => 'reference photo',
        ] + Address::attributes('delivery_'));

        $data['place'] = $this->placeAddress($data, Customer::findOrFail($data['customer_id']));

        $total = collect($data['items'])->sum(fn ($row) => (int) $row['quantity'] * (float) $row['unit_price']);
        if ($total <= 0) {
            throw ValidationException::withMessages(['items' => 'The order total must be more than ₱0.00.']);
        }

        $products = Product::whereIn('id', collect($data['items'])->pluck('product_id')->filter())->get()->keyBy('id');

        $items = [];
        foreach ($data['items'] as $index => $row) {
            $product = ! empty($row['product_id']) ? ($products[$row['product_id']] ?? null) : null;
            $category = $product?->category ?? ($row['custom_category'] ?? null);
            $path = OrderItem::takesSpecsFor($category) && $request->hasFile("items.{$index}.reference_image")
                ? $request->file("items.{$index}.reference_image")->store('order-references', 'public')
                : null;

            $items[] = OrderWorkflow::itemFromInput($row, $path, $product);
        }

        $order = $workflow->create($data, $items, $request->user());
        $order->load('items');

        return redirect()->route('orders.show', $order)->with('success',
            "Order {$order->order_number} saved. " . ($order->isRush() ? 'Rush order: full payment needed, ₱' : 'Down payment needed: ₱') . number_format($order->down_payment_required, 2) . '.');
    }

    public function show(Order $order)
    {
        $order->load(['customer', 'creator', 'items.product', 'payments.receiver', 'jobOrder.assignee']);
        $staff = User::active()->orderBy('first_name')->get();

        return view('orders.show', compact('order', 'staff'));
    }

    public function update(Request $request, Order $order)
    {
        if (! $order->isOpen()) {
            return back()->with('error', 'Completed or cancelled orders can no longer be edited.');
        }

        if ($order->status === 'out_for_delivery' && $request->input('order_type') !== $order->order_type) {
            throw ValidationException::withMessages([
                'order_type' => 'This order is already out for delivery, so it has to stay a delivery.',
            ])->errorBag('editOrder');
        }

        $dateChanged = $request->input('scheduled_at') !== $order->scheduled_at->format('Y-m-d\TH:i');

        // Regular or rush follows the due date. The payment asked for was worked out from the
        // kind, so a date that would flip it is refused once money has been taken.
        if ($order->total_paid > 0 && $dateChanged && filled($request->input('scheduled_at')) && strtotime((string) $request->input('scheduled_at')) !== false) {
            $wouldBe = Order::kindFor($request->input('scheduled_at'), $order->order_date);
            if ($wouldBe !== $order->order_kind) {
                throw ValidationException::withMessages([
                    'scheduled_at' => 'That due date would make this a ' . strtolower(Order::KINDS[$wouldBe]) . ' order, and a payment was already recorded.',
                ])->errorBag('editOrder');
            }
        }

        $data = $request->validateWithBag('editOrder', [
            'scheduled_at' => array_merge(['required', 'date'], $dateChanged ? ['after_or_equal:today'] : []),
            'order_type' => ['required', Rule::in(['pickup', 'delivery'])],
        ] + $this->kindRules($request, $order->order_date) + $this->feeRules() + [
            'notes' => ['nullable', 'string', 'max:1000'],
        ] + $this->placeRules(), $this->messages(), ['scheduled_at' => 'due date and time', 'order_type' => 'pickup or delivery', 'rush_fee_percent' => 'rush fee', 'delivery_fee' => 'delivery fee'] + Address::attributes('delivery_'));

        // The kind is re-read from the dates only when the due date moved; otherwise the stored
        // kind stays (and a rush order takes the fee typed in the pop-up).
        $kind = $dateChanged
            ? OrderWorkflow::kindFields($data, $order->order_date)
            : ['order_kind' => $order->order_kind, 'rush_fee_percent' => $order->isRush() ? round((float) ($data['rush_fee_percent'] ?? $order->rush_fee_percent), 2) : null];

        // The rush fee is part of what was asked for too, so it is fixed after a payment.
        if ($order->total_paid > 0 && $order->isRush() && round((float) $kind['rush_fee_percent'], 2) !== round((float) $order->rush_fee_percent, 2)) {
            throw ValidationException::withMessages([
                'rush_fee_percent' => 'The rush fee can\'t change once a payment was recorded.',
            ])->errorBag('editOrder');
        }

        // Dropping the delivery fee (or the delivery) must not leave the customer having paid more than the order.
        $fee = OrderWorkflow::feeFields($data);
        $rushFee = $kind['order_kind'] === 'rush' ? round($order->items_total * (float) $kind['rush_fee_percent'] / 100, 2) : 0.0;
        $newTotal = round($order->items_total + $rushFee + (float) ($fee['delivery_fee'] ?? 0), 2);
        if ($newTotal < $order->total_paid) {
            throw ValidationException::withMessages([
                'delivery_fee' => 'The customer already paid ₱' . number_format($order->total_paid, 2) . ', more than the order would come to (₱' . number_format($newTotal, 2) . '). Keep the delivery fee, or cancel and refund instead.',
            ])->errorBag('editOrder');
        }

        // The kitchen's date must still be on or before the new due date.
        $job = $order->jobOrder;
        if ($job && $job->status !== 'cancelled' && $job->production_date->gt(\Illuminate\Support\Carbon::parse($data['scheduled_at'])->startOfDay())) {
            throw ValidationException::withMessages([
                'scheduled_at' => 'The job order is scheduled for ' . $job->production_date->format('D, M j') . ', so the due date can\'t be earlier than that.',
            ])->errorBag('editOrder');
        }

        $place = $this->placeAddress($data, $order->customer, 'editOrder');

        $order->update([
            'scheduled_at' => $data['scheduled_at'],
            'order_type' => $data['order_type'],
            'notes' => $data['notes'] ?? null,
        ] + $kind + $fee + Address::withPrefix($place, 'delivery_'));

        return back()->with('success', 'Order details updated.');
    }

    /** The cancelled order's refund was handed back to the customer. */
    public function markRefunded(Order $order, OrderWorkflow $workflow)
    {
        $workflow->markRefunded($order);

        return back()->with('success', 'Refund of ₱' . number_format($order->refund_amount, 2) . ' recorded as given.');
    }

    public function updateStatus(Request $request, Order $order, OrderWorkflow $workflow)
    {
        $data = $request->validateWithBag('orderStatus', [
            'status' => ['required', Rule::in(array_keys(Order::STATUSES))],
            'cancel_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $workflow->changeStatus($order, $data['status'], $data['cancel_reason'] ?? null);

        return back()->with('success', "Order {$order->order_number} is now \"" . Order::STATUSES[$data['status']] . '".');
    }

    /**
     * Flavor, size, design, inscription and photo can change until baking starts.
     * Product, quantity and price stay as agreed (the down payment was based on them).
     */
    public function updateItem(Request $request, OrderItem $item)
    {
        if (! $item->order->canEditItemDetails()) {
            return back()->with('error', 'Item details can only be changed before baking starts.');
        }

        if (! OrderItem::takesSpecsFor($item->category)) {
            return back()->with('error', 'Only cakes have details to edit.');
        }

        $data = $request->validateWithBag('editItem', $this->specRules() + [
            'design_theme' => ['nullable', 'string', 'max:255'],
            'inscription' => ['nullable', 'string', 'max:255'],
            'reference_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:' . OrderItem::photoMaxKb()],
            'remove_image' => ['nullable', 'boolean'],
        ], $this->messages(), ['reference_image' => 'reference photo', 'cake_type' => 'type of cake', 'tiers' => 'tiers']);

        $changes = OrderItem::cleanSpecs($data, $item->category);

        $oldImage = $item->reference_image;

        if ($request->hasFile('reference_image')) {
            $changes['reference_image'] = $request->file('reference_image')->store('order-references', 'public');
        } elseif ($request->boolean('remove_image')) {
            $changes['reference_image'] = null;
        }

        $item->update($changes);

        if (array_key_exists('reference_image', $changes) && $oldImage) {
            Storage::disk('public')->delete($oldImage);
        }

        return back()->with('success', "Updated the details of {$item->name}.");
    }

    /** Shows a reference photo without needing "php artisan storage:link". */
    public function itemImage(OrderItem $item)
    {
        abort_unless($item->reference_image && Storage::disk('public')->exists($item->reference_image), 404);

        return Storage::disk('public')->response($item->reference_image);
    }

    /**
     * The rush fee percent (above 0, up to 100) is needed exactly when the due date makes
     * the order a rush one; the kind itself is never posted, it comes from the dates.
     */
    private function kindRules(Request $request, \DateTimeInterface|string $orderDate): array
    {
        $rush = fn () => filled($request->input('scheduled_at'))
            && strtotime((string) $request->input('scheduled_at')) !== false
            && Order::kindFor($request->input('scheduled_at'), $orderDate) === 'rush';

        return [
            'rush_fee_percent' => [Rule::excludeIf(fn () => ! $rush()), 'required', 'numeric', 'gt:0', 'max:100'],
        ];
    }

    /** The delivery fee typed from the delivery app (Maxim); only on deliveries. */
    private function feeRules(): array
    {
        return ['delivery_fee' => ['exclude_unless:order_type,delivery', 'nullable', 'numeric', 'min:0', 'max:99999']];
    }

    /** Cake details: the type, flavor and size come from the lists in config/sweetnest.php. */
    private function specRules(string $prefix = ''): array
    {
        $flavors = array_merge(config('sweetnest.cake_flavors'), config('sweetnest.icing_flavors'));

        return [
            $prefix . 'cake_type' => ['nullable', Rule::in(array_keys(config('sweetnest.cake_types')))],
            $prefix . 'flavor' => ['nullable', 'string', 'max:100', Rule::in($flavors)],
            $prefix . 'size' => ['nullable', 'string', 'max:100', Rule::in(config('sweetnest.cake_sizes'))],
            $prefix . 'tiers' => ['nullable', 'integer', Rule::in(config('sweetnest.cake_tiers'))],
        ];
    }

    /** Rules for the place the order goes to (pickup spot or delivery address), in the delivery_* fields. */
    private function placeRules(): array
    {
        return Address::rules('delivery_');
    }

    /**
     * The place kept with the order. Pickup: the spot typed on the form, or the shop when
     * nothing was typed. Delivery: the address typed, or a copy of the customer's saved
     * address; either way it has to be in the delivery city (config delivery_city).
     */
    private function placeAddress(array $data, Customer $customer, string $bag = 'default'): array
    {
        $typed = Address::fromInput($data, 'delivery_');
        $delivery = $data['order_type'] === 'delivery';

        if (Address::isBlank($typed)) {
            if (! $delivery) {
                return Order::shopAddress();
            }
            if (! $customer->has_address) {
                throw ValidationException::withMessages([
                    'delivery_street' => 'Enter the delivery address. This customer has no saved address yet.',
                ])->errorBag($bag);
            }
            $place = $customer->address_parts;
        } else {
            // An address typed by hand must be complete.
            Validator::make($data, Address::rules('delivery_', true), $this->messages(), Address::attributes('delivery_'))
                ->validateWithBag($bag);
            $place = $typed;
        }

        if ($delivery && ! Order::deliverable($place)) {
            throw ValidationException::withMessages([
                'order_type' => 'Deliveries only go around ' . config('sweetnest.delivery_city') . '. This address is in '
                    . ($place['city'] ?: 'another place') . ', so choose Pickup instead (the customer can name a pickup spot).',
            ])->errorBag($bag);
        }

        return $place;
    }

    private function messages(): array
    {
        return [
            'customer_id.exists' => 'Choose an active customer.',
            'scheduled_at.after_or_equal' => 'The due date cannot be in the past.',
            'items.required' => 'Add at least one item.',
            'items.*.product_id.exists' => 'Choose a product that is still on the price list.',
            'items.*.product_id.required_without' => 'Choose a product, or press Custom and type the item.',
            'items.*.custom_name.required_without' => 'Type a name for the custom item.',
            'items.*.custom_category.required_with' => 'Choose what kind of item it is.',
            'rush_fee_percent.required' => 'Enter the rush fee percent. This order is due within a day, so it is a rush order.',
            'rush_fee_percent.gt' => 'A rush order needs a fee above 0%.',
            'items.*.tiers.in' => 'Choose 1, 2 or 3 tiers.',
            'tiers.in' => 'Choose 1, 2 or 3 tiers.',
            'items.*.cake_type.in' => 'Choose a type of cake from the list.',
            'items.*.flavor.in' => 'Choose a flavor from the list.',
            'items.*.size.in' => 'Choose a size from the list.',
            'cake_type.in' => 'Choose a type of cake from the list.',
            'flavor.in' => 'Choose a flavor from the list.',
            'size.in' => 'Choose a size from the list.',
        ] + Address::messages('delivery_');
    }
}
