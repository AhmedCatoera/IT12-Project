<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionIngredient extends Model
{
    use HasFactory;

    protected $table = 'production_ingredients';

    protected $fillable = [
        'production_id',
        'ingredient_id',
        'quantity_used',
    ];

    protected function casts(): array
    {
        return [
            'quantity_used' => 'decimal:2',
        ];
    }

    public function production()
    {
        return $this->belongsTo(Production::class);
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }
}
