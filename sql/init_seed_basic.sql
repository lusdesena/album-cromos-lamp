-- Minimal safe seed data for a new album-cromos-lamp instance.
-- Run after sql/init_schema.sql.
-- This file is generic and contains no production data or uploads.

SET NAMES utf8mb4;

-- Passwords:
-- Generate a new bcrypt hash before using this in a real instance:
--   php -r 'echo password_hash("CHANGE_THIS_PASSWORD", PASSWORD_DEFAULT), PHP_EOL;'
--
-- The admin hash below is a placeholder only. Replace it before production use.

INSERT INTO app_settings (setting_key, setting_value, description) VALUES
('album_title', 'Àlbum de captures del projecte', 'Títol principal visible a la pantalla de login'),
('album_brief', 'Àlbum de cromos', 'Títol curt visible a la vista de l''àlbum'),
('album_subtitle', 'Accés per grups i professorat. Cada grup només pot consultar i pujar les seves captures. El professorat pot consultar tots els àlbums en mode lectura.', 'Subtítol o descripció breu visible a la pantalla de login'),
('project_name', 'Projecte de xarxes', 'Nom del projecte o activitat'),
('institution_name', 'Centre educatiu', 'Nom del centre o institució'),
('module_label', 'Mòdul o assignatura', 'Etiqueta del mòdul visible a la pantalla de login'),
('login_instructions', 'Si ets alumne, utilitza la credencial del teu grup. Si ets professor/a, entra amb el teu usuari de professorat.', 'Instruccions breus sota el formulari de login')
ON DUPLICATE KEY UPDATE
  setting_value = VALUES(setting_value),
  description = VALUES(description);

INSERT INTO grupsclasse (class_id, identificador) VALUES
(1, 'Classe demo')
ON DUPLICATE KEY UPDATE
  identificador = VALUES(identificador);

INSERT INTO blocs (id, nom, slot_inici, slot_final, visible, editable, ordre) VALUES
(1, 'Bloc 1', 1, 4, 1, 1, 1),
(2, 'Bloc 2', 5, 8, 1, 1, 2)
ON DUPLICATE KEY UPDATE
  nom = VALUES(nom),
  slot_inici = VALUES(slot_inici),
  slot_final = VALUES(slot_final),
  visible = VALUES(visible),
  editable = VALUES(editable),
  ordre = VALUES(ordre);

INSERT INTO bloc_calendari (class_id, bloc_id, data_obertura, data_tancament) VALUES
(1, 1, '2026-01-01 00:00:00', '2026-12-31 23:59:59'),
(1, 2, '2026-01-01 00:00:00', '2026-12-31 23:59:59')
ON DUPLICATE KEY UPDATE
  data_obertura = VALUES(data_obertura),
  data_tancament = VALUES(data_tancament);

INSERT INTO stickers (slot, title, description, bloc_id, visible, enabled, required, sort_order) VALUES
(1, 'Cromo demo 1', 'Primer cromo de mostra', 1, 1, 1, 1, 1),
(2, 'Cromo demo 2', 'Segon cromo de mostra', 1, 1, 1, 1, 2),
(3, 'Cromo demo 3', 'Tercer cromo de mostra', 1, 1, 1, 1, 3),
(4, 'Cromo demo 4', 'Quart cromo de mostra', 1, 1, 1, 1, 4),
(5, 'Cromo demo 5', 'Cinquè cromo de mostra', 2, 1, 1, 1, 5),
(6, 'Cromo demo 6', 'Sisè cromo de mostra', 2, 1, 1, 1, 6),
(7, 'Cromo demo 7', 'Setè cromo de mostra', 2, 1, 1, 1, 7),
(8, 'Cromo demo 8', 'Vuitè cromo de mostra', 2, 1, 1, 1, 8)
ON DUPLICATE KEY UPDATE
  title = VALUES(title),
  description = VALUES(description),
  bloc_id = VALUES(bloc_id),
  visible = VALUES(visible),
  enabled = VALUES(enabled),
  required = VALUES(required),
  sort_order = VALUES(sort_order);

INSERT INTO groups (name, username, password_hash, role, active, class_id) VALUES
('Admin demo', 'admin', '$2y$12$ul24nDZ8NQHrBqaENrAvVewo40GjvuNpNXLq5wP4z54mCtZ5DOqZq', 'admin', 1, 0)
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  role = VALUES(role),
  active = VALUES(active),
  class_id = VALUES(class_id);
