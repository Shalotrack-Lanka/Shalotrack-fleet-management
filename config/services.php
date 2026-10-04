<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Firebase Web App Config
    |--------------------------------------------------------------------------
    | These values are injected into Blade views for the Firebase JS SDK.
    | They are NOT secrets — they are safe to expose to the browser.
    | The actual auth security is enforced server-side by Firebase and
    | the Laravel encrypted session.
    |
    */
    'firebase' => [
        'api_key'     => env('FIREBASE_API_KEY'),
        'auth_domain' => env('FIREBASE_AUTH_DOMAIN'),
        'app_id'      => env('FIREBASE_APP_ID'),
    ],

    'google_maps' => [
        'api_key' => env('GOOGLE_MAPS_API_KEY'),
    ],

    // Reverse geocoding (coordinates -> address). Free OpenStreetMap Nominatim by
    // default; point GEOCODER_URL at a self-hosted instance if volume grows.
    // GEOCODER_USER_AGENT must identify the app + a contact (Nominatim policy).
    'geocoder' => [
        'url'        => env('GEOCODER_URL', 'https://nominatim.openstreetmap.org'),
        'user_agent' => env('GEOCODER_USER_AGENT', 'ShaloTrack-Fleet/1.0 (fleet.shalotrack.com)'),
    ],

];