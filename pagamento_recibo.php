<?php
// Etapas 3 e 4 da doação em dinheiro: confirmação do pagamento e recibo para imprimir.
require "funcoes.php";
iniciar_sessao();

$p = buscar_pedido(isset($_GET["c"]) ? $_GET["c"] : "");
if (!$p) {
    header("Location: pagamento.html?erro=pedido");
    exit;
}
if ($p["status"] !== "Pago") {
    header("Location: pagamento_pedido.php?c=" . $p["codigo"]);
    exit;
}

$numero = numero_pedido($p["id"]);
site_inicio("Recibo " . $numero, "doacao.html");
etapas_pagamento(4);
?>
<div class="confirmado">
    <div class="confirmado-icone" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></div>
    <h1>Pagamento confirmado!</h1>
    <p>Obrigado, <?= h(strtok($p["nome"], " ")) ?>! A sua doação de <strong><?= dinheiro($p["valor"]) ?></strong>
       vai ajudar a levar brinquedos até as crianças.</p>
    <?php if ($p["recibo_enviado"]): ?>
        <p class="confirmado-email">Enviamos uma cópia do recibo para <strong><?= h($p["email"]) ?></strong>.</p>
    <?php endif; ?>
</div>

<article class="recibo" id="recibo">
    <header class="recibo-topo">
        <div class="recibo-marca">
            <img src="imagens/logo_infancia_viva.png?v=2" alt="Infância Viva" width="150">
        </div>
        <div class="recibo-numero">
            <span>Recibo de doação</span>
            <strong><?= $numero ?></strong>
        </div>
    </header>

    <p class="recibo-texto">
        Recebemos de <strong><?= h($p["nome"]) ?></strong> a quantia de
        <strong><?= dinheiro($p["valor"]) ?></strong> (<?= h(por_extenso($p["valor"])) ?>),
        referente a uma doação em dinheiro para o projeto <strong>Infância Viva</strong>.
    </p>

    <dl class="recibo-dados">
        <div><dt>Data do pagamento</dt><dd><?= h($p["pago_quando"]) ?></dd></div>
        <div><dt>Forma de pagamento</dt><dd><?= h(forma_pagamento($p)) ?></dd></div>
        <div><dt>Situação</dt><dd>Pago</dd></div>
        <div><dt>Autenticação</dt><dd><code><?= autenticacao_recibo($p) ?></code></dd></div>
    </dl>

    <div class="recibo-assinatura">
        <span>Infância Viva</span>
        <small>Doar brinquedos. Transformar infâncias.</small>
    </div>

    <p class="recibo-aviso">Documento emitido no ambiente de demonstração do site (projeto de TCC).
       Nenhum valor foi cobrado e este recibo não tem valor fiscal.</p>
</article>

<div class="recibo-botoes">
    <button type="button" id="imprimirRecibo">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9V3h12v6"/><rect x="3" y="9" width="18" height="8" rx="2"/><path d="M6 14h12v7H6z"/></svg>
        Imprimir recibo
    </button>
    <a class="botao contorno" href="index.html">Voltar para o início</a>
</div>
<?php if (usuario_logado_id()): ?>
    <p class="centro"><a href="minha_conta.php">Ver minhas doações</a></p>
<?php endif; ?>
<?php
site_fim();
