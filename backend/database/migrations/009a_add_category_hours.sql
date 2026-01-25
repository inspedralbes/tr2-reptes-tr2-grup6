-- ============================================================
-- Migració 009A: COMENTADA I DESACTIVADA
-- ============================================================
-- Data: 14 de Gener 2026
-- Descripció: Aquesta migració ja NO és necessària
-- Raó: Les columnes que necessitem ja existeixen amb altres noms:
--      - category → ambit (JA EXISTEIX)
--      - hours → duration_hours (JA EXISTEIX)
-- Les migracions 008 ja va afegir tots els camps necessaris
-- ============================================================

-- USE kairos_db;

-- -- Verificar que la columna category no existeix ja
-- ALTER TABLE workshops
-- ADD COLUMN IF NOT EXISTS category VARCHAR(100) NULL COMMENT 'Categoria del taller (Robòtica, Programació, etc.)',
-- ADD COLUMN IF NOT EXISTS hours INT DEFAULT 20 COMMENT 'Hores totals del taller';

-- -- Verificar que s'han afegit les columnes correctament
-- SHOW COLUMNS FROM workshops WHERE Field IN ('category', 'hours');
