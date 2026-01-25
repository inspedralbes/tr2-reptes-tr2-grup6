-- ============================================================
-- Migració: Taula de Fases Temporals del Projecte
-- ============================================================
-- Data: Gener 2026
-- Descripció: Sistema de fases temporals per controlar funcionalitats
-- ============================================================

USE kairos_db;

-- ============================================================
-- TAULA: FASES TEMPORALS
-- ============================================================
CREATE TABLE IF NOT EXISTS phases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('completed', 'active', 'upcoming') DEFAULT 'upcoming',
    features JSON COMMENT 'Funcionalitats disponibles en aquesta fase',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_status (status),
    INDEX idx_dates (start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DADES INICIALS: Fases del Projecte KAIROS
-- ============================================================

INSERT INTO phases (id, name, description, start_date, end_date, features) VALUES
(1, 'Fase 1: Exploració', 
 'Els centres poden explorar el catàleg de tallers disponibles',
 '2025-09-01', '2025-10-15',
 JSON_OBJECT(
   'marketplace_view', true,
   'add_to_cart', false,
   'submit_requests', false,
   'view_allocations', false,
   'schedule_teachers', false,
   'feedback', false
 )),

(2, 'Fase 2: Llista de Desitjos',
 'Els centres poden crear el seu carret i enviar sol·licituds de tallers',
 '2025-10-16', '2025-11-30',
 JSON_OBJECT(
   'marketplace_view', true,
   'add_to_cart', true,
   'submit_requests', true,
   'view_allocations', false,
   'schedule_teachers', false,
   'feedback', false
 )),

(3, 'Fase 3: La Concordança',
 'Assignació de tallers segons prioritats i disponibilitat',
 '2025-12-01', '2026-01-15',
 JSON_OBJECT(
   'marketplace_view', true,
   'add_to_cart', false,
   'submit_requests', false,
   'view_allocations', true,
   'schedule_teachers', false,
   'feedback', false
 )),

(4, 'Fase 4: Calendari',
 'Docents poden agendar els tallers assignats',
 '2026-01-16', '2026-03-31',
 JSON_OBJECT(
   'marketplace_view', true,
   'add_to_cart', false,
   'submit_requests', false,
   'view_allocations', true,
   'schedule_teachers', true,
   'feedback', false
 )),

(5, 'Fase 5: Execució',
 'Realització dels tallers als centres educatius',
 '2026-04-01', '2026-05-31',
 JSON_OBJECT(
   'marketplace_view', true,
   'add_to_cart', false,
   'submit_requests', false,
   'view_allocations', true,
   'schedule_teachers', true,
   'feedback', false
 )),

(6, 'Fase 6: Avaluació',
 'Recollida de feedback i avaluació dels tallers',
 '2026-06-01', '2026-06-30',
 JSON_OBJECT(
   'marketplace_view', true,
   'add_to_cart', false,
   'submit_requests', false,
   'view_allocations', true,
   'schedule_teachers', false,
   'feedback', true
 ));

-- ============================================================
-- NOTES:
-- - El sistema actualitzarà automàticament l'estat de les fases
--   basant-se en les dates (completed, active, upcoming)
-- - Les funcionalitats es controlen amb el camp JSON 'features'
-- - Les dates es poden ajustar des del panell d'administració
-- ============================================================
