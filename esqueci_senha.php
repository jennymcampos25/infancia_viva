<?php
// "Esqueci minha senha": a pessoa digita o e-mail e recebe um link para criar uma senha nova.
// A resposta é sempre a mesma, exista a conta ou não (assim ninguém usa a página para descobrir
// quais e-mails têm conta no site).
require "funcoes.php";
iniciar_sessao();
garantir_colunas_senha();

$enviado = false;
$falha = false;
$email = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = isset($_POST["email"]) ? strtolower(trim($_POST["email"])) : "";
    $st = $pdo->prepare("SELECT id, nome, email, token_senha_expira FROM usuarios WHERE email = ?");
    $st->execute(array($email));
    $u = $st->fetch();
    if ($u) {
        // Evita mandar vários e-mails seguidos: só gera link novo se o último tem mais de 1 minuto
        $criado = $u["token_senha_expira"] ? strtotime($u["token_senha_expira"]) - 3600 : 0;
        if (time() - $criado > 60 && !enviar_redefinicao($u["id"], $u["nome"], $u["email"])) {
            $falha = true;
        }
    }
    $enviado = !$falha;
}

site_inicio("Esqueci minha senha", "login.html");
?>
<h1 class="centro">Esqueci minha senha</h1>
<?php if ($enviado): ?>
    <div class="formulario-estreito caixa-mensagem">
        <p class="aviso ok">Pronto! Se existe uma conta com o e-mail <strong><?= h($email) ?></strong>, mandamos um link
           para criar uma senha nova.</p>
        <p>O link vale por <strong>1 hora</strong>. Confira também a caixa de spam.</p>
        <p><a href="login.html">Voltar para o login</a></p>
    </div>
<?php else: ?>
    <form class="formulario-estreito" action="esqueci_senha.php" method="post">
        <p>Digite o e-mail da sua conta. Vamos mandar um link para você criar uma senha nova.</p>
        <?php if ($falha): ?>
            <p class="aviso erro">Não conseguimos enviar o e-mail agora. Tente de novo em alguns minutos.</p>
        <?php endif; ?>
        <label for="email">E-mail</label>
        <input id="email" type="email" name="email" value="<?= h($email) ?>" required>
        <button type="submit">Enviar link</button>
        <p><a href="login.html">Voltar para o login</a></p>
    </form>
<?php endif; ?>
<?php
site_fim();
