<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ShaloTrack C# API Base URL
    |--------------------------------------------------------------------------
    | All outbound HTTP requests from this portal go through ShalotrackApiService.
    | Never call the C# API directly from a controller.
    |
    */
    'api_base_url' => env('SHALOTRACK_API_BASE_URL', 'https://api.shalotrack.com'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Timeout
    |--------------------------------------------------------------------------
    | Seconds before an outbound API request is considered failed.
    | Keep this tight — the user is waiting on the other side.
    |
    */
    'api_timeout' => env('SHALOTRACK_API_TIMEOUT', 10),

    /*
    |--------------------------------------------------------------------------
    | Firebase Project ID
    |--------------------------------------------------------------------------
    | Used to verify Firebase ID tokens server-side.
    | Must match the Firebase project used by the Android app.
    |
    */
    'firebase_project_id' => env('FIREBASE_PROJECT_ID'),

];