<?php

namespace App\Models;

use App\Casts\DateOnly;
use Illuminate\Database\Eloquent\Model;

class StockInItem extends Model
{
    protected $fillable = ['stock_in_id', 'ingredient_id', 'quantity', 'unit_cost', 'expiration_date'];

    protected function casts(): array
    {
        return [
            'quantity' => 'float',
            'unit_cost' => 'float',
            'expiration_date' => DateOnly::class,
        ];
    }

    public function stockIn()
    {
        return $this->belongsTo(StockIn::class);
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function getLineCostAttribute(): float
    {
        return round($this->quantity * (float) $this->unit_cost, 2);
    }
}
