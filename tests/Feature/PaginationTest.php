<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\SweetNestData;
use Tests\TestCase;

/** Tables come in pages so nobody has to scroll a long list (Ahmed, October 1). */
class PaginationTest extends TestCase
{
    use RefreshDatabase, SweetNestData;

    private function dueToday(int $count): void
    {
        $staff = $this->staff();
        for ($i = 0; $i < $count; $i++) {
            $this->order($staff, 1000, ['scheduled_at' => now()->addMinutes(30 + $i)->format('Y-m-d H:i:s')]);
        }
    }

    public function test_dashboard_due_list_shows_five_at_a_time(): void
    {
        $this->dueToday(7);
        $owner = $this->owner();

        $first = $this->actingAs($owner)->get('/dashboard')->assertOk()->assertSee('Showing 1 to 5 of 7');
        $this->assertCount(5, $first->viewData('dueSoon')->items());

        $second = $this->actingAs($owner)->get('/dashboard?page=2')->assertOk()->assertSee('Showing 6 to 7 of 7');
        $this->assertCount(2, $second->viewData('dueSoon')->items());
    }

    public function test_a_page_past_the_end_shows_the_last_page_instead_of_nothing(): void
    {
        $this->dueToday(7);

        // Someone stays on page 9 while orders get handled: show page 2, not "Nothing due".
        $this->actingAs($this->owner())->get('/dashboard?page=9')
            ->assertOk()
            ->assertSee('Showing 6 to 7 of 7')
            ->assertDontSee('Nothing due today or tomorrow');
    }

    public function test_customer_page_pages_orders_but_totals_cover_all_of_them(): void
    {
        $staff = $this->staff();
        $customer = $this->customer();
        for ($i = 0; $i < 12; $i++) {
            $this->order($staff, 100, ['customer' => $customer]);
        }

        $response = $this->actingAs($staff)->get("/customers/{$customer->id}")->assertOk()->assertSee('12 total');
        $this->assertCount(10, $response->viewData('orders')->items());
        $this->assertSame(12, $response->viewData('summary')['orders']);
        $this->assertEquals(1200, $response->viewData('summary')['total']);
    }

    public function test_job_orders_waiting_list_has_its_own_pages(): void
    {
        $staff = $this->staff();
        for ($i = 0; $i < 6; $i++) {
            $this->confirmedOrder($staff);
        }

        $response = $this->actingAs($staff)->get('/job-orders')->assertOk()->assertSee('6 waiting');
        $this->assertCount(5, $response->viewData('waiting')->items());

        $next = $this->actingAs($staff)->get('/job-orders?waiting=2')->assertOk();
        $this->assertCount(1, $next->viewData('waiting')->items());
    }

    public function test_inventory_report_tables_page_separately(): void
    {
        $owner = $this->owner();
        for ($i = 0; $i < 12; $i++) {
            $this->ingredient(5, ['name' => 'Ingredient ' . str_pad((string) $i, 2, '0', STR_PAD_LEFT)]);
        }

        $response = $this->actingAs($owner)->get('/reports/inventory')->assertOk();
        $levels = $response->viewData('levels');
        $this->assertCount(10, $levels->items());
        $this->assertSame(12, $levels->total());
        $this->assertStringContainsString('levels=2', $levels->nextPageUrl());
        $this->assertStringContainsString('#levels', $levels->nextPageUrl());

        // The "Low stock now" tile still counts every ingredient, not just this page.
        $this->assertSame(Ingredient::active()->lowStock()->count(), $response->viewData('lowCount'));

        $this->assertCount(2, $this->actingAs($owner)->get('/reports/inventory?levels=2')->viewData('levels')->items());
    }

    public function test_full_lists_use_fifteen_rows(): void
    {
        $owner = $this->owner();
        for ($i = 0; $i < 16; $i++) {
            $this->ingredient(5);
        }

        $page = $this->actingAs($owner)->get('/inventory')->assertOk()->viewData('ingredients');
        $this->assertSame(15, $page->perPage());
        $this->assertCount(15, $page->items());
    }

    public function test_a_new_report_period_starts_on_page_one_but_a_movement_filter_keeps_the_pages(): void
    {
        $owner = $this->owner();
        for ($i = 0; $i < 12; $i++) {
            $this->ingredient(5, ['name' => 'Ingredient ' . str_pad((string) $i, 2, '0', STR_PAD_LEFT)]);
        }

        $html = $this->actingAs($owner)->get('/reports/inventory?levels=2&used=2')->assertOk()->getContent();

        // The period form and the quick ranges (Today, This week...) drop the table pages.
        $this->assertStringNotContainsString('name="levels"', $html);
        $this->assertStringNotContainsString('name="used"', $html);
        preg_match_all('/<a href="([^"]+)" class="chip/', $html, $chips);
        $this->assertNotEmpty($chips[1]);
        foreach ($chips[1] as $href) {
            $this->assertStringNotContainsString('levels=', html_entity_decode($href));
            $this->assertStringNotContainsString('used=', html_entity_decode($href));
        }

        // The movement type tabs keep "Stock levels now" on page 2 and jump to the movements.
        preg_match_all('/<a href="([^"]+)#movements" class="tab/', $html, $tabs);
        $this->assertCount(4, $tabs[1]);
        foreach ($tabs[1] as $href) {
            $this->assertStringContainsString('levels=2', html_entity_decode($href));
        }
    }
}
