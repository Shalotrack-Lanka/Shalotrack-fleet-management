<?php

namespace App\Http\Controllers;

use App\Services\ShalotrackApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class ComplaintController extends Controller
{
    public function __construct(private ShalotrackApiService $api) {}

    /**
     * GET /complaints
     * Shows the customer's complaint list + the vehicle picker for filing a new one.
     * Vehicle picker is scoped to OWNED vehicles only — the API's create-complaint
     * check rejects a VehicleId the caller doesn't own (shared vehicles excluded),
     * same rule the mobile app follows.
     */
    public function index()
    {
        try {
            $profile    = $this->api->getMyProfile();
            $customerId = $profile['data']['customerId'] ?? null;

            $vehicles = [];
            if ($customerId) {
                $vResponse = $this->api->getVehiclesByCustomer($customerId);
                $vehicles  = $vResponse['data'] ?? $vResponse;
                $vehicles  = is_array($vehicles) ? $vehicles : [];
            }

            $complaintsRes = $this->api->getMyComplaints();
            $complaints    = is_array($complaintsRes['data'] ?? null) ? $complaintsRes['data'] : (is_array($complaintsRes) ? $complaintsRes : []);

            return view('complaints.index', [
                'vehicles'   => $vehicles,
                'complaints' => $complaints,
                'error'      => null,
            ]);

        } catch (\Exception $e) {
            Log::error('ComplaintController: Failed to load', ['error' => $e->getMessage()]);
            if ($e->getCode() === 401) { Session::flush(); return redirect('/login?expired=1'); }
            return view('complaints.index', [
                'vehicles'   => [],
                'complaints' => [],
                'error'      => 'Could not load complaints. Please refresh.',
            ]);
        }
    }

    /**
     * GET /complaints/{id}
     * Complaint detail / reply thread.
     */
    public function show(string $id)
    {
        try {
            $response  = $this->api->getComplaint($id);
            $complaint = $response['data'] ?? $response;

            return view('complaints.show', [
                'complaint' => $complaint,
                'error'     => null,
            ]);

        } catch (\Exception $e) {
            Log::error('ComplaintController: Failed to load complaint', ['id' => $id, 'error' => $e->getMessage()]);
            if ($e->getCode() === 401) { Session::flush(); return redirect('/login?expired=1'); }
            if ($e->getCode() === 404) { return redirect('/complaints')->with('error', 'Complaint not found.'); }
            return view('complaints.show', [
                'complaint' => null,
                'error'     => 'Could not load this complaint. Please refresh.',
            ]);
        }
    }

    /**
     * POST /complaints
     * File a new complaint against an owned vehicle.
     */
    public function store(Request $request)
    {
        $request->validate([
            'vehicleId'   => 'required|uuid',
            'category'    => 'required|integer|min:0|max:3',
            'description' => 'required|string|max:2000',
        ]);

        try {
            $response = $this->api->fileComplaint(
                $request->input('vehicleId'),
                (int) $request->input('category'),
                $request->input('description')
            );
            return response()->json(['success' => true, 'data' => $response['data'] ?? $response]);
        } catch (\Exception $e) {
            Log::error('ComplaintController: file failed', ['error' => $e->getMessage()]);
            $message = $e->getCode() === 403
                ? 'You can only file a complaint against a vehicle you own.'
                : 'Failed to submit complaint. Please try again.';
            return response()->json(['success' => false, 'message' => $message], 422);
        }
    }

    /**
     * POST /complaints/{id}/reply
     * Add a customer reply to an open complaint thread.
     */
    public function reply(Request $request, string $id)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        try {
            $response = $this->api->replyToComplaint($id, $request->input('message'));
            return response()->json(['success' => true, 'data' => $response['data'] ?? $response]);
        } catch (\Exception $e) {
            Log::error('ComplaintController: reply failed', ['id' => $id, 'error' => $e->getMessage()]);
            $message = $e->getCode() === 409
                ? 'This complaint is closed and can no longer receive replies.'
                : 'Failed to send reply. Please try again.';
            return response()->json(['success' => false, 'message' => $message], 422);
        }
    }
}