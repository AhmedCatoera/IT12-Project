<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'custom_name',
        'custom_category',
        'cake_type',
        'flavor',
        'size',
        'tiers',
        'design_theme',
        'inscription',
        'reference_image',
        'quantity',
        'unit_price',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'float',
            'tiers' => 'integer',
        ];
    }

    /** Largest reference photo allowed, in KB: 4 MB, or less if the server's PHP upload limit is lower. */
    public static function photoMaxKb(): int
    {
        return (int) min(4096, floor(UploadedFile::getMaxFilesize() / 1024));
    }

    public static function photoMaxLabel(): string
    {
        return rtrim(rtrim(number_format(self::photoMaxKb() / 1024, 1), '0'), '.') . ' MB';
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /** The choice lists the cake forms offer, for the React components. */
    public static function specOptions(): array
    {
        return [
            'types' => config('sweetnest.cake_types'),
            'flavors' => config('sweetnest.cake_flavors'),
            'icingFlavors' => config('sweetnest.icing_flavors'),
            'sizes' => config('sweetnest.cake_sizes'),
            'tiers' => config('sweetnest.cake_tiers'),
        ];
    }

    /** True when an item of this category takes cake details (type, flavor, size, design, message, photo). */
    public static function takesSpecsFor(?string $category): bool
    {
        return $category === 'cake';
    }

    /** Same, for a listed product. */
    public static function takesSpecs(?Product $product): bool
    {
        return self::takesSpecsFor($product?->category);
    }

    /** The category of this item: the product's, or the one typed for a custom item. */
    public function getCategoryAttribute(): ?string
    {
        return $this->product?->category ?? $this->custom_category;
    }

    public function getCategoryLabelAttribute(): string
    {
        return Product::CATEGORIES[$this->category] ?? ucfirst((string) $this->category);
    }

    /** A custom item is one typed by the Owner or Staff instead of picked from the price list. */
    public function isCustom(): bool
    {
        return $this->product_id === null;
    }

    /** The flavor list that goes with a cake type: chiffon has its own flavors, the others are flavored by the icing. */
    public static function flavorsFor(?string $cakeType): array
    {
        return $cakeType === 'chiffon' ? config('sweetnest.cake_flavors') : config('sweetnest.icing_flavors');
    }

    /** The word used for the flavor of this cake type. */
    public static function flavorLabel(?string $cakeType): string
    {
        return $cakeType === 'chiffon' ? 'Flavor' : 'Icing';
    }

    /**
     * Keeps the cake details only for cakes; pastries, breads and souvenirs have none.
     * A flavor that does not belong to the chosen cake type is dropped too.
     */
    public static function cleanSpecs(array $row, ?string $category): array
    {
        if (! self::takesSpecsFor($category)) {
            return ['cake_type' => null, 'flavor' => null, 'size' => null, 'tiers' => null, 'design_theme' => null, 'inscription' => null];
        }

        $text = fn (string $key) => ($v = trim((string) ($row[$key] ?? ''))) === '' ? null : $v;

        $type = $text('cake_type');
        $flavor = $text('flavor');
        if (! $type || ($flavor && ! in_array($flavor, self::flavorsFor($type), true))) {
            $flavor = null; // a flavor only makes sense with its cake type
        }

        return [
            'cake_type' => $type,
            'flavor' => $flavor,
            'size' => $text('size'),
            'tiers' => in_array((int) ($row['tiers'] ?? 0), config('sweetnest.cake_tiers'), true) ? (int) $row['tiers'] : null,
            'design_theme' => $text('design_theme'),
            'inscription' => $text('inscription'),
        ];
    }

    public function getCakeTypeLabelAttribute(): ?string
    {
        if (! $this->cake_type) {
            return null;
        }

        // "Sponge (plain)" in the form, "Sponge" on the order.
        return trim(preg_replace('/\s*\(plain\)/', '', config('sweetnest.cake_types')[$this->cake_type] ?? $this->cake_type));
    }

    /** Label => value pairs shown under the item name on the order, job order and printed slip. */
    public function getSpecsAttribute(): array
    {
        return array_filter([
            'Type' => $this->cake_type_label,
            self::flavorLabel($this->cake_type) => $this->flavor,
            'Size' => $this->size,
            'Tiers' => $this->tiers ? ($this->tiers === 1 ? '1 tier' : $this->tiers . ' tiers') : null,
            'Design' => $this->design_theme,
        ]);
    }

    public function getSubtotalAttribute(): float
    {
        return round($this->quantity * $this->unit_price, 2);
    }

    public function getNameAttribute(): string
    {
        return $this->product?->name ?? $this->custom_name ?? 'Item';
    }

    /** Served through a route, so it works without "php artisan storage:link". */
    public function getReferenceImageUrlAttribute(): ?string
    {
        return $this->reference_image ? route('orderItems.image', $this) : null;
    }
}
