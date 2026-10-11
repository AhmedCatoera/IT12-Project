<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\SweetNestData;
use Tests\TestCase;

/** Rounds 6 and 7: regular or rush (from the dates), the cancellation refund, custom items, bread, pickup spots, Davao-only deliveries, delivery fees and tiers. */
class OrderKindTest extends TestCase
{
    use RefreshDatabase, SweetNestData;

    private function form(array $overrides = []): array
    {
        return $overrides + [
            'customer_id' => $this->customer()->id,
            'scheduled_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
            'order_type' => 'pickup',
            'order_kind' => 'regular',
            'items' => [['product_id' => $this->product(1000)->id, 'quantity' => 2, 'unit_price' => 1000]],
        ];
    }

    public function test_regular_or_rush_comes_from_the_due_date(): void
    {
        // Due 2 or more days after the order date = regular; due tomorrow or today = rush.
        $this->assertSame('regular', Order::kindFor(today()->addDays(5), today()));
        $this->assertSame('regular', Order::kindFor(today()->addDays(2), today()));
        $this->assertSame('rush', Order::kindFor(today()->addDay()->setTime(10, 0), today()));
        $this->assertSame('rush', Order::kindFor(today()->setTime(18, 0), today()));
        // Ahmed's table: Friday to Wednesday regular, Tuesday to Wednesday rush.
        $this->assertSame('regular', Order::kindFor('2026-10-14', '2026-10-09'));
        $this->assertSame('regular', Order::kindFor('2026-10-14', '2026-10-12'));
        $this->assertSame('rush', Order::kindFor('2026-10-14', '2026-10-13'));
        $this->assertSame('rush', Order::kindFor('2026-10-14', '2026-10-14'));

        $staff = $this->staff();
        // The form never sends a kind; the server works it out and ignores any kind posted.
        $this->actingAs($staff)->post('/orders', $this->form(['order_kind' => 'rush']))->assertSessionHasNoErrors();
        $this->assertSame('regular', Order::latest('id')->first()->order_kind);
        $this->assertNull(Order::latest('id')->first()->rush_fee_percent);
    }

    public function test_a_rush_order_adds_its_fee_and_is_paid_in_full(): void
    {
        $staff = $this->staff();

        $this->actingAs($staff)->post('/orders', $this->form(['scheduled_at' => now()->addDay()->format('Y-m-d\TH:i'), 'rush_fee_percent' => 40]))->assertSessionHasNoErrors();

        $order = Order::withTotals()->latest('id')->first();
        $this->assertTrue($order->isRush());
        $this->assertSame(2000.0, $order->items_total);
        $this->assertSame(800.0, $order->rush_fee);
        $this->assertSame(2800.0, $order->total_amount);
        $this->assertSame(2800.0, $order->down_payment_required);   // a rush order is paid in full to confirm

        // The order page shows the fee as its own line and asks for the full payment.
        $this->actingAs($staff)->get("/orders/{$order->id}")->assertOk()->assertSee('Rush fee (40%)')->assertSee('₱800.00')->assertSee('₱2,800.00')->assertSee('full payment');

        // Half of it does not confirm the order; the full amount does.
        $this->pay($order, 1400, $staff);
        $this->assertSame('pending', $order->fresh()->status);
        $this->actingAs($staff)->post("/orders/{$order->id}/job-order", ['production_date' => today()->toDateString()])->assertSessionHasErrors();
        $this->pay($order, 1400, $staff);
        $this->assertSame('confirmed', $order->fresh()->status);
        $this->actingAs($staff)->post("/orders/{$order->id}/job-order", ['production_date' => today()->toDateString()])->assertSessionHasNoErrors();

        // A regular order gets its job order on the 50% down payment, as before.
        $regular = $this->confirmedOrder($staff);
        $this->assertSame(500.0, Order::withTotals()->find($regular->id)->total_paid);
        $this->actingAs($staff)->post("/orders/{$regular->id}/job-order", ['production_date' => today()->toDateString()])->assertSessionHasNoErrors();
    }

    public function test_the_fee_can_change_until_a_payment_is_recorded(): void
    {
        $staff = $this->staff();
        $this->actingAs($staff)->post('/orders', $this->form(['scheduled_at' => now()->addDay()->format('Y-m-d\TH:i'), 'rush_fee_percent' => 40]))->assertSessionHasNoErrors();
        $order = Order::withTotals()->latest('id')->first();
        $base = ['scheduled_at' => $order->scheduled_at->format('Y-m-d\TH:i'), 'order_type' => 'pickup'];

        // Edit details: the fee changes and the totals follow.
        $this->actingAs($staff)->put("/orders/{$order->id}", $base + ['rush_fee_percent' => 45])->assertSessionHasNoErrors();
        $this->assertSame(2900.0, Order::withTotals()->find($order->id)->total_amount);

        // Moving the due date 2+ days out makes it regular: no fee at all.
        $this->actingAs($staff)->put("/orders/{$order->id}", ['scheduled_at' => now()->addDays(4)->format('Y-m-d\TH:i'), 'order_type' => 'pickup'])->assertSessionHasNoErrors();
        $fresh = Order::withTotals()->find($order->id);
        $this->assertSame('regular', $fresh->order_kind);
        $this->assertNull($fresh->rush_fee_percent);
        $this->assertSame(2000.0, $fresh->total_amount);
        $this->assertSame(1000.0, $fresh->down_payment_required);
    }

    public function test_a_rush_order_needs_a_fee_percent(): void
    {
        $staff = $this->staff();
        $rush = ['scheduled_at' => now()->addDay()->format('Y-m-d\TH:i')];

        $this->actingAs($staff)->post('/orders', $this->form($rush))->assertSessionHasErrors(['rush_fee_percent']);
        $this->actingAs($staff)->post('/orders', $this->form($rush + ['rush_fee_percent' => 150]))->assertSessionHasErrors(['rush_fee_percent']);
        $this->actingAs($staff)->post('/orders', $this->form($rush + ['rush_fee_percent' => 0]))->assertSessionHasErrors(['rush_fee_percent']);
        $this->assertSame(0, Order::count());

        // A regular order ignores any fee sent.
        $this->actingAs($staff)->post('/orders', $this->form(['rush_fee_percent' => 40]))->assertSessionHasNoErrors();
        $this->assertNull(Order::latest('id')->first()->rush_fee_percent);
    }

    public function test_cancelling_a_fully_paid_order_refunds_half_the_total(): void
    {
        $staff = $this->staff();
        $order = $this->order($staff, 1000);
        $this->pay($order, 1000, $staff);

        // The cancel pop-up tells the amount before confirming.
        $this->actingAs($staff)->get("/orders/{$order->id}")->assertOk()->assertSee('Refund due: ₱500.00');

        $this->actingAs($staff)->patch("/orders/{$order->id}/status", ['status' => 'cancelled', 'cancel_reason' => 'Changed plans'])->assertSessionHasNoErrors();
        $order = $order->fresh();
        $this->assertSame(500.0, $order->refund_amount);   // 50% of the ₱1,000 total
        $this->assertNull($order->refunded_at);
        $this->assertTrue($order->needsRefund());
        $this->actingAs($staff)->get("/orders/{$order->id}")->assertOk()->assertSee('Refund due')->assertSee('Refund given');

        $this->actingAs($staff)->patch("/orders/{$order->id}/refund")->assertSessionHasNoErrors();
        $this->assertNotNull($order->fresh()->refunded_at);
        $this->assertFalse($order->fresh()->needsRefund());
        $this->actingAs($staff)->patch("/orders/{$order->id}/refund")->assertSessionHasErrorsIn('orderStatus', ['refund']);
        $this->actingAs($staff)->from("/orders/{$order->id}")->patch("/orders/{$order->id}/refund")->assertRedirect("/orders/{$order->id}");
        $this->actingAs($staff)->get("/orders/{$order->id}")->assertOk()->assertSee('no refund waiting');   // the refusal is shown, not lost

        // It shows up in the collections report for the day it was given.
        $this->actingAs($this->owner())->get('/reports/collections')->assertOk()->assertSee('Refunds given')->assertSee('₱500.00');
    }

    public function test_only_the_down_payment_paid_means_no_refund(): void
    {
        $staff = $this->staff();

        // The shop keeps the 50% down payment.
        $order = $this->confirmedOrder($staff, 1000);
        $this->actingAs($staff)->get("/orders/{$order->id}")->assertOk()->assertSee('No refund');
        $this->actingAs($staff)->patch("/orders/{$order->id}/status", ['status' => 'cancelled', 'cancel_reason' => 'Changed plans'])->assertSessionHasNoErrors();
        $this->assertSame(0.0, (float) $order->fresh()->refund_amount);
        $this->assertFalse($order->fresh()->needsRefund());
        $this->actingAs($staff)->get("/orders/{$order->id}")->assertOk()->assertDontSee('Refund due')->assertDontSee('Refund given');

        // Nothing paid, nothing to refund either.
        $unpaid = $this->order($staff, 1000);
        $this->actingAs($staff)->patch("/orders/{$unpaid->id}/status", ['status' => 'cancelled', 'cancel_reason' => 'Test']);
        $this->assertSame(0.0, (float) $unpaid->fresh()->refund_amount);

        // A rush order is always fully paid, so cancelling it gives half the total back.
        $this->actingAs($staff)->post('/orders', $this->form(['scheduled_at' => now()->addDay()->format('Y-m-d\TH:i'), 'rush_fee_percent' => 40]))->assertSessionHasNoErrors();
        $rush = Order::withTotals()->latest('id')->first();
        $this->pay($rush, 2800, $staff);
        $this->actingAs($staff)->patch("/orders/{$rush->id}/status", ['status' => 'cancelled', 'cancel_reason' => 'Test'])->assertSessionHasNoErrors();
        $this->assertSame(1400.0, (float) $rush->fresh()->refund_amount);
    }

    public function test_a_custom_item_is_typed_in_instead_of_picked(): void
    {
        $staff = $this->staff();

        $this->actingAs($staff)->post('/orders', $this->form(['items' => [
            ['custom_name' => '3-layer number cake', 'custom_category' => 'cake', 'quantity' => 1, 'unit_price' => 1800, 'cake_type' => 'chiffon', 'flavor' => 'Mocha', 'size' => '8x8'],
            ['custom_name' => 'Banana loaf', 'custom_category' => 'bread', 'quantity' => 3, 'unit_price' => 120, 'cake_type' => 'chiffon'],
        ]]))->assertSessionHasNoErrors();

        $order = Order::withTotals()->latest('id')->first();
        [$cake, $loaf] = $order->items()->orderBy('id')->get();
        $this->assertNull($cake->product_id);
        $this->assertTrue($cake->isCustom());
        $this->assertSame('3-layer number cake', $cake->name);
        $this->assertSame('cake', $cake->category);
        $this->assertSame(['Type' => 'Chiffon', 'Flavor' => 'Mocha', 'Size' => '8x8'], $cake->specs);
        $this->assertSame('Banana loaf', $loaf->name);
        $this->assertNull($loaf->cake_type);              // a bread carries no cake details
        $this->assertSame(2160.0, $order->total_amount);

        $page = $this->actingAs($staff)->get("/orders/{$order->id}")->assertOk();
        $page->assertSee('3-layer number cake')->assertSee('Custom item')->assertSee('Banana loaf');
        $page->assertSee('data-title="Edit 3-layer number cake"', false)->assertDontSee('data-title="Edit Banana loaf"', false);

        // Custom cakes can still be edited like listed ones; custom breads have nothing to edit.
        $this->actingAs($staff)->put("/order-items/{$cake->id}", ['cake_type' => 'moist', 'flavor' => 'Vanilla'])->assertSessionHasNoErrors();
        $this->assertSame('Vanilla', $cake->fresh()->flavor);
        $this->actingAs($staff)->put("/order-items/{$loaf->id}", ['cake_type' => 'moist'])->assertSessionHas('error');

        // The sales report groups it under its category and lists it by name.
        $this->actingAs($this->owner())->get('/reports/sales')->assertOk()->assertSee('3-layer number cake')->assertSee('Bread');
    }

    public function test_an_item_needs_a_product_or_a_custom_name(): void
    {
        $staff = $this->staff();

        $this->actingAs($staff)->post('/orders', $this->form(['items' => [['quantity' => 1, 'unit_price' => 100]]]))
            ->assertSessionHasErrors(['items.0.product_id', 'items.0.custom_name']);
        $this->actingAs($staff)->post('/orders', $this->form(['items' => [['custom_name' => 'Mystery box', 'quantity' => 1, 'unit_price' => 100]]]))
            ->assertSessionHasErrors(['items.0.custom_category']);
        $this->actingAs($staff)->post('/orders', $this->form(['items' => [['custom_name' => 'Mystery box', 'custom_category' => 'toys', 'quantity' => 1, 'unit_price' => 100]]]))
            ->assertSessionHasErrors(['items.0.custom_category']);
        $this->assertSame(0, Order::count());

        // A row with a product is that product; leftover custom fields are ignored.
        $this->actingAs($staff)->post('/orders', $this->form(['items' => [
            ['product_id' => $this->product(500)->id, 'custom_name' => 'leftover', 'custom_category' => 'bread', 'quantity' => 1, 'unit_price' => 500],
        ]]))->assertSessionHasNoErrors();
        $item = Order::latest('id')->first()->items()->first();
        $this->assertNotNull($item->product_id);
        $this->assertNull($item->custom_name);
        $this->assertFalse($item->isCustom());
    }

    public function test_the_type_and_fee_are_fixed_once_a_payment_is_recorded(): void
    {
        $staff = $this->staff();
        $this->actingAs($staff)->post('/orders', $this->form(['scheduled_at' => now()->addDay()->setTime(15, 0)->format('Y-m-d\TH:i'), 'rush_fee_percent' => 40]))->assertSessionHasNoErrors();
        $order = Order::withTotals()->latest('id')->first();
        $this->pay($order, 2800, $staff);
        $base = ['scheduled_at' => $order->scheduled_at->format('Y-m-d\TH:i'), 'order_type' => 'pickup'];

        // Changing the fee, or moving the date so the order turns regular, is refused.
        $this->actingAs($staff)->put("/orders/{$order->id}", $base + ['rush_fee_percent' => 45])->assertSessionHasErrorsIn('editOrder', ['rush_fee_percent']);
        $this->actingAs($staff)->put("/orders/{$order->id}", ['scheduled_at' => now()->addDays(5)->format('Y-m-d\TH:i'), 'order_type' => 'pickup'])->assertSessionHasErrorsIn('editOrder', ['scheduled_at']);
        $this->assertSame(40.0, (float) $order->fresh()->rush_fee_percent);
        $this->assertSame('rush', $order->fresh()->order_kind);
        $this->actingAs($staff)->get("/orders/{$order->id}")->assertOk()->assertSee('once a payment was recorded');

        // Other details still change: same day, a later hour, with the fee sent back unchanged.
        $this->actingAs($staff)->put("/orders/{$order->id}", ['scheduled_at' => now()->addDay()->setTime(17, 0)->format('Y-m-d\TH:i'), 'order_type' => 'pickup', 'rush_fee_percent' => 40, 'notes' => 'Ring the bell'])->assertSessionHasNoErrors();
        $this->assertSame('Ring the bell', $order->fresh()->notes);

        // A paid regular order can't be moved to tomorrow either (that would make it rush).
        $regular = $this->confirmedOrder($staff);
        $this->actingAs($staff)->put("/orders/{$regular->id}", ['scheduled_at' => now()->addDay()->format('Y-m-d\TH:i'), 'order_type' => 'pickup'])->assertSessionHasErrorsIn('editOrder', ['scheduled_at']);
    }

    public function test_deliveries_only_go_around_davao_and_carry_the_delivery_fee(): void
    {
        $staff = $this->staff();
        $davao = ['delivery_street' => 'Door 3, J.P. Laurel Ave.', 'delivery_barangay' => 'Bajada', 'delivery_city' => 'Davao City', 'delivery_province' => 'Davao del Sur'];

        // The fee from the delivery app is its own line and part of the total and the down payment.
        $this->actingAs($staff)->post('/orders', $this->form(['order_type' => 'delivery', 'delivery_fee' => 85] + $davao))->assertSessionHasNoErrors();
        $order = Order::withTotals()->latest('id')->first();
        $this->assertSame(85.0, $order->delivery_fee_amount);
        $this->assertSame(2085.0, $order->total_amount);
        $this->assertSame(1042.5, $order->down_payment_required);
        $this->actingAs($staff)->get("/orders/{$order->id}")->assertOk()->assertSee('Delivery fee')->assertSee('₱85.00')->assertSee('₱2,085.00');

        // Changing to pickup drops the fee; the fee can be edited on a delivery.
        $this->actingAs($staff)->put("/orders/{$order->id}", ['scheduled_at' => $order->scheduled_at->format('Y-m-d\TH:i'), 'order_type' => 'delivery', 'delivery_fee' => 120] + $davao)->assertSessionHasNoErrors();
        $this->assertSame(120.0, Order::withTotals()->find($order->id)->delivery_fee_amount);
        $this->actingAs($staff)->put("/orders/{$order->id}", ['scheduled_at' => $order->scheduled_at->format('Y-m-d\TH:i'), 'order_type' => 'pickup', 'delivery_fee' => 120])->assertSessionHasNoErrors();
        $this->assertSame(0.0, Order::withTotals()->find($order->id)->delivery_fee_amount);
        $this->assertNull($order->fresh()->delivery_fee);

        // An address outside Davao City is refused for delivery, typed or saved.
        $this->actingAs($staff)->post('/orders', $this->form(['order_type' => 'delivery', 'delivery_city' => 'Tagum City', 'delivery_province' => 'Davao del Norte'] + $davao))
            ->assertSessionHasErrors(['order_type']);
        $farAway = $this->customer(['city' => 'Digos City']);
        $this->actingAs($staff)->post('/orders', $this->form(['order_type' => 'delivery', 'customer_id' => $farAway->id]))->assertSessionHasErrors(['order_type']);
        // The same customer can still order for pickup, at the shop or at a spot they name.
        $this->actingAs($staff)->post('/orders', $this->form(['order_type' => 'pickup', 'customer_id' => $farAway->id]))->assertSessionHasNoErrors();
        $this->assertTrue(Order::latest('id')->first()->picked_up_at_shop);
        $this->assertSame(0, Order::where('customer_id', $farAway->id)->where('order_type', 'delivery')->count());
    }

    public function test_an_edit_cannot_leave_the_customer_overpaid(): void
    {
        $staff = $this->staff();
        $davao = ['delivery_street' => 'Door 3, J.P. Laurel Ave.', 'delivery_barangay' => 'Bajada', 'delivery_city' => 'Davao City', 'delivery_province' => 'Davao del Sur'];
        $this->actingAs($staff)->post('/orders', $this->form(['order_type' => 'delivery', 'delivery_fee' => 100] + $davao))->assertSessionHasNoErrors();
        $order = Order::withTotals()->latest('id')->first();
        $this->pay($order, 2100, $staff);   // paid in full, fee included
        $base = ['scheduled_at' => $order->scheduled_at->format('Y-m-d\TH:i')];

        // Dropping the fee, or the delivery, would put the total under what was paid: refused.
        $this->actingAs($staff)->put("/orders/{$order->id}", $base + ['order_type' => 'delivery', 'delivery_fee' => 20] + $davao)->assertSessionHasErrorsIn('editOrder', ['delivery_fee']);
        $this->actingAs($staff)->put("/orders/{$order->id}", $base + ['order_type' => 'pickup'])->assertSessionHasErrorsIn('editOrder', ['delivery_fee']);
        $this->assertSame(100.0, (float) $order->fresh()->delivery_fee);

        // A higher fee is fine: the difference becomes the balance.
        $this->actingAs($staff)->put("/orders/{$order->id}", $base + ['order_type' => 'delivery', 'delivery_fee' => 150] + $davao)->assertSessionHasNoErrors();
        $this->assertSame(50.0, Order::withTotals()->find($order->id)->balance_due);
    }

    public function test_an_edit_without_a_date_change_keeps_the_kind(): void
    {
        $staff = $this->staff();
        // A stored kind that disagrees with its dates (an order from before this rule) is left alone by a notes-only edit.
        $order = $this->order($staff);
        $order->update(['order_kind' => 'rush', 'rush_fee_percent' => 40]);
        $this->actingAs($staff)->put("/orders/{$order->id}", ['scheduled_at' => $order->scheduled_at->format('Y-m-d\TH:i'), 'order_type' => 'pickup', 'rush_fee_percent' => 40, 'notes' => 'Keep'])->assertSessionHasNoErrors();
        $this->assertSame('rush', $order->fresh()->order_kind);

        // A pickup order kept with no spot (older data) still reads as picked up at the shop.
        $order->update(\App\Support\Address::withPrefix(\App\Support\Address::empty(), 'delivery_'));
        $this->assertTrue($order->fresh()->picked_up_at_shop);
        $this->actingAs($staff)->get("/orders/{$order->id}")->assertOk()->assertSee(config('sweetnest.shop.address.street'))->assertSee('The shop');
    }

    public function test_cakes_can_have_tiers(): void
    {
        $staff = $this->staff();
        $this->actingAs($staff)->post('/orders', $this->form(['items' => [
            ['product_id' => $this->product(2500)->id, 'quantity' => 1, 'unit_price' => 2500, 'cake_type' => 'moist', 'flavor' => 'Chocolate', 'size' => '8x8', 'tiers' => 2],
        ]]))->assertSessionHasNoErrors();
        $item = Order::latest('id')->first()->items()->first();
        $this->assertSame(2, $item->tiers);
        $this->assertSame('2 tiers', $item->specs['Tiers']);

        // 4 tiers is not on the list; a bread never has tiers.
        $this->actingAs($staff)->post('/orders', $this->form(['items' => [['product_id' => $this->product(2500)->id, 'quantity' => 1, 'unit_price' => 2500, 'tiers' => 4]]]))->assertSessionHasErrors(['items.0.tiers']);
        $this->actingAs($staff)->put("/order-items/{$item->id}", ['cake_type' => 'moist', 'flavor' => 'Chocolate', 'tiers' => 3])->assertSessionHasNoErrors();
        $this->assertSame(3, $item->fresh()->tiers);
    }

    public function test_bread_is_a_product_category(): void
    {
        $owner = $this->owner();

        $this->assertArrayHasKey('bread', Product::CATEGORIES);
        $this->actingAs($owner)->post('/products', ['category' => 'bread', 'name' => 'Pandesal (bag of 10)', 'base_price' => 50])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('products', ['name' => 'Pandesal (bag of 10)', 'category' => 'bread']);
        $this->actingAs($owner)->get('/products?category=bread')->assertOk()->assertSee('Pandesal (bag of 10)');

        // A bread on an order carries no cake details.
        $bread = Product::where('name', 'Pandesal (bag of 10)')->first();
        $this->actingAs($owner)->post('/orders', $this->form(['items' => [['product_id' => $bread->id, 'quantity' => 2, 'unit_price' => 50, 'cake_type' => 'chiffon', 'flavor' => 'Ube']]]))->assertSessionHasNoErrors();
        $this->assertNull(Order::latest('id')->first()->items()->first()->cake_type);
    }

    public function test_pickup_orders_show_where_the_customer_collects_them(): void
    {
        $staff = $this->staff();
        $order = $this->order($staff);

        $this->actingAs($staff)->get("/orders/{$order->id}")->assertOk()->assertSee('Pick up at')->assertSee(config('sweetnest.shop.address.street'))->assertSee('The shop');
    }
}
