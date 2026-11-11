<?php
// Painel do admin: grava (incluir/alterar) ou apaga um registro. Recebe os formulários do
// admin_registro.php e os botões "Apagar" das listas do admin.php.
require "funcoes.php";
iniciar_sessao();

$admin = exigir_admin();
$cfg = registros_config();

if ($_SERVER["REQUEST_METHOD"] !== "POST"
    || !isset($_POST["chave"]) || $_POST["chave"] !== chave_admin()) {
    header("Location: admin.php");
    exit;
}

$acao   = isset($_POST["acao"]) ? $_POST["acao"] : "";
$tabela = isset($_POST["tabela"]) ? $_POST["tabela"] : "";
$id     = isset($_POST["id"]) ? (int) $_POST["id"] : 0;
if (!isset($cfg[$tabela])) {
    header("Location: admin.php");
    exit;
}
$conf = $cfg[$tabela];
$voltar_lista = "admin.php?pagina=" . $tabela;

function terminar($destino, $aviso) {
    $_SESSION["aviso_admin"] = $aviso;
    header("Location: " . $destino);
    exit;
}

// Erro no formulário: volta para ele com a mensagem e o que foi digitado (menos a senha)
function erro_formulario($tabela, $id, $mensagem) {
    $dados = $_POST;
    unset($dados["senha"], $dados["chave"], $dados["acao"]);
    $_SESSION["form_admin"] = array("erro" => $mensagem, "dados" => $dados);
    header("Location: admin_registro.php?tabela=" . $tabela . ($id ? "&id=" . $id : ""));
    exit;
}

// ======================= APAGAR =======================
if ($acao === "apagar") {
    if ($tabela === "usuarios" && $id === (int) $admin["id"]) {
        terminar($voltar_lista, "Você não pode apagar a sua própria conta.");
    }
    if ($tabela === "usuarios") {
        $st = $pdo->prepare("SELECT tipo FROM usuarios WHERE id = ?");
        $st->execute(array($id));
        if ($st->fetchColumn() === "admin"
            && (int) $pdo->query("SELECT COUNT(*) FROM usuarios WHERE tipo = 'admin'")->fetchColumn() <= 1) {
            terminar($voltar_lista, "O site precisa ter pelo menos um administrador.");
        }
    }
    // Ao apagar uma conta, as doações, mensagens etc. dela continuam e passam para "Visitantes sem conta"
    $st = $pdo->prepare("DELETE FROM $tabela WHERE id = ?");
    $st->execute(array($id));
    terminar($voltar_lista, $st->rowCount() ? "Registro de " . $conf["nome"] . " apagado." : "Esse registro não existe mais.");
}

if ($acao !== "salvar") {
    header("Location: " . $voltar_lista);
    exit;
}

// ======================= INCLUIR / ALTERAR =======================
// Valor original (para saber se o e-mail mudou)
$original = array();
if ($id) {
    $st = $pdo->prepare("SELECT * FROM $tabela WHERE id = ?");
    $st->execute(array($id));
    $original = $st->fetch();
    if (!$original) terminar($voltar_lista, "Esse registro não existe mais.");
}

$colunas = array();   // coluna => valor que vai para o banco
foreach ($conf["campos"] as $campo => $c) {
    list($rotulo, $tipo, $obrigatorio) = $c;
    $v = isset($_POST[$campo]) ? trim($_POST[$campo]) : "";

    if ($tipo === "senha") {
        if ($v === "" && $id) continue;                       // alterar sem mexer na senha
        if (strlen($v) < 6) erro_formulario($tabela, $id, "A senha precisa ter pelo menos 6 caracteres.");
        $colunas["senha_hash"] = password_hash($v, PASSWORD_DEFAULT);
        continue;
    }

    if ($v === "") {
        if ($obrigatorio) erro_formulario($tabela, $id, "Preencha o campo \"" . $rotulo . "\".");
        $colunas[$campo] = null;
        continue;
    }

    if ($tipo === "email") {
        $v = strtolower($v);
        $mudou = !$original || strtolower((string) $original[$campo]) !== $v;
        if (!filter_var($v, FILTER_VALIDATE_EMAIL) || ($mudou && !email_real($v))) {
            erro_formulario($tabela, $id, "O e-mail \"" . $v . "\" não parece existir. Confira se digitou certo.");
        }
    } elseif ($tipo === "numero") {
        if (!ctype_digit($v) || (int) $v < 1) erro_formulario($tabela, $id, "\"" . $rotulo . "\" precisa ser um número maior que zero.");
        $v = (int) $v;
    } elseif ($tipo === "dinheiro") {
        $v = str_replace(",", ".", $v);
        if (!is_numeric($v) || $v < 0 || $v > 100000) erro_formulario($tabela, $id, "\"" . $rotulo . "\" precisa ser um valor em reais.");
        $v = number_format((float) $v, 2, ".", "");
    } elseif ($tipo === "data") {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) erro_formulario($tabela, $id, "Data inválida em \"" . $rotulo . "\".");
    } elseif ($tipo === "lista") {
        if (!isset($c[3][$v])) erro_formulario($tabela, $id, "Escolha uma opção válida em \"" . $rotulo . "\".");
    } elseif ($tipo === "conta") {
        $st = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
        $st->execute(array((int) $v));
        if (!$st->fetch()) erro_formulario($tabela, $id, "Essa conta não existe mais.");
        $v = (int) $v;
    } elseif (strlen($v) > 5000) {
        erro_formulario($tabela, $id, "O texto de \"" . $rotulo . "\" está grande demais.");
    }
    $colunas[$campo] = $v;
}

// Regras de cada tabela
if ($tabela === "avaliacoes" && $colunas["nota"] === null && $colunas["comentario"] === null) {
    erro_formulario($tabela, $id, "Uma avaliação precisa de uma nota ou de um comentário.");
}
if ($tabela === "usuarios") {
    $st = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id <> ?");
    $st->execute(array($colunas["email"], $id));
    if ($st->fetch()) erro_formulario($tabela, $id, "Já existe uma conta com esse e-mail.");
    if ($id === (int) $admin["id"] && $colunas["tipo"] !== "admin") {
        erro_formulario($tabela, $id, "Você não pode tirar o acesso de administrador da sua própria conta.");
    }
}

try {
    if ($id) {
        $partes = array();
        foreach (array_keys($colunas) as $col) $partes[] = "$col = ?";
        $st = $pdo->prepare("UPDATE $tabela SET " . implode(", ", $partes) . " WHERE id = ?");
        $st->execute(array_merge(array_values($colunas), array($id)));
        // Se o admin mudou o próprio nome, o menu passa a mostrar o nome novo
        if ($tabela === "usuarios" && $id === (int) $admin["id"]) $_SESSION["usuario_nome"] = $colunas["nome"];
        terminar($voltar_lista, "Registro de " . $conf["nome"] . " alterado.");
    } else {
        $st = $pdo->prepare("INSERT INTO $tabela (" . implode(", ", array_keys($colunas)) . ") VALUES ("
                            . implode(", ", array_fill(0, count($colunas), "?")) . ")");
        $st->execute(array_values($colunas));
        terminar($voltar_lista, "Registro de " . $conf["nome"] . " incluído.");
    }
} catch (PDOException $e) {
    erro_formulario($tabela, $id, "Não foi possível salvar. Confira os dados e tente de novo.");
}
