<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'unit',
        'current_stock',
        'reorder_level',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'current_stock' => 'decimal:2',
            'reorder_level' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function isLowStock(): bool
    {
        return $this->current_stock <= $this->reorder_level;
    }

    public function productions()
    {
        return $this->belongsToMany(Production::class, 'production_ingredients')
                    ->withPivot('quantity_used')
                    ->withTimestamps();
    }

    public function transactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function auditItems()
    {
        return $this->hasMany(InventoryAuditItem::class);
    }
}
