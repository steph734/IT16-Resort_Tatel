<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Http\Kernel');

use Illuminate\Http\Request;
use App\Models\ClosedDate;

echo "Testing Dashboard API Endpoints\n";
echo "================================\n\n";

// Clean up any existing test data
ClosedDate::where('closed_date', '2025-12-15')->delete();

// Test 1: Get calendar data
echo "1. Testing GET /admin/dashboard/calendar-data\n";
$request = Request::create('/admin/dashboard/calendar-data?year=2025&month=12', 'GET');
$request->headers->set('Accept', 'application/json');

try {
    $response = $kernel->handle($request);
    $content = $response->getContent();
    $data = json_decode($content, true);
    
    echo "   Response status: {$response->getStatusCode()}\n";
    echo "   Booked dates: " . count($data['booked_dates'] ?? []) . "\n";
    echo "   Closed dates: " . count($data['closed_dates'] ?? []) . "\n";
    echo "   Content: " . substr($content, 0, 200) . "...\n";
} catch (Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}

// Test 2: Toggle closed date (close it)
echo "\n2. Testing POST /admin/dashboard/toggle-closed-date (CLOSE)\n";
$csrfToken = csrf_token();
$request = Request::create('/admin/dashboard/toggle-closed-date', 'POST', [], [], [], 
    ['HTTP_X-CSRF-TOKEN' => $csrfToken],
    json_encode(['date' => '2025-12-15', 'is_closed' => true])
);
$request->headers->set('Content-Type', 'application/json');
$request->headers->set('Accept', 'application/json');

try {
    $response = $kernel->handle($request);
    $content = $response->getContent();
    
    echo "   Response status: {$response->getStatusCode()}\n";
    echo "   Response: {$content}\n";
    
    // Verify in database
    $exists = ClosedDate::where('closed_date', '2025-12-15')->exists();
    echo "   Exists in DB: " . ($exists ? 'YES' : 'NO') . "\n";
} catch (Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}

// Test 3: Get calendar data again (should show closed date)
echo "\n3. Testing GET /admin/dashboard/calendar-data (should show closed date)\n";
$request = Request::create('/admin/dashboard/calendar-data?year=2025&month=12', 'GET');
$request->headers->set('Accept', 'application/json');

try {
    $response = $kernel->handle($request);
    $content = $response->getContent();
    $data = json_decode($content, true);
    
    echo "   Response status: {$response->getStatusCode()}\n";
    echo "   Closed dates: " . json_encode($data['closed_dates'] ?? []) . "\n";
} catch (Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}

// Test 4: Toggle closed date (open it)
echo "\n4. Testing POST /admin/dashboard/toggle-closed-date (OPEN)\n";
$request = Request::create('/admin/dashboard/toggle-closed-date', 'POST', [], [], [], 
    ['HTTP_X-CSRF-TOKEN' => $csrfToken],
    json_encode(['date' => '2025-12-15', 'is_closed' => false])
);
$request->headers->set('Content-Type', 'application/json');
$request->headers->set('Accept', 'application/json');

try {
    $response = $kernel->handle($request);
    $content = $response->getContent();
    
    echo "   Response status: {$response->getStatusCode()}\n";
    echo "   Response: {$content}\n";
    
    // Verify in database
    $exists = ClosedDate::where('closed_date', '2025-12-15')->exists();
    echo "   Exists in DB: " . ($exists ? 'YES' : 'NO') . "\n";
} catch (Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}

// Test 5: Get bookings closed dates
echo "\n5. Testing GET /admin/bookings/closed-dates\n";
$request = Request::create('/admin/bookings/closed-dates', 'GET');
$request->headers->set('Accept', 'application/json');

try {
    $response = $kernel->handle($request);
    $content = $response->getContent();
    
    echo "   Response status: {$response->getStatusCode()}\n";
    echo "   Response: {$content}\n";
} catch (Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}

// Clean up
ClosedDate::where('closed_date', '2025-12-15')->delete();

echo "\n\nAll API tests completed!\n";
