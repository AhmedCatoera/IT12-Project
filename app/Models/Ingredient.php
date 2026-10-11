<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Ingredient extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'unit', 'reorder_level', 'is_active'];

    protected function casts(): array
    {
        return [
            'reorder_level' => 'float',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Stock on hand is never stored. It is always:
     * stock in - stock out + adjustments (adjustment lines are signed).
     */
    public static function stockSql(string $idColumn = 'ingredients.id'): string
    {
        return "(COALESCE((SELECT SUM(si.quantity) FROM stock_in_items si WHERE si.ingredient_id = {$idColumn}), 0)"
            . " - COALESCE((SELECT SUM(so.quantity) FROM stock_out_items so WHERE so.ingredient_id = {$idColumn}), 0)"
            . " + COALESCE((SELECT SUM(sa.quantity) FROM stock_adjustment_items sa WHERE sa.ingredient_id = {$idColumn}), 0))";
    }

    public static function currentStock(int $ingredientId): float
    {
        $in = (float) DB::table('stock_in_items')->where('ingredient_id', $ingredientId)->sum('quantity');
        $out = (float) DB::table('stock_out_items')->where('ingredient_id', $ingredientId)->sum('quantity');
        $adj = (float) DB::table('stock_adjustment_items')->where('ingredient_id', $ingredientId)->sum('quantity');

        return round($in - $out + $adj, 2);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeWithStock(Builder $query): Builder
    {
        if ($query->getQuery()->columns === null) {
            $query->select('ingredients.*');
        }

        return $query->selectRaw(self::stockSql() . ' as stock_on_hand');
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereRaw(self::stockSql() . ' <= ingredients.reorder_level');
    }

    protected function stockOnHand(): Attribute
    {
        return Attribute::get(fn ($value) => $value !== null
            ? round((float) $value, 2)
            : self::currentStock($this->id));
    }

    public function isLowStock(): bool
    {
        return $this->stock_on_hand <= $this->reorder_level;
    }

    public function stockInItems()
    {
        return $this->hasMany(StockInItem::class);
    }

    public function stockOutItems()
    {
        return $this->hasMany(StockOutItem::class);
    }

    public function adjustmentItems()
    {
        return $this->hasMany(StockAdjustmentItem::class);
    }

    public function countItems()
    {
        return $this->hasMany(PhysicalCountItem::class);
    }
}
