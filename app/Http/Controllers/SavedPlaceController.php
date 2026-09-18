<?php

namespace App\Http\Controllers;

use App\Services\ShalotrackApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class SavedPlaceController extends Controller
{
    public function __construct(private ShalotrackApiService $api) {}

    public function index()
    {
        try {
            $response = $this->api->getSavedPlaces();
            $data     = $response['data'] ?? $response;
            $places   = is_array($data) ? $data : [];

            return view('saved-places.index', [
                'places' => $places,
                'error'  => null,
            ]);
        } catch (\Exception $e) {
            Log::error('SavedPlaceController: load failed', ['error' => $e->getMessage()]);
            if ($e->getCode() === 401) { Session::flush(); return redirect('/login?expired=1'); }
            return view('saved-places.index', [
                'places' => [],
                'error'  => 'Could not load saved places. Please refresh.',
            ]);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:100',
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        try {
            $response = $this->api->createSavedPlace([
                'name'      => $request->input('name'),
                'latitude'  => (float) $request->input('latitude'),
                'longitude' => (float) $request->input('longitude'),
            ]);
            return response()->json(['success' => true, 'data' => $response['data'] ?? $response]);
        } catch (\Exception $e) {
            Log::error('SavedPlaceController: create failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to save place.'], 422);
        }
    }

    public function destroy(string $id)
    {
        try {
            $this->api->deleteSavedPlace($id);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('SavedPlaceController: delete failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to delete place.'], 422);
        }
    }
}