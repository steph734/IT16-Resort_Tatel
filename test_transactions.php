<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Payment;
use Carbon\Carbon;

echo "=== Testing Transaction Queries ===\n\n";

// Test for December 2025 (current month)
$startDate = Carbon::parse('2025-12-01')->startOfDay();
$endDate = Carbon::parse('2025-12-31')->endOfDay();

echo "Date Range: {$startDate->toDateString()} to {$endDate->toDateString()}\n\n";

// Query with OLD status list (should miss some)
$oldStatuses = ['Verified', 'Paid', 'Fully Paid', 'Downpayment', 'For Verification'];
$oldCount = Payment::whereBetween('PaymentDate', [$startDate, $endDate])
    ->whereIn('PaymentStatus', $oldStatuses)
    ->count();
$oldSum = Payment::whereBetween('PaymentDate', [$startDate, $endDate])
    ->whereIn('PaymentStatus', $oldStatuses)
    ->sum('Amount');

echo "OLD Query (missing Completed & Partial):\n";
echo "  - Count: {$oldCount}\n";
echo "  - Total: ₱" . number_format($oldSum, 2) . "\n\n";

// Query with NEW status list (should get all)
$newStatuses = ['Verified', 'Paid', 'Fully Paid', 'Downpayment', 'Partial', 'Completed', 'For Verification'];
$newCount = Payment::whereBetween('PaymentDate', [$startDate, $endDate])
    ->whereIn('PaymentStatus', $newStatuses)
    ->count();
$newSum = Payment::whereBetween('PaymentDate', [$startDate, $endDate])
    ->whereIn('PaymentStatus', $newStatuses)
    ->sum('Amount');

echo "NEW Query (includes all statuses):\n";
echo "  - Count: {$newCount}\n";
echo "  - Total: ₱" . number_format($newSum, 2) . "\n\n";

// Show breakdown by status
echo "Breakdown by Status:\n";
$statusBreakdown = Payment::whereBetween('PaymentDate', [$startDate, $endDate])
    ->selectRaw('PaymentStatus, COUNT(*) as count, SUM(Amount) as total')
    ->groupBy('PaymentStatus')
    ->get();

foreach ($statusBreakdown as $status) {
    echo "  - {$status->PaymentStatus}: {$status->count} transactions, ₱" . number_format($status->total, 2) . "\n";
}

echo "\n";

// Show recent transactions
echo "Recent Payments (December 2025):\n";
$recentPayments = Payment::whereBetween('PaymentDate', [$startDate, $endDate])
    ->orderBy('PaymentDate', 'desc')
    ->select('PaymentID', 'BookingID', 'Amount', 'PaymentStatus', 'PaymentPurpose', 'PaymentDate')
    ->get();

foreach ($recentPayments as $payment) {
    echo "  - {$payment->PaymentID} ({$payment->BookingID}): ₱" . number_format($payment->Amount, 2);
    echo " [{$payment->PaymentStatus}] - {$payment->PaymentPurpose} on {$payment->PaymentDate}\n";
}
