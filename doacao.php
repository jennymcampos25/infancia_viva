<?php
header("Content-Type: text/html; charset=UTF-8");
require "funcoes.php";
iniciar_sessao();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: doacao.html");
    exit;
}


// Se a pessoa estiver logada, o registro fica ligado à conta dela (aparece em "Minha conta")
$usuario_id = usuario_logado_id();

function campo($k) { return isset($_POST[$k]) ? trim($_POST[$k]) : ""; }
// h() (em funcoes.php) protege a tela contra HTML/JS digitado no formulário. Só é usado ao MOSTRAR; no banco vai o texto puro.

$pontos = array(
    "sede"   => "Sede da Infância Viva",
    "escola" => "Escola parceira",
    "centro" => "Centro comunitário"
);

$nome        = campo("nome");
$email       = campo("email");
$telefone    = campo("telefone");
$brinquedo   = campo("brinquedo");
$quantidade  = (int) campo("quantidade");
$data        = campo("data");
$endereco    = campo("endereco");
$observacoes = campo("observacoes");
$ponto       = campo("ponto");
$valor       = campo("valor");
if ($valor === "outro") {
    $valor = campo("outroValor");
}
$valor = is_numeric($valor) ? (float) $valor : 0;

$erro = "";
if ($nome == "" || $email == "" || $telefone == "" || $brinquedo == "" || $endereco == "") {
    $erro = "Por favor, preencha todos os campos obrigatórios.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $erro = "E-mail inválido.";
} elseif (!email_real($email)) {
    $erro = "Esse e-mail não parece existir. Confira se digitou certo.";
} elseif ($quantidade <= 0) {
    $erro = "A quantidade de brinquedos deve ser maior que zero.";
} elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
    $erro = "Data inválida.";
} elseif (!isset($pontos[$ponto])) {
    $erro = "Escolha um ponto de arrecadação.";
}

if ($erro == "") {
    try {
        $st = $pdo->prepare(
            "INSERT INTO doacoes (usuario_id, nome, email, telefone, brinquedo, quantidade, data_doacao,
                                  endereco, observacoes, ponto, valor)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $st->execute(array(
            $usuario_id, $nome, $email, $telefone, $brinquedo, $quantidade, $data,
            $endereco, $observacoes, $pontos[$ponto], ($valor > 0 ? $valor : null)
        ));
    } catch (PDOException $e) {
        $erro = "Não foi possível registrar a doação agora. Tente novamente.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Doação - Infância Viva</title>
    <link rel="stylesheet" href="estilo.css?v=5">
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
<main>
<?php if ($erro): ?>
    <h1>Ops!</h1>
    <p class="aviso erro"><?= h($erro) ?></p>
    <a class="botao" href="doacao.html">Voltar</a>
<?php else: ?>
    <h1>Doação recebida!</h1>
    <p>Obrigado, <?= h($nome) ?>!</p>
    <p>Sua doação de <?= $quantidade ?> brinquedo(s) foi registrada.</p>
    <p>Brinquedo: <?= h($brinquedo) ?></p>
    <p>Ponto de arrecadação: <?= h($pontos[$ponto]) ?></p>
    <?php if ($valor > 0): ?>
        <p>Você também escolheu contribuir com R$ <?= number_format($valor, 2, ",", ".") ?>.</p>
        <a class="botao" href="pagamento.html?valor=<?= urlencode($valor) ?>">Ir para o pagamento</a>
    <?php endif; ?>
    <?php if ($usuario_id): ?>
        <p><a href="minha_conta.php">Ver minhas doações</a></p>
    <?php else: ?>
        <p><small>Dica: <a href="cadastro.html">crie uma conta</a> e entre nela antes de doar para acompanhar suas doações.</small></p>
    <?php endif; ?>
<?php endif; ?>
</main>
<footer class="rodape">
    <div class="rodape-conteudo">
        <div>
            <strong><svg viewBox="0 0 64 64" aria-hidden="true"><path d="M32 4 52 21H12z" fill="#2f7a80"/><rect x="21" y="19" width="22" height="7" fill="#2f7a80"/><path fill="#f5a524" fill-rule="evenodd" d="M13 26h38a4 4 0 0 1 4 4v26a4 4 0 0 1-4 4H13a4 4 0 0 1-4-4V30a4 4 0 0 1 4-4zM25 60V46a7 7 0 0 1 14 0v14z"/></svg> Infância Viva</strong>
            <p>Doar brinquedos. Transformar infâncias.</p>
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
<script src="script.js?v=7"></script>
</body>
</html>
