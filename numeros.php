<?php
// Totais mostrados na faixa de números da página inicial (lidos no banco, nada inventado).
header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store");
require "conexao.php";

try {
    $d = $pdo->query("SELECT COALESCE(SUM(quantidade), 0) AS brinquedos,
                             COUNT(*) AS doacoes,
                             COUNT(DISTINCT LOWER(TRIM(email))) AS doadores
                      FROM doacoes")->fetch();
    echo json_encode(array(
        "brinquedos" => (int) $d["brinquedos"],
        "doacoes"    => (int) $d["doacoes"],
        "doadores"   => (int) $d["doadores"],
        "pontos"     => 3   // Sede, Escola parceira e Centro comunitário (lista do doacao.php)
    ));
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(array("erro" => true));
}
