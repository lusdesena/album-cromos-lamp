-- Clean schema for a new album-cromos-lamp instance.
-- Run this on an empty database selected with USE your_database_name;
-- This file contains structure only. Load sql/init_seed_basic.sql afterwards for safe starter data.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS uploads;
DROP TABLE IF EXISTS bloc_calendari;
DROP TABLE IF EXISTS stickers;
DROP TABLE IF EXISTS app_settings;
DROP TABLE IF EXISTS groups;
DROP TABLE IF EXISTS blocs;
DROP TABLE IF EXISTS grupsclasse;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE grupsclasse (
  class_id INT NOT NULL,
  identificador VARCHAR(50) NOT NULL,
  PRIMARY KEY (class_id),
  UNIQUE KEY uq_grupsclasse_identificador (identificador)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE blocs (
  id INT NOT NULL AUTO_INCREMENT,
  nom VARCHAR(100) NOT NULL,
  slot_inici INT NOT NULL,
  slot_final INT NOT NULL,
  visible TINYINT(1) NOT NULL DEFAULT 1,
  editable TINYINT(1) NOT NULL DEFAULT 1,
  ordre INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_blocs_ordre (ordre),
  KEY idx_blocs_slot_range (slot_inici, slot_final),
  KEY idx_blocs_visible (visible)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE groups (
  id INT NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  username VARCHAR(50) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('group', 'profe', 'admin') NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  class_id INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_groups_username (username),
  KEY idx_groups_role_name (role, name),
  KEY idx_groups_class_id (class_id),
  KEY idx_groups_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE uploads (
  id INT NOT NULL AUTO_INCREMENT,
  group_id INT NOT NULL,
  slot INT NOT NULL,
  filename VARCHAR(255) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status ENUM('pendent', 'pendent_validacio', 'validat', 'rebutjat') NOT NULL DEFAULT 'pendent_validacio',
  profe_comment TEXT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_group_slot (group_id, slot),
  KEY idx_uploads_group_created (group_id, created_at),
  KEY idx_uploads_group_status (group_id, status),
  KEY idx_uploads_status_slot (status, slot),
  CONSTRAINT fk_uploads_group
    FOREIGN KEY (group_id) REFERENCES groups(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bloc_calendari (
  id INT NOT NULL AUTO_INCREMENT,
  class_id INT NOT NULL,
  bloc_id INT NOT NULL,
  data_obertura DATETIME NOT NULL,
  data_tancament DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_bloc_calendari_class_bloc (class_id, bloc_id),
  KEY idx_bloc_calendari_bloc (bloc_id),
  KEY idx_bloc_calendari_dates (data_obertura, data_tancament),
  CONSTRAINT fk_bloc_calendari_class
    FOREIGN KEY (class_id) REFERENCES grupsclasse(class_id)
    ON DELETE CASCADE,
  CONSTRAINT fk_bloc_calendari_bloc
    FOREIGN KEY (bloc_id) REFERENCES blocs(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE stickers (
  id INT NOT NULL AUTO_INCREMENT,
  slot INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT NULL,
  bloc_id INT NULL,
  visible TINYINT(1) NOT NULL DEFAULT 1,
  enabled TINYINT(1) NOT NULL DEFAULT 1,
  required TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_stickers_slot (slot),
  KEY idx_stickers_bloc_sort (bloc_id, sort_order),
  KEY idx_stickers_visible_enabled (visible, enabled),
  KEY idx_stickers_sort_slot (sort_order, slot),
  CONSTRAINT fk_stickers_bloc
    FOREIGN KEY (bloc_id) REFERENCES blocs(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE app_settings (
  setting_key VARCHAR(100) NOT NULL,
  setting_value TEXT NOT NULL,
  description TEXT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
