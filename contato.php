<?php
// Recebe o formulário "Envie uma mensagem" da página Contato.
session_start();
require "funcoes.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: contato.html");
    exit;
}


// Se a pessoa estiver logada, o registro fica ligado à conta dela (aparece em "Minha conta")
$usuario_id = usuario_logado_id();

function campo($k) { return isset($_POST[$k]) ? trim($_POST[$k]) : ""; }

$nome     = campo("nome");
$email    = campo("email");
$mensagem = campo("mensagem");

if ($nome === "" || strlen($nome) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)
    || $mensagem === "" || strlen($mensagem) > 5000) {
    header("Location: contato.html?erro=contato#mensagemSucesso");
    exit;
}

try {
    $st = $pdo->prepare("INSERT INTO mensagens (usuario_id, nome, email, mensagem) VALUES (?, ?, ?, ?)");
    $st->execute(array($usuario_id, $nome, $email, $mensagem));
} catch (PDOException $e) {
    header("Location: contato.html?erro=contato#mensagemSucesso");
    exit;
}

header("Location: contato.html?enviado=ok#mensagemSucesso");
exit;
