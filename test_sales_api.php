<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\Admin\SalesController;

echo "=== Testing SalesController Endpoints ===\n\n";

// Create controller instance
$controller = new SalesController();

// Test 1: Get Dashboard Data (December 2025)
echo "1. Dashboard Data (This Month - December 2025):\n";
$request = Request::create('/admin/sales/dashboard-data', 'GET', [
    'preset' => 'month',
    'start_date' => '2025-12-01',
    'end_date' => '2025-12-31'
]);

$response = $controller->getDashboardData($request);
$data = json_decode($response->getContent(), true);

echo "   Booking Sales: ₱" . number_format($data['kpis']['booking_sales'], 2) . "\n";
echo "   Rental Sales: ₱" . number_format($data['kpis']['rental_sales'], 2) . "\n";
echo "   Growth Rate: " . number_format($data['kpis']['growth_rate'], 2) . "%\n\n";

// Test 2: Get Transactions for Ledger
echo "2. Transactions Ledger (December 2025):\n";
$request = Request::create('/admin/sales/transactions', 'GET', [
    'start_date' => '2025-12-01',
    'end_date' => '2025-12-31'
]);

$response = $controller->getTransactions($request);
$data = json_decode($response->getContent(), true);

echo "   Total Transactions: " . $data['summary']['total_transactions'] . "\n";
echo "   Total Amount: ₱" . number_format($data['summary']['total_amount'], 2) . "\n";
echo "   Booking Transactions: " . $data['summary']['booking_count'] . "\n";
echo "   Rental Transactions: " . $data['summary']['rental_count'] . "\n\n";

echo "   Recent Transactions:\n";
foreach (array_slice($data['transactions'], 0, 10) as $txn) {
    $date = \Carbon\Carbon::parse($txn['date'])->format('M d, Y');
    echo "   - [{$txn['source']}] {$date}: {$txn['purpose']} - ₱" . number_format($txn['amount'], 2);
    echo " ({$txn['method']}) by {$txn['processed_by']}\n";
}

echo "\n✅ All tests completed!\n";
