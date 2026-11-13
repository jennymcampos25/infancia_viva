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
if (!isset($PAINEL_MENU[$pagina]) && $pagina !== "busca") $pagina = "geral";

function contar($tabela) {
    global $pdo;
    $permitidas = array("pagamentos", "doacoes", "usuarios", "mensagens", "avaliacoes");
    if (!in_array($tabela, $permitidas)) return 0;
    return (int) $pdo->query("SELECT COUNT(*) FROM $tabela")->fetchColumn();
}

// "2 doações · 1 mensagem" (ou "—" se a pessoa não fez nada ainda)
function resumo_atividade($l) {
    $partes = array();
    foreach (array("n_doacoes" => array("doação", "doações"), "n_pagamentos" => array("pagamento", "pagamentos"),
                   "n_mensagens" => array("mensagem", "mensagens"), "n_avaliacoes" => array("avaliação", "avaliações")) as $k => $nomes) {
        $n = (int) $l[$k];
        if ($n) $partes[] = $n . " " . ($n === 1 ? $nomes[0] : $nomes[1]);
    }
    return $partes ? implode(" · ", $partes) : "—";
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

painel_inicio($pagina === "busca" ? "Pesquisa" : $PAINEL_MENU[$pagina][0], $pagina, $admin);

if ($aviso_limpeza) {
    echo '<p class="aviso ok">' . h($aviso_limpeza) . '</p>';
}

// =====================================================================
if ($pagina === "geral"):
    $total      = (float) $pdo->query("SELECT COALESCE(SUM(valor), 0) FROM pagamentos WHERE status IN ('Pago', 'Simulado', 'Confirmado')")->fetchColumn();
    $brinquedos = (int) $pdo->query("SELECT COALESCE(SUM(quantidade), 0) FROM doacoes")->fetchColumn();
    $media      = $pdo->query("SELECT AVG(nota) FROM avaliacoes")->fetchColumn();
    $doadores   = (int) $pdo->query("SELECT COUNT(*) FROM usuarios WHERE tipo <> 'admin'")->fetchColumn();
    $em_estoque = (int) $pdo->query("SELECT COALESCE(SUM(quantidade), 0) FROM estoque WHERE situacao = 'Em estoque'")->fetchColumn();
    $entregues  = (int) $pdo->query("SELECT COALESCE(SUM(quantidade), 0) FROM estoque WHERE situacao = 'Entregue'")->fetchColumn();

    // Atividade recente: as últimas coisas que aconteceram no site, de todas as tabelas
    $eventos = array();
    foreach ($pdo->query("SELECT nome, quantidade, brinquedo, criado_em FROM doacoes ORDER BY id DESC LIMIT 8") as $r) {
        $eventos[] = array($r["criado_em"], "doacao", $r["nome"], "doou " . (int) $r["quantidade"] . " × " . $r["brinquedo"]);
    }
    foreach ($pdo->query("SELECT nome, valor, metodo, criado_em FROM pagamentos WHERE status IN ('Pago', 'Simulado', 'Confirmado')
                          ORDER BY id DESC LIMIT 8") as $r) {
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
        <div class="stat"><strong><?= $brinquedos ?></strong>brinquedos prometidos</div>
        <div class="stat"><strong><?= $em_estoque ?></strong>no estoque (<?= $entregues ?> já entregues)</div>
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
        "SELECT u.id, u.nome, u.email, u.tipo, u.foto, u.email_confirmado, DATE_FORMAT(u.criado_em, '%d/%m/%Y') AS desde,
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
                <th>Nome</th><th>E-mail</th><th>Cadastro</th><th>Atividade</th><th>Ações</th>
            </tr>
            <?php foreach ($usuarios as $u): ?>
            <tr>
                <td><a class="pessoa" href="admin_usuario.php?id=<?= (int) $u["id"] ?>"><img src="<?= h(foto_url($u["id"], $u["foto"])) ?>" alt=""><?= h($u["nome"]) ?></a><?php if ($u["tipo"] === "admin"): ?> <span class="etiqueta et-admin">admin</span><?php endif; ?></td>
                <td><?= h($u["email"]) ?><?php if (!$u["email_confirmado"]): ?> <span class="etiqueta et-pendente">não confirmado</span><?php endif; ?></td>
                <td><?= h($u["desde"]) ?></td>
                <td><?= h(resumo_atividade($u)) ?></td>
                <td><?= acoes_registro("usuarios", $u["id"], "usuário") ?></td>
            </tr>
            <?php endforeach; ?>
            <tr>
                <td><a class="pessoa" href="admin_usuario.php?id=0"><img src="imagens/avatares/padrao.svg" alt=""><em>Visitantes sem conta</em></a></td>
                <td>—</td><td>—</td>
                <td><?= h(resumo_atividade($visitantes)) ?></td>
                <td></td>
            </tr>
        </table></div>
    </section>
<?php

// =====================================================================
elseif ($pagina === "locais"):
    $locais = $pdo->query("SELECT l.id, l.nome, l.tipo, l.endereco, l.coleta, l.ativo,
                                  (SELECT COUNT(*) FROM doacoes d WHERE d.ponto = l.nome) AS n_doacoes
                           FROM locais l ORDER BY l.ativo DESC, l.nome")->fetchAll();
    ?>
    <section class="painel-caixa">
        <div class="painel-caixa-topo">
            <p>Os locais ativos aparecem na página "Doe um brinquedo" e no formulário de doação.</p>
            <div class="painel-ferramentas"><?php busca_tabela("tabela-locais", "Buscar local"); ?><?= botao_novo("locais") ?></div>
        </div>
        <?php if (!$locais): ?>
            <p class="vazio">Nenhum local cadastrado. Sem locais, o formulário de doação não pode ser enviado.</p>
        <?php else: ?>
        <div class="tabela"><table id="tabela-locais">
            <tr><th>Local</th><th>Tipo</th><th>Endereço</th><th>Quando</th><th>Doações</th><th>Ações</th></tr>
            <?php foreach ($locais as $l): ?>
            <tr>
                <td><strong><?= h($l["nome"]) ?></strong><?php if (!$l["ativo"]): ?> <span class="etiqueta et-pendente">escondido</span><?php endif; ?></td>
                <td><?= h($l["tipo"]) ?></td>
                <td><?= h($l["endereco"]) ?></td>
                <td><?= h($l["coleta"]) ?></td>
                <td><?= (int) $l["n_doacoes"] ?></td>
                <td><?= acoes_registro("locais", $l["id"], "local") ?></td>
            </tr>
            <?php endforeach; ?>
        </table></div>
        <?php endif; ?>
    </section>
<?php

// =====================================================================
elseif ($pagina === "doacoes"):
    $doacoes = $pdo->query("SELECT id, nome, email, telefone, brinquedo, quantidade, ponto, valor, observacoes,
                                   DATE_FORMAT(data_doacao, '%d/%m/%Y') AS dia, $dh,
                                   (SELECT COUNT(*) FROM estoque e WHERE e.doacao_id = doacoes.id) AS no_estoque
                            FROM doacoes ORDER BY id DESC LIMIT 300")->fetchAll();
    foreach ($doacoes as &$d) {
        $d["valor"] = $d["valor"] === null ? "—" : dinheiro($d["valor"]);
        $d["estoque"] = $d["no_estoque"] ? "Recebida" : "Aguardando";
        if (!$d["no_estoque"]) {
            $d["_acao_extra"] = '<a class="acao acao-receber" href="admin_registro.php?tabela=estoque&amp;de=' . (int) $d["id"] . '">Receber no estoque</a> ';
        }
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
            <p>As 300 doações mais recentes. Quando o brinquedo chegar, clique em "Receber no estoque".</p>
            <div class="painel-ferramentas"><?php busca_tabela("tabela-doacoes", "Buscar por doador, brinquedo ou local"); ?><?= botao_novo("doacoes") ?></div>
        </div>
        <?php tabela(array(
            "Registrada em" => "quando", "Doador" => "nome", "Brinquedo" => "brinquedo", "Qtd" => "quantidade",
            "Entrega" => "dia", "Local" => "ponto", "Estoque" => "estoque"
        ), $doacoes, "tabela-doacoes", "doacoes"); ?>
    </section>
<?php

// =====================================================================
elseif ($pagina === "estoque"):
    // Totais por situação (somando a quantidade de cada registro)
    $totais = array("Em avaliação" => 0, "Em estoque" => 0, "Entregue" => 0, "Descartado" => 0);
    foreach ($pdo->query("SELECT situacao, SUM(quantidade) AS q FROM estoque GROUP BY situacao") as $r) {
        $totais[$r["situacao"]] = (int) $r["q"];
    }
    $por_faixa = $pdo->query("SELECT faixa_etaria, SUM(quantidade) AS q FROM estoque WHERE situacao = 'Em estoque'
                              GROUP BY faixa_etaria ORDER BY faixa_etaria")->fetchAll();

    $filtro = isset($_GET["situacao"]) && (isset($SITUACOES_ESTOQUE[$_GET["situacao"]]) || $_GET["situacao"] === "aguardando")
              ? $_GET["situacao"] : "";

    // Doações feitas pelo site que ainda não foram recebidas no estoque (a pessoa ainda vai levar o brinquedo)
    $aguardando = $pdo->query("SELECT id, nome, brinquedo, quantidade, ponto, DATE_FORMAT(data_doacao, '%d/%m/%Y') AS dia
                               FROM doacoes d WHERE NOT EXISTS (SELECT 1 FROM estoque e WHERE e.doacao_id = d.id)
                               ORDER BY data_doacao, id")->fetchAll();
    $qtd_aguardando = 0;
    foreach ($aguardando as $a) $qtd_aguardando += (int) $a["quantidade"];
    $sql = "SELECT id, brinquedo, categoria, quantidade, faixa_etaria, situacao, doador, instituicao,
                   DATE_FORMAT(recebido_em, '%d/%m/%Y') AS recebido, DATE_FORMAT(entregue_em, '%d/%m/%Y') AS entregue
            FROM estoque" . ($filtro && $filtro !== "aguardando" ? " WHERE situacao = ?" : "") . " ORDER BY FIELD(situacao, 'Em avaliação', 'Em estoque', 'Entregue', 'Descartado'), recebido_em DESC, id DESC";
    $st = $pdo->prepare($sql);
    $st->execute($filtro && $filtro !== "aguardando" ? array($filtro) : array());
    $itens = $filtro === "aguardando" ? array() : $st->fetchAll();
    ?>
    <div class="stats">
        <div class="stat"><strong><?= $qtd_aguardando ?></strong>aguardando chegada (<?= count($aguardando) ?> doações)</div>
        <div class="stat"><strong><?= $totais["Em estoque"] ?></strong>em estoque (prontos para entregar)</div>
        <div class="stat"><strong><?= $totais["Em avaliação"] ?></strong>em avaliação</div>
        <div class="stat"><strong><?= $totais["Entregue"] ?></strong>já entregues</div>
        <div class="stat"><strong><?= $totais["Descartado"] ?></strong>descartados</div>
    </div>
    <?php if ($por_faixa): ?>
        <p class="faixas">Em estoque por idade:
            <?php foreach ($por_faixa as $f): ?><span class="etiqueta"><?= h($f["faixa_etaria"]) ?>: <?= (int) $f["q"] ?></span> <?php endforeach; ?>
        </p>
    <?php endif; ?>
    <section class="painel-caixa">
        <div class="painel-caixa-topo">
            <nav class="filtros" aria-label="Filtrar por situação">
                <a href="admin.php?pagina=estoque"<?= $filtro === "" ? ' class="ativo"' : "" ?>>Todos</a>
                <a href="admin.php?pagina=estoque&amp;situacao=aguardando"<?= $filtro === "aguardando" ? ' class="ativo"' : "" ?>>Aguardando chegada</a>
                <?php foreach ($SITUACOES_ESTOQUE as $sit => $nome): ?>
                    <a href="admin.php?pagina=estoque&amp;situacao=<?= urlencode($sit) ?>"<?= $filtro === $sit ? ' class="ativo"' : "" ?>><?= h($sit) ?></a>
                <?php endforeach; ?>
            </nav>
            <div class="painel-ferramentas"><?php busca_tabela("tabela-estoque", "Buscar brinquedo, doador ou instituição"); ?><?= botao_novo("estoque") ?></div>
        </div>
        <?php if (($filtro === "" || $filtro === "aguardando") && $aguardando): ?>
            <h2 class="sub-titulo">Aguardando chegada <small>(<?= count($aguardando) ?>)</small></h2>
            <p class="dica">Doações registradas no site cujo brinquedo ainda não chegou. Quando chegar, clique em "Receber no estoque".</p>
            <?php foreach ($aguardando as &$a) {
                $a["_acao_extra"] = '<a class="acao acao-receber" href="admin_registro.php?tabela=estoque&amp;de=' . (int) $a["id"] . '">Receber no estoque</a> ';
            }
            unset($a);
            tabela(array("Entrega prevista" => "dia", "Doador" => "nome", "Brinquedo" => "brinquedo", "Qtd" => "quantidade", "Local" => "ponto"),
                   $aguardando, "tabela-aguardando", "doacoes"); ?>
            <?php if ($filtro === ""): ?><h2 class="sub-titulo">No estoque</h2><?php endif; ?>
        <?php endif; ?>
        <?php if ($filtro === "aguardando"): ?>
            <?php if (!$aguardando): ?><p class="vazio">Nenhuma doação aguardando chegada: todas já foram recebidas.</p><?php endif; ?>
        <?php elseif (!$itens): ?>
            <p class="vazio"><?= $filtro ? "Nenhum brinquedo com essa situação." : "Nenhum brinquedo registrado ainda. Use \"Receber no estoque\" na página Doações ou \"+ Registrar brinquedo\"." ?></p>
        <?php else: ?>
        <div class="tabela"><table id="tabela-estoque">
            <tr><th>Recebido</th><th>Brinquedo</th><th>Qtd</th><th>Idade</th><th>Situação</th><th>Destino</th><th>Ações</th></tr>
            <?php foreach ($itens as $i): ?>
            <tr>
                <td><?= h($i["recebido"]) ?></td>
                <td><strong><?= h($i["brinquedo"]) ?></strong><small class="sub"><?= h($i["categoria"]) ?><?= $i["doador"] ? " · doado por " . h($i["doador"]) : "" ?></small></td>
                <td><?= (int) $i["quantidade"] ?></td>
                <td><?= h($i["faixa_etaria"]) ?></td>
                <td><?= etiqueta_situacao($i["situacao"]) ?></td>
                <td><?= $i["situacao"] === "Entregue" ? h($i["instituicao"]) . '<small class="sub">em ' . h($i["entregue"]) . '</small>' : "—" ?></td>
                <td><?php if (in_array($i["situacao"], array("Em avaliação", "Em estoque"))): ?><a class="acao acao-receber" href="admin_registro.php?tabela=estoque&amp;id=<?= (int) $i["id"] ?>&amp;entregar=1">Entregar</a> <?php endif; ?><?= acoes_registro("estoque", $i["id"], "brinquedo do estoque") ?></td>
            </tr>
            <?php endforeach; ?>
        </table></div>
        <?php endif; ?>
    </section>
<?php

// =====================================================================
elseif ($pagina === "busca"):
    $q = isset($_GET["q"]) ? trim($_GET["q"]) : "";
    $cat = isset($_GET["cat"]) && array_key_exists($_GET["cat"], categorias_busca()) ? $_GET["cat"] : "";
    $like = "%" . str_replace(array("\\", "%", "_"), array("\\\\", "\\%", "\\_"), $q) . "%";

    // Cada categoria: [título, SQL (com um "?" para o texto), colunas da tabela]
    $buscas = array(
        "doacoes" => array("Doações de brinquedos",
            "SELECT id, nome, brinquedo, quantidade, ponto, DATE_FORMAT(data_doacao, '%d/%m/%Y') AS dia, $dh FROM doacoes
             WHERE CONCAT_WS(' ', nome, email, telefone, brinquedo, ponto, endereco, observacoes) LIKE ? ORDER BY id DESC LIMIT 100",
            array("Registrada em" => "quando", "Doador" => "nome", "Brinquedo" => "brinquedo", "Qtd" => "quantidade", "Entrega" => "dia", "Local" => "ponto")),
        "estoque" => array("Estoque de brinquedos",
            "SELECT id, brinquedo, categoria, quantidade, faixa_etaria, situacao, instituicao, DATE_FORMAT(recebido_em, '%d/%m/%Y') AS recebido FROM estoque
             WHERE CONCAT_WS(' ', brinquedo, categoria, faixa_etaria, situacao, doador, instituicao, observacoes) LIKE ? ORDER BY id DESC LIMIT 100",
            array("Recebido" => "recebido", "Brinquedo" => "brinquedo", "Categoria" => "categoria", "Qtd" => "quantidade", "Idade" => "faixa_etaria", "Situação" => "situacao", "Destino" => "instituicao")),
        "pagamentos" => array("Pagamentos",
            "SELECT id, nome, valor, metodo, detalhe, status, $dh FROM pagamentos
             WHERE CONCAT_WS(' ', nome, email, metodo, detalhe, status, CONCAT('IV-', LPAD(id, 6, '0'))) LIKE ? ORDER BY id DESC LIMIT 100",
            array("Quando" => "quando", "Pedido" => "pedido", "Nome" => "nome", "Valor" => "valor", "Forma" => "metodo", "Situação" => "status")),
        "mensagens" => array("Mensagens",
            "SELECT id, nome, email, mensagem, $dh FROM mensagens WHERE CONCAT_WS(' ', nome, email, mensagem) LIKE ? ORDER BY id DESC LIMIT 100",
            array("Quando" => "quando", "Nome" => "nome", "E-mail" => "email", "Mensagem" => "mensagem")),
        "avaliacoes" => array("Avaliações",
            "SELECT a.id, a.nota, a.comentario, DATE_FORMAT(a.criado_em, '%d/%m/%Y %H:%i') AS quando, COALESCE(u.nome, 'Visitante') AS quem
             FROM avaliacoes a LEFT JOIN usuarios u ON u.id = a.usuario_id
             WHERE CONCAT_WS(' ', a.comentario, u.nome, a.nota) LIKE ? ORDER BY a.id DESC LIMIT 100",
            array("Quando" => "quando", "Quem" => "quem", "Nota" => "nota", "Comentário" => "comentario")),
        "usuarios" => array("Usuários",
            "SELECT id, nome, email, tipo, DATE_FORMAT(criado_em, '%d/%m/%Y') AS desde FROM usuarios
             WHERE CONCAT_WS(' ', nome, email, tipo) LIKE ? ORDER BY nome LIMIT 100",
            array("Nome" => "nome", "E-mail" => "email", "Tipo" => "tipo", "Cadastro" => "desde")),
        "locais" => array("Locais de coleta",
            "SELECT id, nome, tipo, endereco, coleta FROM locais WHERE CONCAT_WS(' ', nome, tipo, endereco, coleta) LIKE ? ORDER BY nome LIMIT 100",
            array("Local" => "nome", "Tipo" => "tipo", "Endereço" => "endereco", "Quando" => "coleta")),
    );
    $mostrar = $cat ? array($cat => $buscas[$cat]) : $buscas;
    $nomes_cat = categorias_busca();
    ?>
    <p class="busca-resumo">
        <?php if ($q === "" && !$cat): ?>
            Escolha uma categoria ou digite algo na pesquisa lá em cima.
        <?php else: ?>
            <?= $q !== "" ? 'Resultados para <strong>"' . h($q) . '"</strong>' : "Tudo" ?> em
            <strong><?= h($nomes_cat[$cat]) ?></strong>.
        <?php endif; ?>
    </p>
    <?php if ($q !== "" || $cat):
        $achou = 0;
        foreach ($mostrar as $chave => $b):
            $st = $pdo->prepare($b[1]);
            $st->execute(array($like));
            $linhas = $st->fetchAll();
            if (!$linhas && !$cat) continue;     // em "Tudo", esconde as categorias sem resultado
            $achou += count($linhas);
            if ($chave === "pagamentos") {
                foreach ($linhas as &$l) { $l["valor"] = dinheiro($l["valor"]); $l["pedido"] = numero_pedido($l["id"]); $l["metodo"] = forma_pagamento($l); }
                unset($l);
            }
            if ($chave === "avaliacoes") {
                foreach ($linhas as &$l) { $l["nota"] = estrelas($l["nota"]); }
                unset($l);
            }
            ?>
            <section class="painel-caixa">
                <div class="painel-caixa-topo">
                    <h2><?= h($b[0]) ?> <small>(<?= count($linhas) ?><?= count($linhas) == 100 ? "+" : "" ?>)</small></h2>
                    <a class="acao" href="admin.php?pagina=<?= $chave ?>">Abrir a página</a>
                </div>
                <?php tabela($b[2], $linhas, "", $chave); ?>
            </section>
        <?php endforeach;
        if (!$achou && !$cat): ?>
            <p class="vazio">Nada encontrado. Tente outra palavra ou escolha uma categoria.</p>
        <?php endif;
    endif; ?>
<?php

// =====================================================================
elseif ($pagina === "pagamentos"):
    garantir_colunas_pagamento();
    $pagamentos = $pdo->query("SELECT id, nome, email, valor, metodo, detalhe, status, $dh FROM pagamentos ORDER BY id DESC LIMIT 300")->fetchAll();
    $total = 0;
    $n_pagos = 0;
    $n_aguardando = 0;
    foreach ($pagamentos as &$p) {
        if (in_array($p["status"], $SITUACOES_PAGO, true)) { $total += (float) $p["valor"]; $n_pagos++; }
        if ($p["status"] === "Aguardando") $n_aguardando++;
        $p["valor"]  = dinheiro($p["valor"]);
        $p["pedido"] = numero_pedido($p["id"]);
        $p["metodo"] = forma_pagamento($p);
    }
    unset($p);
    ?>
    <div class="stats">
        <div class="stat"><strong><?= dinheiro($total) ?></strong>arrecadado (simulado)</div>
        <div class="stat"><strong><?= $n_pagos ?></strong>pagos</div>
        <div class="stat"><strong><?= $n_aguardando ?></strong>aguardando pagamento</div>
        <div class="stat"><strong><?= count($pagamentos) ?></strong>pedidos no total</div>
    </div>
    <section class="painel-caixa">
        <div class="painel-caixa-topo">
            <p>O pagamento do site é uma demonstração: nenhum valor é cobrado de verdade. Só os pedidos pagos entram no total.</p>
            <div class="painel-ferramentas"><?php busca_tabela("tabela-pagamentos", "Buscar por nome, pedido ou forma"); ?><?= botao_novo("pagamentos") ?></div>
        </div>
        <?php tabela(array("Quando" => "quando", "Pedido" => "pedido", "Nome" => "nome", "Valor" => "valor",
                           "Forma" => "metodo", "Situação" => "status"),
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
