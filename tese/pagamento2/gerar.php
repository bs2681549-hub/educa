<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Método inválido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$nome = isset($input['nome_pagador']) ? trim($input['nome_pagador']) : '';
$cpf = isset($input['cpf_pagador']) ? preg_replace('/\D/', '', $input['cpf_pagador']) : '';
$email = isset($input['email_pagador']) ? trim($input['email_pagador']) : '';
$telefone = isset($input['telefone_pagador']) ? preg_replace('/\D+/', '', $input['telefone_pagador']) : '';
if (strlen($telefone) < 8 || strlen($telefone) > 12) {
    echo json_encode(['success' => false, 'message' => 'Telefone inválido.']);
    exit;
}


if (strlen($cpf) !== 11 || !$nome || !$email) {
    echo json_encode(['success' => false, 'message' => 'Dados do pagador incompletos.']);
    exit;
}

$valor_centavos = isset($input['valor_pagamento']) ? (int)$input['valor_pagamento'] : 0;
if ($valor_centavos <= 0) {
    echo json_encode(['success' => false, 'message' => 'Valor de pagamento inválido.']);
    exit;
}

// Valor em centavos vindo do front
$valorConvertido = $valor_centavos;
$titulo = isset($input['descricao']) ? $input['descricao'] : 'Transação via PIX';
$postbackUrl = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/webhook.php';

// Payload GhostsPays v2
$payload = [
    'paymentMethod' => 'PIX',
    'amount' => $valorConvertido,
    'description' => $titulo,
    'postbackUrl' => $postbackUrl,
    'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
    'customer' => [
        'name' => $nome,
        'email' => $email,
        'phone' => $telefone,
        'document' => $cpf
    ],
    'items' => [
        [
            'title' => $titulo,
            'unitPrice' => $valorConvertido,
            'quantity' => 1
        ]
    ]
];

// Basic Auth (SecretKey:CompanyId)
$secretKey = 'sk_live_dF3G0cVmf0ZISiLTc8zlkFH12x9ROTn18CGiTDzWlaT0dvqW';
$companyId = '843c2313-3c9b-4acf-9b2c-c42206759932';
$credentials = base64_encode($secretKey . ':' . $companyId);

$ch = curl_init('https://api.ghostspaysv2.com/functions/v1/transactions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Basic ' . $credentials
    ],
    CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

$data = json_decode($response, true);

file_put_contents(__DIR__ . '/ghostpay_pix_log.txt', json_encode([
    'timestamp' => date('Y-m-d H:i:s'),
    'payload' => $payload,
    'httpCode' => $httpCode,
    'curlError' => $curlError,
    'resposta_raw' => $response,
    'resposta_decode' => $data
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);

// Normaliza resposta para o front
$tx = $data['data'] ?? $data;
$pix = $tx['pix'] ?? ($tx['data']['pix'] ?? []);
$pixCodeCandidate = $pix['emv'] ?? $pix['copyPaste'] ?? $pix['copiaecola'] ?? $pix['code'] ?? $pix['qrcode'] ?? null;
$transactionId = $tx['id'] ?? ($tx['data']['id'] ?? null);

if ($httpCode >= 200 && $httpCode < 300 && $pixCodeCandidate) {
    echo json_encode([
        'success' => true,
        'pixCode' => $pixCodeCandidate,
        'pixQrCode' => $pixCodeCandidate,
        'transacao_id' => $transactionId,
        'url' => $tx['receiptUrl'] ?? null
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao gerar Pix',
        'debug' => [
            'payload' => $payload,
            'httpCode' => $httpCode,
            'curlError' => $curlError,
            'response' => $data
        ]
    ]);
}
