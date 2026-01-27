-- sql/init_data.sql
-- Dades inicials (PROFE + Grups)
-- IMPORTANT:
-- 1) Genera password_hash amb php -r 'echo password_hash("TU_PASSWORD", PASSWORD_DEFAULT), PHP_EOL;'
-- 2) Substitueix els placeholders __HASH_*__ abans d'executar.

USE album_captures;

-- Neteja opcional (si es reexecuta en un entorn de proves)
-- DELETE FROM uploads;
-- DELETE FROM groups;

INSERT INTO groups (name, username, password_hash, role, active) VALUES
  ('Professorat', 'profe',  '$2y$12$uVqtVA1pk.wSnzBgq/ojD.6efMq0qFDKtrgXc97V5QEnoXXZro0pe', 'profe', 1),
  ('Usuari de proves', 'alumnat', '$2y$12$FV/tuqXcCm0B/qpH4clkPe9SxN.Usi1WmW7CsBvTGRu0.mpVDNaX2', 'group', 1);



