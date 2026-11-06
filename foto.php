<?php
// Mostra a foto de perfil enviada por um usuário. Só o próprio usuário e o administrador podem ver.
session_start();
require "funcoes.php";

$id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;
$eu = usuario_logado_id();
if ($eu === null) {
    http_response_code(403);
    exit;
}
if ($eu !== $id) {
    $st = $pdo->prepare("SELECT tipo FROM usuarios WHERE id = ?");
    $st->execute(array($eu));
    $quem = $st->fetch();
    if (!$quem || $quem["tipo"] !== "admin") {
        http_response_code(403);
        exit;
    }
}

$st = $pdo->prepare("SELECT foto_dados FROM usuarios WHERE id = ?");
$st->execute(array($id));
$dados = $st->fetchColumn();
if (!$dados) {
    http_response_code(404);
    exit;
}

$info = getimagesizefromstring($dados);
header("Content-Type: " . $info["mime"]);
header("X-Content-Type-Options: nosniff");
header("Cache-Control: private, max-age=31536000"); // o endereço muda (?v=...) quando a foto é trocada
echo $dados;
