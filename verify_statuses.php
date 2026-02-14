<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Database Status Verification ===\n\n";

// Check booking statuses
echo "1. Booking Statuses:\n";
$bookingStatuses = DB::table('bookings')->distinct()->pluck('BookingStatus');
foreach ($bookingStatuses as $status) {
    $count = DB::table('bookings')->where('BookingStatus', $status)->count();
    echo "   - {$status}: {$count} bookings\n";
}

echo "\n2. Payment Statuses:\n";
$paymentStatuses = DB::table('payments')->distinct()->pluck('PaymentStatus');
foreach ($paymentStatuses as $status) {
    $count = DB::table('payments')->where('PaymentStatus', $status)->count();
    echo "   - {$status}: {$count} payments\n";
}

echo "\n3. December 2025 Payments:\n";
$payments = DB::table('payments')
    ->whereBetween('PaymentDate', ['2025-12-01', '2025-12-31'])
    ->orderBy('PaymentDate', 'desc')
    ->get(['PaymentID', 'BookingID', 'Amount', 'PaymentStatus', 'PaymentMethod', 'PaymentDate']);

foreach ($payments as $p) {
    echo "   - {$p->PaymentID} ({$p->BookingID}): ₱" . number_format($p->Amount, 2);
    echo " [{$p->PaymentStatus}] via {$p->PaymentMethod} on {$p->PaymentDate}\n";
}

echo "\n4. Checking if payments match expected statuses:\n";
echo "   Expected Payment Statuses: Fully Paid, Downpayment\n";
echo "   Actual Payment Statuses: " . implode(', ', $paymentStatuses->toArray()) . "\n";

$invalidStatuses = $paymentStatuses->diff(['Fully Paid', 'Downpayment']);
if ($invalidStatuses->count() > 0) {
    echo "   ⚠️  WARNING: Found unexpected statuses: " . implode(', ', $invalidStatuses->toArray()) . "\n";
    echo "   These need to be fixed!\n";
}

echo "\n5. Expected Booking Statuses: Booked, Staying, Completed\n";
echo "   Actual Booking Statuses: " . implode(', ', $bookingStatuses->toArray()) . "\n";

$expectedBooking = ['Booked', 'Staying', 'Completed'];
$missingBooking = collect($expectedBooking)->diff($bookingStatuses);
if ($missingBooking->count() > 0) {
    echo "   ℹ️  Missing statuses: " . implode(', ', $missingBooking->toArray()) . "\n";
}
