-- ============================================================
-- AdminFlow — Schema MySQL  |  Executar UMA VEZ no phpMyAdmin
-- ============================================================
CREATE DATABASE IF NOT EXISTS adminflow
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE adminflow;

CREATE TABLE IF NOT EXISTS divisoes (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  nome          VARCHAR(100) NOT NULL,
  descricao     TEXT,
  responsavel   VARCHAR(100),
  ativa         TINYINT(1) NOT NULL DEFAULT 1,
  criado_em     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categorias (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  nome      VARCHAR(100) NOT NULL UNIQUE,
  cor       VARCHAR(20) NOT NULL DEFAULT '#7C6FFF',
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- online: 0=offline  1=online
-- last_seen: última vez que esteve online
CREATE TABLE IF NOT EXISTS funcionarios (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  nome          VARCHAR(100) NOT NULL,
  cargo         VARCHAR(100),
  turno         VARCHAR(60),
  divisao_id    INT DEFAULT NULL,
  telemovel     VARCHAR(25) DEFAULT NULL,
  email         VARCHAR(150) DEFAULT NULL,
  online        TINYINT(1) NOT NULL DEFAULT 0,
  last_seen     DATETIME DEFAULT NULL,
  meta_horas    INT NOT NULL DEFAULT 160,
  ativo         TINYINT(1) NOT NULL DEFAULT 1,
  criado_em     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (divisao_id) REFERENCES divisoes(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS usuarios (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  login          VARCHAR(60) NOT NULL UNIQUE,
  senha_hash     VARCHAR(255) NOT NULL,
  role           ENUM('admin','func') NOT NULL DEFAULT 'func',
  funcionario_id INT DEFAULT NULL,
  ativo          TINYINT(1) NOT NULL DEFAULT 1,
  ultimo_login   DATETIME DEFAULT NULL,
  criado_em      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (funcionario_id) REFERENCES funcionarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tickets (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  assunto       VARCHAR(200) NOT NULL,
  descricao     TEXT,
  prioridade    ENUM('Alta','Média','Baixa') NOT NULL DEFAULT 'Média',
  atribuido_id  INT DEFAULT NULL,
  criado_por_id INT DEFAULT NULL,
  estado        ENUM('Pendente','Resolvido') NOT NULL DEFAULT 'Pendente',
  criado_em     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  resolvido_em  DATETIME DEFAULT NULL,
  FOREIGN KEY (atribuido_id)  REFERENCES funcionarios(id) ON DELETE SET NULL,
  FOREIGN KEY (criado_por_id) REFERENCES funcionarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS bate_ponto (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  funcionario_id   INT NOT NULL,
  tipo             ENUM('Entrada','Saída') NOT NULL,
  timestamp        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  duracao_minutos  INT DEFAULT NULL,
  observacao       VARCHAR(200) DEFAULT NULL,
  FOREIGN KEY (funcionario_id) REFERENCES funcionarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS registros (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  nome          VARCHAR(200) NOT NULL,
  categoria_id  INT DEFAULT NULL,
  descricao     TEXT,
  estado        ENUM('Activo','Pendente','Inactivo') NOT NULL DEFAULT 'Activo',
  data          DATE DEFAULT NULL,
  criado_em     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX IF NOT EXISTS idx_func_online ON funcionarios(online);
CREATE INDEX IF NOT EXISTS idx_bp_func_ts  ON bate_ponto(funcionario_id, timestamp);
CREATE INDEX IF NOT EXISTS idx_tk_estado   ON tickets(estado);
