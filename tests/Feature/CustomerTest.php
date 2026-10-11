<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\SweetNestData;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase, SweetNestData;

    private function form(array $overrides = []): array
    {
        return $overrides + [
            'first_name' => 'Maria',
            'middle_name' => 'Clara',
            'last_name' => 'Santos',
            'contact_number' => '0917-123-4567',
            'facebook_name' => 'Maria Santos',
            'street' => 'Blk 7 Lot 3, Narra St.',
            'address_line2' => 'Phase 1',
            'barangay' => 'Santo Niño',
            'city' => 'Davao City',
            'province' => 'Davao del Sur',
            'postal_code' => '8022',
            'latitude' => '7.0846000',
            'longitude' => '125.5093000',
        ];
    }

    public function test_contact_number_is_saved_as_digits_only(): void
    {
        $this->actingAs($this->staff())->post('/customers', $this->form())->assertRedirect();

        $this->assertDatabaseHas('customers', ['last_name' => 'Santos', 'contact_number' => '09171234567', 'is_active' => true]);
    }

    /** B17: the same number typed differently is still a duplicate. */
    public function test_duplicate_number_in_another_format_is_rejected(): void
    {
        $staff = $this->staff();
        $this->actingAs($staff)->post('/customers', $this->form());

        $this->actingAs($staff)->post('/customers', $this->form(['first_name' => 'Other', 'contact_number' => '+63 917 123 4567']))
            ->assertSessionHasErrorsIn('createCustomer', ['contact_number']);
    }

    public function test_contact_number_must_be_eleven_digits(): void
    {
        $staff = $this->staff();

        foreach (['0917123456', '091712345678', '2345678', 'abc'] as $bad) {
            $this->actingAs($staff)->post('/customers', $this->form(['contact_number' => $bad]))
                ->assertSessionHasErrorsIn('createCustomer', ['contact_number']);
        }
        $this->assertSame(0, Customer::count());

        $this->actingAs($staff)->post('/customers', $this->form(['contact_number' => '0998 555 8899']))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('customers', ['contact_number' => '09985558899']);

        $this->assertSame(1, Customer::count());
    }

    public function test_customers_cannot_be_deleted(): void
    {
        $customer = $this->customer();

        $this->actingAs($this->owner())->delete("/customers/{$customer->id}")->assertStatus(405);
        $this->assertModelExists($customer);
    }

    public function test_deactivating_keeps_the_customer_and_their_orders(): void
    {
        $owner = $this->owner();
        $customer = $this->customer();
        $order = $this->order($owner, 500, ['customer' => $customer]);

        $this->actingAs($owner)->patch("/customers/{$customer->id}/status")->assertRedirect();

        $this->assertFalse($customer->fresh()->is_active);
        $this->assertModelExists($order);

        $active = $this->actingAs($owner)->get('/customers')->viewData('customers');
        $this->assertFalse($active->contains('id', $customer->id));
        $inactive = $this->actingAs($owner)->get('/customers?status=inactive')->viewData('customers');
        $this->assertTrue($inactive->contains('id', $customer->id));

        // Activate again
        $this->actingAs($owner)->patch("/customers/{$customer->id}/status");
        $this->assertTrue($customer->fresh()->is_active);
    }

    public function test_deactivated_customer_is_not_offered_for_new_orders(): void
    {
        $customer = $this->customer(['is_active' => false]);

        $this->actingAs($this->staff())->get('/orders/create')->assertOk()->assertDontSee($customer->name);
    }

    /** B2: searching never brings back customers from another tab. */
    public function test_search_respects_the_active_filter(): void
    {
        $active = $this->customer(['first_name' => 'Maria', 'middle_name' => 'Clara', 'last_name' => 'Santos']);
        $inactive = $this->customer(['first_name' => 'Maria', 'last_name' => 'Lopez', 'is_active' => false]);

        $this->actingAs($this->staff())->get('/customers?status=active&search=Maria')
            ->assertSee($active->name)
            ->assertDontSee($inactive->name);
    }

    public function test_search_by_first_and_last_name_together(): void
    {
        $customer = $this->customer(['first_name' => 'Maria', 'middle_name' => 'Clara', 'last_name' => 'Santos']);

        $this->actingAs($this->staff())->get('/customers?search=' . urlencode('Maria Santos'))->assertSee($customer->name);
        $this->actingAs($this->staff())->get('/customers?search=4567')->assertOk();
    }

    public function test_edit_errors_reopen_the_edit_modal(): void
    {
        $staff = $this->staff();
        $taken = $this->customer();
        $customer = $this->customer();

        $this->actingAs($staff)->from('/customers')->put("/customers/{$customer->id}", $this->form(['contact_number' => $taken->contact_number, '_edit_id' => $customer->id]))
            ->assertRedirect('/customers')
            ->assertSessionHasErrorsIn('editCustomer', ['contact_number']);

        $this->actingAs($staff)->followingRedirects()->from('/customers')
            ->put("/customers/{$customer->id}", $this->form(['contact_number' => $taken->contact_number, '_edit_id' => $customer->id]))
            ->assertSee('id="editCustomer"', false)
            ->assertSee('data-open-on-load', false);
    }

    public function test_adding_a_customer_from_the_order_form_returns_to_it(): void
    {
        $this->actingAs($this->staff())->post('/customers', $this->form(['return_to_order' => 1]))
            ->assertRedirect(route('orders.create', ['customer_id' => Customer::first()->id]));
    }
}
