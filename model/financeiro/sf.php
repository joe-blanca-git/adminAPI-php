<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Lista de domínios permitidos
$allowedOrigins = [
    'http://localhost:4200',
    'http://192.168.0.217:4200'
];

// Verifica o cabeçalho 'Origin' da solicitação
if (isset($_SERVER['HTTP_ORIGIN'])) {
    $origin = $_SERVER['HTTP_ORIGIN'];
    if (in_array($origin, $allowedOrigins)) {
        header("Access-Control-Allow-Origin: $origin");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    exit;
}

header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");


require_once '../../database/Persistencia.php';

function validarToken($token) {
    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://192.168.0.109/bit/adminAPI-php/model/auth/authToken.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        
        $data = json_encode(['token' => $token]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        $response = curl_exec($ch);
        
        if ($response === false) {
            throw new Exception(curl_error($ch), curl_errno($ch));
        }

        curl_close($ch);
        $decoded_response = json_decode($response, true);
        if (isset($decoded_response['message']) && $decoded_response['message'] === 'OK') {
            return true;
        }

        return false;
    } catch (Exception $e) {
        error_log("cURL Error ({$e->getCode()}): {$e->getMessage()}");
        return false;
    }
}

function novoFinanceiro($pdo, $financeiro) {
    $fields = array_keys($financeiro);
    $values = array_values($financeiro);

    $placeholders = array_fill(0, count($fields), '?');
    $sql = "INSERT INTO sf (" . implode(',', $fields) . ") VALUES (" . implode(',', $placeholders) . ")";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);

    return $pdo->lastInsertId();
}

function geraParcelas($pdo, $parcelas, $financeiroId) {
    foreach ($parcelas as $parcela) {
        $parcela['SfMovId'] = $financeiroId;
        $fields = array_keys($parcela);
        $values = array_values($parcela);

        $placeholders = array_fill(0, count($fields), '?');
        $sql = "INSERT INTO sf_mov_parcela (" . implode(',', $fields) . ") VALUES (" . implode(',', $placeholders) . ")";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
    }
}

$conexao = new Conexao();
$pdo = $conexao->conectar();

$headers = apache_request_headers();
$token = null;
foreach ($headers as $header => $value) {
    if (strtolower($header) === 'authorization') {
        $token = str_replace('Bearer ', '', $value);
        break;
    }
}

if (empty($token)) {
    http_response_code(401);
    echo json_encode(array("error" => "Token não fornecido"));
    exit;
}

if (!validarToken($token)) {
    http_response_code(401);
    echo json_encode(array("error" => "Token inválido"));
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($body['financeiro'], $body['parcelas']) && isset($_GET['EmpresaId'])) {
    $empresaId = $_GET['EmpresaId'];
    $body['financeiro']['EmpresaId'] = $empresaId;

    try {
        $idSf = novoFinanceiro($pdo, $body['financeiro']);
        geraParcelas($pdo, $body['parcelas'], $idSf);

        http_response_code(200);
        echo json_encode(array("message" => 'Financeiro numero: ' . $idSf . ' criado com sucesso!'));
    } catch (Exception $e) {
        error_log("Erro: " . $e->getMessage());
        error_log("Pilha: " . $e->getTraceAsString());
        http_response_code(500);
        echo json_encode(array("error" => "Erro ao criar financeiro: " . $e->getMessage()));
        echo json_encode(array("stack" => $e->getTraceAsString()));
    }
} else {
    http_response_code(400);
    echo json_encode(array("error" => "Dados insuficientes fornecidos"));
}
?>
