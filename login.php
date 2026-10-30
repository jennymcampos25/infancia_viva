<?php
// Recebe o formulário de login.html e confere e-mail e senha no banco.
session_start();
require "conexao.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.html");
    exit;
}

$email = isset($_POST["email"]) ? strtolower(trim($_POST["email"])) : "";
$senha = isset($_POST["senha"]) ? $_POST["senha"] : "";

$st = $pdo->prepare("SELECT id, nome, email, senha_hash, tipo FROM usuarios WHERE email = ?");
$st->execute(array($email));
$u = $st->fetch();

// Mesma mensagem para "e-mail não existe" e "senha errada" (não ajuda quem tenta adivinhar)
if (!$u || !password_verify($senha, $u["senha_hash"])) {
    header("Location: login.html?erro=login");
    exit;
}

session_regenerate_id(true);
$_SESSION["usuario_id"]   = $u["id"];
$_SESSION["usuario_nome"] = $u["nome"];
$_SESSION["usuario_tipo"] = $u["tipo"];

// Administrador vai direto para o painel; os demais, para a página inicial
header("Location: " . ($u["tipo"] === "admin" ? "admin.php" : "index.html"));
exit;
