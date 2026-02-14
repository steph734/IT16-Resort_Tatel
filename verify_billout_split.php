<?php

/**
 * Verification script to check the Bill Out transaction splitting
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== BILL OUT TRANSACTION VERIFICATION ===\n\n";

// Check PY184 transactions
echo "1. Checking PY184 (Split Transaction):\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$py184Transactions = DB::table('transactions')
    ->where('reference_id', 'PY184')
    ->orderBy('transaction_type')
    ->orderBy('transaction_id')
    ->get();

$bookingCount = 0;
$rentalCount = 0;
$totalAmount = 0;

foreach ($py184Transactions as $trans) {
    if ($trans->transaction_type === 'booking') {
        $bookingCount++;
        echo "  ✅ BOOKING: {$trans->purpose} - ₱" . number_format($trans->amount, 2) . "\n";
    } else {
        $rentalCount++;
        echo "  ✅ RENTAL: {$trans->purpose} - ₱" . number_format($trans->amount, 2) . "\n";
    }
    $totalAmount += $trans->amount;
}

echo "\n  Summary:\n";
echo "    - Booking Transactions: {$bookingCount}\n";
echo "    - Rental Transactions: {$rentalCount}\n";
echo "    - Total Amount: ₱" . number_format($totalAmount, 2) . "\n";

// Get payment amount to verify
$payment = DB::table('payments')->where('PaymentID', 'PY184')->first();
if ($payment) {
    echo "    - Payment Amount: ₱" . number_format($payment->Amount, 2) . "\n";
    if (abs($totalAmount - $payment->Amount) < 0.01) {
        echo "    ✅ Amounts match!\n";
    } else {
        echo "    ❌ Amounts don't match! Difference: ₱" . number_format(abs($totalAmount - $payment->Amount), 2) . "\n";
    }
}

// Check transaction counts by type
echo "\n\n2. Overall Transaction Statistics:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$bookingTransactions = DB::table('transactions')
    ->where('transaction_type', 'booking')
    ->where('is_voided', false)
    ->count();

$rentalTransactions = DB::table('transactions')
    ->where('transaction_type', 'rental')
    ->where('is_voided', false)
    ->count();

echo "  Total Booking Transactions: {$bookingTransactions}\n";
echo "  Total Rental Transactions: {$rentalTransactions}\n";

// Check for any remaining unsplit Bill Out transactions
echo "\n\n3. Checking for Unsplit Bill Out Transactions:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$billOutTransactions = DB::table('transactions')
    ->where('purpose', 'Bill Out Settlement')
    ->where('transaction_type', 'booking')
    ->get();

$unsplitCount = 0;

foreach ($billOutTransactions as $trans) {
    $rentalCount = DB::table('transactions')
        ->where('reference_id', $trans->reference_id)
        ->where('transaction_type', 'rental')
        ->count();
    
    if ($rentalCount === 0) {
        // Check if this booking has rentals
        $payment = DB::table('payments')->where('PaymentID', $trans->reference_id)->first();
        if ($payment) {
            $hasRentals = DB::table('rentals')
                ->where('BookingID', $payment->BookingID)
                ->where('is_paid', true)
                ->exists();
            
            if ($hasRentals) {
                echo "  ⚠️  Transaction {$trans->transaction_id} (Ref: {$trans->reference_id}) has rentals but not split\n";
                $unsplitCount++;
            }
        }
    }
}

if ($unsplitCount === 0) {
    echo "  ✅ All Bill Out transactions with rentals are properly split!\n";
} else {
    echo "  ❌ Found {$unsplitCount} unsplit transaction(s)\n";
}

echo "\n\n✨ Verification Complete!\n";
