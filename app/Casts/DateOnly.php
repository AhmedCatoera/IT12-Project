<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A calendar date with no time part.
 *
 * Laravel's built-in "date" cast saves "2026-09-27 00:00:00". MySQL DATE columns
 * cut that back to "2026-09-27", but SQLite keeps the time, so a filter like
 * "between Sep 1 and Sep 27" would miss everything dated Sep 27. Saving plain
 * Y-m-d keeps the data identical on both databases.
 */
class DateOnly implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Carbon
    {
        return $value === null ? null : Carbon::parse($value)->startOfDay();
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value === null || $value === '' ? null : Carbon::parse($value)->format('Y-m-d');
    }
}
