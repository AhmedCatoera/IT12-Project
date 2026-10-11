<?php

namespace App\Support;

/**
 * A Philippine address split into parts. Used for a customer's saved address and
 * for an order's delivery address (a copy kept with the order, so old orders keep
 * the address they were delivered to even if the customer moves).
 */
final class Address
{
    public const PARTS = ['street', 'address_line2', 'barangay', 'city', 'province', 'postal_code', 'latitude', 'longitude'];

    public const DEFAULT_CITY = 'Davao City';

    public const DEFAULT_PROVINCE = 'Davao del Sur';

    /** Pins can be anywhere in the Philippines. */
    public const BOUNDS = ['south' => 4.4, 'west' => 116.7, 'north' => 21.3, 'east' => 127.0];

    /** Davao City and the towns around it: where the map starts and what search looks at first. */
    public const DAVAO = ['south' => 6.70, 'west' => 125.15, 'north' => 7.75, 'east' => 125.95];

    /** Davao City Hall, where the map opens. */
    public const CENTER = [7.0731, 125.6128];

    /**
     * @param  bool  $required  true when a full address must be given (a delivery typed by hand)
     */
    /** What the React map components need: tiles, limits and the lookup routes. */
    public static function mapConfig(): array
    {
        return [
            'tiles' => config('services.map.tiles'),
            'attribution' => config('services.map.attribution'),
            'bounds' => self::BOUNDS,
            'home' => self::DAVAO,
            'center' => self::CENTER,
            'reverseUrl' => route('geo.reverse'),
            'searchUrl' => route('geo.search'),
        ];
    }

    /** City and province a blank address form starts with. */
    public static function defaults(): array
    {
        return ['city' => self::DEFAULT_CITY, 'province' => self::DEFAULT_PROVINCE];
    }

    public static function rules(string $prefix = '', bool $required = false): array
    {
        $p = $prefix;
        $b = self::BOUNDS;
        $need = fn (array $others) => $required
            ? 'required'
            : 'required_with:' . implode(',', array_map(fn ($part) => $p . $part, $others));

        return [
            $p . 'street' => ['nullable', 'string', 'max:150', $need(['address_line2', 'barangay', 'postal_code', 'latitude'])],
            $p . 'address_line2' => ['nullable', 'string', 'max:150'],
            $p . 'barangay' => ['nullable', 'string', 'max:100', $need(['street'])],
            $p . 'city' => ['nullable', 'string', 'max:100', $need(['street'])],
            $p . 'province' => ['nullable', 'string', 'max:100', $need(['street'])],
            $p . 'postal_code' => ['nullable', 'regex:/^\d{4}$/'],
            $p . 'latitude' => ['nullable', 'numeric', "between:{$b['south']},{$b['north']}", 'required_with:' . $p . 'longitude'],
            $p . 'longitude' => ['nullable', 'numeric', "between:{$b['west']},{$b['east']}", 'required_with:' . $p . 'latitude'],
        ];
    }

    public static function messages(string $prefix = ''): array
    {
        return [
            $prefix . 'postal_code.regex' => 'The ZIP code has 4 digits, for example 8000.',
            $prefix . 'latitude.between' => 'Pick a spot inside the Philippines.',
            $prefix . 'longitude.between' => 'Pick a spot inside the Philippines.',
            $prefix . 'street.required_with' => 'Enter the house or unit number and street.',
        ];
    }

    public static function attributes(string $prefix = ''): array
    {
        return [
            $prefix . 'street' => 'house no. and street',
            $prefix . 'address_line2' => 'subdivision or landmark',
            $prefix . 'barangay' => 'barangay',
            $prefix . 'city' => 'city or municipality',
            $prefix . 'province' => 'province',
            $prefix . 'postal_code' => 'ZIP code',
            $prefix . 'latitude' => 'map pin',
            $prefix . 'longitude' => 'map pin',
        ];
    }

    /**
     * The address parts from (validated) input, without the prefix. Blank values
     * become null, and an address with nothing but the pre-filled city and
     * province counts as no address at all.
     */
    public static function fromInput(array $input, string $prefix = ''): array
    {
        $parts = [];

        foreach (self::PARTS as $part) {
            $value = $input[$prefix . $part] ?? null;
            $value = is_string($value) ? trim($value) : $value;
            $parts[$part] = $value === '' ? null : $value;
        }

        foreach (['latitude', 'longitude'] as $coordinate) {
            if ($parts[$coordinate] !== null) {
                $parts[$coordinate] = round((float) $parts[$coordinate], 7);
            }
        }

        return self::isBlank($parts) ? self::empty() : $parts;
    }

    public static function empty(): array
    {
        return array_fill_keys(self::PARTS, null);
    }

    /** Nothing entered apart from the city and province the form fills in by default. */
    public static function isBlank(array $parts): bool
    {
        foreach (['street', 'address_line2', 'barangay', 'postal_code', 'latitude'] as $part) {
            if (filled($parts[$part] ?? null)) {
                return false;
            }
        }

        return true;
    }

    public static function isComplete(array $parts): bool
    {
        foreach (['street', 'barangay', 'city', 'province'] as $part) {
            if (blank($parts[$part] ?? null)) {
                return false;
            }
        }

        return true;
    }

    public static function hasPin(array $parts): bool
    {
        return ($parts['latitude'] ?? null) !== null && ($parts['longitude'] ?? null) !== null;
    }

    /** "Blk 7 Lot 3 Narra St., Phase 1, Brgy. Santo Niño, Davao City, Davao del Sur 8022" */
    public static function format(array $parts): string
    {
        $province = trim(($parts['province'] ?? '') . ' ' . ($parts['postal_code'] ?? ''));

        return implode(', ', array_filter([
            $parts['street'] ?? null,
            $parts['address_line2'] ?? null,
            self::barangayLabel($parts['barangay'] ?? null),
            $parts['city'] ?? null,
            $province !== '' ? $province : null,
        ]));
    }

    /** "Brgy. Santo Niño, Davao City" for lists. */
    public static function short(array $parts): string
    {
        return implode(', ', array_filter([self::barangayLabel($parts['barangay'] ?? null), $parts['city'] ?? null]));
    }

    public static function barangayLabel(?string $barangay): ?string
    {
        if (blank($barangay)) {
            return null;
        }

        return preg_match('/^(brgy\.?|barangay)\s/i', $barangay) ? $barangay : 'Brgy. ' . $barangay;
    }

    /** Directions in Google Maps: to the pin if there is one, otherwise a search for the address. */
    public static function mapsUrl(array $parts): ?string
    {
        if (self::hasPin($parts)) {
            return 'https://www.google.com/maps/dir/?api=1&destination=' . $parts['latitude'] . ',' . $parts['longitude'];
        }

        return self::isComplete($parts)
            ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode(self::format($parts))
            : null;
    }

    public static function withPrefix(array $parts, string $prefix): array
    {
        $prefixed = [];

        foreach (self::PARTS as $part) {
            $prefixed[$prefix . $part] = $parts[$part] ?? null;
        }

        return $prefixed;
    }

    /** Same address (street, line 2, barangay, city and postal code; the pin may differ). */
    public static function same(array $a, array $b): bool
    {
        foreach (['street', 'address_line2', 'barangay', 'city', 'postal_code'] as $part) {
            if (trim((string) ($a[$part] ?? '')) !== trim((string) ($b[$part] ?? ''))) {
                return false;
            }
        }

        return true;
    }

    /** "Davao City", "davao city" and "Davao  City" compare equal. */
    public static function cityKey(string $city): string
    {
        return strtolower(preg_replace('/\s+/', ' ', trim($city)));
    }

    public static function inBounds(float $lat, float $lng): bool
    {
        $b = self::BOUNDS;

        return $lat >= $b['south'] && $lat <= $b['north'] && $lng >= $b['west'] && $lng <= $b['east'];
    }
}
