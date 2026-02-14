<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Total transactions: " . App\Models\Transaction::count() . PHP_EOL;
echo "Earliest: " . App\Models\Transaction::min('transaction_date') . PHP_EOL;
echo "Latest: " . App\Models\Transaction::max('transaction_date') . PHP_EOL;

// Check December 2025
$decCount = App\Models\Transaction::whereYear('transaction_date', 2025)
    ->whereMonth('transaction_date', 12)
    ->count();
echo "December 2025 transactions: " . $decCount . PHP_EOL;

// Check all of 2025
$yearCount = App\Models\Transaction::whereYear('transaction_date', 2025)->count();
echo "All 2025 transactions: " . $yearCount . PHP_EOL;
