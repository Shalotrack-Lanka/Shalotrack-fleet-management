<?php

namespace App\Http\Controllers;

use App\Services\ShalotrackApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class EmergencyContactController extends Controller
{
    public function __construct(private ShalotrackApiService $api) {}

    public function index()
    {
        try {
            $response  = $this->api->getEmergencyContacts();
            $data      = $response['data'] ?? $response;
            $contacts  = is_array($data) ? $data : [];

            return view('emergency-contacts.index', [
                'contacts' => $contacts,
                'error'    => null,
            ]);
        } catch (\Exception $e) {
            Log::error('EmergencyContactController: load failed', ['error' => $e->getMessage()]);
            if ($e->getCode() === 401) { Session::flush(); return redirect('/login?expired=1'); }
            return view('emergency-contacts.index', [
                'contacts' => [],
                'error'    => 'Could not load emergency contacts. Please refresh.',
            ]);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:100',
            'phoneNumber'  => 'required|string|max:20',
            'relationship' => 'nullable|string|max:50',
        ]);

        try {
            $response = $this->api->createEmergencyContact([
                'Name'         => $request->input('name'),
                'PhoneNumber'  => $request->input('phoneNumber'),
                'Relationship' => $request->input('relationship') ?? 'Other',
            ]);
            return response()->json(['success' => true, 'data' => $response['data'] ?? $response]);
        } catch (\Exception $e) {
            Log::error('EmergencyContactController: create failed', ['error' => $e->getMessage()]);
            $message = $e->getCode() === 422
                ? 'Plan limit reached. You cannot add more emergency contacts on your current plan.'
                : 'Failed to add contact. Please try again.';
            return response()->json(['success' => false, 'message' => $message], 422);
        }
    }

    public function destroy(string $id)
    {
        try {
            $this->api->deleteEmergencyContact($id);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('EmergencyContactController: delete failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to delete contact.'], 422);
        }
    }
}