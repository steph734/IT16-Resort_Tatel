<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== DETAILED TRANSACTION CHECK ===\n\n";

// Get all Bill Out transactions
$transactions = DB::table('transactions')
    ->where('purpose', 'like', '%Bill Out%')
    ->get();

if ($transactions->isEmpty()) {
    echo "❌ No Bill Out transactions found.\n";
} else {
    echo "✅ Found {$transactions->count()} Bill Out transaction(s):\n\n";
    foreach ($transactions as $transaction) {
        echo "Transaction ID: {$transaction->transaction_id}\n";
        echo "Type: {$transaction->transaction_type}\n";
        echo "Reference ID: {$transaction->reference_id}\n";
        echo "Purpose: {$transaction->purpose}\n";
        echo "Amount: ₱" . number_format($transaction->amount, 2) . "\n";
        echo "Transaction Date: {$transaction->transaction_date}\n";
        echo "Booking ID: {$transaction->booking_id}\n";
        echo "Rental ID: {$transaction->rental_id}\n";
        echo "---\n\n";
    }
}

// Check the payment with PaymentID = 'PY184'
echo "\n=== CHECKING PAYMENT PY184 ===\n\n";
$payment = DB::table('payments')->where('PaymentID', 'PY184')->first();

if ($payment) {
    echo "✅ Payment ID: {$payment->PaymentID}\n";
    echo "Booking ID: {$payment->BookingID}\n";
    echo "Amount: ₱" . number_format($payment->Amount, 2) . "\n";
    echo "Purpose: {$payment->PaymentPurpose}\n";
    echo "Method: {$payment->PaymentMethod}\n";
    echo "Date: {$payment->PaymentDate}\n";
    
    // Check bookings for the payment
    echo "\n=== CHECKING BOOKING FOR PY184 ===\n\n";
    $booking = DB::table('bookings')->where('BookingID', $payment->BookingID)->first();
    
    if ($booking) {
        echo "✅ Booking ID: {$booking->BookingID}\n";
        echo "Guest ID: {$booking->GuestID}\n";
        echo "Status: {$booking->BookingStatus}\n";
        
        // Get guest info
        $guest = DB::table('guests')->where('GuestID', $booking->GuestID)->first();
        if ($guest) {
            echo "Guest Name: {$guest->FName} {$guest->LName}\n";
        }
        
        // Get rentals
        echo "\n=== CHECKING RENTALS FOR THIS BOOKING ===\n\n";
        $rentals = DB::table('rentals')->where('BookingID', $booking->BookingID)->get();
        
        if ($rentals->isEmpty()) {
            echo "❌ No rentals found.\n";
        } else {
            echo "✅ Found {$rentals->count()} rental(s):\n\n";
            foreach ($rentals as $rental) {
                echo "Rental ID: {$rental->id}\n";
                echo "Item ID: {$rental->rental_item_id}\n";
                echo "Quantity: {$rental->quantity}\n";
                echo "Rate: ₱" . number_format($rental->rate_snapshot, 2) . "\n";
                echo "Rate Type: {$rental->rate_type_snapshot}\n";
                echo "Status: {$rental->status}\n";
                echo "Is Paid: " . ($rental->is_paid ? 'Yes' : 'No') . "\n";
                echo "Issued At: {$rental->issued_at}\n";
                echo "Returned At: " . ($rental->returned_at ?? 'N/A') . "\n";
                
                // Get rental item name
                $rentalItem = DB::table('rental_items')->where('id', $rental->rental_item_id)->first();
                if ($rentalItem) {
                    $itemName = $rentalItem->name ?? $rentalItem->item_name ?? 'Unknown';
                    echo "Item Name: {$itemName}\n";
                }
                
                // Get fees
                $fees = DB::table('rental_fees')->where('rental_id', $rental->id)->get();
                if ($fees->isNotEmpty()) {
                    echo "Fees:\n";
                    foreach ($fees as $fee) {
                        echo "  - {$fee->type}: ₱" . number_format($fee->amount, 2) . " ({$fee->reason})\n";
                    }
                }
                
                echo "---\n";
            }
        }
    } else {
        echo "❌ Booking not found.\n";
    }
} else {
    echo "❌ Payment PY184 not found.\n";
}
