<?php
// Etapa 1 da doação em dinheiro: recebe o formulário do pagamento.html e cria o PEDIDO
// (situação "Aguardando"). Depois manda a pessoa para a etapa 2 (pagamento_pedido.php),
// onde ela paga com Pix ou cartão. Tudo é simulado: nenhum banco é chamado.
require "funcoes.php";
iniciar_sessao();
garantir_colunas_pagamento();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: pagamento.html");
    exit;
}

// Se a pessoa estiver logada, o pedido fica ligado à conta dela (aparece em "Minha conta")
$usuario_id = usuario_logado_id();

function campo($k) { return isset($_POST[$k]) ? trim($_POST[$k]) : ""; }

$nome   = campo("nome");
$email  = strtolower(campo("email"));
$valor  = str_replace(",", ".", campo("valor"));
$metodo = campo("metodo");
$metodos = array("pix" => "Pix", "cartao" => "Cartão");

if ($nome === "" || strlen($nome) > 100 || !is_numeric($valor) || $valor < 1
    || $valor > 100000 || !isset($metodos[$metodo])) {
    header("Location: pagamento.html?erro=pagamento");
    exit;
}
if ($email !== "" && (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150)) {
    header("Location: pagamento.html?erro=email_invalido");
    exit;
}

// Código que vai no endereço das próximas páginas (difícil de adivinhar, então só quem fez o pedido vê)
$codigo = substr(codigo_aleatorio(), 0, 24);

try {
    $st = $pdo->prepare("INSERT INTO pagamentos (codigo, usuario_id, nome, email, valor, metodo, status, expira_em)
                         VALUES (?, ?, ?, ?, ?, ?, 'Aguardando', DATE_ADD(NOW(), INTERVAL " . MINUTOS_PEDIDO . " MINUTE))");
    $st->execute(array($codigo, $usuario_id, $nome, $email === "" ? null : $email,
                       number_format((float) $valor, 2, ".", ""), $metodos[$metodo]));
} catch (PDOException $e) {
    header("Location: pagamento.html?erro=erro");
    exit;
}

header("Location: pagamento_pedido.php?c=" . $codigo);
exit;
