<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    public const TYPES = [
        'down_payment' => 'Down payment',
        'balance_payment' => 'Balance payment',
        'full_payment' => 'Full payment',
    ];

    public const METHODS = [
        'cash' => 'Cash',
        'gcash' => 'GCash',
    ];

    protected $fillable = [
        'order_id',
        'received_by',
        'payment_type',
        'payment_method',
        'amount',
        'reference_no',
        'proof_image',
        'paid_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'paid_at' => 'datetime',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->payment_type] ?? $this->payment_type;
    }

    public function getMethodLabelAttribute(): string
    {
        return self::METHODS[$this->payment_method] ?? $this->payment_method;
    }

    /** The GCash screenshot, served through a route so it works without "php artisan storage:link". */
    public function getProofImageUrlAttribute(): ?string
    {
        return $this->proof_image ? route('payments.proof', $this) : null;
    }
}
