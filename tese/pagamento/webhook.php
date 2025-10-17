<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

file_put_contents(__DIR__ . '/webhook_log.txt', date('Y-m-d H:i:s') . "\n" . $rawInput . "\n\n", FILE_APPEND);

if (!isset($data['status']) || strtolower($data['status']) !== 'paid') {
    echo json_encode(['success' => false, 'message' => 'Pagamento não confirmado ou payload inválido.']);
    exit;
}

$nome = $data['name'] ?? '';
$cpf = $data['cpf'] ?? '';
$email = $data['email'] ?? '';
$telefone = $data['phone'] ?? '';
$valor = $data['amount'] ?? 0;
$transacao_id = $data['id'] ?? '';

$registro = [
    'data' => date('Y-m-d H:i:s'),
    'nome' => $nome,
    'cpf' => $cpf,
    'email' => $email,
    'telefone' => $telefone,
    'valor' => $valor / 100,
    'transacao_id' => $transacao_id
];

file_put_contents(__DIR__ . '/pagamentos_confirmados.txt', json_encode($registro, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);

echo json_encode(['success' => true, 'message' => 'Pagamento confirmado']);
