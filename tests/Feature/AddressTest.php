<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Support\Address;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\SweetNestData;
use Tests\TestCase;

/** Addresses are split into parts (street, barangay, city...) with an optional map pin in the Davao area. */
class AddressTest extends TestCase
{
    use RefreshDatabase, SweetNestData;

    private function customerForm(array $overrides = []): array
    {
        return $overrides + [
            'first_name' => 'Liza',
            'last_name' => 'Soberano',
            'contact_number' => '09277654321',
            'street' => 'Blk 7 Lot 3, Narra St.',
            'address_line2' => 'Phase 1',
            'barangay' => 'Santo Niño',
            'city' => 'Davao City',
            'province' => 'Davao del Sur',
            'postal_code' => '8022',
            'latitude' => '7.0846',
            'longitude' => '125.5093',
        ];
    }

    public function test_customer_address_is_saved_in_parts_with_its_pin(): void
    {
        $this->actingAs($this->staff())->post('/customers', $this->customerForm())->assertSessionHasNoErrors();

        $customer = Customer::sole();
        $this->assertSame('Santo Niño', $customer->barangay);
        $this->assertSame(7.0846, $customer->latitude);
        $this->assertSame('Blk 7 Lot 3, Narra St., Phase 1, Brgy. Santo Niño, Davao City, Davao del Sur 8022', $customer->full_address);
        $this->assertSame('Brgy. Santo Niño, Davao City', $customer->short_address);
        $this->assertTrue($customer->has_address);
        $this->assertSame('https://www.google.com/maps/dir/?api=1&destination=7.0846,125.5093', $customer->maps_url);

        $this->actingAs($this->staff())->get("/customers/{$customer->id}")
            ->assertSee('Brgy. Santo Niño')
            ->assertSee('data-react="PinMap"', false)
            ->assertSee('Directions in Google Maps');
    }

    public function test_the_address_is_optional(): void
    {
        // The form pre-fills the city and province; with nothing else typed, no address is saved.
        $this->actingAs($this->staff())->post('/customers', $this->customerForm([
            'street' => '', 'address_line2' => '', 'barangay' => '', 'postal_code' => '', 'latitude' => '', 'longitude' => '',
        ]))->assertSessionHasNoErrors();

        $customer = Customer::sole();
        $this->assertNull($customer->city);
        $this->assertNull($customer->province);
        $this->assertFalse($customer->has_address);
        $this->assertSame('', $customer->full_address);
    }

    public function test_a_started_address_needs_its_main_parts(): void
    {
        $this->actingAs($this->staff())->post('/customers', $this->customerForm(['barangay' => '', 'city' => '']))
            ->assertSessionHasErrorsIn('createCustomer', ['barangay', 'city']);

        $this->actingAs($this->staff())->post('/customers', $this->customerForm(['street' => '']))
            ->assertSessionHasErrorsIn('createCustomer', ['street']);
    }

    public function test_zip_code_has_four_digits(): void
    {
        $this->actingAs($this->staff())->post('/customers', $this->customerForm(['postal_code' => '80000']))
            ->assertSessionHasErrorsIn('createCustomer', ['postal_code']);
    }

    public function test_the_pin_can_be_anywhere_in_the_philippines_but_not_outside(): void
    {
        // Manila is fine (a customer visiting from out of town, say)
        $this->actingAs($this->staff())->post('/customers', $this->customerForm(['latitude' => '14.5995', 'longitude' => '120.9842', 'city' => 'Manila', 'province' => 'Metro Manila']))
            ->assertSessionHasNoErrors();

        // Singapore is not
        $this->actingAs($this->staff())->post('/customers', $this->customerForm(['contact_number' => '09170000009', 'latitude' => '1.3521', 'longitude' => '103.8198']))
            ->assertSessionHasErrorsIn('createCustomer', ['latitude', 'longitude']);

        $this->assertSame(1, Customer::count());
    }

    public function test_editing_a_delivery_address_on_an_order(): void
    {
        $staff = $this->staff();
        $order = $this->order($staff, 1000); // pickup

        // Switch to delivery with nothing typed: the customer's saved address is copied.
        $this->actingAs($staff)->put("/orders/{$order->id}", [
            'scheduled_at' => $order->scheduled_at->format('Y-m-d\\TH:i'),
            'order_type' => 'delivery',
            'delivery_city' => 'Davao City',
            'delivery_province' => 'Davao del Sur',
        ])->assertSessionHasNoErrors();
        $this->assertSame($order->customer->street, $order->fresh()->delivery_street);

        // Then change it to a venue.
        $this->actingAs($staff)->put("/orders/{$order->id}", [
            'scheduled_at' => $order->scheduled_at->format('Y-m-d\\TH:i'),
            'order_type' => 'delivery',
            'delivery_street' => 'J.P. Laurel Ave.',
            'delivery_barangay' => 'Bajada',
            'delivery_city' => 'Davao City',
            'delivery_province' => 'Davao del Sur',
            'delivery_latitude' => '7.0905',
            'delivery_longitude' => '125.613',
        ])->assertSessionHasNoErrors();
        $this->assertSame('Bajada', $order->fresh()->delivery_barangay);

        // Back to pickup with nothing typed: the place becomes the shop.
        $this->actingAs($staff)->put("/orders/{$order->id}", [
            'scheduled_at' => $order->scheduled_at->format('Y-m-d\\TH:i'),
            'order_type' => 'pickup',
        ])->assertSessionHasNoErrors();
        $this->assertSame(config('sweetnest.shop.address.street'), $order->fresh()->delivery_street);
        $this->assertTrue($order->fresh()->picked_up_at_shop);

        // A pickup spot typed by hand has to be complete, like a delivery address.
        $this->actingAs($staff)->put("/orders/{$order->id}", [
            'scheduled_at' => $order->scheduled_at->format('Y-m-d\\TH:i'),
            'order_type' => 'pickup',
            'delivery_street' => 'J.P. Laurel Ave.',
        ])->assertSessionHasErrorsIn('editOrder', ['delivery_barangay']);
    }

    public function test_the_map_is_on_the_order_page_not_the_dashboard(): void
    {
        $staff = $this->staff();
        $delivery = $this->order($staff, 1000, ['order_type' => 'delivery', 'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s')]);

        // Neither dashboard draws a map (Ahmed: the Owner doesn't need delivery locations there).
        $this->actingAs($staff)->get('/dashboard')->assertOk()->assertDontSee('data-react="PinMap"', false);
        $this->actingAs($this->owner())->get('/dashboard')->assertOk()->assertDontSee('data-react="PinMap"', false);

        // The delivery's own page still has its pin and the rider's directions.
        $this->actingAs($staff)->get("/orders/{$delivery->id}")
            ->assertOk()
            ->assertSee('data-react="PinMap"', false)
            ->assertSee('Show on map')
            ->assertSee('Directions for the rider');
    }

    public function test_deactivate_buttons_are_red(): void
    {
        $this->customer();

        $this->actingAs($this->owner())->get('/customers')->assertSee('btn-sm btn-danger">Deactivate', false);
        $this->actingAs($this->owner())->get('/users')->assertSee('btn-sm btn-danger">Deactivate', false);
    }

    public function test_address_helper_formats_and_checks(): void
    {
        $parts = ['street' => '12 Narra St.', 'address_line2' => null, 'barangay' => 'Barangay 5-A', 'city' => 'Davao City', 'province' => 'Davao del Sur', 'postal_code' => null, 'latitude' => null, 'longitude' => null];

        $this->assertSame('12 Narra St., Barangay 5-A, Davao City, Davao del Sur', Address::format($parts));
        $this->assertTrue(Address::isComplete($parts));
        $this->assertFalse(Address::hasPin($parts));
        $this->assertStringStartsWith('https://www.google.com/maps/search/?api=1&query=', Address::mapsUrl($parts));
        $this->assertTrue(Address::inBounds(7.07, 125.61));
        $this->assertTrue(Address::inBounds(14.59, 120.98));
        $this->assertFalse(Address::inBounds(1.35, 103.82));
    }
}
