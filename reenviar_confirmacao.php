<?php
// Manda um link de confirmação novo. A resposta é sempre a mesma, exista a conta ou não
// (assim ninguém usa esta página para descobrir quais e-mails têm conta no site).
require "funcoes.php";
garantir_colunas_confirmacao();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.html");
    exit;
}
$email = isset($_POST["email"]) ? strtolower(trim($_POST["email"])) : "";

$st = $pdo->prepare("SELECT id, nome, email, token_expira FROM usuarios WHERE email = ? AND email_confirmado = 0");
$st->execute(array($email));
$u = $st->fetch();

$resultado = "ok";
if ($u) {
    // Evita mandar vários e-mails seguidos: só gera link novo se o último tem mais de 1 minuto
    $criado = $u["token_expira"] ? strtotime($u["token_expira"]) - 24 * 3600 : 0;
    if (time() - $criado > 60 && !enviar_confirmacao($u["id"], $u["nome"], $u["email"])) {
        $resultado = "falha";
    }
}
header("Location: login.html?reenvio=" . $resultado . "&erro=nao_confirmado&email=" . urlencode($email));
exit;
