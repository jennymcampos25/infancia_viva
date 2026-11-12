<?php
// Diz ao JavaScript das páginas HTML se há alguém logado (usado na bolinha de perfil do menu).
// Nome e tipo vêm do BANCO a cada pedido, não do que foi guardado no login: assim, se o admin
// transformar alguém em administrador (ou tirar o acesso), o menu muda na próxima página que a
// pessoa abrir, sem precisar sair e entrar de novo.
require "funcoes.php";
iniciar_sessao();
header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store");

$u = null;
if (isset($_SESSION["usuario_id"])) {
    try {
        $st = $pdo->prepare("SELECT nome, email, tipo, foto FROM usuarios WHERE id = ?");
        $st->execute(array($_SESSION["usuario_id"]));
    } catch (PDOException $e) {
        // Banco ainda sem a coluna "foto" (rode o atualizar_banco.php): usa a imagem padrão
        $st = $pdo->prepare("SELECT nome, email, tipo, NULL AS foto FROM usuarios WHERE id = ?");
        $st->execute(array($_SESSION["usuario_id"]));
    }
    $u = $st->fetch();

    if ($u) {
        $_SESSION["usuario_nome"] = $u["nome"];
        $_SESSION["usuario_tipo"] = $u["tipo"];
    } else {
        // A conta foi apagada pelo admin: encerra a sessão
        $_SESSION = array();
        session_destroy();
    }
}

if ($u) {
    echo json_encode(array(
        "logado" => true,
        "nome"   => $u["nome"],
        "tipo"   => $u["tipo"],
        "email"  => $u["email"],
        "foto"   => foto_url($_SESSION["usuario_id"], $u["foto"])
    ));
} else {
    echo json_encode(array("logado" => false));
}
