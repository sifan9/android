<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$imei = $_GET['imei'] ?? '';
$baseResponse = ['imei' => $imei];

if (!preg_match('/^\d{15}$/', $imei)) {
    echo json_encode($baseResponse + ['error' => 'imei parameter must be a 15-digit number.']);
    exit;
}

function extractDeviceResult(string $xml): ?array
{
    libxml_use_internal_errors(true);
    $simple = @simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
    if ($simple === false) {
        return null;
    }

    $nodes = $simple->xpath('//*[local-name()="device_unlock_code_result"]');
    if (!$nodes || !isset($nodes[0])) {
        return null;
    }

    return json_decode(json_encode($nodes[0]), true) ?? null;
}

function fieldValue(array $source, string $key): ?string
{
    if (!array_key_exists($key, $source)) {
        return null;
    }
    $value = $source[$key];

    if (is_array($value)) {
        $value = array_filter($value, static function ($item) {
            if (is_array($item)) {
                return !empty(array_filter($item, static fn($nested) => $nested !== null && $nested !== '' && $nested !== []));
            }
            return $item !== null && $item !== '' && $item !== [];
        });

        if (empty($value)) {
            return null;
        }

        $value = reset($value);
    }

    if ($value === null) {
        return null;
    }

    $value = trim((string)$value);

    return $value === '' ? null : $value;
}

function orNo(?string $value): string
{
    return $value === null ? 'NO' : $value;
}

function normalizeRawValue($value)
{
    if (is_array($value)) {
        if (empty($value)) {
            return 'NO';
        }
        $normalized = [];
        foreach ($value as $k => $v) {
            $normalized[$k] = normalizeRawValue($v);
        }
        return $normalized;
    }

    $value = trim((string)$value);
    return $value === '' ? 'NO' : $value;
}

$soapPayload = <<<XML
<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
    <s:Body>
        <service xmlns="http://ibase.lenovo.com/webservices/IQS_WARRANTY_CHECK_1.0" xmlns:a="java:ibase.lenovo.com.services" xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
            <a:id>MST</a:id>
            <a:pw>NS1xpq#Y</a:pw>
            <a:string>IMEI</a:string>
            <a:string0>{$imei}</a:string0>
            <a:sublockcode/>
            <a:deviceunlockcode>Y</a:deviceunlockcode>
            <a:R12DMP/>
            <a:R12PCBA/>
        </service>
    </s:Body>
</s:Envelope>
XML;

$ch = curl_init('https://wsgw.motorola.com/IQS_WARRANTY_CHECK_1.0');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CONNECTTIMEOUT => 12,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $soapPayload,
    CURLOPT_HTTPHEADER => [
        'Authorization: Basic bGVub3ZvMjAxODpMZW5vdm9AMjAxOA==',
        'Content-Type: text/xml; charset=utf-8',
        'Accept: text/xml',
        'Host: wsgw.motorola.com',
        'Content-Length: ' . strlen($soapPayload),
    ],
]);

$response = curl_exec($ch);
$errno = curl_errno($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($errno !== 0 || $httpCode !== 200 || empty($response)) {
    echo json_encode($baseResponse + ['error' => 'Motorola service is unavailable right now.']);
    exit;
}

$resultNode = extractDeviceResult($response);

if (!is_array($resultNode)) {
    echo json_encode($baseResponse + ['error' => 'Unable to parse Motorola response.']);
    exit;
}

$model = fieldValue($resultNode, 'carrier_model_info') ?? fieldValue($resultNode, 'external_marketing_name');
$carrier = fieldValue($resultNode, 'ship_to_cust_name')
    ?? fieldValue($resultNode, 'sold_to_cust_name')
    ?? fieldValue($resultNode, 'direct_ship_cust_name')
    ?? $model;
$country = fieldValue($resultNode, 'country_to_ship')
    ?? fieldValue($resultNode, 'direct_ship_country_code')
    ?? fieldValue($resultNode, 'warranty_country_code');

$rawUnlock = fieldValue($resultNode, 'master_service_lock')
    ?? fieldValue($resultNode, 'master_lock_code')
    ?? fieldValue($resultNode, 'deviceunlockcode');

if ($rawUnlock !== null && strpos($imei, $rawUnlock) === 0 && strlen($rawUnlock) === 8) {
    $rawUnlock = 'No Code';
}

$lock4 = fieldValue($resultNode, 'lock_4') ?? $rawUnlock;
$lock5 = fieldValue($resultNode, 'lock_5');

$derived = [
    'imei' => orNo(fieldValue($resultNode, 'serial_no') ?? $imei),
    'serial_no_type' => orNo(fieldValue($resultNode, 'serial_no_type') ?? 'IMEI'),
    'model' => orNo($model),
    'carrier' => orNo($carrier),
    'country' => orNo($country),
    'unlock_code' => orNo($rawUnlock),
    'lock_4' => orNo($lock4),
    'lock_5' => orNo($lock5),
    'status' => orNo(fieldValue($resultNode, 'status') ?? fieldValue($resultNode, 'status_code')),
    'device_type' => orNo(fieldValue($resultNode, 'device_type')),
    'apc' => orNo(fieldValue($resultNode, 'apc')),
    'message' => orNo(fieldValue($resultNode, 'response_message')),
];

$payload = array_merge($derived, [
    'customer' => [
        'ship_to' => orNo(fieldValue($resultNode, 'ship_to_cust_name')),
        'sold_to' => orNo(fieldValue($resultNode, 'sold_to_cust_name')),
        'direct_ship' => orNo(fieldValue($resultNode, 'direct_ship_cust_name')),
    ],
    'country_codes' => [
        'ship' => orNo(fieldValue($resultNode, 'country_to_ship')),
        'direct' => orNo(fieldValue($resultNode, 'direct_ship_country_code')),
        'warranty' => orNo(fieldValue($resultNode, 'warranty_country_code')),
    ],
    'raw' => normalizeRawValue($resultNode),
]);

echo json_encode($payload, JSON_UNESCAPED_UNICODE);
