<?php
// Recebe o formulário de cadastro.html e grava o usuário no banco.
require "funcoes.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: cadastro.html");
    exit;
}

function campo($k) {
    return isset($_POST[$k]) ? trim($_POST[$k]) : "";
}
function voltar($erro) {
    header("Location: cadastro.html?erro=" . $erro);
    exit;
}

$nome      = campo("nome");
$email     = strtolower(campo("email"));
$senha     = isset($_POST["senha"]) ? $_POST["senha"] : "";
$confirmar = isset($_POST["confirmar"]) ? $_POST["confirmar"] : "";

if ($nome === "" || $email === "" || $senha === "") voltar("campos");
if (!filter_var($email, FILTER_VALIDATE_EMAIL))     voltar("email_invalido");
if (strlen($senha) < 6)                             voltar("senha_curta");
if ($senha !== $confirmar)                          voltar("senhas_diferentes");

// E-mail já cadastrado?
$st = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
$st->execute(array($email));
if ($st->fetch()) voltar("email_existe");

// Foto de perfil (opcional): confere antes de criar a conta
$foto = ler_foto_do_formulario();
if ($foto === false) voltar("foto");

// A senha NUNCA é guardada pura: vira um hash que não dá para desfazer
$hash = password_hash($senha, PASSWORD_DEFAULT);

try {
    $st = $pdo->prepare("INSERT INTO usuarios (nome, email, senha_hash, tipo) VALUES (?, ?, ?, 'doador')");
    $st->execute(array($nome, $email, $hash));
    if ($foto !== null) {
        salvar_foto($pdo->lastInsertId(), $foto);
    }
} catch (PDOException $e) {
    voltar("erro");
}

header("Location: login.html?cadastro=ok");
exit;
