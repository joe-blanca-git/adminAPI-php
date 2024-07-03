<?php
// Habilitar CORS para requisições OPTIONS
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

// Incluir arquivo de conexão ou classes necessárias
require_once '../../database/Persistencia.php';

// Chave de validação
define('VALIDATION_KEY', 'bitApi2024Likeaboss');

// Receber o payload JSON do corpo da requisição
$body = json_decode(file_get_contents('php://input'), true);

// Função para validar o token
function validarToken($token){
    try {
        // Chave utilizada para criar o token
        $key = VALIDATION_KEY;

        // Decodificar o token
        $tokenData = json_decode(openssl_decrypt(base64_decode($token), 'AES-128-ECB', $key, OPENSSL_RAW_DATA), true);
        
        // Verificar se o token foi decodificado corretamente
        if (!$tokenData || !isset($tokenData['usuario'], $tokenData['expire_in'])) {
            return "Unauthorized";
        }

        // Verificar se o token expirou
        if ($tokenData['expire_in'] <= 0) {
            return "Token expired";
        }
        
        return "OK";
        
    } catch (Exception $e) {
        return "Unauthorized";
    }
}


// Verificar se o método da requisição é POST e se o token foi enviado no corpo da requisição
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($body['token'])) {
    $token = filter_var($body['token'], FILTER_SANITIZE_STRING);

    // Validar o token
    $status = validarToken($token);

    // Retornar resposta conforme o status da validação
    if ($status === "OK") {
        http_response_code(200);
        echo json_encode(array("message" => "OK"));
    } elseif ($status === "Token expired") {
        http_response_code(401);
        echo json_encode(array("error" => "Token expired"));
    } else {
        http_response_code(401);
        echo json_encode(array("error" => "Unauthorized"));
    }
} else {
    http_response_code(400);
    echo json_encode(array("error" => "Bad request"));
}
?>
