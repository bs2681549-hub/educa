<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID da transação não informado']);
    exit;
}

$transacao_id = $_GET['id'];

// GhostsPays v2
$secretKey = 'sk_live_dF3G0cVmf0ZISiLTc8zlkFH12x9ROTn18CGiTDzWlaT0dvqW';
$companyId = '843c2313-3c9b-4acf-9b2c-c42206759932';
$credentials = base64_encode($secretKey . ':' . $companyId);

$url = 'https://api.ghostspaysv2.com/functions/v1/transactions/' . urlencode($transacao_id);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Basic ' . $credentials,
        'Content-Type: application/json'
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

$data = json_decode($response, true);

$log = [
    'timestamp' => date('Y-m-d H:i:s'),
    'transacao_id' => $transacao_id,
    'httpCode' => $httpCode,
    'curlError' => $curlError,
    'resposta_raw' => $response,
    'resposta_decode' => $data
];
file_put_contents(__DIR__ . '/ghostpay_status_log.txt', json_encode($log, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);

if ($httpCode === 200) {
    $tx = $data['data'] ?? $data;
    $status = $tx['status'] ?? null;
    $normalized = ($status === 'paid') ? 'APPROVED' : strtoupper((string)$status);
    echo json_encode([
        'success' => true,
        'status' => $normalized,
        'dados' => $tx
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao consultar status da transação',
        'debug' => [
            'httpCode' => $httpCode,
            'curlError' => $curlError,
            'resposta' => $data
        ]
    ]);
}
