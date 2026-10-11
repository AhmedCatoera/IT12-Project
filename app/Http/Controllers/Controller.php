<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

abstract class Controller
{
    /**
     * A text filter from the address bar. Anything that isn't plain text
     * (for example ?status[]=x typed by hand) is ignored instead of crashing.
     */
    protected function textFilter(Request $request, string $key): ?string
    {
        $value = $request->query($key);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    /** A Y-m-d date from the address bar, or null when it is missing or not a real date. */
    protected function dateFilter(Request $request, string $key): ?Carbon
    {
        $value = $this->textFilter($request, $key);

        if ($value === null || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('!Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }

        // Rejects dates that roll over, such as 2026-02-31.
        return $date->format('Y-m-d') === $value ? $date : null;
    }
}
