<?php
// Etapa 2 da doação em dinheiro: a pessoa paga o pedido.
//  - Pix: mostra o QR Code e o "copia e cola", conta o prazo e confere sozinho se o pagamento chegou.
//  - Cartão: formulário do cartão. O número inteiro e o CVV NÃO são enviados ao servidor:
//    o JavaScript confere os dados e manda só a bandeira e os 4 últimos dígitos.
// É uma simulação: nenhum banco é chamado e nenhum valor é cobrado.
require "funcoes.php";
iniciar_sessao();

$codigo = isset($_REQUEST["c"]) ? $_REQUEST["c"] : "";
$p = buscar_pedido($codigo);
if (!$p) {
    header("Location: pagamento.html?erro=pedido");
    exit;
}

$recusado = false;
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $acao = isset($_POST["acao"]) ? $_POST["acao"] : "";

    if ($acao === "cancelar" && $p["status"] === "Aguardando") {
        $pdo->prepare("UPDATE pagamentos SET status = 'Cancelado' WHERE id = ? AND status = 'Aguardando'")
            ->execute(array($p["id"]));
        header("Location: pagamento.html?pedido=cancelado");
        exit;
    }

    if ($acao === "cartao" && $p["metodo"] === "Cartão" && $p["status"] === "Aguardando") {
        $bandeiras = array("Visa", "Mastercard", "Elo", "American Express", "Hipercard");
        $bandeira = isset($_POST["bandeira"]) ? $_POST["bandeira"] : "";
        $final    = isset($_POST["final"]) ? $_POST["final"] : "";
        if (!in_array($bandeira, $bandeiras, true) || !preg_match('/^\d{4}$/', $final)) {
            header("Location: pagamento_pedido.php?c=" . $p["codigo"] . "&erro=cartao");
            exit;
        }
        // Cartão de teste que o "banco" recusa, para mostrar como fica uma recusa
        if ($final === "0002") {
            header("Location: pagamento_pedido.php?c=" . $p["codigo"] . "&recusado=1");
            exit;
        }
        confirmar_pagamento($p, $bandeira . " final " . $final);
        header("Location: pagamento_recibo.php?c=" . $p["codigo"]);
        exit;
    }
    header("Location: pagamento_pedido.php?c=" . $p["codigo"]);
    exit;
}

if ($p["status"] === "Pago" || in_array($p["status"], $SITUACOES_PAGO, true)) {
    header("Location: pagamento_recibo.php?c=" . $p["codigo"]);
    exit;
}

$numero = numero_pedido($p["id"]);
site_inicio("Pagamento do pedido " . $numero, "doacao.html");
etapas_pagamento(2);
?>
<?php if ($p["status"] !== "Aguardando"): ?>
    <div class="pedido-fim">
        <h1><?= $p["status"] === "Expirado" ? "O prazo deste pedido acabou" : "Este pedido foi cancelado" ?></h1>
        <p>O pedido <strong><?= $numero ?></strong> de <?= dinheiro($p["valor"]) ?> não foi pago
           <?= $p["status"] === "Expirado" ? "dentro de " . MINUTOS_PEDIDO . " minutos" : "" ?>. Nenhum valor foi cobrado.</p>
        <a class="botao" href="pagamento.html?valor=<?= h($p["valor"]) ?>">Fazer um novo pedido</a>
    </div>
<?php else: ?>
    <h1><?= $p["metodo"] === "Pix" ? "Pague com Pix" : "Pague com cartão de crédito" ?></h1>

    <div class="pedido-resumo">
        <div><span>Pedido</span><strong><?= $numero ?></strong></div>
        <div><span>Doador</span><strong><?= h($p["nome"]) ?></strong></div>
        <div><span>Para</span><strong>Infância Viva</strong></div>
        <div class="pedido-valor"><span>Valor</span><strong><?= dinheiro($p["valor"]) ?></strong></div>
    </div>

    <?php if ($p["metodo"] === "Pix"): ?>
        <div class="pix-pagar" id="pixPagar" data-codigo="<?= h($p["codigo"]) ?>" data-segundos="<?= (int) $p["segundos_restantes"] ?>">
            <div class="pix-qr">
                <div id="pixQr" data-qr="<?= h(endereco_site() . "/pagamento_simular.php?c=" . $p["codigo"]) ?>" role="img" aria-label="QR Code do Pix"></div>
                <p class="pix-prazo">Pague em até <strong id="pixTempo">--:--</strong></p>
            </div>
            <div class="pix-instrucoes">
                <ol>
                    <li>Abra o app do seu banco e entre na área <strong>Pix</strong>.</li>
                    <li>Escolha <strong>Pagar com QR Code</strong> e aponte a câmera, ou use o <strong>Pix copia e cola</strong>.</li>
                    <li>Confira o valor (<?= dinheiro($p["valor"]) ?>) e o recebedor (Infância Viva) e confirme.</li>
                </ol>
                <label for="pixCodigo">Pix copia e cola</label>
                <div class="copia-cola">
                    <input type="text" id="pixCodigo" readonly value="<?= h(pix_copia_e_cola($p["valor"], $numero)) ?>">
                    <button type="button" class="botao-secundario" id="pixCopiar">Copiar</button>
                </div>
                <p class="status-pedido" id="pixStatus" aria-live="polite"><span class="girando" aria-hidden="true"></span>Aguardando o pagamento…</p>
            </div>
        </div>

        <div class="demo-caixa">
            <strong>Ambiente de demonstração</strong>
            <p>Nenhum banco de verdade está ligado ao site, então o Pix acima não cobra nada. Para simular que o
               pagamento chegou, <strong>escaneie o QR Code com a câmera do celular</strong> (abre uma tela de
               pagamento de teste) ou clique no botão abaixo. Esta página percebe sozinha quando o pagamento é feito.</p>
            <form action="pagamento_simular.php" method="post">
                <input type="hidden" name="c" value="<?= h($p["codigo"]) ?>">
                <input type="hidden" name="voltar" value="1">
                <button type="submit">Simular pagamento recebido</button>
            </form>
        </div>
    <?php else: ?>
        <?php if (isset($_GET["recusado"])): ?>
            <p class="aviso erro">Pagamento recusado pelo banco do cartão. Nenhum valor foi cobrado. Confira os dados ou use outro cartão.</p>
        <?php elseif (isset($_GET["erro"])): ?>
            <p class="aviso erro">Não foi possível ler os dados do cartão. Confira e tente de novo.</p>
        <?php endif; ?>

        <div class="cartao-grade">
        <form id="formCartao" class="form-cartao" action="pagamento_pedido.php" method="post" novalidate>
            <input type="hidden" name="c" value="<?= h($p["codigo"]) ?>">
            <input type="hidden" name="acao" value="cartao">
            <input type="hidden" name="bandeira">
            <input type="hidden" name="final">

            <!-- Os campos abaixo NÃO têm "name": o navegador não envia o número nem o CVV para o servidor -->
            <label for="cartaoNumero">Número do cartão</label>
            <div class="campo-cartao">
                <input type="text" id="cartaoNumero" inputmode="numeric" autocomplete="cc-number" maxlength="23" placeholder="0000 0000 0000 0000">
                <span id="cartaoBandeira" class="bandeira"></span>
            </div>
            <label for="cartaoNome">Nome impresso no cartão</label>
            <input type="text" id="cartaoNome" autocomplete="cc-name" placeholder="Como está escrito no cartão">
            <div class="cartao-linha">
                <div>
                    <label for="cartaoValidade">Validade</label>
                    <input type="text" id="cartaoValidade" inputmode="numeric" autocomplete="cc-exp" maxlength="5" placeholder="MM/AA">
                </div>
                <div>
                    <label for="cartaoCvv">CVV</label>
                    <input type="text" id="cartaoCvv" inputmode="numeric" autocomplete="cc-csc" maxlength="4" placeholder="123">
                </div>
            </div>
            <p id="aviso" class="aviso" aria-live="polite"></p>
            <button type="submit">Pagar <?= dinheiro($p["valor"]) ?></button>
        </form>

        <div class="demo-caixa">
            <strong>Ambiente de demonstração</strong>
            <p>Nenhum valor é cobrado e o número do cartão não é enviado nem guardado (só a bandeira e os 4 últimos
               dígitos). Use um <strong>cartão de teste</strong>:</p>
            <ul>
                <li><code>4111 1111 1111 1111</code>: pagamento aprovado</li>
                <li><code>4000 0000 0000 0002</code>: pagamento recusado</li>
            </ul>
            <p>Qualquer nome, uma validade futura (ex.: 12/30) e qualquer CVV de 3 dígitos.</p>
        </div>
        </div>

        <div class="processando" id="processando" hidden>
            <div><span class="girando" aria-hidden="true"></span><strong>Processando pagamento…</strong><p>Não feche esta página.</p></div>
        </div>
    <?php endif; ?>

    <form class="cancelar-pedido" action="pagamento_pedido.php" method="post"
          onsubmit="return confirm('Cancelar este pedido? Nenhum valor será cobrado.')">
        <input type="hidden" name="c" value="<?= h($p["codigo"]) ?>">
        <input type="hidden" name="acao" value="cancelar">
        <button type="submit" class="botao-link">Cancelar pedido</button>
    </form>
<?php endif; ?>
<?php
site_fim($p["metodo"] === "Pix" && $p["status"] === "Aguardando" ? array("lib/qrcode/qrcode.js") : array());
