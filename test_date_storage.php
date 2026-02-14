<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ClosedDate;

echo "Testing Date Storage\n";
echo "====================\n\n";

// Test 1: Save with date string
echo "1. Saving date '2025-10-24'...\n";
$test = new ClosedDate();
$test->closed_date = '2025-10-24';
$test->reason = 'Test';
$test->save();
$test->refresh();

echo "   Saved ID: {$test->id}\n";
echo "   Formatted date: {$test->closed_date}\n";
echo "   Raw date from DB: {$test->getRawOriginal('closed_date')}\n";
echo "   As Y-m-d: " . \Carbon\Carbon::parse($test->closed_date)->format('Y-m-d') . "\n";

// Test 2: Check what getClosedDates returns
echo "\n2. Testing getClosedDates() method...\n";
$dates = ClosedDate::getClosedDates();
echo "   Result: " . json_encode($dates) . "\n";

// Clean up
$test->delete();
echo "\n3. Cleaned up test data\n";
