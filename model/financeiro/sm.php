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

function novoFinanceiro($pedido, $financeiro, $idPedido){
    global $pdo;
    if (!$pdo) {
        die("Erro ao conectar ao banco de dados");
    }

    $stmt = $pdo->prepare("INSERT INTO `mov_sf`(`ID_ORIGEM`, `DTINC`, `VALOR`, `DTVENC`) VALUES (?, NOW(), ?, ?)");

    if ($stmt === false) {
        die("Erro ao preparar a consulta: " . $pdo->errorInfo()[2]);
    }

    foreach ($financeiro as $item) {
        $vencimento = date('Y-m-d H:i:s', strtotime($item['vencimento']));
        $valor = $item['valor_parcela'];

        $stmt->bindValue(1, $idPedido);
        $stmt->bindValue(2, $valor);
        $stmt->bindValue(3, $vencimento);

        $stmt->execute();

        if ($stmt->errorInfo()[0] !== '00000') {
            die("Erro ao executar a consulta: " . $stmt->errorInfo()[2]);
        }
    }

    $stmt->closeCursor();

    return $pdo->lastInsertId();
}

function obterParcelas($idPedido) {

    global $pdo;
    $query = "SELECT * FROM mov_sf WHERE id_origem = :id_pedido";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':id_pedido', $idPedido);
    $stmt->execute();
    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return json_encode($dados, JSON_PRETTY_PRINT);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($body['pedido'], $body['financeiro'])) {

    $idPedido = isset($_GET['id_pedido']) ? $_GET['id_pedido'] : null;

    $lastId = novoFinanceiro($body['pedido'], $body['financeiro'], $idPedido);

    echo json_encode(array("id_inserido" => $lastId));
}else if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    if (isset($_GET['id_pedido'])) {
        $idPedido = $_GET['id_pedido'];
        echo obterParcelas($idPedido);
    } 
}

?>
