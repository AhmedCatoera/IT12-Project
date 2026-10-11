<?php

namespace Tests\Feature;

use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\SweetNestData;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase, SweetNestData;

    private function form(array $overrides = []): array
    {
        return $overrides + [
            'amount' => 500,
            'payment_method' => 'cash',
            'paid_at' => now()->subMinute()->format('Y-m-d\TH:i'),
        ];
    }

    public function test_down_payment_confirms_the_order(): void
    {
        $staff = $this->staff();
        $order = $this->order($staff, 1000);

        $this->actingAs($staff)->post("/orders/{$order->id}/payments", $this->form(['amount' => 499]))->assertSessionHasNoErrors();
        $this->assertSame('pending', $order->fresh()->status);

        $this->actingAs($staff)->post("/orders/{$order->id}/payments", $this->form(['amount' => 1]))->assertSessionHasNoErrors();
        $this->assertSame('confirmed', $order->fresh()->status);

        $this->actingAs($staff)->post("/orders/{$order->id}/payments", $this->form(['amount' => 500]))->assertSessionHasNoErrors();

        // Money paid before reaching 50% counts as down payment; after that it is the balance.
        $this->assertSame(['down_payment', 'down_payment', 'balance_payment'], Payment::orderBy('id')->pluck('payment_type')->all());
        $this->assertSame('fully_paid', $order->fresh()->payment_status);
    }

    public function test_a_balance_under_one_peso_can_be_paid(): void
    {
        $staff = $this->staff();
        $order = $this->order($staff, 100.50);
        $this->pay($order, 100, $staff);

        $this->actingAs($staff)->post("/orders/{$order->id}/payments", $this->form(['amount' => 0.50]))->assertSessionHasNoErrors();
        $this->assertSame('fully_paid', $order->fresh()->payment_status);
    }

    /** A payment tried on an order that someone else just cancelled still shows its error. */
    public function test_payment_error_shows_even_when_the_form_is_gone(): void
    {
        $staff = $this->staff();
        $order = $this->order($staff, 1000);
        $order->update(['status' => 'cancelled', 'cancel_reason' => 'Customer changed plans']);

        $this->actingAs($staff)->followingRedirects()->from("/orders/{$order->id}")
            ->post("/orders/{$order->id}/payments", $this->form())
            ->assertSee('payments can no longer be recorded');
    }

    public function test_paying_everything_at_once_is_a_full_payment(): void
    {
        $staff = $this->staff();
        $order = $this->order($staff, 1000);

        $this->actingAs($staff)->post("/orders/{$order->id}/payments", $this->form(['amount' => 1000]));

        $this->assertSame('full_payment', Payment::first()->payment_type);
        $this->assertSame('confirmed', $order->fresh()->status);
        $this->assertSame('fully_paid', $order->fresh()->payment_status);
    }

    public function test_payment_cannot_be_more_than_the_balance(): void
    {
        $staff = $this->staff();
        $order = $this->order($staff, 1000);

        $this->actingAs($staff)->post("/orders/{$order->id}/payments", $this->form(['amount' => 1000.01]))
            ->assertSessionHasErrorsIn('payment', ['amount']);
        $this->assertSame(0, Payment::count());
    }

    /** B11: no payments on cancelled orders. */
    public function test_cancelled_orders_take_no_payments(): void
    {
        $staff = $this->staff();
        $order = $this->order($staff, 1000);
        $order->update(['status' => 'cancelled', 'cancel_reason' => 'Test']);

        $this->actingAs($staff)->post("/orders/{$order->id}/payments", $this->form())
            ->assertSessionHasErrorsIn('payment', ['amount']);
        $this->assertSame(0, Payment::count());
    }

    public function test_only_cash_and_gcash_are_accepted(): void
    {
        $staff = $this->staff();
        $order = $this->order($staff, 1000);

        foreach (['bank_transfer', 'other', 'card'] as $method) {
            $this->actingAs($staff)->post("/orders/{$order->id}/payments", $this->form(['payment_method' => $method, 'reference_no' => 'X-1']))
                ->assertSessionHasErrorsIn('payment', ['payment_method']);
        }
        $this->assertSame(0, Payment::count());
        $this->assertSame(['cash', 'gcash'], array_keys(Payment::METHODS));
    }

    public function test_gcash_needs_a_reference_number_or_a_screenshot(): void
    {
        Storage::fake('public');
        $staff = $this->staff();
        $order = $this->order($staff, 1000);

        // Neither: refused.
        $this->actingAs($staff)->post("/orders/{$order->id}/payments", $this->form(['payment_method' => 'gcash', 'amount' => 100]))
            ->assertSessionHasErrorsIn('payment', ['reference_no']);

        // The reference number alone is enough.
        $this->actingAs($staff)->post("/orders/{$order->id}/payments", $this->form(['payment_method' => 'gcash', 'amount' => 100, 'reference_no' => '1009231177']))
            ->assertSessionHasNoErrors();

        // A screenshot alone is enough too; it is stored and shown through the app.
        $this->actingAs($staff)->post("/orders/{$order->id}/payments", $this->form([
            'payment_method' => 'gcash', 'amount' => 100, 'proof_image' => UploadedFile::fake()->image('gcash.jpg', 600, 900),
        ]))->assertSessionHasNoErrors();

        $payment = Payment::latest('id')->first();
        $this->assertNull($payment->reference_no);
        Storage::disk('public')->assertExists($payment->proof_image);
        $this->assertSame(route('payments.proof', $payment), $payment->proof_image_url);
        $this->actingAs($staff)->get($payment->proof_image_url)->assertOk();
        $this->actingAs($staff)->get("/orders/{$order->id}")->assertOk()->assertSee('Screenshot');
    }

    public function test_cash_needs_no_proof_and_ignores_any_sent(): void
    {
        Storage::fake('public');
        $staff = $this->staff();
        $order = $this->order($staff, 1000);

        $this->actingAs($staff)->post("/orders/{$order->id}/payments", $this->form([
            'payment_method' => 'cash', 'amount' => 100, 'reference_no' => 'CASH-REF', 'proof_image' => UploadedFile::fake()->image('x.jpg'),
        ]))->assertSessionHasNoErrors();

        $payment = Payment::latest('id')->first();
        $this->assertNull($payment->reference_no);
        $this->assertNull($payment->proof_image);
    }

    public function test_payment_date_cannot_be_in_the_future(): void
    {
        $staff = $this->staff();
        $order = $this->order($staff, 1000);

        $this->actingAs($staff)->post("/orders/{$order->id}/payments", $this->form(['paid_at' => now()->addDay()->format('Y-m-d\TH:i')]))
            ->assertSessionHasErrorsIn('payment', ['paid_at']);
    }

    public function test_the_person_who_received_it_is_recorded(): void
    {
        $staff = $this->staff();
        $order = $this->order($staff, 1000);

        $this->actingAs($staff)->post("/orders/{$order->id}/payments", $this->form());

        $this->assertSame($staff->id, Payment::first()->received_by);
    }

    /** B2 + B3: filters combine correctly and totals cover every page, not just the first 15 rows. */
    public function test_payment_list_totals_cover_all_filtered_rows(): void
    {
        $staff = $this->staff();

        for ($i = 0; $i < 18; $i++) {
            $this->pay($this->order($staff, 1000), 100, $staff, 'gcash');
        }
        $this->pay($this->order($staff, 1000), 300, $staff, 'cash');

        $response = $this->actingAs($staff)->get('/payments?method=gcash');
        $response->assertOk()->assertSee('₱1,800.00')->assertSee('from 18 payments');

        $cashOrder = \App\Models\Order::latest('id')->first();
        $this->actingAs($staff)->get('/payments?method=gcash&search=' . $cashOrder->order_number)
            ->assertSee('from 0 payments');
    }

    public function test_saving_a_payment_lands_on_the_payments_tab(): void
    {
        $staff = $this->staff();
        $order = $this->order($staff, 1000);

        $this->actingAs($staff)->post("/orders/{$order->id}/payments", $this->form())
            ->assertRedirect(route('orders.show', $order) . '#payments');
    }

    public function test_a_failed_payment_reopens_on_the_payments_tab(): void
    {
        $staff = $this->staff();
        $order = $this->order($staff, 1000);

        $this->actingAs($staff)->followingRedirects()->from("/orders/{$order->id}")
            ->post("/orders/{$order->id}/payments", $this->form(['amount' => 5000]))
            ->assertSee('cannot be more than the balance')
            ->assertSee('aria-selected="true" data-tab="payments"', false)
            ->assertSee('aria-selected="false" data-tab="order"', false);
    }
}
