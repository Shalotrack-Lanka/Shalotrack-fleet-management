<?php

namespace App\Http\Controllers;

use App\Support\ApiErrors;
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

            return view('emergency-contacts.emergency-contacts', [
                'contacts' => $contacts,
                'error'    => null,
            ]);
        } catch (\Exception $e) {
            Log::error('EmergencyContactController: load failed', ['error' => $e->getMessage()]);
            if ($e->getCode() === 401) { Session::flush(); return redirect('/login?expired=1'); }
            return view('emergency-contacts.emergency-contacts', [
                'contacts' => [],
                'error'    => 'Could not load emergency contacts. Please refresh.',
            ]);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:100',
            'phoneNumber'  => ['required', 'string', 'max:20', 'regex:/^\+?[0-9][0-9\s().-]{6,19}$/'],
            'relationship' => 'nullable|string|max:50',
        ], [
            'phoneNumber.regex' => 'Enter a valid phone number, for example 077 123 4567.',
        ]);

        try {
            $response = $this->api->createEmergencyContact([
                'Name'         => $request->input('name'),
                'PhoneNumber'  => $request->input('phoneNumber'),
                'Relationship' => $request->input('relationship') ?? 'Other',
            ]);
            return response()->json(['success' => true, 'data' => $response['data'] ?? $response]);
        } catch (\Exception $e) {
            return ApiErrors::json($e, 'emergency.create', 'Failed to add contact. Please try again.', [
                422 => 'Plan limit reached. You cannot add more emergency contacts on your current plan.',
            ]);
        }
    }

    public function destroy(string $id)
    {
        try {
            $this->api->deleteEmergencyContact($id);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return ApiErrors::json($e, 'emergency.delete', 'Failed to delete contact.');
        }
    }
}