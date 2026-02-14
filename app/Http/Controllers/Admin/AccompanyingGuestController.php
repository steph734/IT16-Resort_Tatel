<?php

namespace App\Http\Controllers;

use App\Models\AccompanyingGuest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AccompanyingGuestController extends Controller
{
    // Load existing guests (when reopening modal)
    public function index($bookingId)
    {
        $guests = AccompanyingGuest::where('BookingID', $bookingId)
            ->select('first_name', 'last_name', 'gender', 'guest_type')
            ->get();

        return response()->json([
            'success' => true,
            'guests' => $guests
        ]);
    }

    // Save new guests
    public function store(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|string|size:10|exists:bookings,BookingID',
            'guests' => 'required|array',
            'guests.*.first_name' => 'required|string|max:255',
            'guests.*.last_name' => 'required|string|max:255',
            'guests.*.gender' => 'required|in:Male,Female,Other',
            'guests.*.guest_type' => 'required|in:Regular,Children,Senior',
        ]);

        $bookingId = $request->booking_id;

        // Optional: delete old ones if you want to replace
        AccompanyingGuest::where('BookingID', $bookingId)->delete();

        foreach ($request->guests as $guest) {
            // Generate unique AccompanyingID
            do {
                $id = 'AG' . strtoupper(Str::random(8));
            } while (AccompanyingGuest::where('AccompanyingID', $id)->exists());

            AccompanyingGuest::create([
                'AccompanyingID' => $id,
                'BookingID' => $bookingId,
                'first_name' => $guest['first_name'],
                'last_name' => $guest['last_name'],
                'gender' => $guest['gender'],
                'guest_type' => $guest['guest_type'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Accompanying guests saved successfully'
        ]);
    }
}