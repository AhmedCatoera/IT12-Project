<?php

namespace Tests\Feature;

use App\Services\Geocoder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Concerns\SweetNestData;
use Tests\TestCase;

/** The map's address lookups (OpenStreetMap Nominatim), with the real service faked. */
class GeoTest extends TestCase
{
    use RefreshDatabase, SweetNestData;

    private function nominatimReverse(array $address): array
    {
        return ['lat' => '7.0846', 'lon' => '125.5093', 'display_name' => 'Somewhere in Davao', 'address' => $address];
    }

    public function test_clicking_the_map_fills_the_address_parts(): void
    {
        Http::fake(['nominatim.openstreetmap.org/reverse*' => Http::response($this->nominatimReverse([
            'house_number' => '12', 'road' => 'Narra Street', 'neighbourhood' => 'Phase 1', 'quarter' => 'Santo Niño',
            'suburb' => 'Tugbok District', 'city' => 'Davao City', 'region' => 'Davao Region', 'postcode' => '8022', 'country' => 'Philippines',
        ]))]);

        $this->actingAs($this->staff())->getJson('/geo/reverse?lat=7.0846&lng=125.5093')
            ->assertOk()
            ->assertJsonPath('fields.street', '12 Narra Street')
            ->assertJsonPath('fields.address_line2', 'Phase 1')
            ->assertJsonPath('fields.barangay', 'Santo Niño')
            ->assertJsonPath('fields.city', 'Davao City')
            ->assertJsonPath('fields.province', 'Davao del Sur')   // Davao City has no province of its own
            ->assertJsonPath('fields.postal_code', '8022');

        // Nominatim asks every app to identify itself.
        Http::assertSent(fn (Request $request) => str_contains($request->header('User-Agent')[0] ?? '', 'SweetNest'));
    }

    public function test_towns_outside_davao_city_keep_their_province(): void
    {
        $fields = Geocoder::fieldsFrom(['road' => 'Rizal St.', 'village' => 'Poblacion', 'city' => 'Panabo', 'state' => 'Davao del Norte', 'postcode' => '8105']);

        $this->assertSame('Poblacion', $fields['barangay']);
        $this->assertSame('Panabo', $fields['city']);
        $this->assertSame('Davao del Norte', $fields['province']);
    }

    public function test_spots_outside_the_philippines_are_refused(): void
    {
        Http::fake();

        $this->actingAs($this->staff())->getJson('/geo/reverse?lat=1.3521&lng=103.8198')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['lat', 'lng']);

        Http::assertNothingSent();
    }

    public function test_no_internet_gives_a_friendly_message(): void
    {
        Http::fake(['*' => Http::response('Service unavailable', 503)]);

        $this->actingAs($this->staff())->getJson('/geo/reverse?lat=7.07&lng=125.61')
            ->assertStatus(503)
            ->assertJsonPath('message', "Couldn't look up this spot right now. The pin is kept, so just type the address.");
    }

    public function test_search_puts_davao_places_first(): void
    {
        Http::fake(['nominatim.openstreetmap.org/search*' => Http::response([
            ['lat' => '14.5836', 'lon' => '121.0567', 'display_name' => 'SM Megamall, Mandaluyong'],
            ['lat' => '7.0987', 'lon' => '125.6311', 'display_name' => 'SM Lanang Premier, Davao City'],
            ['lat' => '1.3521', 'lon' => '103.8198', 'display_name' => 'Somewhere in Singapore'],
        ])]);

        $this->actingAs($this->staff())->getJson('/geo/search?q=SM+mall')
            ->assertOk()
            ->assertJsonCount(2, 'results')
            ->assertJsonPath('results.0.label', 'SM Lanang Premier, Davao City')
            ->assertJsonPath('results.1.label', 'SM Megamall, Mandaluyong');

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'viewbox=') && ! str_contains($request->url(), 'bounded=1') && str_contains($request->url(), 'countrycodes=ph'));
    }

    public function test_lookups_are_cached(): void
    {
        Http::fake(['*' => Http::response($this->nominatimReverse(['road' => 'Narra Street', 'city' => 'Davao City']))]);
        $staff = $this->staff();

        $this->actingAs($staff)->getJson('/geo/reverse?lat=7.08&lng=125.51')->assertOk();
        $this->actingAs($staff)->getJson('/geo/reverse?lat=7.08&lng=125.51')->assertOk();

        Http::assertSentCount(1);
    }

    public function test_lookups_need_a_login(): void
    {
        $this->getJson('/geo/reverse?lat=7.07&lng=125.61')->assertUnauthorized();
    }
}
