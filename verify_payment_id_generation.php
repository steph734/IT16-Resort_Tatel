<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Payment;
use Illuminate\Support\Facades\DB;

echo "=== TESTING PAYMENT ID GENERATION ===\n\n";

echo "Current Payments:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$latestPayments = Payment::orderBy('PaymentID', 'desc')->take(5)->get();
foreach ($latestPayments as $payment) {
    $status = $payment->is_voided ? 'VOIDED' : 'ACTIVE';
    echo "  {$payment->PaymentID}: ₱" . number_format($payment->Amount, 2) . " - {$status}\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Test ID generation logic
echo "Testing generatePaymentID() logic:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$lastPayment = Payment::orderBy('PaymentID', 'desc')->first();
$startNumber = $lastPayment ? intval(substr($lastPayment->PaymentID, 2)) : 0;

echo "  Latest PaymentID: {$lastPayment->PaymentID}\n";
echo "  Starting number: {$startNumber}\n\n";

// Simulate the generation
$attempts = 0;
do {
    $increment = 1 + $attempts;
    $newNumber = $startNumber + $increment;
    $newId = 'PY' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    $exists = Payment::where('PaymentID', $newId)->exists();
    
    if ($exists) {
        $existingPayment = Payment::where('PaymentID', $newId)->first();
        $status = $existingPayment->is_voided ? 'voided' : 'active';
        $tryNum = $attempts + 1;
        echo "  Try #{$tryNum}: {$newId} - EXISTS ({$status}) - skipping\n";
    } else {
        $tryNum = $attempts + 1;
        echo "  Try #{$tryNum}: {$newId} - AVAILABLE ✅\n";
    }
    
    $attempts++;
    
    if ($attempts >= 10) {
        echo "\n  Stopping after 10 attempts (for demonstration)\n";
        break;
    }
    
} while ($exists);

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

if (!$exists) {
    echo "✅ Next payment will use: {$newId}\n";
} else {
    echo "⚠️  Would continue searching for available ID\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "Verification:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

// Check for any duplicate PaymentIDs
$duplicates = DB::select("
    SELECT PaymentID, COUNT(*) as count 
    FROM payments 
    GROUP BY PaymentID 
    HAVING COUNT(*) > 1
");

if (empty($duplicates)) {
    echo "  ✅ No duplicate PaymentIDs found\n";
} else {
    echo "  ⚠️  Found duplicate PaymentIDs:\n";
    foreach ($duplicates as $dup) {
        echo "    - {$dup->PaymentID}: {$dup->count} records\n";
    }
}

// Check voided payments
$voidedCount = Payment::where('is_voided', true)->count();
$activeCount = Payment::where('is_voided', false)->count();
echo "  ✅ Active payments: {$activeCount}\n";
echo "  ✅ Voided payments: {$voidedCount}\n";

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
