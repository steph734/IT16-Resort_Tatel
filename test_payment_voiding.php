<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Payment;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;

echo "=== PAYMENT VOIDING BEHAVIOR TEST ===\n\n";

echo "1. CHECKING PAYMENT MODEL CHANGES:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

// Check if voiding fields exist
$columns = DB::getSchemaBuilder()->getColumnListing('payments');
$voidingFields = ['is_voided', 'voided_at', 'voided_by', 'void_reason'];
foreach ($voidingFields as $field) {
    $exists = in_array($field, $columns);
    echo "  " . ($exists ? "✅" : "❌") . " Column '{$field}' exists\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "2. TESTING PAYMENT SCOPES:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$totalPayments = Payment::count();
$activePayments = Payment::active()->count();
$voidedPayments = Payment::voided()->count();

echo "  Total Payments: {$totalPayments}\n";
echo "  Active Payments: {$activePayments}\n";
echo "  Voided Payments: {$voidedPayments}\n";

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "3. BOOKING B177 PAYMENT ANALYSIS:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$booking = Booking::where('BookingID', 'B177')->first();
if ($booking) {
    // Get active payments (default relationship)
    $activePayments = $booking->payments;
    echo "  Active Payments (via \$booking->payments): {$activePayments->count()}\n";
    foreach ($activePayments as $payment) {
        echo "    - {$payment->PaymentID}: ₱" . number_format($payment->Amount, 2);
        echo " ({$payment->PaymentMethod})\n";
    }
    
    $activeTotalPaid = $activePayments->sum('Amount');
    echo "  Active Total Paid: ₱" . number_format($activeTotalPaid, 2) . "\n\n";
    
    // Get all payments including voided
    $allPayments = $booking->allPayments;
    echo "  All Payments (via \$booking->allPayments): {$allPayments->count()}\n";
    foreach ($allPayments as $payment) {
        echo "    - {$payment->PaymentID}: ₱" . number_format($payment->Amount, 2);
        echo " ({$payment->PaymentMethod})";
        if ($payment->is_voided) {
            echo " - VOIDED";
            if ($payment->voided_at) {
                echo " on " . $payment->voided_at->format('M d, Y H:i');
            }
            if ($payment->voided_by) {
                echo " by {$payment->voided_by}";
            }
        }
        echo "\n";
    }
    
    $allTotalPaid = $allPayments->sum('Amount');
    echo "  All Payments Total: ₱" . number_format($allTotalPaid, 2) . "\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "4. PY184 PAYMENT STATUS:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$py184 = Payment::where('PaymentID', 'PY184')->first();
if ($py184) {
    echo "  Payment ID: {$py184->PaymentID}\n";
    echo "  Amount: ₱" . number_format($py184->Amount, 2) . "\n";
    echo "  Is Voided: " . ($py184->is_voided ? "YES" : "NO") . "\n";
    
    if ($py184->is_voided) {
        echo "  Voided At: " . ($py184->voided_at ? $py184->voided_at->format('M d, Y H:i:s') : 'N/A') . "\n";
        echo "  Voided By: " . ($py184->voided_by ?? 'N/A') . "\n";
        echo "  Void Reason: " . ($py184->void_reason ?? 'N/A') . "\n";
    }
} else {
    echo "  ❌ Payment PY184 not found\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "5. KEY BENEFITS OF NEW APPROACH:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ Payment record preserved in database\n";
echo "✅ Full audit trail of voided payments\n";
echo "✅ Can trace who voided and why\n";
echo "✅ Can see original payment details\n";
echo "✅ Voided payments automatically excluded from calculations\n";
echo "✅ Can query payment history including voids\n";
echo "✅ Better compliance with accounting standards\n";

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "6. WHAT HAPPENS ON NEXT BILL OUT:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "When admin processes bill out again:\n\n";

// Get latest payment ID
$latestPayment = Payment::orderBy('PaymentID', 'desc')->first();
if ($latestPayment) {
    preg_match('/\d+/', $latestPayment->PaymentID, $matches);
    $nextNumber = isset($matches[0]) ? intval($matches[0]) + 1 : 185;
    $nextPaymentId = 'PY' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    
    echo "  Latest Payment: {$latestPayment->PaymentID}\n";
    echo "  Next Payment ID: {$nextPaymentId}\n\n";
}

echo "Step 1: Calculate outstanding (excludes voided payments)\n";
echo "  • \$booking->payments->sum('Amount') only counts active\n";
echo "  • Outstanding: ₱8,325.00 (in this example)\n\n";

echo "Step 2: Create new payment record\n";
echo "  • PaymentID: {$nextPaymentId} (auto-generated)\n";
echo "  • Amount: ₱8,325.00\n";
echo "  • is_voided: false (default)\n\n";

echo "Step 3: Create new transactions\n";
echo "  • reference_id: {$nextPaymentId}\n";
echo "  • Separate transactions for booking + rentals\n\n";

echo "Step 4: Mark rentals as paid\n";
echo "  • is_paid: true\n\n";

echo "Result:\n";
echo "  • PY184 remains in database (voided)\n";
echo "  • {$nextPaymentId} is the active payment\n";
echo "  • Complete payment history preserved\n";

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
