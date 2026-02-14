<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\ClosedDate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard with real data.
     */
    public function index(Request $request): View
    {
        // Handle date filtering
        $dateRange = $this->getDateRange($request);
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];
        
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();

        // Get statistics
        $stats = [
            'total_bookings' => Booking::count(),
            'confirmed_bookings' => Booking::where('BookingStatus', 'Confirmed')->count(),
            'pending_bookings' => Booking::where('BookingStatus', 'Pending')->count(),
            'completed_bookings' => Booking::where('BookingStatus', 'Completed')->count(),
            'cancelled_bookings' => Booking::where('BookingStatus', 'Cancelled')->count(),
            'today_checkins' => Booking::whereDate('CheckInDate', $today)->count(),
            'today_checkouts' => Booking::whereDate('CheckOutDate', $today)->count(),
            'month_bookings' => Booking::where('BookingDate', '>=', $thisMonth)->count(),
            'total_guests' => Guest::count(),
            'total_revenue' => Payment::where('PaymentStatus', 'Paid')->sum('Amount'),
            'month_revenue' => Payment::where('PaymentStatus', 'Paid')
                ->where('PaymentDate', '>=', $thisMonth)
                ->sum('Amount'),
            'pending_payments' => Payment::where('PaymentStatus', 'Pending')->sum('Amount'),
        ];

        // Get recent bookings
        $recentBookings = Booking::with(['guest', 'package', 'payments']) // Corrected to 'payments'
            ->orderBy('BookingDate', 'desc')
            ->limit(5)
            ->get();

        // Get upcoming check-ins
        $upcomingCheckins = Booking::with(['guest', 'package'])
            ->where('CheckInDate', '>=', $today)
            ->where('BookingStatus', 'Confirmed')
            ->orderBy('CheckInDate', 'asc')
            ->limit(5)
            ->get();

        // Get monthly booking trend (last 6 months)
        $monthlyBookings = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthlyBookings[] = [
                'month' => $month->format('M Y'),
                'count' => Booking::whereYear('BookingDate', $month->year)
                    ->whereMonth('BookingDate', $month->month)
                    ->count()
            ];
        }

        // Get monthly revenue trend (last 6 months)
        $monthlyRevenue = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthlyRevenue[] = [
                'month' => $month->format('M Y'),
                'revenue' => Payment::where('PaymentStatus', 'Paid')
                    ->whereYear('PaymentDate', $month->year)
                    ->whereMonth('PaymentDate', $month->month)
                    ->sum('Amount')
            ];
        }

        // Get chart data based on date filter
        $chartData = $this->getChartData($startDate, $endDate, $request->input('preset', 'month'));

        return view('admin.bookings.booking-dashboard', compact(
            'stats',
            'recentBookings',
            'upcomingCheckins',
            'monthlyBookings',
            'monthlyRevenue',
            'startDate',
            'endDate',
            'chartData'
        ));
    }

    public function getCalendarData(Request $request)
    {
        $year = (int) $request->get('year', date('Y'));
        $month = (int) $request->get('month', date('n'));

        // Define month range
        $monthStart = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $monthEnd = $monthStart->copy()->endOfMonth()->endOfDay();

        // Any booking that overlaps this month (align with guest view statuses)
        $overlappingBookings = Booking::whereIn('BookingStatus', ['Pending', 'Booked', 'Confirmed', 'Staying', 'Completed'])
            ->where(function ($q) use ($monthStart, $monthEnd) {
                $q->whereBetween('CheckInDate', [$monthStart, $monthEnd])
                  ->orWhereBetween('CheckOutDate', [$monthStart, $monthEnd])
                  ->orWhere(function ($q2) use ($monthStart, $monthEnd) {
                      $q2->where('CheckInDate', '<=', $monthStart)
                         ->where('CheckOutDate', '>=', $monthEnd);
                  });
            })
            ->get();

        // Build booked dates as day-level, start to finish INCLUSIVE to match guest side
        $bookedDates = $overlappingBookings->flatMap(function ($booking) use ($monthStart, $monthEnd) {
            $dates = [];
            $start = Carbon::parse($booking->CheckInDate)->startOfDay();
            $endInclusive = Carbon::parse($booking->CheckOutDate)->startOfDay(); // include check-out day

            // Clamp to month range
            $rangeStart = $start->greaterThan($monthStart) ? $start->copy() : $monthStart->copy();
            $rangeEndInclusive = $endInclusive->lessThan($monthEnd) ? $endInclusive->copy() : $monthEnd->copy();

            while ($rangeStart->lte($rangeEndInclusive)) {
                $dates[] = $rangeStart->format('Y-m-d');
                $rangeStart->addDay();
            }
            return $dates;
        })->unique()->values()->toArray();

        // Get closed dates for the month and filter out any that are booked (defensive)
        $closedDates = ClosedDate::whereBetween('closed_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->pluck('closed_date')
            ->map(function ($date) {
                return Carbon::parse($date)->format('Y-m-d');
            })
            ->filter(function ($date) use ($bookedDates) {
                return !in_array($date, $bookedDates, true);
            })
            ->values()
            ->toArray();

        return response()->json([
            'booked_dates' => $bookedDates,
            'closed_dates' => $closedDates,
        ]);
    }

    public function toggleClosedDate(Request $request)
    {
        $date = $request->input('date');
        $isClosed = (bool) $request->input('is_closed');

        // Normalize date boundaries (treat as a single day range)
        $dayStart = Carbon::parse($date)->startOfDay();
        $dayEndExclusive = $dayStart->copy()->addDay();

        if ($isClosed) {
            // Prevent closing a date that is already booked (nightly occupancy)
            $hasBooking = Booking::whereIn('BookingStatus', ['Pending', 'Booked', 'Confirmed', 'Staying', 'Completed'])
                // Inclusive day-level overlap: any touch blocks closing
                ->where('CheckInDate', '<=', $dayEndExclusive)
                ->where('CheckOutDate', '>=', $dayStart)
                ->exists();

            if ($hasBooking) {
                return response()->json([
                    'success' => false,
                    'message' => 'This date is booked and cannot be closed.',
                ], 409);
            }

            ClosedDate::firstOrCreate(
                ['closed_date' => $dayStart->toDateString()],
                [
                    'reason' => 'Manually closed from dashboard',
                    'closed_by' => Auth::user()->user_id ?? null,
                ]
            );
        } else {
            ClosedDate::where('closed_date', $dayStart->toDateString())->delete();
        }

        return response()->json(['success' => true]);
    }

    /**
     * Get date range based on preset or custom dates
     */
    private function getDateRange(Request $request)
    {
        $preset = $request->input('preset', 'month');
        
        $startDate = null;
        $endDate = Carbon::now()->endOfDay();

        switch ($preset) {
            case 'year':
                $startDate = Carbon::now()->startOfYear();
                break;
            case 'week':
                $startDate = Carbon::now()->startOfWeek();
                break;
            case 'custom':
                $startDate = $request->input('start_date') 
                    ? Carbon::parse($request->input('start_date'))->startOfDay()
                    : Carbon::now()->startOfMonth();
                $endDate = $request->input('end_date')
                    ? Carbon::parse($request->input('end_date'))->endOfDay()
                    : Carbon::now()->endOfDay();
                break;
            case 'month':
            default:
                $startDate = Carbon::now()->startOfMonth();
                break;
        }

        return [
            'start' => $startDate,
            'end' => $endDate,
        ];
    }

    /**
     * Get chart data based on selected date range
     */
    private function getChartData($startDate, $endDate, $preset)
    {
        $labels = [];
        $bookingsData = [];
        $cancellationsData = [];
        
        // Determine granularity based on preset
        switch ($preset) {
            case 'year':
                // Show monthly data for the year
                $start = $startDate->copy()->startOfMonth();
                $end = $endDate->copy();
                
                while ($start <= $end) {
                    $labels[] = $start->format('M');
                    
                    $bookingsData[] = Booking::whereMonth('CheckInDate', $start->month)
                        ->whereYear('CheckInDate', $start->year)
                        ->whereIn('BookingStatus', ['Confirmed', 'Completed', 'Staying'])
                        ->count();
                    
                    $cancellationsData[] = Booking::whereMonth('CheckInDate', $start->month)
                        ->whereYear('CheckInDate', $start->year)
                        ->where('BookingStatus', 'Cancelled')
                        ->count();
                    
                    $start->addMonth();
                }
                break;
                
            case 'week':
                // Show daily data for the week
                $start = $startDate->copy();
                $end = $endDate->copy();
                
                while ($start <= $end) {
                    $labels[] = $start->format('D');
                    
                    $bookingsData[] = Booking::whereDate('CheckInDate', $start->toDateString())
                        ->whereIn('BookingStatus', ['Confirmed', 'Completed', 'Staying'])
                        ->count();
                    
                    $cancellationsData[] = Booking::whereDate('CheckInDate', $start->toDateString())
                        ->where('BookingStatus', 'Cancelled')
                        ->count();
                    
                    $start->addDay();
                }
                break;
                
            case 'custom':
                // Determine granularity based on date range length
                $daysDiff = $startDate->diffInDays($endDate);
                
                if ($daysDiff <= 31) {
                    // Daily for ranges up to 31 days
                    $start = $startDate->copy();
                    $end = $endDate->copy();
                    
                    while ($start <= $end) {
                        $labels[] = $start->format('M d');
                        
                        $bookingsData[] = Booking::whereDate('CheckInDate', $start->toDateString())
                            ->whereIn('BookingStatus', ['Confirmed', 'Completed', 'Staying'])
                            ->count();
                        
                        $cancellationsData[] = Booking::whereDate('CheckInDate', $start->toDateString())
                            ->where('BookingStatus', 'Cancelled')
                            ->count();
                        
                        $start->addDay();
                    }
                } else {
                    // Monthly for longer ranges
                    $start = $startDate->copy()->startOfMonth();
                    $end = $endDate->copy();
                    
                    while ($start <= $end) {
                        $labels[] = $start->format('M Y');
                        
                        $bookingsData[] = Booking::whereMonth('CheckInDate', $start->month)
                            ->whereYear('CheckInDate', $start->year)
                            ->whereBetween('CheckInDate', [$startDate, $endDate])
                            ->whereIn('BookingStatus', ['Confirmed', 'Completed', 'Staying'])
                            ->count();
                        
                        $cancellationsData[] = Booking::whereMonth('CheckInDate', $start->month)
                            ->whereYear('CheckInDate', $start->year)
                            ->whereBetween('CheckInDate', [$startDate, $endDate])
                            ->where('BookingStatus', 'Cancelled')
                            ->count();
                        
                        $start->addMonth();
                    }
                }
                break;
                
            case 'month':
            default:
                // Show weekly data for the month
                $start = $startDate->copy();
                $end = $endDate->copy();
                $weekNum = 1;
                
                while ($start <= $end) {
                    $weekEnd = $start->copy()->endOfWeek();
                    if ($weekEnd > $end) {
                        $weekEnd = $end->copy();
                    }
                    
                    $labels[] = 'Week ' . $weekNum;
                    
                    $bookingsData[] = Booking::whereBetween('CheckInDate', [
                            $start->startOfDay(),
                            $weekEnd->endOfDay()
                        ])
                        ->whereIn('BookingStatus', ['Confirmed', 'Completed', 'Staying'])
                        ->count();
                    
                    $cancellationsData[] = Booking::whereBetween('CheckInDate', [
                            $start->startOfDay(),
                            $weekEnd->endOfDay()
                        ])
                        ->where('BookingStatus', 'Cancelled')
                        ->count();
                    
                    $start = $weekEnd->copy()->addDay()->startOfDay();
                    $weekNum++;
                }
                break;
        }

        return [
            'labels' => $labels,
            'bookings' => $bookingsData,
            'cancellations' => $cancellationsData,
            'totalBookings' => array_sum($bookingsData),
            'totalCancellations' => array_sum($cancellationsData),
        ];
    }
}
