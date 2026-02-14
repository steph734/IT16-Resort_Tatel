<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\ClosedDate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Audit_Log;
use Carbon\Carbon;
use App\Services\PaymongoService;

class BookingController extends Controller
{
    /**
     * Display the bookings page with all bookings data
     */
 public function index(): View
    {
        // Removed auto-update to "Staying" - user must manually check in via Currently Staying page

        $search  = request('search');
        $status  = request('status');
        $payment = request('payment');

        // Define payment filter options (no "For Verification" - payments are auto-confirmed)
        $paymentOptions = ['Fully Paid', 'Partial', 'Downpayment', 'Unpaid'];

        // Default sort: status priority (Staying → Booked → Completed), then newest check-in first
        $sort = request('sort', 'status_priority');

        $query = Booking::with(['guest', 'package', 'payments']);

        // Search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('BookingID', 'like', "%{$search}%")
                  ->orWhereHas('guest', function ($guestQuery) use ($search) {
                      $guestQuery->where('FName', 'like', "%{$search}%")
                                 ->orWhere('LName', 'like', "%{$search}%")
                                 ->orWhere('Email', 'like', "%{$search}%");
                  });
            });
        }

        // Status filter
        if ($status) {
            $query->where('BookingStatus', $status);
        }

        // Payment filter (your robust logic)
        if ($payment) {
            $this->applyPaymentFilter($query, $payment);
        }

        // Sorting
        switch ($sort) {
            case 'status_priority':
                // Staying first, Booked second, then Completed (newest to oldest)
                $query->orderByRaw("CASE 
                    WHEN BookingStatus = 'Staying' THEN 1
                    WHEN BookingStatus = 'Booked' THEN 2
                    WHEN BookingStatus = 'Completed' THEN 3
                    WHEN BookingStatus = 'Cancelled' THEN 4
                    WHEN BookingStatus = 'Confirmed' THEN 5
                    WHEN BookingStatus = 'Pending' THEN 6
                    ELSE 7 END")
                      ->orderBy('CheckInDate', 'desc');
                break;

            case 'bookingdate_oldest':
                $query->orderBy('BookingDate', 'asc');
                break;

            case 'checkin_nearest':
                $today = Carbon::today();
                $query->orderByRaw("CASE WHEN CheckInDate >= ? THEN 0 ELSE 1 END", [$today])
                      ->orderBy('CheckInDate', 'asc'); // Changed to 'asc' for nearest first
                break;

            case 'checkin_farthest':
                $query->orderBy('CheckInDate', 'desc'); // Changed to 'desc' for farthest first
                break;

            case 'name_asc':
                $query->join('guests', 'bookings.GuestID', '=', 'guests.GuestID')
                      ->orderBy('guests.FName', 'asc')
                      ->select('bookings.*');
                break;

            case 'name_desc':
                $query->join('guests', 'bookings.GuestID', '=', 'guests.GuestID')
                      ->orderBy('guests.FName', 'desc')
                      ->select('bookings.*');
                break;

            default:
                // Default: status priority (Staying → Booked → Completed), newest first
                $query->orderByRaw("CASE 
                    WHEN BookingStatus = 'Staying' THEN 1
                    WHEN BookingStatus = 'Booked' THEN 2
                    WHEN BookingStatus = 'Completed' THEN 3
                    WHEN BookingStatus = 'Cancelled' THEN 4
                    WHEN BookingStatus = 'Confirmed' THEN 5
                    WHEN BookingStatus = 'Pending' THEN 6
                    ELSE 7 END")
                      ->orderBy('CheckInDate', 'desc');
                break;
        }

        $bookings = $query->get();

        $packages = Package::all();
        $statuses = ['Pending', 'Confirmed', 'Staying', 'Cancelled', 'Completed'];

        // Pass $paymentOptions to the view
        return view('admin.bookings.bookings', compact(
            'bookings',
            'packages',
            'statuses',
            'paymentOptions'
        ));
    }
// ---

    /**
     * Apply payment filter to a query builder instance.
     * Supports values: Fully Paid, Partial, Downpayment, Unpaid (case-insensitive).
     * No "For Verification" - all payments are auto-confirmed.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $filter
     * @return void
     */
   protected function applyPaymentFilter(&$query, string $filter): void
    {
        $filter = trim(strtolower($filter));
        if ($filter === '') return;

        // Aggregate payments per booking
        $paymentsSub = DB::table('payments')
            ->select('BookingID', DB::raw('COALESCE(SUM(Amount),0) as paid'), DB::raw('LOWER(MAX(PaymentStatus)) as payment_status'))
            ->groupBy('BookingID');

        // Join packages to compute expected total: price * diffdays + ExcessFee
        // Use DATEDIFF to compute days (MySQL). If using other DB adjust accordingly.
        $query->leftJoinSub($paymentsSub, 'p', function ($join) {
            $join->on('p.BookingID', '=', 'bookings.BookingID');
        })->leftJoin('packages', 'bookings.PackageID', '=', 'packages.PackageID');

        // total booking amount expression (price per day * days) + excess fee
        $daysExpr = "GREATEST(DATEDIFF(bookings.CheckOutDate, bookings.CheckInDate), 1)";
        $totalExpr = "(COALESCE(packages.Price,0) * {$daysExpr}) + COALESCE(bookings.ExcessFee, 0)";
        $paidExpr = "COALESCE(p.paid, 0)";
        $statusExpr = "LOWER(COALESCE(p.payment_status, ''))";

        // Define percentage thresholds for clarity and maintainability
        $downpaymentThreshold = 0.50; // 50%
        $partialThreshold = 0.50;     // > 50%

        switch ($filter) {
            case 'fully paid':
            case 'fullypaid':
                $query->where(function($q) use ($totalExpr, $paidExpr, $statusExpr) {
                    // Paid >= Total AND Total > 0
                    $q->whereRaw("({$paidExpr} >= {$totalExpr} AND {$totalExpr} > 0)")
                      ->orWhereRaw("{$statusExpr} = ?", ['fully paid']);
                });
                break;

            case 'partial':
                // FIX: Partial should capture bookings paid > 50% AND < 100%
                $query->where(function($q) use ($totalExpr, $paidExpr, $statusExpr, $partialThreshold) {
                    // Paid is > 50% AND Paid is < Total
                    $q->whereRaw("({$paidExpr} > ({$totalExpr} * {$partialThreshold}) AND {$paidExpr} < {$totalExpr})")
                      ->orWhereRaw("{$statusExpr} = ?", ['partial']);
                });
                break;

            case 'downpayment':
                // FIX: Downpayment should capture bookings paid > 0% AND <= 50%
                $query->where(function($q) use ($totalExpr, $paidExpr, $statusExpr, $downpaymentThreshold) {
                    // Paid is > 0 AND Paid is <= 50%
                    $q->whereRaw("({$paidExpr} > 0 AND {$paidExpr} <= ({$totalExpr} * {$downpaymentThreshold}))")
                      ->orWhereRaw("{$statusExpr} = ?", ['downpayment']);
                });
                break;

            case 'unpaid':
                $query->where(function($q) use ($totalExpr, $paidExpr, $statusExpr) {
                    // Paid = 0 AND Total > 0
                    $q->whereRaw("({$paidExpr} = 0 AND {$totalExpr} > 0)")
                      ->orWhereRaw("{$statusExpr} = ?", ['unpaid']);
                });
                break;

            default:
                // fallback: try matching PaymentStatus text
                $query->whereRaw("{$statusExpr} = ?", [$filter]);
                break;
        }

        // Ensure original booking columns selected (avoid polluting with joins)
        $query->select('bookings.*')->distinct();
    }
    public function create(): View
    {
        $packages = Package::all();

        // Add amenities array to each package
        foreach ($packages as $package) {
            $package->amenities_array = $package->amenities
                ? array_filter(array_map('trim', explode("\n", $package->amenities)))
                : [];
        }

        return view('admin.bookings.create', compact('packages'));
    }

    /**
     * Get bookings data for AJAX requests
     */
    public function getData(): JsonResponse
    {
        $bookings = Booking::with(['guest', 'package', 'payments'])
            // Order by Check In Date (nearest first)
            ->orderBy('CheckInDate', 'asc')
            ->get()
            ->map(function ($booking) {
                // Calculate total amount
                $package = $booking->package;
                $checkInDate = new \DateTime($booking->CheckInDate);
                $checkOutDate = new \DateTime($booking->CheckOutDate);
                $days = $checkInDate->diff($checkOutDate)->days;
                $packageTotal = ($package->Price ?? 0) * $days;

                // Use stored ExcessFee instead of recalculating
                $excessFee = $booking->ExcessFee ?? 0;
                $totalAmount = $packageTotal + $excessFee;

                // Calculate total paid
                $totalPaid = $booking->payments->sum('Amount');

                // Determine payment status - all payments are auto-confirmed
                $paymentStatuses = $booking->payments->pluck('PaymentStatus')->unique();

                if ($totalPaid >= $totalAmount) {
                    $paymentStatus = 'Fully Paid';
                } elseif ($totalPaid > ($totalAmount * 0.50)) {
                    // More than 50% paid but not fully paid
                    $paymentStatus = 'Partial';
                } elseif ($totalPaid > 0) {
                    // Some payment made but 50% or less
                    $paymentStatus = 'Downpayment';
                } else {
                    $paymentStatus = 'Unpaid';
                }

                $paymentMethods = $booking->payments->pluck('PaymentMethod')->unique()->join(', ') ?: 'N/A';

                return [
                    'BookingID' => $booking->BookingID,
                    'BookingDate' => $booking->BookingDate->format('Y-m-d'),
                    'CheckInDate' => $booking->CheckInDate->format('Y-m-d'),
                    'CheckOutDate' => $booking->CheckOutDate->format('Y-m-d'),
                    'BookingStatus' => $booking->BookingStatus,
                    'Pax' => $booking->Pax,
                    'NumOfAdults' => $booking->NumOfAdults,
                    'NumOfSeniors' => $booking->NumOfSeniors,
                    'NumOfChild' => $booking->NumOfChild,
                    'GuestName' => $booking->guest ? $booking->guest->FName . ' ' . $booking->guest->LName : 'N/A',
                    'GuestEmail' => $booking->guest->Email ?? 'N/A',
                    'GuestPhone' => $booking->guest->Phone ?? 'N/A',
                    'PackageName' => $booking->package->Name ?? 'N/A',
                    'PackagePrice' => $booking->package->Price ?? 0,
                    'PaymentStatus' => $paymentStatus,
                    'PaymentMethod' => $paymentMethods,
                    'AmountPaid' => $totalPaid,
                ];
            });

        return response()->json($bookings);
    }

    /**
     * Store a new booking
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'guest_fname' => 'required|string|max:255',
                'guest_lname' => 'required|string|max:255',
                'guest_email' => 'required|email|max:255',
                'guest_phone' => 'required|string|max:20',
                'guest_address' => 'nullable|string',
                'check_in' => 'required|date|after_or_equal:today',
                'check_out' => 'required|date|after:check_in',
                'regular_guests' => 'required|integer|min:1',
                'children' => 'required|integer|min:0',
                'num_of_seniors' => 'required|integer|min:0',
                'package_id' => 'required|exists:packages,PackageID',
                'payment_method' => 'required|string',
                'payment_purpose' => 'required|string|in:reservation_fee,downpayment,full_payment',
                'amount_paid' => 'required|numeric|min:0',
                'reference_number' => 'nullable|string',
            ]);

        $package = Package::findOrFail($validated['package_id']);

        // Set fixed check-in time to 2:00 PM and check-out time to 12:00 PM
        $checkInDate = new \DateTime($validated['check_in']);
        $checkInDate->setTime(14, 0, 0); // 2:00 PM

        $checkOutDate = new \DateTime($validated['check_out']);
        $checkOutDate->setTime(12, 0, 0); // 12:00 PM

        // Calculate days - use dates without time for accurate day count
        $checkInForDays = new \DateTime($validated['check_in']);
        $checkInForDays->setTime(0, 0, 0);
        $checkOutForDays = new \DateTime($validated['check_out']);
        $checkOutForDays->setTime(0, 0, 0);
        $days = $checkInForDays->diff($checkOutForDays)->days;

    $packageTotal = $package->Price * $days;
    $maxGuests = $package->max_guests ?? 30;
    $adultCount = $validated['regular_guests'] + $validated['num_of_seniors'];
    $excessGuests = max(0, $adultCount - $maxGuests);
        $excessFee = $excessGuests * 100;
    $seniorCount = $validated['num_of_seniors'];
    $perAdultShare = $adultCount > 0 ? ($packageTotal / max(1, $adultCount)) : 0;
    $seniorDiscount = 0;
    $totalAmount = ($packageTotal + $excessFee);

        // Debug logging
        Log::info('Booking Calculation Debug', [
            'package_price' => $package->Price,
            'days' => $days,
            'package_total' => $packageTotal,
            'max_guests' => $maxGuests,
            'regular_guests' => $validated['regular_guests'],
            'num_of_seniors' => $validated['num_of_seniors'],
            'excess_guests' => $excessGuests,
            'excess_fee' => $excessFee,
            'total_amount' => $totalAmount,
            'senior_discount' => $seniorDiscount,
            'required_downpayment' => $totalAmount * 0.5,
            'amount_paid' => $validated['amount_paid'],
        ]);

        $today = new \DateTime();
        $today->setTime(0, 0, 0);
        $checkIn = new \DateTime($validated['check_in']);
        $checkIn->setTime(0, 0, 0);
        $daysUntilCheckIn = $today->diff($checkIn)->days;

        // Calculate required payments based on booking policy
        $reservationFee = 1000;
        $requiredDownpayment = $daysUntilCheckIn >= 14 ? $reservationFee : ($totalAmount * 0.5);

        // Validate payment amount based on purpose
        if ($validated['payment_purpose'] === 'reservation_fee') {
            // Reservation fee must be exactly ₱1,000
            if (abs($validated['amount_paid'] - $reservationFee) > 0.01) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reservation fee must be exactly ₱' . number_format($reservationFee, 2),
                    'required_amount' => $reservationFee,
                ], 422);
            }
            // Verify booking is eligible for reservation fee (>= 14 days before check-in)
            if ($daysUntilCheckIn < 14) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reservation fee is only available for bookings made 2 weeks or more before check-in date. Please pay 50% downpayment instead.',
                ], 422);
            }
        } elseif ($validated['payment_purpose'] === 'downpayment') {
            // Downpayment: ₱1,000 if booking is 14+ days before check-in, otherwise 50% of total
            if (abs($validated['amount_paid'] - $requiredDownpayment) > 0.01) {
                $downpaymentLabel = $daysUntilCheckIn >= 14 ? '₱1,000 (booking 14+ days in advance)' : number_format($requiredDownpayment, 2) . ' (50% of total)';
                return response()->json([
                    'success' => false,
                    'message' => 'Downpayment must be exactly ₱' . $downpaymentLabel,
                    'required_amount' => $requiredDownpayment,
                ], 422);
            }
        } elseif ($validated['payment_purpose'] === 'full_payment') {
            // Full payment must be exactly the total amount
            if (abs($validated['amount_paid'] - $totalAmount) > 0.01) {
                return response()->json([
                    'success' => false,
                    'message' => 'Full payment must be exactly ₱' . number_format($totalAmount, 2),
                    'required_amount' => $totalAmount,
                ], 422);
            }
        }

        // Check for date conflicts and closed dates
        $checkInDateFormatted = $checkInDate->format('Y-m-d');
        $checkOutDateFormatted = $checkOutDate->format('Y-m-d');

        // Check if any date in the booking range is closed (excluding checkout date)
        $dateRange = [];
        $current = clone $checkInDate;
        while ($current < $checkOutDate) {
            $dateRange[] = $current->format('Y-m-d');
            $current->modify('+1 day');
        }

        if (!empty($dateRange)) {
            $closedDates = \App\Models\ClosedDate::whereIn('closed_date', $dateRange)->exists();
            if ($closedDates) {
                return response()->json([
                    'success' => false,
                    'message' => 'One or more dates in your booking range are closed for booking. Please select different dates.',
                ], 422);
            }
        }

        // Check for date conflicts
        // Rule: Checkout is at 12 PM, Check-in is at 2 PM
        // So we can allow a new booking to check in on the same day another guest checks out
        // A conflict occurs when:
        // 1. New check-in date is BEFORE an existing checkout date AND
        // 2. New checkout date is AFTER an existing check-in date
        $conflictingBookings = Booking::where('BookingStatus', '!=', 'Cancelled')
            ->where(function($q) use ($checkInDateFormatted, $checkOutDateFormatted) {
                // Conflict: new check-in < existing checkout AND new checkout > existing check-in
                $q->where('CheckInDate', '<', $checkOutDateFormatted)
                  ->where('CheckOutDate', '>', $checkInDateFormatted);
            })
            ->count();

        if ($conflictingBookings > 0) {
            return response()->json([
                'success' => false,
                'message' => 'These dates conflict with an existing booking. Please select different dates.',
            ], 422);
        }

        // Persist immediately: upsert Guest, create Booking, then handle Payment
        $halfAmount = $totalAmount * 0.5;
        $paymentStatus = 'Downpayment';
        if ($validated['amount_paid'] >= $totalAmount) {
            $paymentStatus = 'Fully Paid';
        }

        // Upsert guest by Email (fallback to Phone)
        $guest = Guest::where('Email', $validated['guest_email'])->first();
        if (!$guest && !empty($validated['guest_phone'])) {
            $guest = Guest::where('Phone', $validated['guest_phone'])->first();
        }
        if ($guest) {
            $guest->update([
                'FName' => $validated['guest_fname'],
                'LName' => $validated['guest_lname'],
                'Email' => $validated['guest_email'],
                'Phone' => $validated['guest_phone'],
                'Address' => $validated['guest_address'] ?? $guest->Address,
            ]);
        } else {
            $guest = Guest::create([
                'FName' => $validated['guest_fname'],
                'LName' => $validated['guest_lname'],
                'Email' => $validated['guest_email'],
                'Phone' => $validated['guest_phone'],
                'Address' => $validated['guest_address'] ?? '',
                'Contactable' => true,
            ]);
        }

    $totalGuests = $validated['regular_guests'] + $validated['children'] + $validated['num_of_seniors'];
        $booking = Booking::create([
            'GuestID' => $guest->GuestID,
            'PackageID' => $validated['package_id'],
            'BookingDate' => now()->format('Y-m-d H:i:s'),
            'CheckInDate' => $checkInDate->format('Y-m-d H:i:s'),
            'CheckOutDate' => $checkOutDate->format('Y-m-d H:i:s'),
            'BookingStatus' => 'Pending',
            'Pax' => $totalGuests,
            'NumOfAdults' => $validated['regular_guests'],
            'NumOfChild' => $validated['children'],
            'NumOfSeniors' => $validated['num_of_seniors'],
            'ExcessFee' => $excessFee,
        ]);

        // Compute total amount from booking (price per day + excess)
        $computedTotal = $totalAmount;

        // Handle payment flow
        $method = strtolower($validated['payment_method']);
        if ($method === 'paymongo') {
            // Create PayMongo checkout via service, then create Payment row as For Verification
            $purposeMap = [
                'reservation_fee' => 'Downpayment',
                'downpayment' => 'Downpayment',
                'full_payment' => 'Full Payment',
            ];
            $purpose = $purposeMap[$validated['payment_purpose']] ?? 'Downpayment';

            $amountCentavos = (int) round($validated['amount_paid'] * 100);
            $desc = 'JBRB Admin Checkout ' . $booking->BookingID . ' - ' . $purpose;
            $email = $guest->Email ?? null;
            $name = trim(($guest->FName ?? '') . ' ' . ($guest->LName ?? ''));

            $metadata = array_filter([
                'source' => 'admin',
                'purpose' => $purpose,
                'booking_id' => $booking->BookingID,
                'customer_email' => $email,
                'customer_name' => $name,
            ]);

            $successUrl = route('admin.payments.paymongo.success', ['booking' => $booking->BookingID]);
            $cancelUrl = route('admin.payments.paymongo.cancel', ['booking' => $booking->BookingID]);

            $paymongo = app(PaymongoService::class);
            $bdoType = config('paymongo.bdo_method_type', 'online_banking_bdo');
            $attempts = [
                config('paymongo.default_method_types', ['gcash', $bdoType]),
                ['gcash', 'online_banking'],
                ['gcash'],
            ];

            $result = null; $lastError = null;
            foreach ($attempts as $methods) {
                try {
                    $result = $paymongo->createCheckoutSession(
                        $amountCentavos,
                        $desc,
                        $metadata,
                        $methods,
                        $successUrl,
                        $cancelUrl,
                        'JBRB-ADMIN-' . $booking->BookingID
                    );
                    if (!empty($result['checkout_url'])) break;
                } catch (\Throwable $e) {
                    $lastError = $e;
                    Log::warning('Admin checkout attempt failed', ['methods' => $methods, 'error' => $e->getMessage()]);
                }
            }

            if (!$result || empty($result['checkout_url'])) {
                Log::error('Admin PayMongo checkout failed on store()', ['error' => $lastError?->getMessage()]);
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to create PayMongo checkout at the moment.'
                ], 500);
            }

            $linkId = $result['id'] ?? null;
            $checkoutUrl = $result['checkout_url'];

            // Store payment data in session to create on success instead of upfront (no "For Verification")
            Session::put('admin_paymongo_pending', [
                'booking_id' => $booking->BookingID,
                'amount' => $validated['amount_paid'],
                'total_amount' => $computedTotal,
                'purpose' => $purpose,
                'reference' => $linkId ?: $checkoutUrl,
                'processed_by' => Auth::user() ? Auth::user()->user_id : null,
                'processor_name' => Auth::user() ? Auth::user()->name : 'Admin',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Proceed to PayMongo checkout.',
                'redirect_url' => $checkoutUrl,
                'booking_id' => $booking->BookingID,
            ]);
        } else {
            // Non-PayMongo payment: persist Payment immediately
            $payment = Payment::create([
                'BookingID' => $booking->BookingID,
                'Amount' => $validated['amount_paid'],
                'TotalAmount' => $computedTotal,
                'PaymentMethod' => $validated['payment_method'],
                'PaymentStatus' => $paymentStatus,
                'PaymentPurpose' => ucfirst(str_replace('_', ' ', $validated['payment_purpose'])),
                'PaymentDate' => now()->format('Y-m-d H:i:s'),
                'ReferenceNumber' => $validated['reference_number'] ?? null,
                'processed_by' => Auth::user() ? Auth::user()->user_id : null, // Admin/staff who created booking
            ]);

            // Create transaction record for manual payment
            // Map payment status to transaction enum values
            $transactionStatus = match($paymentStatus) {
                'Fully Paid' => 'Fully Paid',
                'Partial' => 'Partial Payment',
                'Downpayment' => 'Downpayment',
                default => 'Downpayment',
            };
            
            Transaction::create([
                'transaction_type' => 'booking',
                'reference_id' => $payment->PaymentID,
                'transaction_date' => now(),
                'amount' => $validated['amount_paid'],
                'payment_method' => $validated['payment_method'],
                'payment_status' => $transactionStatus,
                'purpose' => ucfirst(str_replace('_', ' ', $validated['payment_purpose'])),
                'booking_id' => $booking->BookingID,
                'guest_id' => $guest->GuestID,
                'customer_name' => trim($validated['guest_fname'] . ' ' . ($validated['guest_mname'] ?? '') . ' ' . $validated['guest_lname']),
                'customer_email' => $validated['guest_email'] ?? null,
                'customer_phone' => $validated['guest_phone'] ?? null,
                'processed_by' => Auth::user() ? Auth::user()->user_id : null,
                'processor_name' => Auth::user() ? Auth::user()->name : 'Admin',
                'amount_received' => $validated['amount_paid'],
                'reference_number' => $validated['reference_number'] ?? null,
                'metadata' => [
                    'total_amount' => $computedTotal,
                    'source' => 'admin_booking_create',
                    'payment_status_original' => $paymentStatus,
                ],
            ]);

            // Mark as Booked when there is any upfront payment
            $booking->update(['BookingStatus' => 'Booked']);

            // Audit log: booking created (non-paymongo)
            try {
                Audit_Log::create([
                    'user_id' => Auth::user()->user_id ?? null,
                    'action' => 'Create Booking',
                    'description' => 'Created booking ' . ($booking->BookingID ?? 'unknown') . ' amount: ' . ($validated['amount_paid'] ?? '0'),
                    'ip_address' => $request->ip(),
                ]);
            } catch (\Exception $e) {
                // ignore logging failure
            }

            return response()->json([
                'success' => true,
                'message' => 'Booking created successfully.',
                'booking' => $booking->load(['guest','package','payments'])
            ]);
        }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Booking creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while preparing the booking draft: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Commit a previously saved draft (from session) and persist Guest, Booking, and Payment.
     */
    public function commitDraft(Request $request, string $draftId): JsonResponse
    {
        $drafts = Session::get('admin_booking_drafts', []);
        if (!isset($drafts[$draftId])) {
            return response()->json([
                'success' => false,
                'message' => 'Draft not found or already committed.',
            ], 404);
        }

        $draft = $drafts[$draftId];

        try {
            // Guest: find by email or phone, update or create
            $guest = Guest::where('Email', $draft['guest']['Email'])->first();
            if (!$guest && !empty($draft['guest']['Phone'])) {
                $guest = Guest::where('Phone', $draft['guest']['Phone'])->first();
            }

            if ($guest) {
                $guest->update([
                    'FName' => $draft['guest']['FName'],
                    'LName' => $draft['guest']['LName'],
                    'Email' => $draft['guest']['Email'],
                    'Phone' => $draft['guest']['Phone'],
                    'Address' => $draft['guest']['Address'] ?? $guest->Address,
                ]);
            } else {
                $guest = Guest::create($draft['guest']);
            }

            // Booking
            $bookingData = $draft['booking'];
            $booking = Booking::create(array_merge($bookingData, [
                'GuestID' => $guest->GuestID,
            ]));

            // Payment
            $paymentData = $draft['payment'];
            $payment = Payment::create(array_merge($paymentData, [
                'BookingID' => $booking->BookingID,
            ]));

            // Remove draft after successful commit
            unset($drafts[$draftId]);
            Session::put('admin_booking_drafts', $drafts);

            return response()->json([
                'success' => true,
                'message' => 'Booking draft committed successfully.',
                'booking' => $booking->load(['guest', 'package', 'payments']),
                'payment' => $payment,
            ]);
        } catch (\Exception $e) {
            // If draft committed successfully we would have returned already. No extra logging here.
        } catch (\Exception $e) {
            Log::error('Failed to commit booking draft', [
                'draft_id' => $draftId,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to commit draft: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Discard a booking draft from session.
     */
    public function discardDraft(string $draftId): JsonResponse
    {
        $drafts = Session::get('admin_booking_drafts', []);
        if (!isset($drafts[$draftId])) {
            return response()->json([
                'success' => false,
                'message' => 'Draft not found.',
            ], 404);
        }
        unset($drafts[$draftId]);
        Session::put('admin_booking_drafts', $drafts);
        return response()->json([
            'success' => true,
            'message' => 'Draft discarded.',
        ]);
    }

    /**
     * Update an existing booking
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            Log::info('Update booking request received', [
                'booking_id' => $id,
                'data' => $request->all()
            ]);

            $booking = Booking::findOrFail($id);

            $validated = $request->validate([
                'checkout_date' => 'required|date',
                'adults' => 'required|integer|min:1',
                'children' => 'required|integer|min:0',
                'seniors' => 'required|integer|min:0',
                'package_id' => 'required|exists:packages,PackageID',
                'guest_name' => 'required|string',
                'email' => 'required|email',
                'phone' => 'required|string',
                'address' => 'nullable|string',
            ]);

            Log::info('Validation passed', ['validated' => $validated]);

            // Update guest information
            $guest = Guest::findOrFail($booking->GuestID);
            // The Guest model stores separate FName / MName / LName and exposes a computed GuestName accessor.
            // Split the provided full guest_name into components so updating works correctly.
            $fullName = trim($validated['guest_name']);
            $nameParts = preg_split('/\s+/', $fullName);
            $fName = $nameParts[0] ?? '';
            $lName = '';
            $mName = null;
            if (count($nameParts) > 1) {
                // Take last part as LName, everything in between as MName (to preserve multi-part middle names)
                $lName = array_pop($nameParts);
                if (count($nameParts) > 1) {
                    // Remaining parts after removing first form middle name(s)
                    $mName = implode(' ', array_slice($nameParts, 1));
                }
            }
            // Ensure middle name is null if empty string
            if ($mName !== null) {
                $mName = trim($mName) !== '' ? $mName : null;
            }
            $guest->update([
                'FName' => $fName,
                'MName' => $mName,
                'LName' => $lName,
                'Email' => $validated['email'],
                'Phone' => $validated['phone'],
                'Address' => $validated['address'] ?? $guest->Address,
            ]);

            Log::info('Guest updated', ['guest_id' => $guest->GuestID]);

            // Update customer name in transactions table
            $fullNameWithMiddle = trim($fName . ' ' . ($mName ?? '') . ' ' . $lName);
            \App\Models\Transaction::where('reference_id', $booking->BookingID)->update([
                'customer_name' => $fullNameWithMiddle,
                'customer_email' => $validated['email'],
                'customer_phone' => $validated['phone'],
            ]);

            // Get package information
            $package = Package::findOrFail($validated['package_id']);

            // Calculate days with fixed 12:00 PM times
            $checkIn = Carbon::parse($booking->CheckInDate);
            $checkOut = Carbon::parse($validated['checkout_date'])->setTime(12, 0, 0); // Set to 12:00 PM
            $days = $checkIn->diffInDays($checkOut);

            // Check for closed dates in the updated range
            $checkInDateFormatted = $checkIn->format('Y-m-d');
            $checkOutDateFormatted = $checkOut->format('Y-m-d');

            $closedDates = \App\Models\ClosedDate::whereBetween('closed_date', [$checkInDateFormatted, $checkOutDateFormatted])->exists();
            if ($closedDates) {
                return response()->json([
                    'success' => false,
                    'message' => 'One or more dates in your updated booking range are closed for booking. Please select different dates.',
                ], 422);
            }

            // Calculate excess guests (only adults count)
            $maxGuests = $package->max_guests ?? 30;
            $adultCount = $validated['adults'] + $validated['seniors'];
            $excessGuests = max(0, $adultCount - $maxGuests);
            $excessFee = $excessGuests * 100;

  // Calculate new total amount (senior discounts are handled at bill-out)
            $packageTotal = $package->Price * $days;
            $perAdultShare = $adultCount > 0 ? ($packageTotal / max(1, $adultCount)) : 0; // kept for reference
            $seniorDiscount = 0; // no automatic discount
            $totalAmount = ($packageTotal + $excessFee);

            Log::info('Calculations done', [
                'days' => $days,
                'excess_guests' => $excessGuests,
                'excess_fee' => $excessFee,
                'total_amount' => $totalAmount
            ]);

            // Update booking with fixed checkout time
            $totalGuests = $validated['adults'] + $validated['children'] + $validated['seniors'];
            $booking->update([
                'CheckOutDate' => $checkOut->format('Y-m-d H:i:s'),
                'NumOfAdults' => $validated['adults'],
                'NumOfChild' => $validated['children'],
                'NumOfSeniors' => $validated['seniors'],
                'Pax' => $totalGuests,
                'PackageID' => $validated['package_id'],
                'ExcessFee' => $excessFee,
            ]);

            Log::info('Booking updated', ['booking_id' => $booking->BookingID]);

            // Update payment total amount
            $payment = $booking->payments()->first();
            if ($payment) {
                $payment->update([
                    'TotalAmount' => $totalAmount,
                ]);
                Log::info('Payment updated', ['payment_id' => $payment->PaymentID]);
                // Audit log: payment total updated due to booking update
                try {
                    Audit_Log::create([
                        'user_id' => Auth::user()->user_id ?? null,
                        'action' => 'Update Booking',
                        'description' => 'Updated payment TotalAmount for booking ' . ($booking->BookingID ?? $id) . ' payment: ' . ($payment->PaymentID ?? 'n/a'),
                        'ip_address' => request()->ip(),
                    ]);
                } catch (\Exception $e) {
                    // ignore
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Booking updated successfully',
                'booking' => $booking->load(['guest', 'package', 'payments'])
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Update booking failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update booking: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update booking status
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|string|in:Confirmed,Cancelled,Completed,Staying',
            'user_id' => 'nullable|string', // For confirmation when reopening or cancelling
        ]);

        // If reopening a cancelled booking or cancelling, require user_id
        if (($booking->BookingStatus === 'Cancelled' && $validated['status'] !== 'Cancelled') ||
            ($booking->BookingStatus !== 'Cancelled' && $validated['status'] === 'Cancelled')) {
            if (empty($validated['user_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'User ID confirmation required for this action',
                    'require_confirmation' => true
                ], 422);
            }
        }

        $updateData = ['BookingStatus' => $validated['status']];

        // Capture check-in time when status is 'Staying' and ActualCheckInTime is not set
        if ($validated['status'] === 'Staying') {
            if (is_null($booking->ActualCheckInTime)) {
                $updateData['ActualCheckInTime'] = now();
            }
        }

        // Capture check-out time when status changes to 'Completed' and ActualCheckOutTime is not set
        if ($validated['status'] === 'Completed') {
            if (is_null($booking->ActualCheckOutTime)) {
                $updateData['ActualCheckOutTime'] = now();
            }
        }

        $booking->update($updateData);

        // Audit log: booking status changed
        try {
            Audit_Log::create([
                'user_id' => Auth::user()->user_id ?? null,
                'action' => 'Update Booking Status',
                'description' => 'Changed status of ' . ($booking->BookingID ?? $id) . ' to ' . ($validated['status'] ?? 'unknown'),
                'ip_address' => request()->ip(),
            ]);
        } catch (\Exception $e) {
            // ignore logging errors
        }

        return response()->json([
            'success' => true,
            'message' => 'Booking status updated successfully',
            'booking' => $booking->load(['guest', 'package', 'payments'])
        ]);
    }

    /**
     * Update payment information
     */
    public function updatePayment(Request $request, $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);

        $payment = $booking->payments->first(); // Get the first payment
        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'No payment record found for this booking'
            ], 404);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'status' => 'required|string|in:Downpayment,Partial,Fully Paid'
        ]);

        $payment->update([
            'Amount' => $validated['amount'],
            'PaymentMethod' => $validated['payment_method'],
            'PaymentStatus' => $validated['status'],
        ]);

        // Audit log: payment updated
        try {
            Audit_Log::create([
                'user_id' => Auth::user()->user_id ?? null,
                'action' => 'Update Booking Payment',
                'description' => 'Updated payment ' . ($payment->PaymentID ?? 'n/a') . ' for booking ' . ($booking->BookingID ?? $id) . ' status: ' . ($validated['status'] ?? 'n/a'),
                'ip_address' => request()->ip(),
            ]);
        } catch (\Exception $e) {
            // ignore
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment updated successfully',
            'payment' => $payment
        ]);
    }

    /**
     * Delete a booking
     */
    public function destroy($id): JsonResponse
    {
        $booking = Booking::findOrFail($id);

        if ($booking->payments) {
            $booking->payments()->delete(); // Delete all associated payments
        }

        $booking->delete();

        // Audit log: booking deleted
        try {
            Audit_Log::create([
                'user_id' => Auth::user()->user_id ?? null,
                'action' => 'Delete Booking',
                'description' => 'Deleted booking ' . ($booking->BookingID ?? $id),
                'ip_address' => request()->ip(),
            ]);
        } catch (\Exception $e) {
            // ignore
        }

        return response()->json([
            'success' => true,
            'message' => 'Booking deleted successfully'
        ]);
    }

    /**
     * Get bookings for calendar view
     */
    public function getCalendarData(): JsonResponse
    {
        $bookings = Booking::with(['guest', 'package'])
            ->whereIn('BookingStatus', ['Confirmed', 'Pending'])
            ->get()
            ->map(function ($booking) {
                return [
                    'id' => $booking->BookingID,
                    'title' => ($booking->guest->FName ?? 'Guest') . ' - ' . ($booking->package->Name ?? 'Package'),
                    'start' => $booking->CheckInDate->format('Y-m-d'),
                    'end' => $booking->CheckOutDate->format('Y-m-d'),
                    'backgroundColor' => $booking->BookingStatus === 'Confirmed' ? '#10b981' : '#f59e0b',
                    'borderColor' => $booking->BookingStatus === 'Confirmed' ? '#059669' : '#d97706',
                    'extendedProps' => [
                        'guestName' => $booking->guest ? $booking->guest->FName . ' ' . $booking->guest->LName : 'N/A',
                        'packageName' => $booking->package->Name ?? 'N/A',
                        'pax' => $booking->Pax,
                        'status' => $booking->BookingStatus,
                    ]
                ];
            });

        return response()->json($bookings);
    }

    /**
     * Get statistics for dashboard
     */
    public function getStatistics(): JsonResponse
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();

        $stats = [
            'total_bookings' => Booking::count(),
            'confirmed_bookings' => Booking::where('BookingStatus', 'Confirmed')->count(),
            'pending_bookings' => Booking::where('BookingStatus', 'Pending')->count(),
            'today_checkins' => Booking::whereDate('CheckInDate', $today)->count(),
            'month_bookings' => Booking::where('BookingDate', '>=', $thisMonth)->count(),
            'total_revenue' => Payment::where('PaymentStatus', 'Paid')->sum('Amount'),
            'month_revenue' => Payment::where('PaymentStatus', 'Paid')
                ->where('PaymentDate', '>=', $thisMonth)
                ->sum('Amount'),
        ];

        return response()->json($stats);
    }

    /**
     * Show specific booking details
     */
    public function show($id): JsonResponse
    {
        $booking = Booking::with(['guest', 'package', 'payments'])->findOrFail($id);

        // Removed auto-update to "Staying" - user must manually check in

        $checkInDate = Carbon::parse($booking->CheckInDate);
        $checkOutDate = Carbon::parse($booking->CheckOutDate);
        $bookingDate = Carbon::parse($booking->BookingDate);

        $daysOfStay = $checkInDate->diffInDays($checkOutDate);

        // Calculate total paid from all payments
        $totalPaid = $booking->payments->sum('Amount');

        // Get all payment methods used
        $paymentMethods = $booking->payments->pluck('PaymentMethod')->unique()->join(', ');
        if (empty($paymentMethods)) {
            $paymentMethods = 'N/A';
        }

        $packagePrice = $booking->package->Price ?? 0;
        $totalAmount = $packagePrice * $daysOfStay;

        // Use stored ExcessFee from booking record
        $excessFee = $booking->ExcessFee ?? 0;
        $maxGuests = $booking->package->max_guests ?? 30;
        $excessGuests = max(0, $booking->NumOfAdults - $maxGuests);
        $totalAmount += $excessFee;

        // Determine overall payment status based on actual payment statuses
        // All payments are auto-confirmed, no "For Verification" status
        $paymentStatuses = $booking->payments->pluck('PaymentStatus')->unique();

        if ($totalPaid >= $totalAmount) {
            $paymentStatus = 'Fully Paid';
        } elseif ($totalPaid > 0) {
            // Check if we have multiple payments to determine Partial vs Downpayment
            $paymentCount = $booking->payments->count();
            $paymentStatus = $paymentCount > 1 ? 'Partial' : 'Downpayment';
        } else {
            $paymentStatus = 'Unpaid';
        }

        return response()->json([
            'success' => true,
            'booking' => [
                'BookingID' => $booking->BookingID,
                'BookingDate' => $bookingDate->format('F d, Y'),
                'CheckInDate' => $checkInDate->format('F d, Y'),
                'CheckOutDate' => $checkOutDate->format('F d, Y'),
                'CheckInDateRaw' => $booking->CheckInDate,
                'CheckOutDateRaw' => $booking->CheckOutDate,
                'ActualCheckInTime' => $booking->ActualCheckInTime ? Carbon::parse($booking->ActualCheckInTime)->format('g:i A') : null,
                'ActualCheckOutTime' => $booking->ActualCheckOutTime ? Carbon::parse($booking->ActualCheckOutTime)->format('g:i A') : null,
                'DaysOfStay' => $daysOfStay,
                'BookingStatus' => $booking->BookingStatus,
                'Pax' => $booking->Pax,
                'NumOfAdults' => $booking->NumOfAdults,
                'NumOfSeniors' => $booking->NumOfSeniors,
                'NumOfChild' => $booking->NumOfChild,
                'ExcessGuests' => $excessGuests,
                'ExcessFee' => $excessFee,
            ],
            'guest' => $booking->guest ? [
                'GuestID' => $booking->guest->GuestID,
                'GuestName' => trim($booking->guest->FName . ' ' . ($booking->guest->MName ?? '') . ' ' . $booking->guest->LName),
                'FName' => $booking->guest->FName,
                'MName' => $booking->guest->MName ?? '',
                'middle_name' => $booking->guest->MName ?? '',
                'LName' => $booking->guest->LName,
                'Email' => $booking->guest->Email,
                'Phone' => $booking->guest->Phone,
                'Address' => $booking->guest->Address,
            ] : null,
            'package' => $booking->package ? [
                'PackageID' => $booking->package->PackageID,
                'PackageName' => $booking->package->Name,
                'Name' => $booking->package->Name,
                'Price' => $booking->package->Price,
                'MaxGuests' => $maxGuests,
                'amenities_array' => $booking->package->amenities_array,
            ] : null,
            'payment' => [
                'PaymentStatus' => $paymentStatus,
                'AmountPaid' => $totalPaid,
                'PaymentMethods' => $paymentMethods,
                'PaymentMethod' => $paymentMethods, // For backward compatibility
                'TotalAmount' => $totalAmount,
                'RemainingBalance' => max(0, $totalAmount - $totalPaid),
            ],
            'payments' => $booking->payments->map(function ($payment) {
                return [
                    'PaymentID' => $payment->PaymentID,
                    'Amount' => $payment->Amount,
                    'PaymentMethod' => $payment->PaymentMethod,
                    'PaymentStatus' => $payment->PaymentStatus,
                    'PaymentDate' => Carbon::parse($payment->PaymentDate)->format('F d, Y'),
                    'PaymentPurpose' => $payment->PaymentPurpose ?? 'Booking Payment',
                    'ReferenceNumber' => $payment->ReferenceNumber ?? 'N/A',
                    // Payment proof image removed from schema; no image field returned
                ];
            })
        ]);
    }

    /**
     * Get payment history for a booking
     */
    public function getPaymentHistory($id): JsonResponse
    {
        $booking = Booking::with('payments')->findOrFail($id);

        $payments = $booking->payments->map(function ($payment) {
            return [
                'PaymentID' => $payment->PaymentID,
                'Amount' => $payment->Amount,
                'PaymentMethod' => $payment->PaymentMethod,
                'PaymentStatus' => $payment->PaymentStatus,
                'PaymentDate' => $payment->PaymentDate,
                'PaymentPurpose' => $payment->PaymentPurpose,
            ];
        })->all();

        return response()->json([
            'success' => true,
            'payments' => $payments
        ]);
    }

    /**
     * Add additional payment to a booking
     */
    public function addPayment(Request $request, $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'purpose' => 'nullable|string',
            'reference_number' => 'nullable|string',
        ]);

        // Calculate total amount for this booking
        $package = $booking->package;
        $checkInDate = new \DateTime($booking->CheckInDate);
        $checkOutDate = new \DateTime($booking->CheckOutDate);
        $days = $checkInDate->diff($checkOutDate)->days;
        $totalPrice = ($package->Price ?? 0) * $days;

        // Use stored ExcessFee instead of recalculating
        $excessFee = $booking->ExcessFee ?? 0;
        $totalAmount = $totalPrice + $excessFee;

        // Calculate total paid including this new payment
        $previousTotal = $booking->payments->sum('Amount');
        $newTotal = $previousTotal + $validated['amount'];

        // Determine payment status: Downpayment, Partial, or Fully Paid
        $halfAmount = $totalAmount * 0.5;
        $paymentStatus = 'Downpayment';

        if ($newTotal >= $totalAmount) {
            $paymentStatus = 'Fully Paid';
        } elseif ($newTotal > $halfAmount) {
            $paymentStatus = 'Partial';
        }

        // Create new payment record
        $payment = Payment::create([
            'BookingID' => $booking->BookingID,
            'Amount' => $validated['amount'],
            'TotalAmount' => $totalAmount,
            'PaymentMethod' => $validated['payment_method'],
            'PaymentStatus' => $paymentStatus,
            'PaymentPurpose' => $validated['purpose'] ?? 'Additional Payment',
            'PaymentDate' => now(),
            'ReferenceNumber' => $validated['reference_number'] ?? null,
            'processed_by' => Auth::user() ? Auth::user()->user_id : null,
        ]);

        // Create transaction record for additional payment
        // Map payment status to transaction enum values
        $transactionStatus = match($paymentStatus) {
            'Fully Paid' => 'Fully Paid',
            'Partial' => 'Partial Payment',
            'Downpayment' => 'Downpayment',
            default => 'Downpayment',
        };
        
        Transaction::create([
            'transaction_type' => 'booking',
            'reference_id' => $payment->PaymentID,
            'transaction_date' => now(),
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'payment_status' => $transactionStatus,
            'purpose' => $validated['purpose'] ?? 'Additional Payment',
            'booking_id' => $booking->BookingID,
            'guest_id' => $booking->GuestID,
            'customer_name' => trim(($booking->guest->FName ?? '') . ' ' . ($booking->guest->MName ?? '') . ' ' . ($booking->guest->LName ?? '')),
            'customer_email' => $booking->guest->Email ?? null,
            'customer_phone' => $booking->guest->Phone ?? null,
                'processed_by' => Auth::user() ? Auth::user()->user_id : null,
            'processor_name' => Auth::user() ? Auth::user()->name : 'Admin',
            'amount_received' => $validated['amount'],
            'reference_number' => $validated['reference_number'] ?? null,
            'metadata' => [
                'total_amount' => $totalAmount,
                'total_paid' => $newTotal,
                'previous_total' => $previousTotal,
                'source' => 'admin_add_payment',
                'payment_status_original' => $paymentStatus,
            ],
        ]);

        // Audit log: additional payment added
        try {
            Audit_Log::create([
                'user_id' => Auth::user()->user_id ?? null,
                'action' => 'Add Payment',
                'description' => 'Added payment ' . ($payment->PaymentID ?? 'n/a') . ' amount: ' . ($validated['amount'] ?? '0') . ' for booking ' . ($booking->BookingID ?? $id),
                'ip_address' => request()->ip(),
            ]);
        } catch (\Exception $e) {
            // ignore
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment added successfully',
            'payment' => $payment->fresh()
        ]);
    }

    /**
     * Check for booking date conflicts
     */
    public function checkDateConflict(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'booking_id' => 'nullable|string', // For edit mode, exclude this booking
        ]);

        $checkIn = new \DateTime($validated['check_in']);
        $checkOut = new \DateTime($validated['check_out']);

        // Query for overlapping bookings
        // Rule: Checkout is at 12 PM, Check-in is at 2 PM
        // So we can allow a new booking to check in on the same day another guest checks out
        // A conflict occurs when:
        // 1. New check-in date is BEFORE an existing checkout date AND
        // 2. New checkout date is AFTER an existing check-in date
        $query = Booking::where('BookingStatus', '!=', 'Cancelled')
            ->where(function($q) use ($checkIn, $checkOut) {
                $q->where('CheckInDate', '<', $checkOut->format('Y-m-d'))
                  ->where('CheckOutDate', '>', $checkIn->format('Y-m-d'));
            });

        // Exclude current booking if editing
        if (!empty($validated['booking_id'])) {
            $query->where('BookingID', '!=', $validated['booking_id']);
        }

        $conflicts = $query->get();

        if ($conflicts->count() > 0) {
            return response()->json([
                'conflict' => true,
                'message' => 'These dates conflict with existing bookings',
                'conflicting_bookings' => $conflicts->map(function($booking) {
                    return [
                        'BookingID' => $booking->BookingID,
                        'CheckInDate' => $booking->CheckInDate->format('Y-m-d'),
                        'CheckOutDate' => $booking->CheckOutDate->format('Y-m-d'),
                    ];
                })
            ]);
        }

        return response()->json([
            'conflict' => false,
            'message' => 'Dates are available'
        ]);
    }

    /**
     * Get all booked date ranges
     */
    public function getBookedDates(): JsonResponse
    {
        $bookings = Booking::whereIn('BookingStatus', ['Pending', 'Booked', 'Confirmed', 'Staying'])
            ->select('CheckInDate', 'CheckOutDate')
            ->get()
            ->map(function($booking) {
                // Checkout date should be available for new check-ins
                // So we mark dates from check-in to (checkout - 1 day) as booked
                $checkOutDate = $booking->CheckOutDate->copy()->subDay();
                return [
                    'start' => $booking->CheckInDate->format('Y-m-d'),
                    'end' => $checkOutDate->format('Y-m-d'),
                ];
            });

        return response()->json([
            'success' => true,
            'booked_dates' => $bookings
        ]);
    }

    /**
     * Get closed dates for the booking modals
     */
    public function getClosedDates(): JsonResponse
    {
        $closedDates = ClosedDate::pluck('closed_date')
            ->map(function($date) {
                return Carbon::parse($date)->format('Y-m-d');
            })
            ->toArray();

        return response()->json([
            'success' => true,
            'closed_dates' => $closedDates
        ]);
    }

    /**
     * Verify payment for a booking
     * This updates the payment status to "Downpayment" and booking status to "Confirmed"
     */
    public function verifyPayment($id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|string|max:255',
            'payment_id' => 'required|string|max:255',
        ]);

        try {
            $booking = Booking::with('payments')->findOrFail($id);

            // Find the specific payment
            $payment = Payment::where('PaymentID', $validated['payment_id'])
                ->where('BookingID', $id)
                ->first();

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found.'
                ], 404);
            }

            // Check if already verified
            if ($payment->PaymentStatus !== 'For Verification') {
                return response()->json([
                    'success' => false,
                    'message' => 'This payment has already been verified.'
                ], 400);
            }

            // Calculate total amount for the booking
            $checkInDate = Carbon::parse($booking->CheckInDate);
            $checkOutDate = Carbon::parse($booking->CheckOutDate);
            $daysOfStay = $checkInDate->diffInDays($checkOutDate);
            $packagePrice = $booking->package->Price ?? 0;
            $totalAmount = ($packagePrice * $daysOfStay) + ($booking->ExcessFee ?? 0);

            // Determine the correct payment status based on amount paid
            if ($payment->Amount >= $totalAmount) {
                $payment->PaymentStatus = 'Fully Paid';
            } else {
                $payment->PaymentStatus = 'Downpayment';
            }
            $payment->save();

            // Update booking status to "Confirmed" if not already
            if ($booking->BookingStatus !== 'Confirmed') {
                $booking->BookingStatus = 'Confirmed';
                $booking->save();
            }

            // Log the verification action
            Log::info("Payment {$validated['payment_id']} verified for booking {$id} by user {$validated['user_id']}. Status set to {$payment->PaymentStatus}.");

            // Audit log: payment verified
            try {
                Audit_Log::create([
                    'user_id' => Auth::user()->user_id ?? null,
                    'action' => 'Verify Payment',
                    'description' => 'Verified payment ' . ($validated['payment_id'] ?? 'n/a') . ' for booking ' . $id . ' by user ' . ($validated['user_id'] ?? 'n/a') . ' status: ' . $payment->PaymentStatus,
                    'ip_address' => $request->ip(),
                ]);
            } catch (\Exception $e) {
                // ignore logging errors
            }

            return response()->json([
                'success' => true,
                'message' => "Payment verified successfully. Payment status updated to {$payment->PaymentStatus} and booking status updated to Confirmed."
            ]);

        } catch (\Exception $e) {
            Log::error("Error verifying payment for booking {$id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify payment: ' . $e->getMessage()
            ], 500);
        }
    }
}
