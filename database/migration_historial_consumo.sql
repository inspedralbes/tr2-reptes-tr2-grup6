-- FASE 3: HISTORIAL DE CONSUMO
-- Tabla para rastrear qué centros han hecho qué talleres (base del algoritmo de prioridad)

-- 1. Crear tabla historial_consumo
CREATE TABLE IF NOT EXISTS historial_consumo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    centre_id INT NOT NULL,
    taller_id INT NOT NULL,
    anyo INT NOT NULL,
    realitzada BOOLEAN DEFAULT 0,
    data_execucio DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (centre_id) REFERENCES usuaris(id),
    FOREIGN KEY (taller_id) REFERENCES tallers(id),
    UNIQUE KEY unique_consumo (centre_id, taller_id, anyo)
);

-- 2. Insertar datos históricos ficticios (años anteriores)
-- Esto es para permitir que el algoritmo funcione desde el inicio
INSERT IGNORE INTO historial_consumo (centre_id, taller_id, anyo, realitzada, data_execucio) VALUES
(2, 1, 2024, 1, '2024-11-15'),
(2, 2, 2024, 1, '2024-12-10'),
(2, 3, 2024, 0, NULL),
(2, 4, 2024, 1, '2024-11-20'),
(3, 1, 2024, 0, NULL),
(3, 5, 2024, 1, '2024-12-05'),
(3, 6, 2024, 1, '2024-11-10')
ON DUPLICATE KEY UPDATE realitzada=VALUES(realitzada);

-- 3. Crear vista auxiliar para contar consumo por centro-taller
CREATE OR REPLACE VIEW vw_consumo_centre_taller AS
SELECT 
    centre_id,
    taller_id,
    COUNT(*) as vegades_realitzat,
    YEAR(CURDATE()) - MAX(anyo) as anys_sense_fer
FROM historial_consumo
WHERE realitzada = 1
GROUP BY centre_id, taller_id;

-- 4. Crear vista para calcular prioridad (0-100, mayor = mayor prioridad)
CREATE OR REPLACE VIEW vw_prioridad_sollicitud AS
SELECT 
    s.id as sollicitud_id,
    s.centre_id,
    s.taller_id,
    COALESCE(h.vegades_realitzat, 0) as vegades_realitzat,
    COALESCE(h.anys_sense_fer, 999) as anys_sense_fer,
    -- Fórmula: Centros que nunca han hecho = prioridad 80
    --         Centros que lo hicieron años atrás = prioridad 60
    --         Centros que lo hicieron recientemente = prioridad 30
    CASE 
        WHEN h.vegades_realitzat IS NULL THEN 80  -- Nunca lo ha hecho
        WHEN h.anys_sense_fer >= 2 THEN 60         -- Hace 2+ años
        WHEN h.anys_sense_fer = 1 THEN 40          -- Hace 1 año
        ELSE 20                                    -- Lo hizo este año
    END as puntuacion_equidad,
    DATEDIFF(CURDATE(), s.data_creacio) as dias_desde_solicitud,
    CASE 
        WHEN DATEDIFF(CURDATE(), s.data_creacio) <= 7 THEN 10
        WHEN DATEDIFF(CURDATE(), s.data_creacio) <= 30 THEN 5
        ELSE 0
    END as puntuacion_rapidez
FROM sollicituds s
LEFT JOIN vw_consumo_centre_taller h ON s.centre_id = h.centre_id AND s.taller_id = h.taller_id
WHERE s.estat = 'pendent';

-- 5. Verificación
SELECT 'Historial creado' as status;
SELECT COUNT(*) as total_registros FROM historial_consumo;
SELECT * FROM vw_prioridad_sollicitud LIMIT 5;
