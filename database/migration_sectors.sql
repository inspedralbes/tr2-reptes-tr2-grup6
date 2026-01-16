-- FASE 1: ESTRUCTURA DE SECTORES PROFESSIONALS
-- Script SQL para implementar los 11 sectores oficiales del programa ENGINY

-- 1. Crear la tabla maestra de SECTORES
CREATE TABLE IF NOT EXISTS sectors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    icona VARCHAR(50) DEFAULT 'school',
    color VARCHAR(20) DEFAULT '#64748B',
    actiu BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Insertar los 11 Sectores del PDF (con colores aproximados)
INSERT INTO sectors (nom, color, icona) VALUES 
('Agroalimentari', '#4ADE80', 'agriculture'),
('Manufacturer', '#F87171', 'precision_manufacturing'),
('Indústria del Metall i la Mobilitat', '#94A3B8', 'engineering'),
('Energia i Sostenibilitat', '#34D399', 'bolt'),
('Construcció', '#FBBF24', 'construction'),
('Transformació Digital', '#60A5FA', 'computer'),
('Químic', '#A78BFA', 'science'),
('Serveis a les empreses', '#1E293B', 'business_center'),
('Serveis a les persones', '#F472B6', 'people'),
('Artístic', '#F43F5E', 'palette'),
('Salut i Esport', '#2DD4BF', 'fitness_center');

-- 3. Modificar la tabla TALLERS para vincularla al Sector
-- Añadir columna sector_id si no existe (ignora error si ya existe)
-- 3. Modificar la tabla TALLERS para vincularla al Sector
-- Añadir columna sector_id de forma segura (Idempotente)
DROP PROCEDURE IF EXISTS upgrade_sectors_schema;
DELIMITER //
CREATE PROCEDURE upgrade_sectors_schema()
BEGIN
    -- Verificar si existe la column sector_id
    IF NOT EXISTS(SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='kairos_db' AND TABLE_NAME='tallers' AND COLUMN_NAME='sector_id') THEN
        ALTER TABLE tallers ADD COLUMN sector_id INT AFTER nom;
        ALTER TABLE tallers ADD CONSTRAINT fk_taller_sector FOREIGN KEY (sector_id) REFERENCES sectors(id) ON DELETE SET NULL;
    END IF;
END //
DELIMITER ;
CALL upgrade_sectors_schema();
DROP PROCEDURE upgrade_sectors_schema;

-- 4. Migrar datos existentes (si tienes talleres con categoria_id)
-- Mapeo básico de categorías antiguas a sectores nuevos:
-- Tecnologia (1) -> Transformació Digital (6)
-- Sostenibilitat (2) -> Energia i Sostenibilitat (4)
-- Fabricació (3) -> Manufacturer (2)

UPDATE tallers SET sector_id = 6 WHERE categoria_id = 1;
UPDATE tallers SET sector_id = 4 WHERE categoria_id = 2;
UPDATE tallers SET sector_id = 2 WHERE categoria_id = 3;

-- 5. Opcional: Eliminar la columna antigua categoria_id
-- (Descomenta si quieres limpiar completamente)
-- ALTER TABLE tallers DROP FOREIGN KEY tallers_ibfk_1;
-- ALTER TABLE tallers DROP COLUMN categoria_id;

-- Verificación: Ver los sectores creados
SELECT * FROM sectors;

-- Verificación: Ver talleres con sus sectores
SELECT t.id, t.nom, s.nom as sector 
FROM tallers t 
LEFT JOIN sectors s ON t.sector_id = s.id 
WHERE t.estat = 'actiu';
