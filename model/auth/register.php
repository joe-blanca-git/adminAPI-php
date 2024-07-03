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

function registerUser($userName, $email, $empresa, $status, $passwordHash) {
    try {
        $conexao = new Conexao();
        $pdo = $conexao->conectar();
        
        $sql = "INSERT INTO users (UserName, Email, Empresa, Status, PasswordHash) VALUES (:userName, :email, :empresa, :status, :passwordHash)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':userName', $userName, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':empresa', $empresa, PDO::PARAM_INT);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':passwordHash', $passwordHash, PDO::PARAM_STR);

        return $stmt->execute();
    } catch (Exception $e) {
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($body['userName'], $body['email'], $body['empresa'], $body['status'], $body['password'])) {
    $userName = filter_var($body['userName'], FILTER_SANITIZE_STRING);
    $email = filter_var($body['email'], FILTER_SANITIZE_EMAIL);
    $empresa = filter_var($body['empresa'], FILTER_VALIDATE_INT);
    $status = filter_var($body['status'], FILTER_SANITIZE_STRING);
    $password = filter_var($body['password'], FILTER_SANITIZE_STRING);
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    if ($userName && $email && $status && $passwordHash) {
        try {
            if (registerUser($userName, $email, $empresa, $status, $passwordHash)) {
                http_response_code(200);
                echo json_encode(array("message" => "Usuário criado com sucesso!"));
            } else {
                http_response_code(500);
                echo json_encode(array("error" => "Erro ao criar o usuário"));
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
