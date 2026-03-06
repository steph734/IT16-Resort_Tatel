<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Package;
use App\Models\ClosedDate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BookingController extends Controller
{
    /**
     * Display the bookings page with all bookings data
     */
    public function index(): View
    {
        $search  = request('search', '');
        $status  = request('status', '');
        $payment = request('payment', '');

        $paymentOptions = ['Fully Paid', 'Partial', 'Downpayment', 'Unpaid'];

        $sort = request('sort', 'status_priority');

        $query = Booking::with(['guest', 'package', 'payments']);

        // Search filter
        if ($search !== '') {
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
        if ($status !== '') {
            $query->where('BookingStatus', $status);
        }

        // Payment filter
        if ($payment !== '') {
            $this->applyPaymentFilter($query, $payment);
        }

        // Sorting
        switch ($sort) {
            case 'status_priority':
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
                      ->orderBy('CheckInDate', 'asc');
                break;

            case 'checkin_farthest':
                $query->orderBy('CheckInDate', 'desc');
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

        return view('admin.bookings.bookings', compact(
            'bookings',
            'packages',
            'statuses',
            'paymentOptions'
        ));
    }

    /**
     * Apply payment filter to query
     */
    protected function applyPaymentFilter($query, string $filter): void
    {
        $filter = trim(strtolower($filter));
        if ($filter === '') {
            return;
        }

        $paymentsSub = DB::table('payments')
            ->select('BookingID', DB::raw('COALESCE(SUM(Amount), 0) as paid'), DB::raw('LOWER(MAX(PaymentStatus)) as payment_status'))
            ->groupBy('BookingID');

        $query->leftJoinSub($paymentsSub, 'p', function ($join) {
            $join->on('p.BookingID', '=', 'bookings.BookingID');
        })->leftJoin('packages', 'bookings.PackageID', '=', 'packages.PackageID');

        $daysExpr = "GREATEST(DATEDIFF(bookings.CheckOutDate, bookings.CheckInDate), 1)";
        $totalExpr = "(COALESCE(packages.Price, 0) * {$daysExpr}) + COALESCE(bookings.ExcessFee, 0)";
        $paidExpr = "COALESCE(p.paid, 0)";
        $statusExpr = "LOWER(COALESCE(p.payment_status, ''))";

        $downpaymentThreshold = 0.50;
        $partialThreshold = 0.50;

        switch ($filter) {
            case 'fully paid':
            case 'fullypaid':
                $query->whereRaw("({$paidExpr} >= {$totalExpr} AND {$totalExpr} > 0)")
                      ->orWhereRaw("{$statusExpr} = 'fully paid'");
                break;

            case 'partial':
                $query->whereRaw("({$paidExpr} > ({$totalExpr} * {$partialThreshold}) AND {$paidExpr} < {$totalExpr})")
                      ->orWhereRaw("{$statusExpr} = 'partial'");
                break;

            case 'downpayment':
                $query->whereRaw("({$paidExpr} > 0 AND {$paidExpr} <= ({$totalExpr} * {$downpaymentThreshold}))")
                      ->orWhereRaw("{$statusExpr} = 'downpayment'");
                break;

            case 'unpaid':
                $query->whereRaw("({$paidExpr} = 0 AND {$totalExpr} > 0)")
                      ->orWhereRaw("{$statusExpr} = 'unpaid'");
                break;

            default:
                $query->whereRaw("{$statusExpr} = ?", [$filter]);
                break;
        }

        $query->select('bookings.*')->distinct();
    }

    public function create(): View
    {
        $packages = Package::all();

        foreach ($packages as $package) {
            $package->amenities_array = $package->amenities
                ? array_filter(array_map('trim', explode("\n", $package->amenities ?? '')))
                : [];
        }

        return view('admin.bookings.create', compact('packages'));
    }

    /**
     * Get bookings data for AJAX/DataTables
     */
    public function getData(): JsonResponse
    {
        $bookings = Booking::with(['guest', 'package', 'payments'])
            ->orderBy('CheckInDate', 'asc')
            ->get()
            ->map(function ($booking) {
                $package = $booking->package;

                $checkIn = $booking->CheckInDate ? Carbon::parse($booking->CheckInDate) : null;
                $checkOut = $booking->CheckOutDate ? Carbon::parse($booking->CheckOutDate) : null;

                $days = $checkIn && $checkOut ? $checkIn->diffInDays($checkOut) : 0;

                $packageTotal = ($package?->Price ?? 0) * max(1, $days);
                $excessFee = $booking->ExcessFee ?? 0;
                $totalAmount = $packageTotal + $excessFee;

                $totalPaid = $booking->payments?->sum('Amount') ?? 0;

                $paymentStatus = 'Unpaid';
                if ($totalPaid >= $totalAmount && $totalAmount > 0) {
                    $paymentStatus = 'Fully Paid';
                } elseif ($totalPaid > ($totalAmount * 0.50)) {
                    $paymentStatus = 'Partial';
                } elseif ($totalPaid > 0) {
                    $paymentStatus = 'Downpayment';
                }

                $paymentMethods = $booking->payments?->pluck('PaymentMethod')->unique()->join(', ') ?: 'N/A';

                return [
                    'BookingID'     => $booking->BookingID ?? 'N/A',
                    'BookingDate'   => $booking->BookingDate?->format('Y-m-d') ?? 'N/A',
                    'CheckInDate'   => $booking->CheckInDate ?? 'N/A',
                    'CheckOutDate'  => $booking->CheckOutDate ?? 'N/A',
                    'BookingStatus' => $booking->BookingStatus ?? 'N/A',
                    'Pax'           => $booking->Pax ?? 0,
                    'NumOfAdults'   => $booking->NumOfAdults ?? 0,
                    'NumOfSeniors'  => $booking->NumOfSeniors ?? 0,
                    'NumOfChild'    => $booking->NumOfChild ?? 0,
                    'GuestName'     => $booking->guest ? trim(($booking->guest->FName ?? '') . ' ' . ($booking->guest->LName ?? '')) : 'N/A',
                    'GuestEmail'    => $booking->guest?->Email ?? 'N/A',
                    'GuestPhone'    => $booking->guest?->Phone ?? 'N/A',
                    'PackageName'   => $package?->Name ?? 'N/A',
                    'PackagePrice'  => $package?->Price ?? 0,
                    'PaymentStatus' => $paymentStatus,
                    'PaymentMethod' => $paymentMethods,
                    'AmountPaid'    => $totalPaid,
                ];
            });

        return response()->json($bookings);
    }

    /**
     * Store a new booking (partial - showing safety fixes only)
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'guest_fname'     => 'required|string|max:255',
                'guest_lname'     => 'required|string|max:255',
                'guest_email'     => 'required|email|max:255',
                'guest_phone'     => 'required|string|max:20',
                'guest_address'   => 'nullable|string',
                'check_in'        => 'required|date|after_or_equal:today',
                'check_out'       => 'required|date|after:check_in',
                'regular_guests'  => 'required|integer|min:1',
                'children'        => 'required|integer|min:0',
                'num_of_seniors'  => 'required|integer|min:0',
                'package_id'      => 'required|exists:packages,PackageID',
                'payment_method'  => 'required|string',
                'payment_purpose' => 'required|string|in:reservation_fee,downpayment,full_payment',
                'amount_paid'     => 'required|numeric|min:0',
                'reference_number'=> 'nullable|string',
            ]);

            $package = Package::findOrFail($validated['package_id']);

            $checkIn  = Carbon::parse($validated['check_in'])->setTime(14, 0, 0);
            $checkOut = Carbon::parse($validated['check_out'])->setTime(12, 0, 0);

            $days = max(1, $checkIn->diffInDays($checkOut));

            $packageTotal = ($package->Price ?? 0) * $days;
            $maxGuests    = $package->max_guests ?? 30;
            $adultCount   = $validated['regular_guests'] + $validated['num_of_seniors'];
            $excessGuests = max(0, $adultCount - $maxGuests);
            $excessFee    = $excessGuests * 100;
            $totalAmount  = $packageTotal + $excessFee;

            // ... rest of your store logic (guest upsert, conflict check, booking creation, payment) ...

            // Example safe access in conflict check:
            $conflictingBookings = Booking::where('BookingStatus', '!=', 'Cancelled')
                ->where('CheckInDate', '<', $checkOut->format('Y-m-d'))
                ->where('CheckOutDate', '>', $checkIn->format('Y-m-d'))
                ->count();

            if ($conflictingBookings > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'These dates conflict with an existing booking.',
                ], 422);
            }

            // ... continue with guest creation/update, booking save, etc.

            // Return success
            return response()->json([
                'success' => true,
                'message' => 'Booking created successfully',
                'booking_id' => $booking->BookingID ?? null,
            ]);

        } catch (\Exception $e) {
            Log::error('Booking store failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create booking. Please try again.',
            ], 500);
        }
    }
}