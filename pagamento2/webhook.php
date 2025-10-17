<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

file_put_contents(__DIR__ . '/webhook_log.txt', date('Y-m-d H:i:s') . "\n" . $rawInput . "\n\n", FILE_APPEND);

// Genesys envia status 'AUTHORIZED' para pagamentos aprovados
if (!isset($data['status']) || !in_array(strtoupper($data['status']), ['AUTHORIZED', 'APPROVED'])) {
    echo json_encode(['success' => false, 'message' => 'Pagamento não confirmado ou payload inválido.']);
    exit;
}

// Estrutura do webhook da Genesys
$transacao_id = $data['id'] ?? '';
$external_id = $data['external_id'] ?? '';
$total_amount = $data['total_amount'] ?? 0;
$status = $data['status'] ?? '';
$payment_method = $data['payment_method'] ?? '';

$registro = [
    'data' => date('Y-m-d H:i:s'),
    'transacao_id' => $transacao_id,
    'external_id' => $external_id,
    'total_amount' => $total_amount,
    'status' => $status,
    'payment_method' => $payment_method,
    'raw_data' => $data
];

file_put_contents(__DIR__ . '/pagamentos_confirmados_genesys.txt', json_encode($registro, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);

echo json_encode(['success' => true, 'message' => 'Webhook processado com sucesso']);
