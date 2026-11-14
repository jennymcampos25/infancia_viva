<?php
// Página "Minha conta" do doador, em duas abas:
//  - "Minhas doações": resumo em números e o histórico (doações de brinquedos, em dinheiro, mensagens e avaliações);
//  - "Meus dados": foto de perfil, nome e senha.
// Sem JavaScript, as duas abas aparecem uma embaixo da outra.
header("Content-Type: text/html; charset=UTF-8");
header("Cache-Control: no-store");
require "funcoes.php";
iniciar_sessao();

$u = exigir_login();
$hist = buscar_historico((int) $u["id"]);

// Resultado de "Meus dados" (vem do conta_salvar.php)
$avisos_conta = array(
    "nome_ok" => array(true, "Nome atualizado!"), "senha_ok" => array(true, "Senha alterada!"),
    "nome_vazio" => array(false, "Escreva o seu nome (até 100 letras)."),
    "senha_atual" => array(false, "A senha atual está errada."),
    "senha_curta" => array(false, "A senha nova precisa ter pelo menos 6 caracteres."),
    "senhas_diferentes" => array(false, "As senhas novas não conferem."),
);
$aviso_conta = isset($_GET["conta"]) && isset($avisos_conta[$_GET["conta"]]) ? $avisos_conta[$_GET["conta"]] : null;
// Depois de mexer nos dados ou na foto, a página já abre na aba "Meus dados"
$aba_inicial = ($aviso_conta || isset($_GET["foto"]) || isset($_GET["erro"])) ? "dados" : "doacoes";

// Números do resumo
$t = $hist["totais"];
$entregues = 0;
foreach ($hist["doacoes"] as $d) {
    if ($d["situacao"] === "Entregue a uma instituição") $entregues++;
}

// Cor da etiqueta de cada situação
function classe_situacao($s) {
    $c = array("Aguardando entrega no local" => "sit-espera", "Recebida pela ONG" => "sit-recebida",
               "Entregue a uma instituição" => "sit-entregue", "Pago" => "sit-recebida", "Simulado" => "sit-recebida",
               "Confirmado" => "sit-recebida", "Aguardando" => "sit-espera");
    return isset($c[$s]) ? $c[$s] : "sit-neutra";
}

$primeiro_nome = strtok($u["nome"], " ");
site_inicio("Minha conta", "minha_conta.php");
?>
<section class="conta-topo">
    <a class="conta-foto" href="#dados" data-ir-aba="dados" title="Trocar a foto">
        <img src="<?= h(foto_url($u["id"], $u["foto"])) ?>" alt="Sua foto de perfil">
        <span>Trocar</span>
    </a>
    <div class="conta-quem">
        <h1>Olá, <?= h($primeiro_nome) ?>!</h1>
        <p><?= h($u["email"]) ?> · Conta criada em <?= h($u["desde"]) ?></p>
    </div>
    <a class="botao" href="doacao.html">Fazer uma nova doação</a>
</section>

<nav class="abas-conta" role="tablist" aria-label="Minha conta" data-aba-inicial="<?= $aba_inicial ?>">
    <button type="button" role="tab" data-aba="doacoes">Minhas doações</button>
    <button type="button" role="tab" data-aba="dados">Meus dados</button>
</nav>

<!-- ===================== ABA: MINHAS DOAÇÕES ===================== -->
<div class="aba-conta" id="aba-doacoes" role="tabpanel">
    <div class="conta-numeros">
        <div><strong><?= (int) $t["brinquedos"] ?></strong>brinquedos doados</div>
        <div><strong><?= $entregues ?></strong><?= $entregues === 1 ? "doação já entregue" : "doações já entregues" ?> às crianças</div>
        <div><strong><?= dinheiro($t["pago"]) ?></strong>doados em dinheiro</div>
        <div><strong><?= count($hist["mensagens"]) + count($hist["avaliacoes"]) ?></strong>mensagens e avaliações</div>
    </div>

    <?php if (!$hist["doacoes"] && !$hist["pagamentos"]): ?>
        <div class="conta-vazio">
            <h2>Você ainda não fez nenhuma doação</h2>
            <p>Quando você doar com a conta aberta, tudo aparece aqui, inclusive quando o seu brinquedo chegar às crianças.</p>
            <a class="botao" href="doacao_brinquedo.php">Doar um brinquedo</a>
            <a class="botao contorno" href="pagamento.html">Doar em dinheiro</a>
        </div>
    <?php endif; ?>

    <?php if ($hist["doacoes"]): ?>
        <h2>Brinquedos</h2>
        <ul class="conta-lista">
            <?php foreach ($hist["doacoes"] as $d): ?>
                <li>
                    <div class="item-principal">
                        <strong><?= (int) $d["quantidade"] ?> × <?= h($d["brinquedo"]) ?></strong>
                        <span>Entrega em <?= h($d["dia"]) ?> · <?= h($d["ponto"]) ?></span>
                        <?php if ($d["observacoes"]): ?><small><?= h($d["observacoes"]) ?></small><?php endif; ?>
                    </div>
                    <span class="situacao <?= classe_situacao($d["situacao"]) ?>"><?= h($d["situacao"]) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if ($hist["pagamentos"]): ?>
        <h2>Dinheiro</h2>
        <ul class="conta-lista">
            <?php foreach ($hist["pagamentos"] as $p): ?>
                <li>
                    <div class="item-principal">
                        <strong><?= h($p["valor"]) ?></strong>
                        <span>Pedido <?= h($p["pedido"]) ?> · <?= h($p["metodo"]) ?> · <?= h($p["quando"]) ?></span>
                    </div>
                    <div class="item-lado">
                        <span class="situacao <?= classe_situacao($p["status"]) ?>"><?= h($p["status"]) ?></span>
                        <?php if ($p["codigo"] && $p["status"] === "Pago"): ?>
                            <a href="pagamento_recibo.php?c=<?= h($p["codigo"]) ?>">Ver recibo</a>
                        <?php elseif ($p["codigo"] && $p["status"] === "Aguardando"): ?>
                            <a href="pagamento_pedido.php?c=<?= h($p["codigo"]) ?>">Continuar pagamento</a>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if ($hist["mensagens"] || $hist["avaliacoes"]): ?>
        <h2>Mensagens e avaliações</h2>
        <ul class="conta-lista">
            <?php foreach ($hist["avaliacoes"] as $a): ?>
                <li>
                    <div class="item-principal">
                        <strong>Avaliação<?php if ($a["nota"] !== "—"): $n = (int) preg_replace('/\D/', "", $a["nota"]); ?><span class="estrelas-mini" aria-label="<?= $n ?> de 5 estrelas"><?= str_repeat("★", $n) . str_repeat("☆", 5 - $n) ?></span><?php endif; ?></strong>
                        <?php if ($a["comentario"]): ?><span><?= h($a["comentario"]) ?></span><?php endif; ?>
                    </div>
                    <small class="item-data"><?= h($a["quando"]) ?></small>
                </li>
            <?php endforeach; ?>
            <?php foreach ($hist["mensagens"] as $m): ?>
                <li>
                    <div class="item-principal">
                        <strong>Mensagem</strong>
                        <span><?= h($m["mensagem"]) ?></span>
                    </div>
                    <small class="item-data"><?= h($m["quando"]) ?></small>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <p class="dica">Aqui aparece só o que você fez com a conta aberta (logado).</p>
</div>

<!-- ===================== ABA: MEUS DADOS ===================== -->
<div class="aba-conta" id="aba-dados" role="tabpanel">
    <span id="dados"></span>
    <?php if ($aviso_conta): ?><p class="aviso <?= $aviso_conta[0] ? "ok" : "erro" ?>"><?= h($aviso_conta[1]) ?></p><?php endif; ?>
    <div class="conta-dados">
        <form id="foto" class="cartao-dados" action="foto_salvar.php" method="post">
            <h2>Foto de perfil</h2>
            <div class="escolha-foto" data-escolha-foto>
                <div class="foto-linha">
                    <img class="foto-previa" src="<?= h(foto_url($u["id"], $u["foto"])) ?>" alt="Prévia da foto de perfil">
                    <div>
                        <label class="botao botao-arquivo">Escolher da galeria
                            <input type="file" accept="image/*" hidden>
                        </label>
                        <p class="dica">Ou escolha uma destas:</p>
                    </div>
                </div>
                <div class="avatares">
                    <button type="button" data-avatar="nenhuma" title="Sem foto"><img src="imagens/avatares/padrao.svg" alt="Sem foto"></button>
                    <button type="button" data-avatar="avatar1" title="Sol"><img src="imagens/avatares/avatar1.svg?v=2" alt="Sol"></button>
                    <button type="button" data-avatar="avatar2" title="Estrela"><img src="imagens/avatares/avatar2.svg?v=2" alt="Estrela"></button>
                    <button type="button" data-avatar="avatar3" title="Coração"><img src="imagens/avatares/avatar3.svg?v=2" alt="Coração"></button>
                    <button type="button" data-avatar="avatar4" title="Folha"><img src="imagens/avatares/avatar4.svg?v=2" alt="Folha"></button>
                    <button type="button" data-avatar="avatar5" title="Lua"><img src="imagens/avatares/avatar5.svg?v=2" alt="Lua"></button>
                    <button type="button" data-avatar="avatar6" title="Sorriso"><img src="imagens/avatares/avatar6.svg?v=2" alt="Sorriso"></button>
                </div>
                <input type="hidden" name="foto_padrao" value="">
                <input type="hidden" name="foto_upload" value="">
            </div>
            <button type="submit">Salvar foto</button>
            <p id="aviso" class="aviso"></p>
        </form>

        <div class="conta-coluna">
            <form class="cartao-dados" action="conta_salvar.php" method="post">
                <input type="hidden" name="acao" value="nome">
                <input type="hidden" name="chave" value="<?= h(chave_conta()) ?>">
                <h2>Nome</h2>
                <label for="nomeConta">Como você quer ser chamado</label>
                <input id="nomeConta" type="text" name="nome" maxlength="100" value="<?= h($u["nome"]) ?>" required>
                <p class="dica">O e-mail (<?= h($u["email"]) ?>) não pode ser trocado aqui. Se precisar, fale com a gente pela página <a href="contato.html">Contato</a>.</p>
                <button type="submit">Salvar nome</button>
            </form>

            <form class="cartao-dados" action="conta_salvar.php" method="post">
                <input type="hidden" name="acao" value="senha">
                <input type="hidden" name="chave" value="<?= h(chave_conta()) ?>">
                <h2>Senha</h2>
                <label for="senhaAtual">Senha atual</label>
                <input id="senhaAtual" type="password" name="atual" autocomplete="current-password" required>
                <div class="senha-dupla">
                    <div>
                        <label for="senhaNova">Senha nova</label>
                        <input id="senhaNova" type="password" name="senha" minlength="6" autocomplete="new-password" required>
                    </div>
                    <div>
                        <label for="senhaConfirmar">Repita a senha nova</label>
                        <input id="senhaConfirmar" type="password" name="confirmar" minlength="6" autocomplete="new-password" required>
                    </div>
                </div>
                <button type="submit">Trocar senha</button>
            </form>
        </div>
    </div>
    <p class="dica">Quer apagar a sua conta? Veja como na <a href="privacidade.html">Política de Privacidade</a>.</p>
</div>
<?php
site_fim();
