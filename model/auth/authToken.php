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

require_once '../../database/Persistencia.php';

define('VALIDATION_KEY', 'bitApi2024Likeaboss');

$body = json_decode(file_get_contents('php://input'), true);

function validarToken($token){
    try {
        $key = VALIDATION_KEY;

        $tokenData = json_decode(openssl_decrypt(base64_decode($token), 'AES-128-ECB', $key, OPENSSL_RAW_DATA), true);
        
        if (!$tokenData || !isset($tokenData['usuario'], $tokenData['expire_in'])) {
            return "Unauthorized";
        }

        if ($tokenData['expire_in'] <= 0) {
            return "Token expired";
        }
        
        return "OK";
        
    } catch (Exception $e) {
        return "Unauthorized";
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($body['token'])) {
    $token = filter_var($body['token'], FILTER_SANITIZE_STRING);

    $status = validarToken($token);

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
