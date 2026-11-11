<?php
// Recebe a avaliação (nota e comentário são opcionais, mas precisa ter pelo menos um).
require "funcoes.php";
iniciar_sessao();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: contato.html");
    exit;
}


// Se a pessoa estiver logada, o registro fica ligado à conta dela (aparece em "Minha conta")
$usuario_id = usuario_logado_id();

function campo($k) { return isset($_POST[$k]) ? trim($_POST[$k]) : ""; }

$nota       = (int) campo("avaliacao");
$comentario = campo("comentario");
if ($nota < 1 || $nota > 5) {
    $nota = null;
}

if (($nota === null && $comentario === "") || strlen($comentario) > 5000) {
    header("Location: contato.html?erro=avaliacao#mensagemAvaliacao");
    exit;
}

try {
    $st = $pdo->prepare("INSERT INTO avaliacoes (usuario_id, nota, comentario) VALUES (?, ?, ?)");
    $st->execute(array($usuario_id, $nota, ($comentario === "" ? null : $comentario)));
} catch (PDOException $e) {
    header("Location: contato.html?erro=avaliacao#mensagemAvaliacao");
    exit;
}

header("Location: contato.html?avaliado=ok#mensagemAvaliacao");
exit;
