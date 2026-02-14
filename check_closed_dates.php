<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ClosedDate;

echo "Current Closed Dates in Database\n";
echo "=================================\n\n";

$closedDates = ClosedDate::all();

if ($closedDates->isEmpty()) {
    echo "No closed dates found.\n";
} else {
    echo "Found {$closedDates->count()} closed date(s):\n\n";
    foreach ($closedDates as $date) {
        echo "ID: {$date->id}\n";
        echo "  Formatted: {$date->closed_date}\n";
        echo "  Raw DB value: {$date->getRawOriginal('closed_date')}\n";
        echo "  As Y-m-d: " . \Carbon\Carbon::parse($date->closed_date)->format('Y-m-d') . "\n";
        echo "  From getClosedDates(): " . (in_array(\Carbon\Carbon::parse($date->closed_date)->format('Y-m-d'), ClosedDate::getClosedDates()) ? 'YES' : 'NO') . "\n";
        echo "\n";
    }
}

echo "\ngetClosedDates() returns:\n";
echo json_encode(ClosedDate::getClosedDates(), JSON_PRETTY_PRINT) . "\n";
