<?php
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
    exit;
}

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

header("Content-Type: application/json; charset=UTF-8");

require_once '../../database/Persistencia.php';

$conexao = new Conexao();
$pdo = $conexao->conectar();

function obterLista($tabela, $empresaId)
{
    global $pdo;

    $query = "SELECT * FROM $tabela WHERE EmpresaId = :EmpresaId";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':EmpresaId', $empresaId, PDO::PARAM_INT);
    $stmt->execute();

    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return json_encode($dados, JSON_PRETTY_PRINT);
}

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

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['tabela']) && isset($_GET['EmpresaId'])) {
    $tabela = $_GET['tabela'];
    $empresaId = $_GET['EmpresaId'];
    
    $resultado = obterLista($tabela, $empresaId);
    
    echo $resultado;
} else {
    http_response_code(400);
    echo json_encode(array("mensagem" => "Erro: Nome da tabela ou EmpresaId não fornecido."));
}
?>
