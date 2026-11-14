<?php
// Abre pelo link do e-mail de "Esqueci minha senha" (redefinir_senha.php?codigo=...).
// O banco guarda só o hash do código, e o link vale por 1 hora e só uma vez.
require "funcoes.php";
iniciar_sessao();
garantir_colunas_senha();

$codigo = isset($_REQUEST["codigo"]) ? $_REQUEST["codigo"] : "";
$u = null;
if (preg_match('/^[a-f0-9]{64}$/', $codigo)) {
    $st = $pdo->prepare("SELECT id, nome FROM usuarios WHERE token_senha = ? AND token_senha_expira >= NOW()");
    $st->execute(array(hash("sha256", $codigo)));
    $u = $st->fetch();
}

$erro = "";
$feito = false;
if ($u && $_SERVER["REQUEST_METHOD"] === "POST") {
    $senha = isset($_POST["senha"]) ? $_POST["senha"] : "";
    $confirmar = isset($_POST["confirmar"]) ? $_POST["confirmar"] : "";
    if (strlen($senha) < 6) {
        $erro = "A senha precisa ter pelo menos 6 caracteres.";
    } elseif ($senha !== $confirmar) {
        $erro = "As senhas não conferem.";
    } else {
        // Troca a senha e invalida o link. Abrir o link do e-mail também prova que o e-mail é da pessoa.
        $st = $pdo->prepare("UPDATE usuarios SET senha_hash = ?, token_senha = NULL, token_senha_expira = NULL,
                                                 email_confirmado = 1, token_confirmacao = NULL, token_expira = NULL
                             WHERE id = ?");
        $st->execute(array(password_hash($senha, PASSWORD_DEFAULT), $u["id"]));
        $feito = true;
    }
}

site_inicio("Criar uma senha nova", "login.html");
?>
<h1 class="centro">Criar uma senha nova</h1>
<?php if ($feito): ?>
    <div class="formulario-estreito caixa-mensagem">
        <p class="aviso ok">Senha alterada! Agora é só entrar com a senha nova.</p>
        <a class="botao" href="login.html">Entrar</a>
    </div>
<?php elseif (!$u): ?>
    <div class="formulario-estreito caixa-mensagem">
        <p class="aviso erro">Esse link não vale mais. Ele dura 1 hora e só pode ser usado uma vez.</p>
        <a class="botao" href="esqueci_senha.php">Pedir um link novo</a>
    </div>
<?php else: ?>
    <form class="formulario-estreito" action="redefinir_senha.php" method="post">
        <input type="hidden" name="codigo" value="<?= h($codigo) ?>">
        <p>Olá, <?= h(strtok($u["nome"], " ")) ?>! Escolha a sua senha nova.</p>
        <?php if ($erro): ?><p class="aviso erro"><?= h($erro) ?></p><?php endif; ?>
        <label for="senha">Senha nova</label>
        <input id="senha" type="password" name="senha" minlength="6" autocomplete="new-password" required>
        <label for="confirmar">Repita a senha nova</label>
        <input id="confirmar" type="password" name="confirmar" minlength="6" autocomplete="new-password" required>
        <button type="submit">Salvar senha nova</button>
    </form>
<?php endif; ?>
<?php
site_fim();
