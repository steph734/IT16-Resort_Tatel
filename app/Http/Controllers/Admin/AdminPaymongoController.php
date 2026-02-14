<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Transaction;
use App\Services\PaymongoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Mail;
use App\Mail\BookingConfirmationMail;

class AdminPaymongoController extends Controller
{
    public function __construct(private PaymongoService $paymongo)
    {
    }

    /**
     * Create a PayMongo checkout session for an existing booking (admin context).
     * Admin only: booking_id is required; we do not create new bookings here.
     */
    public function createLink(Request $request)
    {
        $validated = $request->validate([
            'purpose' => 'required|string|in:Downpayment,Full Payment,Partial Payment',
            'amount' => 'required|numeric|min:0.01',
            'agree' => 'required|accepted',
            'booking_id' => 'required|string',
        ]);

    $booking = Booking::with(['guest','package'])->where('BookingID', $validated['booking_id'])->first();
        if (!$booking) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }

        $amountCentavos = (int) round($validated['amount'] * 100);
        $purpose = $validated['purpose'];
        $desc = "JBRB Admin Checkout {$booking->BookingID} - {$purpose}";
        $email = $booking->guest->Email ?? null;
        $name = trim(($booking->guest->FName ?? '') . ' ' . ($booking->guest->LName ?? ''));

        $metadata = array_filter([
            'source' => 'admin',
            'purpose' => $purpose,
            'booking_id' => $booking->BookingID,
            'customer_email' => $email,
            'customer_name' => $name,
        ]);

        // Redirect to admin success/cancel handlers that auto-confirm without manual verification
        $successUrl = route('admin.payments.paymongo.success', [
            'booking' => $booking->BookingID,
        ]);
        $cancelUrl = route('admin.payments.paymongo.cancel', [
            'booking' => $booking->BookingID,
        ]);

        // Method attempts similar to public controller
        $bdoType = config('paymongo.bdo_method_type', 'online_banking_bdo');
        $attempts = [
            config('paymongo.default_method_types', ['gcash', $bdoType]),
            ['gcash', 'online_banking'],
            ['gcash'],
        ];

        $result = null; $lastError = null;
        foreach ($attempts as $methods) {
            try {
                $result = $this->paymongo->createCheckoutSession(
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
            Log::error('Admin PayMongo checkout failed', ['error' => $lastError?->getMessage()]);
            return response()->json(['message' => 'Unable to create admin checkout at the moment.'], 500);
        }

        $linkId = $result['id'] ?? null;
        $checkoutUrl = $result['checkout_url'];

        // Get TotalAmount from booking, or compute from package + excess fee
        $totalAmount = $booking->TotalAmount;
        
        if (!$totalAmount || $totalAmount <= 0) {
            // Fallback: compute total amount from booking (price per day + excess)
            try {
                $checkIn = new \DateTime((string) $booking->CheckInDate);
                $checkOut = new \DateTime((string) $booking->CheckOutDate);
                $days = max(1, $checkIn->diff($checkOut)->days);
            } catch (\Throwable $e) {
                $days = 1;
            }
            $packagePrice = $booking->package->Price ?? 0;
            $totalAmount = ($packagePrice * $days) + (float) ($booking->ExcessFee ?? 0);
        }
        
        // Ensure we have a valid total amount
        if (!$totalAmount || $totalAmount <= 0) {
            Log::error('Admin booking missing TotalAmount', ['booking_id' => $booking->BookingID]);
            return response()->json(['message' => 'Booking total amount not properly set.'], 422);
        }

        // Store payment data in session to create on success instead of upfront
        Session::put('admin_paymongo_pending', [
            'booking_id' => $booking->BookingID,
            'amount' => $validated['amount'],
            'total_amount' => $totalAmount,
            'purpose' => $purpose,
            'reference' => $linkId ?: $checkoutUrl,
            'processed_by' => auth()->user()->user_id ?? null,
            'processor_name' => auth()->user()->name ?? 'Admin',
        ]);

        return response()->json([
            'checkout_url' => $checkoutUrl,
            'booking_id' => $booking->BookingID,
            'reference' => $linkId,
        ]);
    }

    /**
     * Poll booking status for admin (simplified).
     */
    public function status(string $bookingId)
    {
        $booking = Booking::with('payments')->where('BookingID', $bookingId)->first();
        if (!$booking) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }
        $paid = $booking->payments()
            ->whereIn('PaymentMethod', ['GCash', 'paymongo'])
            ->whereIn('PaymentStatus', ['Downpayment', 'Partial', 'Fully Paid'])
            ->exists();
        return response()->json([
            'paid' => (bool) $paid,
            'redirect' => true,
            'redirect_url' => route('admin.bookings.index'),
        ]);
    }

    /**
     * Success redirect for admin PayMongo checkout: auto-mark payment with proper status and update booking.
     */
    public function success(Request $request)
    {
        $bookingId = $request->query('booking');
        if (!$bookingId) {
            return redirect()->route('admin.bookings.index')->with('error', 'Missing booking reference.');
        }

        $booking = Booking::with(['payments', 'guest', 'package'])->where('BookingID', $bookingId)->first();
        if (!$booking) {
            return redirect()->route('admin.bookings.index')->with('error', 'Booking not found.');
        }

        // Get pending payment data from session
        $pendingData = Session::get('admin_paymongo_pending');
        if (!$pendingData || $pendingData['booking_id'] !== $bookingId) {
            return redirect()->route('admin.bookings.index')->with('error', 'No pending payment found.');
        }

        // Calculate total amount from booking
        $totalAmount = $pendingData['total_amount'];
        
        // Calculate total payments including this one
        $currentPaymentAmount = $pendingData['amount'];
        $previousTotal = $booking->payments()->sum('Amount');
        $totalPaid = $previousTotal + $currentPaymentAmount;
        
        // Count previous payments
        $previousPaymentsCount = $booking->payments()->count();
        
        // Determine payment status based on rule of thumb
        if (abs($totalPaid - $totalAmount) < 0.01) {
            // Fully paid: total payments equal booking total
            $paymentStatus = 'Fully Paid';
        } elseif ($previousPaymentsCount > 0) {
            // Partial: This is a subsequent payment but not fully paid yet
            $paymentStatus = 'Partial';
        } else {
            // Downpayment: This is the first payment
            $paymentStatus = 'Downpayment';
        }
        
        Log::info('Admin PayMongo Payment Status Determination', [
            'booking_id' => $booking->BookingID,
            'total_amount' => $totalAmount,
            'payment_amount' => $currentPaymentAmount,
            'total_paid' => $totalPaid,
            'previous_payments' => $previousPaymentsCount,
            'determined_status' => $paymentStatus,
        ]);
        
        // Create the payment record
        $payment = Payment::create([
            'BookingID' => $booking->BookingID,
            'PaymentDate' => now(),
            'Amount' => $currentPaymentAmount,
            'TotalAmount' => $totalAmount,
            'PaymentMethod' => 'GCash',
            'PaymentStatus' => $paymentStatus,
            'PaymentPurpose' => $pendingData['purpose'],
            'ReferenceNumber' => $pendingData['reference'],
            'processed_by' => $pendingData['processed_by'],
        ]);
        
        // Create transaction record
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
            'amount' => $currentPaymentAmount,
            'payment_method' => 'GCash',
            'payment_status' => $transactionStatus,
            'purpose' => $pendingData['purpose'],
            'booking_id' => $booking->BookingID,
            'guest_id' => $booking->GuestID,
            'customer_name' => trim(($booking->guest->FName ?? '') . ' ' . ($booking->guest->MName ?? '') . ' ' . ($booking->guest->LName ?? '')),
            'customer_email' => $booking->guest->Email ?? null,
            'customer_phone' => $booking->guest->Phone ?? null,
            'processed_by' => $pendingData['processed_by'],
            'processor_name' => $pendingData['processor_name'],
            'amount_received' => $currentPaymentAmount,
            'reference_number' => $pendingData['reference'],
            'metadata' => [
                'total_amount' => $totalAmount,
                'total_paid' => $totalPaid,
                'previous_payments_count' => $previousPaymentsCount,
                'source' => 'paymongo_admin',
                'payment_status_original' => $paymentStatus,
            ],
        ]);
        
        // Clear session data
        Session::forget('admin_paymongo_pending');
        
        // Send confirmation email ONLY for the first payment
        // Check if this is the first payment (only 1 payment exists)
        $paymentCount = $booking->payments()->count();
        
        if ($paymentCount === 1 && $booking->guest && $booking->guest->Email) {
            try {
                Mail::to($booking->guest->Email)->send(new BookingConfirmationMail($booking, $payment));
                Log::info('Admin booking confirmation email sent (first payment)', [
                    'booking_id' => $booking->BookingID,
                    'email' => $booking->guest->Email,
                    'payment_status' => $paymentStatus,
                    'payment_count' => $paymentCount,
                ]);
            } catch (\Exception $e) {
                Log::error('Admin email sending failed', [
                    'error' => $e->getMessage(),
                    'booking_id' => $booking->BookingID,
                    'email' => $booking->guest->Email ?? 'N/A'
                ]);
            }
        } elseif ($paymentCount > 1) {
            Log::info('Skipping admin email - not first payment', [
                'booking_id' => $booking->BookingID,
                'payment_count' => $paymentCount,
                'payment_status' => $paymentStatus,
            ]);
        }

        // Align with public flow: mark booking as Booked
        if ($booking->BookingStatus !== 'Booked') {
            $booking->update(['BookingStatus' => 'Booked']);
        }

        return redirect()->route('admin.bookings.index')->with('success', 'Payment confirmed and booking updated.');
    }

    /**
     * Cancel redirect for admin PayMongo checkout.
     */
    public function cancel(Request $request)
    {
        return redirect()->route('admin.bookings.index')->with('info', 'Payment cancelled.');
    }
}
