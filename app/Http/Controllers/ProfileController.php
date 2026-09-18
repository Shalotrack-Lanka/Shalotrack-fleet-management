<?php

namespace App\Http\Controllers;

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

            return view('profile.index', [
                'profile' => $profile,
                'error'   => null,
            ]);

        } catch (\Exception $e) {
            Log::error('ProfileController: Failed to load', ['error' => $e->getMessage()]);
            if ($e->getCode() === 401) { Session::flush(); return redirect('/login?expired=1'); }
            return view('profile.index', [
                'profile' => null,
                'error'   => 'Could not load profile. Please refresh.',
            ]);
        }
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'fullName'    => 'required|string|max:150',
            'phoneNumber' => 'required|string|max:20',
            'address'     => 'nullable|string|max:300',
        ]);

        try {
            $profile    = $this->api->getMyProfile();
            $customerId = $profile['data']['customerId'] ?? null;

            if (!$customerId) {
                return response()->json(['success' => false, 'message' => 'Profile not found.'], 404);
            }

            $response = $this->api->updateProfile($customerId, [
                'FullName'    => $validated['fullName'],
                'PhoneNumber' => $validated['phoneNumber'],
                'Address'     => $validated['address'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully.',
                'data'    => $response['data'] ?? $response,
            ]);

        } catch (\Exception $e) {
            Log::error('ProfileController: update failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to update profile.'], 422);
        }
    }
}