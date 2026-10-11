<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    public const CATEGORIES = [
        'cake' => 'Cake',
        'pastry' => 'Pastry',
        'bread' => 'Bread',
        'souvenir' => 'Souvenir',
    ];

    protected $fillable = ['category', 'name', 'base_price', 'is_active'];

    protected function casts(): array
    {
        return [
            'base_price' => 'float',
            'is_active' => 'boolean',
        ];
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst((string) $this->category);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
