<?php
// Faz o papel do "app do banco" na demonstração do Pix.
//  - Abrindo pelo QR Code (câmera do celular): mostra uma tela de pagamento de teste.
//  - Clicando em "Confirmar" (ou no botão "Simular pagamento recebido" do computador): marca o pedido como pago.
// A página do pedido, aberta no computador, percebe sozinha e vai para o recibo.
require "funcoes.php";

$codigo = isset($_REQUEST["c"]) ? $_REQUEST["c"] : "";
$p = buscar_pedido($codigo);
if (!$p || $p["metodo"] !== "Pix") {
    header("Location: pagamento.html?erro=pedido");
    exit;
}

$feito = false;
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if ($p["status"] === "Aguardando") confirmar_pagamento($p, null);
    if (!empty($_POST["voltar"])) {
        header("Location: pagamento_recibo.php?c=" . $p["codigo"]);
        exit;
    }
    $p = buscar_pedido($codigo);
    $feito = true;
}

$numero = numero_pedido($p["id"]);
site_inicio("Pagar com Pix (teste)", "doacao.html");
?>
<div class="banco-teste">
    <p class="banco-teste-topo">Pix · tela de teste</p>
    <?php if ($p["status"] === "Pago"): ?>
        <div class="banco-ok" aria-hidden="true">✓</div>
        <h1>Pix enviado</h1>
        <p class="banco-valor"><?= dinheiro($p["valor"]) ?></p>
        <p>para <strong>Infância Viva</strong> · pedido <?= $numero ?></p>
        <p><?= $feito ? "Pronto! Pode voltar para a tela do computador: ela já mostra a confirmação."
                      : "Este pedido já foi pago." ?></p>
        <a class="botao" href="pagamento_recibo.php?c=<?= h($p["codigo"]) ?>">Ver recibo</a>
    <?php elseif ($p["status"] !== "Aguardando"): ?>
        <h1>Pedido indisponível</h1>
        <p>O pedido <?= $numero ?> está <strong><?= h(mb_strtolower($p["status"], "UTF-8")) ?></strong> e não pode mais ser pago.</p>
        <a class="botao" href="pagamento.html">Fazer um novo pedido</a>
    <?php else: ?>
        <p>Você está pagando</p>
        <p class="banco-valor"><?= dinheiro($p["valor"]) ?></p>
        <dl class="banco-dados">
            <dt>Para</dt><dd>Infância Viva</dd>
            <dt>Pedido</dt><dd><?= $numero ?></dd>
            <dt>Doador</dt><dd><?= h($p["nome"]) ?></dd>
        </dl>
        <form method="post">
            <input type="hidden" name="c" value="<?= h($p["codigo"]) ?>">
            <button type="submit">Confirmar pagamento</button>
        </form>
        <p class="banco-nota">Demonstração do projeto de TCC: nenhum valor sai da sua conta.</p>
    <?php endif; ?>
</div>
<?php
site_fim();
