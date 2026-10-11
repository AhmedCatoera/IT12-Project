<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockAdjustmentItem extends Model
{
    protected $fillable = ['stock_adjustment_id', 'ingredient_id', 'quantity'];

    protected function casts(): array
    {
        return ['quantity' => 'float'];
    }

    public function adjustment()
    {
        return $this->belongsTo(StockAdjustment::class, 'stock_adjustment_id');
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }
}
