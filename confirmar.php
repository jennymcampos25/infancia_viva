<?php
// Link do e-mail de confirmação: confirmar.php?codigo=...
require "funcoes.php";
garantir_colunas_confirmacao();

$codigo = isset($_GET["codigo"]) ? $_GET["codigo"] : "";
if (!preg_match('/^[0-9a-f]{64}$/', $codigo)) {
    header("Location: login.html?erro=link_invalido");
    exit;
}

// No banco fica só o hash do código (quem visse o banco não conseguiria montar o link)
$st = $pdo->prepare("SELECT id, email FROM usuarios WHERE token_confirmacao = ? AND token_expira > NOW()");
$st->execute(array(hash("sha256", $codigo)));
$u = $st->fetch();
if (!$u) {
    header("Location: login.html?erro=link_invalido");
    exit;
}

$st = $pdo->prepare("UPDATE usuarios SET email_confirmado = 1, token_confirmacao = NULL, token_expira = NULL WHERE id = ?");
$st->execute(array($u["id"]));
header("Location: login.html?confirmado=ok&email=" . urlencode($u["email"]));
exit;
