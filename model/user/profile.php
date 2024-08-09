<?php
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, GET, PUT, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
    exit;
}

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, PUT, OPTIONS");
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

function atualizaUsuario($idUser, $dados) {
    try {
        $conexao = new Conexao();
        $pdo = $conexao->conectar();

        // Atualizar a tabela 'users'
        $updateUsersQuery = "UPDATE apibit.users SET ";
        $updateUsersParams = [];
        
        if (isset($dados['UserName'])) {
            $updateUsersQuery .= "UserName = :UserName, ";
            $updateUsersParams[':UserName'] = $dados['UserName'];
        }
        if (isset($dados['Email'])) {
            $updateUsersQuery .= "Email = :Email, ";
            $updateUsersParams[':Email'] = $dados['Email'];
        }
        if (isset($dados['Status'])) {
            $updateUsersQuery .= "Status = :Status, ";
            $updateUsersParams[':Status'] = $dados['Status'];
        }

        // Remove a vírgula extra do final
        $updateUsersQuery = rtrim($updateUsersQuery, ", ");
        $updateUsersQuery .= " WHERE Id = :idUser";
        $updateUsersParams[':idUser'] = $idUser;

        $stmt = $pdo->prepare($updateUsersQuery);
        $stmt->execute($updateUsersParams);

        // Atualizar a tabela 'pessoa'
        $updatePessoaQuery = "UPDATE apibit.pessoa SET ";
        $updatePessoaParams = [];
        
        if (isset($dados['Nome'])) {
            $updatePessoaQuery .= "Nome = :Nome, ";
            $updatePessoaParams[':Nome'] = $dados['Nome'];
        }
        if (isset($dados['Descricao'])) {
            $updatePessoaQuery .= "Descricao = :Descricao, ";
            $updatePessoaParams[':Descricao'] = $dados['Descricao'];
        }
        if (isset($dados['Cargo'])) {
            $updatePessoaQuery .= "Cargo = :Cargo, ";
            $updatePessoaParams[':Cargo'] = $dados['Cargo'];
        }
        if (isset($dados['Idade'])) {
            $updatePessoaQuery .= "Idade = :Idade, ";
            $updatePessoaParams[':Idade'] = $dados['Idade'];
        }
        if (isset($dados['Fone'])) {
            $updatePessoaQuery .= "Fone = :Fone, ";
            $updatePessoaParams[':Fone'] = $dados['Fone'];
        }
        if (isset($dados['Estado'])) {
            $updatePessoaQuery .= "Estado = :Estado, ";
            $updatePessoaParams[':Estado'] = $dados['Estado'];
        }
        if (isset($dados['Cidade'])) {
            $updatePessoaQuery .= "Cidade = :Cidade, ";
            $updatePessoaParams[':Cidade'] = $dados['Cidade'];
        }
        if (isset($dados['EmpresaId'])) {
            $updatePessoaQuery .= "EmpresaId = :EmpresaId, ";
            $updatePessoaParams[':EmpresaId'] = $dados['EmpresaId'];
        }
        if (isset($dados['urlImagem'])) {
            $updatePessoaQuery .= "imgUrl = :urlImagem, ";
            $updatePessoaParams[':urlImagem'] = $dados['urlImagem'];
        }

        // Remove a vírgula extra do final
        $updatePessoaQuery = rtrim($updatePessoaQuery, ", ");
        $updatePessoaQuery .= " WHERE UserId = :idUser";
        $updatePessoaParams[':idUser'] = $idUser;

        $stmt = $pdo->prepare($updatePessoaQuery);
        $stmt->execute($updatePessoaParams);

        return json_encode(array("message" => "Usuário atualizado com sucesso"));
    } catch (Exception $e) {
        http_response_code(500);
        return json_encode(array("error" => "Erro ao atualizar usuário"));
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $idUser = isset($_GET['UserId']) ? $_GET['UserId'] : null;

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

    if ($idUser !== null) {
        echo obterUsuarioId($idUser);
    } else {
        echo obterUsuario();
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $idUser = isset($_GET['UserId']) ? $_GET['UserId'] : null;
    $dados = json_decode(file_get_contents('php://input'), true);

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

    if ($idUser !== null && !empty($dados)) {
        echo atualizaUsuario($idUser, $dados);
    } else {
        http_response_code(400);
        echo json_encode(array("error" => "Dados insuficientes para atualizar usuário"));
    }
}
?>
