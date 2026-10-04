<?php

namespace App\Http\Controllers;

use App\Services\ReverseGeocoder;
use Illuminate\Http\Request;

class GeocodeController extends Controller
{
    public function __construct(private ReverseGeocoder $geocoder) {}

    /**
     * GET /geocode/reverse?lat=&lng=
     *
     * Logged-in users only (route group) and throttled per session, so this can't
     * be used as a free open geocoding proxy. Always answers 200: `address` is
     * null when nothing was found and the page falls back to coordinates.
     */
    public function reverse(Request $request)
    {
        $data = $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        $fromCache = true;
        $address   = $this->geocoder->resolve((float) $data['lat'], (float) $data['lng'], $fromCache);

        return response()->json(
            ['success' => true, 'address' => $address, 'cached' => $fromCache],
            200,
            ['Cache-Control' => 'private, max-age=3600']
        );
    }
}