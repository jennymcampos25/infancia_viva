-- Banco de dados do site Infância Viva
-- Pode ser importado mais de uma vez sem apagar nada (usa IF NOT EXISTS).
-- usuario_id liga o registro à conta de quem estava logado (NULL = visitante sem conta).
-- Banco antigo, criado antes dessa coluna existir? Rode o atualizar_banco.php.

CREATE DATABASE IF NOT EXISTS infancia_viva
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;

USE infancia_viva;

-- Contas de quem se cadastra no site (a senha é guardada como hash, nunca pura)
CREATE TABLE IF NOT EXISTS usuarios (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nome        VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL UNIQUE,
    senha_hash  VARCHAR(255) NOT NULL,
    tipo        ENUM('doador','admin') NOT NULL DEFAULT 'doador',
    foto        VARCHAR(30) NULL,       -- NULL = sem foto; avatar1..avatar6 = foto pronta; upload:... = foto enviada
    foto_dados  MEDIUMBLOB NULL,        -- a foto enviada (já reduzida para 256x256)
    email_confirmado  TINYINT(1) NOT NULL DEFAULT 1,   -- 0 = criou a conta mas ainda não clicou no link do e-mail
    token_confirmacao CHAR(64) NULL,                   -- hash do código do link de confirmação
    token_expira      DATETIME NULL,
    criado_em   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sessões de login (ficam no banco para ninguém ser deslogado quando o site é atualizado)
CREATE TABLE IF NOT EXISTS sessoes (
    id            VARCHAR(128) PRIMARY KEY,
    dados         MEDIUMBLOB NOT NULL,
    atualizado_em INT NOT NULL,
    INDEX (atualizado_em)
) ENGINE=InnoDB;

-- Locais de coleta (escolas, centros de acolhimento...). O admin cadastra e edita no painel.
CREATE TABLE IF NOT EXISTS locais (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    nome      VARCHAR(120) NOT NULL,
    tipo      VARCHAR(40)  NOT NULL,
    endereco  VARCHAR(200) NOT NULL,
    coleta    VARCHAR(200) NOT NULL,          -- dias e horários em que o local recebe brinquedos
    ativo     TINYINT(1)   NOT NULL DEFAULT 1, -- 0 = escondido do site
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Locais iniciais (só entram se a tabela estiver vazia): troque pelos locais de verdade no painel
INSERT INTO locais (nome, tipo, endereco, coleta)
SELECT * FROM (SELECT 'Sede da Infância Viva (NEAC)' AS nome, 'Sede da ONG' AS tipo, 'Rua Cantor Emílio Santiago, 118 - Campo Grande, Rio de Janeiro - RJ' AS endereco, 'Datas a definir' AS coleta
               UNION ALL SELECT 'Escola parceira', 'Escola', 'Endereço a definir', 'Datas a definir'
               UNION ALL SELECT 'Centro comunitário', 'Centro comunitário', 'Endereço a definir', 'Datas a definir') iniciais
WHERE NOT EXISTS (SELECT 1 FROM locais);

-- Formulário da página Doação (brinquedos)
CREATE TABLE IF NOT EXISTS doacoes (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id   INT NULL,
    nome         VARCHAR(100) NOT NULL,
    email        VARCHAR(150) NOT NULL,
    telefone     VARCHAR(30)  NOT NULL,
    brinquedo    VARCHAR(150) NOT NULL,
    quantidade   INT NOT NULL,
    data_doacao  DATE NOT NULL,
    endereco     VARCHAR(200) NOT NULL,
    observacoes  TEXT NULL,
    ponto        VARCHAR(50)  NOT NULL,
    valor        DECIMAL(10,2) NULL,
    criado_em    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_doacoes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Doação em dinheiro (simulada): cada linha é um pedido.
-- status: Aguardando -> Pago (ou Expirado / Cancelado). "Simulado" e "Confirmado" são do formato antigo.
-- Do cartão só ficam a bandeira e os 4 últimos dígitos (coluna detalhe), nunca o número inteiro.
CREATE TABLE IF NOT EXISTS pagamentos (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    codigo         VARCHAR(40)  NULL,
    usuario_id     INT NULL,
    nome           VARCHAR(100) NOT NULL,
    email          VARCHAR(150) NULL,
    valor          DECIMAL(10,2) NOT NULL,
    metodo         VARCHAR(20)  NOT NULL,
    detalhe        VARCHAR(60)  NULL,
    status         VARCHAR(20)  NOT NULL DEFAULT 'Simulado',
    expira_em      DATETIME     NULL,
    pago_em        DATETIME     NULL,
    recibo_enviado TINYINT(1)   NOT NULL DEFAULT 0,
    criado_em      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_pagamentos_codigo (codigo),
    CONSTRAINT fk_pagamentos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Formulário "Envie uma mensagem" da página Contato
CREATE TABLE IF NOT EXISTS mensagens (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT NULL,
    nome        VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL,
    mensagem    TEXT NOT NULL,
    criado_em   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mensagens_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Avaliações (nota de 1 a 5 e comentário, ambos opcionais)
CREATE TABLE IF NOT EXISTS avaliacoes (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT NULL,
    nota        TINYINT NULL,
    comentario  TEXT NULL,
    criado_em   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_avaliacoes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
