<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== VOIDING & PAYMENT BEHAVIOR ANALYSIS ===\n\n";

// Check current state of PY184
echo "1. CURRENT STATE OF PY184:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$payment = DB::table('payments')->where('PaymentID', 'PY184')->first();
if ($payment) {
    echo "✅ Payment EXISTS in database:\n";
    echo "  PaymentID: {$payment->PaymentID}\n";
    echo "  BookingID: {$payment->BookingID}\n";
    echo "  Amount: ₱" . number_format($payment->Amount, 2) . "\n";
    echo "  Method: {$payment->PaymentMethod}\n";
    echo "  Date: {$payment->PaymentDate}\n";
} else {
    echo "❌ Payment DELETED from database\n";
}

$transactions = DB::table('transactions')->where('reference_id', 'PY184')->get();
echo "\nTransactions with reference PY184: {$transactions->count()}\n";
foreach ($transactions as $tx) {
    $txId = $tx->TransactionID ?? 'N/A';
    echo "  - TX#{$txId}: {$tx->purpose} (₱" . number_format($tx->amount, 2) . ") - ";
    echo ($tx->is_voided ? "VOIDED" : "ACTIVE") . "\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "2. WHAT HAPPENS WHEN VOIDING:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Based on SalesController.voidTransaction() code:\n\n";

echo "Step 1: Mark all transactions as voided\n";
echo "  • Sets is_voided = true\n";
echo "  • Records voided_at timestamp\n";
echo "  • Records voided_by (admin user)\n";
echo "  • Records void_reason\n";
echo "  ⚠️  Transactions stay in database (audit trail)\n\n";

echo "Step 2: DELETE the payment record\n";
echo "  • Removes payment from payments table\n";
echo "  • Uses \$payment->delete() - permanent deletion\n";
echo "  • No soft delete - payment is GONE\n\n";

echo "Step 3: If Bill Out Settlement:\n";
echo "  • Mark all rentals as unpaid (is_paid = false)\n";
echo "  • Mark all unpaid items as unpaid (IsPaid = false)\n";
echo "  • Clear senior discount from booking\n";
echo "  • Recalculate booking payment status\n\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "3. WHAT HAPPENS WHEN REDOING PAYMENT:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "When admin processes Bill Out again:\n\n";

echo "Step 1: Generate NEW PaymentID\n";
echo "  • Uses Payment::generatePaymentId()\n";
echo "  • Creates sequential ID (PY185, PY186, etc.)\n";
echo "  • Will NOT reuse PY184\n\n";

echo "Step 2: Create NEW Payment record\n";
echo "  • New PaymentID (e.g., PY185)\n";
echo "  • Same BookingID (B177)\n";
echo "  • New Amount (based on current outstanding)\n";
echo "  • New PaymentDate (current timestamp)\n";
echo "  • Payment method from form\n\n";

echo "Step 3: Create NEW Transaction records\n";
echo "  • New transaction IDs\n";
echo "  • reference_id = new PaymentID (PY185)\n";
echo "  • Purpose: 'Bill Out Settlement'\n";
echo "  • Separate transactions for booking + rentals\n";
echo "  • Linked by same reference_id\n\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "4. PAYMENT ID SEQUENCE:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

// Check latest payment ID
$latestPayment = DB::table('payments')->orderBy('PaymentID', 'desc')->first();
if ($latestPayment) {
    echo "Latest Payment in database: {$latestPayment->PaymentID}\n";
    preg_match('/\d+/', $latestPayment->PaymentID, $matches);
    $nextNumber = isset($matches[0]) ? intval($matches[0]) + 1 : 185;
    echo "Next Payment ID will be: PY{$nextNumber}\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "5. AUDIT TRAIL PRESERVATION:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ GOOD: Voided transactions kept in database\n";
echo "  • Can see what was voided\n";
echo "  • Can see who voided it and why\n";
echo "  • Full audit trail maintained\n\n";

echo "❌ LOST: Payment record is deleted\n";
echo "  • Original payment details lost\n";
echo "  • No history of payment method used\n";
echo "  • No record of when payment was made\n";
echo "  • Cannot trace payment → voiding connection\n\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "6. RECOMMENDED IMPROVEMENT:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Instead of DELETING payment, consider:\n\n";

echo "Option A: Soft Delete (preferred)\n";
echo "  • Add 'deleted_at' column to payments table\n";
echo "  • Use Laravel soft deletes\n";
echo "  • Payment remains in database but hidden\n";
echo "  • Can restore if needed\n\n";

echo "Option B: Status Flag\n";
echo "  • Add 'is_voided' column to payments table\n";
echo "  • Keep payment but mark as voided\n";
echo "  • Exclude voided payments from reports\n";
echo "  • Full history preserved\n\n";

echo "Option C: Void History Table\n";
echo "  • Create 'voided_payments' table\n";
echo "  • Move payment data there before deleting\n";
echo "  • Includes void_reason, voided_by, voided_at\n";
echo "  • Separate audit log\n\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
