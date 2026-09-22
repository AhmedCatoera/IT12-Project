<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryAuditItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'audit_id',
        'ingredient_id',
        'system_stock',
        'physical_stock',
        'variance',
    ];

    protected function casts(): array
    {
        return [
            'system_stock' => 'decimal:2',
            'physical_stock' => 'decimal:2',
            'variance' => 'decimal:2',
        ];
    }

    public function audit()
    {
        return $this->belongsTo(InventoryAudit::class, 'audit_id');
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }
}
