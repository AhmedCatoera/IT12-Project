<?php

namespace App\Services;

use App\Support\Address;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Looks up real addresses with OpenStreetMap's Nominatim service (free, no API key).
 * Calls go through the server so they carry a proper User-Agent (Nominatim's usage
 * policy) and can be cached; the map itself only talks to this app.
 */
class Geocoder
{
    /** Map pin -> address parts that fill the address form. */
    public function reverse(float $lat, float $lng): array
    {
        $key = sprintf('geo:reverse:%.5f,%.5f', $lat, $lng);

        return Cache::remember($key, now()->addDays(7), function () use ($lat, $lng) {
            $json = $this->http()->get('/reverse', [
                'format' => 'jsonv2',
                'lat' => $lat,
                'lon' => $lng,
                'zoom' => 18,
                'addressdetails' => 1,
            ])->throw()->json();

            return [
                'fields' => self::fieldsFrom($json['address'] ?? []),
                'label' => $json['display_name'] ?? null,
            ];
        });
    }

    /** Place search: Davao-area matches first, then the rest of the Philippines. */
    public function search(string $query): array
    {
        $key = 'geo:search:' . md5(mb_strtolower(trim($query)));

        return Cache::remember($key, now()->addDays(7), function () use ($query) {
            $d = Address::DAVAO;

            // viewbox without "bounded" only prefers that area; other places still come back.
            $results = $this->http()->get('/search', [
                'format' => 'jsonv2',
                'q' => $query,
                'countrycodes' => 'ph',
                'viewbox' => "{$d['west']},{$d['north']},{$d['east']},{$d['south']}",
                'limit' => 8,
            ])->throw()->json();

            $inDavao = fn ($r) => (float) $r['lat'] >= $d['south'] && (float) $r['lat'] <= $d['north']
                && (float) $r['lon'] >= $d['west'] && (float) $r['lon'] <= $d['east'];

            return collect($results)
                ->filter(fn ($r) => isset($r['lat'], $r['lon']) && Address::inBounds((float) $r['lat'], (float) $r['lon']))
                ->sortByDesc($inDavao)
                ->map(fn ($r) => [
                    'label' => $r['display_name'] ?? $query,
                    'lat' => round((float) $r['lat'], 7),
                    'lng' => round((float) $r['lon'], 7),
                ])
                ->values()
                ->all();
        });
    }

    /**
     * Turns Nominatim's address details into our fields. In the Philippines a barangay
     * usually comes back as "quarter" or "village", and Davao City (a highly urbanized
     * city) has no province of its own, so it gets Davao del Sur like postal addresses do.
     */
    public static function fieldsFrom(array $a): array
    {
        $road = $a['road'] ?? $a['pedestrian'] ?? $a['footway'] ?? $a['path'] ?? null;
        $street = trim(implode(' ', array_filter([$a['house_number'] ?? null, $road])));

        $barangay = self::first($a, ['quarter', 'village', 'neighbourhood', 'hamlet', 'suburb', 'city_district']);
        $city = self::first($a, ['city', 'town', 'municipality']);
        $province = self::first($a, ['province', 'state', 'county']);

        if ($province === null && $city !== null && str_contains(mb_strtolower($city), 'davao city')) {
            $province = Address::DEFAULT_PROVINCE;
        }

        $landmark = self::first($a, ['building', 'amenity', 'shop', 'office', 'tourism', 'leisure', 'residential', 'neighbourhood']);
        if ($landmark === $barangay) {
            $landmark = null;
        }

        $postcode = preg_match('/^\d{4}$/', (string) ($a['postcode'] ?? '')) ? $a['postcode'] : null;

        return [
            'street' => $street !== '' ? $street : null,
            'address_line2' => $landmark,
            'barangay' => $barangay,
            'city' => $city,
            'province' => $province,
            'postal_code' => $postcode,
        ];
    }

    private static function first(array $a, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (filled($a[$key] ?? null)) {
                return trim($a[$key]);
            }
        }

        return null;
    }

    private function http()
    {
        return Http::baseUrl(config('services.nominatim.url'))
            ->withHeaders([
                'User-Agent' => config('services.nominatim.user_agent'),
                'Accept-Language' => 'en',
            ])
            ->acceptJson()
            ->timeout(8);
    }
}
