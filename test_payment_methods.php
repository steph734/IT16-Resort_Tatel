<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Payment;
use Carbon\Carbon;

echo "=== PAYMENT METHODS TEST ===\n\n";

$startDate = Carbon::parse('2025-11-01')->startOfDay();
$endDate = Carbon::parse('2025-11-30')->endOfDay();

// Get all payment methods
$paymentMethods = Payment::whereBetween('PaymentDate', [$startDate, $endDate])
    ->whereIn('PaymentStatus', ['Verified', 'Paid', 'Fully Paid', 'Downpayment'])
    ->select('PaymentMethod', \DB::raw('SUM(Amount) as total'))
    ->groupBy('PaymentMethod')
    ->get();

echo "Payment Methods for November 2025:\n";
foreach ($paymentMethods as $method) {
    echo "  - {$method->PaymentMethod}: ₱" . number_format($method->total, 2) . "\n";
}

echo "\nTotal: ₱" . number_format($paymentMethods->sum('total'), 2) . "\n";

// Get all unique payment methods in database
echo "\n\nAll Payment Methods in Database:\n";
$allMethods = Payment::select('PaymentMethod')
    ->distinct()
    ->get();
foreach ($allMethods as $method) {
    echo "  - {$method->PaymentMethod}\n";
}
