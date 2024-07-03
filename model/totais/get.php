<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../../database/Persistencia.php';

$conexao = new conexao();
$pdo = $conexao->conectar();
$body = json_decode(file_get_contents('php://input'), true);

function obterTotais($tabela, $dtDE = null, $dtAte = null)
{
    global $pdo;
    
    $query = "SELECT * FROM $tabela";

    if ($dtDE !== null && $dtAte !== null) {

        $dtDeFormatted = date('Y-m-d H:i:s', strtotime($dtDE));
        $dtAteFormatted = date('Y-m-d H:i:s', strtotime($dtAte));

        $params[':dtDe'] = $dtDeFormatted;
        $params[':dtAte'] = $dtAteFormatted;
    }
    
    
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    
    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return json_encode($dados, JSON_PRETTY_PRINT);
}

if (isset($_GET['tabela'])) {
    $tabela = $_GET['tabela'];
    
    if (isset($_GET['dtDe']) && isset($_GET['dtAte'])) {
        $dtDE = $_GET['dtDe'];
        $dtAte = $_GET['dtAte'];
    } else {
        $dtDE = null;
        $dtAte = null;
    }
    
    $resultado = obterTotais($tabela, $dtDE, $dtAte);
    
    echo $resultado;
} else {
    http_response_code(400);
    echo json_encode(array("mensagem" => "Erro: Nome da tabela não fornecido."));
}

?>
