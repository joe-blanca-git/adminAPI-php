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

$conexao = new conexao();
$pdo = $conexao->conectar();
$body = json_decode(file_get_contents('php://input'), true);

function obterLista($tabela)
{
    global $pdo;
    
    $query = "SELECT * FROM $tabela";
    $stmt = $pdo->query($query);
    
    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return json_encode($dados, JSON_PRETTY_PRINT);
}

if (isset($_GET['tabela'])) {
    $tabela = $_GET['tabela'];
    
    $resultado = obterLista($tabela);
    
    echo $resultado;
} else {
    http_response_code(400);
    echo json_encode(array("mensagem" => "Erro: Nome da tabela não fornecido."));
}

?>
