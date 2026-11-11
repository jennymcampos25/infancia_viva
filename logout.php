<?php
require "funcoes.php";
iniciar_sessao();
$_SESSION = array();
session_destroy();
// Apaga o cookie do navegador também (inclusive o do "Manter conectado")
if (PHP_VERSION_ID >= 70300) {
    setcookie(session_name(), "", array("expires" => time() - 3600, "path" => "/"));
} else {
    setcookie(session_name(), "", time() - 3600, "/");
}
header("Location: index.html");
exit;
