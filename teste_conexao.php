<?php
header("Content-Type: text/html; charset=UTF-8");
require "conexao.php";

$tabelas = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
$esperadas = array("usuarios", "doacoes", "pagamentos", "mensagens", "avaliacoes");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Teste de conexão</title>
    <link rel="stylesheet" href="estilo.css?v=7">
    <link rel="icon" href="favicon.svg" type="image/svg+xml">
</head>
<body>
<main>
    <h1>Teste de conexão</h1>
    <p class="aviso ok">Conexão com o banco OK! ✔</p>

    <h2>Tabelas encontradas</h2>
    <ul>
    <?php foreach ($esperadas as $t): ?>
        <li><?= $t ?>:
            <?= in_array($t, $tabelas) ? "✔ existe" : "✘ NÃO encontrada (importe o banco.sql de novo)" ?>
        </li>
    <?php endforeach; ?>
    </ul>

    <p>Pode apagar este arquivo quando terminar os testes.</p>
</main>
</body>
</html>
