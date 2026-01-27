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
('Carlos i Diego Andino', 'carlosdiegoandino', '$2y$12$ggbTrFQ75yzGaYABOcFVn.ClzoiK7wjTLzao4mcQrrTSMvf/ot3su', 'group', 1),
('Joel i Dorinel',        'joeldorinel',        '$2y$12$hSQgFLlxjQ.eHGcgR6IXh.yYw/y9TgOFKV3xD/yVRWayAfaDDl6we', 'group', 1),
('Manuel i Olaf',        'manuelolaf',         '$2y$12$77zkU./9TQSSAewAb4x6Yu2r5vob2WstI5Z5qO8M11ax6o7hnjiQW', 'group', 1),
('Sami i Kevin',         'samikevin',          '$2y$12$PSVjbP4DfEr7oHvJLt88JOBfU62oq5/rOm8yOp6Xpnvx/qIxNvdCC', 'group', 1),
('Jayden i Wenkang',     'jaydenwenkang',      '$2y$12$Y2/L4ccjl8E8aiEq5nn1cekUs2dV9ZucCVETRORADH0Q20ExiYfyu', 'group', 1),
('Abby i Harry',         'abbyharry',          '$2y$12$bFKs2vWfgDZ22zA7jbwtOOckLsnXiRZeO6l57S3LPOZ0BNO9ah5W2', 'group', 1),
('David i Irene',        'davidirene',         '$2y$12$y4MM7Kh44AXkvhx5uRxEiu0EYH/0Thg.TPhzWxFgRcuGGmOgXQCRO', 'group', 1),
('Diego Carreño',        'diegocarreno',       '$2y$12$Z2QWaJJApZsmL/s/pMCDpONnYI6BlYt0u2xSdHdHWu4qtAkoalRjy', 'group', 1),
('Alex i Jeffery',       'alexjeffery',        '$2y$12$Ed1tw982YY/DWVL75YTKwuOpGKT.fNcJNLNhiCjFze909teoKTEnK', 'group', 1);



