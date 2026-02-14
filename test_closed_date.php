<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ClosedDate;

echo "Testing ClosedDate Model\n";
echo "========================\n\n";

// Test 1: Create a closed date
echo "1. Creating closed date for 2025-12-15...\n";
try {
    $closedDate = ClosedDate::create([
        'closed_date' => '2025-12-15',
        'reason' => 'Test closure'
    ]);
    echo "   SUCCESS: Created closed date ID: {$closedDate->id}\n";
} catch (Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}

// Test 2: Retrieve all closed dates
echo "\n2. Retrieving all closed dates...\n";
$allClosedDates = ClosedDate::all();
echo "   Found {$allClosedDates->count()} closed date(s):\n";
foreach ($allClosedDates as $date) {
    echo "   - {$date->closed_date} (ID: {$date->id})\n";
}

// Test 3: Test getClosedDates method
echo "\n3. Testing getClosedDates() method...\n";
$closedDatesArray = ClosedDate::getClosedDates();
echo "   Result: " . json_encode($closedDatesArray) . "\n";

// Test 4: Test isDateClosed method
echo "\n4. Testing isDateClosed() method...\n";
$isClosed = ClosedDate::isDateClosed('2025-12-15');
echo "   Is 2025-12-15 closed? " . ($isClosed ? 'YES' : 'NO') . "\n";

// Clean up
echo "\n5. Cleaning up test data...\n";
ClosedDate::where('closed_date', '2025-12-15')->delete();
echo "   Deleted test closed date\n";

echo "\nAll tests completed!\n";
