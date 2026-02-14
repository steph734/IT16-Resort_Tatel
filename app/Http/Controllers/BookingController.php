<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Package;
use App\Models\Payment;
use App\Models\ClosedDate;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Mail;
use App\Mail\BookingConfirmationMail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BookingController extends Controller
{
    /**
     * Show calendar and booked availability
     */
    public function checkAvailability()
    {
        // Fetch booked dates from all confirmed/staying/pending bookings
        // Note: Check-out date is NOT included as the resort is available on checkout day
        $bookedDates = Booking::select('CheckInDate', 'CheckOutDate')
            ->whereIn('BookingStatus', ['Pending', 'Booked', 'Confirmed', 'Staying'])
            ->get()
            ->flatMap(function ($booking) {
                // Mark booked dates from start to finish (inclusive of checkout day)
                $start = Carbon::parse($booking->CheckInDate)->startOfDay();
                $endInclusive = Carbon::parse($booking->CheckOutDate)->startOfDay();
                $dates = [];
                while ($start->lte($endInclusive)) {
                    $dates[] = $start->format('Y-m-d');
                    $start->addDay();
                }
                return $dates;
            })
            ->unique()
            ->values();

        // Fetch closed dates
        $closedDates = ClosedDate::pluck('closed_date')
            ->map(function ($date) {
                return Carbon::parse($date)->format('Y-m-d');
            })
            ->values();

        return view('bookings.check-availability', compact('bookedDates', 'closedDates'));
    }

    /**
     * Reset booking sessions
     */
    public function resetBooking()
    {
        Session::forget(['booking_data', 'personal_details', 'booking_details']);
        return redirect()->route('bookings.personal-details');
    }

    /**
     * Step 2: Personal Details
     */
    public function personalDetails()
    {
        $personalDetails = Session::get('personal_details', []);
        return view('bookings.personal-details', compact('personalDetails'));
    }

    public function storePersonalDetails(Request $request)
    {
        $request->validate([
            // Allow letters (including accents), spaces, hyphens, apostrophes
            'first_name' => ['required','string','max:50','regex:/^[A-Za-zÀ-ÿ\'\s\-]+$/'],
            'last_name' => ['required','string','max:50','regex:/^[A-Za-zÀ-ÿ\'\s\-]+$/'],
            'middle_name' => ['nullable','string','max:50','regex:/^[A-Za-zÀ-ÿ\'\s\-]+$/'],
            'email' => 'required|email',
            // Enforce Philippine mobile format: +639XXXXXXXXX
            'phone' => ['required','string','regex:/^\+639\d{9}$/'],
            'address' => 'nullable|string|max:255',
        ]);

        // Normalize whitespace
        $clean = [
            'first_name' => trim($request->input('first_name')),
            'last_name' => trim($request->input('last_name')),
            'middle_name' => trim($request->input('middle_name','')),
            // Normalize email to lowercase on server-side for consistency
            'email' => strtolower(trim($request->input('email'))),
            'phone' => trim($request->input('phone')),
            'address' => trim($request->input('address','')),
        ];

        Session::put('personal_details', $clean);

        return redirect()->route('bookings.details');
    }

    /**
     * Step 3: Booking Details
     */
    public function bookingDetails()
    {
        $bookingData = Session::get('booking_data', []);
        $personalDetails = Session::get('personal_details', []);

        if (empty($bookingData) || empty($personalDetails)) {
            return redirect()->route('bookings.check-availability')
                             ->withErrors('Please complete previous steps before proceeding.');
        }

        // Calculate days until check-in and payment requirements
        $checkInDate = isset($bookingData['check_in']) ? \Carbon\Carbon::parse($bookingData['check_in']) : null;
        $today = \Carbon\Carbon::today();

        if ($checkInDate) {
            $daysUntilCheckIn = $today->diffInDays($checkInDate, false);
            $daysUntilCheckIn = max(0, $daysUntilCheckIn); // Ensure non-negative

            $isReservationFeeEligible = $daysUntilCheckIn >= 14;

            $bookingData['days_until_checkin'] = $daysUntilCheckIn;
            $bookingData['is_reservation_fee_eligible'] = $isReservationFeeEligible;
            $bookingData['payment_label'] = $isReservationFeeEligible ? '₱1,000 reservation fee' : '50% downpayment';
        } else {
            $bookingData['days_until_checkin'] = 0;
            $bookingData['is_reservation_fee_eligible'] = false;
            $bookingData['payment_label'] = '50% downpayment';
        }

        $packages = Package::all();
        return view('bookings.booking-details', compact('bookingData', 'personalDetails', 'packages'));
    }

    public function storeBookingDetails(Request $request)
    {
        $bookingData = Session::get('booking_data', []);
        $personalDetails = Session::get('personal_details', []);

        if (empty($bookingData) || empty($personalDetails)) {
            return redirect()->route('bookings.check-availability')
                             ->withErrors('Incomplete booking data.');
        }

        $request->validate([
            'package_id' => 'required|exists:packages,PackageID',
            'num_of_adults' => 'required|integer|min:0',
            'num_of_child' => 'nullable|integer|min:0',
            'num_of_seniors' => 'nullable|integer|min:0',
            'total_guests' => 'required|integer|min:1',
            'total_amount' => 'required|numeric|min:0.01',
            'downpayment_amount' => 'nullable|numeric|min:0',
        ]);

        $totalGuests = $request->num_of_adults + ($request->num_of_child ?? 0) + ($request->num_of_seniors ?? 0);

        if ($totalGuests !== (int)$request->total_guests) {
            return redirect()->back()->withErrors(['total_guests' => 'Total guests mismatch.'])->withInput();
        }

        // Get package to calculate excess fee
        $package = Package::findOrFail($request->package_id);

        // Calculate excess fee - adults + seniors count; children are FREE
        $numAdults = $request->num_of_adults + ($request->num_of_seniors ?? 0);
        $maxGuests = $package->max_guests ?? 0;
        $excessGuests = max(0, $numAdults - $maxGuests);
        $excessFee = $excessGuests * ($package->excess_rate ?? 100); // Default ₱100 per excess guest

        $bookingDetails = [
            'guest' => [
                'FName' => $personalDetails['first_name'],
                'MName' => $personalDetails['middle_name'] ?? null,
                'LName' => $personalDetails['last_name'],
                'Email' => $personalDetails['email'],
                'Phone' => $personalDetails['phone'],
                'Address' => $personalDetails['address'] ?? null,
                'SocialMedia' => $personalDetails['socialmedia'] ?? null,
                'Contactable' => true,
            ],
            'booking' => [
                'PackageID' => $request->package_id,
                'CheckInDate' => $bookingData['check_in'],
                'CheckOutDate' => $bookingData['check_out'],
                'Pax' => $totalGuests,
                'NumOfAdults' => $request->num_of_adults,
                'NumOfChild' => $request->num_of_child ?? 0,
                'NumOfSeniors' => $request->num_of_seniors ?? 0,
                'TotalAmount' => $request->total_amount, // Store the total amount from the booking-details form
                'DownpaymentAmount' => $request->downpayment_amount ?? null, // Store downpayment amount if provided
                'ExcessFee' => $excessFee, // Add excess fee to booking details
            ],
        ];

        Log::info('storeBookingDetails', ['bookingDetails' => $bookingDetails]);

        Session::put('booking_details', $bookingDetails);
        return redirect()->route('bookings.payment');
    }

    /**
     * Step 4: Payment
     */
    public function payment()
    {
        $bookingDetails = Session::get('booking_details', []);

        if (empty($bookingDetails) || !isset($bookingDetails['booking'])) {
            return redirect()->route('bookings.details')
                             ->withErrors('Please complete previous steps before proceeding.');
        }

        $package = Package::findOrFail($bookingDetails['booking']['PackageID']);

        // Calculate days of stay
        $checkInStr = $bookingDetails['booking']['CheckInDate'];
        $checkOutStr = $bookingDetails['booking']['CheckOutDate'];

        $checkIn = \Carbon\Carbon::parse($checkInStr);
        $checkOut = \Carbon\Carbon::parse($checkOutStr);
        // Calculate days to match booking-details calculation (checkout - checkin)
        $daysOfStay = max(1, ceil($checkOut->diffInDays($checkIn, true))); // Minimum 1 day

        // Use stored total amount from session if available, otherwise calculate
        if (isset($bookingDetails['booking']['TotalAmount']) && is_numeric($bookingDetails['booking']['TotalAmount'])) {
            $totalAmount = (float) $bookingDetails['booking']['TotalAmount'];
            
            // Calculate package total and excess fee from stored values
            $excessFee = $bookingDetails['booking']['ExcessFee'] ?? 0;
            $packageTotal = $totalAmount - $excessFee;
        } else {
            // Fallback: Calculate from scratch
            $packageTotal = $package->Price * $daysOfStay;
            $numAdults = $bookingDetails['booking']['NumOfAdults'];
            $maxGuests = $package->max_guests ?? 0;
            $excessGuests = max(0, $numAdults - $maxGuests);
            $excessFee = $excessGuests * ($package->excess_rate ?? 0);
            $totalAmount = $packageTotal + $excessFee;
        }

        // Calculate excess guests for display purposes
        $numAdults = $bookingDetails['booking']['NumOfAdults'];
        $numSeniors = $bookingDetails['booking']['NumOfSeniors'] ?? 0;
        $maxGuests = $package->max_guests ?? 0;
        // Adults + Seniors count toward excess (children are FREE)
        $excessGuests = max(0, ($numAdults + $numSeniors) - $maxGuests);

        // Senior discounts are not applied automatically online to prevent misuse.
        $seniorDiscount = 0; // no automatic discount

        // Calculate days until check-in
        $today = \Carbon\Carbon::today();
        $daysUntilCheckIn = max(0, $today->diffInDays($checkIn, false));
        $isReservationFeeEligible = $daysUntilCheckIn >= 14;

        // Calculate required payment based on booking policy
        // Use stored downpayment amount if available, otherwise calculate
        $reservationFee = 1000;
        if (isset($bookingDetails['booking']['DownpaymentAmount']) && is_numeric($bookingDetails['booking']['DownpaymentAmount'])) {
            $downpaymentAmount = (float) $bookingDetails['booking']['DownpaymentAmount'];
            // Determine label based on amount
            $paymentLabel = ($downpaymentAmount == $reservationFee) ? 'Reservation Fee' : 'Required Downpayment (50%)';
        } else {
            // Fallback: Calculate based on policy
            if ($isReservationFeeEligible) {
                // Booking made 14+ days before check-in: only ₱1,000 reservation fee required
                $downpaymentAmount = $reservationFee;
                $paymentLabel = 'Reservation Fee';
            } else {
                // Booking made less than 14 days before check-in: 50% downpayment required
                $downpaymentAmount = $totalAmount * 0.5;
                $paymentLabel = 'Required Downpayment (50%)';
            }
        }

        // Pass booking data to view for auto-fill functionality
        $bookingData = [
            'total_amount' => $totalAmount,
            'downpayment_amount' => $downpaymentAmount,
            'package_total' => $packageTotal,
            'senior_discount' => $seniorDiscount,
            'excess_fee' => $excessFee,
            'package_id' => $bookingDetails['booking']['PackageID'],
            'package_name' => $package->Name . ' - ₱' . number_format($package->Price, 0) . '/day',
            'check_in' => $bookingDetails['booking']['CheckInDate'],
            'check_out' => $bookingDetails['booking']['CheckOutDate'],
            'num_of_adults' => $bookingDetails['booking']['NumOfAdults'],
            'num_of_child' => $bookingDetails['booking']['NumOfChild'],
            'num_of_seniors' => $bookingDetails['booking']['NumOfSeniors'],
            'total_guests' => $bookingDetails['booking']['Pax'],
            'days_of_stay' => $daysOfStay,
            'max_guests' => $maxGuests,
            'excess_guests' => $excessGuests,
            'days_until_checkin' => $daysUntilCheckIn,
            'is_reservation_fee_eligible' => $isReservationFeeEligible,
            'reservation_fee' => $reservationFee,
            'payment_label' => $paymentLabel,
        ];

        return view('bookings.payment', compact('bookingDetails', 'package', 'bookingData'));
    }

    public function storePayment(Request $request)
    {
        $bookingDetails = Session::get('booking_details');
        $bookingId = $request->input('booking_id'); // Hidden field when paying via /payment/{id}

        // --- CASE 1: NO SESSION (Existing booking, paying remaining balance) ---
        if (empty($bookingDetails) && $bookingId) {
            $booking = Booking::with('guest')->where('BookingID', $bookingId)->firstOrFail();

            $guest = $booking->guest;
            $bookingDetails = [
                'guest' => [
                    'FName' => $guest->FName,
                    'MName' => $guest->MName,
                    'LName' => $guest->LName,
                    'Email' => $guest->Email,
                    'Phone' => $guest->Phone,
                    'Address' => $guest->Address,
                    'SocialMedia' => $guest->SocialMedia,
                    'Contactable' => $guest->Contactable,
                ],
                'booking' => [
                    'PackageID' => $booking->PackageID,
                    'CheckInDate' => $booking->CheckInDate,
                    'CheckOutDate' => $booking->CheckOutDate,
                    'Pax' => $booking->Pax,
                    'NumOfAdults' => $booking->NumOfAdults,
                    'NumOfChild' => $booking->NumOfChild,
                    'NumOfSeniors' => $booking->NumOfSeniors,
                    'TotalAmount' => $booking->TotalAmount,
                    'ExcessFee' => $booking->ExcessFee,
                ],
            ];
        }

        // --- Validation ---
        $validMethods = ['bdo', 'bpi', 'gcash', 'tyme'];
        $paymentMode = $request->input('payment_mode', 'account');

        $validationRules = [
            'payment_method' => 'required|in:' . implode(',', $validMethods),
            'payment_mode' => 'required|string|in:account,qr',
            'amount' => 'required|numeric|min:0.01',
            'purpose' => 'required|string|in:Downpayment,Full Payment',
            'reference_number' => 'nullable|string|max:255',
        ];

        if ($paymentMode === 'account') {
            $validationRules['name_on_account'] = 'required|string|max:255';
            $validationRules['account_number'] = 'required|string|max:255';
        }

        $request->validate($validationRules);

        // --- File Upload Handling ---
        // Payment proof images are no longer stored in the payments table.
        // We keep accepting uploads in request for backward compatibility, but we do not persist them.
        // If you want to store proofs elsewhere, add a dedicated storage/attachment system.

        // --- CASE 1: EXISTING BOOKING ---
        if ($bookingId) {
            $booking = Booking::with(['guest', 'package'])->where('BookingID', $bookingId)->firstOrFail();

            $payment = Payment::create([
                'BookingID' => $booking->BookingID,
                'PaymentDate' => now(),
                'Amount' => $request->amount,
                'TotalAmount' => $booking->TotalAmount,
                'PaymentMethod' => $request->payment_method,
                'PaymentStatus' => 'For Verification',
                'PaymentPurpose' => $request->purpose,
                'NameOnAccount' => $request->name_on_account ?? null,
                'AccountNumber' => $request->account_number ?? null,
                'ReferenceNumber' => $request->reference_number ?? null,
            ]);

            // Send notification
            try {
                Mail::to($booking->guest->Email)->send(new BookingConfirmationMail($booking, $payment));
            } catch (\Exception $e) {
                Log::error('Email sending failed', ['error' => $e->getMessage(), 'booking_id' => $booking->BookingID]);
            }

            return redirect()->route('bookings.confirmation', ['booking_id' => $booking->BookingID])
                             ->with('success', 'Payment submitted for verification.');
        }

        // --- CASE 2: NEW BOOKING FLOW (Session-based) ---
        if (empty($bookingDetails)) {
            Log::error('Missing booking_details in storePayment');
            return redirect()->route('bookings.details')->withErrors('Incomplete booking data.');
        }

        $guest = Guest::create($bookingDetails['guest']);

        // Normalize times for consistency
        $normalizedCheckIn = Carbon::parse($bookingDetails['booking']['CheckInDate'])->setTime(14, 0, 0);
        $normalizedCheckOut = Carbon::parse($bookingDetails['booking']['CheckOutDate'])->setTime(12, 0, 0);

        $booking = Booking::create(array_merge($bookingDetails['booking'], [
            'GuestID' => $guest->GuestID,
            'BookingDate' => now(),
            'BookingStatus' => 'Pending',
            'CheckInDate' => $normalizedCheckIn,
            'CheckOutDate' => $normalizedCheckOut,
        ]));

        $payment = Payment::create([
            'BookingID' => $booking->BookingID,
            'PaymentDate' => now(),
            'Amount' => $request->amount,
            'TotalAmount' => $bookingDetails['booking']['TotalAmount'],
            'PaymentMethod' => $request->payment_method,
            'PaymentStatus' => 'For Verification',
            'PaymentPurpose' => $request->purpose,
            // Payment proofs are no longer persisted in the payments table
            'NameOnAccount' => $request->name_on_account ?? null,
            'AccountNumber' => $request->account_number ?? null,
            'ReferenceNumber' => $request->reference_number ?? null,
        ]);

        // Reload booking with relationships for email
        $booking->load(['guest', 'package']);

        try {
            Mail::to($guest->Email)->send(new BookingConfirmationMail($booking, $payment));
        } catch (\Exception $e) {
            Log::error('Email sending failed', ['error' => $e->getMessage(), 'booking_id' => $booking->BookingID]);
        }

        Session::forget('booking_details');
        Session::put('booking_id', $booking->BookingID);

        return redirect()->route('bookings.confirmation')->with('booking_id', $booking->BookingID);
    }

    public function paymentByBooking($bookingId)
    {
        // Load booking with all relationships from database
        $booking = Booking::with(['guest', 'payments', 'package'])->where('BookingID', $bookingId)->firstOrFail();

        // Latest payment (if any)
        $payment = Payment::where('BookingID', $booking->BookingID)
            ->orderBy('created_at', 'desc')
            ->first();

        // Get package from relationship
        $package = $booking->package;

        // Calculate days of stay from stored dates
        $checkIn = Carbon::parse($booking->CheckInDate);
        $checkOut = Carbon::parse($booking->CheckOutDate);
        $daysOfStay = $checkIn->diffInDays($checkOut); // Calculate FROM check-in TO check-out

        // Use STORED values from database
        $packageTotal = ($package->Price ?? 0) * $daysOfStay;
        $excessFee = $booking->ExcessFee ?? 0; // Use stored ExcessFee from database
        $seniorDiscount = $booking->senior_discount ?? 0; // Use stored senior discount
        $totalAmount = $packageTotal + $excessFee - $seniorDiscount;

        // Build bookingData using ACTUAL database values (no calculation, just reading)
        $bookingData = [
            'total_amount' => $totalAmount,
            'package_total' => $packageTotal,
            'excess_fee' => $excessFee,
            'senior_discount' => $seniorDiscount,
            'package_id' => $booking->PackageID,
            'package_name' => ($package->Name ?? '') . ' - ₱' . number_format($package->Price ?? 0, 0) . '/day',
            'check_in' => $booking->CheckInDate,
            'check_out' => $booking->CheckOutDate,
            'num_of_adults' => $booking->NumOfAdults ?? 0,
            'num_of_child' => $booking->NumOfChild ?? 0,
            'num_of_seniors' => $booking->NumOfSeniors ?? 0,
            'total_guests' => $booking->Pax ?? 0,
            'days_of_stay' => $daysOfStay,
            'max_guests' => $package->max_guests ?? 0,
            'excess_guests' => max(0, (($booking->NumOfAdults ?? 0) + ($booking->NumOfSeniors ?? 0)) - ($package->max_guests ?? 0)),
        ];

        // Return view with database data only
        return view('bookings.payment-existing', compact('booking', 'payment', 'package', 'bookingData'));
    }

    /**
     * Step 5: Confirmation
     */
    public function confirmation(Request $request)
    {
        $bookingId = session('booking_id') ?? $request->query('booking_id');
        if (!$bookingId) return redirect()->route('bookings.payment')->withErrors('No booking found.');

        $booking = Booking::with(['guest', 'payments', 'package'])->find($bookingId);
        if (!$booking) return redirect()->route('bookings.payment')->withErrors('Booking not found.');

        Session::forget('booking_id');
        return view('bookings.confirmation', compact('booking'));
    }

    /**
     * Send confirmation email for a booking (invoked from confirmation page)
     */

    public function sendConfirmationEmail(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|string',
        ]);
        $bookingId = $request->input('booking_id');
        $booking = Booking::with(['guest', 'payments'])->where('BookingID', $bookingId)->first();
        if (!$booking) {
            return redirect()->back()->withErrors('Booking not found for sending confirmation email.');
        }
        if (empty($booking->guest) || empty($booking->guest->Email)) {
            return redirect()->route('bookings.confirmation', ['booking_id' => $booking->BookingID])->withErrors('No guest email available to send confirmation.');
        }
        // Find the latest payment if available
        $payment = $booking->payments->sortByDesc('created_at')->first();
        try {
            Mail::to($booking->guest->Email)->send(new BookingConfirmationMail($booking, $payment));
            return redirect()->route('bookings.confirmation', ['booking_id' => $booking->BookingID])->with('success', 'Confirmation email sent.');
        } catch (\Exception $e) {
            Log::error('Failed to send confirmation email', ['error' => $e->getMessage(), 'booking' => $booking->BookingID]);
            return redirect()->route('bookings.confirmation', ['booking_id' => $booking->BookingID])->withErrors('Failed to send confirmation email.');
        }
    }

    /**
     * Admin: Booking List
     */
    public function adminIndex(Request $request)
    {
        $query = Booking::with(['guest', 'payments', 'package']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('BookingID', 'like', "%{$search}%")
                  ->orWhere('BookingStatus', 'like', "%{$search}%")
                  ->orWhereHas('guest', fn($g) =>
                  $g->where('FName', 'like', "%{$search}%")
                    ->orWhere('LName', 'like', "%{$search}%")
                    ->orWhere('Email', 'like', "%{$search}%")
                  );
            });
        }

        $bookings = $query->orderBy('BookingDate', 'desc')->paginate(5);
        $statuses = Booking::distinct()->pluck('BookingStatus')->filter();

        return view('admin.bookings', compact('bookings', 'statuses'));
    }
}
