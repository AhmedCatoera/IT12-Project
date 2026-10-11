<?php

namespace App\Models;

use App\Casts\DateOnly;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobOrder extends Model
{
    use HasFactory;

    public const STATUSES = [
        'pending' => 'Pending',
        'in_progress' => 'In progress',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    protected $fillable = [
        'order_id',
        'assigned_to',
        'production_date',
        'status',
        'started_at',
        'completed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'production_date' => DateOnly::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function stockOuts()
    {
        return $this->hasMany(StockOut::class);
    }

    public function stockOutItems()
    {
        return $this->hasManyThrough(StockOutItem::class, StockOut::class);
    }

    public function getNumberAttribute(): string
    {
        return 'JO-' . str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /** Ingredients used are logged once the job order is done (marked as done), not while baking. */
    public function canLogUsage(): bool
    {
        return $this->status === 'completed';
    }
}
