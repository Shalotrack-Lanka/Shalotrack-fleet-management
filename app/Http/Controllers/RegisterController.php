<?php

namespace App\Http\Controllers;

use App\Services\ShalotrackApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

/**
 * RegisterController
 *
 * Handles new customer profile creation after Firebase Phone OTP.
 *
 * Flow (same order as the Android app):
 * 1. Customer completes Phone OTP on /login
 * 2. Laravel creates session, checks GET /api/Customers/me
 * 3. If 404 (no profile) → redirect to /register
 * 4. Customer fills name, email, NIC, address → POST /register
 *    The details are only KEPT in the session here — nothing is sent to the API yet.
 * 5. /email/verify sends Firebase's verification link to that email and waits for the click
 * 6. POST /email/mark-verified gets a fresh Firebase token (email_verified = true) and only THEN
 *    calls POST /api/Customers → profile created → /dashboard
 *
 * Why not create the profile first: the API refuses to create a profile unless the caller's token
 * already says email_verified = true (CustomerService.CreateAsync, since 7 Sep). Creating first
 * always ended in 403 and "Failed to create your profile".
 */
class RegisterController extends Controller
{
    public function __construct(private ShalotrackApiService $api) {}

    /**
     * GET /register
     * Show the registration form.
     * Only accessible when logged in (session exists) but no profile yet.
     */
    public function show()
    {
        // Must be authenticated
        if (!Session::has('firebase_token')) {
            return redirect('/login');
        }

        // If profile already exists, go to dashboard
        try {
            $this->api->getMyProfile();
            return redirect('/dashboard');
        } catch (\Exception $e) {
            if ($e->getCode() !== 404) {
                return redirect('/dashboard');
            }
        }

        return view('auth.register', [
            'phone'   => Session::get('firebase_phone'),
            'pending' => (array) Session::get('pending_profile', []),   // pre-fill when the customer comes back to fix a detail
        ]);
    }

    /**
     * POST /register
     * Keep the entered details and send the customer to email verification. The profile itself is
     * created by EmailVerificationController::markVerified once the email is verified.
     */
    public function store(Request $request)
    {
        if (!Session::has('firebase_token')) {
            return redirect('/login');
        }

        // One canonical NIC form (no spaces, upper-case V/X) so the API's uniqueness check cannot be dodged.
        $request->merge(['nicNumber' => strtoupper(preg_replace('/\s+/', '', (string) $request->input('nicNumber', '')))]);

        $validated = $request->validate([
            'fullName'  => 'required|string|max:150',
            'email'     => 'required|email|max:150',
            // Sri Lankan NIC: 9 digits + V/X (old) or 12 digits (new). Same rule as the API's CreateCustomerDto.
            'nicNumber' => ['required', 'string', 'max:20', 'regex:/^([0-9]{9}[VX]|[0-9]{12})$/'],
            'address'   => 'nullable|string|max:300',
        ], [
            'nicNumber.regex' => 'Enter a valid NIC number, for example 861234567V or 198612345678.',
        ]);

        // Keep the details until the email is verified (see class docblock). Session data is server-side
        // and encrypted; nothing personal goes into the URL or a cookie.
        Session::put('pending_profile', [
            'fullName'  => trim($validated['fullName']),
            'email'     => trim($validated['email']),
            'nicNumber' => trim($validated['nicNumber']),
            'address'   => isset($validated['address']) ? trim((string) $validated['address']) : null,
        ]);
        Session::put('pending_verification_email', trim($validated['email']));
        Session::put('email_verified', false);

        return redirect('/email/verify');
    }
}