<?php
/**
 * Quick test script to check if sales data exists in the database
 * Run with: php test_sales_data.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Payment;
use App\Models\UnpaidItem;
use App\Models\Rental;
use Carbon\Carbon;

echo "=== Sales Data Test ===\n\n";

// Check Payments
echo "1. Checking Payments table...\n";
$totalPayments = Payment::count();
$paidPayments = Payment::whereIn('PaymentStatus', ['Verified', 'Paid', 'Fully Paid', 'Downpayment'])->count();
$totalAmount = Payment::whereIn('PaymentStatus', ['Verified', 'Paid', 'Fully Paid', 'Downpayment'])->sum('Amount');

echo "   Total payments: {$totalPayments}\n";
echo "   Paid/Verified payments: {$paidPayments}\n";
echo "   Total amount: ₱" . number_format($totalAmount, 2) . "\n";

if ($paidPayments > 0) {
    $samplePayment = Payment::whereIn('PaymentStatus', ['Verified', 'Paid', 'Fully Paid', 'Downpayment'])->first();
    echo "   Sample payment: {$samplePayment->PaymentID} - ₱{$samplePayment->Amount} - {$samplePayment->PaymentStatus}\n";
    echo "   Payment date: {$samplePayment->PaymentDate}\n";
}

echo "\n2. Checking UnpaidItems table...\n";
$totalUnpaidItems = UnpaidItem::count();
$paidUnpaidItems = UnpaidItem::where('IsPaid', true)->count();
$totalUnpaidAmount = UnpaidItem::where('IsPaid', true)->sum('TotalAmount');

echo "   Total unpaid items: {$totalUnpaidItems}\n";
echo "   Paid items: {$paidUnpaidItems}\n";
echo "   Total amount: ₱" . number_format($totalUnpaidAmount, 2) . "\n";

if ($paidUnpaidItems > 0) {
    $sampleItem = UnpaidItem::where('IsPaid', true)->first();
    echo "   Sample item: {$sampleItem->ItemID} - {$sampleItem->ItemName} - ₱{$sampleItem->TotalAmount}\n";
}

echo "\n3. Checking Rentals table...\n";
$totalRentals = Rental::count();
$paidRentals = Rental::where('is_paid', true)->count();

echo "   Total rentals: {$totalRentals}\n";
echo "   Paid rentals: {$paidRentals}\n";

if ($paidRentals > 0) {
    $sampleRental = Rental::where('is_paid', true)->first();
    echo "   Sample rental: ID {$sampleRental->id} - Qty {$sampleRental->quantity}\n";
    echo "   Returned at: {$sampleRental->returned_at}\n";
}

echo "\n4. Date range test (This Month)...\n";
$startDate = Carbon::now()->startOfMonth();
$endDate = Carbon::now()->endOfMonth();
echo "   Date range: {$startDate->toDateString()} to {$endDate->toDateString()}\n";

$thisMonthPayments = Payment::whereBetween('PaymentDate', [$startDate, $endDate])
    ->whereIn('PaymentStatus', ['Verified', 'Paid', 'Fully Paid', 'Downpayment'])
    ->sum('Amount');

echo "   This month's booking sales: ₱" . number_format($thisMonthPayments, 2) . "\n";

echo "\n=== Test Complete ===\n";
