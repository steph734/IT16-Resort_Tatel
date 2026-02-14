<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\Transaction;
use App\Services\PaymongoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Mail;
use App\Mail\BookingConfirmationMail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class PaymongoController extends Controller
{
    public function __construct(private PaymongoService $paymongo)
    {
    }

    /**
     * Create a PayMongo payment link after user agrees to terms.
     * Returns JSON with checkout_url.
     */
    public function createLink(Request $request)
    {
        $validated = $request->validate([
            'purpose' => 'required|string|in:Downpayment,Full Payment,Partial Payment,Full Payment (Balance)',
            'amount' => 'required|numeric|min:0.01',
            'agree' => 'required|accepted',
            'booking_id' => 'nullable|string', // optional when using existing booking
        ]);

        // Determine booking context: either from existing booking_id or session booking_details
        $bookingDetails = Session::get('booking_details');
        $booking = null;
        
        // ONLY load booking if explicitly passed in request (for existing booking payments)
        if ($request->filled('booking_id')) {
            $booking = Booking::with('guest')->where('BookingID', $request->booking_id)->first();
        }

        // If no existing booking, ensure we have session details (guest flow for NEW booking)
        if (!$booking && empty($bookingDetails)) {
            return response()->json(['message' => 'No booking context found. Start the booking flow first.'], 422);
        }

        // For NEW booking flow: clear any old booking_id from session to prevent conflicts
        // This ensures we don't accidentally use a previous booking's ID
        if (!$booking && !empty($bookingDetails)) {
            // This is a NEW booking flow - clear old session booking_id
            Session::forget('booking_id');
            
            Log::info('New booking payment flow detected - cleared old session booking_id', [
                'has_booking_details' => true,
            ]);
        }

        // Amount in centavos
        $amountCentavos = (int) round($validated['amount'] * 100);

        // Build description and metadata
        $purpose = $validated['purpose'];
        if ($booking) {
            $desc = "JBRB Booking {$booking->BookingID} - {$purpose}";
            $email = $booking->guest->Email ?? null;
            $name = trim(($booking->guest->FName ?? '') . ' ' . ($booking->guest->LName ?? ''));
            $packageId = $booking->PackageID;
            $checkin = $booking->CheckInDate;
            $checkout = $booking->CheckOutDate;
        } else {
            $bd = $bookingDetails['booking'];
            $gd = $bookingDetails['guest'];
            $desc = "JBRB Booking - {$purpose}"; // fallback (should rarely occur now that we create provisional booking)
            $email = $gd['Email'] ?? null;
            $name = trim(($gd['FName'] ?? '') . ' ' . ($gd['LName'] ?? ''));
            $packageId = $bd['PackageID'] ?? null;
            $checkin = $bd['CheckInDate'] ?? null;
            $checkout = $bd['CheckOutDate'] ?? null;
        }

        // Include booking_id only for existing bookings; omit for session-only (uncommitted) bookings.
        $metadata = array_filter([
            'booking_id' => $booking?->BookingID,
            'purpose' => $purpose,
            'package_id' => $packageId ?? null,
            'checkin' => $checkin ? (string) $checkin : null,
            'checkout' => $checkout ? (string) $checkout : null,
            'customer_email' => $email,
            'customer_name' => $name,
            'remarks' => $booking ? ("JBRB Online Booking Payment for " . $booking->BookingID) : 'JBRB Online Booking Payment (Deferred Commit)',
        ]);

        // Build success and cancel URLs for Checkout Session
        // ALL payments (new and existing bookings) should go through success callback
        // to ensure payment and transaction are created
        $successUrl = route('payments.paymongo.success');
        
        // For cancel URL: check if this is a NEW booking flow or EXISTING booking payment
        // NEW booking: user has booking_details session but did NOT pass booking_id in request
        // EXISTING booking: user passed booking_id in request
        $isNewBookingFlow = !$request->filled('booking_id') && !empty($bookingDetails);
        
        $cancelUrl = $isNewBookingFlow
            ? route('payments.paymongo.cancel')  // New booking → go through cancel() method
            : ($booking 
                ? route('bookings.payment.booking', ['booking' => $booking->BookingID])  // Existing booking → direct to that booking's payment page
                : route('payments.paymongo.cancel'));  // Fallback

        // Restrict payment methods to GCash and BDO Online if available.
        // For BDO Online, PayMongo may expect 'online_banking_bdo' depending on account capabilities.
    $bdoType = config('paymongo.bdo_method_type', 'online_banking_bdo');
    $methodSet = config('paymongo.default_method_types', ['gcash', $bdoType]);

        // Try multiple method sets to surface online banking options where supported
        $attempts = [
            $methodSet,                             // config default (e.g., ['gcash','online_banking_bdo'])
            ['gcash', 'online_banking'],            // generic online banking, if supported
            ['gcash'],                              // gcash only fallback
        ];

        $result = null;
        $lastError = null;
        $referenceNumber = $booking ? ('JBRB-' . $booking->BookingID) : ('JBRB-TEMP-' . Str::uuid());
        foreach ($attempts as $methods) {
            try {
                $result = $this->paymongo->createCheckoutSession(
                    $amountCentavos,
                    $desc,
                    $metadata,
                    $methods,
                    $successUrl,
                    $cancelUrl,
                    $referenceNumber
                );
                if (!empty($result['checkout_url'])) {
                    break; // success
                }
            } catch (\Throwable $e) {
                $lastError = $e;
                Log::warning('Checkout session attempt failed', [
                    'methods' => $methods,
                    'error' => $e->getMessage(),
                ]);
                $result = null;
            }
        }

        if (!$result) {
            Log::error('PayMongo checkout error (all attempts failed)', [
                'error' => $lastError?->getMessage(),
                'reference_number' => $referenceNumber,
            ]);
            // Fallback: try creating a payment link instead of a checkout session
            try {
                $linkResult = $this->paymongo->createPaymentLink($amountCentavos, $desc, $metadata);
                if (!empty($linkResult['checkout_url'])) {
                    // Store in session for deferred commit if booking not yet created
                    if (!$booking) {
                        Session::put('paymongo_checkout', [
                            'id' => $linkResult['id'] ?? null,
                            'url' => $linkResult['checkout_url'],
                            'purpose' => $purpose,
                            'amount' => $validated['amount'],
                            'booking' => $bookingDetails['booking'],
                            'guest' => $bookingDetails['guest'],
                            'created_at' => now()->toDateTimeString(),
                        ]);
                    }
                    return response()->json([
                        'checkout_url' => $linkResult['checkout_url'],
                        'booking_id' => $booking?->BookingID,
                        'reference' => $linkResult['id'] ?? null,
                        'fallback' => true,
                    ]);
                }
            } catch (\Throwable $fallbackE) {
                Log::error('PayMongo fallback payment link failed', [
                    'error' => $fallbackE->getMessage(),
                ]);
            }
            $genericMessage = app()->environment('local')
                ? ('Unable to create checkout. Last error: ' . ($lastError?->getMessage() ?? 'Unknown'))
                : 'Unable to create checkout at the moment.';
            return response()->json(['message' => $genericMessage], 500);
        }

        $linkId = $result['id'] ?? null;
        $checkoutUrl = $result['checkout_url'] ?? null;

        if (!$checkoutUrl) {
            return response()->json(['message' => 'No checkout URL returned from PayMongo.'], 502);
        }

        // Persist a Payment row to track this attempt (will be auto-marked Paid on success)
        if ($booking) {
            // Existing booking: record payment attempt immediately.
            // Calculate TotalAmount for the booking if not stored
            $totalAmount = $booking->TotalAmount ?? null;
            
            // If TotalAmount is not set on booking, calculate it from package and stored values
            if (!$totalAmount) {
                $package = $booking->package;
                if ($package) {
                    $checkIn = \Carbon\Carbon::parse($booking->CheckInDate);
                    $checkOut = \Carbon\Carbon::parse($booking->CheckOutDate);
                    $daysOfStay = $checkIn->diffInDays($checkOut);
                    $packageTotal = $package->Price * $daysOfStay;
                    $excessFee = $booking->ExcessFee ?? 0;
                    $seniorDiscount = $booking->senior_discount ?? 0;
                    $totalAmount = $packageTotal + $excessFee - $seniorDiscount;
                } else {
                    Log::error('Booking missing package', ['booking_id' => $booking->BookingID]);
                    return response()->json(['message' => 'Booking package not found. Please contact support.'], 422);
                }
            }

            // Store payment intent for existing booking (will be created in success callback)
            Session::put('paymongo_payment_intent', [
                'booking_id' => $booking->BookingID,
                'amount' => $validated['amount'],
                'total_amount' => $totalAmount,
                'purpose' => $purpose,
                'reference' => $linkId ?: $checkoutUrl,
                'created_at' => now()->toDateTimeString(),
            ]);
            
            // Also store booking_id for success callback
            Session::put('booking_id', $booking->BookingID);
        } else {
            // New booking flow: store checkout data for deferred commit.
            Session::put('paymongo_checkout', [
                'id' => $linkId,
                'url' => $checkoutUrl,
                'purpose' => $purpose,
                'amount' => $validated['amount'],
                'booking' => $bookingDetails['booking'],
                'guest' => $bookingDetails['guest'],
                'created_at' => now()->toDateTimeString(),
            ]);
        }

        return response()->json([
            'checkout_url' => $checkoutUrl,
            'booking_id' => $booking?->BookingID, // null if deferred (session draft)
            'reference' => $linkId ?: null,
        ]);
    }

    /**
     * Webhook endpoint to receive PayMongo events and confirm payments.
     */
    public function webhook(Request $request)
    {
        $payload = $request->all();
        Log::info('PayMongo webhook received', ['payload' => $payload]);

        // Optional: verify signature header using config('paymongo.webhook_secret')
        // Skipped here for brevity; recommended for production.

        $eventType = $payload['data']['attributes']['type'] ?? $payload['type'] ?? null;
        $data = $payload['data']['attributes']['data'] ?? ($payload['data'] ?? []);

        // Attempt to extract payment info
        $paymentAttr = $data['attributes'] ?? [];
        $paid = ($paymentAttr['status'] ?? '') === 'paid';
        $metadata = $paymentAttr['metadata'] ?? [];
        $bookingId = $metadata['booking_id'] ?? null;
        $purpose = $metadata['purpose'] ?? null;
        $amount = isset($paymentAttr['amount']) ? ((int) $paymentAttr['amount']) / 100 : null;
        $paymongoId = $data['id'] ?? null;

        if ($paid && $bookingId) {
            $booking = Booking::with(['guest', 'package'])->where('BookingID', $bookingId)->first();
            if ($booking) {
                // Update the most recent pending GCash/paymongo payment
                $payment = Payment::where('BookingID', $booking->BookingID)
                    ->whereIn('PaymentMethod', ['GCash', 'paymongo'])
                    ->orderByDesc('created_at')
                    ->first();
                if ($payment) {
                    // Determine payment status based on purpose and amount
                    $totalAmount = $payment->TotalAmount ?? $booking->TotalAmount ?? 0;
                    $paidAmount = $amount ?? $payment->Amount;

                    // Mark as "Fully Paid" only if purpose is "Full Payment" AND amount matches total
                    $paymentStatus = ($purpose === 'Full Payment' && abs($paidAmount - $totalAmount) < 0.01)
                        ? 'Fully Paid'
                        : 'Downpayment';

                    $payment->update([
                        'PaymentStatus' => $paymentStatus,
                        'Amount' => $paidAmount,
                        'ReferenceNumber' => $paymongoId ?? $payment->ReferenceNumber,
                        'PaymentMethod' => 'GCash', // Ensure it's set to GCash
                    ]);
                }

                // Optionally set booking status
                if ($purpose === 'Full Payment') {
                    $booking->update(['BookingStatus' => 'Booked']);
                } else {
                    // Keep Pending or set to Booked based on business rules
                    $booking->update(['BookingStatus' => 'Booked']);
                }

                // Send confirmation email via webhook ONLY for the first payment
                // Check if this is the first payment (only 1 payment exists)
                $paymentCount = $booking->payments()->count();
                
                if ($paymentCount === 1 && $booking->guest && $booking->guest->Email && $payment) {
                    try {
                        Mail::to($booking->guest->Email)->send(new BookingConfirmationMail($booking, $payment));
                        Log::info('Booking confirmation email sent via webhook (first payment)', [
                            'booking_id' => $booking->BookingID,
                            'email' => $booking->guest->Email,
                            'payment_count' => $paymentCount
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Email sending failed in webhook', [
                            'error' => $e->getMessage(),
                            'booking_id' => $booking->BookingID
                        ]);
                    }
                } elseif ($paymentCount > 1) {
                    Log::info('Skipping webhook email - not first payment', [
                        'booking_id' => $booking->BookingID,
                        'payment_count' => $paymentCount
                    ]);
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Polling endpoint: check if a booking has a paid PayMongo payment and provide confirmation redirect URL.
     */
    public function status(string $bookingId)
    {
        $booking = Booking::with('payments')->where('BookingID', $bookingId)->first();
        if (!$booking) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }

        $paid = $booking->payments()
            ->whereIn('PaymentMethod', ['GCash', 'paymongo'])
            ->whereIn('PaymentStatus', ['Downpayment', 'Fully Paid'])
            ->exists();

        $isBooked = $booking->BookingStatus === 'Booked';
        $shouldRedirect = $paid || $isBooked;

        return response()->json([
            'paid' => (bool) $paid,
            'booking_status' => $booking->BookingStatus,
            'redirect' => $shouldRedirect,
            'redirect_url' => $shouldRedirect ? route('bookings.confirmation', ['booking_id' => $booking->BookingID]) : null,
        ]);
    }

    /**
     * Create/ensure booking and create a 'For Verification' PayMongo payment,
     * then return confirmation redirect URL. Used by "Book My Stay" button.
     */
    public function confirmNow(Request $request)
    {
        $validated = $request->validate([
            'purpose' => 'required|string|in:Downpayment,Full Payment',
            'amount' => 'required|numeric|min:0.01',
            'booking_id' => 'nullable|string',
        ]);

        $booking = null;
        if ($request->filled('booking_id')) {
            $booking = Booking::with('guest')->where('BookingID', $request->booking_id)->first();
        }

        if (!$booking) {
            $bookingDetails = Session::get('booking_details');
            if (empty($bookingDetails)) {
                return response()->json(['message' => 'No booking context found.'], 422);
            }
            // Defer commit: store intent in session instead of creating booking now
            Session::put('paymongo_checkout_intent', [
                'purpose' => $validated['purpose'],
                'amount' => $validated['amount'],
                'booking' => $bookingDetails['booking'],
                'guest' => $bookingDetails['guest'],
                'created_at' => now()->toDateTimeString(),
            ]);
            return response()->json([
                'message' => 'Deferred confirmation stored. Proceed to payment.',
            ]);
        }

        // Create payment entry marked For Verification
        Payment::create([
            'BookingID' => $booking->BookingID,
            'PaymentDate' => now(),
            'Amount' => $validated['amount'],
            'TotalAmount' => $booking->TotalAmount ?? $validated['amount'],
            'PaymentMethod' => 'GCash',
            'PaymentStatus' => 'For Verification',
            'PaymentPurpose' => $validated['purpose'],
            // Generate a human-friendly reference number instead of 'confirm-now'
            'ReferenceNumber' => 'JBRB-' . $booking->BookingID . '-' . now()->format('YmdHis'),
            'processed_by' => $booking->BookingID, // Guest processed their own booking
        ]);

        return response()->json([
            'redirect_url' => route('bookings.confirmation', ['booking_id' => $booking->BookingID])
        ]);
    }

    /**
     * Success redirect for session-based (guest) payments. Commits booking & payment if not yet persisted.
     */
    public function success(Request $request)
    {
        $bookingId = Session::get('booking_id');
        $booking = $bookingId ? Booking::with('payments')->where('BookingID',$bookingId)->first() : null;
        $checkout = Session::get('paymongo_checkout');
        $paymentIntent = Session::get('paymongo_payment_intent');

        if (!$booking && empty($checkout)) {
            return redirect()->route('bookings.payment')->withErrors('No payment context found.');
        }

        // Track if this is a new booking (before we create it)
        $isNewBooking = (!$booking && !empty($checkout));

        // If we have deferred checkout data (new booking), create guest + booking now
        if (!$booking && !empty($checkout)) {
            $guest = Guest::create($checkout['guest']);
            $booking = Booking::create(array_merge($checkout['booking'], [
                'GuestID' => $guest->GuestID,
                'BookingDate' => now(),
                'BookingStatus' => 'Pending',
            ]));
            Session::put('booking_id', $booking->BookingID);

            // Reload booking with guest relationship to ensure we have fresh data
            $booking = Booking::with('guest')->find($booking->BookingID);
        }

        // Treat successful return as confirmed payment to remove need for manual verification.
        // Optionally still attempt a lookup, but default to Paid.
        $paid = true;
        $referenceForLookup = $checkout['id'] ?? ($booking?->payments()->whereIn('PaymentMethod', ['GCash', 'paymongo'])->orderByDesc('created_at')->value('ReferenceNumber'));
        try {
            if ($referenceForLookup) {
                $sessionAttr = $this->paymongo->getCheckoutSession($referenceForLookup);
                $status = $sessionAttr['status'] ?? null;
                // If API confirms success, keep Paid; if it indicates otherwise, we still consider Paid per requirement.
                if (in_array($status, ['paid','completed'])) {
                    $paid = true;
                }
            }
        } catch (\Throwable $e) {
            // Ignore lookup errors; per requirement we auto-confirm.
        }

        // Create or update payment record for deferred checkout
        $payment = null;
        if (!empty($checkout)) {
            // Check if payment already exists
            $existingPayment = $booking->payments()
                ->whereIn('PaymentMethod', ['paymongo', 'GCash'])
                ->first();
            
            if (!$existingPayment) {
                // Determine payment status based on purpose and amount
                // Get TotalAmount from booking first, then checkout booking data, finally from booking object
                $totalAmount = $checkout['booking']['TotalAmount'] ?? $booking->TotalAmount ?? $checkout['amount'];
                $paidAmount = $checkout['amount'];
                $purpose = $checkout['purpose'];

                // Debug logging to track TotalAmount source
                Log::info('PayMongo Payment Creation', [
                    'booking_id' => $booking->BookingID,
                    'checkout_total' => $checkout['booking']['TotalAmount'] ?? 'not set',
                    'booking_total' => $booking->TotalAmount ?? 'not set',
                    'calculated_total' => $totalAmount,
                    'paid_amount' => $paidAmount,
                    'purpose' => $purpose,
                ]);

                // Mark as "Fully Paid" only if purpose is "Full Payment" AND amount matches total
                $paymentStatus = ($purpose === 'Full Payment' && abs($paidAmount - $totalAmount) < 0.01)
                    ? 'Fully Paid'
                    : 'Downpayment';

                $payment = Payment::create([
                    'BookingID' => $booking->BookingID,
                    'PaymentDate' => now(),
                    'Amount' => $paidAmount,
                    'TotalAmount' => $totalAmount,
                    'PaymentMethod' => 'GCash',
                    'PaymentStatus' => $paymentStatus,
                    'PaymentPurpose' => $purpose,
                    'ReferenceNumber' => $referenceForLookup,
                    'processed_by' => $booking->BookingID, // Guest processed their own booking
                ]);
            } else {
                // Payment already exists (callback called multiple times)
                $payment = $existingPayment;
                $paidAmount = $payment->Amount;
                $totalAmount = $payment->TotalAmount;
                $purpose = $payment->PaymentPurpose;
                $paymentStatus = $payment->PaymentStatus;
                
                Log::info('Payment already exists, will check for transaction', [
                    'payment_id' => $payment->PaymentID,
                    'booking_id' => $booking->BookingID,
                ]);
            }

            // Create transaction record ONLY if payment exists AND transaction doesn't exist
            if ($payment) {
                $transactionExists = Transaction::where('reference_id', $payment->PaymentID)
                    ->where('transaction_type', 'booking')
                    ->exists();
                
                if (!$transactionExists) {
                    // Map payment status to transaction enum values
                    $transactionStatus = ($paymentStatus === 'Fully Paid') ? 'Fully Paid' : 'Downpayment';
                    
                    try {
                        Transaction::create([
                            'transaction_type' => 'booking',
                            'reference_id' => $payment->PaymentID,
                            'transaction_date' => now(),
                            'amount' => $paidAmount,
                            'payment_method' => 'GCash',
                            'payment_status' => $transactionStatus,
                            'purpose' => $purpose,
                            'booking_id' => $booking->BookingID,
                            'guest_id' => $booking->GuestID,
                            'customer_name' => trim(($booking->guest->FName ?? '') . ' ' . ($booking->guest->MName ?? '') . ' ' . ($booking->guest->LName ?? '')),
                            'customer_email' => $booking->guest->Email ?? null,
                            'customer_phone' => $booking->guest->Phone ?? null,
                            'processed_by' => Auth::check() ? Auth::user()->user_id : null,
                            'processor_name' => 'Online Payment (Guest)',
                            'amount_received' => $paidAmount,
                            'reference_number' => $referenceForLookup,
                            'metadata' => [
                                'paymongo_session_id' => $referenceForLookup,
                                'total_amount' => $totalAmount,
                                'source' => 'paymongo_public',
                                'payment_status_original' => $paymentStatus,
                            ],
                        ]);
                        
                        Log::info('Transaction created for new booking payment', [
                            'payment_id' => $payment->PaymentID,
                            'booking_id' => $booking->BookingID,
                            'transaction_status' => $transactionStatus,
                            'amount' => $paidAmount,
                        ]);
                    } catch (\Throwable $e) {
                        Log::error('Failed to create transaction for new booking payment', [
                            'payment_id' => $payment->PaymentID,
                            'booking_id' => $booking->BookingID,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                        // Don't fail the booking - payment was successful
                    }
                } else {
                    Log::info('Transaction already exists for payment, skipping duplicate', [
                        'payment_id' => $payment->PaymentID,
                        'booking_id' => $booking->BookingID,
                    ]);
                }
            }
        } elseif (!empty($paymentIntent)) {
            // Handle payment intent for existing bookings
            Log::info('Processing payment intent for existing booking', [
                'booking_id' => $paymentIntent['booking_id'],
                'amount' => $paymentIntent['amount'],
            ]);
            
            // Determine payment status
            $totalAmount = $paymentIntent['total_amount'];
            $paidAmount = $paymentIntent['amount'];
            $purpose = $paymentIntent['purpose'];
            
            // Calculate total paid including this new payment
            $previousPaid = $booking->payments()->sum('Amount');
            $totalPaid = $previousPaid + $paidAmount;
            
            $paymentStatus = ($purpose === 'Full Payment' && abs($paidAmount - $totalAmount) < 0.01)
                ? 'Fully Paid'
                : (($totalPaid >= $totalAmount) ? 'Fully Paid' : 'Downpayment');
            
            // Create payment record
            $payment = Payment::create([
                'BookingID' => $booking->BookingID,
                'PaymentDate' => now(),
                'Amount' => $paidAmount,
                'TotalAmount' => $totalAmount,
                'PaymentMethod' => 'GCash',
                'PaymentStatus' => $paymentStatus,
                'PaymentPurpose' => $purpose,
                'ReferenceNumber' => $paymentIntent['reference'],
                'processed_by' => Auth::check() ? Auth::user()->user_id : null,
            ]);
            
            // Create transaction record
            $transactionStatus = ($paymentStatus === 'Fully Paid') ? 'Fully Paid' : 'Downpayment';
            
            Transaction::create([
                'transaction_type' => 'booking',
                'reference_id' => $payment->PaymentID,
                'transaction_date' => now(),
                'amount' => $paidAmount,
                'payment_method' => 'GCash',
                'payment_status' => $transactionStatus,
                'purpose' => $purpose,
                'booking_id' => $booking->BookingID,
                'guest_id' => $booking->GuestID,
                'customer_name' => trim(($booking->guest->FName ?? '') . ' ' . ($booking->guest->MName ?? '') . ' ' . ($booking->guest->LName ?? '')),
                'customer_email' => $booking->guest->Email ?? null,
                'customer_phone' => $booking->guest->Phone ?? null,
                'processed_by' => Auth::check() ? Auth::user()->user_id : null,
                'processor_name' => 'Online Payment (Guest)',
                'amount_received' => $paidAmount,
                'reference_number' => $paymentIntent['reference'],
                'metadata' => [
                    'total_amount' => $totalAmount,
                    'previous_paid' => $previousPaid,
                    'total_paid' => $totalPaid,
                    'source' => 'paymongo_public_existing',
                    'payment_status_original' => $paymentStatus,
                ],
            ]);
            
            Log::info('Payment and transaction created for existing booking', [
                'payment_id' => $payment->PaymentID,
                'booking_id' => $booking->BookingID,
            ]);
        } else {
            // Update existing paymongo/GCash payment - mark as Paid (PayMongo handles verification)
            $payment = $booking->payments()->whereIn('PaymentMethod', ['paymongo', 'GCash'])->orderByDesc('created_at')->first();
            if ($payment) {
                $payment->update(['PaymentStatus' => 'Paid']);
                
                // Create transaction record ONLY if it doesn't already exist for this payment
                $transactionExists = Transaction::where('reference_id', $payment->PaymentID)
                    ->where('transaction_type', 'booking')
                    ->exists();
                
                if (!$transactionExists) {
                    // Determine transaction status based on total paid
                    $totalPaid = $booking->payments()->sum('Amount');
                    $totalAmount = $booking->TotalAmount ?? 0;
                    $transactionStatus = ($totalPaid >= $totalAmount) ? 'Fully Paid' : 'Downpayment';
                    
                    Transaction::create([
                        'transaction_type' => 'booking',
                        'reference_id' => $payment->PaymentID,
                        'transaction_date' => now(),
                        'amount' => $payment->Amount,
                        'payment_method' => $payment->PaymentMethod,
                        'payment_status' => $transactionStatus,
                        'purpose' => $payment->PaymentPurpose ?? 'Payment',
                        'booking_id' => $booking->BookingID,
                        'guest_id' => $booking->GuestID,
                        'customer_name' => trim(($booking->guest->FName ?? '') . ' ' . ($booking->guest->MName ?? '') . ' ' . ($booking->guest->LName ?? '')),
                        'customer_email' => $booking->guest->Email ?? null,
                        'customer_phone' => $booking->guest->Phone ?? null,
                        'processed_by' => Auth::check() ? Auth::user()->user_id : null,
                        'processor_name' => 'Online Payment (Guest)',
                        'amount_received' => $payment->Amount,
                        'reference_number' => $payment->ReferenceNumber,
                        'metadata' => [
                            'updated_from_verification' => true,
                            'source' => 'paymongo_public',
                            'payment_status_original' => 'Paid',
                        ],
                    ]);
                    
                    Log::info('Transaction created for existing payment', [
                        'payment_id' => $payment->PaymentID,
                        'booking_id' => $booking->BookingID,
                    ]);
                } else {
                    Log::info('Transaction already exists for payment, skipping duplicate', [
                        'payment_id' => $payment->PaymentID,
                        'booking_id' => $booking->BookingID,
                    ]);
                }
            }
        }

        // Update booking status based on payment
        $booking->update(['BookingStatus' => 'Booked']);

        // Cleanup session deferred data
        Session::forget('paymongo_checkout');
        Session::forget('paymongo_payment_intent');

        // Load relationships for email
        $booking->load(['guest', 'package']);

        // Get the payment record for email
        $payment = $booking->payments()->orderByDesc('created_at')->first();

        // Send confirmation email ONLY for the first payment
        // Check if this is the first payment (only 1 payment exists)
        $paymentCount = $booking->payments()->count();
        
        if ($paymentCount === 1 && $booking->guest && $booking->guest->Email && $payment) {
            try {
                Mail::to($booking->guest->Email)->send(new BookingConfirmationMail($booking, $payment));
                Log::info('Booking confirmation email sent (first payment)', [
                    'booking_id' => $booking->BookingID, 
                    'email' => $booking->guest->Email,
                    'payment_count' => $paymentCount
                ]);
            } catch (\Exception $e) {
                Log::error('Email sending failed after PayMongo success', [
                    'error' => $e->getMessage(),
                    'booking_id' => $booking->BookingID,
                    'email' => $booking->guest->Email ?? 'N/A'
                ]);
            }
        } elseif ($paymentCount > 1) {
            Log::info('Skipping email - not first payment', [
                'booking_id' => $booking->BookingID,
                'payment_count' => $paymentCount
            ]);
        }

        // Route to appropriate confirmation page based on whether this was a new booking
        if ($isNewBooking) {
            // For new bookings, redirect to regular confirmation page
            Log::info('Redirecting to confirmation page (new booking)', [
                'booking_id' => $booking->BookingID,
            ]);
            return redirect()->route('bookings.confirmation', ['booking_id' => $booking->BookingID])
                ->with('success', 'Payment successful! Your booking has been confirmed.');
        } else {
            // For existing booking payments (additional payments), show close window page
            Log::info('Showing close window page (existing booking payment)', [
                'booking_id' => $booking->BookingID,
            ]);
            return view('payments.success-close', compact('booking'));
        }
    }

    /**
     * Cancel redirect – discard pending checkout data without creating booking.
     */
    public function cancel(Request $request)
    {
        // Check if this is a new booking flow by looking for paymongo_checkout session
        $checkoutSession = Session::get('paymongo_checkout');
        $isNewBookingFlow = !empty($checkoutSession);
        
        // Clean up PayMongo session data
        // Keep booking_details session intact so user can modify their booking options
        Session::forget('paymongo_checkout');
        Session::forget('paymongo_payment_intent');
        
        Log::info('PayMongo payment cancelled by user', [
            'is_new_booking_flow' => $isNewBookingFlow,
            'has_booking_details' => !empty(Session::get('booking_details')),
            'user_agent' => $request->userAgent(),
        ]);
        
        // Always redirect to payment.blade.php for new booking flow
        // This ensures guests continue their NEW booking, not previous bookings
        return redirect()->route('bookings.payment')
            ->with('info', 'Payment cancelled. You can modify your booking options and try again.');
    }
}
