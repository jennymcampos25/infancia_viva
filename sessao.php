<?php
// Diz ao JavaScript das páginas HTML se há alguém logado (usado na bolinha de perfil do menu).
session_start();
header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store");

if (isset($_SESSION["usuario_id"])) {
    $foto = null;
    try {
        require "funcoes.php";
        $st = $pdo->prepare("SELECT foto FROM usuarios WHERE id = ?");
        $st->execute(array($_SESSION["usuario_id"]));
        $foto = $st->fetchColumn();
    } catch (PDOException $e) {
        // Banco ainda sem a coluna "foto" (rode o atualizar_banco.php): usa a imagem padrão
    }
    echo json_encode(array(
        "logado" => true,
        "nome"   => $_SESSION["usuario_nome"],
        "tipo"   => $_SESSION["usuario_tipo"],
        "foto"   => foto_url($_SESSION["usuario_id"], $foto)
    ));
} else {
    echo json_encode(array("logado" => false));
}
