<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'customer_id',
        'created_by',
        'order_date',
        'scheduled_date',
        'order_type',
        'delivery_address',
        'status',
        'total_amount',
        'down_payment_required',
        'total_paid',
        'payment_status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'scheduled_date' => 'datetime',
            'total_amount' => 'decimal:2',
            'down_payment_required' => 'decimal:2',
            'total_paid' => 'decimal:2',
        ];
    }

    public function getBalanceDueAttribute(): float
    {
        return max(0, (float)$this->total_amount - (float)$this->total_paid);
    }

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

    public function productions()
    {
        return $this->hasMany(Production::class);
    }
}
