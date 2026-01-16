-- FASE 2: LISTADOS DE ALUMNOS
-- Migració per crear la taula d'alumnes inscrits

-- Crear taula d'alumnes inscrits
CREATE TABLE IF NOT EXISTS alumnes_inscrits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sollicitud_id INT NOT NULL,
    nom VARCHAR(100) NOT NULL,
    cognoms VARCHAR(150) NOT NULL,
    curs VARCHAR(50) NULL,
    grup VARCHAR(20) NULL,
    email VARCHAR(100) NULL,
    observacions TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sollicitud_id) REFERENCES sollicituds(id) ON DELETE CASCADE,
    INDEX idx_sollicitud (sollicitud_id)
);

-- Insertar alumnes de exemple per testing (Idempotente)
INSERT IGNORE INTO alumnes_inscrits (sollicitud_id, nom, cognoms, curs, grup, email) VALUES
(1, 'Marc', 'García López', '1r ESO', 'A', 'marc.garcia@exemple.cat'),
(1, 'Laura', 'Martínez Sánchez', '1r ESO', 'A', 'laura.martinez@exemple.cat'),
(1, 'David', 'Fernández Ruiz', '1r ESO', 'B', 'david.fernandez@exemple.cat'),
(1, 'Anna', 'Rodríguez Pérez', '1r ESO', 'B', 'anna.rodriguez@exemple.cat'),
(1, 'Pol', 'González Martín', '1r ESO', 'A', 'pol.gonzalez@exemple.cat');

-- Verificació
SELECT 'Taula alumnes_inscrits creada correctament' as status;
SELECT COUNT(*) as total_alumnes FROM alumnes_inscrits;
SELECT s.id as sollicitud_id, t.nom as taller, COUNT(a.id) as num_alumnes
FROM sollicituds s
LEFT JOIN tallers t ON s.taller_id = t.id
LEFT JOIN alumnes_inscrits a ON s.id = a.sollicitud_id
GROUP BY s.id, t.nom;
