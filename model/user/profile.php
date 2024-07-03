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

function obterUsuario() {
    try {
        $conexao = new Conexao();
        $pdo = $conexao->conectar();
        
        $stmt = $pdo->query("SELECT * FROM v_profile");
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return json_encode($usuarios);
    } catch (Exception $e) {
        http_response_code(500);
        return json_encode(array("error" => "Erro ao obter usuários"));
    }
}

function obterUsuarioId($idUser) {
    try {
        $conexao = new Conexao();
        $pdo = $conexao->conectar();
        
        $stmt = $pdo->prepare("SELECT * FROM v_profile WHERE Id = :idUser");
        $stmt->bindParam(':idUser', $idUser, PDO::PARAM_INT);
        $stmt->execute();
        
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($usuario) {
            return json_encode($usuario);
        } else {
            http_response_code(404);
            return json_encode(array("error" => "Usuário não encontrado"));
        }
    } catch (Exception $e) {
        http_response_code(500);
        return json_encode(array("error" => "Erro ao obter usuário"));
    }
}

function validarToken($token) {

    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://localhost/adminAPI-php/model/auth/authToken.php'); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response !== false) {
            $decoded_response = json_decode($response, true);
            if (isset($decoded_response['status']) && $decoded_response['status'] === 'valid') {
                return true;
            }
        }

        return false;
    } catch (Exception $e) {
        return false;
    }
}

// Verifica se é uma requisição GET
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Verifica se foi passado o parâmetro idUser
    $idUser = isset($_GET['idUser']) ? $_GET['idUser'] : null;

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

    if (!$token) {
        http_response_code(401);
        echo json_encode(array("error" => "Token não fornecido"));
        exit;
    }

    if (!validarToken($token)) {
        http_response_code(401);
        echo json_encode(array("error" => "Token inválido"));
        exit;
    }

    if ($idUser !== null) {
        echo obterUsuarioId($idUser);
    } else {
        echo obterUsuario();
    }
}
?>
