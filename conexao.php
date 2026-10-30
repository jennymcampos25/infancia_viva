<?php
// Conexão com o banco de dados (PDO).
// Todas as páginas PHP que precisarem do banco fazem:  require "conexao.php";
// Se um dia mudar a senha ou o nome do banco, é só alterar aqui.

$host    = "127.0.0.1";
$banco   = "infancia_viva";
$usuario = "root";
$senha   = "";          // no EasyPHP o root vem sem senha

// true = mostra o erro real na tela (bom enquanto você está desenvolvendo)
// false = mostra uma mensagem genérica (use quando o site estiver "no ar")
$MODO_DEV = true;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$banco;charset=utf8mb4", $usuario, $senha);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    if ($MODO_DEV) {
        die("Erro ao conectar no banco: " . htmlspecialchars($e->getMessage()));
    }
    die("Não foi possível conectar ao banco de dados.");
}
