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
    criado_em   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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

-- Página de pagamento (simulado)
CREATE TABLE IF NOT EXISTS pagamentos (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT NULL,
    nome        VARCHAR(100) NOT NULL,
    valor       DECIMAL(10,2) NOT NULL,
    metodo      VARCHAR(20)  NOT NULL,
    status      VARCHAR(20)  NOT NULL DEFAULT 'Simulado',
    criado_em   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
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
