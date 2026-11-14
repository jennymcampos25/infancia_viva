<?php
// Página "Minha conta": o doador vê tudo o que fez no site enquanto estava logado.
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
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Minha conta - Infância Viva</title>
    <link rel="stylesheet" href="estilo.css?v=14">
    <link rel="icon" href="favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Quicksand:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap">
</head>
<body>
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
<main class="largo">
    <h1>Olá, <?= h($u["nome"]) ?>!</h1>
    <p>E-mail: <?= h($u["email"]) ?> · Conta criada em <?= h($u["desde"]) ?></p>

    <h2 id="dados">Meus dados</h2>
    <?php if ($aviso_conta): ?><p class="aviso <?= $aviso_conta[0] ? "ok" : "erro" ?>"><?= h($aviso_conta[1]) ?></p><?php endif; ?>
    <div class="meus-dados">
        <form action="conta_salvar.php" method="post">
            <input type="hidden" name="acao" value="nome">
            <input type="hidden" name="chave" value="<?= h(chave_conta()) ?>">
            <h3>Trocar o nome</h3>
            <label for="nomeConta">Nome</label>
            <input id="nomeConta" type="text" name="nome" maxlength="100" value="<?= h($u["nome"]) ?>" required>
            <p class="dica">O e-mail (<?= h($u["email"]) ?>) não pode ser trocado aqui. Se precisar, fale com a gente pela página Contato.</p>
            <button type="submit">Salvar nome</button>
        </form>
        <form action="conta_salvar.php" method="post">
            <input type="hidden" name="acao" value="senha">
            <input type="hidden" name="chave" value="<?= h(chave_conta()) ?>">
            <h3>Trocar a senha</h3>
            <label for="senhaAtual">Senha atual</label>
            <input id="senhaAtual" type="password" name="atual" autocomplete="current-password" required>
            <label for="senhaNova">Senha nova</label>
            <input id="senhaNova" type="password" name="senha" minlength="6" autocomplete="new-password" required>
            <label for="senhaConfirmar">Repita a senha nova</label>
            <input id="senhaConfirmar" type="password" name="confirmar" minlength="6" autocomplete="new-password" required>
            <button type="submit">Trocar senha</button>
        </form>
    </div>

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

    <?php mostrar_historico($hist, false); ?>

    <p><small>Aqui aparece só o que você fez com a conta aberta (logado).</small></p>
    <a class="botao" href="doacao.html">Fazer uma nova doação</a>
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
    <p class="rodape-final">© 2026 Infância Viva · Projeto de TCC · <a href="privacidade.html">Política de Privacidade</a></p>
</footer>
<script src="script.js?v=12"></script>
</body>
</html>
