<?php
session_start();
header("Content-Type: text/html; charset=UTF-8");
header("Cache-Control: no-store");
require "funcoes.php";

// ---- PROTEÇÃO: só entra quem está logado E é administrador no banco ----
exigir_admin();

// Banco ainda no formato antigo (sem usuario_id ou sem foto)? Manda para a atualização.
if (!$pdo->query("SHOW COLUMNS FROM doacoes LIKE 'usuario_id'")->fetch()
    || !$pdo->query("SHOW COLUMNS FROM usuarios LIKE 'foto'")->fetch()) {
    header("Location: atualizar_banco.php");
    exit;
}

function contar($tabela) {
    global $pdo;
    $permitidas = array("pagamentos", "doacoes", "usuarios", "mensagens", "avaliacoes");
    if (!in_array($tabela, $permitidas)) return 0;
    return (int) $pdo->query("SELECT COUNT(*) FROM $tabela")->fetchColumn();
}

// Chave secreta da sessão: os botões de apagar só funcionam vindos desta página
if (empty($_SESSION["chave_admin"])) {
    $_SESSION["chave_admin"] = sha1(uniqid(mt_rand(), true));
}

// Resultado da última limpeza (vem do admin_limpar.php)
$aviso_limpeza = isset($_SESSION["aviso_admin"]) ? $_SESSION["aviso_admin"] : "";
unset($_SESSION["aviso_admin"]);

// ---- Totais gerais ----
$total = (float) $pdo->query("SELECT COALESCE(SUM(valor), 0) FROM pagamentos")->fetchColumn();
$media = $pdo->query("SELECT AVG(nota) FROM avaliacoes")->fetchColumn();

// ---- Usuários, com quantas coisas cada um fez ----
$usuarios = $pdo->query(
    "SELECT u.id, u.nome, u.email, u.tipo, DATE_FORMAT(u.criado_em, '%d/%m/%Y') AS desde,
            (SELECT COUNT(*) FROM doacoes    WHERE usuario_id = u.id) AS n_doacoes,
            (SELECT COUNT(*) FROM pagamentos WHERE usuario_id = u.id) AS n_pagamentos,
            (SELECT COUNT(*) FROM mensagens  WHERE usuario_id = u.id) AS n_mensagens,
            (SELECT COUNT(*) FROM avaliacoes WHERE usuario_id = u.id) AS n_avaliacoes
     FROM usuarios u ORDER BY u.id DESC"
)->fetchAll();

// O que foi feito sem login (não pertence a nenhum usuário)
$visitantes = $pdo->query(
    "SELECT (SELECT COUNT(*) FROM doacoes    WHERE usuario_id IS NULL) AS n_doacoes,
            (SELECT COUNT(*) FROM pagamentos WHERE usuario_id IS NULL) AS n_pagamentos,
            (SELECT COUNT(*) FROM mensagens  WHERE usuario_id IS NULL) AS n_mensagens,
            (SELECT COUNT(*) FROM avaliacoes WHERE usuario_id IS NULL) AS n_avaliacoes"
)->fetch();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administrador - Infância Viva</title>
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
    <h1>Painel do administrador</h1>
    <?php if ($aviso_limpeza): ?>
        <p class="aviso ok"><?= h($aviso_limpeza) ?></p>
    <?php endif; ?>

    <div class="stats">
        <div class="stat"><strong><?= dinheiro($total) ?></strong>arrecadado</div>
        <div class="stat"><strong><?= contar("pagamentos") ?></strong>pagamentos</div>
        <div class="stat"><strong><?= contar("doacoes") ?></strong>doações de brinquedos</div>
        <div class="stat"><strong><?= contar("usuarios") ?></strong>usuários</div>
        <div class="stat"><strong><?= contar("mensagens") ?></strong>mensagens</div>
        <div class="stat"><strong><?= $media !== null && $media !== false ? number_format($media, 1, ",", ".") : "—" ?></strong>nota média (<?= contar("avaliacoes") ?>)</div>
    </div>

    <h2>Usuários</h2>
    <p>Clique no nome para ver tudo o que a pessoa fez.</p>
    <div class="tabela"><table>
        <tr>
            <th>Nome</th><th>E-mail</th><th>Tipo</th><th>Cadastro</th>
            <th>Doações</th><th>Pagamentos</th><th>Mensagens</th><th>Avaliações</th>
        </tr>
        <?php foreach ($usuarios as $u): ?>
        <tr>
            <td><a href="admin_usuario.php?id=<?= (int) $u["id"] ?>"><?= h($u["nome"]) ?></a></td>
            <td><?= h($u["email"]) ?></td>
            <td><?= h($u["tipo"]) ?></td>
            <td><?= h($u["desde"]) ?></td>
            <td><?= (int) $u["n_doacoes"] ?></td>
            <td><?= (int) $u["n_pagamentos"] ?></td>
            <td><?= (int) $u["n_mensagens"] ?></td>
            <td><?= (int) $u["n_avaliacoes"] ?></td>
        </tr>
        <?php endforeach; ?>
        <tr>
            <td><a href="admin_usuario.php?id=0"><em>Visitantes sem conta</em></a></td>
            <td>—</td><td>—</td><td>—</td>
            <td><?= (int) $visitantes["n_doacoes"] ?></td>
            <td><?= (int) $visitantes["n_pagamentos"] ?></td>
            <td><?= (int) $visitantes["n_mensagens"] ?></td>
            <td><?= (int) $visitantes["n_avaliacoes"] ?></td>
        </tr>
    </table></div>

    <!-- Ferramentas de teste: ficam fechadas; clique em "Ferramentas de teste" para abrir -->
    <details class="ferramentas">
        <summary>Ferramentas de teste</summary>
        <p>Nenhuma opção apaga a conta do administrador. Não dá para desfazer.</p>
        <?php
        $botoes = array(
            "ultimo"     => array("Apagar o último registro", "Apagar o registro mais recente do site?"),
            "visitantes" => array("Apagar registros de visitantes", "Apagar tudo o que foi feito sem login?"),
            "contas"     => array("Apagar contas de doadores", "Apagar todas as contas de doadores e tudo o que elas fizeram?"),
            "tudo"       => array("Apagar tudo", "Apagar TODOS os registros e contas de doadores?")
        );
        foreach ($botoes as $acao => $b): ?>
            <form action="admin_limpar.php" method="post" onsubmit="return confirm('<?= h($b[1]) ?>')">
                <input type="hidden" name="acao" value="<?= $acao ?>">
                <input type="hidden" name="chave" value="<?= h($_SESSION["chave_admin"]) ?>">
                <button type="submit" class="perigo"><?= h($b[0]) ?></button>
            </form>
        <?php endforeach; ?>
    </details>
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
<script src="script.js?v=3"></script>
</body>
</html>
