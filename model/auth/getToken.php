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

$body = json_decode(file_get_contents('php://input'), true);

function obtemToken($user, $password){
    try {
        $conexao = new Conexao();
        $pdo = $conexao->conectar();
        
        $stmt = $pdo->prepare("SELECT * FROM user WHERE id_user = :user");
        $stmt->bindParam(':user', $user, PDO::PARAM_STR);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $userRecord = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $userRecord['password'])) {
                $key = 'bit_api'; 
                $data = $user . '|' . $password;
                $token = base64_encode(openssl_encrypt($data, 'AES-128-ECB', $key, OPENSSL_RAW_DATA));
                return $token;
            }
        }
        return null;
    } catch (Exception $e) {
        return false; 
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($body['user'], $body['password'])) {
    $user = filter_var($body['user'], FILTER_SANITIZE_STRING);
    $password = filter_var($body['password'], FILTER_SANITIZE_STRING);
    
    if ($user && $password) {
        try {
            $token = obtemToken($user, $password);
            
            if ($token) {
                http_response_code(200);
                echo json_encode(array("access_token" => $token));
            } else {
                http_response_code(401);
                echo json_encode(array("error" => "Não Autorizado"));
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(array("error" => "Internal server error"));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("error" => "Bad request"));
    }
} else {
    http_response_code(400);
    echo json_encode(array("error" => "Bad request"));
}
?>
