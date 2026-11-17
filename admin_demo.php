<?php
// Ferramentas de teste do painel: GERA ou APAGA dados de demonstração (pessoas, doações, estoque,
// pagamentos, mensagens e avaliações), para o site não parecer vazio na apresentação.
//
// Tudo é fictício: nomes inventados e e-mails no domínio example.com, que é reservado para
// exemplos e não pertence a ninguém (assim o site nunca manda e-mail para uma pessoa real).
// É por esse domínio que o botão "Apagar dados de demonstração" encontra o que apagar.
require "funcoes.php";
iniciar_sessao();
exigir_admin();

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST["chave"]) || $_POST["chave"] !== chave_admin()) {
    header("Location: admin.php");
    exit;
}

define("DOMINIO_DEMO", "@example.com");
class AvisoDemo extends Exception {}
$acao = isset($_POST["acao"]) ? $_POST["acao"] : "";

function sorteia($lista) { return $lista[mt_rand(0, count($lista) - 1)]; }
function chance($porcento) { return mt_rand(1, 100) <= $porcento; }
// Data e hora de N dias atrás (com hora do dia sorteada entre 8h e 21h)
function dias_atras($dias) {
    return date("Y-m-d", strtotime("-$dias days")) . sprintf(" %02d:%02d:%02d", mt_rand(8, 21), mt_rand(0, 59), mt_rand(0, 59));
}
function sem_acento($t) {
    $t = strtr($t, array("á"=>"a","à"=>"a","â"=>"a","ã"=>"a","é"=>"e","ê"=>"e","í"=>"i","ó"=>"o","ô"=>"o","õ"=>"o","ú"=>"u","ç"=>"c"));
    return preg_replace('/[^a-z]/', "", mb_strtolower($t, "UTF-8"));
}

// Apaga tudo o que é de demonstração (e só isso)
function apagar_demo() {
    global $pdo;
    $d = "%" . DOMINIO_DEMO;
    $n = 0;
    $st = $pdo->prepare("DELETE FROM estoque WHERE doacao_id IN (SELECT id FROM doacoes WHERE email LIKE ?)");
    $st->execute(array($d)); $n += $st->rowCount();
    $st = $pdo->prepare("DELETE FROM avaliacoes WHERE usuario_id IN (SELECT id FROM usuarios WHERE email LIKE ?)");
    $st->execute(array($d)); $n += $st->rowCount();
    foreach (array("doacoes", "pagamentos", "mensagens") as $t) {
        $st = $pdo->prepare("DELETE FROM $t WHERE email LIKE ?");
        $st->execute(array($d)); $n += $st->rowCount();
    }
    $st = $pdo->prepare("DELETE FROM usuarios WHERE email LIKE ? AND tipo <> 'admin'");
    $st->execute(array($d));
    return array($st->rowCount(), $n);
}

try {
    $pdo->beginTransaction();

    if ($acao === "apagar_demo") {
        $r = apagar_demo();
        $aviso = "Dados de demonstração apagados: $r[0] pessoa(s) e $r[1] registro(s). Os registros reais não foram mexidos.";

    } elseif ($acao === "gerar_demo") {
        $st = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email LIKE ?");
        $st->execute(array("%" . DOMINIO_DEMO));
        if ($st->fetchColumn() > 0) {
            throw new AvisoDemo("Os dados de demonstração já existem. Apague antes de gerar de novo.");
        }

        // ---------- listas usadas para inventar os registros ----------
        $nomes = array("Ana", "Beatriz", "Camila", "Daniela", "Eduarda", "Fernanda", "Gabriela", "Helena", "Isabela", "Juliana",
            "Larissa", "Mariana", "Natália", "Patrícia", "Renata", "Sofia", "Tatiane", "Vanessa", "Aline", "Cláudia",
            "André", "Bruno", "Carlos", "Diego", "Eduardo", "Felipe", "Gustavo", "Henrique", "Igor", "João",
            "Lucas", "Marcelo", "Nicolas", "Otávio", "Pedro", "Rafael", "Samuel", "Thiago", "Vinícius", "Wellington");
        $sobrenomes = array("Silva", "Santos", "Oliveira", "Souza", "Rodrigues", "Ferreira", "Alves", "Pereira", "Lima", "Gomes",
            "Costa", "Ribeiro", "Martins", "Carvalho", "Almeida", "Lopes", "Soares", "Fernandes", "Vieira", "Barbosa",
            "Rocha", "Dias", "Nascimento", "Andrade", "Moreira", "Nunes", "Marques", "Machado", "Mendes", "Freitas");
        // brinquedo, categoria do estoque, faixa etária
        $brinquedos = array(
            array("Boneca de pano", "Boneca", "3 a 5 anos"), array("Boneca com acessórios", "Boneca", "6 a 8 anos"),
            array("Carrinho de madeira", "Carrinho", "3 a 5 anos"), array("Carrinho de controle remoto", "Carrinho", "6 a 8 anos"),
            array("Caminhão de brinquedo", "Carrinho", "3 a 5 anos"), array("Jogo da memória", "Jogo educativo", "3 a 5 anos"),
            array("Dominó", "Jogo educativo", "6 a 8 anos"), array("Jogo de tabuleiro", "Jogo educativo", "9 a 12 anos"),
            array("Livro de histórias infantis", "Livro infantil", "3 a 5 anos"), array("Livro de contos", "Livro infantil", "9 a 12 anos"),
            array("Gibi", "Livro infantil", "6 a 8 anos"), array("Bola de futebol", "Bola", "6 a 8 anos"),
            array("Bola de borracha", "Bola", "0 a 2 anos"), array("Blocos de montar", "Blocos de montar", "3 a 5 anos"),
            array("Peças de montar coloridas", "Blocos de montar", "6 a 8 anos"), array("Urso de pelúcia", "Pelúcia", "0 a 2 anos"),
            array("Pelúcia de cachorro", "Pelúcia", "0 a 2 anos"), array("Quebra-cabeça de 60 peças", "Quebra-cabeça", "6 a 8 anos"),
            array("Quebra-cabeça de 200 peças", "Quebra-cabeça", "9 a 12 anos"), array("Chocalho", "Outro", "0 a 2 anos"),
            array("Corda de pular", "Outro", "6 a 8 anos"), array("Massinha de modelar", "Outro", "3 a 5 anos"),
            array("Kit de lápis de cor e livro de colorir", "Outro", "Todas as idades"), array("Pião", "Outro", "6 a 8 anos"));
        $observacoes = array("Está na caixa original.", "Lavado e embalado.", "Usado, mas em ótimo estado.", "Novo, ganhei repetido.",
            "São do meu filho, que já cresceu.", "Vou levar junto com as pilhas.");
        $mensagens = array(
            "Olá! Gostaria de saber se vocês aceitam brinquedos usados em bom estado.",
            "Minha escola quer fazer uma campanha de arrecadação. Como podemos ser parceiros?",
            "Posso ajudar como voluntária nas entregas do Dia das Crianças?",
            "Parabéns pelo projeto! Vocês também recebem livros infantis?",
            "Tenho uma loja no bairro e gostaria de ser ponto de coleta.",
            "Doei pelo site e queria saber quando o brinquedo será entregue.",
            "Vocês aceitam bicicletas infantis?",
            "Gostaria de levar meus filhos para ajudar na separação dos brinquedos. É possível?",
            "A empresa onde trabalho quer apoiar a campanha de Natal. Com quem posso falar?",
            "Que iniciativa linda! Já compartilhei com meus amigos.",
            "Fiz uma doação em dinheiro e queria confirmar se chegou.",
            "Vocês recebem brinquedos em outros bairros além de Campo Grande?",
            "Sou professora e quero levar a ideia para a minha turma.",
            "Como funciona a avaliação dos brinquedos antes da entrega?");
        $comentarios = array("Muito fácil de doar pelo site!", "Projeto lindo, parabéns a todos.", "Adorei acompanhar a entrega da minha doação.",
            "Gostei de ver para onde o brinquedo foi.", "Site bonito e simples de usar.", "Uma causa muito importante.",
            "Doei com meus filhos e eles amaram participar.", "Recebi o recibo na hora, muito organizado.",
            "Poderia ter mais pontos de coleta perto de mim.", "Ótima iniciativa para o Dia das Crianças.",
            "Faria de novo com certeza!", "Atendimento rápido pelo contato.");
        $bandeiras = array("Visa", "Visa", "Mastercard", "Mastercard", "Elo");
        $valores = array(10, 15, 20, 20, 25, 30, 30, 50, 50, 50, 75, 100, 100, 150, 200);

        // Locais de coleta ativos e instituições (centros de acolhimento) cadastrados pelo admin
        $locais = $pdo->query("SELECT nome FROM locais WHERE ativo = 1")->fetchAll(PDO::FETCH_COLUMN);
        if (!$locais) $locais = array("Sede da Infância Viva");
        $instituicoes = $pdo->query("SELECT nome FROM locais WHERE tipo = 'Centro de acolhimento'")->fetchAll(PDO::FETCH_COLUMN);
        if (!$instituicoes) $instituicoes = array("Instituição de acolhimento parceira");

        // Telefone de mentira, sempre com o prefixo 90000 (para não ligar para ninguém por engano)
        $telefone = function () { return sprintf("(21) 90000-%04d", mt_rand(0, 9999)); };
        $usados = array();
        $nova_pessoa = function () use ($nomes, $sobrenomes, &$usados) {
            do {
                $nome = sorteia($nomes) . " " . sorteia($sobrenomes) . (chance(40) ? " " . sorteia($sobrenomes) : "");
                $partes = explode(" ", $nome);
                $email = sem_acento($partes[0]) . "." . sem_acento(end($partes)) . (chance(50) ? mt_rand(1, 99) : "") . DOMINIO_DEMO;
            } while (isset($usados[$email]));
            $usados[$email] = true;
            return array($nome, $email);
        };

        // ---------- pessoas com conta (40) ----------
        $contas = array();   // id => [nome, email, dias desde o cadastro]
        $st = $pdo->prepare("INSERT INTO usuarios (nome, email, senha_hash, tipo, foto, email_confirmado, criado_em)
                             VALUES (?, ?, ?, 'doador', ?, 1, ?)");
        for ($i = 0; $i < 40; $i++) {
            list($nome, $email) = $nova_pessoa();
            $dias = mt_rand(5, 150);
            // senha sorteada e descartada: ninguém consegue entrar nessas contas
            $st->execute(array($nome, $email, password_hash(bin2hex(random_bytes(12)), PASSWORD_DEFAULT),
                               chance(55) ? "avatar" . mt_rand(1, 6) : null, dias_atras($dias)));
            $contas[(int) $pdo->lastInsertId()] = array($nome, $email, $dias);
        }
        $ids = array_keys($contas);
        $quem = function ($porcento_conta) use ($ids, $contas, $nova_pessoa) {
            if (chance($porcento_conta)) {
                $id = sorteia($ids);
                return array($id, $contas[$id][0], $contas[$id][1], $contas[$id][2]);
            }
            list($nome, $email) = $nova_pessoa();
            return array(null, $nome, $email, 150);
        };

        // ---------- doações de brinquedos (75) e o estoque de cada uma ----------
        $ins_doacao = $pdo->prepare("INSERT INTO doacoes (usuario_id, nome, email, telefone, brinquedo, quantidade, data_doacao,
                                                          endereco, observacoes, ponto, criado_em)
                                     VALUES (?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, ?)");
        $ins_estoque = $pdo->prepare("INSERT INTO estoque (doacao_id, brinquedo, categoria, quantidade, faixa_etaria, situacao, doador,
                                                           recebido_em, instituicao, entregue_em, observacoes, criado_em)
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $n_doacoes = 0;
        for ($i = 0; $i < 75; $i++) {
            list($uid, $nome, $email, $desde) = $quem(70);
            $b = sorteia($brinquedos);
            $qtd = sorteia(array(1, 1, 1, 1, 2, 2, 2, 3, 4, 5));
            $criada = mt_rand(1, min($desde - 1, 120));               // registrada há N dias (depois do cadastro)
            $entrega = $criada - mt_rand(2, 12);                       // dia combinado para levar (pode ser no futuro)
            $data_entrega = date("Y-m-d", strtotime(($entrega >= 0 ? "-$entrega" : "+" . (-$entrega)) . " days"));
            $ins_doacao->execute(array($uid, $nome, $email, $telefone(), $b[0], $qtd, $data_entrega,
                                       chance(25) ? sorteia($observacoes) : null, sorteia($locais), dias_atras($criada)));
            $doacao_id = (int) $pdo->lastInsertId();
            $n_doacoes++;

            // Se o dia da entrega já passou, o brinquedo chegou e foi para o estoque
            // (só algumas doações dos últimos 10 dias ainda estão "aguardando chegada")
            if ($entrega > 0 && ($entrega > 10 || chance(70))) {
                $obs = null;
                if (chance(4)) {
                    $sit = "Descartado"; $obs = "Peças quebradas, não passou na avaliação.";
                } elseif ($entrega > 25 && chance(75)) {
                    $sit = "Entregue";
                } elseif ($entrega > 6) {
                    $sit = "Em estoque";
                } else {
                    $sit = chance(60) ? "Em avaliação" : "Em estoque";
                }
                $entregue_em = null; $inst = null;
                if ($sit === "Entregue") {
                    $dias_ent = max(0, $entrega - mt_rand(7, 20));
                    $entregue_em = date("Y-m-d", strtotime("-$dias_ent days"));
                    $inst = sorteia($instituicoes);
                }
                $ins_estoque->execute(array($doacao_id, $b[0], $b[1], $qtd, $b[2], $sit, $nome, $data_entrega, $inst, $entregue_em,
                                            $obs, $data_entrega . " " . sprintf("%02d:%02d:00", mt_rand(9, 17), mt_rand(0, 59))));
            }
        }

        // ---------- doações em dinheiro (36) ----------
        $ins_pag = $pdo->prepare("INSERT INTO pagamentos (codigo, usuario_id, nome, email, valor, metodo, detalhe, status,
                                                          expira_em, pago_em, criado_em)
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        for ($i = 0; $i < 36; $i++) {
            list($uid, $nome, $email, $desde) = $quem(75);
            $quando = dias_atras(mt_rand(0, min($desde - 1, 120)));
            $t = strtotime($quando);
            $pix = chance(65);
            $sorte = mt_rand(1, 100);
            $status = $sorte <= 86 ? "Pago" : ($sorte <= 94 ? "Expirado" : "Cancelado");
            $ins_pag->execute(array(substr(codigo_aleatorio(), 0, 24), $uid, $nome, $email, sorteia($valores),
                $pix ? "Pix" : "Cartão",
                ($status === "Pago" && !$pix) ? sorteia($bandeiras) . " final " . sprintf("%04d", mt_rand(0, 9999)) : null,
                $status, date("Y-m-d H:i:s", $t + MINUTOS_PEDIDO * 60),
                $status === "Pago" ? date("Y-m-d H:i:s", $t + mt_rand(30, 400)) : null, $quando));
        }

        // ---------- mensagens (14) ----------
        $ins_msg = $pdo->prepare("INSERT INTO mensagens (usuario_id, nome, email, mensagem, criado_em) VALUES (?, ?, ?, ?, ?)");
        foreach ($mensagens as $texto) {
            list($uid, $nome, $email, $desde) = $quem(60);
            $ins_msg->execute(array($uid, $nome, $email, $texto, dias_atras(mt_rand(0, min($desde - 1, 120)))));
        }

        // ---------- avaliações (22, de pessoas diferentes com conta) ----------
        $ins_av = $pdo->prepare("INSERT INTO avaliacoes (usuario_id, nota, comentario, criado_em) VALUES (?, ?, ?, ?)");
        $avaliadores = $ids;
        shuffle($avaliadores);
        foreach (array_slice($avaliadores, 0, 22) as $uid) {
            $nota = sorteia(array(5, 5, 5, 5, 5, 5, 4, 4, 4, 3));
            $ins_av->execute(array($uid, $nota, chance(70) ? sorteia($comentarios) : null,
                                   dias_atras(mt_rand(0, min($contas[$uid][2] - 1, 120)))));
        }

        $aviso = "Dados de demonstração criados: 40 pessoas com conta, $n_doacoes doações de brinquedos (com o estoque), "
               . "36 doações em dinheiro, " . count($mensagens) . " mensagens e 22 avaliações. Para tirar, use \"Apagar dados de demonstração\".";
    } else {
        $aviso = "";
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    $aviso = $e instanceof AvisoDemo ? $e->getMessage() : "Não foi possível: " . $e->getMessage();
}

$_SESSION["aviso_admin"] = $aviso;
header("Location: admin.php");
exit;
