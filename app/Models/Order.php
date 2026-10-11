<?php

namespace App\Models;

use App\Casts\DateOnly;
use App\Support\Address;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    /** A regular order is confirmed by a 50% down payment; a rush order by paying in full. */
    public const DOWN_PAYMENT_RATE = 0.50;

    /**
     * Regular orders are due 2 or more days after the order date and pay the items.
     * Rush orders are due the next day or the same day, add a fee and are paid in full.
     */
    public const KINDS = [
        'regular' => 'Regular',
        'rush' => 'Rush',
    ];

    public const STATUSES = [
        'pending' => 'Awaiting payment',
        'confirmed' => 'Confirmed',
        'in_production' => 'In production',
        'ready_for_release' => 'Ready for release',
        'out_for_delivery' => 'Out for delivery',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    /** Orders that still need work. */
    public const OPEN_STATUSES = ['pending', 'confirmed', 'in_production', 'ready_for_release', 'out_for_delivery'];

    public const PAYMENT_STATUSES = [
        'unpaid' => 'Unpaid',
        'partially_paid' => 'Partially paid',
        'fully_paid' => 'Fully paid',
    ];

    protected $fillable = [
        'order_number',
        'customer_id',
        'created_by',
        'order_date',
        'scheduled_at',
        'order_type',
        'order_kind',
        'rush_fee_percent',
        'delivery_fee',
        'refund_amount',
        'refunded_at',
        'delivery_street',
        'delivery_address_line2',
        'delivery_barangay',
        'delivery_city',
        'delivery_province',
        'delivery_postal_code',
        'delivery_latitude',
        'delivery_longitude',
        'status',
        'cancel_reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => DateOnly::class,
            'scheduled_at' => 'datetime',
            'refunded_at' => 'datetime',
            'rush_fee_percent' => 'float',
            'delivery_fee' => 'float',
            'refund_amount' => 'float',
            'delivery_latitude' => 'float',
            'delivery_longitude' => 'float',
        ];
    }

    // ---------- relationships ----------

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function jobOrder()
    {
        return $this->hasOne(JobOrder::class);
    }

    // ---------- scopes ----------

    /**
     * Adds computed items_total and total_paid columns (sub-selects, nothing stored).
     * The rush fee and the order total are worked out from items_total in PHP.
     */
    public function scopeWithTotals(Builder $query): Builder
    {
        if ($query->getQuery()->columns === null) {
            $query->select('orders.*');
        }

        return $query->addSelect([
            'items_total' => OrderItem::selectRaw('COALESCE(SUM(quantity * unit_price), 0)')
                ->whereColumn('order_items.order_id', 'orders.id'),
            'total_paid' => Payment::selectRaw('COALESCE(SUM(amount), 0)')
                ->whereColumn('payments.order_id', 'orders.id'),
        ]);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', self::OPEN_STATUSES);
    }

    /** Search grouped in one where() so status/type filters still apply. */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if ($term === null || trim($term) === '') {
            return $query;
        }

        $term = trim($term);

        return $query->where(function (Builder $q) use ($term) {
            $q->where('order_number', 'like', "%{$term}%")
                ->orWhereHas('customer', fn (Builder $c) => $c->search($term));
        });
    }

    // ---------- computed money values ----------

    /** The items alone, before any rush fee. */
    protected function itemsTotal(): Attribute
    {
        return Attribute::get(function ($value) {
            if ($value !== null) {
                return round((float) $value, 2);
            }

            return round($this->items->sum(fn (OrderItem $item) => $item->quantity * $item->unit_price), 2);
        });
    }

    public function isRush(): bool
    {
        return $this->order_kind === 'rush';
    }

    /**
     * Regular or rush, from the dates alone: an order due within config rush_within_days
     * of the day it was placed (tomorrow or today) is a rush order.
     */
    public static function kindFor(\DateTimeInterface|string $scheduledAt, \DateTimeInterface|string|null $orderDate = null): string
    {
        $due = \Illuminate\Support\Carbon::parse($scheduledAt)->startOfDay();
        $placed = ($orderDate ? \Illuminate\Support\Carbon::parse($orderDate) : today())->startOfDay();

        return $placed->diffInDays($due, false) <= (int) config('sweetnest.rush_within_days') ? 'rush' : 'regular';
    }

    /** What the due date makes this order now (the stored kind can lag behind an edit). */
    public function kindFromDates(): string
    {
        return self::kindFor($this->scheduled_at, $this->order_date);
    }

    public function getKindLabelAttribute(): string
    {
        return self::KINDS[$this->order_kind] ?? ucfirst((string) $this->order_kind);
    }

    /** The rush fee in pesos: a percent of the items total, zero on regular orders. */
    public function getRushFeeAttribute(): float
    {
        if (! $this->isRush() || ! $this->rush_fee_percent) {
            return 0.0;
        }

        return round($this->items_total * $this->rush_fee_percent / 100, 2);
    }

    /** The delivery fee typed from the delivery app; zero on pickups. */
    public function getDeliveryFeeAmountAttribute(): float
    {
        return $this->order_type === 'delivery' ? round((float) $this->delivery_fee, 2) : 0.0;
    }

    /** Items plus the rush fee and the delivery fee. Everything else (down payment, balance) builds on this. */
    public function getTotalAmountAttribute(): float
    {
        return round($this->items_total + $this->rush_fee + $this->delivery_fee_amount, 2);
    }

    protected function totalPaid(): Attribute
    {
        return Attribute::get(function ($value) {
            if ($value !== null) {
                return round((float) $value, 2);
            }

            return round((float) $this->payments->sum('amount'), 2);
        });
    }

    /** What confirms the order: 50% of the total on a regular order, the whole total on a rush order. */
    public function getDownPaymentRequiredAttribute(): float
    {
        return $this->isRush() ? $this->total_amount : round($this->total_amount * self::DOWN_PAYMENT_RATE, 2);
    }

    /** "Down payment (50%)" or "Full payment": what the pending order is waiting for. */
    public function getDownPaymentLabelAttribute(): string
    {
        return $this->isRush() ? 'Full payment' : 'Down payment (50%)';
    }

    public function getBalanceDueAttribute(): float
    {
        return max(0, round($this->total_amount - $this->total_paid, 2));
    }

    public function getPaymentStatusAttribute(): string
    {
        if ($this->total_paid <= 0) {
            return $this->total_amount > 0 ? 'unpaid' : 'fully_paid';
        }

        return $this->balance_due <= 0 ? 'fully_paid' : 'partially_paid';
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return self::PAYMENT_STATUSES[$this->payment_status];
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst(str_replace('_', ' ', (string) $this->status));
    }

    /** "Today, 2:00 PM", "Tomorrow, 9:00 AM" or "Wed, Sep 30, 2:00 PM". */
    public function getDueLabelAttribute(): string
    {
        $at = $this->scheduled_at;

        $day = match (true) {
            $at->isToday() => 'Today',
            $at->isTomorrow() => 'Tomorrow',
            $at->isYesterday() => 'Yesterday',
            $at->year === now()->year => $at->format('D, M j'),
            default => $at->format('M j, Y'),
        };

        return $day . ', ' . $at->format('g:i A');
    }

    // ---------- delivery address (a copy kept with the order) ----------

    /** The delivery address parts without the "delivery_" prefix. */
    public function getDeliveryPartsAttribute(): array
    {
        $parts = [];
        foreach (Address::PARTS as $part) {
            $parts[$part] = $this->{'delivery_' . $part};
        }

        return $parts;
    }

    public function getDeliveryAddressAttribute(): string
    {
        return Address::format($this->delivery_parts);
    }

    public function getHasDeliveryPinAttribute(): bool
    {
        return Address::hasPin($this->delivery_parts);
    }

    public function getDeliveryMapsUrlAttribute(): ?string
    {
        return Address::mapsUrl($this->delivery_parts);
    }

    public function getOrderTypeLabelAttribute(): string
    {
        return $this->order_type === 'delivery' ? 'Delivery' : 'Pickup';
    }

    /** The shop's address parts: where a pickup order is collected unless the customer asks for another spot. */
    public static function shopAddress(): array
    {
        return array_merge(Address::empty(), (array) config('sweetnest.shop.address'));
    }

    /** True when the pickup spot is the shop itself (not a meet-up place). An order with no spot kept means the shop. */
    public function getPickedUpAtShopAttribute(): bool
    {
        return $this->order_type === 'pickup' && (Address::isBlank($this->delivery_parts) || Address::same($this->delivery_parts, self::shopAddress()));
    }

    /** "Deliver to" or "Pick up at": the address kept with the order, formatted (a pickup with none kept = the shop). */
    public function getPlaceAddressAttribute(): string
    {
        if ($this->order_type === 'pickup' && Address::isBlank($this->delivery_parts)) {
            return Address::format(self::shopAddress());
        }

        return $this->delivery_address;
    }

    /** Deliveries only go to config delivery_city (Davao City); anywhere else is pickup. */
    public static function deliverable(array $parts): bool
    {
        $want = Address::cityKey((string) config('sweetnest.delivery_city'));

        return $want === '' || Address::cityKey((string) ($parts['city'] ?? '')) === $want;
    }

    // ---------- refunds ----------

    /**
     * What a cancellation gives back: a share of the total (config refund_percent) when the
     * order was fully paid, nothing when only the down payment was paid (the shop keeps it).
     */
    public function refundDue(): float
    {
        if (! $this->isFullyPaid() || $this->total_amount <= 0) {
            return 0.0;
        }

        return min($this->total_paid, round($this->total_amount * (float) config('sweetnest.refund_percent') / 100, 2));
    }

    public function needsRefund(): bool
    {
        return $this->status === 'cancelled' && (float) $this->refund_amount > 0 && ! $this->refunded_at;
    }

    // ---------- rules ----------

    public function isOverdue(): bool
    {
        return $this->isOpen() && $this->scheduled_at->isPast();
    }

    /**
     * The latest production date a job order may have: the order's due date, or today
     * when the due date has already passed (a late order still has to be baked).
     */
    public function latestProductionDate(): \Illuminate\Support\Carbon
    {
        $due = $this->scheduled_at->copy()->startOfDay();

        return $due->lt(today()) ? today() : $due;
    }

    /** Flavor, size, design and inscription can change until baking starts. */
    public function canEditItemDetails(): bool
    {
        return in_array($this->status, ['pending', 'confirmed'], true);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }

    public function isFullyPaid(): bool
    {
        return $this->payment_status === 'fully_paid';
    }

    public function canReceivePayment(): bool
    {
        return $this->status !== 'cancelled' && $this->balance_due > 0;
    }

    /**
     * Statuses a person may pick by hand. In production and Ready for release are
     * set only by the job order, and Confirmed only by the down payment.
     */
    public function manualNextStatuses(): array
    {
        return match ($this->status) {
            'pending', 'confirmed', 'in_production' => ['cancelled'],
            'ready_for_release' => $this->order_type === 'delivery'
                ? ['out_for_delivery', 'cancelled']
                : ['completed', 'cancelled'],
            'out_for_delivery' => ['completed'],
            default => [],
        };
    }
}
