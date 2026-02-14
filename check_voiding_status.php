<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

echo "Checking Voiding Status for PY184:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

// Get all transactions for PY184
$transactions = Transaction::where('reference_id', 'PY184')->get();

echo "Total transactions with reference PY184: {$transactions->count()}\n\n";

foreach ($transactions as $transaction) {
    echo "Transaction ID: {$transaction->id}\n";
    echo "  Type: {$transaction->type}\n";
    echo "  Purpose: {$transaction->purpose}\n";
    echo "  Amount: ₱" . number_format($transaction->amount, 2) . "\n";
    echo "  Is Voided: " . ($transaction->is_voided ? 'YES' : 'NO') . "\n";
    if ($transaction->is_voided) {
        echo "  Voided At: {$transaction->voided_at}\n";
        echo "  Voided By: {$transaction->voided_by}\n";
        echo "  Void Reason: {$transaction->void_reason}\n";
    }
    echo "\n";
}

// Check if the payment still exists
$payment = DB::table('payments')->where('PaymentID', 'PY184')->first();
if ($payment) {
    echo "⚠️  Payment PY184 still exists in database!\n";
    echo "  Amount: ₱" . number_format($payment->Amount, 2) . "\n";
} else {
    echo "✅ Payment PY184 has been deleted.\n";
}

// Check booking status
$booking = DB::table('bookings')->where('BookingID', 'B177')->first();
echo "\nBooking B177 Status:\n";
echo "  Booking Status: {$booking->BookingStatus}\n";
echo "  Senior Discount: ₱" . number_format($booking->senior_discount ?? 0, 2) . "\n";
echo "  Actual Seniors: " . ($booking->actual_seniors_at_checkout ?? 0) . "\n";

// Check the first transaction's purpose
$firstTransaction = $transactions->first();
if ($firstTransaction) {
    echo "\nFirst Transaction Purpose: '{$firstTransaction->purpose}'\n";
    $isBillOut = $firstTransaction->purpose === 'Bill Out Settlement' || 
                 str_starts_with($firstTransaction->purpose, 'Bill Out Settlement -');
    echo "Is Bill Out Settlement: " . ($isBillOut ? 'YES' : 'NO') . "\n";
}
