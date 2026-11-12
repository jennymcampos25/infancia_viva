<?php
// A página do Pix pergunta aqui, a cada poucos segundos, se o pedido já foi pago.
require "funcoes.php";
header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store");

$p = buscar_pedido(isset($_GET["c"]) ? $_GET["c"] : "");
echo json_encode($p ? array("status" => $p["status"], "segundos" => (int) $p["segundos_restantes"])
                    : array("status" => "inexistente"));
