<?php

namespace App\Http\Controllers;

use App\Services\FirebaseTokenVerifier;
use App\Services\RememberLogin;
use App\Services\ShalotrackApiService;
use App\Support\ApiErrors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * Step 2 of registration: optionally choose a password, THEN create the profile.
 *
 * DECISION (2 Nov 2026, owner): email verification is gone. The phone number, proven by our own OTP, is
 * the only identity check. The email is kept as a plain contact field and as the login name for the
 * optional email + password sign-in. It is NOT proof of anything, so nothing here (or anywhere) may use
 * it for recovery, account linking or trust. Phone-code sign-in always remains the recovery path.
 *
 * Order:
 *   1. /register keeps the entered details in the session (pending_profile)
 *   2. this page lets the browser attach an email + password to the signed-in Firebase account
 *      (optional: "Skip" keeps phone-code sign-in only)
 *   3. the browser sends a FRESH ID token to POST /register/complete
 *   4. the server checks that token, makes it the session's token, and calls POST /api/Customers
 */
class RegistrationCompleteController extends Controller
{
    /**
     * GET /register/password
     */
    public function show()
    {
        if (!Session::has('firebase_token')) {
            return redirect('/login');
        }

        // Nothing waiting: the details were never entered, or the profile is already created.
        if (!is_array(Session::get('pending_profile'))) {
            return redirect('/register');
        }

        $pending = (array) Session::get('pending_profile');

        return view('auth.set-password', ['email' => (string) ($pending['email'] ?? '')]);
    }

    /**
     * POST /register/resign-in
     * Drop the sign-in (session token AND the remember cookie, or /login would silently restore a
     * server session that still has no Firebase user in the browser) but keep the typed details.
     */
    public function resignIn(RememberLogin $remember)
    {
        $remember->forget();
        foreach (['firebase_token', 'firebase_token_expires_at', 'firebase_uid', 'firebase_phone'] as $k) {
            Session::forget($k);
        }

        return redirect('/login');
    }

    /**
     * POST /register/complete
     *
     * The browser sends a FRESH Firebase ID token (taken after the optional password was attached).
     * The server verifies its signature and that it belongs to this session's account. If the token
     * carries an email (a password was set), it must be the one typed on the form. Then the profile
     * is created with that token.
     */
    public function complete(Request $request, FirebaseTokenVerifier $verifier, ShalotrackApiService $api)
    {
        if (!Session::has('firebase_token')) {
            return response()->json(['success' => false, 'expired' => true], 401);
        }

        $idToken = (string) $request->input('idToken', '');
        if ($idToken === '' || strlen($idToken) > 4096) {
            return response()->json([
                'success' => false,
                'message' => 'We could not finish creating your account. Please try again.',
            ], 422);
        }

        try {
            $payload = $verifier->verify($idToken, (string) config('shalotrack.firebase_project_id'));
        } catch (\Throwable $e) {
            Log::info('register-complete: token rejected', ['type' => class_basename($e)]);
            return response()->json([
                'success' => false,
                'message' => 'We could not finish creating your account. Please try again.',
            ], 422);
        }

        $uid = (string) Session::get('firebase_uid');
        if ($uid === '' || !hash_equals($uid, (string) ($payload['sub'] ?? ''))) {
            return response()->json([
                'success' => false,
                'message' => 'We could not finish creating your account. Please try again.',
            ], 422);
        }

        $pending = Session::get('pending_profile');

        // An account that already carries a DIFFERENT email (set on an earlier attempt) must not get a
        // profile with the one typed now, or the login name and the profile would disagree.
        $tokenEmail = strtolower((string) ($payload['email'] ?? ''));
        if (is_array($pending) && $tokenEmail !== ''
            && $tokenEmail !== strtolower((string) ($pending['email'] ?? ''))) {
            return response()->json([
                'success' => false,
                'restart' => true,
                'message' => 'This phone number is already linked to a different email. Go back and enter that email instead.',
            ], 422);
        }

        // Use the fresh token from here on.
        Session::put('firebase_token', $idToken);
        Session::put('firebase_token_expires_at', (int) ($payload['exp'] ?? 0));

        if (is_array($pending)) {
            try {
                $api->createProfile([
                    'FullName'    => $pending['fullName'] ?? '',
                    'Email'       => $pending['email'] ?? '',
                    'PhoneNumber' => Session::get('firebase_phone'),
                    'NicNumber'   => $pending['nicNumber'] ?? '',
                    'Address'     => $pending['address'] ?? null,
                ]);
                Log::info('RegistrationComplete: profile created', ['uid' => $uid]);
            } catch (\Throwable $e) {
                $msg = strtolower($e->getMessage());

                // Two tabs / a double click: the first call already created it. That is success.
                if ((int) $e->getCode() === 409 && str_contains($msg, 'profile already exists')) {
                    Log::info('RegistrationComplete: profile already existed', ['uid' => $uid]);
                } else {
                    // Details the API rejected: the customer must change them, so offer the form again.
                    $restart = in_array((int) $e->getCode(), [400, 409, 422], true);
                    $response = ApiErrors::json($e, 'register', 'We could not create your profile. Please try again.', [
                        409 => str_contains($msg, 'nic')
                            ? 'An account with this NIC number already exists. Check the NIC, or sign in with the phone number you used before.'
                            : (str_contains($msg, 'email')
                                ? 'An account with this email already exists. Use a different email, or sign in with the phone number you used before.'
                                : 'An account with these details already exists. Sign in instead.'),
                        422 => 'Please check your details and try again.',
                        400 => 'Please check your details and try again.',
                    ]);

                    if ($restart && $response->getStatusCode() !== 401) {
                        $data = $response->getData(true);
                        $data['restart'] = true;
                        return response()->json($data, $response->getStatusCode());
                    }

                    return $response;
                }
            }
        }

        Session::forget('pending_profile');

        return response()->json(['success' => true, 'redirect' => '/dashboard']);
    }
}