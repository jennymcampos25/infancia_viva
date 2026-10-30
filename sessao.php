<?php
// Diz ao JavaScript das páginas HTML se há alguém logado (usado no menu "Login" / "Sair").
session_start();
header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store");

if (isset($_SESSION["usuario_id"])) {
    echo json_encode(array(
        "logado" => true,
        "nome"   => $_SESSION["usuario_nome"],
        "tipo"   => $_SESSION["usuario_tipo"]
    ));
} else {
    echo json_encode(array("logado" => false));
}
