<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$imei = $_GET['imei'] ?? '';

if (!preg_match('/^\d{15}$/', $imei)) {
    echo json_encode(['error' => 'imei parameter must be a 15-digit number.']);
    exit;
}

$remoteUrl = 'https://general-unlocker.com/testsifan/index.php?Data=' . $imei;

$ch = curl_init($remoteUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 25,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'Cache-Control: no-cache',
    ],
]);

$response = curl_exec($ch);
$errno = curl_errno($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($errno !== 0 || $httpCode !== 200 || empty($response)) {
    echo json_encode(['error' => 'Remote service is unavailable right now.']);
    exit;
}

echo $response;
