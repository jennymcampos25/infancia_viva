<?php
// Página "Doe um brinquedo": lista os locais de coleta (cadastrados pelo admin) e o formulário.
require "funcoes.php";
header("Content-Type: text/html; charset=UTF-8");
$locais = locais_ativos();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Doe um brinquedo - Infância Viva</title>
    <link rel="stylesheet" href="estilo.css?v=11">
    <link rel="icon" href="favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Quicksand:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap">
</head>
<body data-menu="doacao.html">
<header class="topo">
    <a class="logo" href="index.html" aria-label="Infância Viva - página inicial"><svg viewBox="0 0 64 64" aria-hidden="true"><path d="M32 4 52 21H12z" fill="#2f7a80"/><rect x="21" y="19" width="22" height="7" fill="#2f7a80"/><path fill="#f5a524" fill-rule="evenodd" d="M13 26h38a4 4 0 0 1 4 4v26a4 4 0 0 1-4 4H13a4 4 0 0 1-4-4V30a4 4 0 0 1 4-4zM25 60V46a7 7 0 0 1 14 0v14z"/></svg><span>Infância <span class="l1">V</span><span class="l2">i</span><span class="l3">v</span><span class="l4">a</span></span></a>
    <button type="button" class="menu-botao" id="menuBotao" aria-label="Abrir menu" aria-expanded="false">☰</button>
    <nav id="menu">
        <a href="index.html">Início</a>
        <a href="doacao.html">Doação</a>
        <a href="sobre.html">Sobre nós</a>
        <a href="faq.html">Perguntas frequentes</a>
        <a href="contato.html">Contato</a>
        <a href="login.html" id="linkLogin">Login</a>
    </nav>
</header>
<main>
<nav class="abas-doacao" aria-label="Tipo de doação">
    <a href="doacao_brinquedo.php" class="ativa"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13"/><path d="M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7"/><path d="M7.5 8a2.5 2.5 0 0 1 0-5C10 3 12 8 12 8s2-5 4.5-5a2.5 2.5 0 0 1 0 5"/></svg><span>Doe um brinquedo</span></a>
    <a href="pagamento.html"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M15 9.5c-.5-1-1.6-1.5-3-1.5-1.7 0-3 .8-3 2s1.3 1.7 3 2 3 .8 3 2-1.3 2-3 2c-1.4 0-2.5-.5-3-1.5M12 6.5v1.5M12 16v1.5"/></svg><span>Doe em dinheiro</span></a>
</nav>

<h1>Doe um brinquedo</h1>
<p>
    Brinquedos limpos, em bom estado e sem peças faltando. Você registra a doação aqui
    e leva o brinquedo a um dos locais abaixo, nos dias de coleta.
</p>

<h2>Como funciona</h2>
<div class="passos passos-pequenos">
    <div class="passo" style="--cor:var(--vermelho)"><div class="num">1</div><h3>Escolha um local</h3><p>Veja os locais de coleta e os dias em que cada um recebe brinquedos.</p></div>
    <div class="passo" style="--cor:var(--amarelo)"><div class="num">2</div><h3>Registre aqui</h3><p>Preencha o formulário com o brinquedo e o local de entrega.</p></div>
    <div class="passo" style="--cor:var(--azul)"><div class="num">3</div><h3>Leve o brinquedo</h3><p>Entregue no local, na data marcada. Nós levamos até as crianças.</p></div>
</div>

<h2 id="locais">Locais de coleta</h2>
<?php if (!$locais): ?>
    <p class="vazio">Ainda não há locais de coleta cadastrados. Volte em breve!</p>
<?php else: ?>
<div class="locais">
    <?php foreach ($locais as $l): ?>
        <article class="local">
            <span class="etiqueta"><?= h($l["tipo"]) ?></span>
            <h3><?= h($l["nome"]) ?></h3>
            <p><strong>Onde:</strong> <?= h($l["endereco"]) ?></p>
            <p><strong>Quando:</strong> <?= h($l["coleta"]) ?></p>
        </article>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<h2>Registre sua doação</h2>
<p>
    Tem conta? <a href="login.html">Entre</a> antes de doar para acompanhar suas doações
    em "Minha conta". Não tem? Você pode doar mesmo assim.
</p>

<form action="doacao.php" method="post">
    <label for="nome">Nome completo</label>
    <input id="nome" type="text" name="nome" required>

    <label for="email">E-mail</label>
    <input id="email" type="email" name="email" required>

    <label for="telefone">Telefone</label>
    <input id="telefone" type="text" name="telefone" required>

    <label for="brinquedo">Brinquedo</label>
    <input id="brinquedo" type="text" name="brinquedo" placeholder="Ex.: carrinho de madeira, boneca, quebra-cabeça" required>

    <label for="quantidade">Quantidade</label>
    <input id="quantidade" type="number" name="quantidade" min="1" value="1" required>

    <label for="local">Onde você vai entregar?</label>
    <select id="local" name="local" required>
        <option value="">Escolha um local de coleta</option>
        <?php foreach ($locais as $l): ?>
            <option value="<?= (int) $l["id"] ?>"><?= h($l["nome"]) ?> (<?= h($l["coleta"]) ?>)</option>
        <?php endforeach; ?>
    </select>

    <label for="data">Quando você vai entregar?</label>
    <input id="data" type="date" name="data" required>
    <p class="dica">Escolha um dia em que o local recebe brinquedos.</p>

    <label for="endereco">Seu endereço</label>
    <input id="endereco" type="text" name="endereco" required>

    <label for="observacoes">Observações <small>(opcional)</small></label>
    <input id="observacoes" type="text" name="observacoes">

    <button type="submit">Registrar doação</button>
</form>

<p>Prefere ajudar com dinheiro? <a href="pagamento.html">Doe em dinheiro →</a></p>

</main>
<footer class="rodape">
    <div class="rodape-conteudo">
        <div>
            <strong><svg viewBox="0 0 64 64" aria-hidden="true"><path d="M32 4 52 21H12z" fill="#2f7a80"/><rect x="21" y="19" width="22" height="7" fill="#2f7a80"/><path fill="#f5a524" fill-rule="evenodd" d="M13 26h38a4 4 0 0 1 4 4v26a4 4 0 0 1-4 4H13a4 4 0 0 1-4-4V30a4 4 0 0 1 4-4zM25 60V46a7 7 0 0 1 14 0v14z"/></svg> Infância Viva</strong>
            <p>Doar brinquedos. Transformar infâncias.</p>
        </div>
        <div>
            <strong>Contato</strong>
            <p>E-mail: <a href="mailto:infanciavivaong8@gmail.com">infanciavivaong8@gmail.com</a><br>Telefone: <a href="tel:+5521955553333">(21) 95555-3333</a><br>Instagram: <a href="https://www.instagram.com/onginfanciaviva_/" target="_blank" rel="noopener">@onginfanciaviva_</a><br>Campo Grande, Rio de Janeiro – RJ</p>
        </div>
        <div>
            <strong>Como ajudar</strong>
            <p><a href="doacao_brinquedo.php">Doar brinquedos</a><br><a href="pagamento.html">Contribuir online</a><br><a href="faq.html">Perguntas frequentes</a></p>
        </div>
    </div>
    <p class="rodape-final">© 2026 Infância Viva · Projeto de TCC</p>
</footer>
<script src="script.js?v=10"></script>
</body>
</html>
