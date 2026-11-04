<?php

namespace App\Http\Controllers;

use App\Support\ApiErrors;
use App\Support\PersonName;
use App\Support\SriLankanPhone;
use App\Services\ShalotrackApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class ProfileController extends Controller
{
    public function __construct(private ShalotrackApiService $api) {}

    public function index()
    {
        try {
            $response = $this->api->getMyProfile();
            $profile  = $response['data'] ?? $response;

            return view('profile.profile', [
                'profile' => $profile,
                'error'   => null,
            ]);

        } catch (\Exception $e) {
            Log::error('ProfileController: Failed to load', ['error' => $e->getMessage()]);
            if ($e->getCode() === 401) { Session::flush(); return redirect('/login?expired=1'); }
            return view('profile.profile', [
                'profile' => null,
                'error'   => 'Could not load profile. Please refresh.',
            ]);
        }
    }

    public function update(Request $request)
    {
        $request->merge(['fullName' => is_string($request->input('fullName')) ? PersonName::clean($request->input('fullName')) : $request->input('fullName')]);

        $validated = $request->validate([
            'fullName'    => ['required', 'string', 'max:150', 'regex:' . PersonName::REGEX],
            'address'     => 'nullable|string|max:300',
        ], ['fullName.regex' => PersonName::MESSAGE]);
        // The phone number is the sign-in identity, so it is NOT editable here: it changes only through the
        // verified flow below (code to the new number). Anything the browser sends as phoneNumber is ignored.

        try {
            $profile    = $this->api->getMyProfile();
            $customerId = $profile['data']['customerId'] ?? null;

            if (!$customerId) {
                return response()->json(['success' => false, 'message' => 'Profile not found.'], 404);
            }

            $response = $this->api->updateProfile($customerId, [
                'FullName'    => $validated['fullName'],
                'PhoneNumber' => $profile['data']['phoneNumber'] ?? null,   // always the saved one, never the browser's
                'Address'     => $validated['address'] ?? null,
            ]);

            // Keep the session in sync so the header name stays correct on
            // subsequent page navigations without requiring a fresh login.
            Session::put('customer_name', $validated['fullName']);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully.',
                'data'    => $response['data'] ?? $response,
            ]);

        } catch (\Exception $e) {
            return ApiErrors::json($e, 'profile.update', 'Failed to update profile.');
        }
    }

    // ---- Change phone number (verified) ---------------------------------------------------------------

    /** Step 1: text a 6-digit code to the NEW number. */
    public function phoneRequest(Request $request)
    {
        $request->validate(['phoneNumber' => 'required|string|max:25']);
        $new = SriLankanPhone::normalise($request->input('phoneNumber'));

        if ($new === null) {
            return $this->phoneFail('INVALID_PHONE', 'Enter a valid Sri Lankan mobile number, for example 077 123 4567.');
        }

        try {
            $current = SriLankanPhone::normalise($this->api->getMyProfile()['data']['phoneNumber'] ?? null);
            if ($current !== null && $current === $new) {
                return $this->phoneFail('SAME_NUMBER', 'That is already your number.');
            }

            $res = $this->api->requestPhoneChange($new);

            return response()->json([
                'success'            => true,
                'message'            => 'We sent a 6-digit code to ' . SriLankanPhone::pretty($new) . '.',
                'phoneNumber'        => $new,
                'resendAfterSeconds' => max(30, min(300, (int) ($res['data']['resendAfterSeconds'] ?? 60))),
            ]);
        } catch (\Exception $e) {
            return $this->phoneApiFailure($e, 'profile.phone.request', 'Could not send the code. Please try again.', 'That number is not valid. Check it and try again.');
        }
    }

    /** Step 2: the code that arrived on the new number. The API saves the number only if it is right. */
    public function phoneVerify(Request $request)
    {
        $request->validate(['phoneNumber' => 'required|string|max:25', 'code' => 'required|string|max:12']);
        $new  = SriLankanPhone::normalise($request->input('phoneNumber'));
        $code = $request->input('code');

        if ($new === null) {
            return $this->phoneFail('INVALID_PHONE', 'Enter a valid Sri Lankan mobile number, for example 077 123 4567.');
        }
        if (!is_string($code) || preg_match('/^\d{6}$/D', $code) !== 1) {
            return $this->phoneFail('INVALID_CODE', 'Enter the 6-digit code.');
        }

        try {
            $res = $this->api->verifyPhoneChange($new, $code);

            return response()->json([
                'success'     => true,
                'message'     => 'Your phone number was changed.',
                'phoneNumber' => is_string($res['data']['phoneNumber'] ?? null) ? $res['data']['phoneNumber'] : $new,
            ]);
        } catch (\Exception $e) {
            return $this->phoneApiFailure($e, 'profile.phone.verify', 'Could not verify the code. Please try again.', 'That code is not right or has expired. Request a new one.');
        }
    }

    private function phoneFail(string $code, string $message)
    {
        return response()->json(['success' => false, 'code' => $code, 'message' => $message], 422);
    }

    private function phoneApiFailure(\Throwable $e, string $action, string $fallback, string $badRequest)
    {
        // Signed in too long ago for something this serious: the page tells the customer to sign in again.
        if ($e->getMessage() === 'REAUTH_REQUIRED') {
            return response()->json([
                'success' => false,
                'code'    => 'REAUTH_REQUIRED',
                'message' => 'For your security, please sign out and sign in again, then change your number within 10 minutes.',
            ], 403);
        }

        return ApiErrors::json($e, $action, $fallback, [
            400 => $badRequest,
            422 => $badRequest,
            409 => 'That number cannot be used. Try a different number.',
        ]);
    }
}
