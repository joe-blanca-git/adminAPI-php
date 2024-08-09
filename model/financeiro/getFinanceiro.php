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

function obterFinanceiro($dtDE, $dtAte, $PessoaOrigem, $Conta, $Categoria, $EmpresaId)
{
    global $pdo;

    // Inicializa a consulta base
    $query = "SELECT * FROM v_financeiro WHERE EmpresaId = :EmpresaId";

    // Inicializa o array de parâmetros
    $params = [
        ':EmpresaId' => $EmpresaId
    ];

    // Adiciona filtros de data se as datas forem fornecidas
    if ($dtDE !== null && $dtAte !== null) {
        $dtDeFormatted = date('Y-m-d 00:00:00', strtotime($dtDE)); // Início do dia
        $dtAteFormatted = date('Y-m-d 23:59:59', strtotime($dtAte)); // Fim do dia

        $query .= " AND Dt_inc BETWEEN :dtDe AND :dtAte";
        $params[':dtDe'] = $dtDeFormatted;
        $params[':dtAte'] = $dtAteFormatted;
    }

    // Adiciona filtro de PessoaOrigem se fornecido
    if ($PessoaOrigem !== null) {
        $query .= " AND PessoaOrigemId = :PessoaOrigemId";
        $params[':PessoaOrigemId'] = $PessoaOrigem;
    }

    // Adiciona filtro de Conta se fornecido
    if ($Conta !== null) {
        $query .= " AND ContaId = :ContaId";
        $params[':ContaId'] = $Conta;
    }

    // Adiciona filtro de Categoria se fornecido
    if ($Categoria !== null) {
        $query .= " AND CategoriaId = :CategoriaId";
        $params[':CategoriaId'] = $Categoria;
    }

    // Se EmpresaId não for fornecido, retorna mensagem de erro
    if ($EmpresaId === null) {
        return 'Empresa não informada';
    }

    // Prepara e executa a consulta
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    
    // Obtém os dados
    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Converte tipos numéricos
    foreach ($dados as &$row) {
        foreach ($row as $key => &$value) {
            if (is_numeric($value)) {
                // Verifica se é um inteiro ou float e converte
                if (strpos($value, '.') !== false) {
                    $value = (float)$value;
                } else {
                    $value = (int)$value;
                }
            }
        }
    }

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

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['EmpresaId'])) {

    if (isset($_GET['dtDe']) && isset($_GET['dtAte'])) {
        $dtDE = $_GET['dtDe'];
        $dtAte = $_GET['dtAte'];
    } else {
        $dtDE = null;
        $dtAte = null;
    }

    if (isset($_GET['PessoaOrigemId'])){
        $PessoaOrigem = $_GET['PessoaOrigemId'];
    }else {
        $PessoaOrigem = null;
    }

    if (isset($_GET['ContaId'])){
        $Conta = $_GET['ContaId'];
    }else {
        $Conta = null;
    }

    if (isset($_GET['CategoriaId'])){
        $Categoria = $_GET['CategoriaId'];
    }else {
        $Categoria = null;
    }

    $EmpresaId = $_GET['EmpresaId'];
    
    $resultado = obterFinanceiro($dtDE, $dtAte, $PessoaOrigem, $Conta, $Categoria, $EmpresaId);
    
    echo $resultado;
} else {
    http_response_code(400);
    echo json_encode(array("mensagem" => "Erro: Nome da tabela ou EmpresaId não fornecido."));
}
?>
