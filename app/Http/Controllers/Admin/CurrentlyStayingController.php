<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\AccompanyingGuest;
use App\Mail\NoShowNotification;
use App\Mail\UndoNoShowNotification;
use App\Models\Audit_Log;

class CurrentlyStayingController extends Controller
{
    /**
     * Display the currently staying guests page.
     */
    public function index()
    {
        // Get currently staying guests (include Pending/Booked/Confirmed/Staying/Cancelled and within check-in/check-out dates)
        // Include Cancelled so "No Show" guests remain visible in the table until checked out
        $currentGuests = Booking::with(['guest', 'package', 'payments', 'unpaidItems'])
            ->whereIn('BookingStatus', ['Pending', 'Booked', 'Confirmed', 'Staying', 'Cancelled'])
            ->whereDate('CheckInDate', '<=', today())
            ->whereDate('CheckOutDate', '>=', today())
            ->orderBy('CheckInDate', 'desc')
            ->get();

        return view('admin.bookings.currently-staying', compact('currentGuests'));
    }

    /**
     * Get guest details by booking ID (AJAX endpoint)
     */
    public function getGuestDetails(Request $request)
    {
        $bookingId = $request->input('booking_id');

        $booking = Booking::with(['guest', 'package', 'payments', 'unpaidItems', 'rentals.fees'])
            ->where('BookingID', $bookingId)
            ->first();

        if ($booking) {
            // Calculate days
            $checkIn = \Carbon\Carbon::parse($booking->CheckInDate);
            $checkOut = \Carbon\Carbon::parse($booking->CheckOutDate);
            $days = $checkIn->diffInDays($checkOut);
            
            // Calculate payment details
            $packagePrice = $booking->package->Price ?? 0;
            $totalAmount = $packagePrice * $days;
            
            // Add excess fee if any
            $excessFee = $booking->ExcessFee ?? 0;
            $totalAmount += $excessFee;
            
            // NOTE: Senior discount is NOT applied here
            // It should only be applied during the bill-out process when admin confirms actual seniors present
            // This matches the Bill Out page calculation which also doesn't pre-apply the discount
            
            // Calculate total paid from all payments
            $amountPaid = $booking->payments->sum('Amount');
            $remainingBalance = $totalAmount - $amountPaid;
            
            // Get unpaid items
            $unpaidItems = $booking->unpaidItems ? $booking->unpaidItems->where('IsPaid', false) : collect([]);
            $unpaidItemsTotal = $unpaidItems->sum('TotalAmount');
            
            // Get unpaid rental charges for issued/returned/damaged/lost rentals
            $activeRentals = $booking->rentals()
                ->whereIn('status', ['Issued', 'Returned', 'Lost', 'Damaged'])
                ->where('is_paid', false)
                ->with(['rentalItem', 'fees'])
                ->get();
            $rentalCharges = $activeRentals->sum(function($rental) {
                return $rental->calculateTotalCharges();
            });
            
            // Calculate total outstanding (booking balance + unpaid items + unpaid rental charges)
            $totalOutstanding = $remainingBalance + $unpaidItemsTotal + $rentalCharges;
            
            // Check if there are unreturned rentals
            $hasUnreturnedRentals = $booking->rentals()->where('status', 'Issued')->exists();
            
            // Get payment methods
            $paymentMethods = $booking->payments->pluck('PaymentMethod')->unique()->join(', ');
            if (empty($paymentMethods)) {
                $paymentMethods = 'N/A';
            }
            
            // Determine payment status
            $halfAmount = $totalAmount * 0.5;
            $paymentStatus = 'Downpayment';
            
            if ($amountPaid >= $totalAmount) {
                $paymentStatus = 'Fully Paid';
            }
            
            // Calculate excess guests
            $maxGuests = $booking->package->max_guests ?? 30;
            $excessGuests = max(0, $booking->NumOfAdults - $maxGuests);

            return response()->json([
                'success' => true,
                'booking' => [
                    'BookingID' => $booking->BookingID,
                    'BookingDate' => $booking->BookingDate ? \Carbon\Carbon::parse($booking->BookingDate)->format('F d, Y') : 'N/A',
                    'CheckInDate' => $booking->CheckInDate->format('F d, Y'),
                    'CheckInTime' => $booking->CheckInDate->format('g:i A'),
                    'CheckOutDate' => $booking->CheckOutDate->format('F d, Y'),
                    'CheckOutTime' => $booking->CheckOutDate->format('g:i A'),
                    'ActualCheckInTime' => $booking->ActualCheckInTime ? $booking->ActualCheckInTime->toIso8601String() : null,
                    'ActualCheckOutTime' => $booking->ActualCheckOutTime ? $booking->ActualCheckOutTime->toIso8601String() : null,
                    'NumOfAdults' => $booking->NumOfAdults,
                    'NumOfSeniors' => $booking->NumOfSeniors,
                    'NumOfChild' => $booking->NumOfChild,
                    'Pax' => $booking->Pax ?? ($booking->NumOfAdults + $booking->NumOfChild),
                    'BookingStatus' => $booking->BookingStatus,
                    'Days' => $days,
                    'ExcessGuests' => $excessGuests,
                    'ExcessFee' => $excessFee,
                ],
                'guest' => [
                    'FName' => $booking->guest->FName ?? '',
                    'LName' => $booking->guest->LName ?? '',
                    'Email' => $booking->guest->Email ?? 'N/A',
                    'Phone' => $booking->guest->Phone ?? 'N/A',
                    'Address' => $booking->guest->Address ?? 'N/A',
                ],
                'package' => [
                    'Name' => $booking->package->Name ?? 'N/A',
                    'Price' => $booking->package->Price ?? 0,
                    'MaxGuests' => $maxGuests,
                ],
                'payment' => [
                    'PaymentStatus' => $paymentStatus,
                    'PaymentMethods' => $paymentMethods,
                    'TotalAmount' => $totalAmount,
                    'AmountPaid' => $amountPaid,
                    'RemainingBalance' => $remainingBalance,
                    'RentalCharges' => $rentalCharges,
                    'UnpaidItemsTotal' => $unpaidItemsTotal,
                    'TotalOutstanding' => $totalOutstanding,
                    'HasUnreturnedRentals' => $hasUnreturnedRentals,
                ],
                'payments' => $booking->payments->map(function ($payment) {
                    return [
                        'PaymentID' => $payment->PaymentID,
                        'Amount' => $payment->Amount,
                        'PaymentMethod' => $payment->PaymentMethod,
                        'PaymentStatus' => $payment->PaymentStatus,
                        'PaymentDate' => \Carbon\Carbon::parse($payment->PaymentDate)->format('F d, Y'),
                        'PaymentPurpose' => $payment->PaymentPurpose ?? 'Booking Payment',
                        'ReferenceNumber' => $payment->ReferenceNumber ?? 'N/A',
                    ];
                }),
                'unpaidItems' => $unpaidItems->map(function ($item) {
                    return [
                        'ItemID' => $item->ItemID,
                        'ItemName' => $item->ItemName,
                        'Quantity' => $item->Quantity,
                        'Price' => $item->Price,
                        'TotalAmount' => $item->TotalAmount,
                    ];
                }),
                'rentals' => $activeRentals->map(function ($rental) {
                    $rentalFee = 0;
                    if ($rental->rate_type_snapshot === 'Per-Day') {
                        $endDate = $rental->returned_at ?? \Carbon\Carbon::now();
                        $days = $rental->issued_at->diffInDays($endDate);
                        $days = max(1, $days);
                        $rentalFee = $rental->rate_snapshot * $days * $rental->quantity;
                    } else {
                        $rentalFee = $rental->rate_snapshot * $rental->quantity;
                    }
                    $additionalFees = $rental->fees()->sum('amount');
                    
                    return [
                        'id' => $rental->id,
                        'ItemName' => $rental->rentalItem->name ?? 'N/A',
                        'Quantity' => $rental->quantity,
                        'Status' => $rental->status,
                        'RentalFee' => $rentalFee,
                        'AdditionalFees' => $additionalFees,
                        'TotalCharges' => $rental->calculateTotalCharges(),
                        'IssuedAt' => $rental->issued_at->format('M d, Y'),
                    ];
                })
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No booking found'
        ]);
    }
    public function updateStatus(Request $request, $bookingId)
    {
        $booking = Booking::findOrFail($bookingId);
        $status = $request->input('status');
        
        // For no-show/cancel and undo actions, validate admin password
        if (in_array($status, ['no-show', 'undo-cancel'])) {
            $userId = $request->input('user_id');
            // Trim whitespace to avoid mismatches from user input
            $userId = is_string($userId) ? trim($userId) : $userId;

            if (empty($userId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin ID is required for this action'
                ], 400);
            }

            // Lookup user case-insensitively (user_id like A001)
            $user = \App\Models\User::whereRaw('LOWER(user_id) = ?', [strtolower($userId)])->first();

            // Accept both admin and staff roles for this action
            $allowedRoles = [\App\Enums\Role::Admin->value, \App\Enums\Role::Staff->value];
            $userRole = strtolower($user->role ?? '');

            if (!$user || !in_array($userRole, $allowedRoles)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], 403);
            }
        }

        switch ($status) {
            case 'Staying':
                $booking->BookingStatus = 'Staying';
                // Capture actual check-in time if not already set
                if (is_null($booking->ActualCheckInTime)) {
                    $booking->ActualCheckInTime = now();
                }
                $message = 'Guest checked in successfully';
                break;
                
            case 'Completed':
                // Calculate remaining balance including unpaid items and rental charges
                $checkIn = \Carbon\Carbon::parse($booking->CheckInDate);
                $checkOut = \Carbon\Carbon::parse($booking->CheckOutDate);
                $days = $checkIn->diffInDays($checkOut);
                
                $packagePrice = $booking->package->Price ?? 0;
                $totalAmount = $packagePrice * $days;
                $excessFee = $booking->ExcessFee ?? 0;
                $totalAmount += $excessFee;
                
                // NOTE: Senior discount is NOT applied here during checkout validation
                // The discount should have already been applied during bill-out settlement
                // This prevents double-application and ensures consistency with bill-out calculation
                
                // Add unpaid items to total
                $unpaidItemsTotal = $booking->unpaidItems()->where('IsPaid', false)->sum('TotalAmount');
                $totalAmount += $unpaidItemsTotal;
                
                // Add unpaid rental charges
                $rentalCharges = $booking->rentals()
                    ->whereIn('status', ['Issued', 'Returned', 'Lost', 'Damaged'])
                    ->where('is_paid', false)
                    ->get()
                    ->sum(function($rental) {
                        return $rental->calculateTotalCharges();
                    });
                $totalAmount += $rentalCharges;
                
                $amountPaid = $booking->payments->sum('Amount');
                $remainingBalance = $totalAmount - $amountPaid;
                
                // Allow small rounding difference (within 1 peso)
                if ($remainingBalance > 1) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot check out. Guest still has a remaining balance of ₱' . number_format($remainingBalance, 2)
                    ], 400);
                }
                
                $booking->BookingStatus = 'Completed';
                // Capture actual check-out time if not already set
                if (is_null($booking->ActualCheckOutTime)) {
                    $booking->ActualCheckOutTime = now();
                }
                $message = 'Guest checked out successfully';
                break;
                
            case 'Cancelled':
                $booking->BookingStatus = 'Cancelled';
                $message = 'Booking cancelled (No Show)';
                
                // Send No Show email notification
                try {
                    if ($booking->guest && $booking->guest->Email) {
                        Mail::to($booking->guest->Email)->send(new NoShowNotification($booking));
                    }
                } catch (\Exception $e) {
                    // Log the error but don't fail the status update
                    Log::error('Failed to send No Show email: ' . $e->getMessage());
                }
                break;
                
            case 'Confirmed':
                // Restore booking to Confirmed status
                $booking->BookingStatus = 'Confirmed';
                $message = 'Booking cancellation undone successfully';
                
                // Send Undo No Show email notification
                try {
                    if ($booking->guest && $booking->guest->Email) {
                        Mail::to($booking->guest->Email)->send(new UndoNoShowNotification($booking));
                    }
                } catch (\Exception $e) {
                    // Log the error but don't fail the status update
                    Log::error('Failed to send Undo No Show email: ' . $e->getMessage());
                }
                break;
                
            default:
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid status'
                ], 400);
        }

        $booking->save();

        // Audit log: currently staying booking status changed
        try {
            Audit_Log::create([
                'user_id' => \Illuminate\Support\Facades\Auth::user()->user_id ?? null,
                'action' => 'Update Stay Status',
                'description' => 'Changed status of ' . ($booking->BookingID ?? 'n/a') . ' to ' . ($status ?? 'n/a'),
                'ip_address' => request()->ip(),
            ]);
        } catch (\Exception $e) {
            // ignore
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'booking' => $booking->load(['guest', 'package', 'payments'])
        ]);
    }

    /**
     * Search and filter guests
     */
    public function search(Request $request)
    {
        $search = $request->input('search');
        $statusFilter = $request->input('status_filter');
        $roomFilter = $request->input('room_filter');

        // Base: same date window and allowed statuses as index()
        $query = Booking::with('guest')
            ->whereIn('BookingStatus', ['Pending', 'Booked', 'Confirmed', 'Staying'])
            ->whereDate('CheckInDate', '<=', today())
            ->whereDate('CheckOutDate', '>=', today());

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('BookingID', 'like', '%' . $search . '%')
                  ->orWhereHas('guest', function($guestQuery) use ($search) {
                      $guestQuery->where('FName', 'like', '%' . $search . '%')
                                 ->orWhere('LName', 'like', '%' . $search . '%');
                  });
            });
        }

        if ($statusFilter) {
            switch ($statusFilter) {
                case 'checked-in':
                    $query->where('BookingStatus', 'Confirmed');
                    break;
                case 'checked-out':
                    $query->where('BookingStatus', 'Completed');
                    break;
            }
        }

        if ($roomFilter) {
            // Since we don't have RoomNumber in the database, 
            // we'll skip this filter for now
            // $query->where('RoomNumber', $roomFilter);
        }

        $guests = $query->orderBy('CheckInDate', 'desc')->paginate(10);

        return response()->json([
            'success' => true,
            'guests' => $guests
        ]);
    }

    /**
     * Add unpaid item to booking
     */
    public function addUnpaidItem(Request $request)
    {
        $validated = $request->validate([
            'booking_id' => 'required|string|exists:bookings,BookingID',
            'item_name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
        ]);

        $unpaidItem = \App\Models\UnpaidItem::create([
            'BookingID' => $validated['booking_id'],
            'ItemName' => $validated['item_name'],
            'Quantity' => $validated['quantity'],
            'Price' => $validated['price'],
        ]);

        // Audit log: unpaid item added
        try {
            Audit_Log::create([
                'user_id' => \Illuminate\Support\Facades\Auth::user()->user_id ?? null,
                'action' => 'Add Unpaid Item',
                'description' => 'Added unpaid item ' . ($unpaidItem->ItemID ?? 'n/a') . ' for booking ' . ($validated['booking_id'] ?? 'n/a') . ' item: ' . ($unpaidItem->ItemName ?? 'n/a'),
                'ip_address' => $request->ip(),
            ]);
        } catch (\Exception $e) {
            // ignore
        }

        return response()->json([
            'success' => true,
            'message' => 'Item added successfully',
            'item' => [
                'ItemID' => $unpaidItem->ItemID,
                'ItemName' => $unpaidItem->ItemName,
                'Quantity' => $unpaidItem->Quantity,
                'Price' => $unpaidItem->Price,
                'TotalAmount' => $unpaidItem->TotalAmount,
            ]
        ]);
    }

    /**
     * Process bill out payment
     */
    /**
     * Display the bill out page for a booking
     */
    public function showBillOut($bookingId)
    {
        $booking = Booking::with(['guest', 'package', 'payments', 'unpaidItems'])
            ->where('BookingID', $bookingId)
            ->firstOrFail();

        // Get the most recent payment for display
        $payment = $booking->payments()->latest()->first();

        // Calculate days and booking balance
        $checkIn = \Carbon\Carbon::parse($booking->CheckInDate);
        $checkOut = \Carbon\Carbon::parse($booking->CheckOutDate);
        $days = $checkIn->diffInDays($checkOut);
        
        $packagePrice = $booking->package->Price ?? 0;
        $totalAmount = $packagePrice * $days;
        $excessFee = $booking->ExcessFee ?? 0;
        $totalAmount += $excessFee;
        
        $amountPaid = $booking->payments->sum('Amount');
        $bookingBalance = $totalAmount - $amountPaid;

        // Get unpaid items
        $unpaidItems = $booking->unpaidItems()->where('IsPaid', false)->get();
        $unpaidItemsTotal = $unpaidItems->sum('TotalPrice');

        // Get unpaid rentals with fees (Issued, Returned, Lost, Damaged)
        $rentals = $booking->rentals()
            ->whereIn('status', ['Issued', 'Returned', 'Lost', 'Damaged'])
            ->where('is_paid', false)
            ->with(['rentalItem', 'fees'])
            ->get();
        
        $rentalChargesTotal = $rentals->sum(function($rental) {
            return $rental->calculateTotalCharges();
        });

        // Calculate total outstanding
        $totalOutstanding = $bookingBalance + $unpaidItemsTotal + $rentalChargesTotal;

        return view('admin.bookings.bill-out', compact(
            'booking',
            'payment',
            'bookingBalance',
            'unpaidItems',
            'unpaidItemsTotal',
            'rentals',
            'rentalChargesTotal',
            'totalOutstanding'
        ));
    }

    /**
     * Process the bill out payment
     */
    public function processBillOut(Request $request)
    {
        $validated = $request->validate([
            'booking_id' => 'required|string|exists:bookings,BookingID',
            'payment_method' => 'required|string',
            'settlement_amount' => 'required|numeric|min:0',
            'total_outstanding' => 'required|string',
            'amount_received' => 'required|numeric|min:0',
            'change_amount' => 'required|string',
            'notes' => 'nullable|string',
            'actual_seniors' => 'nullable|integer|min:0',
            'apply_senior_discount' => 'nullable|boolean',
        ]);

        $booking = Booking::with(['unpaidItems', 'package', 'payments', 'rentals'])->findOrFail($validated['booking_id']);

        // Parse the currency values (remove ₱ and commas)
        $totalOutstanding = floatval(preg_replace('/[^0-9.]/', '', $validated['total_outstanding']));
        $changeAmount = floatval(preg_replace('/[^0-9.-]/', '', $validated['change_amount']));

        // Calculate booking costs
        $checkIn = \Carbon\Carbon::parse($booking->CheckInDate);
        $checkOut = \Carbon\Carbon::parse($booking->CheckOutDate);
        $days = $checkIn->diffInDays($checkOut);
        
        $packagePrice = $booking->package->Price ?? 0;
        $packageTotal = $packagePrice * $days;
        $excessFee = $booking->ExcessFee ?? 0;
        
        // Apply senior citizen discount if requested
        $seniorDiscount = 0;
        if (!empty($validated['apply_senior_discount']) && !empty($validated['actual_seniors'])) {
            $actualSeniors = intval($validated['actual_seniors']);
            $adultCount = $booking->NumOfAdults + $booking->NumOfSeniors;
            
            if ($adultCount > 0 && $actualSeniors > 0) {
                // Calculate per-adult share of package total
                $perAdultShare = $packageTotal / $adultCount;
                // Apply 20% discount to senior citizens' share
                $seniorDiscount = $perAdultShare * $actualSeniors * 0.20;
            }
        }
        
        // Calculate total booking cost (package + excess - senior discount)
        $bookingTotal = $packageTotal + $excessFee - $seniorDiscount;
        
        // Get previously paid amount for booking
        $previouslyPaid = $booking->payments()
            ->whereIn('PaymentPurpose', ['Booking Payment', 'Reservation Fee', 'Downpayment', 'Full Payment', null])
            ->sum('Amount');
        
        // Get unpaid items total
        $unpaidItemsTotal = $booking->unpaidItems()->where('IsPaid', false)->sum('TotalAmount');
        
        // Get unpaid rental charges
        $rentalCharges = $booking->rentals()
            ->whereIn('status', ['Issued', 'Returned', 'Lost', 'Damaged'])
            ->where('is_paid', false)
            ->get()
            ->sum(function($rental) {
                return $rental->calculateTotalCharges();
            });
        
        // Total outstanding = booking balance + unpaid items + rental charges
        $bookingBalance = max(0, $bookingTotal - $previouslyPaid);
        $calculatedOutstanding = $bookingBalance + $unpaidItemsTotal + $rentalCharges;
        
        // Create payment record for bill out
        $payment = \App\Models\Payment::create([
            'BookingID' => $validated['booking_id'],
            'Amount' => $validated['settlement_amount'],
            'PaymentMethod' => $validated['payment_method'],
            'PaymentPurpose' => 'Bill Out Settlement',
            'PaymentStatus' => 'Fully Paid',
            'PaymentDate' => now(),
            'ReferenceNumber' => $validated['notes'] ?? null,
            'total_outstanding' => $totalOutstanding,
            'amount_received' => $validated['amount_received'],
            'change_amount' => $changeAmount,
            'processed_by' => \Illuminate\Support\Facades\Auth::user() ? \Illuminate\Support\Facades\Auth::user()->user_id : null,
        ]);

        // Get authenticated user details
        $authUser = \Illuminate\Support\Facades\Auth::user();
        $processedBy = $authUser ? $authUser->user_id : null;
        $processorName = $authUser ? $authUser->name : 'Admin';
        $customerName = trim(($booking->guest->FName ?? '') . ' ' . ($booking->guest->MName ?? '') . ' ' . ($booking->guest->LName ?? ''));

        // Create transaction record for booking balance portion (if any)
        if ($bookingBalance > 0) {
            \App\Models\Transaction::create([
                'transaction_type' => 'booking',
                'reference_id' => $payment->PaymentID,
                'transaction_date' => now(),
                'amount' => $bookingBalance,
                'payment_method' => $validated['payment_method'],
                'payment_status' => 'Fully Paid',
                'purpose' => 'Bill Out Settlement',
                'booking_id' => $booking->BookingID,
                'guest_id' => $booking->GuestID,
                'customer_name' => $customerName,
                'customer_email' => $booking->guest->Email ?? null,
                'customer_phone' => $booking->guest->Phone ?? null,
                'processed_by' => $processedBy,
                'processor_name' => $processorName,
                'amount_received' => $validated['amount_received'],
                'change_amount' => $changeAmount,
                'reference_number' => $validated['notes'] ?? null,
                'metadata' => [
                    'total_outstanding' => $totalOutstanding,
                    'booking_balance' => $bookingBalance,
                    'unpaid_items_total' => $unpaidItemsTotal,
                    'rental_charges' => $rentalCharges,
                    'senior_discount' => $seniorDiscount,
                    'actual_seniors' => $validated['actual_seniors'] ?? 0,
                    'source' => 'admin_bill_out',
                ],
            ]);
        }

        // Create separate transaction records for each rental item
        $unpaidRentals = $booking->rentals()
            ->whereIn('status', ['Issued', 'Returned', 'Lost', 'Damaged'])
            ->where('is_paid', false)
            ->with('rentalItem.inventoryItem', 'fees')
            ->get();

        foreach ($unpaidRentals as $rental) {
            $rentalItemName = $rental->rentalItem->name ?? 'Unknown Item';
            
            // Calculate base rental charge (without additional fees)
            $baseCharge = 0;
            if ($rental->rate_type_snapshot === 'Per-Day') {
                $endDate = $rental->returned_at ?? \Carbon\Carbon::now();
                $rentalDays = $rental->issued_at->diffInDays($endDate);
                $rentalDays = max(1, $rentalDays); // Minimum 1 day
                $baseCharge = $rental->rate_snapshot * $rentalDays * $rental->quantity;
            } else {
                // Flat rate
                $baseCharge = $rental->rate_snapshot * $rental->quantity;
            }
            
            // Create transaction for base rental charge
            if ($baseCharge > 0) {
                \App\Models\Transaction::create([
                    'transaction_type' => 'rental',
                    'reference_id' => $payment->PaymentID,
                    'transaction_date' => now(),
                    'amount' => $baseCharge,
                    'payment_method' => $validated['payment_method'],
                    'payment_status' => 'Fully Paid',
                    'purpose' => 'Bill Out Settlement - ' . $rentalItemName,
                    'booking_id' => $booking->BookingID,
                    'rental_id' => $rental->id,
                    'guest_id' => $booking->GuestID,
                    'customer_name' => $customerName,
                    'customer_email' => $booking->guest->Email ?? null,
                    'customer_phone' => $booking->guest->Phone ?? null,
                    'processed_by' => $processedBy,
                    'processor_name' => $processorName,
                    'amount_received' => null,
                    'change_amount' => null,
                    'reference_number' => $validated['notes'] ?? null,
                    'metadata' => [
                        'rental_item_id' => $rental->rental_item_id,
                        'rental_item_name' => $rentalItemName,
                        'quantity' => $rental->quantity,
                        'rate' => $rental->rate_snapshot,
                        'rate_type' => $rental->rate_type_snapshot,
                        'source' => 'admin_bill_out',
                    ],
                ]);
            }

            // Create separate transactions for each damage/loss fee
            $fees = $rental->fees()->get();
            foreach ($fees as $fee) {
                $feeType = $fee->type === 'Damage' ? 'DF' : 'LF'; // Damage Fee or Loss Fee
                \App\Models\Transaction::create([
                    'transaction_type' => 'rental',
                    'reference_id' => $payment->PaymentID,
                    'transaction_date' => now(),
                    'amount' => $fee->amount,
                    'payment_method' => $validated['payment_method'],
                    'payment_status' => 'Fully Paid',
                    'purpose' => 'Bill Out Settlement - ' . $rentalItemName . ' ' . $feeType,
                    'booking_id' => $booking->BookingID,
                    'rental_id' => $rental->id,
                    'guest_id' => $booking->GuestID,
                    'customer_name' => $customerName,
                    'customer_email' => $booking->guest->Email ?? null,
                    'customer_phone' => $booking->guest->Phone ?? null,
                    'processed_by' => $processedBy,
                    'processor_name' => $processorName,
                    'amount_received' => null,
                    'change_amount' => null,
                    'reference_number' => $validated['notes'] ?? null,
                    'metadata' => [
                        'rental_item_id' => $rental->rental_item_id,
                        'rental_item_name' => $rentalItemName,
                        'fee_type' => $fee->type,
                        'fee_reason' => $fee->reason,
                        'source' => 'admin_bill_out',
                    ],
                ]);
            }
        }

        // Mark all unpaid items as paid
        $booking->unpaidItems()->where('IsPaid', false)->update(['IsPaid' => true]);
        
        // Mark all rental charges as paid
        $booking->rentals()
            ->whereIn('status', ['Issued', 'Returned', 'Lost', 'Damaged'])
            ->where('is_paid', false)
            ->update(['is_paid' => true]);

        // Update booking with senior discount if applied
        if ($seniorDiscount > 0) {
            $booking->update([
                'senior_discount' => $seniorDiscount,
                'actual_seniors_at_checkout' => $validated['actual_seniors'] ?? 0,
            ]);
        }

        // Check if booking is now fully paid
        $booking->load('payments');
        $totalPaid = $booking->payments->sum('Amount');
        $finalBalance = $bookingTotal - $totalPaid;
        
        // If the balance is essentially zero (within 1 peso to account for rounding), mark as fully paid
        if (abs($finalBalance) <= 1) {
            // Update the booking's payment status would be determined by the payments relationship
            // The bill out payment we just created should reflect this is complete
            $message = 'Bill out processed successfully. Booking is now fully paid. Payment of ₱' . number_format((float)$payment->Amount, 2) . ' recorded.';
        } else {
            $message = 'Bill out processed successfully. Payment of ₱' . number_format((float)$payment->Amount, 2) . ' recorded.';
        }

        // Audit log: bill out processed
        try {
            Audit_Log::create([
                'user_id' => \Illuminate\Support\Facades\Auth::user()->user_id ?? null,
                'action' => 'Process Bill Out',
                'description' => 'Processed bill out for booking ' . ($booking->BookingID ?? 'n/a') . ' payment: ' . ($payment->PaymentID ?? 'n/a') . ' amount: ' . ($payment->Amount ?? '0'),
                'ip_address' => request()->ip(),
            ]);
        } catch (\Exception $e) {
            // ignore
        }

        return redirect()->route('admin.currently-staying')
            ->with('success', $message);
    }

    /**
     * Process rental returns with damage fees
     */
    public function returnRentals(Request $request)
    {
        $validated = $request->validate([
            'booking_id' => 'required|string|exists:bookings,BookingID',
            'returns' => 'required|array',
            'returns.*.rental_id' => 'required|integer|exists:rentals,id',
            'returns.*.quantity' => 'required|integer|min:0',
            'returns.*.status' => 'required|string|in:Returned,Lost,Damaged',
            'returns.*.damage_fee' => 'nullable|numeric|min:0',
        ]);

        $booking = Booking::findOrFail($validated['booking_id']);
        $processedCount = 0;

        foreach ($validated['returns'] as $returnData) {
            $rental = \App\Models\Rental::findOrFail($returnData['rental_id']);
            
            // Validate quantity based on status
            $returnQty = $returnData['quantity'];
            $status = $returnData['status'];
            
            if ($returnQty > $rental->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot return more than rented quantity for {$rental->rentalItem->name}"
                ], 400);
            }
            
            // For Returned or Damaged status, quantity must be at least 1
            if (in_array($status, ['Returned', 'Damaged']) && $returnQty <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Quantity must be at least 1 for {$status} items"
                ], 400);
            }
            
            // Update rental status and quantity
            $rental->status = $status;
            $rental->returned_at = now();
            $rental->returned_by = \Illuminate\Support\Facades\Auth::user()->user_id ?? null;
            $rental->returned_quantity = $returnQty;
            
            if ($status === 'Returned') {
                $rental->condition = 'Good';
            } elseif ($status === 'Damaged') {
                $rental->condition = 'Damaged';
                
                // Calculate damaged quantity vs. good quantity
                $damagedQty = $returnQty; // The quantity specified is the damaged amount
                $goodQty = $rental->quantity - $damagedQty;
                
                // If some items were returned in good condition (not all damaged)
                if ($goodQty > 0) {
                    // Create a new rental record for the items returned in good condition
                    $goodRental = \App\Models\Rental::create([
                        'BookingID' => $rental->BookingID,
                        'rental_item_id' => $rental->rental_item_id,
                        'quantity' => $goodQty,
                        'rate_snapshot' => $rental->rate_snapshot,
                        'rate_type_snapshot' => $rental->rate_type_snapshot,
                        'status' => 'Returned',
                        'returned_quantity' => $goodQty,
                        'condition' => 'Good',
                        'notes' => "Partial return from rental #{$rental->id} - {$goodQty} of {$rental->quantity} items returned in good condition",
                        'issued_at' => $rental->issued_at,
                        'returned_at' => now(),
                        'issued_by' => $rental->issued_by,
                        'returned_by' => \Illuminate\Support\Facades\Auth::user()->user_id ?? null,
                        'is_paid' => $rental->is_paid,
                    ]);
                    
                    // Create a stock movement record for the returned items (informational)
                    $rentalItem = $rental->rentalItem;
                    if ($rentalItem && $rentalItem->inventoryItem) {
                        \App\Models\StockMovement::create([
                            'sku' => $rentalItem->inventoryItem->sku,
                            'movement_type' => 'in',
                            'quantity' => $goodQty, // Track good items quantity
                            'reason' => 'adjustment_in',
                            'rental_id' => $goodRental->id,
                            'notes' => "Returned in good condition: {$goodQty} items from rental #{$rental->id} ({$damagedQty} damaged, {$goodQty} good of {$rental->quantity} total)",
                            'performed_by' => \Illuminate\Support\Facades\Auth::user()->user_id ?? null,
                        ]);
                    }
                }
                
                // Update the original rental to reflect only the damaged quantity
                $rental->quantity = $damagedQty;
                $rental->returned_quantity = $damagedQty; // All damaged items are "returned" (physically back)
                
                // Create a stock movement record for damaged items (for audit trail)
                if ($damagedQty > 0) {
                    $rentalItem = $rental->rentalItem;
                    if ($rentalItem && $rentalItem->inventoryItem) {
                        \App\Models\StockMovement::create([
                            'sku' => $rentalItem->inventoryItem->sku,
                            'movement_type' => 'out',
                            'quantity' => $damagedQty, // Track damaged items quantity
                            'reason' => 'rental_damage',
                            'rental_id' => $rental->id,
                            'notes' => "Damaged: {$damagedQty} items from rental #{$rental->id} ({$damagedQty} damaged, {$goodQty} good of " . ($damagedQty + $goodQty) . " total)",
                            'performed_by' => \Illuminate\Support\Facades\Auth::user()->user_id ?? null,
                        ]);
                    }
                }
                
                // Add damage fee if provided
                if (isset($returnData['damage_fee']) && $returnData['damage_fee'] > 0) {
                    \App\Models\RentalFee::create([
                        'rental_id' => $rental->id,
                        'type' => 'Damage',
                        'amount' => $returnData['damage_fee'],
                        'reason' => 'Damage fee for damaged item',
                        'added_by' => \Illuminate\Support\Facades\Auth::user()->user_id ?? null
                    ]);
                }
            } elseif ($status === 'Lost') {
                $rental->condition = 'Lost';
                
                // Calculate lost quantity (total issued - returned)
                $lostQty = $rental->quantity - $returnQty;
                
                // If some items were returned (not all lost), create a separate record for the returned items
                if ($returnQty > 0) {
                    // Create a new rental record for the returned portion
                    $returnedRental = \App\Models\Rental::create([
                        'BookingID' => $rental->BookingID,
                        'rental_item_id' => $rental->rental_item_id,
                        'quantity' => $returnQty,
                        'rate_snapshot' => $rental->rate_snapshot,
                        'rate_type_snapshot' => $rental->rate_type_snapshot,
                        'status' => 'Returned',
                        'returned_quantity' => $returnQty,
                        'condition' => 'Good',
                        'notes' => "Partial return from rental #{$rental->id} - {$returnQty} of {$rental->quantity} items returned in good condition",
                        'issued_at' => $rental->issued_at,
                        'returned_at' => now(),
                        'issued_by' => $rental->issued_by,
                        'returned_by' => \Illuminate\Support\Facades\Auth::user()->user_id ?? null,
                        'is_paid' => $rental->is_paid, // Inherit payment status
                    ]);
                    
                    // Create a stock movement record for the returned items (informational)
                    $rentalItem = $rental->rentalItem;
                    if ($rentalItem && $rentalItem->inventoryItem) {
                        \App\Models\StockMovement::create([
                            'sku' => $rentalItem->inventoryItem->sku,
                            'movement_type' => 'in',
                            'quantity' => $returnQty, // Track returned items quantity
                            'reason' => 'adjustment_in',
                            'rental_id' => $returnedRental->id,
                            'notes' => "Returned in good condition: {$returnQty} items from rental #{$rental->id} ({$lostQty} lost, {$returnQty} returned of {$rental->quantity} total)",
                            'performed_by' => \Illuminate\Support\Facades\Auth::user()->user_id ?? null,
                        ]);
                    }
                }
                
                // Update the original rental to reflect only the lost quantity
                $rental->quantity = $lostQty;
                $rental->returned_quantity = 0; // None of the lost items were returned
                
                // If some items are lost (not all returned), decrease inventory quantity on hand
                if ($lostQty > 0) {
                    $rentalItem = $rental->rentalItem;
                    if ($rentalItem && $rentalItem->inventoryItem) {
                        $inventoryItem = $rentalItem->inventoryItem;
                        $newQuantity = max(0, $inventoryItem->quantity_on_hand - $lostQty);
                        $inventoryItem->update(['quantity_on_hand' => $newQuantity]);
                        
                        // Create a stock movement record for audit trail
                        \App\Models\StockMovement::create([
                            'sku' => $inventoryItem->sku,
                            'movement_type' => 'out',
                            'quantity' => $lostQty,
                            'reason' => 'lost',
                            'rental_id' => $rental->id,
                            'notes' => "Lost: {$lostQty} items from rental #{$rental->id} ({$lostQty} lost, {$returnQty} returned of " . ($lostQty + $returnQty) . " total)",
                            'performed_by' => \Illuminate\Support\Facades\Auth::user()->user_id ?? null,
                        ]);
                    }
                }
                
                // Add loss fee if provided
                if (isset($returnData['damage_fee']) && $returnData['damage_fee'] > 0) {
                    \App\Models\RentalFee::create([
                        'rental_id' => $rental->id,
                        'type' => 'Loss',
                        'amount' => $returnData['damage_fee'],
                        'reason' => 'Loss fee for lost item',
                        'added_by' => \Illuminate\Support\Facades\Auth::user()->user_id ?? null
                    ]);
                }
            }
            
            $rental->save();
            $processedCount++;
        }

        // Audit log: rentals returned
        try {
            Audit_Log::create([
                'user_id' => \Illuminate\Support\Facades\Auth::user()->user_id ?? null,
                'action' => 'Return Rentals',
                'description' => 'Processed returns for booking ' . ($validated['booking_id'] ?? 'n/a') . ' processed_count: ' . ($processedCount ?? 0),
                'ip_address' => request()->ip(),
            ]);
        } catch (\Exception $e) {
            // ignore
        }

        return response()->json([
            'success' => true,
            'message' => "$processedCount rental(s) returned successfully"
        ]);
    }
}
