<?php
// Funções usadas por várias páginas: proteção de login, tabelas e histórico do usuário.
// Quem usa faz:  require "funcoes.php";  (ele já inclui o conexao.php)
require_once "conexao.php";

// ---- SESSÃO (login) ----
// As sessões ficam guardadas no banco (tabela "sessoes"), e não em arquivos dentro do servidor.
// Assim ninguém é deslogado quando o site é atualizado no Coolify (o servidor é recriado a cada atualização).
define("SESSAO_DIAS", 30);   // quanto tempo dura o "Manter conectado" (e uma sessão parada no banco)

class SessaoNoBanco implements SessionHandlerInterface {
    #[\ReturnTypeWillChange]
    public function open($caminho, $nome) { return true; }

    #[\ReturnTypeWillChange]
    public function close() { return true; }

    #[\ReturnTypeWillChange]
    public function read($id) {
        global $pdo;
        try {
            $st = $pdo->prepare("SELECT dados FROM sessoes WHERE id = ?");
            $st->execute(array($id));
        } catch (PDOException $e) {
            criar_tabela_sessoes();   // primeira vez: a tabela ainda não existe
            return "";
        }
        $dados = $st->fetchColumn();
        return $dados === false ? "" : $dados;
    }

    #[\ReturnTypeWillChange]
    public function write($id, $dados) {
        global $pdo;
        $st = $pdo->prepare("REPLACE INTO sessoes (id, dados, atualizado_em) VALUES (?, ?, ?)");
        return $st->execute(array($id, $dados, time()));
    }

    #[\ReturnTypeWillChange]
    public function destroy($id) {
        global $pdo;
        $pdo->prepare("DELETE FROM sessoes WHERE id = ?")->execute(array($id));
        return true;
    }

    #[\ReturnTypeWillChange]
    public function gc($duracao) {
        global $pdo;
        $pdo->prepare("DELETE FROM sessoes WHERE atualizado_em < ?")->execute(array(time() - $duracao));
        return true;
    }
}

function criar_tabela_sessoes() {
    global $pdo;
    $pdo->exec("CREATE TABLE IF NOT EXISTS sessoes (
                    id            VARCHAR(128) PRIMARY KEY,
                    dados         MEDIUMBLOB NOT NULL,
                    atualizado_em INT NOT NULL,
                    INDEX (atualizado_em)
                ) ENGINE=InnoDB");
}

function site_em_https() {
    return (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off")
        || (isset($_SERVER["HTTP_X_FORWARDED_PROTO"]) && $_SERVER["HTTP_X_FORWARDED_PROTO"] === "https");
}

// Envia o cookie da sessão. $manter = true: o cookie vale 30 dias (continua logado depois de fechar o navegador);
// false: o cookie some quando o navegador fecha.
function cookie_sessao($manter) {
    $validade = $manter ? time() + SESSAO_DIAS * 86400 : 0;
    if (PHP_VERSION_ID >= 70300) {
        setcookie(session_name(), session_id(), array("expires" => $validade, "path" => "/",
                  "secure" => site_em_https(), "httponly" => true, "samesite" => "Lax"));
    } else {
        setcookie(session_name(), session_id(), $validade, "/", "", site_em_https(), true);
    }
}

// Use no lugar de session_start() em todas as páginas PHP
function iniciar_sessao() {
    if (session_id() !== "") return;
    ini_set("session.gc_maxlifetime", SESSAO_DIAS * 86400);   // o padrão do PHP apagava a sessão após 24 min parada
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params(array("lifetime" => 0, "path" => "/", "secure" => site_em_https(),
                                        "httponly" => true, "samesite" => "Lax"));
    } else {
        session_set_cookie_params(0, "/", "", site_em_https(), true);
    }
    session_set_save_handler(new SessaoNoBanco(), true);
    session_start();
    // "Manter conectado": cada visita renova o prazo de 30 dias
    if (!empty($_SESSION["manter"])) cookie_sessao(true);
}

// ---- E-MAIL ----
// Confere se o e-mail pode existir: formato certo, domínio que recebe e-mails e não descartável.
// Não garante que a caixa de e-mail existe (para isso seria preciso mandar um e-mail de confirmação).
function email_real($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;
    $dominio = strtolower(substr(strrchr($email, "@"), 1));

    $bloqueados = array(
        "example.com", "example.org", "example.net", "exemplo.com", "teste.com", "test.com",
        "mailinator.com", "yopmail.com", "guerrillamail.com", "10minutemail.com", "temp-mail.org",
        "tempmail.com", "trashmail.com", "sharklasers.com", "getnada.com", "dispostable.com",
        "maildrop.cc", "fakeinbox.com", "throwawaymail.com", "emailondeck.com"
    );
    if (in_array($dominio, $bloqueados, true)) return false;
    if (preg_match('/\.(test|example|invalid|localhost|local)$/', $dominio)) return false;

    if (dominio_recebe_email($dominio)) return true;
    // Se o servidor não consegue consultar a internet (DNS), não bloqueia ninguém por engano
    return !dominio_recebe_email("gmail.com");
}

function dominio_recebe_email($dominio) {
    if (!function_exists("getmxrr")) return true;
    if (@getmxrr($dominio, $servidores)) {
        foreach ($servidores as $s) {
            if ($s !== "" && $s !== ".") return true;   // "." = domínio que avisa que não recebe e-mails
        }
        return false;
    }
    return @checkdnsrr($dominio, "A");
}

// ---- ENVIO DE E-MAIL (confirmação de conta) ----
// Os dados do e-mail da ONG ficam nas variáveis do Coolify (SMTP_HOST, SMTP_PORTA, SMTP_USUARIO,
// SMTP_SENHA, SMTP_NOME e SITE_URL), nunca no código: o repositório no GitHub é público.

function email_configurado() {
    return getenv("SMTP_HOST") && getenv("SMTP_USUARIO") && getenv("SMTP_SENHA");
}

// Endereço do site, para montar links nos e-mails (ex.: https://infanciaviva.artgenial.com.br)
function endereco_site() {
    if (getenv("SITE_URL")) return rtrim(getenv("SITE_URL"), "/");
    $pasta = rtrim(str_replace("\\", "/", dirname($_SERVER["SCRIPT_NAME"])), "/");
    return (site_em_https() ? "https://" : "http://") . $_SERVER["HTTP_HOST"] . $pasta;
}

// Envia um e-mail em HTML. Devolve true se o servidor de e-mail aceitou.
function enviar_email($para, $nome, $assunto, $html, $texto) {
    if (!email_configurado()) return false;
    require_once __DIR__ . "/lib/PHPMailer/Exception.php";
    require_once __DIR__ . "/lib/PHPMailer/PHPMailer.php";
    require_once __DIR__ . "/lib/PHPMailer/SMTP.php";

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $porta = getenv("SMTP_PORTA") ? (int) getenv("SMTP_PORTA") : 587;
        $mail->isSMTP();
        $mail->Host     = getenv("SMTP_HOST");
        $mail->Port     = $porta;
        $mail->SMTPAuth = true;
        $mail->Username = getenv("SMTP_USUARIO");
        $mail->Password = getenv("SMTP_SENHA");
        // Porta 465 usa SSL direto; as outras (587) começam sem e trocam para TLS (STARTTLS)
        $seguranca = getenv("SMTP_SEGURANCA") ? getenv("SMTP_SEGURANCA") : ($porta === 465 ? "ssl" : "tls");
        if ($seguranca === "nenhuma") {        // só para testes com servidor de e-mail local
            $mail->SMTPSecure = "";
            $mail->SMTPAutoTLS = false;
        } else {
            $mail->SMTPSecure = $seguranca === "ssl" ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
                                                     : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        }
        $mail->Timeout  = 15;
        $mail->getSMTPInstance()->Timelimit = 20;   // nunca deixa a página esperando mais que alguns segundos
        $mail->CharSet  = "UTF-8";
        $mail->setFrom(getenv("SMTP_USUARIO"), getenv("SMTP_NOME") ? getenv("SMTP_NOME") : "Infância Viva");
        $mail->addAddress($para, $nome);
        $mail->isHTML(true);
        $mail->Subject = $assunto;
        $mail->Body    = $html;
        $mail->AltBody = $texto;
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Falha ao enviar e-mail: " . $mail->ErrorInfo);
        return false;
    }
}

// ---- CONFIRMAÇÃO DE E-MAIL DA CONTA ----
// Colunas em usuarios: email_confirmado (1 = confirmado; contas antigas e criadas pelo admin já valem 1),
// token_confirmacao (guardado como hash, o link leva o código original) e token_expira.

// Cria as colunas na primeira vez (banco antigo), sem precisar rodar o atualizar_banco.php
function garantir_colunas_confirmacao() {
    global $pdo;
    static $feito = false;
    if ($feito) return;
    if (!$pdo->query("SHOW COLUMNS FROM usuarios LIKE 'email_confirmado'")->fetch()) {
        $pdo->exec("ALTER TABLE usuarios
                    ADD COLUMN email_confirmado  TINYINT(1) NOT NULL DEFAULT 1 AFTER foto_dados,
                    ADD COLUMN token_confirmacao CHAR(64) NULL AFTER email_confirmado,
                    ADD COLUMN token_expira      DATETIME NULL AFTER token_confirmacao");
    }
    $feito = true;
}

function codigo_aleatorio() {
    if (function_exists("random_bytes")) return bin2hex(random_bytes(32));
    return bin2hex(openssl_random_pseudo_bytes(32));
}

// Gera um link novo (válido por 24 h) e envia o e-mail. Devolve true se o e-mail foi enviado.
function enviar_confirmacao($usuario_id, $nome, $email) {
    global $pdo;
    $codigo = codigo_aleatorio();
    $st = $pdo->prepare("UPDATE usuarios SET token_confirmacao = ?, token_expira = DATE_ADD(NOW(), INTERVAL 24 HOUR)
                         WHERE id = ?");
    $st->execute(array(hash("sha256", $codigo), $usuario_id));

    $link = endereco_site() . "/confirmar.php?codigo=" . $codigo;
    $primeiro = h(strtok($nome, " "));
    $html = '<div style="font-family:Arial,sans-serif;max-width:520px;margin:auto;color:#3a4248">'
          . '<h2 style="color:#242d32">Olá, ' . $primeiro . '!</h2>'
          . '<p>Falta só um passo para ativar sua conta na <strong>Infância Viva</strong>: confirme que este e-mail é seu.</p>'
          . '<p style="text-align:center;margin:28px 0"><a href="' . h($link) . '" style="background:#e8572a;color:#fff;'
          . 'padding:14px 28px;border-radius:99px;text-decoration:none;font-weight:bold">Confirmar meu e-mail</a></p>'
          . '<p style="font-size:13px;color:#6b7378">O link vale por 24 horas. Se o botão não funcionar, copie este endereço no navegador:<br>'
          . h($link) . '</p>'
          . '<p style="font-size:13px;color:#6b7378">Se você não criou uma conta, é só ignorar este e-mail.</p></div>';
    $texto = "Olá, " . strtok($nome, " ") . "!\n\nConfirme seu e-mail para ativar sua conta na Infância Viva:\n"
           . $link . "\n\nO link vale por 24 horas. Se você não criou uma conta, ignore este e-mail.";
    return enviar_email($email, $nome, "Confirme seu e-mail - Infância Viva", $html, $texto);
}

// h() protege a tela contra HTML/JS digitado nos formulários
function h($v) { return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8"); }

function dinheiro($v) { return "R$ " . number_format((float) $v, 2, ",", "."); }

// ---- PROTEÇÃO ----

// Só deixa passar quem está logado. Devolve os dados do usuário (id, nome, email, tipo).
function exigir_login() {
    global $pdo;
    iniciar_sessao();

    if (!isset($_SESSION["usuario_id"])) {
        header("Location: login.html");
        exit;
    }
    $campos = "id, nome, email, tipo, DATE_FORMAT(criado_em, '%d/%m/%Y') AS desde";
    try {
        $st = $pdo->prepare("SELECT $campos, foto FROM usuarios WHERE id = ?");
        $st->execute(array($_SESSION["usuario_id"]));
    } catch (PDOException $e) {
        // Banco ainda sem a coluna "foto": funciona sem ela até rodar o atualizar_banco.php
        $st = $pdo->prepare("SELECT $campos, NULL AS foto FROM usuarios WHERE id = ?");
        $st->execute(array($_SESSION["usuario_id"]));
    }
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
    iniciar_sessao();
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
    garantir_colunas_confirmacao();
    $u = exigir_login();
    if ($u["tipo"] !== "admin") {
        header("Location: index.html");
        exit;
    }
    return $u;
}

// ---- FOTO DE PERFIL ----
// Coluna usuarios.foto: NULL = sem foto, "avatar1" a "avatar6" = foto pronta,
// "upload:123456" = foto enviada pela pessoa (guardada em usuarios.foto_dados; o número muda a cada troca).

$AVATARES = array("avatar1", "avatar2", "avatar3", "avatar4", "avatar5", "avatar6");

// Endereço da imagem para usar no <img src="...">
function foto_url($id, $foto) {
    global $AVATARES;
    if (in_array($foto, $AVATARES, true)) return "imagens/avatares/$foto.svg?v=2";   // ?v= muda quando as cores mudam
    if (strpos((string) $foto, "upload:") === 0) return "foto.php?id=" . (int) $id . "&v=" . substr($foto, 7);
    return "imagens/avatares/padrao.svg";
}

// Lê a escolha do formulário (campos foto_padrao e foto_upload).
// Devolve null se a pessoa não escolheu nada, false se a imagem é inválida,
// ou array(valor_da_coluna_foto, bytes_da_imagem_ou_null).
function ler_foto_do_formulario() {
    global $AVATARES;
    $padrao = isset($_POST["foto_padrao"]) ? $_POST["foto_padrao"] : "";
    $upload = isset($_POST["foto_upload"]) ? $_POST["foto_upload"] : "";

    if ($upload !== "") {
        // O navegador já reduz a foto para 256x256 antes de enviar (script.js)
        if (!preg_match('#^data:image/(jpeg|png);base64,([A-Za-z0-9+/=]+)$#', $upload, $m)) return false;
        $bytes = base64_decode($m[2], true);
        if ($bytes === false || strlen($bytes) > 300000) return false;
        $info = @getimagesizefromstring($bytes);
        if (!$info || !in_array($info["mime"], array("image/jpeg", "image/png"), true)
            || $info[0] > 1024 || $info[1] > 1024) return false;
        return array("upload:" . time(), $bytes);
    }
    if (in_array($padrao, $AVATARES, true)) return array($padrao, null);
    if ($padrao === "nenhuma") return array(null, null);
    return null;
}

function salvar_foto($usuario_id, $escolha) {
    global $pdo;
    $st = $pdo->prepare("UPDATE usuarios SET foto = ?, foto_dados = ? WHERE id = ?");
    $st->bindValue(1, $escolha[0]);
    $st->bindValue(2, $escolha[1], $escolha[1] === null ? PDO::PARAM_NULL : PDO::PARAM_LOB);
    $st->bindValue(3, (int) $usuario_id, PDO::PARAM_INT);
    $st->execute();
}

// ---- LAYOUT DO PAINEL ADMINISTRATIVO ----
// O admin tem uma "cara" de sistema, diferente do site: menu lateral escuro e sem o topo/rodapé públicos.

// Links do menu lateral: pagina => array(texto, ícone)
$PAINEL_MENU = array(
    "geral"      => array("Visão geral", '<path d="M3 13h8V3H3zM13 21h8V11h-8zM13 3v6h8V3zM3 21h8v-6H3z"/>'),
    "usuarios"   => array("Usuários", '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>'),
    "doacoes"    => array("Doações", '<rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7M7.5 8a2.5 2.5 0 0 1 0-5C10 3 12 8 12 8s2-5 4.5-5a2.5 2.5 0 0 1 0 5"/>'),
    "pagamentos" => array("Pagamentos", '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>'),
    "mensagens"  => array("Mensagens", '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>'),
    "avaliacoes" => array("Avaliações", '<path d="M12 2l2.9 6.1 6.6.8-4.9 4.6 1.3 6.5L12 16.8 6.1 20l1.3-6.5L2.5 8.9l6.6-.8z"/>'),
);

function icone_painel($desenho) {
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" '
         . 'stroke-linejoin="round" aria-hidden="true">' . $desenho . '</svg>';
}

// Começo da página do painel. $ativo = chave do $PAINEL_MENU destacada; $admin = dados de exigir_admin()
function painel_inicio($titulo, $ativo, $admin) {
    global $PAINEL_MENU;
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($titulo) ?> - Painel - Infância Viva</title>
    <link rel="stylesheet" href="estilo.css?v=6">
    <link rel="icon" href="favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Quicksand:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap">
</head>
<body class="painel">
<aside class="painel-lateral">
    <a class="painel-marca" href="admin.php">
        <svg viewBox="0 0 64 64" aria-hidden="true"><path d="M32 4 52 21H12z" fill="#2f7a80"/><rect x="21" y="19" width="22" height="7" fill="#2f7a80"/><path fill="#f5a524" fill-rule="evenodd" d="M13 26h38a4 4 0 0 1 4 4v26a4 4 0 0 1-4 4H13a4 4 0 0 1-4-4V30a4 4 0 0 1 4-4zM25 60V46a7 7 0 0 1 14 0v14z"/></svg>
        <span>Infância Viva<small>Painel administrativo</small></span>
    </a>
    <nav class="painel-menu">
        <?php foreach ($PAINEL_MENU as $pagina => $item): ?>
            <a href="admin.php?pagina=<?= $pagina ?>"<?= $pagina === $ativo ? ' class="ativo"' : '' ?>><?= icone_painel($item[1]) ?><?= h($item[0]) ?></a>
        <?php endforeach; ?>
    </nav>
    <div class="painel-saidas">
        <a href="index.html"><?= icone_painel('<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6M15 3h6v6M10 14 21 3"/>') ?>Ver o site</a>
        <a href="logout.php"><?= icone_painel('<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>') ?>Sair</a>
    </div>
</aside>
<div class="painel-conteudo">
    <header class="painel-topo">
        <h1><?= h($titulo) ?></h1>
        <div class="painel-quem">
            <img src="<?= h(foto_url($admin["id"], $admin["foto"])) ?>" alt="">
            <span><?= h($admin["nome"]) ?><small>Administrador</small></span>
        </div>
    </header>
    <main class="painel-principal">
    <?php
}

function painel_fim() {
    ?>
    </main>
</div>
<script src="script.js?v=8"></script>
</body>
</html>
    <?php
}

// Caixa de busca que filtra as linhas de uma tabela (o script.js faz o filtro)
function busca_tabela($id_tabela, $texto) {
    echo '<input type="search" class="painel-busca" data-filtrar="' . h($id_tabela) . '" placeholder="' . h($texto) . '">';
}

// ---- INCLUIR, ALTERAR E APAGAR REGISTROS (painel do admin) ----
// Cada tabela que o admin pode editar e os campos do formulário.
// Tipos: texto, email, numero, data, dinheiro, lista, textarea, senha, conta (usuário dono do registro)
function registros_config() {
    $pontos = array("Sede da Infância Viva" => "Sede da Infância Viva", "Escola parceira" => "Escola parceira",
                    "Centro comunitário" => "Centro comunitário");
    return array(
        "doacoes" => array("nome" => "doação", "novo" => "Nova doação", "campos" => array(
            "usuario_id"  => array("Conta do doador", "conta", false),
            "nome"        => array("Nome do doador", "texto", true),
            "email"       => array("E-mail", "email", true),
            "telefone"    => array("Telefone", "texto", true),
            "brinquedo"   => array("Brinquedo", "texto", true),
            "quantidade"  => array("Quantidade", "numero", true),
            "data_doacao" => array("Data da entrega", "data", true),
            "endereco"    => array("Endereço", "texto", true),
            "ponto"       => array("Local de entrega", "lista", true, $pontos),
            "valor"       => array("Valor em dinheiro (R$)", "dinheiro", false),
            "observacoes" => array("Observações", "textarea", false),
        )),
        "pagamentos" => array("nome" => "pagamento", "novo" => "Novo pagamento", "campos" => array(
            "usuario_id" => array("Conta de quem pagou", "conta", false),
            "nome"       => array("Nome", "texto", true),
            "valor"      => array("Valor (R$)", "dinheiro", true),
            "metodo"     => array("Forma", "lista", true, array("Pix" => "Pix", "Cartão" => "Cartão")),
            "status"     => array("Situação", "lista", true, array("Simulado" => "Simulado", "Confirmado" => "Confirmado", "Cancelado" => "Cancelado")),
        )),
        "mensagens" => array("nome" => "mensagem", "novo" => "Nova mensagem", "campos" => array(
            "usuario_id" => array("Conta de quem enviou", "conta", false),
            "nome"       => array("Nome", "texto", true),
            "email"      => array("E-mail", "email", true),
            "mensagem"   => array("Mensagem", "textarea", true),
        )),
        "avaliacoes" => array("nome" => "avaliação", "novo" => "Nova avaliação", "campos" => array(
            "usuario_id" => array("Conta de quem avaliou", "conta", false),
            "nota"       => array("Nota", "lista", false, array("1" => "★ 1", "2" => "★★ 2", "3" => "★★★ 3", "4" => "★★★★ 4", "5" => "★★★★★ 5")),
            "comentario" => array("Comentário", "textarea", false),
        )),
        "usuarios" => array("nome" => "usuário", "novo" => "Novo usuário", "campos" => array(
            "nome"  => array("Nome", "texto", true),
            "email" => array("E-mail", "email", true),
            "tipo"  => array("Tipo de conta", "lista", true, array("doador" => "Doador", "admin" => "Administrador")),
            "email_confirmado" => array("E-mail confirmado?", "lista", true, array("1" => "Sim", "0" => "Não, ainda não confirmou")),
            "senha" => array("Senha", "senha", false),
        )),
    );
}

// Chave da sessão que os formulários do painel mandam junto (impede que outro site apague coisas em nome do admin)
function chave_admin() {
    if (empty($_SESSION["chave_admin"])) $_SESSION["chave_admin"] = sha1(uniqid(mt_rand(), true));
    return $_SESSION["chave_admin"];
}

// Botões "Editar" e "Apagar" de um registro
function acoes_registro($tabela, $id, $nome) {
    return '<span class="acoes">'
         . '<a class="acao" href="admin_registro.php?tabela=' . h($tabela) . '&amp;id=' . (int) $id . '">Editar</a>'
         . '<form action="admin_salvar.php" method="post" onsubmit="return confirm(\'Apagar este registro de ' . h($nome) . '? Não dá para desfazer.\')">'
         . '<input type="hidden" name="acao" value="apagar"><input type="hidden" name="tabela" value="' . h($tabela) . '">'
         . '<input type="hidden" name="id" value="' . (int) $id . '"><input type="hidden" name="chave" value="' . h(chave_admin()) . '">'
         . '<button type="submit" class="acao perigo">Apagar</button></form></span>';
}

function botao_novo($tabela) {
    $cfg = registros_config();
    return '<a class="botao botao-novo" href="admin_registro.php?tabela=' . h($tabela) . '">+ ' . h($cfg[$tabela]["novo"]) . '</a>';
}

// ---- TABELAS ----

// $colunas = array("Título da coluna" => "campo_do_banco")
// $acoes = nome da tabela no banco para mostrar "Editar" e "Apagar" em cada linha (as linhas precisam ter "id")
function tabela($colunas, $linhas, $id = "", $acoes = "") {
    if (!$linhas) {
        echo '<p class="vazio">Nenhum registro ainda.</p>';
        return;
    }
    if ($acoes) $cfg = registros_config();
    echo '<div class="tabela"><table' . ($id ? ' id="' . h($id) . '"' : '') . '><tr>';
    foreach ($colunas as $titulo => $campo) {
        echo "<th>" . h($titulo) . "</th>";
    }
    if ($acoes) echo "<th>Ações</th>";
    echo "</tr>";
    foreach ($linhas as $l) {
        echo "<tr>";
        foreach ($colunas as $campo) {
            echo "<td>" . h(isset($l[$campo]) ? $l[$campo] : "") . "</td>";
        }
        if ($acoes) echo "<td>" . acoes_registro($acoes, $l["id"], $cfg[$acoes]["nome"]) . "</td>";
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
