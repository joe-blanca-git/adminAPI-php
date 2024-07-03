<?php
// Senha a ser criptografada
$password = '123';

// Gerar o hash da senha usando bcrypt
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

// Exibir o hash gerado
echo "Senha criptografada: " . $hashedPassword;
?>