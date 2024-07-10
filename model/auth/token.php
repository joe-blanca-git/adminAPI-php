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
        
        $stmt = $pdo->prepare("SELECT Id, UserName, IdEmpresa, PasswordHash FROM users WHERE UserName = :user");
        $stmt->bindParam(':user', $user, PDO::PARAM_STR);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $userRecord = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $userRecord['PasswordHash'])) {
                
                $stmtRoles = $pdo->prepare("
                SELECT type, r.Name as value
                FROM roles r 
                INNER JOIN userroles ur ON ur.RoleId = r.Id 
                WHERE ur.UserId = :userId
            ");
            $stmtRoles->bindParam(':userId', $userRecord['Id'], PDO::PARAM_INT);
            $stmtRoles->execute();
            
            $roles = $stmtRoles->fetchAll(PDO::FETCH_ASSOC);

                $expiracao = new DateTime();
                $expiracao->add(new DateInterval('PT4H')); 

                $agora = new DateTime();
                $diff = $expiracao->getTimestamp() - $agora->getTimestamp();
                $expireIn = ($diff > 0) ? $diff : 0;

                $tokenData = array(
                    "access_token" => base64_encode(openssl_encrypt(json_encode(array(
                        "usuario" => array(
                            "userName" => $userRecord['UserName'],
                            "ID" => $userRecord['Id'],
                            "claims" => $roles
                        ),
                        "expiracao" => $expiracao->format('Y-m-d H:i:s'),
                        "expire_in" => $expireIn
                    )), 'AES-128-ECB', 'bitApi2024Likeaboss', OPENSSL_RAW_DATA)),
                    "expiracao" => $expiracao->format('Y-m-d H:i:s'),
                    "expire_in" => $expireIn,
                    "usuarioToken" => array(
                        "userName" => $userRecord['UserName'],
                        "id" => $userRecord['Id'],
                        "idEmpresa" => $userRecord['IdEmpresa'],
                        "claims" => $roles
                    )
                );

                return $tokenData;
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
                echo json_encode($token);
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
