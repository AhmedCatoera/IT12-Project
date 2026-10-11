<?php

namespace App\Models;

use App\Support\Address;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'contact_number',
        'street',
        'address_line2',
        'barangay',
        'city',
        'province',
        'postal_code',
        'latitude',
        'longitude',
        'facebook_name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    // ---------- saved address ----------

    public function getAddressPartsAttribute(): array
    {
        return $this->only(Address::PARTS);
    }

    public function getFullAddressAttribute(): string
    {
        return Address::format($this->address_parts);
    }

    public function getShortAddressAttribute(): string
    {
        return Address::short($this->address_parts);
    }

    /** Street, barangay, city and province are all there, so it can be used for a delivery. */
    public function getHasAddressAttribute(): bool
    {
        return Address::isComplete($this->address_parts);
    }

    public function getHasPinAttribute(): bool
    {
        return Address::hasPin($this->address_parts);
    }

    public function getMapsUrlAttribute(): ?string
    {
        return Address::mapsUrl($this->address_parts);
    }

    public function getNameAttribute(): string
    {
        return trim(implode(' ', array_filter([$this->first_name, $this->middle_name, $this->last_name])));
    }

    /** Contact number shown as 0917 123 4567 (stored as digits only). */
    public function getContactDisplayAttribute(): string
    {
        $n = (string) $this->contact_number;

        if (strlen($n) === 11 && str_starts_with($n, '09')) {
            return substr($n, 0, 4) . ' ' . substr($n, 4, 3) . ' ' . substr($n, 7);
        }

        return $n;
    }

    /** Stores contact numbers as digits only so "0917-123-4567" and "09171234567" match. */
    public static function normalizeContact(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value);

        // +63 917... -> 0917...
        if (strlen($digits) === 12 && str_starts_with($digits, '63')) {
            $digits = '0' . substr($digits, 2);
        }

        return $digits;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Search grouped in one where() so it never cancels other filters. */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if ($term === null || trim($term) === '') {
            return $query;
        }

        $term = trim($term);
        $digits = preg_replace('/\D+/', '', $term);

        return $query->where(function (Builder $q) use ($term, $digits) {
            // Every word must match a name part, so "Maria Santos" finds Maria Clara Santos.
            $q->where(fn (Builder $names) => self::applyNameWords($names, $term))
                ->orWhere('facebook_name', 'like', "%{$term}%")
                ->orWhere('barangay', 'like', "%{$term}%");

            if (strlen($digits) >= 3) {
                $q->orWhere('contact_number', 'like', "%{$digits}%");
            }
        });
    }

    /** Adds "each word matches first, middle or last name" conditions (works on MySQL and SQLite). */
    public static function applyNameWords(Builder $query, string $term, string $prefix = ''): Builder
    {
        foreach (preg_split('/\s+/', trim($term)) as $word) {
            $query->where(function (Builder $q) use ($word, $prefix) {
                $q->where($prefix . 'first_name', 'like', "%{$word}%")
                    ->orWhere($prefix . 'middle_name', 'like', "%{$word}%")
                    ->orWhere($prefix . 'last_name', 'like', "%{$word}%");
            });
        }

        return $query;
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
