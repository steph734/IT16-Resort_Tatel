<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\ClosedDate;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class CheckAvailabilityController extends Controller
{
    public function index(Request $request)
    {
        // Clear session if coming from a fresh page load (not from navigation)
        // Check if there's a 'from' parameter indicating navigation from another step
        if (!$request->has('from')) {
            // This is a fresh page load or refresh - clear booking session
            Session::forget(['booking_data', 'personal_details', 'booking_details']);
        }
        
        // Get all booked dates from confirmed/staying/pending bookings
        // Note: Check-out date is NOT included as the resort is available on checkout day
        $bookedDates = Booking::select('CheckInDate', 'CheckOutDate')
            ->whereIn('BookingStatus', ['Pending', 'Booked', 'Confirmed', 'Staying'])
            ->get()
            ->map(function ($booking) {
                // Return date range: check-in to (checkout - 1 day)
                $checkIn = Carbon::parse($booking->CheckInDate)->format('Y-m-d');
                $checkOut = Carbon::parse($booking->CheckOutDate)->format('Y-m-d');
                // End date is checkout - 1 (last day that's actually blocked)
                $endBlocked = Carbon::parse($booking->CheckOutDate)->subDay()->format('Y-m-d');
                return [
                    'start' => $checkIn,
                    'end' => $endBlocked
                ];
            })
            ->values()
            ->all();

        // Fetch closed dates
        $closedDates = ClosedDate::pluck('closed_date')
            ->map(function ($date) {
                return Carbon::parse($date)->format('Y-m-d');
            })
            ->values()
            ->all();

        // Get booking data from session to pre-fill the form
        $bookingData = Session::get('booking_data', []);

        return view('bookings.check-availability', compact('bookedDates', 'closedDates', 'bookingData'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // Disallow selecting the current date (must be strictly after today)
            'check_in' => 'required|date|after:today',
            'check_out' => 'required|date|after:check_in',
            'regular_guests' => 'required|integer|min:0',
            'children' => 'nullable|integer|min:0',
            'seniors' => 'nullable|integer|min:0',
            'pax' => 'required|integer|min:1',
        ]);

        $checkIn = Carbon::parse($validated['check_in']);
        $checkOut = Carbon::parse($validated['check_out']);

        // Check if any existing booking overlaps with the selected dates
        // Only consider active bookings (not Cancelled or Completed)
        $hasConflict = Booking::whereIn('BookingStatus', ['Pending', 'Booked', 'Confirmed', 'Staying'])
            ->where(function ($query) use ($checkIn, $checkOut) {
                // Inclusive day-level overlap: prevent booking on any day that touches an existing booking
                $query->where(function ($q) use ($checkIn, $checkOut) {
                    $q->where('CheckInDate', '<=', $checkOut)
                      ->where('CheckOutDate', '>=', $checkIn);
                });
            })
            ->exists();

        if ($hasConflict) {
            return redirect()
                ->route('bookings.check-availability')
                ->withErrors(['check_in' => 'The selected dates are not available. Please choose different dates.'])
                ->withInput();
        }

        // Check if any date in the range is a closed date
    // Check closed dates inclusive of both check-in and check-out
    $dateRange = CarbonPeriod::create($checkIn, $checkOut);
        foreach ($dateRange as $date) {
            $isClosed = ClosedDate::where('closed_date', $date->format('Y-m-d'))->exists();
            if ($isClosed) {
                return redirect()
                    ->route('bookings.check-availability')
                    ->withErrors(['check_in' => 'One or more selected dates are closed. Please choose different dates.'])
                    ->withInput();
            }
        }

        // Ensure pax reflects sum of all guest categories (regular + children + seniors)
        $totalGuests = ($validated['regular_guests'] ?? 0)
            + ($validated['children'] ?? 0)
            + ($validated['seniors'] ?? 0);
        $validated['pax'] = $totalGuests; // normalize pax

        // Save validated data (including seniors) to session for later steps
        Session::put('booking_data', $validated);

        return redirect()
            ->route('bookings.personal-details')
            ->with('success', 'Dates are available! Please complete your booking details.');
    }
}
