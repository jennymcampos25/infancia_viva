<?php
// Página "Minha conta": o doador vê tudo o que fez no site enquanto estava logado.
session_start();
header("Content-Type: text/html; charset=UTF-8");
header("Cache-Control: no-store");
require "funcoes.php";

$u = exigir_login();
$hist = buscar_historico((int) $u["id"]);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Minha conta - Infância Viva</title>
    <link rel="stylesheet" href="estilo.css?v=2">
    <link rel="icon" href="favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap">
</head>
<body>
<header class="topo">
    <a class="logo" href="index.html"><span class="logo-icone">♥</span> Infância Viva</a>
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
<main class="largo">
    <h1>Olá, <?= h($u["nome"]) ?>!</h1>
    <p>E-mail: <?= h($u["email"]) ?> · Conta criada em <?= h($u["desde"]) ?></p>

    <h2 id="foto">Foto de perfil</h2>
    <form class="formulario-estreito a-esquerda" action="foto_salvar.php" method="post">
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
                <button type="button" data-avatar="avatar1" title="Sol"><img src="imagens/avatares/avatar1.svg" alt="Sol"></button>
                <button type="button" data-avatar="avatar2" title="Estrela"><img src="imagens/avatares/avatar2.svg" alt="Estrela"></button>
                <button type="button" data-avatar="avatar3" title="Coração"><img src="imagens/avatares/avatar3.svg" alt="Coração"></button>
                <button type="button" data-avatar="avatar4" title="Folha"><img src="imagens/avatares/avatar4.svg" alt="Folha"></button>
                <button type="button" data-avatar="avatar5" title="Lua"><img src="imagens/avatares/avatar5.svg" alt="Lua"></button>
                <button type="button" data-avatar="avatar6" title="Sorriso"><img src="imagens/avatares/avatar6.svg" alt="Sorriso"></button>
            </div>
            <input type="hidden" name="foto_padrao" value="">
            <input type="hidden" name="foto_upload" value="">
        </div>
        <button type="submit">Salvar foto</button>
        <p id="aviso" class="aviso"></p>
    </form>

    <?php mostrar_historico($hist, false); ?>

    <p><small>Aqui aparece só o que você fez com a conta aberta (logado).</small></p>
    <a class="botao" href="doacao.html">Fazer uma nova doação</a>
</main>
<footer class="rodape">
    <div class="rodape-conteudo">
        <div>
            <strong>♥ Infância Viva</strong>
            <p>Pequenos gestos podem transformar grandes histórias.</p>
        </div>
        <div>
            <strong>Contato</strong>
            <p>E-mail: *<br>Telefone: *<br>Instagram: *</p>
        </div>
        <div>
            <strong>Como ajudar</strong>
            <p><a href="doacao.html">Doar brinquedos</a><br><a href="pagamento.html">Contribuir online</a><br><a href="faq.html">Perguntas frequentes</a></p>
        </div>
    </div>
    <p class="rodape-final">© 2026 Infância Viva · Projeto de TCC</p>
</footer>
<script src="script.js?v=4"></script>
</body>
</html>
