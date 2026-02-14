<?php
/**
 * Test script to verify session expiration handling
 */

// Simulate accessing admin routes with expired session
$testUrl = 'http://127.0.0.1:8000/admin/inventory/stock-movements';

echo "Testing session expiration for URL: {$testUrl}\n";
echo "=============================================\n\n";

// Use curl to test
$ch = curl_init($testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // Don't follow redirects
curl_setopt($ch, CURLOPT_HEADER, true); // Get headers
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Parse response
$headers = substr($response, 0, strpos($response, "\r\n\r\n"));
$body = substr($response, strpos($response, "\r\n\r\n"));

echo "HTTP Status Code: {$httpCode}\n";
echo "Headers:\n{$headers}\n\n";

if ($httpCode === 302 || $httpCode === 301) {
    echo "✓ REDIRECT DETECTED (Expected behavior)\n";
    // Extract redirect location
    if (preg_match('/Location: (.+)/i', $headers, $matches)) {
        echo "Redirect to: " . trim($matches[1]) . "\n";
    }
} else if ($httpCode === 500) {
    echo "✗ ERROR 500 (Session expiration NOT handled properly)\n";
    echo "Body contains error page\n";
} else if ($httpCode === 200) {
    echo "✓ Got 200 OK (User is logged in or guest access allowed)\n";
}
?>
