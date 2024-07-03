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

function novoPedido($dadosPedido)
{
    global $pdo;
    $colunas = implode(', ', array_keys($dadosPedido));
    $valores = ':' . implode(', :', array_keys($dadosPedido));

    $query = "INSERT INTO pedido ($colunas) VALUES ($valores)";
    $stmt = $pdo->prepare($query);

    foreach ($dadosPedido as $chave => $valor) {
        $stmt->bindValue(":$chave", $valor);
    }

    $stmt->execute();

     return $pdo->lastInsertId();
}

function inserirItens($idPedido, $itens)
{
    global $pdo;
    foreach ($itens as $item) {
        $colunas = implode(', ', array_keys($item));
        $valores = ':' . implode(', :', array_keys($item));

        $query = "INSERT INTO pedido_item (id_pedido, $colunas) VALUES (:id_pedido, $valores)";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':id_pedido', $idPedido);

        foreach ($item as $chave => $valor) {
            $stmt->bindValue(":$chave", $valor);
        }

        $stmt->execute();
    }
}

function obterPedidos($dtDe = null, $dtAte = null, $cliente = null, $tipo = null, $qtde = null)
{
    global $pdo;

    $query = "SELECT * FROM v_pedido";
    $params = [];
    $whereClause = "";

    if ($dtDe !== null && $dtAte !== null) {

        $dtDeFormatted = DateTime::createFromFormat('d/m/Y', $dtDe)->format('Y-m-d');
        $dtAteFormatted = DateTime::createFromFormat('d/m/Y', $dtAte)->format('Y-m-d');

        $whereClause .= " WHERE data BETWEEN :dtDe AND :dtAte";
        $params[':dtDe'] = $dtDeFormatted;
        $params[':dtAte'] = $dtAteFormatted;
    }

    if ($cliente !== null) {
        $whereClause .= ($whereClause ? " AND" : " WHERE") . " id_pessoa = :cliente";
        $params[':cliente'] = $cliente;
    }

    if ($tipo !== null) {
        $whereClause .= ($whereClause ? " AND" : " WHERE") . " sigla = :tipo";
        $params[':tipo'] = $tipo;
    }


    $query .= $whereClause . " ORDER BY data DESC LIMIT " . $qtde;

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return json_encode($dados, JSON_PRETTY_PRINT);
}

function obterPedidoID($idPedido) {
    global $pdo;
    $query = "SELECT * FROM v_pedido WHERE id = :id_pedido";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':id_pedido', $idPedido);
    $stmt->execute();
    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return json_encode($dados, JSON_PRETTY_PRINT);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $dtDe = isset($_GET['dtDe']) ? $_GET['dtDe'] : null;
    $dtAte = isset($_GET['dtAte']) ? $_GET['dtAte'] : null;
    $cliente = isset($_GET['cliente']) ? $_GET['cliente'] : null;
    $tipo = isset($_GET['tipo']) ? $_GET['tipo'] : null;
    $qtde = isset($_GET['qtde']) ? $_GET['qtde'] : null;

    if (isset($_GET['id_pedido'])) {
        $idPedido = $_GET['id_pedido'];
        echo obterPedidoID($idPedido);
    } else {
        echo obterPedidos($dtDe, $dtAte, $cliente, $tipo, $qtde);
    }
} elseif (!empty($body) && isset($body['pedido'], $body['itens'])) {
    $idInserido = novoPedido($body['pedido']);
    inserirItens($idInserido, $body['itens']);
    echo json_encode(array("idInserido" => $idInserido));
} else {
    http_response_code(400);
    echo json_encode(array("mensagem" => "Erro: Dados do pedido ou dos itens não encontrados."));
}
?>
