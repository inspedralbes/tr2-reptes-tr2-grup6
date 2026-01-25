-- Script per arreglar la codificació dels tallers
-- Executa aquest script per convertir els caràcters malament codificats

USE kairos_db;

-- Actualitzar els noms i descripcions dels workshops que tenen problemes d'encoding
UPDATE workshops 
SET 
    name = CONVERT(CAST(CONVERT(name USING latin1) AS BINARY) USING utf8mb4),
    description = CONVERT(CAST(CONVERT(description USING latin1) AS BINARY) USING utf8mb4)
WHERE 
    name LIKE '%Ã%' OR 
    description LIKE '%Ã%' OR
    name LIKE '%Ã%' OR
    description LIKE '%Ã%';

-- Verificar que s'ha arreglat
SELECT id, name, description FROM workshops LIMIT 5;
