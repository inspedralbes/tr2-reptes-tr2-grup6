-- ============================================================
-- Migració: Taula de Docents (Teachers)
-- ============================================================
-- Data: Gener 2026
-- Descripció: Crea la taula de docents amb relació a centres
-- ============================================================

USE kairos_db;

-- ============================================================
-- TAULA: DOCENTS (TEACHERS)
-- ============================================================
CREATE TABLE IF NOT EXISTS teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    center_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (center_id) REFERENCES centers(id) ON DELETE SET NULL,
    
    INDEX idx_email (email),
    INDEX idx_center (center_id),
    INDEX idx_full_name (full_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- NOTES:
-- - Els docents tenen una relació opcional amb un centre
-- - Quan un docent es crea, automàticament es crea un usuari
--   corresponent a la taula 'users' amb role='teacher'
-- - L'email ha de ser únic a nivell de sistema
-- - Si s'elimina un centre, els docents mantenen el seu registre
--   però center_id es posa a NULL
-- ============================================================
