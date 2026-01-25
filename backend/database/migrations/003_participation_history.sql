    -- ============================================
    -- Migration 003: Participation History
    -- ============================================
    -- Taula per registrar l'historial de participació
    -- dels centres en tallers ENGINY per any acadèmic

    CREATE TABLE IF NOT EXISTS participation_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    center_id INT NOT NULL,
    workshop_id INT NOT NULL,
    academic_year VARCHAR(10) NOT NULL,  -- Ex: "2024-2025"
    participated BOOLEAN DEFAULT FALSE,
    assigned BOOLEAN DEFAULT FALSE,
    requested BOOLEAN DEFAULT FALSE,
    attendance_rate DECIMAL(5,2) DEFAULT 0.00,  -- Percentatge assistència (0-100)
    satisfaction_score DECIMAL(3,2) DEFAULT NULL,  -- Puntuació 0-5
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (center_id) REFERENCES centers(id) ON DELETE CASCADE,
    FOREIGN KEY (workshop_id) REFERENCES workshops(id) ON DELETE CASCADE,
    
    -- Un centre només pot tenir un registre per taller per any
    UNIQUE KEY unique_center_workshop_year (center_id, workshop_id, academic_year),
    
    INDEX idx_center_year (center_id, academic_year),
    INDEX idx_workshop_year (workshop_id, academic_year),
    INDEX idx_academic_year (academic_year)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    -- ============================================
    -- Triggers per mantenir coherència
    -- ============================================

    DELIMITER //

DROP TRIGGER IF EXISTS trg_participation_updated//

CREATE TRIGGER trg_participation_updated
BEFORE UPDATE ON participation_history
FOR EACH ROW
BEGIN
  SET NEW.updated_at = CURRENT_TIMESTAMP;
END//

DELIMITER ;

    -- ============================================
    -- Dades de prova (opcional)
    -- ============================================

-- Los datos de prueba se insertarán después de que existan centros y talleres
-- DESCOMENTAR después de haber ejecutado schema.sql y tener datos en centers y workshops

/*
-- Historial 2023-2024
INSERT INTO participation_history (center_id, workshop_id, academic_year, participated, assigned, requested, attendance_rate, satisfaction_score) VALUES
(1, 1, '2023-2024', TRUE, TRUE, TRUE, 95.50, 4.8),
(1, 2, '2023-2024', TRUE, TRUE, TRUE, 88.20, 4.5),
(2, 1, '2023-2024', FALSE, TRUE, TRUE, 45.00, 2.1),
(2, 3, '2023-2024', TRUE, TRUE, TRUE, 92.00, 4.9),
(3, 2, '2023-2024', TRUE, TRUE, TRUE, 100.00, 5.0),
(3, 4, '2023-2024', FALSE, FALSE, TRUE, NULL, NULL);

-- Historial 2024-2025
INSERT INTO participation_history (center_id, workshop_id, academic_year, participated, assigned, requested, attendance_rate, satisfaction_score) VALUES
(1, 3, '2024-2025', TRUE, TRUE, TRUE, 90.00, 4.6),
(1, 5, '2024-2025', TRUE, TRUE, TRUE, 85.50, 4.3),
(2, 2, '2024-2025', TRUE, TRUE, TRUE, 78.50, 4.0),
(3, 1, '2024-2025', TRUE, TRUE, TRUE, 98.00, 4.9);
*/

    ALTER TABLE participation_history COMMENT = 'Historial de participació dels centres en tallers per any acadèmic';
