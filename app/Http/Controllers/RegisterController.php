<?php

namespace App\Http\Controllers;

use App\Services\ShalotrackApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * RegisterController
 *
 * Handles new customer profile creation after Firebase Phone OTP.
 *
 * Flow:
 * 1. Customer completes Phone OTP on /login
 * 2. Laravel creates session, checks GET /api/Customers/me
 * 3. If 404 (no profile) → redirect to /register
 * 4. Customer fills name, email, NIC, address → POST /register
 * 5. Laravel calls POST /api/Customers → profile created
 * 6. Redirect to /dashboard
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
            'phone' => Session::get('firebase_phone'),
        ]);
    }

    /**
     * POST /register
     * Create the customer profile via the C# API.
     */
    public function store(Request $request)
    {
        if (!Session::has('firebase_token')) {
            return redirect('/login');
        }

        $validated = $request->validate([
            'fullName'  => 'required|string|max:150',
            'email'     => 'required|email|max:150',
            'nicNumber' => 'required|string|max:20',
            'address'   => 'nullable|string|max:300',
        ]);

        $phone = Session::get('firebase_phone');

        try {
            $this->api->createProfile([
                'FullName'    => $validated['fullName'],
                'Email'       => $validated['email'],
                'PhoneNumber' => $phone,
                'NicNumber'   => $validated['nicNumber'],
                'Address'     => $validated['address'] ?? null,
            ]);

            Log::info('RegisterController: Profile created', ['phone' => $phone]);

            // Store email in session for the verification page
            Session::put('pending_verification_email', $validated['email']);
            Session::put('email_verified', false);

            return redirect('/email/verify');

        } catch (\Exception $e) {
            Log::error('RegisterController: Failed', [
                'error' => $e->getMessage(),
                'code'  => $e->getCode(),
            ]);

            $message = match ($e->getCode()) {
                409     => 'An account with this phone number or email already exists. Please log in instead.',
                422     => 'Please check your details and try again.',
                default => 'Failed to create your profile. Please try again.',
            };

            return back()->withErrors(['general' => $message])->withInput();
        }
    }
}