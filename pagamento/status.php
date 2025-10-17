<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID da transação não informado']);
    exit;
}

$transacao_id = $_GET['id'];

$url = 'https://api.genesys.finance/v1/transactions/' . urlencode($transacao_id);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'api-secret: sk_ceb803639d6660bc849d6e2c583f179a9d78a6c5a8fc5b99460f4c74d5ff338e2aad7ecf54e411280acd2a35a22ca8e87c6459234df1537f09aaeffba19fc985'
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

$data = json_decode($response, true);

file_put_contents(__DIR__ . '/genesys_status_log.txt', json_encode([
    'timestamp' => date('Y-m-d H:i:s'),
    'transacao_id' => $transacao_id,
    'httpCode' => $httpCode,
    'curlError' => $curlError,
    'resposta_raw' => $response,
    'resposta_decode' => $data
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);

if ($httpCode === 200 && isset($data['status'])) {
    // Mapear status da Genesys para o formato esperado pelo frontend
    $statusMapeado = $data['status'];
    if ($statusMapeado === 'AUTHORIZED') {
        $statusMapeado = 'APPROVED';
    }
    
    echo json_encode([
        'success' => true,
        'status' => $statusMapeado,
        'dados' => $data
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
