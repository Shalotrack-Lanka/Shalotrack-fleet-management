<?php

namespace App\Http\Controllers;

use App\Services\ShalotrackApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class SharingController extends Controller
{
    public function __construct(private ShalotrackApiService $api) {}

    /**
     * GET /sharing
     * Shows three lists: shares I own, shared with me, pending invites.
     */
    public function index()
    {
        try {
            // Get vehicles for the invite modal
            $profile    = $this->api->getMyProfile();
            $customerId = $profile['data']['customerId'] ?? null;
            $vehicles   = [];
            if ($customerId) {
                $vResponse = $this->api->getVehiclesByCustomer($customerId);
                $vehicles  = $vResponse['data'] ?? $vResponse;
                $vehicles  = is_array($vehicles) ? $vehicles : [];
            }

            // Fetch all three lists in one go
            $mySharesRes   = $this->api->getMyShares();
            $sharedWithMe  = $this->api->getSharedWithMe();
            $pendingInvites= $this->api->getPendingInvites();

            $myShares      = is_array($mySharesRes['data']   ?? null) ? $mySharesRes['data']    : (is_array($mySharesRes)    ? $mySharesRes    : []);
            $sharedWithMe  = is_array($sharedWithMe['data']  ?? null) ? $sharedWithMe['data']   : (is_array($sharedWithMe)   ? $sharedWithMe   : []);
            $pendingInvites= is_array($pendingInvites['data'] ?? null) ? $pendingInvites['data'] : (is_array($pendingInvites) ? $pendingInvites : []);

            return view('sharing.index', [
                'vehicles'       => $vehicles,
                'myShares'       => $myShares,
                'sharedWithMe'   => $sharedWithMe,
                'pendingInvites' => $pendingInvites,
                'error'          => null,
            ]);

        } catch (\Exception $e) {
            Log::error('SharingController: Failed to load', ['error' => $e->getMessage()]);
            if ($e->getCode() === 401) { Session::flush(); return redirect('/login?expired=1'); }
            return view('sharing.index', [
                'vehicles'       => [],
                'myShares'       => [],
                'sharedWithMe'   => [],
                'pendingInvites' => [],
                'error'          => 'Could not load sharing data. Please refresh.',
            ]);
        }
    }

    /**
     * POST /sharing
     * Invite someone to view a vehicle by phone number.
     */
    public function store(Request $request)
    {
        $request->validate([
            'vehicleId'   => 'required|uuid',
            'phoneNumber' => 'required|string',
        ]);

        // Normalise phone to E.164
        $phone  = preg_replace('/\D/', '', $request->input('phoneNumber'));
        if (strlen($phone) === 9) $phone = '+94' . $phone;
        elseif (strlen($phone) === 10 && str_starts_with($phone, '0')) $phone = '+94' . substr($phone, 1);
        elseif (strlen($phone) === 11 && str_starts_with($phone, '94')) $phone = '+' . $phone;
        else $phone = '+' . $phone;

        try {
            $response = $this->api->inviteShare($request->input('vehicleId'), $phone);
            return response()->json(['success' => true, 'data' => $response['data'] ?? $response]);
        } catch (\Exception $e) {
            Log::error('SharingController: invite failed', ['error' => $e->getMessage()]);
            $message = $e->getCode() === 404
                ? 'No ShaloTrack account found with that phone number.'
                : 'Failed to send invite. Please try again.';
            return response()->json(['success' => false, 'message' => $message], 422);
        }
    }

    /**
     * POST /sharing/{id}/accept
     * Accept a pending invite.
     */
    public function accept(string $id)
    {
        try {
            $response = $this->api->respondToShare($id, true);
            return response()->json(['success' => true, 'data' => $response['data'] ?? $response]);
        } catch (\Exception $e) {
            Log::error('SharingController: accept failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to accept invite.'], 422);
        }
    }

    /**
     * POST /sharing/{id}/decline
     * Decline a pending invite.
     */
    public function decline(string $id)
    {
        try {
            $response = $this->api->respondToShare($id, false);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('SharingController: decline failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to decline invite.'], 422);
        }
    }

    /**
     * DELETE /sharing/{id}
     * Revoke an existing share.
     */
    public function destroy(string $id)
    {
        try {
            $this->api->revokeShare($id);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('SharingController: revoke failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to revoke share.'], 422);
        }
    }
}