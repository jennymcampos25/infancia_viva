<?php
// Painel do administrador: tudo o que UM usuário fez. ?id=0 mostra o que foi feito sem login.
session_start();
header("Content-Type: text/html; charset=UTF-8");
header("Cache-Control: no-store");
require "funcoes.php";

$admin = exigir_admin();

$id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

if ($id === 0) {
    $pessoa = null;
    $hist = buscar_historico(null);
} else {
    $st = $pdo->prepare("SELECT id, nome, email, tipo, foto, DATE_FORMAT(criado_em, '%d/%m/%Y') AS desde
                         FROM usuarios WHERE id = ?");
    $st->execute(array($id));
    $pessoa = $st->fetch();
    if (!$pessoa) {
        header("Location: admin.php?pagina=usuarios");
        exit;
    }
    $hist = buscar_historico($id);
}

painel_inicio($pessoa ? $pessoa["nome"] : "Visitantes sem conta", "usuarios", $admin);
?>
    <p><a href="admin.php?pagina=usuarios">← Voltar para a lista de usuários</a></p>

    <section class="painel-caixa ficha">
        <?php if ($pessoa): ?>
            <img src="<?= h(foto_url($pessoa["id"], $pessoa["foto"])) ?>" alt="">
            <div>
                <strong><?= h($pessoa["nome"]) ?></strong>
                <p><?= h($pessoa["email"]) ?> · <span class="etiqueta <?= $pessoa["tipo"] === "admin" ? "et-admin" : "" ?>"><?= h($pessoa["tipo"]) ?></span> · cadastro em <?= h($pessoa["desde"]) ?></p>
            </div>
        <?php else: ?>
            <img src="imagens/avatares/padrao.svg" alt="">
            <div>
                <strong>Visitantes sem conta</strong>
                <p>Tudo o que foi feito no site sem estar logado.</p>
            </div>
        <?php endif; ?>
    </section>

    <?php mostrar_historico($hist, true); ?>
<?php
painel_fim();
