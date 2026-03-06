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
        $search  = request('search', '');
        $status  = request('status', '');
        $payment = request('payment', '');

        $paymentOptions = ['Fully Paid', 'Partial', 'Downpayment', 'Unpaid'];

        $sort = request('sort', 'status_priority');

        $query = Booking::with(['guest', 'package', 'payments']);

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

        if ($status !== '') {
            $query->where('BookingStatus', $status);
        }

        if ($payment !== '') {
            $this->applyPaymentFilter($query, $payment);
        }

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
     * Apply payment filter safely
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
     * Get bookings data for AJAX/DataTables with full null safety
     */
    public function getData(): JsonResponse
    {
        $bookings = Booking::with(['guest', 'package', 'payments'])
            ->orderBy('CheckInDate', 'asc')
            ->get()
            ->map(function ($booking) {
                $guest   = $booking->guest;
                $package = $booking->package;

                $checkIn  = $booking->CheckInDate ? Carbon::parse($booking->CheckInDate) : null;
                $checkOut = $booking->CheckOutDate ? Carbon::parse($booking->CheckOutDate) : null;

                $days = $checkIn && $checkOut ? $checkIn->diffInDays($checkOut) : 0;

                $packageTotal = ($package?->Price ?? 0) * max(1, $days); // avoid 0 days
                $excessFee    = $booking->ExcessFee ?? 0;
                $totalAmount  = $packageTotal + $excessFee;

                $totalPaid = $booking->payments?->sum('Amount') ?? 0;

                $paymentStatus = 'Unpaid';
                if ($totalAmount > 0) {
                    if ($totalPaid >= $totalAmount) {
                        $paymentStatus = 'Fully Paid';
                    } elseif ($totalPaid > ($totalAmount * 0.50)) {
                        $paymentStatus = 'Partial';
                    } elseif ($totalPaid > 0) {
                        $paymentStatus = 'Downpayment';
                    }
                }

                $paymentMethods = $booking->payments?->pluck('PaymentMethod')->unique()->join(', ') ?: 'N/A';

                $guestName = $guest ? trim(($guest->FName ?? '') . ' ' . ($guest->LName ?? '')) : 'N/A';

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
                    'GuestName'     => $guestName,
                    'GuestEmail'    => $guest?->Email ?? 'N/A',
                    'GuestPhone'    => $guest?->Phone ?? 'N/A',
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
     * Store a new booking - with added null safety
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
            $adultCount   = ($validated['regular_guests'] ?? 0) + ($validated['num_of_seniors'] ?? 0);
            $excessGuests = max(0, $adultCount - $maxGuests);
            $excessFee    = $excessGuests * 100;
            $totalAmount  = $packageTotal + $excessFee;

            // Your full store logic here (guest upsert, closed dates check, conflict check, create booking/payment)
            // For example:
            $guest = Guest::firstOrCreate(
                ['Email' => $validated['guest_email']],
                [
                    'FName'   => $validated['guest_fname'],
                    'LName'   => $validated['guest_lname'],
                    'Phone'   => $validated['guest_phone'],
                    'Address' => $validated['guest_address'] ?? '',
                ]
            );

            // ... conflict / closed date checks ...

            $booking = Booking::create([
                'GuestID'      => $guest->GuestID,
                'PackageID'    => $package->PackageID,
                'BookingDate'  => now(),
                'CheckInDate'  => $checkIn,
                'CheckOutDate' => $checkOut,
                'BookingStatus'=> 'Pending',
                'Pax'          => ($validated['regular_guests'] ?? 0) + ($validated['children'] ?? 0) + ($validated['num_of_seniors'] ?? 0),
                'NumOfAdults'  => $validated['regular_guests'] ?? 0,
                'NumOfChild'   => $validated['children'] ?? 0,
                'NumOfSeniors' => $validated['num_of_seniors'] ?? 0,
                'ExcessFee'    => $excessFee,
            ]);

            // ... payment creation ...

            return response()->json([
                'success' => true,
                'message' => 'Booking created successfully',
                'booking' => $booking,
            ]);
        } catch (\Exception $e) {
            Log::error('Booking store failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create booking: ' . $e->getMessage(),
            ], 500);
        }
    }

    // Add any other methods here if needed
}