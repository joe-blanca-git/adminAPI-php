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

function movEstoque($pedido, $itens, $idPedido){
    $conexao = new conexao();
    $pdo = $conexao->conectar();

    $stmt = $pdo->prepare("INSERT INTO `mov_estoque` (
        `ID_ORIGEM`,
        `ORIGEM`,
        `ID_ITEM`,
        `QTDE`,
        `DT_INC`
    ) VALUES (?, ?, ?, ?, NOW())");

    if ($stmt === false) {
        die("Erro ao preparar a consulta: " . $pdo->errorInfo()[2]);
    }

    

    foreach ($itens as $item) {

        if($pedido['tipo_mov'] == 'V'){
            $tipoMov = 'B';
        }elseif($pedido['tipo_mov'] == 'C'){
            $tipoMov = 'E';
        }elseif($pedido['tipo_mov'] == 'B'){
            $tipoMov = 'B';
        }elseif($pedido['tipo_mov'] == 'P'){
            $tipoMov = 'B';
        }

        $id_item = $item['id_item'];
        $qtde = $item['qtde'];
        
        $stmt->bindValue(1, $idPedido);
        $stmt->bindValue(2, $tipoMov);
        $stmt->bindValue(3, $id_item);
        $stmt->bindValue(4, $qtde);
        
        $stmt->execute();

        if ($stmt->errorInfo()[0] !== '00000') {
            die("Erro ao executar a consulta: " . $stmt->errorInfo()[2]);
        }
    }

    $stmt->closeCursor();

    return $pdo->lastInsertId();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($body['pedido'], $body['itens'])) {

    $idPedido = isset($_GET['id_pedido']) ? $_GET['id_pedido'] : null;
    $lasId = movEstoque($body['pedido'], $body['itens'], $idPedido);

    echo json_encode(array("id_inserido" => $lasId));
}

?>
