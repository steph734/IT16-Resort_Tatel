<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\UnpaidItem;
use App\Models\Booking;
use App\Models\Rental;
use App\Models\RentalFee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Audit_Log;

class SalesController extends Controller
{
    /**
     * Display the sales dashboard (overview).
     */
    public function index()
    {
        return view('admin.sales.sales-dashboard');
    }

    /**
     * Display the sales dashboard (overview).
     */
    public function dashboard()
    {
        return view('admin.sales.sales-dashboard');
    }

    /**
     * Display the transactions ledger.
     */
    public function ledger()
    {
        return view('admin.sales.transactions-ledger');
    }

    /**
     * Display the reports page.
     */
    public function reports()
    {
        // Get completed bookings for Per Booking report selector
        $completedBookings = \App\Models\Booking::with(['guest', 'package'])
            ->where('BookingStatus', 'Completed')
            ->orderBy('CheckOutDate', 'desc')
            ->get()
            ->map(function($booking) {
                return [
                    'BookingID' => $booking->BookingID,
                    'GuestName' => $booking->guest ? trim($booking->guest->FName . ' ' . ($booking->guest->MName ? $booking->guest->MName . ' ' : '') . $booking->guest->LName) : 'Unknown Guest',
                    'CheckInDate' => $booking->CheckInDate,
                    'CheckOutDate' => $booking->CheckOutDate,
                    'PackageName' => $booking->package->Name ?? 'Unknown Package',
                ];
            });

        return view('admin.sales.reports', compact('completedBookings'));
    }

    /**
     * Generate Per Booking Sales Report
     */
    public function generatePerBookingReport(Request $request)
    {
        $bookingId = $request->input('booking_id');
        $format = $request->input('format', 'pdf');

        $booking = \App\Models\Booking::with(['guest', 'package', 'payments', 'rentals.rentalItem', 'rentals.fees', 'unpaidItems'])
            ->where('BookingID', $bookingId)
            ->where('BookingStatus', 'Completed')
            ->firstOrFail();

        // Calculate booking costs
        $checkIn = \Carbon\Carbon::parse($booking->CheckInDate);
        $checkOut = \Carbon\Carbon::parse($booking->CheckOutDate);
        $days = $checkIn->diffInDays($checkOut);
        $packageCost = ($booking->package->Price ?? 0) * $days;
        $excessFee = $booking->ExcessFee ?? 0;
        $seniorDiscount = $booking->senior_discount ?? 0;
        $bookingTotal = $packageCost + $excessFee - $seniorDiscount;

        // Get all payments with details
        $payments = $booking->payments->map(function($payment) {
            return [
                'PaymentID' => $payment->PaymentID,
                'Amount' => floatval($payment->Amount),
                'PaymentMethod' => $payment->PaymentMethod,
                'PaymentPurpose' => $payment->PaymentPurpose,
                'PaymentDate' => $payment->PaymentDate,
                'PaymentStatus' => $payment->PaymentStatus,
            ];
        });

        // Get rental charges and fees
        $rentals = $booking->rentals->map(function($rental) {
            $charges = $rental->calculateTotalCharges();
            
            // Calculate rental fee based on rate type
            $rentalFee = 0;
            $usageDisplay = '';
            if ($rental->rate_type_snapshot === 'Per-Day') {
                $endDate = $rental->returned_at ?? \Carbon\Carbon::now();
                $days = $rental->issued_at->diffInDays($endDate);
                $days = max(1, $days);
                $rentalFee = $rental->rate_snapshot * $days * $rental->quantity;
                $usageDisplay = "{$days} day" . ($days > 1 ? 's' : '') . " @ ₱" . number_format($rental->rate_snapshot, 2) . "/day";
            } else {
                $rentalFee = $rental->rate_snapshot * $rental->quantity;
                $usageDisplay = "Flat rate @ ₱" . number_format($rental->rate_snapshot, 2);
            }
            
            // Get individual fees from eager-loaded relationship
            $damageFee = $rental->fees->where('type', 'Damage')->sum('amount');
            $lostFee = $rental->fees->where('type', 'Loss')->sum('amount');
            
            // Calculate total: rental fee + all fees
            $total = $rentalFee + $damageFee + $lostFee;
            
            return [
                'rental_id' => $rental->id,
                'item_name' => $rental->rentalItem->name ?? 'Unknown Item',
                'quantity' => $rental->quantity,
                'usage_display' => $usageDisplay,
                'rental_fee' => floatval($rentalFee),
                'damage_fee' => floatval($damageFee),
                'lost_fee' => floatval($lostFee),
                'total' => floatval($total),
                'status' => $rental->status,
            ];
        });

        // Get unpaid items (store purchases)
        $unpaidItems = $booking->unpaidItems->where('IsPaid', true)->map(function($item) {
            return [
                'UnpaidItemID' => $item->UnpaidItemID,
                'ItemName' => $item->ItemName,
                'Quantity' => $item->Quantity,
                'UnitPrice' => floatval($item->UnitPrice),
                'TotalAmount' => floatval($item->TotalAmount),
            ];
        });

        $reportData = [
            'booking' => [
                'BookingID' => $booking->BookingID,
                'GuestName' => $booking->guest ? trim($booking->guest->FName . ' ' . ($booking->guest->MName ? $booking->guest->MName . ' ' : '') . $booking->guest->LName) : 'Unknown Guest',
                'GuestEmail' => $booking->guest->Email ?? 'N/A',
                'GuestPhone' => $booking->guest->Phone ?? 'N/A',
                'PackageName' => $booking->package->Name ?? 'Unknown Package',
                'CheckInDate' => \Carbon\Carbon::parse($booking->CheckInDate)->format('M d, Y g:i A'),
                'CheckOutDate' => \Carbon\Carbon::parse($booking->CheckOutDate)->format('M d, Y g:i A'),
                'Days' => $days,
                'PackageCost' => $packageCost,
                'ExcessFee' => $excessFee,
                'SeniorDiscount' => $seniorDiscount,
                'BookingTotal' => $bookingTotal,
            ],
            'payments' => $payments,
            'rentals' => $rentals,
            'unpaidItems' => $unpaidItems,
            'totals' => [
                'BookingAmount' => $bookingTotal,
                'RentalAmount' => $rentals->sum('total'),
                'StoreAmount' => $unpaidItems->sum('TotalAmount'),
                'GrandTotal' => $payments->sum('Amount'),
                'RentalDamageFees' => $rentals->sum('damage_fee'),
                'RentalLostFees' => $rentals->sum('lost_fee'),
            ],
        ];

        // Audit log: generated per-booking report
        try {
            Audit_Log::create([
                'user_id' => Auth::user()->user_id ?? null,
                'action' => 'Generate Per Booking Report',
                'description' => 'Generated per-booking sales report for booking ' . ($booking->BookingID ?? 'n/a') . ' format: ' . $format,
                'ip_address' => $request->ip(),
            ]);
        } catch (\Exception $e) {
            // ignore logging failures
        }

        if ($format === 'pdf') {
            return $this->generatePerBookingPDF($reportData);
        } elseif ($format === 'json') {
            return response()->json($reportData);
        } else {
            return $this->generatePerBookingCSV($reportData);
        }
    }

    /**
     * Generate Monthly Sales Report
     */
    public function generateMonthlyReport(Request $request)
    {
        $month = $request->input('month'); // Format: YYYY-MM
        $format = $request->input('format', 'pdf');

        $startDate = \Carbon\Carbon::parse($month . '-01')->startOfMonth();
        $endDate = \Carbon\Carbon::parse($month . '-01')->endOfMonth();

        // Get all payments made during this period (includes downpayment, partial, and full payments)
        $allPayments = \App\Models\Payment::whereBetween('PaymentDate', [$startDate, $endDate])
            ->where('PaymentStatus', '!=', 'Cancelled')
            ->get();

        // Calculate total sales from booking payments
        $bookingSales = $allPayments->sum('Amount');
        
        // Calculate total rental sales (calculated from rate_snapshot × quantity × days)
        $rentalsInPeriod = \App\Models\Rental::whereBetween('issued_at', [$startDate, $endDate])->get();
        
        $rentalSales = 0;
        foreach($rentalsInPeriod as $rental) {
            if ($rental->rate_type_snapshot === 'Per-Day') {
                $rentalEndDate = $rental->returned_at ?? \Carbon\Carbon::now();
                $days = $rental->issued_at->diffInDays($rentalEndDate);
                $days = max(1, $days);
                $rentalSales += $rental->rate_snapshot * $days * $rental->quantity;
            } else {
                $rentalSales += $rental->rate_snapshot * $rental->quantity;
            }
        }
        
        // Calculate additional charges (damage and loss fees)
        $additionalCharges = \App\Models\RentalFee::whereBetween('created_at', [$startDate, $endDate])->sum('amount');
        
        // Total sales = Booking payments + Rental sales + Additional charges
        $totalSales = $bookingSales + $rentalSales + $additionalCharges;

        // Get daily sales breakdown (group payments by date)
        $dailySales = \App\Models\Payment::whereBetween('PaymentDate', [$startDate, $endDate])
            ->where('PaymentStatus', '!=', 'Cancelled')
            ->selectRaw('DATE(PaymentDate) as date, SUM(Amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function($day) use ($startDate, $endDate, $rentalsInPeriod) {
                // Add rental sales and fees for rentals issued on this day
                $rentalSalesForDay = 0;
                foreach($rentalsInPeriod as $rental) {
                    if ($rental->issued_at && $rental->issued_at->toDateString() === $day->date) {
                        if ($rental->rate_type_snapshot === 'Per-Day') {
                            $rentalEndDate = $rental->returned_at ?? \Carbon\Carbon::now();
                            $days = $rental->issued_at->diffInDays($rentalEndDate);
                            $days = max(1, $days);
                            $rentalSalesForDay += $rental->rate_snapshot * $days * $rental->quantity;
                        } else {
                            $rentalSalesForDay += $rental->rate_snapshot * $rental->quantity;
                        }
                        // Add additional charges (damage/loss fees)
                        $rentalSalesForDay += $rental->fees()->sum('amount');
                    }
                }
                
                return [
                    'date' => \Carbon\Carbon::parse($day->date)->format('M d, Y'),
                    'total' => $day->total + $rentalSalesForDay,
                ];
            });

        // Package Sales - All packages availed during the month
        $packageSales = \App\Models\Booking::with(['package', 'payments'])
            ->whereBetween('CheckInDate', [$startDate, $endDate])
            ->get()
            ->groupBy('PackageID')
            ->map(function($bookings) use ($startDate, $endDate) {
                $package = $bookings->first()->package;
                $totalSales = 0;
                $bookingCount = $bookings->count();
                
                // Sum all payments for these bookings made during the month
                foreach($bookings as $booking) {
                    $bookingPayments = $booking->payments()
                        ->whereBetween('PaymentDate', [$startDate, $endDate])
                        ->where('PaymentStatus', '!=', 'Cancelled')
                        ->sum('Amount');
                    $totalSales += $bookingPayments;
                }
                
                return [
                    'name' => $package->Name ?? 'Unknown',
                    'bookings' => $bookingCount,
                    'sales' => $totalSales,
                ];
            })
            ->sortByDesc('sales')
            ->values();

        // Rental Item Sales - All rental items rented during the month
        $rentalItemSales = \App\Models\Rental::with(['rentalItem', 'fees'])
            ->whereBetween('issued_at', [$startDate, $endDate])
            ->get()
            ->groupBy('rental_item_id')
            ->map(function($rentals) {
                $item = $rentals->first()->rentalItem;
                $totalSales = 0;
                $timesRented = $rentals->sum('quantity');
                
                foreach($rentals as $rental) {
                    // Calculate rental charge from rate_snapshot
                    if ($rental->rate_type_snapshot === 'Per-Day') {
                        $rentalEndDate = $rental->returned_at ?? \Carbon\Carbon::now();
                        $days = $rental->issued_at->diffInDays($rentalEndDate);
                        $days = max(1, $days);
                        $totalSales += $rental->rate_snapshot * $days * $rental->quantity;
                    } else {
                        $totalSales += $rental->rate_snapshot * $rental->quantity;
                    }
                }
                
                return [
                    'name' => $item->name ?? 'Unknown',
                    'times_rented' => $timesRented,
                    'sales' => $totalSales,
                ];
            })
            ->sortByDesc('sales')
            ->values();

        // Rental Charges Summary - Only damage and lost fees (subsection of rental sales)
        $rentalCharges = [
            'damage_fees' => 0,
            'lost_fees' => 0,
            'total' => 0,
        ];
        
        $rentalsInPeriod = \App\Models\Rental::whereBetween('issued_at', [$startDate, $endDate])->get();
        
        foreach($rentalsInPeriod as $rental) {
            $rentalCharges['damage_fees'] += $rental->fees()->where('type', 'Damage')->sum('amount');
            $rentalCharges['lost_fees'] += $rental->fees()->where('type', 'Loss')->sum('amount');
        }
        $rentalCharges['total'] = $rentalCharges['damage_fees'] + $rentalCharges['lost_fees'];

        // Weekly breakdown - Get sales for each week of the month
        $weeklySummary = [];
        $currentDate = $startDate->copy();
        $weekNumber = 1;
        
        while ($currentDate <= $endDate) {
            $weekStart = $currentDate->copy()->startOfWeek();
            $weekEnd = $currentDate->copy()->endOfWeek();
            
            // Ensure week boundaries are within the month
            if ($weekStart < $startDate) $weekStart = $startDate->copy();
            if ($weekEnd > $endDate) $weekEnd = $endDate->copy();
            
            // Booking payments for this week
            $weekBookingSales = \App\Models\Payment::whereBetween('PaymentDate', [$weekStart, $weekEnd])
                ->where('PaymentStatus', '!=', 'Cancelled')
                ->sum('Amount');
            
            // Rental sales for this week (from rentals issued during this week)
            $weekRentalSales = 0;
            foreach($rentalsInPeriod as $rental) {
                if ($rental->issued_at && $rental->issued_at->between($weekStart, $weekEnd)) {
                    if ($rental->rate_type_snapshot === 'Per-Day') {
                        $rentalEndDate = $rental->returned_at ?? \Carbon\Carbon::now();
                        $days = $rental->issued_at->diffInDays($rentalEndDate);
                        $days = max(1, $days);
                        $weekRentalSales += $rental->rate_snapshot * $days * $rental->quantity;
                    } else {
                        $weekRentalSales += $rental->rate_snapshot * $rental->quantity;
                    }
                    // Add additional charges
                    $weekRentalSales += $rental->fees()->sum('amount');
                }
            }
            
            $weeklySummary[] = [
                'week' => 'Week ' . $weekNumber,
                'date_range' => $weekStart->format('M d') . ' - ' . $weekEnd->format('M d'),
                'sales' => $weekBookingSales + $weekRentalSales,
            ];
            
            $currentDate->addWeek();
            $weekNumber++;
        }

        // Calculate section totals
        $packageSalesTotal = $packageSales->sum('sales');
        $rentalItemSalesTotal = $rentalItemSales->sum('sales');
        
        $reportData = [
            'period' => [
                'month' => $startDate->format('F Y'),
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'daily_sales' => $dailySales,
            'package_sales' => $packageSales,
            'package_sales_total' => $packageSalesTotal,
            'rental_item_sales' => $rentalItemSales,
            'rental_item_sales_total' => $rentalItemSalesTotal,
            'rental_charges' => $rentalCharges,
            'weekly_summary' => $weeklySummary,
            'totals' => [
                'sales' => $totalSales,
                'bookings' => \App\Models\Booking::whereBetween('CheckInDate', [$startDate, $endDate])->count(),
            ],
        ];

        // Audit log: generated monthly report
        try {
            Audit_Log::create([
                'user_id' => Auth::user()->user_id ?? null,
                'action' => 'Generate Monthly Report',
                'description' => 'Generated monthly sales report for ' . ($startDate->format('Y-m') ?? 'n/a') . ' format: ' . $format,
                'ip_address' => $request->ip(),
            ]);
        } catch (\Exception $e) {
            // ignore logging failures
        }

        if ($format === 'pdf') {
            return $this->generateMonthlyPDF($reportData);
        } elseif ($format === 'json') {
            return response()->json($reportData);
        } else {
            return $this->generateMonthlyCSV($reportData);
        }
    }

    /**
     * Generate Annual Sales Report
     */
    public function generateAnnualReport(Request $request)
    {
        $year = $request->input('year');
        $format = $request->input('format', 'pdf');

        $startDate = \Carbon\Carbon::parse($year . '-01-01')->startOfYear();
        $endDate = \Carbon\Carbon::parse($year . '-12-31')->endOfYear();

        // Calculate booking sales (all payments in the year, excluding Cancelled bookings)
        $bookingSales = \App\Models\Payment::whereHas('booking', function($query) {
            $query->where('BookingStatus', '!=', 'Cancelled');
        })
        ->whereBetween('PaymentDate', [$startDate, $endDate])
        ->sum('Amount');

        // Calculate rental sales from rate_snapshot - filter by rental start dates within the year
        $rentals = \App\Models\Rental::whereHas('booking', function($query) {
            $query->where('BookingStatus', '!=', 'Cancelled');
        })
        ->whereBetween('issued_at', [$startDate, $endDate])
        ->with('rentalItem')
        ->get();

        $rentalSales = $rentals->sum(function($rental) {
            $quantity = $rental->quantity ?? 1;
            $rateSnapshot = $rental->rate_snapshot ?? 0;
            $rateType = $rental->rate_type_snapshot ?? 'Per-Day';
            
            if ($rateType === 'Per-Day') {
                $issuedAt = \Carbon\Carbon::parse($rental->issued_at);
                $returnedAt = $rental->returned_at ? \Carbon\Carbon::parse($rental->returned_at) : \Carbon\Carbon::now();
                $days = max(1, $issuedAt->diffInDays($returnedAt));
                return $rateSnapshot * $quantity * $days;
            } else {
                return $rateSnapshot * $quantity;
            }
        });

        // Calculate additional charges (damage and loss fees) - filter by when fees were created
        $additionalCharges = \App\Models\RentalFee::whereHas('rental.booking', function($query) {
            $query->where('BookingStatus', '!=', 'Cancelled');
        })
        ->whereBetween('created_at', [$startDate, $endDate])
        ->sum('amount');

        $totalSales = $bookingSales + $rentalSales + $additionalCharges;

        // Monthly sales breakdown with rental sales included
        $monthlySalesData = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthStart = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
            $monthEnd = \Carbon\Carbon::create($year, $month, 1)->endOfMonth();

            // Booking sales for the month
            $monthBookingSales = \App\Models\Payment::whereHas('booking', function($query) {
                $query->where('BookingStatus', '!=', 'Cancelled');
            })
            ->whereBetween('PaymentDate', [$monthStart, $monthEnd])
            ->sum('Amount');

            // Rental sales for rentals starting in this month
            $monthRentals = \App\Models\Rental::whereHas('booking', function($query) {
                $query->where('BookingStatus', '!=', 'Cancelled');
            })
            ->whereBetween('issued_at', [$monthStart, $monthEnd])
            ->with('rentalItem')
            ->get();

            $monthRentalSales = $monthRentals->sum(function($rental) {
                $quantity = $rental->quantity ?? 1;
                $rateSnapshot = $rental->rate_snapshot ?? 0;
                $rateType = $rental->rate_type_snapshot ?? 'Per-Day';
                
                if ($rateType === 'Per-Day') {
                    $issuedAt = \Carbon\Carbon::parse($rental->issued_at);
                    $returnedAt = $rental->returned_at ? \Carbon\Carbon::parse($rental->returned_at) : \Carbon\Carbon::now();
                    $days = max(1, $issuedAt->diffInDays($returnedAt));
                    return $rateSnapshot * $quantity * $days;
                } else {
                    return $rateSnapshot * $quantity;
                }
            });

            // Additional charges created in this month
            $monthAdditionalCharges = \App\Models\RentalFee::whereHas('rental.booking', function($query) {
                $query->where('BookingStatus', '!=', 'Cancelled');
            })
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->sum('amount');

            $monthlySalesData[] = [
                'month' => $month,
                'sales' => $monthBookingSales + $monthRentalSales + $monthAdditionalCharges
            ];
        }

        // Calculate growth percentages
        $monthlySalesWithGrowth = collect($monthlySalesData)->map(function($item, $index) use ($monthlySalesData) {
            if ($index === 0 || $monthlySalesData[$index - 1]['sales'] == 0) {
                $growth = 0;
            } else {
                $prevTotal = $monthlySalesData[$index - 1]['sales'];
                $growth = $prevTotal > 0 ? (($item['sales'] - $prevTotal) / $prevTotal) * 100 : 0;
            }
            return [
                'month' => \Carbon\Carbon::create()->month($item['month'])->format('F'),
                'sales' => $item['sales'],
                'growth' => round($growth, 2),
            ];
        });

        // Package performance - calculate from payments made within the year
        $packagePerformance = \App\Models\Payment::with('booking.package')
            ->whereHas('booking', function($query) {
                $query->where('BookingStatus', '!=', 'Cancelled');
            })
            ->whereBetween('PaymentDate', [$startDate, $endDate])
            ->get()
            ->groupBy(function($payment) {
                return $payment->booking->PackageID;
            })
            ->map(function($payments) {
                $package = $payments->first()->booking->package;
                $bookingIds = $payments->pluck('BookingID')->unique();
                
                return [
                    'name' => $package->Name ?? 'Unknown',
                    'bookings' => $bookingIds->count(),
                    'sales' => $payments->sum('Amount'),
                ];
            })
            ->sortByDesc('sales')
            ->values();

        // Rental performance - group by rental item, filter by rental start dates
        $rentalPerformance = \App\Models\Rental::with('rentalItem')
            ->whereHas('booking', function($query) {
                $query->where('BookingStatus', '!=', 'Cancelled');
            })
            ->whereBetween('issued_at', [$startDate, $endDate])
            ->get()
            ->groupBy('rental_item_id')
            ->map(function($rentals) {
                $item = $rentals->first()->rentalItem;
                
                $sales = 0;
                foreach ($rentals as $rental) {
                    $quantity = $rental->quantity ?? 1;
                    $rateSnapshot = $rental->rate_snapshot ?? 0;
                    $rateType = $rental->rate_type_snapshot ?? 'Per-Day';
                    
                    if ($rateType === 'Per-Day') {
                        $issuedAt = \Carbon\Carbon::parse($rental->issued_at);
                        $returnedAt = $rental->returned_at ? \Carbon\Carbon::parse($rental->returned_at) : \Carbon\Carbon::now();
                        $days = max(1, $issuedAt->diffInDays($returnedAt));
                        $sales += $rateSnapshot * $quantity * $days;
                    } else {
                        $sales += $rateSnapshot * $quantity;
                    }
                }

                return [
                    'name' => $item->Name ?? 'Unknown',
                    'times_rented' => $rentals->count(),
                    'sales' => $sales,
                ];
            })
            ->sortByDesc('sales')
            ->values();

        // Rental charges breakdown - filter by when fees were created
        $damageFees = \App\Models\RentalFee::where('type', 'Damage')
            ->whereHas('rental.booking', function($query) {
                $query->where('BookingStatus', '!=', 'Cancelled');
            })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');

        $lostFees = \App\Models\RentalFee::where('type', 'Loss')
            ->whereHas('rental.booking', function($query) {
                $query->where('BookingStatus', '!=', 'Cancelled');
            })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');

        $reportData = [
            'period' => [
                'year' => $year,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'monthly_sales' => $monthlySalesWithGrowth,
            'package_performance' => $packagePerformance,
            'rental_performance' => $rentalPerformance,
            'rental_charges' => [
                'damage_fees' => $damageFees,
                'lost_fees' => $lostFees,
                'total' => $damageFees + $lostFees,
            ],
            'totals' => [
                'sales' => $totalSales,
                'bookings' => \App\Models\Payment::whereHas('booking', function($query) {
                    $query->where('BookingStatus', '!=', 'Cancelled');
                })
                ->whereBetween('PaymentDate', [$startDate, $endDate])
                ->distinct('BookingID')
                ->count('BookingID'),
            ],
            'isPdf' => ($format === 'pdf'),
        ];

        // Audit log: generated annual report
        try {
            Audit_Log::create([
                'user_id' => Auth::user()->user_id ?? null,
                'action' => 'Generate Annual Report',
                'description' => 'Generated annual sales report for year ' . ($year ?? 'n/a') . ' format: ' . $format,
                'ip_address' => $request->ip(),
            ]);
        } catch (\Exception $e) {
            // ignore logging failures
        }

        if ($format === 'pdf') {
            return $this->generateAnnualPDF($reportData);
        } elseif ($format === 'json') {
            return response()->json($reportData);
        } else {
            return $this->generateAnnualCSV($reportData);
        }
    }

    /**
     * Generate Custom Date Range Sales Report
     */
    public function generateCustomReport(Request $request)
    {
        $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
        $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay();
        $format = $request->input('format', 'pdf');

        // Calculate booking sales (all payments in the period, excluding Cancelled bookings)
        $bookingSales = \App\Models\Payment::whereHas('booking', function($query) {
            $query->where('BookingStatus', '!=', 'Cancelled');
        })
        ->whereBetween('PaymentDate', [$startDate, $endDate])
        ->sum('Amount');

        // Calculate rental sales from rate_snapshot - filter by rental issue dates within the period
        $rentalsInPeriod = \App\Models\Rental::whereBetween('issued_at', [$startDate, $endDate])->get();
        
        $rentalSales = 0;
        foreach($rentalsInPeriod as $rental) {
            if ($rental->rate_type_snapshot === 'Per-Day') {
                $rentalEndDate = $rental->returned_at ?? \Carbon\Carbon::now();
                $days = $rental->issued_at->diffInDays($rentalEndDate);
                $days = max(1, $days);
                $rentalSales += $rental->rate_snapshot * $days * $rental->quantity;
            } else {
                $rentalSales += $rental->rate_snapshot * $rental->quantity;
            }
        }

        // Calculate rental charges (damage and loss fees)
        $additionalCharges = \App\Models\RentalFee::whereBetween('created_at', [$startDate, $endDate])->sum('amount');
        
        // Total sales = Booking payments + Rental sales + Additional charges
        $totalSales = $bookingSales + $rentalSales + $additionalCharges;

        // Total bookings in period (unique bookings with payments)
        $totalBookings = \App\Models\Payment::whereHas('booking', function($query) {
            $query->where('BookingStatus', '!=', 'Cancelled');
        })
        ->whereBetween('PaymentDate', [$startDate, $endDate])
        ->distinct('BookingID')
        ->count('BookingID');

        // Daily sales breakdown
        $dailySales = \App\Models\Payment::whereBetween('PaymentDate', [$startDate, $endDate])
            ->where('PaymentStatus', '!=', 'Cancelled')
            ->selectRaw('DATE(PaymentDate) as date, SUM(Amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function($day) use ($rentalsInPeriod) {
                // Add rental sales for rentals issued on this day
                $rentalSalesForDay = 0;
                foreach($rentalsInPeriod as $rental) {
                    if ($rental->issued_at && $rental->issued_at->toDateString() === $day->date) {
                        if ($rental->rate_type_snapshot === 'Per-Day') {
                            $rentalEndDate = $rental->returned_at ?? \Carbon\Carbon::now();
                            $days = $rental->issued_at->diffInDays($rentalEndDate);
                            $days = max(1, $days);
                            $rentalSalesForDay += $rental->rate_snapshot * $days * $rental->quantity;
                        } else {
                            $rentalSalesForDay += $rental->rate_snapshot * $rental->quantity;
                        }
                        // Add rental fees
                        $rentalSalesForDay += $rental->fees()->sum('amount');
                    }
                }
                
                return [
                    'date' => \Carbon\Carbon::parse($day->date)->format('M d, Y'),
                    'total' => $day->total + $rentalSalesForDay,
                ];
            });

        // Package Sales - All packages with payments during the period
        $packageSales = \App\Models\Payment::with('booking.package')
            ->whereHas('booking', function($query) {
                $query->where('BookingStatus', '!=', 'Cancelled');
            })
            ->whereBetween('PaymentDate', [$startDate, $endDate])
            ->get()
            ->groupBy(function($payment) {
                return $payment->booking->PackageID;
            })
            ->map(function($payments) {
                $package = $payments->first()->booking->package;
                $bookingIds = $payments->pluck('BookingID')->unique();
                
                return [
                    'name' => $package->Name ?? 'Unknown',
                    'bookings' => $bookingIds->count(),
                    'sales' => $payments->sum('Amount'),
                ];
            })
            ->sortByDesc('sales')
            ->values();

        $packageSalesTotal = $packageSales->sum('sales');

        // Rental Item Sales - All rental items rented during the period
        $rentalItemSales = \App\Models\Rental::with(['rentalItem', 'fees'])
            ->whereBetween('issued_at', [$startDate, $endDate])
            ->get()
            ->groupBy('rental_item_id')
            ->map(function($rentals) {
                $item = $rentals->first()->rentalItem;
                $totalSales = 0;
                $timesRented = $rentals->sum('quantity');
                
                foreach($rentals as $rental) {
                    // Calculate rental charge from rate_snapshot
                    if ($rental->rate_type_snapshot === 'Per-Day') {
                        $rentalEndDate = $rental->returned_at ?? \Carbon\Carbon::now();
                        $days = $rental->issued_at->diffInDays($rentalEndDate);
                        $days = max(1, $days);
                        $totalSales += $rental->rate_snapshot * $days * $rental->quantity;
                    } else {
                        $totalSales += $rental->rate_snapshot * $rental->quantity;
                    }
                }
                
                return [
                    'name' => $item->name ?? 'Unknown',
                    'times_rented' => $timesRented,
                    'sales' => $totalSales,
                ];
            })
            ->sortByDesc('sales')
            ->values();

        $rentalItemSalesTotal = $rentalItemSales->sum('sales');

        // Rental Charges Summary
        $rentalCharges = [
            'damage_fees' => 0,
            'lost_fees' => 0,
            'total' => 0,
        ];
        
        foreach($rentalsInPeriod as $rental) {
            $rentalCharges['damage_fees'] += $rental->fees()->where('type', 'Damage')->sum('amount');
            $rentalCharges['lost_fees'] += $rental->fees()->where('type', 'Loss')->sum('amount');
        }
        $rentalCharges['total'] = $rentalCharges['damage_fees'] + $rentalCharges['lost_fees'];

        // Transaction history - use transactions table which properly splits bill-out payments
        // This matches the transactions-ledger display (separates booking and rental payments)
        $transactions = \App\Models\Transaction::with(['booking', 'guest', 'rental.rentalItem'])
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderBy('transaction_date', 'desc')
            ->get()
            ->map(function($txn) {
                // Determine booking/payment ID display
                $bookingPaymentId = 'N/A';
                if ($txn->booking_id) {
                    $bookingPaymentId = '#' . $txn->booking_id;
                }
                // Add payment reference if available in metadata
                if ($txn->metadata && isset($txn->metadata['payment_id'])) {
                    $bookingPaymentId .= ' (P#' . $txn->metadata['payment_id'] . ')';
                }
                
                return [
                    'date' => $txn->transaction_date,
                    'transaction_id' => $txn->transaction_id,
                    'booking_payment_id' => $bookingPaymentId,
                    'reference_number' => $txn->reference_number,
                    'source' => ucfirst($txn->transaction_type),
                    'guest_name' => $txn->customer_name ?? 'Unknown',
                    'purpose' => $txn->purpose,
                    'amount' => floatval($txn->amount),
                    'payment_method' => $txn->payment_method,
                    'is_voided' => $txn->is_voided,
                ];
            });

        $reportData = [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'days' => (int)$startDate->diffInDays($endDate) + 1,
                'formatted_range' => $startDate->format('M d, Y') . ' - ' . $endDate->format('M d, Y'),
            ],
            'totals' => [
                'sales' => $totalSales,
                'bookings' => $totalBookings,
            ],
            'daily_sales' => $dailySales,
            'package_sales' => $packageSales,
            'package_sales_total' => $packageSalesTotal,
            'rental_item_sales' => $rentalItemSales,
            'rental_item_sales_total' => $rentalItemSalesTotal,
            'rental_charges' => $rentalCharges,
            'transactions' => $transactions,
            'isPdf' => ($format === 'pdf'),
        ];

        // Audit log: generated custom date-range report
        try {
            Audit_Log::create([
                'user_id' => Auth::user()->user_id ?? null,
                'action' => 'Generate Custom Report',
                'description' => 'Generated custom date-range sales report for ' . ($startDate->toDateString() ?? 'n/a') . ' to ' . ($endDate->toDateString() ?? 'n/a') . ' format: ' . $format,
                'ip_address' => $request->ip(),
            ]);
        } catch (\Exception $e) {
            // ignore logging failures
        }

        if ($format === 'pdf') {
            return $this->generateCustomPDF($reportData);
        } elseif ($format === 'json') {
            return response()->json($reportData);
        } else {
            return $this->generateCustomCSV($reportData);
        }
    }

    /**
     * Generate PDF for Per Booking Report
     */
    private function generatePerBookingPDF($data)
    {
        $data['isPdf'] = true; // Flag to use correct CSS path in blade template
        $pdf = Pdf::loadView('admin.sales.pdf.per-booking', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('margin-top', 5)
            ->setOption('margin-bottom', 5)
            ->setOption('margin-left', 5)
            ->setOption('margin-right', 5);
        
        $fileName = 'per-booking-sales-report-BK' . $data['booking']['BookingID'] . '.pdf';
        return $pdf->download($fileName);
    }

    /**
     * Generate CSV for Per Booking Report
     */
    private function generatePerBookingCSV($data)
    {
        return response()->json($data);
    }

    /**
     * Generate PDF for Monthly Report
     */
    private function generateMonthlyPDF($data)
    {
        $data['isPdf'] = true; // Flag to use correct CSS path in blade template
        $pdf = Pdf::loadView('admin.sales.pdf.monthly', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('margin-top', 5)
            ->setOption('margin-bottom', 5)
            ->setOption('margin-left', 5)
            ->setOption('margin-right', 5);
        
        $fileName = 'monthly-sales-report-' . str_replace(' ', '-', $data['period']['month']) . '.pdf';
        return $pdf->download($fileName);
    }

    /**
     * Generate CSV for Monthly Report
     */
    private function generateMonthlyCSV($data)
    {
        return response()->json($data);
    }

    /**
     * Generate PDF for Annual Report
     */
    private function generateAnnualPDF($data)
    {
        $pdf = Pdf::loadView('admin.sales.pdf.annual', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('margin-top', 5)
            ->setOption('margin-bottom', 5)
            ->setOption('margin-left', 5)
            ->setOption('margin-right', 5);
        
        $fileName = 'annual-sales-report-' . $data['period']['year'] . '.pdf';
        return $pdf->download($fileName);
    }

    /**
     * Generate CSV for Annual Report
     */
    private function generateAnnualCSV($data)
    {
        return response()->json($data);
    }

    /**
     * Generate PDF for Custom Report
     */
    private function generateCustomPDF($data)
    {
        $data['isPdf'] = true; // Flag to use correct CSS path in blade template
        $pdf = Pdf::loadView('admin.sales.pdf.custom', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('margin-top', 5)
            ->setOption('margin-bottom', 5)
            ->setOption('margin-left', 5)
            ->setOption('margin-right', 5);
        
        $fileName = 'custom-sales-report-' . $data['period']['start_date'] . '-to-' . $data['period']['end_date'] . '.pdf';
        return $pdf->download($fileName);
    }

    /**
     * Generate CSV for Custom Report
     */
    private function generateCustomCSV($data)
    {
        return response()->json($data);
    }

    /**
     * Get dashboard data (KPIs and charts)
     */
    public function getDashboardData(Request $request)
    {
        $preset = $request->input('preset', 'month');
        
        // Set default dates based on preset if not provided
        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
        } else {
            // Default to current month
            switch($preset) {
                case 'year':
                    $startDate = Carbon::now()->startOfYear();
                    $endDate = Carbon::now()->endOfYear();
                    break;
                case 'week':
                    $startDate = Carbon::now()->startOfWeek();
                    $endDate = Carbon::now()->endOfWeek();
                    break;
                case 'month':
                default:
                    $startDate = Carbon::now()->startOfMonth();
                    $endDate = Carbon::now()->endOfMonth();
                    break;
            }
        }

        // Get booking sales (from payments)
        $bookingSales = Payment::whereBetween('PaymentDate', [$startDate, $endDate])
            ->whereIn('PaymentStatus', ['Fully Paid', 'Downpayment', 'Verified'])
            ->sum('Amount');

        // Get rental sales from unpaid items (legacy system)
        $unpaidItemSales = UnpaidItem::where('IsPaid', true)
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->sum('TotalAmount');

        // Get rental sales from rentals table (new system)
        $rentalSalesFromRentals = Rental::where('is_paid', true)
            ->whereBetween('returned_at', [$startDate, $endDate])
            ->get()
            ->sum(function($rental) {
                return $rental->calculateTotalCharges();
            });

        $rentalSales = $unpaidItemSales + $rentalSalesFromRentals;

        // Calculate previous period for comparison
        $previousPeriod = $this->getPreviousPeriod($startDate, $endDate, $preset);
        
        $previousBookingSales = Payment::whereBetween('PaymentDate', [$previousPeriod['start'], $previousPeriod['end']])
            ->whereIn('PaymentStatus', ['Fully Paid', 'Downpayment', 'Verified'])
            ->sum('Amount');
            
        $previousUnpaidItemSales = UnpaidItem::where('IsPaid', true)
            ->whereBetween('updated_at', [$previousPeriod['start'], $previousPeriod['end']])
            ->sum('TotalAmount');

        $previousRentalSalesFromRentals = Rental::where('is_paid', true)
            ->whereBetween('returned_at', [$previousPeriod['start'], $previousPeriod['end']])
            ->get()
            ->sum(function($rental) {
                return $rental->calculateTotalCharges();
            });

        $previousRentalSales = $previousUnpaidItemSales + $previousRentalSalesFromRentals;

        $totalSales = $bookingSales + $rentalSales;
        $previousTotalSales = $previousBookingSales + $previousRentalSales;
        
        $salesDifference = $totalSales - $previousTotalSales;
        $growthRate = $previousTotalSales > 0 
            ? (($totalSales - $previousTotalSales) / $previousTotalSales) * 100 
            : 0;

        // Get revenue trend data
        $revenueTrend = $this->getRevenueTrend($startDate, $endDate, $preset);

        // Get revenue by source
        $revenueBySource = [
            'booking' => $bookingSales,
            'rental' => $rentalSales
        ];

        // Get payment methods breakdown
        $paymentMethods = Payment::whereBetween('PaymentDate', [$startDate, $endDate])
            ->whereIn('PaymentStatus', ['Fully Paid', 'Downpayment', 'Verified'])
            ->select('PaymentMethod', DB::raw('SUM(Amount) as total'))
            ->groupBy('PaymentMethod')
            ->get()
            ->pluck('total', 'PaymentMethod');

        // Get top packages
        $topPackages = Payment::join('bookings', 'payments.BookingID', '=', 'bookings.BookingID')
            ->join('packages', 'bookings.PackageID', '=', 'packages.PackageID')
            ->whereBetween('payments.PaymentDate', [$startDate, $endDate])
            ->whereIn('payments.PaymentStatus', ['Fully Paid', 'Downpayment', 'Verified'])
            ->select('packages.Name as PackageName', DB::raw('SUM(payments.Amount) as revenue'), DB::raw('COUNT(DISTINCT bookings.BookingID) as bookings'))
            ->groupBy('packages.PackageID', 'packages.Name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        // Get top rental items from unpaid_items
        $topUnpaidItems = UnpaidItem::where('IsPaid', true)
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->select('ItemName', DB::raw('SUM(TotalAmount) as revenue'), DB::raw('SUM(Quantity) as quantity'))
            ->groupBy('ItemName')
            ->orderByDesc('revenue')
            ->get();

        // Get top rental items from rentals table
        $topRentalsData = Rental::with('rentalItem')
            ->where('is_paid', true)
            ->whereBetween('returned_at', [$startDate, $endDate])
            ->get()
            ->groupBy('rental_item_id')
            ->map(function($group) {
                $rentalItem = $group->first()->rentalItem;
                return [
                    'ItemName' => $rentalItem ? $rentalItem->name : 'Unknown Item',
                    'revenue' => $group->sum(function($rental) {
                        return $rental->calculateTotalCharges();
                    }),
                    'quantity' => $group->sum('quantity')
                ];
            })
            ->values();

        // Merge and sort both sources
        $topRentals = collect($topUnpaidItems)
            ->concat($topRentalsData)
            ->groupBy('ItemName')
            ->map(function($group, $name) {
                return [
                    'ItemName' => $name,
                    'revenue' => $group->sum('revenue'),
                    'quantity' => $group->sum('quantity')
                ];
            })
            ->sortByDesc('revenue')
            ->take(5)
            ->values();

        $responseData = [
            'kpis' => [
                'booking_sales' => number_format($bookingSales, 2, '.', ''),
                'rental_sales' => number_format($rentalSales, 2, '.', ''),
                'sales_difference' => number_format($salesDifference, 2, '.', ''),
                'growth_rate' => number_format($growthRate, 2, '.', ''),
                'previous_booking_sales' => number_format($previousBookingSales, 2, '.', ''),
                'previous_rental_sales' => number_format($previousRentalSales, 2, '.', ''),
            ],
            'revenue_trend' => $revenueTrend,
            'revenue_by_source' => $revenueBySource,
            'payment_methods' => $paymentMethods,
            'top_packages' => $topPackages,
            'top_rentals' => $topRentals,
        ];

        Log::info('Dashboard Data Response', [
            'preset' => $preset,
            'date_range' => [$startDate->toDateString(), $endDate->toDateString()],
            'booking_sales' => $bookingSales,
            'rental_sales' => $rentalSales,
            'response' => $responseData
        ]);

        // Audit log: viewed sales dashboard data
        try {
            Audit_Log::create([
                'user_id' => Auth::user()->user_id ?? null,
                'action' => 'View Dashboard Data',
                'description' => 'Viewed sales dashboard data preset: ' . $preset . ' range: ' . $startDate->toDateString() . ' - ' . $endDate->toDateString(),
                'ip_address' => $request->ip(),
            ]);
        } catch (\Exception $e) {
            // ignore logging failures
        }

        return response()->json($responseData);
    }

    /**
     * Get transactions for ledger (using dedicated transactions table)
     */
    public function getTransactions(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $source = $request->input('source');
        $method = $request->input('method');
        $status = $request->input('status');
        $search = $request->input('search');

        // Query transactions table (include voided transactions)
        $query = \App\Models\Transaction::query();

        // Apply filters
        if ($startDate && $endDate) {
            $query->byDateRange(Carbon::parse($startDate), Carbon::parse($endDate));
        }

        if ($source) {
            $query->byType($source);
        }

        if ($method) {
            $query->where('payment_method', $method);
        }

        if ($status) {
            $statusMap = [
                'downpayment' => 'Downpayment',
                'partial' => 'Partial Payment',
                'completed' => 'Fully Paid'
            ];
            if (isset($statusMap[$status])) {
                $query->where('payment_status', $statusMap[$status]);
            }
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%")
                  ->orWhere('purpose', 'like', "%{$search}%");
            });
        }

        // Get transactions with guest info (for displaying full names with middle names)
    $transactions = $query->with(['guest', 'voidedBy', 'processedBy'])->orderBy('transaction_date', 'desc')->get()->map(function($txn) {
            // Get voided by user name if transaction is voided
            $voidedByName = null;
            if ($txn->is_voided) {
                $voidedByName = $txn->voidedBy?->name ?? ($txn->voided_by ? \App\Models\User::where('user_id', $txn->voided_by)->value('name') : null);
            }
            
            // Build customer name from guest if available, otherwise use transaction customer_name
            $customerName = $txn->customer_name;
            if ($txn->guest_id && $txn->guest) {
                $customerName = trim(($txn->guest->FName ?? '') . ' ' . ($txn->guest->MName ?? '') . ' ' . ($txn->guest->LName ?? ''));
            }
            
            return [
                'id' => $txn->reference_id,
                'source' => $txn->transaction_type,
                'date' => $txn->transaction_date,
                'purpose' => $txn->purpose,
                'amount' => floatval($txn->amount),
                'method' => $txn->payment_method,
                'processed_by' => $txn->processor_name ?? 'System',
                'customer_name' => $customerName,
                'customer_email' => $txn->customer_email,
                'customer_phone' => $txn->customer_phone,
                'amount_received' => $txn->amount_received !== null ? floatval($txn->amount_received) : null,
                'change_amount' => $txn->change_amount !== null ? floatval($txn->change_amount) : null,
                'metadata' => $txn->metadata,
                'reference_number' => $txn->reference_number,
                'notes' => $txn->notes,
                'is_voided' => $txn->is_voided,
                'voided_at' => $txn->voided_at,
                'voided_by_name' => $voidedByName,
                'void_reason' => $txn->void_reason,
            ];
        });

        // Calculate summary
        $summary = [
            'total_transactions' => $transactions->count(),
            'total_amount' => $transactions->sum('amount'),
            'booking_count' => $transactions->where('source', 'booking')->count(),
            'rental_count' => $transactions->whereIn('source', ['rental', 'add-on'])->count(),
        ];

        $responseData = [
            'transactions' => $transactions,
            'summary' => $summary,
        ];

        Log::info('Transactions Response (New Table)', [
            'filters' => compact('startDate', 'endDate', 'source', 'method', 'status', 'search'),
            'count' => $transactions->count(),
            'summary' => $summary
        ]);

        // Audit log: fetched transactions for ledger
        try {
            Audit_Log::create([
                'user_id' => Auth::user()->user_id ?? null,
                'action' => 'View Transactions',
                'description' => 'Fetched transactions ledger with filters: ' . json_encode(compact('startDate','endDate','source','method','status','search')),
                'ip_address' => request()->ip(),
            ]);
        } catch (\Exception $e) {
            // ignore
        }

        return response()->json($responseData);
    }

    /**
     * Get a single transaction by ID
     */
    public function getTransactionById($id)
    {
        try {
            // Find the transaction by reference_id or transaction_id
            $transaction = \App\Models\Transaction::where('reference_id', $id)
                ->orWhere('transaction_id', $id)
                ->first();

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction not found'
                ], 404);
            }

            // Get voided by user name if transaction is voided
            $voidedByName = null;
            if ($transaction->is_voided) {
                $voidedByName = $transaction->voidedBy?->name ?? ($transaction->voided_by ? \App\Models\User::where('user_id', $transaction->voided_by)->value('name') : null);
                if ($voidedByName === null && $transaction->voided_by) {
                    $voidedByName = 'Unknown';
                }
            }

            return response()->json([
                'success' => true,
                'transaction' => [
                    'id' => $transaction->reference_id,
                    'transaction_id' => $transaction->transaction_id,
                    'source' => $transaction->transaction_type,
                    'date' => $transaction->transaction_date,
                    'purpose' => $transaction->purpose,
                    'amount' => floatval($transaction->amount),
                    'method' => $transaction->payment_method,
                    'processed_by' => $transaction->processor_name ?? 'System',
                    'customer_name' => $transaction->customer_name,
                    'customer_email' => $transaction->customer_email,
                    'customer_phone' => $transaction->customer_phone,
                    'amount_received' => $transaction->amount_received !== null ? floatval($transaction->amount_received) : null,
                    'change_amount' => $transaction->change_amount !== null ? floatval($transaction->change_amount) : null,
                    'metadata' => $transaction->metadata,
                    'reference_number' => $transaction->reference_number,
                    'notes' => $transaction->notes,
                    'is_voided' => $transaction->is_voided,
                    'voided_at' => $transaction->voided_at,
                    'voided_by_name' => $voidedByName,
                    'void_reason' => $transaction->void_reason,
                    'booking_id' => $transaction->booking_id,
                    'guest_id' => $transaction->guest_id,
                    'rental_id' => $transaction->rental_id,
                ]
            ]);
            // Audit log: fetched single transaction
            try {
                Audit_Log::create([
                    'user_id' => Auth::user()->user_id ?? null,
                    'action' => 'View Transaction',
                    'description' => 'Fetched transaction ' . ($transaction->transaction_id ?? $transaction->reference_id ?? 'n/a'),
                    'ip_address' => request()->ip(),
                ]);
            } catch (\Exception $e) {
                // ignore
            }
        } catch (\Exception $e) {
            Log::error('Error fetching transaction: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch transaction details'
            ], 500);
        }
    }

    /**
     * Void a transaction
     * This marks the transaction as voided and reverses all related effects
     * 
     * RULES:
     * 1. Can only void transactions if guest has NOT checked out (status != 'Completed')
     * 2. Bill Out Settlement transactions MUST be voided as a complete set (cannot void partial)
     * 3. Only admins can void transactions
     */
    public function voidTransaction(Request $request, $referenceId)
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
            'admin_username' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            // Get the authenticated admin user
            $adminUser = Auth::user();
            
            // Verify user is an admin
            if (!$adminUser || !in_array($adminUser->role, ['admin', 'staff', 'owner'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Only admins can void transactions.'
                ], 403);
            }
            
            // Verify the entered username matches a valid admin (security confirmation)
            $enteredUsername = $validated['admin_username'];
            $verifyUser = \App\Models\User::where(function($query) use ($enteredUsername) {
                $query->where('user_id', $enteredUsername)
                      ->orWhere('name', 'LIKE', "%{$enteredUsername}%")
                      ->orWhere('email', $enteredUsername);
            })
            ->whereIn('role', ['admin', 'staff', 'owner'])
            ->first();
            
            if (!$verifyUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid admin credentials. Please enter a valid admin user ID, name, or email.'
                ], 403);
            }

            // Get all transactions with this reference_id (for bill-out splits)
            $transactions = \App\Models\Transaction::where('reference_id', $referenceId)
                ->where('is_voided', false)
                ->get();

            if ($transactions->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction not found or already voided'
                ], 404);
            }
            
            // VALIDATION: Check if guest has already checked out
            $firstTransaction = $transactions->first();
            if ($firstTransaction->booking_id) {
                $booking = \App\Models\Booking::where('BookingID', $firstTransaction->booking_id)->first();
                
                if ($booking && $booking->BookingStatus === 'Completed') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot void transactions for completed bookings. Guest has already checked out.',
                        'details' => 'Booking Status: Completed'
                    ], 400);
                }
            }
            
            // VALIDATION: Check if this is a Bill Out Settlement - must void ALL or NONE
            $isBillOutSettlement = $transactions->first()->purpose === 'Bill Out Settlement' || 
                                   str_starts_with($transactions->first()->purpose, 'Bill Out Settlement -');
            
            if ($isBillOutSettlement) {
                // Get ALL transactions for this reference (including already voided ones)
                $allRelatedTransactions = \App\Models\Transaction::where('reference_id', $referenceId)->get();
                $voidedCount = $allRelatedTransactions->where('is_voided', true)->count();
                
                // If some are already voided but not all, prevent partial voiding
                if ($voidedCount > 0 && $voidedCount < $allRelatedTransactions->count()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot partially void Bill Out Settlement transactions.',
                        'details' => 'Bill Out Settlement must be voided as a complete set. Some transactions are already voided.',
                        'voided_count' => $voidedCount,
                        'total_count' => $allRelatedTransactions->count()
                    ], 400);
                }
                
                // Show warning about voiding complete Bill Out Settlement
                $transactionCount = $transactions->count();
                $totalAmount = $transactions->sum('amount');
            }

            // Get payment and booking info for proper reversal
            $payment = \App\Models\Payment::where('PaymentID', $referenceId)->first();
            $bookingId = $payment ? $payment->BookingID : $firstTransaction->booking_id;
            
            // Void all transactions as a set
            foreach ($transactions as $transaction) {
                // Mark transaction as voided
                $transaction->update([
                    'is_voided' => true,
                    'voided_at' => now(),
                        'voided_by' => $adminUser->user_id,
                    'void_reason' => $validated['reason'],
                ]);
            }
            
            // Mark payment as voided (preserve payment history instead of deleting)
            if ($payment) {
                $payment->update([
                    'is_voided' => true,
                    'voided_at' => now(),
                    'voided_by' => $adminUser->user_id,
                    'void_reason' => $validated['reason'],
                ]);
            }
            
            // If this was a Bill Out Settlement, mark ALL rentals and unpaid items as unpaid
            if ($isBillOutSettlement && $bookingId) {
                // Mark all rentals for this booking as unpaid
                \App\Models\Rental::where('BookingID', $bookingId)
                    ->whereIn('status', ['Issued', 'Returned', 'Lost', 'Damaged'])
                    ->update(['is_paid' => false]);
                
                // Mark all unpaid items as unpaid again
                \App\Models\UnpaidItem::where('BookingID', $bookingId)
                    ->update(['IsPaid' => false]);
                
                // IMPORTANT: Clear senior discount from booking
                // Senior discount should only be applied during bill-out, not stored permanently
                $booking = \App\Models\Booking::where('BookingID', $bookingId)->first();
                if ($booking) {
                    $booking->update([
                        'senior_discount' => 0,
                        'actual_seniors_at_checkout' => 0
                    ]);
                }
            }
            
            // Recalculate booking payment status if booking exists
            if ($bookingId) {
                $this->recalculateBookingPaymentStatus($bookingId);
            }

            DB::commit();
            
            // Audit log: voided transaction(s)
            try {
                Audit_Log::create([
                    'user_id' => $adminUser->user_id ?? Auth::user()->user_id ?? null,
                    'action' => 'Void Transaction',
                    'description' => 'Voided transaction reference ' . ($referenceId ?? 'n/a') . ' by admin ' . ($adminUser->user_id ?? 'n/a') . ' reason: ' . ($validated['reason'] ?? 'n/a'),
                    'ip_address' => $request->ip(),
                ]);
            } catch (\Exception $e) {
                // ignore logging failures
            }

            // Build success message
            $message = 'Transaction(s) voided successfully';
            if ($isBillOutSettlement) {
                $transactionCount = $transactions->count();
                $totalAmount = $transactions->sum('amount');
                $message = "Bill Out Settlement voided successfully. {$transactionCount} transaction(s) totaling ₱" . number_format($totalAmount, 2) . " have been reversed. Guest can now proceed with a new bill-out payment.";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'details' => [
                    'voided_count' => $transactions->count(),
                    'total_amount' => $transactions->sum('amount'),
                    'booking_id' => $bookingId,
                    'rentals_marked_unpaid' => $isBillOutSettlement,
                    'can_redo_payment' => true
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error voiding transaction', [
                'reference_id' => $referenceId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error voiding transaction: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recalculate booking payment status after payment deletion
     */
    private function recalculateBookingPaymentStatus($bookingId)
    {
        $booking = \App\Models\Booking::with(['package', 'payments'])->find($bookingId);
        if (!$booking) return;

        // Calculate total booking cost
        $checkIn = \Carbon\Carbon::parse($booking->CheckInDate);
        $checkOut = \Carbon\Carbon::parse($booking->CheckOutDate);
        $days = $checkIn->diffInDays($checkOut);
        $packageTotal = ($booking->package->Price ?? 0) * $days;
        $excessFee = $booking->ExcessFee ?? 0;
        $seniorDiscount = $booking->senior_discount ?? 0;
        $totalAmount = $packageTotal + $excessFee - $seniorDiscount;

        // Calculate total paid from remaining payments
        $totalPaid = $booking->payments->sum('Amount');

        // Update booking status if needed
        // The payment status will be automatically recalculated on next load
        // via the BookingController getData() method logic
    }



    /**
     * Get previous period dates for comparison
     */
    private function getPreviousPeriod($startDate, $endDate, $preset)
    {
        $diffInDays = $startDate->diffInDays($endDate);
        
        return [
            'start' => $startDate->copy()->subDays($diffInDays + 1),
            'end' => $startDate->copy()->subDay(),
        ];
    }

    /**
     * Get revenue trend data based on period
     */
    private function getRevenueTrend($startDate, $endDate, $preset)
    {
        $data = [];
        
        if ($preset === 'year') {
            // Monthly data for the year
            for ($i = 0; $i < 12; $i++) {
                $monthStart = $startDate->copy()->addMonths($i)->startOfMonth();
                $monthEnd = $monthStart->copy()->endOfMonth();
                
                if ($monthEnd->gt($endDate)) {
                    $monthEnd = $endDate;
                }
                
                $bookingSales = Payment::whereBetween('PaymentDate', [$monthStart, $monthEnd])
                    ->whereIn('PaymentStatus', ['Fully Paid', 'Downpayment', 'Verified'])
                    ->sum('Amount');
                    
                $unpaidItemSales = UnpaidItem::where('IsPaid', true)
                    ->whereBetween('updated_at', [$monthStart, $monthEnd])
                    ->sum('TotalAmount');

                $rentalSalesFromRentals = Rental::where('is_paid', true)
                    ->whereBetween('returned_at', [$monthStart, $monthEnd])
                    ->get()
                    ->sum(function($rental) {
                        return $rental->calculateTotalCharges();
                    });

                $rentalSales = $unpaidItemSales + $rentalSalesFromRentals;
                
                $data[] = [
                    'period' => $monthStart->format('M'),
                    'booking' => $bookingSales,
                    'rental' => $rentalSales,
                    'total' => $bookingSales + $rentalSales
                ];
            }
        } elseif ($preset === 'month') {
            // Weekly data for the month
            $currentDate = $startDate->copy();
            $weekNum = 1;
            
            while ($currentDate->lte($endDate)) {
                $weekEnd = $currentDate->copy()->addDays(6);
                if ($weekEnd->gt($endDate)) {
                    $weekEnd = $endDate;
                }
                
                $bookingSales = Payment::whereBetween('PaymentDate', [$currentDate, $weekEnd])
                    ->whereIn('PaymentStatus', ['Fully Paid', 'Downpayment', 'Verified'])
                    ->sum('Amount');
                    
                $unpaidItemSales = UnpaidItem::where('IsPaid', true)
                    ->whereBetween('updated_at', [$currentDate, $weekEnd])
                    ->sum('TotalAmount');

                $rentalSalesFromRentals = Rental::where('is_paid', true)
                    ->whereBetween('returned_at', [$currentDate, $weekEnd])
                    ->get()
                    ->sum(function($rental) {
                        return $rental->calculateTotalCharges();
                    });

                $rentalSales = $unpaidItemSales + $rentalSalesFromRentals;
                
                $data[] = [
                    'period' => 'Week ' . $weekNum,
                    'booking' => $bookingSales,
                    'rental' => $rentalSales,
                    'total' => $bookingSales + $rentalSales
                ];
                
                $currentDate->addDays(7);
                $weekNum++;
            }
        } else {
            // Daily data for week
            $currentDate = $startDate->copy();
            
            while ($currentDate->lte($endDate)) {
                $bookingSales = Payment::whereDate('PaymentDate', $currentDate)
                    ->whereIn('PaymentStatus', ['Fully Paid', 'Downpayment', 'Verified'])
                    ->sum('Amount');
                    
                $unpaidItemSales = UnpaidItem::where('IsPaid', true)
                    ->whereDate('updated_at', $currentDate)
                    ->sum('TotalAmount');

                $rentalSalesFromRentals = Rental::where('is_paid', true)
                    ->whereDate('returned_at', $currentDate)
                    ->get()
                    ->sum(function($rental) {
                        return $rental->calculateTotalCharges();
                    });

                $rentalSales = $unpaidItemSales + $rentalSalesFromRentals;
                
                $data[] = [
                    'period' => $currentDate->format('D'),
                    'booking' => $bookingSales,
                    'rental' => $rentalSales,
                    'total' => $bookingSales + $rentalSales
                ];
                
                $currentDate->addDay();
            }
        }
        
        return $data;
    }
}
