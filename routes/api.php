<?php

use App\Http\Controllers\VehicleController;
use App\Http\Middleware\FirebaseAuthenticated;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

/*
|--------------------------------------------------------------------------
| Browser JSON Routes — ShaloTrack Fleet Portal  (prefix: /api)
|--------------------------------------------------------------------------
|
| Loaded from bootstrap/app.php under the `web` middleware group, so these
| routes get the session cookie (and CSRF protection on any non-GET).
|
| Only routes that the Blade pages actually call live here. The previous
| file registered ~25 duplicate/dead routes (several pointing at controller
| methods that don't exist) — removed to shrink the attack surface.
|
| Every {id} is constrained to a UUID so nothing but a real vehicle ID can
| be forwarded into the C# API URL path.
|
*/

Route::middleware(FirebaseAuthenticated::class)->group(function () {

    // SignalR token — used by dashboard, vehicles/show and trips pages.
    // The SignalR JS client cannot send custom headers on the WebSocket
    // upgrade, so it needs the Firebase token for ?access_token=.
    // This is intentionally the ONLY endpoint that returns the token.
    Route::get('/signalr-token', function () {
        return response()
            ->json(['token' => Session::get('firebase_token')])
            ->header('Cache-Control', 'no-store, private');
    });

    // Vehicle detail as JSON — used by the "Unlink GPS" flow on
    // vehicles/index to read currentAssignmentId.
    Route::get('/vehicles/{id}', [VehicleController::class, 'showJson'])
        ->whereUuid('id');

    // Current location — HTTP fallback poll on vehicles/show when SignalR
    // is unavailable. Path matches what the page already calls.
    Route::get('/CurrentLocations/vehicle/{id}', [VehicleController::class, 'location'])
        ->whereUuid('id');
});