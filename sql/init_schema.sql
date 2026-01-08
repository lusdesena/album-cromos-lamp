-- db/init_schema.sql
-- Esquema base del projecte "Àlbum de cromos" (slots fixes)
-- Compatible amb MariaDB / MySQL

-- 1) Base de dades
CREATE DATABASE IF NOT EXISTS album_captures
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE album_captures;

-- 2) Usuari aplicació (NO root)
-- Nota: canvia la contrasenya abans d'executar en entorn real.
CREATE USER IF NOT EXISTS 'album_user'@'localhost' IDENTIFIED BY 'P1X2025';

-- Permisos mínims: només aquesta BD
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, INDEX, ALTER
ON album_captures.* TO 'album_user'@'localhost';

FLUSH PRIVILEGES;

-- 3) Taules
DROP TABLE IF EXISTS uploads;
DROP TABLE IF EXISTS groups;

-- 3.1) Groups (grups + profe)
CREATE TABLE groups (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(100) NOT NULL,
  username      VARCHAR(50)  NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('profe', 'group') NOT NULL,
  active        TINYINT(1) NOT NULL DEFAULT 1,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_groups_username (username)
) ENGINE=InnoDB;

-- 3.2) Uploads (un cromo per slot i per grup)
CREATE TABLE uploads (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  group_id      INT NOT NULL,
  slot          INT NOT NULL,
  filename      VARCHAR(255) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT fk_uploads_group
    FOREIGN KEY (group_id) REFERENCES groups(id)
    ON DELETE CASCADE,

  -- IMPORTANT: un sol registre per slot dins d'un grup
  UNIQUE KEY uq_group_slot (group_id, slot),

  -- Ajuda a consultes i ordenacions (opcional però útil)
  KEY idx_uploads_group_created (group_id, created_at)
) ENGINE=InnoDB;
