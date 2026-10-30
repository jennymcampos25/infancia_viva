<?php
// Registra o pagamento (SIMULADO) no banco. Nenhum dado de cartão é enviado nem guardado:
// os campos do cartão na página não têm "name", então o navegador nem os envia.
session_start();
require "funcoes.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: pagamento.html");
    exit;
}


// Se a pessoa estiver logada, o registro fica ligado à conta dela (aparece em "Minha conta")
$usuario_id = usuario_logado_id();

function campo($k) { return isset($_POST[$k]) ? trim($_POST[$k]) : ""; }

$nome   = campo("nome");
$valor  = str_replace(",", ".", campo("valor"));
$metodo = campo("metodo");
$metodos = array("pix" => "Pix", "cartao" => "Cartão");

if ($nome === "" || strlen($nome) > 100 || !is_numeric($valor) || $valor <= 0
    || $valor > 100000 || !isset($metodos[$metodo])) {
    header("Location: pagamento.html?erro=pagamento");
    exit;
}

try {
    // GATEWAY: se um dia houver pagamento real (Mercado Pago etc.), a cobrança entra AQUI, antes do INSERT.
    $st = $pdo->prepare("INSERT INTO pagamentos (usuario_id, nome, valor, metodo) VALUES (?, ?, ?, ?)");
    $st->execute(array($usuario_id, $nome, number_format((float) $valor, 2, ".", ""), $metodos[$metodo]));
} catch (PDOException $e) {
    header("Location: pagamento.html?erro=erro");
    exit;
}

header("Location: pagamento.html?pagamento=ok");
exit;
