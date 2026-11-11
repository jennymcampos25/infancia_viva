<?php
// Botões de "Ferramentas de teste" do painel. Nenhuma opção apaga a conta do administrador.
require "funcoes.php";
iniciar_sessao();

exigir_admin();

if ($_SERVER["REQUEST_METHOD"] !== "POST"
    || !isset($_POST["chave"], $_SESSION["chave_admin"])
    || $_POST["chave"] !== $_SESSION["chave_admin"]) {
    header("Location: admin.php");
    exit;
}

$acao = isset($_POST["acao"]) ? $_POST["acao"] : "";
$registros = array("doacoes", "pagamentos", "mensagens", "avaliacoes");
$nomes = array(
    "doacoes" => "doação", "pagamentos" => "pagamento", "mensagens" => "mensagem",
    "avaliacoes" => "avaliação", "usuarios" => "conta"
);

// Apaga contas de doadores (e tudo o que elas fizeram). $onde filtra quais contas.
function apagar_contas($onde, $par) {
    global $pdo, $registros;
    $ids = "SELECT id FROM usuarios WHERE tipo <> 'admin' AND $onde";
    $n = 0;
    foreach ($registros as $t) {
        $st = $pdo->prepare("DELETE FROM $t WHERE usuario_id IN ($ids)");
        $st->execute($par);
        $n += $st->rowCount();
    }
    $st = $pdo->prepare("DELETE FROM usuarios WHERE tipo <> 'admin' AND $onde");
    $st->execute($par);
    return array($st->rowCount(), $n);
}

try {
    $pdo->beginTransaction();

    if ($acao === "ultimo") {
        // O mais recente entre todas as tabelas (contas de admin ficam de fora)
        $partes = array();
        foreach ($registros as $t) {
            $partes[] = "SELECT '$t' AS tabela, id, criado_em FROM $t";
        }
        $partes[] = "SELECT 'usuarios' AS tabela, id, criado_em FROM usuarios WHERE tipo <> 'admin'";
        $ultimo = $pdo->query(implode(" UNION ALL ", $partes) . " ORDER BY criado_em DESC, id DESC LIMIT 1")->fetch();

        if (!$ultimo) {
            $aviso = "Não há nenhum registro para apagar.";
        } elseif ($ultimo["tabela"] === "usuarios") {
            $r = apagar_contas("id = ?", array($ultimo["id"]));
            $aviso = "Apagada a última conta criada" . ($r[1] ? " e seus $r[1] registro(s)." : ".");
        } else {
            $st = $pdo->prepare("DELETE FROM {$ultimo["tabela"]} WHERE id = ?");
            $st->execute(array($ultimo["id"]));
            $aviso = "Apagado o último registro: " . $nomes[$ultimo["tabela"]] . " de " .
                     date("d/m/Y H:i", strtotime($ultimo["criado_em"])) . ".";
        }

    } elseif ($acao === "visitantes") {
        $n = 0;
        foreach ($registros as $t) {
            $n += $pdo->exec("DELETE FROM $t WHERE usuario_id IS NULL");
        }
        $aviso = "Apagados $n registro(s) de visitantes.";

    } elseif ($acao === "contas") {
        $r = apagar_contas("1 = 1", array());
        $aviso = "Apagadas $r[0] conta(s) de doadores e $r[1] registro(s) delas.";

    } elseif ($acao === "tudo") {
        $n = 0;
        foreach ($registros as $t) {
            $n += $pdo->exec("DELETE FROM $t");
        }
        $c = $pdo->exec("DELETE FROM usuarios WHERE tipo <> 'admin'");
        $aviso = "Apagados $n registro(s) e $c conta(s) de doadores. O administrador foi mantido.";

    } else {
        $aviso = "";
    }

    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
    $aviso = "Não foi possível apagar: " . $e->getMessage();
}

$_SESSION["aviso_admin"] = $aviso;
header("Location: admin.php");
exit;
