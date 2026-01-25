-- ============================================
-- Migration 006: Sistema de Feedback Post-Taller
-- ============================================
-- Descripció: Taules per gestionar valoracions i feedback
--             dels centres després de completar un taller
-- Data: 2026-01-14
-- ============================================

-- Taula: workshop_feedback
-- Propòsit: Emmagatzemar feedback dels centres sobre tallers realitzats
CREATE TABLE IF NOT EXISTS workshop_feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    center_id INT NOT NULL,
    workshop_id INT NOT NULL,
    submitted_by_user_id INT NULL,
    
    -- Valoracions (escala 1-5)
    overall_rating DECIMAL(2,1) NOT NULL CHECK (overall_rating >= 1 AND overall_rating <= 5),
    content_quality DECIMAL(2,1) NULL CHECK (content_quality >= 1 AND content_quality <= 5),
    teacher_performance DECIMAL(2,1) NULL CHECK (teacher_performance >= 1 AND teacher_performance <= 5),
    organization DECIMAL(2,1) NULL CHECK (organization >= 1 AND organization <= 5),
    relevance DECIMAL(2,1) NULL CHECK (relevance >= 1 AND relevance <= 5),
    would_recommend BOOLEAN DEFAULT TRUE,
    
    -- Comentaris
    positive_aspects TEXT,
    negative_aspects TEXT,
    suggestions TEXT,
    general_comments TEXT,
    
    -- Assistència
    expected_attendees INT NULL,
    actual_attendees INT NULL,
    attendance_rate DECIMAL(5,2) NULL,
    
    -- Metadades
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_public BOOLEAN DEFAULT FALSE,
    is_verified BOOLEAN DEFAULT FALSE,
    verified_by_admin_id INT NULL,
    verified_at TIMESTAMP NULL,
    status ENUM('draft', 'submitted', 'reviewed', 'published') DEFAULT 'submitted',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (assignment_id) REFERENCES workshop_assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (center_id) REFERENCES centers(id) ON DELETE CASCADE,
    FOREIGN KEY (workshop_id) REFERENCES workshops(id) ON DELETE CASCADE,
    FOREIGN KEY (submitted_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (verified_by_admin_id) REFERENCES users(id) ON DELETE SET NULL,
    
    UNIQUE KEY unique_assignment_feedback (assignment_id),
    INDEX idx_center (center_id),
    INDEX idx_workshop (workshop_id),
    INDEX idx_overall_rating (overall_rating),
    INDEX idx_submitted_at (submitted_at),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Taula: feedback_tags
-- Propòsit: Etiquetes predefinides per categoritzar feedback
CREATE TABLE IF NOT EXISTS feedback_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    category ENUM('positive', 'negative', 'neutral') DEFAULT 'neutral',
    color VARCHAR(20),
    icon VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_tag_name (name),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Taula: feedback_tag_relations
-- Propòsit: Relació N:M entre feedback i tags
CREATE TABLE IF NOT EXISTS feedback_tag_relations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    feedback_id INT NOT NULL,
    tag_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (feedback_id) REFERENCES workshop_feedback(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES feedback_tags(id) ON DELETE CASCADE,
    UNIQUE KEY unique_feedback_tag (feedback_id, tag_id),
    INDEX idx_feedback (feedback_id),
    INDEX idx_tag (tag_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Trigger: Calcular attendance_rate automàticament
DELIMITER //
DROP TRIGGER IF EXISTS trg_calculate_attendance_rate//
CREATE TRIGGER trg_calculate_attendance_rate
BEFORE INSERT ON workshop_feedback
FOR EACH ROW
BEGIN
    IF NEW.expected_attendees IS NOT NULL AND NEW.actual_attendees IS NOT NULL AND NEW.expected_attendees > 0 THEN
        SET NEW.attendance_rate = (NEW.actual_attendees / NEW.expected_attendees) * 100;
    END IF;
END//
DELIMITER ;

DELIMITER //
DROP TRIGGER IF EXISTS trg_update_attendance_rate//
CREATE TRIGGER trg_update_attendance_rate
BEFORE UPDATE ON workshop_feedback
FOR EACH ROW
BEGIN
    IF NEW.expected_attendees IS NOT NULL AND NEW.actual_attendees IS NOT NULL AND NEW.expected_attendees > 0 THEN
        SET NEW.attendance_rate = (NEW.actual_attendees / NEW.expected_attendees) * 100;
    END IF;
END//
DELIMITER ;

-- Views and stored procedures removed due to permission constraints
-- These can be recreated separately when needed

-- Datas mock: Tags predefinides
INSERT IGNORE INTO feedback_tags (name, category, color, icon) VALUES
('Excel·lent contingut', 'positive', '#10b981', '✓'),
('Molt pràctic', 'positive', '#10b981', '🔧'),
('Docent molt preparat', 'positive', '#10b981', '👨‍🏫'),
('Bona organització', 'positive', '#10b981', '📋'),
('Recomanable', 'positive', '#10b981', '⭐'),
('Material insuficient', 'negative', '#ef4444', '✗'),
('Massa teòric', 'negative', '#ef4444', '📚'),
('Poc temps', 'negative', '#ef4444', '⏰'),
('Espai inadequat', 'negative', '#ef4444', '🏢'),
('Interessant', 'neutral', '#6b7280', '💡'),
('Necessita millores', 'neutral', '#6b7280', '🔄'),
('Adequat al nivell', 'neutral', '#6b7280', '🎯');

-- Dades mock: Feedback d'exemple
-- DESCOMENTAR después de que existan workshop_assignments con IDs 1, 2, 3
/*
INSERT INTO workshop_feedback (
    assignment_id, center_id, workshop_id, 
    overall_rating, content_quality, teacher_performance, organization, relevance,
    would_recommend, expected_attendees, actual_attendees,
    positive_aspects, negative_aspects, suggestions, general_comments,
    status, is_public
) VALUES
(1, 1, 1, 
 4.8, 5.0, 4.5, 4.8, 5.0,
 TRUE, 20, 19,
 'Excel·lent material didàctic. Els alumnes van estar molt motivats durant tota la sessió.',
 'Només es va fer una mica curt el temps per acabar tots els projectes.',
 'Seria ideal ampliar a 3 hores per tallers de robòtica.',
 'Una experiència molt positiva. Repetiriem segur!',
 'published', TRUE),

(2, 2, 2,
 4.2, 4.0, 4.5, 4.0, 4.3,
 TRUE, 18, 17,
 'Bon enfocament pedagògic. El docent va connectar molt bé amb els estudiants.',
 'Algunes activitats massa simples per al nivell dels alumnes.',
 'Adaptar més les activitats segons el nivell educatiu.',
 'En general positiu, però millorable.',
 'published', TRUE),

(3, 3, 3,
 5.0, 5.0, 5.0, 5.0, 5.0,
 TRUE, 15, 15,
 'Perfecte! Taller molt complet i ben estructurat. Els alumnes van aprendre molt.',
 'Cap aspecte negatiu destacable.',
 'Mantenir aquest nivell de qualitat.',
 'Excel·lent en tots els aspectes. Totalment recomanable.',
 'published', TRUE);

-- Relacions feedback-tags
INSERT INTO feedback_tag_relations (feedback_id, tag_id) VALUES
(1, 1), -- Excel·lent contingut
(1, 2), -- Molt pràctic
(1, 3), -- Docent molt preparat
(2, 4), -- Bona organització
(2, 10), -- Interessant
(3, 1), -- Excel·lent contingut
(3, 3), -- Docent molt preparat
(3, 4), -- Bona organització
(3, 5); -- Recomanable
*/

-- ============================================
-- Fi Migration 006
-- ============================================
