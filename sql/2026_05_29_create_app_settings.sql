-- Create configurable application UI settings.
-- Safe to re-run: seed values are upserted by setting_key.

CREATE TABLE IF NOT EXISTS app_settings (
  setting_key VARCHAR(100) PRIMARY KEY,
  setting_value TEXT NOT NULL,
  description TEXT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO app_settings (setting_key, setting_value, description) VALUES
('album_title', 'Àlbum de captures del projecte', 'Títol principal visible a la pantalla de login'),
('album_brief', 'Àlbum de cromos', 'Títol curt visible a la vista de l''àlbum'),
('album_subtitle', 'Accés per grups i professorat. Cada grup només pot consultar i pujar les seves captures. El professorat pot consultar tots els àlbums en mode lectura.', 'Subtítol o descripció breu visible a la pantalla de login'),
('project_name', 'Projecte de xarxes', 'Nom del projecte o activitat'),
('institution_name', 'Institut Mediterrània', 'Nom del centre o institució'),
('module_label', '0225 - Xarxes Locals', 'Etiqueta del mòdul visible a la pantalla de login'),
('login_instructions', 'Si ets alumne, utilitza la credencial del teu grup. Si ets professor/a, entra amb el teu usuari de professorat.', 'Instruccions breus sota el formulari de login')
ON DUPLICATE KEY UPDATE
  setting_value = VALUES(setting_value),
  description = VALUES(description);
