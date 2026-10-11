<?php

namespace App\Http\Controllers;

use App\Services\Geocoder;
use App\Support\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/** Address lookups for the map on the customer and delivery address forms. */
class GeoController extends Controller
{
    public function reverse(Request $request, Geocoder $geocoder): JsonResponse
    {
        $b = Address::BOUNDS;
        $data = $request->validate([
            'lat' => ['required', 'numeric', "between:{$b['south']},{$b['north']}"],
            'lng' => ['required', 'numeric', "between:{$b['west']},{$b['east']}"],
        ], [
            'lat.between' => 'Pick a spot inside the Philippines.',
            'lng.between' => 'Pick a spot inside the Philippines.',
        ]);

        try {
            return response()->json($geocoder->reverse((float) $data['lat'], (float) $data['lng']));
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => "Couldn't look up this spot right now. The pin is kept, so just type the address."], 503);
        }
    }

    public function search(Request $request, Geocoder $geocoder): JsonResponse
    {
        $data = $request->validate(['q' => ['required', 'string', 'min:3', 'max:100']], [
            'q.required' => 'Type a place to search for.',
            'q.min' => 'Type at least 3 letters to search.',
        ]);

        try {
            return response()->json(['results' => $geocoder->search($data['q'])]);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => "Search isn't available right now. Click the map instead."], 503);
        }
    }
}
