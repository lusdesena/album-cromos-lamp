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

  ('Malak i Miguel', 'malakmiguel', '$2y$12$itmvb5SjCsRrhXKULtFzdOC.b6WeDUhDanHzFxwRdcuiFNEZ6qNyi', 'group', 1),
  ('Jaros i Fabrizzio', 'jarosfabrizzio', '$2y$12$otLJgf.ejM9l4R/x.B2N6ul9RkZnzKncxQiAFrQphK39Ru8Yj9kLO', 'group', 1),
  ('Sasha i Jeferson', 'sashajeferson', '$2y$12$ZdqP4l8XBgALxlfmvEUeLucrrfc98/Ocq2vvPRgoNRWFAUk2ZI7Sm', 'group', 1),
  ('Juan i Iker', 'juanik er', '$2y$12$1szTZ8v0Uee7l0RdtbQtfejOjYf8CnzNzChLiKqnxqICqvZA.RnSS', 'group', 1),
  ('Marcos i Derek', 'marcosderek', '$2y$12$9sN0Cw/aC.p8ocrSqNuXnOz.2/M9b4RCK.C5ur/NGxlgkO3ZxEAIm', 'group', 1),
  ('Eric', 'eric', '$2y$12$sQ39x8yKQ0HdeX0VUCv9WOgXPaMuqKG14p8xJGXaYrtTKOvF8.sMG', 'group', 1),
  ('Marc i Alex', 'marcalex', '$2y$12$GnapKck7tHrU8xQEaTMGUukzlhDBT2dj/WSfSc39GO2dRGGMqLbJy', 'group', 1);



