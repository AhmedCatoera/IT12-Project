<?php

namespace App\Models;

use App\Casts\DateOnly;
use Illuminate\Database\Eloquent\Model;

class StockIn extends Model
{
    protected $fillable = ['receipt_no', 'supplier_name', 'received_date', 'received_by', 'remarks'];

    protected function casts(): array
    {
        return ['received_date' => DateOnly::class];
    }

    public function items()
    {
        return $this->hasMany(StockInItem::class);
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function getNumberAttribute(): string
    {
        return 'SI-' . str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    /** Uses the total_cost sub-select when the list query added it, otherwise sums the lines. */
    public function getTotalCostAttribute($value): float
    {
        if ($value !== null) {
            return round((float) $value, 2);
        }

        return round($this->items->sum(fn (StockInItem $i) => $i->line_cost), 2);
    }
}
