<?php

namespace Tests\Feature;

use App\Models\StockIn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Date-only fields are stored as plain Y-m-d on every database (see App\Casts\DateOnly). */
class DateStorageTest extends TestCase
{
    use RefreshDatabase;

    public function test_dates_are_saved_without_a_time(): void
    {
        $stockIn = StockIn::create(['received_date' => now()->setTime(15, 30)]);

        $this->assertSame(today()->toDateString(), DB::table('stock_ins')->where('id', $stockIn->id)->value('received_date'));
        $this->assertTrue($stockIn->fresh()->received_date->isToday());
        $this->assertSame('00:00:00', $stockIn->fresh()->received_date->format('H:i:s'));
    }

    public function test_date_filters_include_the_last_day(): void
    {
        StockIn::create(['received_date' => today()]);

        $this->assertSame(1, StockIn::whereBetween('received_date', [today()->subWeek()->toDateString(), today()->toDateString()])->count());
    }
}
