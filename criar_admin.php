<?php
// RODE UMA ÚNICA VEZ e depois APAGUE este arquivo da pasta.
header("Content-Type: text/html; charset=UTF-8");
require "conexao.php";

$email = "admin@infanciaviva.com";
$senha = "admin123";   // temporária: troque depois

$st = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
$st->execute(array($email));

if ($st->fetch()) {
    echo "O administrador já existe. Pode apagar este arquivo.";
} else {
    $st = $pdo->prepare("INSERT INTO usuarios (nome, email, senha_hash, tipo) VALUES (?, ?, ?, 'admin')");
    $st->execute(array("Administrador", $email, password_hash($senha, PASSWORD_DEFAULT)));
    echo "Administrador criado! E-mail: $email | Senha: $senha<br>Agora APAGUE este arquivo da pasta.";
}
