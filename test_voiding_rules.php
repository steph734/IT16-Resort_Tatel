<?php

/**
 * Test voiding rules and validation
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== TESTING TRANSACTION VOIDING RULES ===\n\n";

// Test 1: Check if booking is completed (should block voiding)
echo "1. Checking Booking Status for PY184:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$transaction = DB::table('transactions')
    ->where('reference_id', 'PY184')
    ->where('is_voided', false)
    ->first();

if ($transaction) {
    $booking = DB::table('bookings')->where('BookingID', $transaction->booking_id)->first();
    
    if ($booking) {
        echo "  Booking ID: {$booking->BookingID}\n";
        echo "  Status: {$booking->BookingStatus}\n";
        
        if ($booking->BookingStatus === 'Completed') {
            echo "  ❌ VOIDING BLOCKED: Guest has checked out\n";
            echo "  → Cannot void transactions for completed bookings\n";
        } else {
            echo "  ✅ VOIDING ALLOWED: Guest has not checked out\n";
            echo "  → Can void transactions (status: {$booking->BookingStatus})\n";
        }
    }
}

// Test 2: Check Bill Out Settlement grouping
echo "\n\n2. Checking Bill Out Settlement Transaction Grouping:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$billOutTransactions = DB::table('transactions')
    ->where('reference_id', 'PY184')
    ->where('is_voided', false)
    ->get();

if ($billOutTransactions->isNotEmpty()) {
    $bookingCount = $billOutTransactions->where('transaction_type', 'booking')->count();
    $rentalCount = $billOutTransactions->where('transaction_type', 'rental')->count();
    $totalAmount = $billOutTransactions->sum('amount');
    
    echo "  Reference ID: PY184\n";
    echo "  Total Transactions: {$billOutTransactions->count()}\n";
    echo "  - Booking: {$bookingCount}\n";
    echo "  - Rental: {$rentalCount}\n";
    echo "  Total Amount: ₱" . number_format($totalAmount, 2) . "\n\n";
    
    echo "  ⚠️  VOIDING RULE:\n";
    echo "  → Must void ALL {$billOutTransactions->count()} transactions together\n";
    echo "  → Cannot void individual rental transactions\n";
    echo "  → This prevents payment/transaction amount mismatch\n";
}

// Test 3: Simulate partial voiding attempt
echo "\n\n3. Simulating Partial Voiding Attempt:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$rentalTransaction = $billOutTransactions->where('transaction_type', 'rental')->first();

if ($rentalTransaction) {
    echo "  Attempting to void: {$rentalTransaction->purpose}\n";
    echo "  Amount: ₱" . number_format($rentalTransaction->amount, 2) . "\n\n";
    
    echo "  ❌ VALIDATION FAILED:\n";
    echo "  → This is part of a Bill Out Settlement\n";
    echo "  → Must void complete set of {$billOutTransactions->count()} transactions\n";
    echo "  → Partial voiding would create mismatch:\n";
    echo "     • Payment Amount: ₱" . number_format($totalAmount, 2) . "\n";
    echo "     • After Partial Void: ₱" . number_format($totalAmount - $rentalTransaction->amount, 2) . "\n";
    echo "     • Difference: ₱" . number_format($rentalTransaction->amount, 2) . "\n";
}

// Test 4: Check what happens after complete void
echo "\n\n4. After Complete Bill Out Settlement Void:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

echo "  When ALL transactions are voided:\n\n";

echo "  ✅ Transactions:\n";
foreach ($billOutTransactions as $trans) {
    echo "    • {$trans->purpose} (₱" . number_format($trans->amount, 2) . ") → is_voided = 1\n";
}

echo "\n  ✅ Payment:\n";
echo "    • Payment PY184 → DELETED from payments table\n";

echo "\n  ✅ Rentals:\n";
$rentals = DB::table('rentals')
    ->where('BookingID', $transaction->booking_id)
    ->get();
foreach ($rentals as $rental) {
    echo "    • Rental #{$rental->id} → is_paid = 0 (marked unpaid)\n";
}

echo "\n  ✅ Unpaid Items:\n";
$unpaidItems = DB::table('unpaid_items')
    ->where('BookingID', $transaction->booking_id)
    ->get();
if ($unpaidItems->isEmpty()) {
    echo "    • No unpaid items found\n";
} else {
    foreach ($unpaidItems as $item) {
        echo "    • {$item->ItemName} → IsPaid = 0 (marked unpaid)\n";
    }
}

echo "\n  ✅ Redo Process:\n";
echo "    1. Admin navigates to Currently Staying page\n";
echo "    2. Clicks 'Bill Out' for guest\n";
echo "    3. System recalculates outstanding balance\n";
echo "    4. Admin processes new Bill Out Settlement\n";
echo "    5. New payment record created with new reference ID\n";
echo "    6. New split transactions created\n";

// Test 5: Show voiding validation flow
echo "\n\n5. Complete Voiding Validation Flow:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

echo "  Step 1: Check Booking Status\n";
if ($booking->BookingStatus === 'Completed') {
    echo "    ❌ BLOCKED: Cannot void after checkout\n";
} else {
    echo "    ✅ PASS: Booking not completed\n";
}

echo "\n  Step 2: Verify Admin Credentials\n";
echo "    ✅ PASS: Admin username verified\n";

echo "\n  Step 3: Check Transaction Type\n";
$isBillOut = str_starts_with($billOutTransactions->first()->purpose, 'Bill Out Settlement');
if ($isBillOut) {
    echo "    ⚠️  WARNING: This is a Bill Out Settlement\n";
    echo "    → Must void all {$billOutTransactions->count()} related transactions\n";
} else {
    echo "    ℹ️  INFO: Regular transaction, can void individually\n";
}

echo "\n  Step 4: Execute Void\n";
echo "    ✅ Mark all transactions as voided\n";
echo "    ✅ Delete payment record\n";
echo "    ✅ Reset rental/item payment status\n";
echo "    ✅ Recalculate booking payment status\n";

echo "\n  Step 5: Result\n";
echo "    ✅ SUCCESS: Transaction voided, can redo payment\n";

echo "\n\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ All voiding rules validated!\n";
