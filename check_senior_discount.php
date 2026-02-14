<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Booking;

echo "Checking Booking B177 Senior Discount:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$booking = Booking::where('BookingID', 'B177')->first();

if ($booking) {
    echo "Booking ID: {$booking->BookingID}\n";
    echo "Senior Discount: ₱" . number_format($booking->senior_discount ?? 0, 2) . "\n";
    echo "Actual Seniors at Checkout: " . ($booking->actual_seniors_at_checkout ?? 0) . "\n";
    
    if ($booking->senior_discount > 0) {
        echo "\n⚠️  ISSUE: Senior discount is still set!\n";
        echo "This should be 0 after voiding.\n";
    } else {
        echo "\n✅ Senior discount is cleared correctly.\n";
    }
} else {
    echo "Booking B177 not found.\n";
}
