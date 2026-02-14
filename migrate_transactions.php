<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Payment;
use App\Models\Rental;
use App\Models\Transaction;

echo "=== Migrating Data to Transactions Table ===\n\n";

// 1. Migrate booking payments
echo "1. Migrating booking payments...\n";
$payments = Payment::with(['booking.guest', 'booking.package', 'booking.rentals.rentalItem', 'booking.unpaidItems'])
    ->whereIn('PaymentStatus', ['Fully Paid', 'Downpayment', 'Partial Payment'])
    ->get();

$bookingTxnCount = 0;
$rentalTxnCount = 0;
$unpaidItemTxnCount = 0;

foreach ($payments as $payment) {
    $booking = $payment->booking;
    $guest = $booking->guest ?? null;
    
    // Check if user exists
    $user = DB::table('users')->where('user_id', $payment->processed_by)->first();
    $processorName = $user ? $user->name : ($payment->processed_by ?? 'System');
    
    // Common data for all transactions from this payment
    $commonData = [
        'reference_id' => $payment->PaymentID,
        'transaction_date' => $payment->created_at,
        'payment_method' => $payment->PaymentMethod === 'paymongo' ? 'GCash' : $payment->PaymentMethod,
        'payment_status' => $payment->PaymentStatus,
        'booking_id' => $booking->BookingID,
        'guest_id' => $guest->GuestID ?? null,
        'customer_name' => $guest ? trim($guest->FName . ' ' . $guest->LName) : null,
        'customer_email' => $guest->Email ?? null,
        'customer_phone' => $guest->Phone ?? null,
        'processed_by' => $payment->processed_by,
        'processor_name' => $processorName,
        'amount_received' => $payment->amount_received ?? null,
        'change_amount' => $payment->change_amount ?? null,
        'reference_number' => $payment->ReferenceNumber,
    ];
    
    // If this is a bill-out settlement, split into separate transactions
    if ($payment->PaymentPurpose === 'Bill Out Settlement') {
        // Calculate total rental charges
        $totalRentalCharges = 0;
        foreach ($booking->rentals as $rental) {
            $totalRentalCharges += $rental->calculateTotalCharges();
        }
        
        // Calculate total unpaid items
        $totalUnpaidItems = 0;
        foreach ($booking->unpaidItems as $item) {
            $totalUnpaidItems += $item->TotalAmount;
        }
        
        // Booking balance = payment amount - rentals - unpaid items
        $bookingBalance = $payment->Amount - $totalRentalCharges - $totalUnpaidItems;
        
        // Create booking balance transaction (if any)
        if ($bookingBalance > 0) {
            $checkIn = \Carbon\Carbon::parse($booking->CheckInDate);
            $checkOut = \Carbon\Carbon::parse($booking->CheckOutDate);
            $days = $checkIn->diffInDays($checkOut);
            
            Transaction::create(array_merge($commonData, [
                'transaction_type' => 'booking',
                'amount' => $bookingBalance,
                'purpose' => 'Bill Out - Booking Balance',
                'metadata' => [
                    'package_name' => $booking->package->Name ?? null,
                    'check_in' => $booking->CheckInDate->format('Y-m-d'),
                    'check_out' => $booking->CheckOutDate->format('Y-m-d'),
                    'days' => $days,
                ],
            ]));
            $bookingTxnCount++;
        }
        
        // Create separate rental transactions
        foreach ($booking->rentals as $rental) {
            $itemName = $rental->rentalItem->name ?? 'Unknown Item';
            $totalCharges = $rental->calculateTotalCharges();
            
            if ($totalCharges > 0) {
                Transaction::create(array_merge($commonData, [
                    'transaction_type' => 'rental',
                    'amount' => $totalCharges,
                    'purpose' => 'Bill Out - Rental: ' . $itemName,
                    'rental_id' => $rental->id,
                    'metadata' => [
                        'item_name' => $itemName,
                        'quantity' => $rental->quantity,
                        'rate' => $rental->rate_snapshot,
                        'rate_type' => $rental->rate_type_snapshot,
                        'status' => $rental->status,
                        'issued_at' => $rental->issued_at->format('Y-m-d H:i:s'),
                        'returned_at' => $rental->returned_at ? $rental->returned_at->format('Y-m-d H:i:s') : null,
                    ],
                ]));
                $rentalTxnCount++;
            }
        }
        
        // Create unpaid item transactions
        foreach ($booking->unpaidItems as $item) {
            Transaction::create(array_merge($commonData, [
                'transaction_type' => 'add-on',
                'amount' => $item->TotalAmount,
                'purpose' => 'Bill Out - Store Purchase: ' . $item->ItemName,
                'metadata' => [
                    'item_id' => $item->ItemID,
                    'item_name' => $item->ItemName,
                    'quantity' => $item->Quantity,
                    'price' => $item->Price,
                ],
            ]));
            $unpaidItemTxnCount++;
        }
        
    } else {
        // Regular booking payment (downpayment, full payment, partial payment, etc.)
        $purpose = $payment->PaymentPurpose;
        
        // Normalize purpose if it's lowercase with underscore
        if (strtolower($purpose) === 'partial_payment' || $payment->PaymentStatus === 'Partial Payment') {
            $purpose = 'Partial Payment';
        } elseif (!$purpose) {
            $purpose = $payment->PaymentStatus === 'Fully Paid' ? 'Full Payment' : 'Downpayment';
        }
        
        Transaction::create(array_merge($commonData, [
            'transaction_type' => 'booking',
            'amount' => $payment->Amount,
            'purpose' => $purpose,
            'metadata' => [
                'package_name' => $booking->package->Name ?? null,
                'check_in' => $booking->CheckInDate->format('Y-m-d'),
                'check_out' => $booking->CheckOutDate->format('Y-m-d'),
                'payment_purpose' => $payment->PaymentPurpose,
            ],
        ]));
        $bookingTxnCount++;
    }
}

echo "   Booking transactions: {$bookingTxnCount}\n";
echo "   Rental transactions: {$rentalTxnCount}\n";
echo "   Store purchase transactions: {$unpaidItemTxnCount}\n\n";

// 2. Migrate standalone rental transactions (NOT included in bill-out)
echo "2. Migrating standalone rental transactions...\n";

// Get bookings that have bill-out settlements
$billOutBookingIds = Payment::where('PaymentPurpose', 'Bill Out Settlement')
    ->pluck('BookingID')
    ->toArray();

// Only migrate rentals that are NOT part of a bill-out settlement
$rentals = Rental::with(['rentalItem', 'booking.guest'])
    ->where('is_paid', true)
    ->whereNotNull('returned_at')
    ->whereNotIn('BookingID', $billOutBookingIds) // Exclude rentals in bill-out
    ->get();

$rentalCount = 0;
foreach ($rentals as $rental) {
    $guest = $rental->booking->guest ?? null;
    $itemName = $rental->rentalItem->name ?? 'Unknown Item';
    $processedBy = $rental->returnedByUser->name ?? 'Admin';
    
    // Rental fee
    $rentalFee = 0;
    if ($rental->rate_type_snapshot === 'Per-Day') {
        $days = max(1, $rental->issued_at->diffInDays($rental->returned_at));
        $rentalFee = $rental->rate_snapshot * $days * $rental->quantity;
    } else {
        $rentalFee = $rental->rate_snapshot * $rental->quantity;
    }
    
    if ($rentalFee > 0) {
        Transaction::create([
            'transaction_type' => 'rental',
            'reference_id' => 'R' . $rental->id,
            'transaction_date' => $rental->returned_at,
            'amount' => $rentalFee,
            'payment_method' => 'Cash',
            'payment_status' => 'Fully Paid',
            'purpose' => 'Rent Item - ' . $itemName,
            'booking_id' => $rental->BookingID,
            'guest_id' => $guest->GuestID ?? null,
            'rental_id' => $rental->id,
            'customer_name' => $guest ? trim($guest->FName . ' ' . $guest->LName) : null,
            'customer_email' => $guest->Email ?? null,
            'customer_phone' => $guest->Phone ?? null,
            'processed_by' => $rental->returned_by,
            'processor_name' => $processedBy,
            'metadata' => [
                'item_name' => $itemName,
                'quantity' => $rental->quantity,
                'rate' => $rental->rate_snapshot,
                'rate_type' => $rental->rate_type_snapshot,
            ],
        ]);
        $rentalCount++;
    }
    
    // Additional fees
    foreach ($rental->fees as $fee) {
        $feeAddedBy = $fee->addedByUser->name ?? 'Admin';
        Transaction::create([
            'transaction_type' => 'rental',
            'reference_id' => 'RF' . $fee->id,
            'transaction_date' => $rental->returned_at,
            'amount' => $fee->amount,
            'payment_method' => 'Cash',
            'payment_status' => 'Fully Paid',
            'purpose' => ucfirst($fee->type) . ' Fee - ' . $itemName,
            'booking_id' => $rental->BookingID,
            'guest_id' => $guest->GuestID ?? null,
            'rental_id' => $rental->id,
            'customer_name' => $guest ? trim($guest->FName . ' ' . $guest->LName) : null,
            'customer_email' => $guest->Email ?? null,
            'customer_phone' => $guest->Phone ?? null,
            'processed_by' => $fee->added_by,
            'processor_name' => $feeAddedBy,
            'metadata' => [
                'item_name' => $itemName,
                'fee_type' => $fee->type,
                'quantity' => $rental->quantity,
            ],
        ]);
        $rentalCount++;
    }
}
echo "   Migrated {$rentalCount} standalone rental transactions\n";
echo "   (Rentals in bill-out settlements are included in booking payments)\n\n";

echo "✅ Migration Complete!\n";
echo "Total transactions: " . Transaction::count() . "\n";
