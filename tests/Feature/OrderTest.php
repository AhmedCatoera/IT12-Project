<?php

namespace Tests\Feature;

use App\Models\JobOrder;
use App\Models\Order;
use App\Services\JobOrderWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\SweetNestData;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase, SweetNestData;

    private function form(array $overrides = []): array
    {
        return $overrides + [
            'customer_id' => $this->customer()->id,
            'scheduled_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
            'order_type' => 'pickup',
            'items' => [
                ['product_id' => $this->product(1200)->id, 'quantity' => 1, 'unit_price' => 1200, 'cake_type' => 'chiffon', 'flavor' => 'Chocolate', 'size' => '8x8'],
                ['product_id' => $this->product(300)->id, 'quantity' => 2, 'unit_price' => 300],
            ],
        ];
    }

    public function test_new_order_is_saved_with_computed_totals(): void
    {
        $staff = $this->staff();

        $this->actingAs($staff)->post('/orders', $this->form())->assertRedirect();

        $order = Order::withTotals()->first();
        $this->assertSame('pending', $order->status);
        $this->assertSame(1800.0, $order->total_amount);
        $this->assertSame(900.0, $order->down_payment_required);
        $this->assertSame(0.0, $order->total_paid);
        $this->assertSame('unpaid', $order->payment_status);
        $this->assertSame($staff->id, $order->created_by);
        $this->assertSame(sprintf('SN-%s-%04d', now()->year, $order->id), $order->order_number);
        $this->assertCount(2, $order->items);
    }

    /** B1: order numbers come from the id, so they never repeat. */
    public function test_order_numbers_are_unique_and_orders_cannot_be_deleted(): void
    {
        $staff = $this->staff();
        $first = $this->order($staff);
        $second = $this->order($staff);

        $this->assertNotSame($first->order_number, $second->order_number);
        $this->actingAs($staff)->delete("/orders/{$first->id}")->assertStatus(405);
        $this->assertModelExists($first);
    }

    /** B18: an order can't be due in the past. */
    public function test_due_date_cannot_be_in_the_past(): void
    {
        $this->actingAs($this->staff())->post('/orders', $this->form(['scheduled_at' => now()->subDay()->format('Y-m-d\TH:i')]))
            ->assertSessionHasErrors('scheduled_at');

        $this->assertSame(0, Order::count());
    }

    /** F1: a delivery with no address typed gets a copy of the customer's saved address (and pin). */
    public function test_delivery_uses_saved_address_when_left_blank(): void
    {
        $customer = $this->customer(['street' => 'Purok 3, Bayabas Rd.', 'address_line2' => 'Crossing Bayabas', 'barangay' => 'Bayabas']);

        $this->actingAs($this->staff())->post('/orders', $this->form([
            'customer_id' => $customer->id,
            'order_type' => 'delivery',
            'delivery_city' => 'Davao City',       // pre-filled by the form
            'delivery_province' => 'Davao del Sur',
        ]))->assertSessionHasNoErrors();

        $order = Order::first();
        $this->assertSame('Purok 3, Bayabas Rd.', $order->delivery_street);
        $this->assertSame('Bayabas', $order->delivery_barangay);
        $this->assertSame(7.0302, $order->delivery_latitude);
        $this->assertSame('Purok 3, Bayabas Rd., Crossing Bayabas, Brgy. Bayabas, Davao City, Davao del Sur 8025', $order->delivery_address);

        // The order keeps its own copy: the customer moving later doesn't change it.
        $customer->update(['street' => 'New house', 'barangay' => 'Matina Crossing']);
        $this->assertSame('Bayabas', $order->fresh()->delivery_barangay);
    }

    public function test_delivery_can_go_to_another_address(): void
    {
        $this->actingAs($this->staff())->post('/orders', $this->form([
            'order_type' => 'delivery',
            'delivery_street' => 'J.P. Laurel Ave.',
            'delivery_address_line2' => 'Function hall, 3rd floor',
            'delivery_barangay' => 'Bajada',
            'delivery_city' => 'Davao City',
            'delivery_province' => 'Davao del Sur',
            'delivery_latitude' => '7.0905',
            'delivery_longitude' => '125.6130',
        ]))->assertSessionHasNoErrors();

        $order = Order::first();
        $this->assertSame('Bajada', $order->delivery_barangay);
        $this->assertStringContainsString('destination=7.0905,125.613', $order->delivery_maps_url);
    }

    public function test_a_typed_delivery_address_must_be_complete(): void
    {
        $this->actingAs($this->staff())->post('/orders', $this->form([
            'order_type' => 'delivery',
            'delivery_street' => 'J.P. Laurel Ave.',
            'delivery_city' => 'Davao City',
            'delivery_province' => 'Davao del Sur',
        ]))->assertSessionHasErrors('delivery_barangay');

        $this->assertSame(0, Order::count());
    }

    public function test_delivery_without_any_address_is_rejected(): void
    {
        $customer = $this->customer(['street' => null, 'barangay' => null, 'city' => null, 'province' => null, 'latitude' => null, 'longitude' => null]);

        $this->actingAs($this->staff())->post('/orders', $this->form(['customer_id' => $customer->id, 'order_type' => 'delivery']))
            ->assertSessionHasErrors('delivery_street');
    }

    public function test_delivery_pin_must_be_in_the_philippines(): void
    {
        $this->actingAs($this->staff())->post('/orders', $this->form([
            'order_type' => 'delivery',
            'delivery_street' => 'Orchard Rd.',
            'delivery_barangay' => 'Orchard',
            'delivery_city' => 'Singapore',
            'delivery_province' => 'Singapore',
            'delivery_latitude' => '1.3521',
            'delivery_longitude' => '103.8198',
        ]))->assertSessionHasErrors(['delivery_latitude', 'delivery_longitude']);
    }

    public function test_pickup_orders_start_at_the_shop_or_a_spot_the_customer_names(): void
    {
        // Nothing typed: the customer collects it at the shop.
        $this->actingAs($this->staff())->post('/orders', $this->form())->assertSessionHasNoErrors();
        $order = Order::first();
        $this->assertSame(config('sweetnest.shop.address.street'), $order->delivery_street);
        $this->assertTrue($order->picked_up_at_shop);
        $this->actingAs($this->staff())->get("/orders/{$order->id}")->assertOk()->assertSee('Pick up at')->assertSee('The shop');

        // A meet-up spot typed in is kept with the order (and must be complete).
        $this->actingAs($this->staff())->post('/orders', $this->form(['delivery_street' => 'SM City Davao', 'delivery_barangay' => 'Matina', 'delivery_city' => 'Davao City', 'delivery_province' => 'Davao del Sur']))->assertSessionHasNoErrors();
        $spot = Order::latest('id')->first();
        $this->assertSame('SM City Davao', $spot->delivery_street);
        $this->assertFalse($spot->picked_up_at_shop);
        $this->actingAs($this->staff())->get("/orders/{$spot->id}")->assertOk()->assertSee('SM City Davao')->assertSee('Meet-up spot');
        $this->actingAs($this->staff())->post('/orders', $this->form(['delivery_street' => 'Somewhere']))->assertSessionHasErrors(['delivery_barangay']);
    }

    public function test_an_order_must_be_worth_more_than_zero(): void
    {
        $form = $this->form();
        $form['items'][0]['unit_price'] = 0;
        $form['items'][1]['unit_price'] = 0;

        $this->actingAs($this->staff())->post('/orders', $form)->assertSessionHasErrors('items');
        $this->assertSame(0, Order::count());
    }

    public function test_an_order_out_for_delivery_stays_a_delivery(): void
    {
        $staff = $this->staff();
        $order = $this->confirmedOrder($staff, 1000, ['order_type' => 'delivery']);
        $job = $this->jobOrder($order);
        app(JobOrderWorkflow::class)->apply($job, 'start');
        app(JobOrderWorkflow::class)->apply($job->fresh(), 'complete');
        $order->update(['status' => 'out_for_delivery']);

        $this->actingAs($staff)->put("/orders/{$order->id}", [
            'scheduled_at' => $order->scheduled_at->format('Y-m-d\TH:i'),
            'order_type' => 'pickup',
        ])->assertSessionHasErrorsIn('editOrder', ['order_type']);

        $this->assertSame('delivery', $order->fresh()->order_type);
    }

    public function test_inactive_products_cannot_be_ordered(): void
    {
        $form = $this->form();
        $form['items'][0]['product_id'] = $this->product(500, ['is_active' => false])->id;

        $this->actingAs($this->staff())->post('/orders', $form)->assertSessionHasErrors('items.0.product_id');
    }

    public function test_reference_photo_is_stored_and_shown_through_the_app(): void
    {
        Storage::fake('public');
        $form = $this->form();
        $form['items'][0]['reference_image'] = UploadedFile::fake()->image('design.jpg', 400, 300);

        $this->actingAs($staff = $this->staff())->post('/orders', $form)->assertSessionHasNoErrors();

        $item = Order::first()->items()->whereNotNull('reference_image')->first();
        Storage::disk('public')->assertExists($item->reference_image);
        $this->assertSame(route('orderItems.image', $item), $item->reference_image_url);
        $this->actingAs($staff)->get($item->reference_image_url)->assertOk();
    }

    public function test_details_can_be_edited_only_while_open(): void
    {
        $staff = $this->staff();
        $order = $this->order($staff);

        $this->actingAs($staff)->put("/orders/{$order->id}", [
            'scheduled_at' => now()->addDays(5)->format('Y-m-d\TH:i'),
            'order_type' => 'pickup',
            'notes' => 'Add candles',
        ])->assertSessionHasNoErrors();
        $this->assertSame('Add candles', $order->fresh()->notes);

        $order->update(['status' => 'cancelled', 'cancel_reason' => 'Test']);
        $this->actingAs($staff)->put("/orders/{$order->id}", [
            'scheduled_at' => now()->addDays(5)->format('Y-m-d\TH:i'),
            'order_type' => 'pickup',
            'notes' => 'Changed after cancel',
        ])->assertSessionHas('error');
        $this->assertSame('Add candles', $order->fresh()->notes);
    }

    public function test_item_details_can_change_until_baking_starts(): void
    {
        $staff = $this->staff();
        $order = $this->confirmedOrder($staff);
        $item = $order->items()->first();

        $this->actingAs($staff)->put("/order-items/{$item->id}", ['cake_type' => 'chiffon', 'flavor' => 'Ube', 'inscription' => 'Happy 7th!'])
            ->assertSessionHasNoErrors();
        $this->assertSame('Ube', $item->fresh()->flavor);

        $job = $this->jobOrder($order);
        app(JobOrderWorkflow::class)->apply($job, 'start');

        $this->actingAs($staff)->put("/order-items/{$item->id}", ['cake_type' => 'chiffon', 'flavor' => 'Mango'])->assertSessionHas('error');
        $this->assertSame('Ube', $item->fresh()->flavor);
    }

    public function test_cake_details_come_from_the_lists(): void
    {
        $staff = $this->staff();

        // Type, flavor and size must be choices from config/sweetnest.php.
        $this->actingAs($staff)->post('/orders', $this->form(['items' => [
            ['product_id' => $this->product()->id, 'quantity' => 1, 'unit_price' => 500, 'cake_type' => 'layered', 'flavor' => 'Durian', 'size' => '12x12'],
        ]]))->assertSessionHasErrors(['items.0.cake_type', 'items.0.flavor', 'items.0.size']);

        // A flavor from the wrong list is dropped: "Cream cheese" is an icing, not a chiffon flavor.
        $this->actingAs($staff)->post('/orders', $this->form(['items' => [
            ['product_id' => $this->product()->id, 'quantity' => 1, 'unit_price' => 500, 'cake_type' => 'chiffon', 'flavor' => 'Cream cheese', 'size' => '6x6'],
        ]]))->assertSessionHasNoErrors();
        $item = Order::latest('id')->first()->items()->first();
        $this->assertSame('chiffon', $item->cake_type);
        $this->assertNull($item->flavor);
        $this->assertSame('6x6', $item->size);
        $this->assertSame(['Type' => 'Chiffon', 'Size' => '6x6'], $item->specs);

        // Sponge and moist cakes are flavored by the icing. A message of "0" is a message, not nothing.
        $this->actingAs($staff)->put("/order-items/{$item->id}", ['cake_type' => 'moist', 'flavor' => 'Vanilla', 'size' => '7x5', 'inscription' => '0'])->assertSessionHasNoErrors();
        $this->assertSame(['Type' => 'Moist', 'Icing' => 'Vanilla', 'Size' => '7x5'], $item->fresh()->specs);
        $this->assertSame('0', $item->fresh()->inscription);
    }

    public function test_only_cakes_carry_details(): void
    {
        $staff = $this->staff();
        $mug = $this->product(180, ['category' => 'souvenir', 'name' => 'Mug']);

        // Details sent for a souvenir are ignored, not saved.
        $this->actingAs($staff)->post('/orders', $this->form(['items' => [
            ['product_id' => $mug->id, 'quantity' => 2, 'unit_price' => 180, 'cake_type' => 'chiffon', 'flavor' => 'Ube', 'size' => '6x6', 'design_theme' => 'Blue', 'inscription' => 'Migo'],
        ]]))->assertSessionHasNoErrors();
        $item = Order::latest('id')->first()->items()->first();
        $this->assertNull($item->cake_type);
        $this->assertNull($item->flavor);
        $this->assertNull($item->design_theme);
        $this->assertSame([], $item->specs);

        // And there is nothing to edit on it.
        $this->actingAs($staff)->put("/order-items/{$item->id}", ['cake_type' => 'moist'])->assertSessionHas('error');
        $this->actingAs($staff)->get("/orders/{$item->order_id}")->assertOk()->assertDontSee('data-modal-open="editItem"', false);
    }

    public function test_cancelling_needs_a_reason(): void
    {
        $staff = $this->staff();
        $order = $this->order($staff);

        $this->actingAs($staff)->patch("/orders/{$order->id}/status", ['status' => 'cancelled', 'cancel_reason' => ''])
            ->assertSessionHasErrorsIn('orderStatus', ['cancel_reason']);
        $this->assertSame('pending', $order->fresh()->status);

        $this->actingAs($staff)->patch("/orders/{$order->id}/status", ['status' => 'cancelled', 'cancel_reason' => 'Customer changed plans'])
            ->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame('Customer changed plans', $order->fresh()->cancel_reason);
    }

    /** B9: cancelling the order also cancels its unfinished job order. */
    public function test_cancelling_an_order_cancels_its_job_order(): void
    {
        $staff = $this->staff();
        $job = $this->startedJob($staff);

        $this->actingAs($staff)->patch("/orders/{$job->order_id}/status", ['status' => 'cancelled', 'cancel_reason' => 'No longer needed']);

        $this->assertSame('cancelled', $job->fresh()->status);
    }

    /** B10: statuses only move along the allowed path. */
    public function test_statuses_set_by_the_system_cannot_be_picked_by_hand(): void
    {
        $staff = $this->staff();
        $order = $this->order($staff);

        foreach (['confirmed', 'in_production', 'ready_for_release', 'completed'] as $status) {
            $this->actingAs($staff)->patch("/orders/{$order->id}/status", ['status' => $status])
                ->assertSessionHasErrorsIn('orderStatus', ['status']);
        }

        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_order_can_only_be_completed_when_fully_paid(): void
    {
        $staff = $this->staff();
        $job = $this->startedJob($staff, 1000);
        app(JobOrderWorkflow::class)->apply($job, 'complete');
        $order = $job->order->fresh();
        $this->assertSame('ready_for_release', $order->status);

        $this->actingAs($staff)->patch("/orders/{$order->id}/status", ['status' => 'completed'])
            ->assertSessionHasErrorsIn('orderStatus', ['status']);
        $this->assertSame('ready_for_release', $order->fresh()->status);

        $this->pay($order, 500, $staff);
        $this->actingAs($staff)->patch("/orders/{$order->id}/status", ['status' => 'completed'])->assertSessionHasNoErrors();
        $this->assertSame('completed', $order->fresh()->status);
    }

    public function test_delivery_orders_go_out_for_delivery_before_completing(): void
    {
        $staff = $this->staff();
        $order = $this->confirmedOrder($staff, 1000, ['order_type' => 'delivery']);
        $job = $this->jobOrder($order);
        app(JobOrderWorkflow::class)->apply($job, 'start');
        app(JobOrderWorkflow::class)->apply($job->fresh(), 'complete');
        $this->pay($order->fresh(), 500, $staff);

        $this->actingAs($staff)->patch("/orders/{$order->id}/status", ['status' => 'completed'])
            ->assertSessionHasErrorsIn('orderStatus', ['status']);

        $this->actingAs($staff)->patch("/orders/{$order->id}/status", ['status' => 'out_for_delivery'])->assertSessionHasNoErrors();
        $this->actingAs($staff)->patch("/orders/{$order->id}/status", ['status' => 'completed'])->assertSessionHasNoErrors();
        $this->assertSame('completed', $order->fresh()->status);
    }

    public function test_pickup_orders_never_go_out_for_delivery(): void
    {
        $staff = $this->staff();
        $job = $this->startedJob($staff);
        app(JobOrderWorkflow::class)->apply($job, 'complete');

        $this->actingAs($staff)->patch("/orders/{$job->order_id}/status", ['status' => 'out_for_delivery'])
            ->assertSessionHasErrorsIn('orderStatus', ['status']);
    }

    public function test_order_list_filters_combine_with_search(): void
    {
        $staff = $this->staff();
        $maria = $this->customer(['first_name' => 'Maria', 'last_name' => 'Santos']);
        $pickup = $this->order($staff, 500, ['customer' => $maria]);
        $delivery = $this->order($staff, 500, ['customer' => $maria, 'order_type' => 'delivery']);

        $this->actingAs($staff)->get('/orders?status=open&type=delivery&search=Maria')
            ->assertOk()
            ->assertSee($delivery->order_number)
            ->assertDontSee($pickup->order_number);
    }

    public function test_order_page_splits_into_order_and_payments_tabs(): void
    {
        $staff = $this->staff();
        $order = $this->confirmedOrder($staff, 1000);

        $this->actingAs($staff)->get("/orders/{$order->id}")
            ->assertOk()
            ->assertSee('aria-selected="true" data-tab="order"', false)
            ->assertSeeInOrder([
                'data-tab-panel="order"', 'Items', 'Job order', 'Details',
                'data-tab-panel="payments"', 'Down payment', 'Paid so far', 'Balance',
            ], false);
    }
}
