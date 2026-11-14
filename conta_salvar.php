<?php
// "Minha conta": troca o nome ou a senha de quem está logado.
// Para trocar a senha é preciso digitar a senha atual.
require "funcoes.php";
iniciar_sessao();

$u = exigir_login();
if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST["chave"]) || $_POST["chave"] !== chave_conta()) {
    header("Location: minha_conta.php");
    exit;
}

function voltar($codigo) {
    header("Location: minha_conta.php?conta=" . $codigo . "#dados");
    exit;
}

$acao = isset($_POST["acao"]) ? $_POST["acao"] : "";

if ($acao === "nome") {
    $nome = isset($_POST["nome"]) ? trim($_POST["nome"]) : "";
    if ($nome === "" || mb_strlen($nome, "UTF-8") > 100) voltar("nome_vazio");
    $pdo->prepare("UPDATE usuarios SET nome = ? WHERE id = ?")->execute(array($nome, $u["id"]));
    $_SESSION["usuario_nome"] = $nome;
    voltar("nome_ok");
}

if ($acao === "senha") {
    $atual = isset($_POST["atual"]) ? $_POST["atual"] : "";
    $senha = isset($_POST["senha"]) ? $_POST["senha"] : "";
    $confirmar = isset($_POST["confirmar"]) ? $_POST["confirmar"] : "";
    $st = $pdo->prepare("SELECT senha_hash FROM usuarios WHERE id = ?");
    $st->execute(array($u["id"]));
    if (!password_verify($atual, $st->fetchColumn())) voltar("senha_atual");
    if (strlen($senha) < 6) voltar("senha_curta");
    if ($senha !== $confirmar) voltar("senhas_diferentes");
    $pdo->prepare("UPDATE usuarios SET senha_hash = ? WHERE id = ?")->execute(array(password_hash($senha, PASSWORD_DEFAULT), $u["id"]));
    voltar("senha_ok");
}

header("Location: minha_conta.php");
exit;
