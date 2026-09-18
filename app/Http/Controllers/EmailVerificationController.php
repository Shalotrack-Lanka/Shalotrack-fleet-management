<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

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

        // Already verified — go to dashboard
        if (Session::get('email_verified', false)) {
            return redirect('/dashboard');
        }

        $email = Session::get('pending_verification_email', 'your email');

        return view('auth.verify-email', ['email' => $email]);
    }

    /**
     * POST /email/mark-verified
     * Called by the JS client after Firebase confirms email is verified.
     * Marks the session as verified so middleware allows access.
     */
    public function markVerified(Request $request)
    {
        if (!Session::has('firebase_token')) {
            return response()->json(['success' => false], 401);
        }

        Session::put('email_verified', true);

        return response()->json(['success' => true]);
    }
}