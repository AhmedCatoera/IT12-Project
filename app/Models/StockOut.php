<?php

namespace App\Models;

use App\Casts\DateOnly;
use Illuminate\Database\Eloquent\Model;

class StockOut extends Model
{
    protected $fillable = ['job_order_id', 'out_date', 'recorded_by', 'remarks'];

    protected function casts(): array
    {
        return ['out_date' => DateOnly::class];
    }

    public function jobOrder()
    {
        return $this->belongsTo(JobOrder::class);
    }

    public function items()
    {
        return $this->hasMany(StockOutItem::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function getNumberAttribute(): string
    {
        return 'SO-' . str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }
}
