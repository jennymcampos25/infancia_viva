<?php
// Funções usadas por várias páginas: proteção de login, tabelas e histórico do usuário.
// Quem usa faz:  require "funcoes.php";  (ele já inclui o conexao.php)
require_once "conexao.php";

// h() protege a tela contra HTML/JS digitado nos formulários
function h($v) { return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8"); }

function dinheiro($v) { return "R$ " . number_format((float) $v, 2, ",", "."); }

// ---- PROTEÇÃO ----

// Só deixa passar quem está logado. Devolve os dados do usuário (id, nome, email, tipo).
function exigir_login() {
    global $pdo;
    if (session_id() === "") session_start();

    if (!isset($_SESSION["usuario_id"])) {
        header("Location: login.html");
        exit;
    }
    $st = $pdo->prepare("SELECT id, nome, email, tipo, DATE_FORMAT(criado_em, '%d/%m/%Y') AS desde
                         FROM usuarios WHERE id = ?");
    $st->execute(array($_SESSION["usuario_id"]));
    $u = $st->fetch();

    // A conta não existe mais no banco: encerra a sessão
    if (!$u) {
        $_SESSION = array();
        session_destroy();
        header("Location: login.html");
        exit;
    }
    return $u;
}

// Para os formulários: id de quem está logado, ou null (visitante).
// Confere no banco: se a conta foi apagada pelo admin, a sessão é encerrada e o registro vai como visitante.
function usuario_logado_id() {
    global $pdo;
    if (session_id() === "") session_start();
    if (!isset($_SESSION["usuario_id"])) return null;

    $st = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
    $st->execute(array($_SESSION["usuario_id"]));
    if (!$st->fetch()) {
        $_SESSION = array();
        session_destroy();
        return null;
    }
    return (int) $_SESSION["usuario_id"];
}

// Só deixa passar o administrador (o tipo é conferido no banco, não na sessão)
function exigir_admin() {
    $u = exigir_login();
    if ($u["tipo"] !== "admin") {
        header("Location: index.html");
        exit;
    }
    return $u;
}

// ---- TABELAS ----

// $colunas = array("Título da coluna" => "campo_do_banco")
function tabela($colunas, $linhas) {
    if (!$linhas) {
        echo "<p>Nenhum registro ainda.</p>";
        return;
    }
    echo '<div class="tabela"><table><tr>';
    foreach ($colunas as $titulo => $campo) {
        echo "<th>" . h($titulo) . "</th>";
    }
    echo "</tr>";
    foreach ($linhas as $l) {
        echo "<tr>";
        foreach ($colunas as $campo) {
            echo "<td>" . h(isset($l[$campo]) ? $l[$campo] : "") . "</td>";
        }
        echo "</tr>";
    }
    echo "</table></div>";
}

// ---- HISTÓRICO DE UM USUÁRIO ----

// $usuario_id = número do usuário, ou null para "visitantes sem conta"
function buscar_historico($usuario_id) {
    global $pdo;

    if ($usuario_id === null) {
        $onde = "usuario_id IS NULL";
        $par  = array();
    } else {
        $onde = "usuario_id = ?";
        $par  = array($usuario_id);
    }
    $dh = "DATE_FORMAT(criado_em, '%d/%m/%Y %H:%i') AS quando";

    $consultas = array(
        "doacoes"    => "SELECT nome, email, telefone, brinquedo, quantidade, endereco, ponto, valor, observacoes,
                                DATE_FORMAT(data_doacao, '%d/%m/%Y') AS dia, $dh
                         FROM doacoes WHERE $onde ORDER BY id DESC",
        "pagamentos" => "SELECT nome, valor, metodo, status, $dh FROM pagamentos WHERE $onde ORDER BY id DESC",
        "mensagens"  => "SELECT nome, email, mensagem, $dh FROM mensagens WHERE $onde ORDER BY id DESC",
        "avaliacoes" => "SELECT nota, comentario, $dh FROM avaliacoes WHERE $onde ORDER BY id DESC"
    );

    $hist = array();
    foreach ($consultas as $chave => $sql) {
        $st = $pdo->prepare($sql);
        $st->execute($par);
        $hist[$chave] = $st->fetchAll();
    }

    // Totais para os cartões do topo (calculados antes de formatar os valores)
    $brinquedos = 0;
    $prometido  = 0;
    foreach ($hist["doacoes"] as $d) {
        $brinquedos += (int) $d["quantidade"];
        $prometido  += (float) $d["valor"];
    }
    $pago = 0;
    foreach ($hist["pagamentos"] as $p) {
        $pago += (float) $p["valor"];
    }
    $hist["totais"] = array("brinquedos" => $brinquedos, "prometido" => $prometido, "pago" => $pago);

    // Formata para exibir
    foreach ($hist["doacoes"] as &$d) {
        $d["valor"] = $d["valor"] === null ? "—" : dinheiro($d["valor"]);
    }
    unset($d);
    foreach ($hist["pagamentos"] as &$p) {
        $p["valor"] = dinheiro($p["valor"]);
    }
    unset($p);
    foreach ($hist["avaliacoes"] as &$a) {
        $a["nota"] = $a["nota"] ? str_repeat("★", (int) $a["nota"]) . " (" . (int) $a["nota"] . ")" : "—";
    }
    unset($a);

    return $hist;
}

// Mostra os cartões com as quantidades e, embaixo, a lista de cada coisa.
// $com_contato = true mostra nome/e-mail usados em cada registro (útil para o admin)
function mostrar_historico($hist, $com_contato) {
    $t = $hist["totais"];
    ?>
    <div class="stats">
        <div class="stat"><strong><?= count($hist["doacoes"]) ?></strong>doações de brinquedos</div>
        <div class="stat"><strong><?= $t["brinquedos"] ?></strong>brinquedos</div>
        <div class="stat"><strong><?= count($hist["pagamentos"]) ?></strong>pagamentos</div>
        <div class="stat"><strong><?= dinheiro($t["pago"]) ?></strong>pago (simulado)</div>
        <div class="stat"><strong><?= count($hist["mensagens"]) ?></strong>mensagens</div>
        <div class="stat"><strong><?= count($hist["avaliacoes"]) ?></strong>avaliações</div>
    </div>
    <?php

    // Nome, e-mail e telefone digitados no formulário (só aparecem para o admin)
    $contato_doacao   = $com_contato ? array("Doador" => "nome", "E-mail" => "email", "Telefone" => "telefone") : array();
    $contato_mensagem = $com_contato ? array("Nome" => "nome", "E-mail" => "email") : array();

    $doacao = array_merge(array("Registrada em" => "quando"), $contato_doacao, array(
        "Brinquedo" => "brinquedo", "Qtd" => "quantidade", "Data da doação" => "dia",
        "Ponto de entrega" => "ponto", "Endereço" => "endereco",
        "Valor escolhido" => "valor", "Observações" => "observacoes"
    ));
    $mensagem = array_merge(array("Quando" => "quando"), $contato_mensagem, array("Mensagem" => "mensagem"));

    echo "<h2>Doações de brinquedos</h2>";
    tabela($doacao, $hist["doacoes"]);

    echo "<h2>Pagamentos</h2>";
    tabela(array("Quando" => "quando", "Nome" => "nome", "Valor" => "valor", "Forma" => "metodo", "Status" => "status"),
           $hist["pagamentos"]);

    echo "<h2>Mensagens enviadas</h2>";
    tabela($mensagem, $hist["mensagens"]);

    echo "<h2>Avaliações</h2>";
    tabela(array("Quando" => "quando", "Nota" => "nota", "Comentário" => "comentario"), $hist["avaliacoes"]);
}
