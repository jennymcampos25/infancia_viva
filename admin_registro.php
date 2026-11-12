<?php
// Painel do admin: formulário para incluir (sem ?id) ou alterar (com ?id=N) um registro.
// Ex.: admin_registro.php?tabela=doacoes&id=5. O formulário é enviado para admin_salvar.php.
require "funcoes.php";
iniciar_sessao();
header("Content-Type: text/html; charset=UTF-8");
header("Cache-Control: no-store");

$admin = exigir_admin();
$cfg = registros_config();

$tabela = isset($_GET["tabela"]) ? $_GET["tabela"] : "";
if (!isset($cfg[$tabela])) {
    header("Location: admin.php");
    exit;
}
$conf = $cfg[$tabela];
$id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

// Valores do formulário: do banco (alterar), vazios (incluir) ou os que voltaram com erro do admin_salvar.php
$valores = array();
if ($id) {
    $st = $pdo->prepare("SELECT * FROM $tabela WHERE id = ?");
    $st->execute(array($id));
    $valores = $st->fetch();
    if (!$valores) {
        header("Location: admin.php?pagina=" . $tabela);
        exit;
    }
}
$erro = "";
if (isset($_SESSION["form_admin"])) {
    $erro = $_SESSION["form_admin"]["erro"];
    $valores = array_merge((array) $valores, $_SESSION["form_admin"]["dados"]);
    unset($_SESSION["form_admin"]);
}

// Lista de contas para os campos do tipo "conta"
$contas = $pdo->query("SELECT id, nome, email FROM usuarios ORDER BY nome")->fetchAll();

function valor($valores, $campo) {
    return isset($valores[$campo]) && $valores[$campo] !== null ? $valores[$campo] : "";
}

painel_inicio(($id ? "Editar " : "Incluir ") . $conf["nome"], $tabela, $admin);
?>
    <p><a href="admin.php?pagina=<?= h($tabela) ?>">← Voltar</a></p>

    <?php if ($erro): ?><p class="aviso erro"><?= h($erro) ?></p><?php endif; ?>

    <form class="painel-caixa form-registro" action="admin_salvar.php" method="post">
        <input type="hidden" name="acao" value="salvar">
        <input type="hidden" name="tabela" value="<?= h($tabela) ?>">
        <input type="hidden" name="id" value="<?= $id ?>">
        <input type="hidden" name="chave" value="<?= h(chave_admin()) ?>">

        <?php foreach ($conf["campos"] as $campo => $c):
            list($rotulo, $tipo, $obrigatorio) = $c;
            $v = valor($valores, $campo);
            if ($v === "" && !$id && in_array($campo, array("email_confirmado", "ativo"))) $v = "1";   // padrão ao incluir
            $req = $obrigatorio ? " required" : "";
            ?>
            <label for="c_<?= $campo ?>"><?= h($rotulo) ?><?= $obrigatorio ? "" : ' <small>(opcional)</small>' ?></label>
            <?php if ($tipo === "textarea"): ?>
                <textarea id="c_<?= $campo ?>" name="<?= $campo ?>" rows="5"<?= $req ?>><?= h($v) ?></textarea>
            <?php elseif ($tipo === "lista"): ?>
                <select id="c_<?= $campo ?>" name="<?= $campo ?>"<?= $req ?>>
                    <option value=""><?= $obrigatorio ? "Escolha" : "Nenhuma" ?></option>
                    <?php foreach ($c[3] as $op => $texto): ?>
                        <option value="<?= h($op) ?>"<?= (string) $v === (string) $op ? " selected" : "" ?>><?= h($texto) ?></option>
                    <?php endforeach; ?>
                </select>
            <?php elseif ($tipo === "conta"): ?>
                <select id="c_<?= $campo ?>" name="<?= $campo ?>">
                    <option value="">Nenhuma (visitante sem conta)</option>
                    <?php foreach ($contas as $ct): ?>
                        <option value="<?= (int) $ct["id"] ?>"<?= (string) $v === (string) $ct["id"] ? " selected" : "" ?>><?= h($ct["nome"] . " (" . $ct["email"] . ")") ?></option>
                    <?php endforeach; ?>
                </select>
            <?php elseif ($tipo === "senha"): ?>
                <input id="c_<?= $campo ?>" type="password" name="<?= $campo ?>" autocomplete="new-password"<?= $id ? "" : " required" ?>>
                <p class="dica"><?= $id ? "Deixe em branco para manter a senha atual." : "Mínimo de 6 caracteres." ?></p>
            <?php else:
                $html = array("texto" => "text", "email" => "email", "numero" => "number", "data" => "date", "dinheiro" => "number");
                $extra = $tipo === "numero" ? ' min="1"' : ($tipo === "dinheiro" ? ' min="0" step="0.01"' : ""); ?>
                <input id="c_<?= $campo ?>" type="<?= $html[$tipo] ?>" name="<?= $campo ?>" value="<?= h($v) ?>"<?= $extra . $req ?>>
            <?php endif; ?>
        <?php endforeach; ?>

        <button type="submit"><?= $id ? "Salvar alterações" : "Incluir " . h($conf["nome"]) ?></button>
    </form>
<?php
painel_fim();
