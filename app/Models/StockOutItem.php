<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockOutItem extends Model
{
    protected $fillable = ['stock_out_id', 'ingredient_id', 'quantity'];

    protected function casts(): array
    {
        return ['quantity' => 'float'];
    }

    public function stockOut()
    {
        return $this->belongsTo(StockOut::class);
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }
}
