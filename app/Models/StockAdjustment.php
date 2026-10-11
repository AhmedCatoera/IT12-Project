<?php

namespace App\Models;

use App\Casts\DateOnly;
use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    public const REASONS = [
        'spoilage' => 'Spoilage',
        'expired' => 'Expired',
        'damaged' => 'Damaged',
        'count_correction' => 'Physical count correction',
        'entry_correction' => 'Entry correction',
        'other' => 'Other',
    ];

    protected $fillable = ['physical_count_id', 'reason', 'adjusted_date', 'adjusted_by', 'remarks'];

    protected function casts(): array
    {
        return ['adjusted_date' => DateOnly::class];
    }

    public function items()
    {
        return $this->hasMany(StockAdjustmentItem::class);
    }

    public function adjuster()
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }

    public function physicalCount()
    {
        return $this->belongsTo(PhysicalCount::class);
    }

    public function getNumberAttribute(): string
    {
        return 'ADJ-' . str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    public function getReasonLabelAttribute(): string
    {
        return self::REASONS[$this->reason] ?? $this->reason;
    }
}
