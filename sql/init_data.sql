-- db/init_data.sql
-- Dades inicials (PROFE + Grups)
-- IMPORTANT:
-- 1) Genera password_hash amb php -r 'echo password_hash("TU_PASSWORD", PASSWORD_DEFAULT), PHP_EOL;'
-- 2) Substitueix els placeholders __HASH_*__ abans d'executar.

USE album_captures;

-- Neteja opcional (si es reexecuta en un entorn de proves)
-- DELETE FROM uploads;
-- DELETE FROM groups;

INSERT INTO groups (name, username, password_hash, role, active) VALUES
  ('Professorat', 'profe',  '__HASH_PROFE__', 'profe', 1),

  ('Grup 1', 'grup1', '__HASH_GRUP1__', 'group', 1),
  ('Grup 2', 'grup2', '__HASH_GRUP2__', 'group', 1),
  ('Grup 3', 'grup3', '__HASH_GRUP3__', 'group', 1),
  ('Grup 4', 'grup4', '__HASH_GRUP4__', 'group', 1);
