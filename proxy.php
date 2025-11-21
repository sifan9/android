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

$derived = [
    'imei' => $resultNode['serial_no'] ?? $imei,
    'serial_no_type' => $resultNode['serial_no_type'] ?? 'IMEI',
    'model' => $resultNode['carrier_model_info'] ?? $resultNode['external_marketing_name'] ?? null,
    'carrier' => $resultNode['ship_to_cust_name']
        ?? $resultNode['sold_to_cust_name']
        ?? $resultNode['direct_ship_cust_name']
        ?? $resultNode['carrier_model_info']
        ?? null,
    'country' => $resultNode['country_to_ship']
        ?? $resultNode['direct_ship_country_code']
        ?? $resultNode['warranty_country_code']
        ?? null,
    'unlock_code' => $resultNode['master_service_lock']
        ?? $resultNode['master_lock_code']
        ?? $resultNode['deviceunlockcode']
        ?? null,
    'lock_4' => $resultNode['lock_4'] ?? $resultNode['master_service_lock'] ?? null,
    'lock_5' => $resultNode['lock_5'] ?? null,
    'status' => $resultNode['status'] ?? $resultNode['status_code'] ?? null,
    'device_type' => $resultNode['device_type'] ?? null,
    'apc' => $resultNode['apc'] ?? null,
    'message' => $resultNode['response_message'] ?? null,
];

$payload = array_merge($derived, [
    'customer' => [
        'ship_to' => $resultNode['ship_to_cust_name'] ?? null,
        'sold_to' => $resultNode['sold_to_cust_name'] ?? null,
        'direct_ship' => $resultNode['direct_ship_cust_name'] ?? null,
    ],
    'country_codes' => [
        'ship' => $resultNode['country_to_ship'] ?? null,
        'direct' => $resultNode['direct_ship_country_code'] ?? null,
        'warranty' => $resultNode['warranty_country_code'] ?? null,
    ],
    'raw' => $resultNode,
]);

echo json_encode($payload, JSON_UNESCAPED_UNICODE);
