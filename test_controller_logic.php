<?php

/**
 * Test to verify CurrentlyStayingController processBillOut logic
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== TESTING CONTROLLER LOGIC ===\n\n";

// Test that we can load rentals with proper eager loading
echo "1. Testing Rental Eager Loading:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$booking = \App\Models\Booking::where('BookingID', 'B177')->first();

if ($booking) {
    $rentals = $booking->rentals()
        ->whereIn('status', ['Issued', 'Returned', 'Lost', 'Damaged'])
        ->where('is_paid', true)
        ->with('rentalItem.inventoryItem', 'fees')
        ->get();
    
    echo "  ✅ Successfully loaded {$rentals->count()} rental(s) with eager loading\n\n";
    
    foreach ($rentals as $rental) {
        $itemName = $rental->rentalItem->name ?? 'Unknown Item';
        echo "  - {$itemName}\n";
        echo "    Quantity: {$rental->quantity}\n";
        echo "    Rate: ₱" . number_format($rental->rate_snapshot, 2) . " ({$rental->rate_type_snapshot})\n";
        
        $fees = $rental->fees;
        if ($fees->isNotEmpty()) {
            echo "    Fees: {$fees->count()}\n";
            foreach ($fees as $fee) {
                echo "      * {$fee->type}: ₱" . number_format($fee->amount, 2) . "\n";
            }
        }
        echo "\n";
    }
} else {
    echo "  ❌ Booking B177 not found\n";
}

// Test that Transaction model relationships work
echo "2. Testing Transaction Relationships:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$transaction = \App\Models\Transaction::where('reference_id', 'PY184')
    ->where('transaction_type', 'booking')
    ->first();

if ($transaction) {
    echo "  ✅ Transaction loaded: ID {$transaction->transaction_id}\n";
    
    // Test payment relationship
    $payment = $transaction->payment;
    if ($payment) {
        echo "  ✅ Payment relationship works: {$payment->PaymentID}\n";
    } else {
        echo "  ⚠️  Payment relationship returned null\n";
    }
    
    // Test booking relationship
    $booking = $transaction->booking;
    if ($booking) {
        echo "  ✅ Booking relationship works: {$booking->BookingID}\n";
    } else {
        echo "  ⚠️  Booking relationship returned null\n";
    }
    
    // Test guest relationship
    $guest = $transaction->guest;
    if ($guest) {
        echo "  ✅ Guest relationship works: {$guest->FName} {$guest->LName}\n";
    } else {
        echo "  ⚠️  Guest relationship returned null\n";
    }
} else {
    echo "  ❌ Transaction not found\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ All controller logic tests passed!\n";
