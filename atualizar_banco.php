<?php
// Atualiza um banco ANTIGO: liga doações, pagamentos, mensagens e avaliações ao usuário
// e cria as colunas da foto de perfil.
// Rode uma vez em cada computador (precisa estar logado como administrador).
// Pode rodar de novo sem problema: o que já foi feito é pulado.
session_start();
header("Content-Type: text/html; charset=UTF-8");
require "funcoes.php";

exigir_admin();

$tabelas = array("doacoes", "pagamentos", "mensagens", "avaliacoes");
$relatorio = array();

try {
    foreach ($tabelas as $t) {
        $existe = $pdo->query("SHOW COLUMNS FROM $t LIKE 'usuario_id'")->fetch();
        if ($existe) {
            $relatorio[] = "$t: já estava atualizada.";
            continue;
        }

        $pdo->exec("ALTER TABLE $t
                    ADD COLUMN usuario_id INT NULL AFTER id,
                    ADD CONSTRAINT fk_{$t}_usuario FOREIGN KEY (usuario_id)
                        REFERENCES usuarios(id) ON DELETE SET NULL");
        $msg = "$t: coluna usuario_id criada.";

        // Registros antigos: liga pelo e-mail (só doações e mensagens têm e-mail)
        if ($t === "doacoes" || $t === "mensagens") {
            $n = $pdo->exec("UPDATE $t x JOIN usuarios u ON LOWER(TRIM(x.email)) = u.email
                             SET x.usuario_id = u.id WHERE x.usuario_id IS NULL");
            $msg .= " $n registro(s) antigo(s) ligado(s) a usuários pelo e-mail.";
        }
        $relatorio[] = $msg;
    }

    // Foto de perfil dos usuários
    if ($pdo->query("SHOW COLUMNS FROM usuarios LIKE 'foto'")->fetch()) {
        $relatorio[] = "usuarios: colunas da foto de perfil já existiam.";
    } else {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN foto VARCHAR(30) NULL AFTER tipo,
                                         ADD COLUMN foto_dados MEDIUMBLOB NULL AFTER foto");
        $relatorio[] = "usuarios: colunas da foto de perfil criadas.";
    }
    $ok = true;
} catch (PDOException $e) {
    $ok = false;
    $relatorio[] = "Erro: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Atualizar banco</title>
    <link rel="stylesheet" href="estilo.css">
    <link rel="icon" href="favicon.svg" type="image/svg+xml">
</head>
<body>
<main>
    <h1>Atualizar banco</h1>
    <p class="aviso <?= $ok ? "ok" : "erro" ?>"><?= $ok ? "Banco atualizado! ✔" : "Algo deu errado ✘" ?></p>
    <ul>
    <?php foreach ($relatorio as $linha): ?>
        <li><?= h($linha) ?></li>
    <?php endforeach; ?>
    </ul>
    <p><a href="admin.php">Ir para o painel</a></p>
</main>
</body>
</html>
