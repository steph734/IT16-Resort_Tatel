<?php
/**
 * Test API endpoint directly
 * Access via: http://localhost:8000/test_api_direct.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Http\Kernel');

$request = Illuminate\Http\Request::create('/admin/sales/api/dashboard-data?preset=month', 'GET');
$response = $kernel->handle($request);

header('Content-Type: application/json');
echo $response->getContent();

$kernel->terminate($request, $response);
