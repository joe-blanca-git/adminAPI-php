<?php

class conexao{

private static $pdo;
public function conectar(){
try{
if(is_null(self::$pdo)){
self::$pdo = new PDO("mysql:host=localhost;dbname=mbsis;charset=utf8","root","mysql");
self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
self::$pdo->exec("SET NAMES utf8");
}
return self::$pdo;
}catch(PDOException $e){
echo $e->getmessage();
echo "N達o conectado!!";
}
}
}
?>