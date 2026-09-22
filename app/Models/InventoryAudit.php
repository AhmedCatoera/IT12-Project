<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'conducted_by',
        'audit_date',
        'audit_shift',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'audit_date' => 'date',
        ];
    }

    public function conductedBy()
    {
        return $this->belongsTo(User::class, 'conducted_by');
    }

    public function items()
    {
        return $this->hasMany(InventoryAuditItem::class, 'audit_id');
    }
}
