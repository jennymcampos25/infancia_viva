<?php
// Painel administrativo. Cada assunto é uma "página": admin.php?pagina=geral, usuarios, doacoes,
// pagamentos, mensagens ou avaliacoes. O layout (menu lateral) está em funcoes.php.
header("Content-Type: text/html; charset=UTF-8");
header("Cache-Control: no-store");
require "funcoes.php";
iniciar_sessao();

// ---- PROTEÇÃO: só entra quem está logado E é administrador no banco ----
$admin = exigir_admin();

// Banco ainda no formato antigo (sem usuario_id ou sem foto)? Manda para a atualização.
if (!$pdo->query("SHOW COLUMNS FROM doacoes LIKE 'usuario_id'")->fetch()
    || !$pdo->query("SHOW COLUMNS FROM usuarios LIKE 'foto'")->fetch()) {
    header("Location: atualizar_banco.php");
    exit;
}

$pagina = isset($_GET["pagina"]) ? $_GET["pagina"] : "geral";
if (!isset($PAINEL_MENU[$pagina])) $pagina = "geral";

function contar($tabela) {
    global $pdo;
    $permitidas = array("pagamentos", "doacoes", "usuarios", "mensagens", "avaliacoes");
    if (!in_array($tabela, $permitidas)) return 0;
    return (int) $pdo->query("SELECT COUNT(*) FROM $tabela")->fetchColumn();
}

function estrelas($nota) {
    return $nota ? str_repeat("★", (int) $nota) . str_repeat("☆", 5 - (int) $nota) : "sem nota";
}

// Chave secreta da sessão: os botões de apagar só funcionam vindos do painel
chave_admin();

// Resultado da última limpeza (vem do admin_limpar.php)
$aviso_limpeza = isset($_SESSION["aviso_admin"]) ? $_SESSION["aviso_admin"] : "";
unset($_SESSION["aviso_admin"]);

$dh = "DATE_FORMAT(criado_em, '%d/%m/%Y %H:%i') AS quando";

painel_inicio($PAINEL_MENU[$pagina][0], $pagina, $admin);

if ($aviso_limpeza) {
    echo '<p class="aviso ok">' . h($aviso_limpeza) . '</p>';
}

// =====================================================================
if ($pagina === "geral"):
    $total      = (float) $pdo->query("SELECT COALESCE(SUM(valor), 0) FROM pagamentos")->fetchColumn();
    $brinquedos = (int) $pdo->query("SELECT COALESCE(SUM(quantidade), 0) FROM doacoes")->fetchColumn();
    $media      = $pdo->query("SELECT AVG(nota) FROM avaliacoes")->fetchColumn();
    $doadores   = (int) $pdo->query("SELECT COUNT(*) FROM usuarios WHERE tipo <> 'admin'")->fetchColumn();

    // Atividade recente: as últimas coisas que aconteceram no site, de todas as tabelas
    $eventos = array();
    foreach ($pdo->query("SELECT nome, quantidade, brinquedo, criado_em FROM doacoes ORDER BY id DESC LIMIT 8") as $r) {
        $eventos[] = array($r["criado_em"], "doacao", $r["nome"], "doou " . (int) $r["quantidade"] . " × " . $r["brinquedo"]);
    }
    foreach ($pdo->query("SELECT nome, valor, metodo, criado_em FROM pagamentos ORDER BY id DESC LIMIT 8") as $r) {
        $eventos[] = array($r["criado_em"], "pagamento", $r["nome"], "contribuiu com " . dinheiro($r["valor"]) . " (" . $r["metodo"] . ")");
    }
    foreach ($pdo->query("SELECT nome, criado_em FROM mensagens ORDER BY id DESC LIMIT 8") as $r) {
        $eventos[] = array($r["criado_em"], "mensagem", $r["nome"], "enviou uma mensagem");
    }
    foreach ($pdo->query("SELECT a.nota, a.criado_em, u.nome FROM avaliacoes a LEFT JOIN usuarios u ON u.id = a.usuario_id
                          ORDER BY a.id DESC LIMIT 8") as $r) {
        $eventos[] = array($r["criado_em"], "avaliacao", $r["nome"] ? $r["nome"] : "Visitante",
                           "avaliou o projeto" . ($r["nota"] ? " com " . (int) $r["nota"] . " estrela(s)" : ""));
    }
    foreach ($pdo->query("SELECT nome, criado_em FROM usuarios WHERE tipo <> 'admin' ORDER BY id DESC LIMIT 8") as $r) {
        $eventos[] = array($r["criado_em"], "usuario", $r["nome"], "criou uma conta");
    }
    rsort($eventos);   // o primeiro campo é a data: do mais novo para o mais antigo
    $eventos = array_slice($eventos, 0, 10);
    $icones_evento = array("doacao" => "doacoes", "pagamento" => "pagamentos", "mensagem" => "mensagens",
                           "avaliacao" => "avaliacoes", "usuario" => "usuarios");
    ?>
    <div class="stats">
        <div class="stat"><strong><?= contar("doacoes") ?></strong>doações registradas</div>
        <div class="stat"><strong><?= $brinquedos ?></strong>brinquedos</div>
        <div class="stat"><strong><?= $doadores ?></strong>doadores com conta</div>
        <div class="stat"><strong><?= dinheiro($total) ?></strong>em pagamentos (simulados)</div>
        <div class="stat"><strong><?= contar("mensagens") ?></strong>mensagens</div>
        <div class="stat"><strong><?= $media !== null && $media !== false ? number_format($media, 1, ",", ".") : "—" ?></strong>nota média (<?= contar("avaliacoes") ?>)</div>
    </div>

    <div class="painel-grade">
        <section class="painel-caixa">
            <h2>Atividade recente</h2>
            <?php if (!$eventos): ?>
                <p class="vazio">Nada aconteceu no site ainda.</p>
            <?php else: ?>
                <ul class="atividade">
                <?php foreach ($eventos as $e): ?>
                    <li class="ev-<?= $e[1] ?>">
                        <span class="ev-icone"><?= icone_painel($PAINEL_MENU[$icones_evento[$e[1]]][1]) ?></span>
                        <span><strong><?= h($e[2]) ?></strong> <?= h($e[3]) ?>
                            <small><?= h(date("d/m/Y H:i", strtotime($e[0]))) ?></small></span>
                    </li>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <section class="painel-caixa">
            <h2>Atalhos</h2>
            <div class="atalhos">
                <?php foreach (array("doacoes", "mensagens", "usuarios", "avaliacoes") as $p): ?>
                    <a href="admin.php?pagina=<?= $p ?>"><?= icone_painel($PAINEL_MENU[$p][1]) ?><?= h($PAINEL_MENU[$p][0]) ?></a>
                <?php endforeach; ?>
                <a href="index.html"><?= icone_painel('<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6M15 3h6v6M10 14 21 3"/>') ?>Ver o site</a>
            </div>
        </section>
    </div>

    <!-- Ferramentas de teste: ficam fechadas; clique em "Ferramentas de teste" para abrir -->
    <details class="ferramentas">
        <summary>Ferramentas de teste</summary>
        <p>Nenhuma opção apaga a conta do administrador. Não dá para desfazer.</p>
        <?php
        $botoes = array(
            "ultimo"     => array("Apagar o último registro", "Apagar o registro mais recente do site?"),
            "visitantes" => array("Apagar registros de visitantes", "Apagar tudo o que foi feito sem login?"),
            "contas"     => array("Apagar contas de doadores", "Apagar todas as contas de doadores e tudo o que elas fizeram?"),
            "tudo"       => array("Apagar tudo", "Apagar TODOS os registros e contas de doadores?")
        );
        foreach ($botoes as $acao => $b): ?>
            <form action="admin_limpar.php" method="post" onsubmit="return confirm('<?= h($b[1]) ?>')">
                <input type="hidden" name="acao" value="<?= $acao ?>">
                <input type="hidden" name="chave" value="<?= h($_SESSION["chave_admin"]) ?>">
                <button type="submit" class="perigo"><?= h($b[0]) ?></button>
            </form>
        <?php endforeach; ?>
    </details>
<?php

// =====================================================================
elseif ($pagina === "usuarios"):
    $usuarios = $pdo->query(
        "SELECT u.id, u.nome, u.email, u.tipo, u.foto, DATE_FORMAT(u.criado_em, '%d/%m/%Y') AS desde,
                (SELECT COUNT(*) FROM doacoes    WHERE usuario_id = u.id) AS n_doacoes,
                (SELECT COUNT(*) FROM pagamentos WHERE usuario_id = u.id) AS n_pagamentos,
                (SELECT COUNT(*) FROM mensagens  WHERE usuario_id = u.id) AS n_mensagens,
                (SELECT COUNT(*) FROM avaliacoes WHERE usuario_id = u.id) AS n_avaliacoes
         FROM usuarios u ORDER BY u.id DESC"
    )->fetchAll();

    // O que foi feito sem login (não pertence a nenhum usuário)
    $visitantes = $pdo->query(
        "SELECT (SELECT COUNT(*) FROM doacoes    WHERE usuario_id IS NULL) AS n_doacoes,
                (SELECT COUNT(*) FROM pagamentos WHERE usuario_id IS NULL) AS n_pagamentos,
                (SELECT COUNT(*) FROM mensagens  WHERE usuario_id IS NULL) AS n_mensagens,
                (SELECT COUNT(*) FROM avaliacoes WHERE usuario_id IS NULL) AS n_avaliacoes"
    )->fetch();
    ?>
    <section class="painel-caixa">
        <div class="painel-caixa-topo">
            <p>Clique no nome para ver tudo o que a pessoa fez.</p>
            <div class="painel-ferramentas"><?php busca_tabela("tabela-usuarios", "Buscar por nome ou e-mail"); ?><?= botao_novo("usuarios") ?></div>
        </div>
        <div class="tabela"><table id="tabela-usuarios">
            <tr>
                <th>Nome</th><th>E-mail</th><th>Tipo</th><th>Cadastro</th>
                <th>Doações</th><th>Pagamentos</th><th>Mensagens</th><th>Avaliações</th><th>Ações</th>
            </tr>
            <?php foreach ($usuarios as $u): ?>
            <tr>
                <td><a class="pessoa" href="admin_usuario.php?id=<?= (int) $u["id"] ?>"><img src="<?= h(foto_url($u["id"], $u["foto"])) ?>" alt=""><?= h($u["nome"]) ?></a></td>
                <td><?= h($u["email"]) ?></td>
                <td><span class="etiqueta <?= $u["tipo"] === "admin" ? "et-admin" : "" ?>"><?= h($u["tipo"]) ?></span></td>
                <td><?= h($u["desde"]) ?></td>
                <td><?= (int) $u["n_doacoes"] ?></td>
                <td><?= (int) $u["n_pagamentos"] ?></td>
                <td><?= (int) $u["n_mensagens"] ?></td>
                <td><?= (int) $u["n_avaliacoes"] ?></td>
                <td><?= acoes_registro("usuarios", $u["id"], "usuário") ?></td>
            </tr>
            <?php endforeach; ?>
            <tr>
                <td><a class="pessoa" href="admin_usuario.php?id=0"><img src="imagens/avatares/padrao.svg" alt=""><em>Visitantes sem conta</em></a></td>
                <td>—</td><td>—</td><td>—</td>
                <td><?= (int) $visitantes["n_doacoes"] ?></td>
                <td><?= (int) $visitantes["n_pagamentos"] ?></td>
                <td><?= (int) $visitantes["n_mensagens"] ?></td>
                <td><?= (int) $visitantes["n_avaliacoes"] ?></td>
                <td></td>
            </tr>
        </table></div>
    </section>
<?php

// =====================================================================
elseif ($pagina === "doacoes"):
    $doacoes = $pdo->query("SELECT id, nome, email, telefone, brinquedo, quantidade, ponto, valor, observacoes,
                                   DATE_FORMAT(data_doacao, '%d/%m/%Y') AS dia, $dh
                            FROM doacoes ORDER BY id DESC LIMIT 300")->fetchAll();
    foreach ($doacoes as &$d) {
        $d["valor"] = $d["valor"] === null ? "—" : dinheiro($d["valor"]);
    }
    unset($d);
    $por_ponto = $pdo->query("SELECT ponto, COUNT(*) AS n, SUM(quantidade) AS q FROM doacoes GROUP BY ponto ORDER BY q DESC")->fetchAll();
    ?>
    <?php if ($por_ponto): ?>
    <div class="stats">
        <?php foreach ($por_ponto as $p): ?>
            <div class="stat"><strong><?= (int) $p["q"] ?></strong>brinquedos em <?= h($p["ponto"]) ?> (<?= (int) $p["n"] ?> doações)</div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <section class="painel-caixa">
        <div class="painel-caixa-topo">
            <p>As 300 doações mais recentes. E-mail, telefone e observações aparecem em "Editar".</p>
            <div class="painel-ferramentas"><?php busca_tabela("tabela-doacoes", "Buscar por doador, brinquedo ou local"); ?><?= botao_novo("doacoes") ?></div>
        </div>
        <?php tabela(array(
            "Registrada em" => "quando", "Doador" => "nome", "Brinquedo" => "brinquedo", "Qtd" => "quantidade",
            "Entrega" => "dia", "Local" => "ponto", "Valor" => "valor"
        ), $doacoes, "tabela-doacoes", "doacoes"); ?>
    </section>
<?php

// =====================================================================
elseif ($pagina === "pagamentos"):
    $pagamentos = $pdo->query("SELECT id, nome, valor, metodo, status, $dh FROM pagamentos ORDER BY id DESC LIMIT 300")->fetchAll();
    $total = 0;
    foreach ($pagamentos as &$p) {
        $total += (float) $p["valor"];
        $p["valor"] = dinheiro($p["valor"]);
    }
    unset($p);
    ?>
    <div class="stats">
        <div class="stat"><strong><?= dinheiro($total) ?></strong>total (pagamentos simulados)</div>
        <div class="stat"><strong><?= count($pagamentos) ?></strong>pagamentos</div>
    </div>
    <section class="painel-caixa">
        <div class="painel-caixa-topo">
            <p>O pagamento do site é uma demonstração: nenhum valor é cobrado de verdade.</p>
            <div class="painel-ferramentas"><?php busca_tabela("tabela-pagamentos", "Buscar por nome ou forma"); ?><?= botao_novo("pagamentos") ?></div>
        </div>
        <?php tabela(array("Quando" => "quando", "Nome" => "nome", "Valor" => "valor", "Forma" => "metodo", "Status" => "status"),
                     $pagamentos, "tabela-pagamentos", "pagamentos"); ?>
    </section>
<?php

// =====================================================================
elseif ($pagina === "mensagens"):
    $mensagens = $pdo->query("SELECT id, nome, email, mensagem, $dh FROM mensagens ORDER BY id DESC LIMIT 200")->fetchAll();
    ?>
    <div class="painel-caixa-topo">
        <p><?= count($mensagens) ?> mensagem(ns).</p>
        <div class="painel-ferramentas"><?php busca_tabela("lista-mensagens", "Buscar nas mensagens"); ?><?= botao_novo("mensagens") ?></div>
    </div>
    <?php if (!$mensagens): ?>
        <p class="vazio">Nenhuma mensagem recebida ainda.</p>
    <?php endif; ?>
    <div class="mensagens" id="lista-mensagens">
        <?php foreach ($mensagens as $m): ?>
            <article class="mensagem">
                <header>
                    <strong><?= h($m["nome"]) ?></strong>
                    <small><?= h($m["quando"]) ?></small>
                </header>
                <p><?= nl2br(h($m["mensagem"])) ?></p>
                <a class="link" href="mailto:<?= h(rawurlencode($m["email"])) ?>?subject=<?= rawurlencode("Infância Viva: resposta à sua mensagem") ?>">Responder para <?= h($m["email"]) ?> →</a>
                <?= acoes_registro("mensagens", $m["id"], "mensagem") ?>
            </article>
        <?php endforeach; ?>
    </div>
<?php

// =====================================================================
elseif ($pagina === "avaliacoes"):
    $avaliacoes = $pdo->query("SELECT a.id, a.nota, a.comentario, DATE_FORMAT(a.criado_em, '%d/%m/%Y %H:%i') AS quando, u.nome
                               FROM avaliacoes a LEFT JOIN usuarios u ON u.id = a.usuario_id
                               ORDER BY a.id DESC LIMIT 200")->fetchAll();
    $media = $pdo->query("SELECT AVG(nota) FROM avaliacoes")->fetchColumn();
    $contagem = array(5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0);
    foreach ($pdo->query("SELECT nota, COUNT(*) AS n FROM avaliacoes WHERE nota IS NOT NULL GROUP BY nota") as $r) {
        $contagem[(int) $r["nota"]] = (int) $r["n"];
    }
    $maior = max(1, max($contagem));
    ?>
    <div class="painel-grade">
        <section class="painel-caixa nota-geral">
            <strong><?= $media !== null && $media !== false ? number_format($media, 1, ",", ".") : "—" ?></strong>
            <span class="estrela"><?= $media ? estrelas(round($media)) : "" ?></span>
            <p><?= count($avaliacoes) ?> avaliação(ões)</p>
        </section>
        <section class="painel-caixa">
            <h2>Notas</h2>
            <?php foreach ($contagem as $nota => $n): ?>
                <div class="barra-nota">
                    <span><?= $nota ?> ★</span>
                    <div><i style="width:<?= round($n / $maior * 100) ?>%"></i></div>
                    <span><?= $n ?></span>
                </div>
            <?php endforeach; ?>
        </section>
    </div>
    <div class="painel-caixa-topo">
        <p>Avaliações enviadas pela página Contato.</p>
        <div class="painel-ferramentas"><?php busca_tabela("lista-avaliacoes", "Buscar nas avaliações"); ?><?= botao_novo("avaliacoes") ?></div>
    </div>
    <div class="mensagens" id="lista-avaliacoes">
        <?php foreach ($avaliacoes as $a): ?>
            <article class="mensagem">
                <header>
                    <strong><?= h($a["nome"] ? $a["nome"] : "Visitante") ?></strong>
                    <span class="estrela"><?= estrelas($a["nota"]) ?></span>
                    <small><?= h($a["quando"]) ?></small>
                </header>
                <?php if ($a["comentario"]): ?><p><?= nl2br(h($a["comentario"])) ?></p><?php endif; ?>
                <?= acoes_registro("avaliacoes", $a["id"], "avaliação") ?>
            </article>
        <?php endforeach; ?>
    </div>
<?php
endif;

painel_fim();
