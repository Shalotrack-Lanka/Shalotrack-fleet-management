<?php

namespace App\Http\Controllers;

use App\Services\FirebaseTokenVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
     *
     * SECURITY: this used to set the flag for ANY signed-in session that asked — a user could skip the
     * email check with one request. Now the browser must send a FRESH Firebase ID token (taken after
     * reloading the user), and the server verifies its signature, that it belongs to this session's
     * account, and that Firebase itself says email_verified = true.
     */
    public function markVerified(Request $request, FirebaseTokenVerifier $verifier)
    {
        if (!Session::has('firebase_token')) {
            return response()->json(['success' => false], 401);
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

        Session::put('email_verified', true);

        return response()->json(['success' => true]);
    }
}