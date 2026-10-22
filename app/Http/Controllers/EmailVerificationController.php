<?php

namespace App\Http\Controllers;

use App\Services\FirebaseTokenVerifier;
use App\Services\ShalotrackApiService;
use App\Support\ApiErrors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * Step 2 of registration: prove the email is the customer's, THEN create the profile.
 *
 * The API (CustomerService.CreateAsync) refuses to create a profile unless the bearer token already
 * carries email_verified = true, and a phone-OTP token never does. So the order is:
 *   1. /register keeps the entered details in the session (pending_profile)
 *   2. this page asks Firebase (in the browser) to email a verification link to that address
 *   3. once the customer has clicked it, the browser sends a FRESH ID token to /email/mark-verified
 *   4. the server checks that token, makes it the session's token, and only now calls POST /api/Customers
 */
class EmailVerificationController extends Controller
{
    /**
     * GET /email/verify
     * Show the email verification pending page.
     */
    public function show()
    {
        if (!Session::has('firebase_token')) {
            return redirect('/login');
        }

        // Nothing waiting to be verified: either already done, or the details were never entered.
        if (!is_array(Session::get('pending_profile'))) {
            return Session::get('email_verified', false) ? redirect('/dashboard') : redirect('/register');
        }

        $pending = (array) Session::get('pending_profile');

        return view('auth.verify-email', ['email' => (string) ($pending['email'] ?? '')]);
    }

    /**
     * POST /email/mark-verified
     *
     * SECURITY: this used to set the flag for ANY signed-in session that asked — a user could skip the
     * email check with one request. The browser must send a FRESH Firebase ID token (taken after
     * reloading the user); the server verifies its signature, that it belongs to this session's
     * account, that Firebase itself says email_verified = true and that the verified address is the
     * one entered on the form. Only then is the profile created.
     */
    public function markVerified(Request $request, FirebaseTokenVerifier $verifier, ShalotrackApiService $api)
    {
        if (!Session::has('firebase_token')) {
            return response()->json(['success' => false, 'expired' => true], 401);
        }

        $idToken = (string) $request->input('idToken', '');
        if ($idToken === '' || strlen($idToken) > 4096) {
            return response()->json([
                'success' => false,
                'message' => 'We could not confirm your email yet. Please try again.',
            ], 422);
        }

        try {
            $payload = $verifier->verify($idToken, (string) config('shalotrack.firebase_project_id'));
        } catch (\Throwable $e) {
            Log::info('mark-verified: token rejected', ['type' => class_basename($e)]);
            return response()->json([
                'success' => false,
                'message' => 'We could not confirm your email yet. Please try again.',
            ], 422);
        }

        $uid         = (string) Session::get('firebase_uid');
        $sameAccount = $uid !== '' && hash_equals($uid, (string) ($payload['sub'] ?? ''));
        if (!$sameAccount || ($payload['email_verified'] ?? false) !== true) {
            return response()->json([
                'success' => false,
                'message' => 'Your email is not verified yet. Open the link we sent you, then try again.',
            ], 422);
        }

        $pending = Session::get('pending_profile');

        // The address Firebase verified must be the one typed on the form, otherwise the profile would
        // carry an email nobody proved. (An account verified with another address earlier ends up here.)
        if (is_array($pending)
            && strtolower((string) ($payload['email'] ?? '')) !== strtolower((string) ($pending['email'] ?? ''))) {
            return response()->json([
                'success' => false,
                'restart' => true,
                'message' => 'This phone number is already linked to a different verified email. Go back and enter that email instead.',
            ], 422);
        }

        // The API reads email_verified from the bearer token, so the session must carry the FRESH one
        // from here on (the phone-login token never has the claim).
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
                Log::info('EmailVerification: profile created', ['uid' => $uid]);
            } catch (\Throwable $e) {
                $msg = strtolower($e->getMessage());

                // Two tabs / a double click: the first call already created it. That is success.
                if ((int) $e->getCode() === 409 && str_contains($msg, 'profile already exists')) {
                    Log::info('EmailVerification: profile already existed', ['uid' => $uid]);
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

        Session::forget(['pending_profile', 'pending_verification_email']);
        Session::put('email_verified', true);

        return response()->json(['success' => true, 'redirect' => '/dashboard']);
    }
}