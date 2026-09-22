<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Production extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'assigned_baker_id',
        'production_date',
        'status',
        'started_at',
        'completed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'production_date' => 'date',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function baker()
    {
        return $this->belongsTo(User::class, 'assigned_baker_id');
    }

    public function ingredients()
    {
        return $this->belongsToMany(Ingredient::class, 'production_ingredients')
                    ->withPivot('quantity_used')
                    ->withTimestamps();
    }

    public function productionIngredients()
    {
        return $this->hasMany(ProductionIngredient::class);
    }
}
