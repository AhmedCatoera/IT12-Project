<?php

namespace App\Models;

use App\Casts\DateOnly;
use Illuminate\Database\Eloquent\Model;

class PhysicalCount extends Model
{
    public const SHIFTS = [
        'morning' => 'Morning',
        'night' => 'Night',
    ];

    protected $fillable = ['count_date', 'shift', 'counted_by', 'notes'];

    protected function casts(): array
    {
        return ['count_date' => DateOnly::class];
    }

    public function items()
    {
        return $this->hasMany(PhysicalCountItem::class);
    }

    public function counter()
    {
        return $this->belongsTo(User::class, 'counted_by');
    }

    /** The Owner's correction, if it has been applied. */
    public function adjustment()
    {
        return $this->hasOne(StockAdjustment::class);
    }

    /** The next count saved after this one, if any. A newer count replaces an unapplied older one. */
    public function newerCount(): ?self
    {
        return self::where('id', '>', $this->id)->orderBy('id')->first();
    }

    public function getShiftLabelAttribute(): string
    {
        return self::SHIFTS[$this->shift] ?? $this->shift;
    }

    public function getNumberAttribute(): string
    {
        return 'PC-' . str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }
}
