<?php
// Recebe a troca de foto feita na página "Minha conta".
require "funcoes.php";
iniciar_sessao();

$u = exigir_login();
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: minha_conta.php");
    exit;
}

$escolha = ler_foto_do_formulario();
if ($escolha === false) {
    header("Location: minha_conta.php?erro=foto#foto");
    exit;
}
if ($escolha !== null) {
    salvar_foto($u["id"], $escolha);
}
header("Location: minha_conta.php?foto=ok#foto");
exit;
