<?php
// Test script to verify authentication

$token = $argv[1] ?? null;

if (!$token) {
    echo "Usage: php test_auth.php <token>\n";
    echo "Example: php test_auth.php '5|XCEhS0JNA5nJY2eX5YhA2bXORHWgo9zYT70xTsqVbdf85d78'\n";
    exit(1);
}

$url = 'http://127.0.0.1:8000/api/student/dashboard';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: " . $httpCode . "\n";
echo "Response: " . $response . "\n";
