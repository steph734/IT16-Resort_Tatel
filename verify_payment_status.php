<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Payment;
use Carbon\Carbon;

echo "\n";
echo str_repeat("=", 100) . "\n";
echo "PAYMENT STATUS VERIFICATION FOR SALES DASHBOARD\n";
echo str_repeat("=", 100) . "\n\n";

// Check all payment statuses in the database
echo "1. PAYMENT STATUS DISTRIBUTION\n";
echo str_repeat("-", 100) . "\n";
$paymentsByStatus = Payment::selectRaw('PaymentStatus, COUNT(*) as count, SUM(Amount) as total')
    ->groupBy('PaymentStatus')
    ->get();

echo sprintf("   %-20s | %8s | %15s\n", "Payment Status", "Count", "Total Amount");
echo str_repeat("-", 100) . "\n";
foreach ($paymentsByStatus as $status) {
    echo sprintf("   %-20s | %8d | ₱%13.2f\n", $status->PaymentStatus, $status->count, $status->total);
}

// Check current month payments
$startDate = Carbon::now()->startOfMonth();
$endDate = Carbon::now()->endOfMonth();

echo "\n\n2. CURRENT MONTH PAYMENTS (December 2025)\n";
echo str_repeat("-", 100) . "\n";
echo "Date Range: {$startDate->toDateString()} to {$endDate->toDateString()}\n\n";

// Old query (without 'Verified')
$oldQuery = Payment::whereBetween('PaymentDate', [$startDate, $endDate])
    ->whereIn('PaymentStatus', ['Fully Paid', 'Downpayment'])
    ->sum('Amount');

// New query (with 'Verified')
$newQuery = Payment::whereBetween('PaymentDate', [$startDate, $endDate])
    ->whereIn('PaymentStatus', ['Fully Paid', 'Downpayment', 'Verified'])
    ->sum('Amount');

echo sprintf("   Old Query (without 'Verified'): ₱%15.2f\n", $oldQuery);
echo sprintf("   New Query (with 'Verified'):    ₱%15.2f\n", $newQuery);
echo sprintf("   Difference:                     ₱%15.2f\n", $newQuery - $oldQuery);

// Show sample payments
echo "\n\n3. SAMPLE PAYMENTS FROM PAST BOOKINGS\n";
echo str_repeat("-", 100) . "\n";
$samplePayments = Payment::with('booking')
    ->orderBy('PaymentDate', 'desc')
    ->take(10)
    ->get();

echo sprintf("   %-12s | %-12s | %-15s | %-20s | %12s\n", "Payment ID", "Booking ID", "Payment Status", "Payment Date", "Amount");
echo str_repeat("-", 100) . "\n";
foreach ($samplePayments as $payment) {
    echo sprintf("   %-12s | %-12s | %-15s | %-20s | ₱%10.2f\n",
        $payment->PaymentID,
        $payment->BookingID,
        $payment->PaymentStatus,
        $payment->PaymentDate,
        $payment->Amount
    );
}

// Check payments by date
echo "\n\n4. PAYMENTS BY MONTH (Past Bookings Date Range)\n";
echo str_repeat("-", 100) . "\n";
$paymentsByMonth = Payment::selectRaw('DATE_FORMAT(PaymentDate, "%Y-%m") as month, COUNT(*) as count, SUM(Amount) as total')
    ->groupBy('month')
    ->orderBy('month', 'asc')
    ->get();

echo sprintf("   %-10s | %8s | %15s\n", "Month", "Count", "Total Amount");
echo str_repeat("-", 100) . "\n";
foreach ($paymentsByMonth as $month) {
    echo sprintf("   %-10s | %8d | ₱%13.2f\n", $month->month, $month->count, $month->total);
}

echo "\n" . str_repeat("=", 100) . "\n";
echo "VERIFICATION COMPLETE\n";
echo str_repeat("=", 100) . "\n\n";
