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

// Converter centavos para reais (Genesys espera valor em reais)
$valorReais = $valor_centavos / 100;
$titulo = isset($input['descricao']) ? $input['descricao'] : 'Taxa de Inscrição';
$webhookUrl = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/webhook.php';

// Payload para API Genesys
$payload = [
    "external_id" => uniqid('pag2_', true),
    "total_amount" => $valorReais,
    "payment_method" => "PIX",
    "webhook_url" => $webhookUrl,
    "items" => [
        [
            "id" => "item_1",
            "title" => $titulo,
            "description" => $titulo,
            "price" => $valorReais,
            "quantity" => 1,
            "is_physical" => false
        ]
    ],
    "ip" => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
    "customer" => [
        "name" => $nome,
        "email" => $email,
        "phone" => $telefone,
        "document_type" => "CPF",
        "document" => $cpf
    ]
];

// Adicionar UTMs se fornecidos
if (!empty($input['utm_source'])) $payload['customer']['utm_source'] = $input['utm_source'];
if (!empty($input['utm_medium'])) $payload['customer']['utm_medium'] = $input['utm_medium'];
if (!empty($input['utm_campaign'])) $payload['customer']['utm_campaign'] = $input['utm_campaign'];
if (!empty($input['utm_content'])) $payload['customer']['utm_content'] = $input['utm_content'];
if (!empty($input['utm_term'])) $payload['customer']['utm_term'] = $input['utm_term'];

$ch = curl_init("https://api.genesys.finance/v1/transactions");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "api-secret: sk_ceb803639d6660bc849d6e2c583f179a9d78a6c5a8fc5b99460f4c74d5ff338e2aad7ecf54e411280acd2a35a22ca8e87c6459234df1537f09aaeffba19fc985"
    ],
    CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

$data = json_decode($response, true);

file_put_contents(__DIR__ . '/genesys_pix_log.txt', json_encode([
    'timestamp' => date('Y-m-d H:i:s'),
    'payload' => $payload,
    'httpCode' => $httpCode,
    'curlError' => $curlError,
    'resposta_raw' => $response,
    'resposta_decode' => $data
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);

if ($httpCode >= 200 && $httpCode < 300 && isset($data['pix']['payload'])) {
    echo json_encode([
        'success' => true,
        'pixCode' => $data['pix']['payload'],
        'pixQrCode' => $data['pix']['payload'],
        'transacao_id' => $data['id'] ?? null,
        'url' => null
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
