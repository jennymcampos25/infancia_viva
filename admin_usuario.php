<?php
// Painel do administrador: tudo o que UM usuário fez. ?id=0 mostra o que foi feito sem login.
session_start();
header("Content-Type: text/html; charset=UTF-8");
header("Cache-Control: no-store");
require "funcoes.php";

exigir_admin();

$id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

if ($id === 0) {
    $pessoa = null;
    $hist = buscar_historico(null);
} else {
    $st = $pdo->prepare("SELECT id, nome, email, tipo, DATE_FORMAT(criado_em, '%d/%m/%Y') AS desde
                         FROM usuarios WHERE id = ?");
    $st->execute(array($id));
    $pessoa = $st->fetch();
    if (!$pessoa) {
        header("Location: admin.php");
        exit;
    }
    $hist = buscar_historico($id);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Usuário - Administrador - Infância Viva</title>
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
    <p><a href="admin.php">← Voltar para a lista de usuários</a></p>

    <?php if ($pessoa): ?>
        <h1><?= h($pessoa["nome"]) ?></h1>
        <p>E-mail: <?= h($pessoa["email"]) ?> · Tipo: <?= h($pessoa["tipo"]) ?> · Cadastro em <?= h($pessoa["desde"]) ?></p>
    <?php else: ?>
        <h1>Visitantes sem conta</h1>
        <p>Tudo o que foi feito no site sem estar logado.</p>
    <?php endif; ?>

    <?php mostrar_historico($hist, true); ?>
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
<script src="script.js?v=2"></script>
</body>
</html>
