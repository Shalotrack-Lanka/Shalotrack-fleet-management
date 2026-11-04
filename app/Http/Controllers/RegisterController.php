<?php

namespace App\Http\Controllers;

use App\Support\PersonName;
use App\Services\ShalotrackApiService;
use App\Support\EmailDomainCheck;
use App\Support\EmailTypoGuard;
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
 * 5. /register/password lets the customer attach an email + password to the Firebase account (optional)
 * 6. POST /register/complete gets a fresh Firebase token and calls POST /api/Customers → profile created
 *    → /dashboard
 *
 * There is NO email verification (owner decision, 2 Nov 2026): the phone OTP is the only identity check and
 * the email is a plain contact field / login name. The password is attached BEFORE the profile is created so
 * the fresh token already carries the email.
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
     * Keep the entered details and send the customer to the password step. The profile itself is
     * created by RegistrationCompleteController::complete.
     */
    public function store(Request $request)
    {
        if (!Session::has('firebase_token')) {
            return redirect('/login');
        }

        // One canonical NIC form (no spaces, upper-case V/X) so the API's uniqueness check cannot be dodged.
        $request->merge(['nicNumber' => strtoupper(preg_replace('/\s+/', '', (string) $request->input('nicNumber', '')))]);

        $request->merge(['fullName' => is_string($request->input('fullName')) ? PersonName::clean($request->input('fullName')) : $request->input('fullName')]);

        $validated = $request->validate([
            'fullName'  => ['required', 'string', 'max:150', 'regex:' . PersonName::REGEX],
            // rfc = proper address syntax; then the domain must be able to receive mail (dead and made-up domains).
            // Our own check, not Laravel's `dns` rule: it fails open if DNS itself is down (see EmailDomainCheck).
            'email'     => ['required', 'email:rfc', 'max:150', function ($attribute, $value, $fail) {
                if (config('shalotrack.email_dns_check') && !EmailDomainCheck::canReceiveMail((string) $value)) {
                    $fail('That email domain cannot receive mail. Check the address after the @.');
                }
            }],
            // Sri Lankan NIC: 9 digits + V/X (old) or 12 digits (new). Same rule as the API's CreateCustomerDto.
            'nicNumber' => ['required', 'string', 'max:20', 'regex:/^([0-9]{9}[VX]|[0-9]{12})$/'],
            'address'   => 'nullable|string|max:300',
        ], [
            'fullName.regex'  => PersonName::MESSAGE,
            'nicNumber.regex' => 'Enter a valid NIC number, for example 861234567V or 198612345678.',
        ]);

        // A likely domain typo (gamil.com): ask once. "Keep what I typed" sends keepEmail=1 and goes through.
        $suggestion = EmailTypoGuard::suggest($validated['email']);
        if ($suggestion !== null && !$request->boolean('keepEmail')) {
            return back()->withInput()
                ->withErrors(['email' => 'Did you mean ' . $suggestion . '?'])
                ->with('email_suggestion', $suggestion);
        }

        // Keep the details until the password step is done (see class docblock). Session data is server-side
        // and encrypted; nothing personal goes into the URL or a cookie.
        Session::put('pending_profile', [
            'fullName'  => trim($validated['fullName']),
            'email'     => trim($validated['email']),
            'nicNumber' => trim($validated['nicNumber']),
            'address'   => isset($validated['address']) ? trim((string) $validated['address']) : null,
        ]);

        return redirect('/register/password');
    }
}