<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhysicalCountItem extends Model
{
    protected $fillable = ['physical_count_id', 'ingredient_id', 'system_qty', 'counted_qty'];

    protected function casts(): array
    {
        return [
            'system_qty' => 'float',
            'counted_qty' => 'float',
        ];
    }

    public function physicalCount()
    {
        return $this->belongsTo(PhysicalCount::class);
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }

    /** Computed, never stored: counted minus what the system expected. */
    public function getVarianceAttribute(): float
    {
        return round($this->counted_qty - $this->system_qty, 2);
    }
}
