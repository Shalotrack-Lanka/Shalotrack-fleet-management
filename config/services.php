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

];