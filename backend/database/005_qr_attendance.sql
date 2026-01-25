-- ============================================
-- Migration 005: Sistema de Codis QR i Assistència
-- ============================================
-- Descripció: Taula per gestionar codis QR dels tallers
--             i registrar assistència mitjançant escaneig
-- Data: 2026-01-14
-- ============================================

-- Taula: workshop_qr_codes
-- Propòsit: Emmagatzemar codis QR únics per cada assignació de taller
CREATE TABLE IF NOT EXISTS workshop_qr_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    qr_code VARCHAR(100) UNIQUE NOT NULL,
    qr_data TEXT NOT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    scan_count INT DEFAULT 0,
    last_scanned_at TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (assignment_id) REFERENCES workshop_assignments(id) ON DELETE CASCADE,
    INDEX idx_qr_code (qr_code),
    INDEX idx_assignment (assignment_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Taula: qr_scans
-- Propòsit: Registrar cada escaneig de QR per assistència
CREATE TABLE IF NOT EXISTS qr_scans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    qr_code_id INT NOT NULL,
    assignment_id INT NOT NULL,
    center_id INT NOT NULL,
    teacher_id INT NULL,
    scan_type ENUM('check_in', 'check_out', 'verification') DEFAULT 'check_in',
    scanned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    scanned_by_user_id INT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    location_data JSON,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (qr_code_id) REFERENCES workshop_qr_codes(id) ON DELETE CASCADE,
    FOREIGN KEY (assignment_id) REFERENCES workshop_assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (center_id) REFERENCES centers(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (scanned_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_qr_code (qr_code_id),
    INDEX idx_assignment (assignment_id),
    INDEX idx_center (center_id),
    INDEX idx_scanned_at (scanned_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Trigger: Actualitzar comptador d'escanejos
DELIMITER //
DROP TRIGGER IF EXISTS trg_increment_scan_count//
CREATE TRIGGER trg_increment_scan_count
AFTER INSERT ON qr_scans
FOR EACH ROW
BEGIN
    UPDATE workshop_qr_codes
    SET scan_count = scan_count + 1,
        last_scanned_at = NEW.scanned_at
    WHERE id = NEW.qr_code_id;
END//
DELIMITER ;

-- Vista: v_qr_attendance_summary
-- Propòsit: Resum d'assistència per taller amb escanejos QR
CREATE OR REPLACE VIEW v_qr_attendance_summary AS
SELECT 
    wa.id AS assignment_id,
    wa.workshop_id,
    w.name AS workshop_name,
    wa.center_id,
    c.name AS center_name,
    wqr.qr_code,
    wqr.is_active AS qr_active,
    wqr.scan_count,
    wqr.last_scanned_at,
    COUNT(DISTINCT qs.id) AS total_scans,
    COUNT(DISTINCT CASE WHEN qs.scan_type = 'check_in' THEN qs.id END) AS check_in_count,
    COUNT(DISTINCT CASE WHEN qs.scan_type = 'check_out' THEN qs.id END) AS check_out_count,
    MIN(qs.scanned_at) AS first_scan,
    MAX(qs.scanned_at) AS last_scan
FROM workshop_assignments wa
LEFT JOIN workshops w ON wa.workshop_id = w.id
LEFT JOIN centers c ON wa.center_id = c.id
LEFT JOIN workshop_qr_codes wqr ON wqr.assignment_id = wa.id
LEFT JOIN qr_scans qs ON qs.assignment_id = wa.id
GROUP BY wa.id, wa.workshop_id, w.name, wa.center_id, c.name, wqr.qr_code, wqr.is_active, wqr.scan_count, wqr.last_scanned_at;

-- Stored Procedure: Generar codi QR per assignació
DELIMITER //
DROP PROCEDURE IF EXISTS sp_generate_qr_code//
CREATE PROCEDURE sp_generate_qr_code(
    IN p_assignment_id INT,
    IN p_expires_days INT
)
BEGIN
    DECLARE v_qr_code VARCHAR(100);
    DECLARE v_qr_data TEXT;
    DECLARE v_expires_at TIMESTAMP;
    
    -- Generar codi únic (hash basat en assignació + timestamp)
    SET v_qr_code = CONCAT('QR-', UPPER(MD5(CONCAT(p_assignment_id, UNIX_TIMESTAMP()))));
    
    -- Generar dades JSON per al QR
    SET v_qr_data = JSON_OBJECT(
        'type', 'workshop_attendance',
        'assignment_id', p_assignment_id,
        'qr_code', v_qr_code,
        'generated_at', NOW()
    );
    
    -- Calcular expiració
    IF p_expires_days IS NOT NULL THEN
        SET v_expires_at = DATE_ADD(NOW(), INTERVAL p_expires_days DAY);
    ELSE
        SET v_expires_at = NULL;
    END IF;
    
    -- Desactivar QR anteriors per aquesta assignació
    UPDATE workshop_qr_codes
    SET is_active = FALSE
    WHERE assignment_id = p_assignment_id;
    
    -- Insertar nou QR
    INSERT INTO workshop_qr_codes (assignment_id, qr_code, qr_data, expires_at, is_active)
    VALUES (p_assignment_id, v_qr_code, v_qr_data, v_expires_at, TRUE);
    
    -- Retornar codi generat
    SELECT v_qr_code AS qr_code, v_qr_data AS qr_data, v_expires_at AS expires_at;
END//
DELIMITER ;

-- Stored Procedure: Registrar escaneig de QR
DELIMITER //
DROP PROCEDURE IF EXISTS sp_scan_qr_code//
CREATE PROCEDURE sp_scan_qr_code(
    IN p_qr_code VARCHAR(100),
    IN p_scan_type VARCHAR(20),
    IN p_user_id INT,
    IN p_ip_address VARCHAR(45)
)
BEGIN
    DECLARE v_qr_code_id INT;
    DECLARE v_assignment_id INT;
    DECLARE v_center_id INT;
    DECLARE v_is_active BOOLEAN;
    DECLARE v_expires_at TIMESTAMP;
    
    -- Verificar QR existeix
    SELECT id, assignment_id, is_active, expires_at
    INTO v_qr_code_id, v_assignment_id, v_is_active, v_expires_at
    FROM workshop_qr_codes
    WHERE qr_code = p_qr_code
    LIMIT 1;
    
    IF v_qr_code_id IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Codi QR no trobat';
    END IF;
    
    -- Verificar QR actiu
    IF v_is_active = FALSE THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Codi QR inactiu';
    END IF;
    
    -- Verificar no ha expirat
    IF v_expires_at IS NOT NULL AND v_expires_at < NOW() THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Codi QR expirat';
    END IF;
    
    -- Obtenir center_id de l'assignació
    SELECT center_id INTO v_center_id
    FROM workshop_assignments
    WHERE id = v_assignment_id;
    
    -- Registrar escaneig
    INSERT INTO qr_scans (qr_code_id, assignment_id, center_id, scan_type, scanned_by_user_id, ip_address)
    VALUES (v_qr_code_id, v_assignment_id, v_center_id, p_scan_type, p_user_id, p_ip_address);
    
    -- Retornar informació
    SELECT 
        v_qr_code_id AS qr_code_id,
        v_assignment_id AS assignment_id,
        v_center_id AS center_id,
        p_scan_type AS scan_type,
        NOW() AS scanned_at,
        'Escaneig registrat correctament' AS message;
END//
DELIMITER ;

-- Dades mock per testing
-- DESCOMENTAR después de que existan workshop_assignments
/*
INSERT INTO workshop_qr_codes (assignment_id, qr_code, qr_data, is_active, scan_count) VALUES
(1, 'QR-A1B2C3D4E5F6', '{"type":"workshop_attendance","assignment_id":1,"qr_code":"QR-A1B2C3D4E5F6"}', TRUE, 5),
(2, 'QR-F6E5D4C3B2A1', '{"type":"workshop_attendance","assignment_id":2,"qr_code":"QR-F6E5D4C3B2A1"}', TRUE, 8),
(3, 'QR-123456789ABC', '{"type":"workshop_attendance","assignment_id":3,"qr_code":"QR-123456789ABC"}', TRUE, 3);

INSERT INTO qr_scans (qr_code_id, assignment_id, center_id, scan_type, scanned_at, ip_address) VALUES
(1, 1, 1, 'check_in', '2026-01-10 09:00:00', '192.168.1.100'),
(1, 1, 1, 'check_out', '2026-01-10 11:00:00', '192.168.1.100'),
(2, 2, 2, 'check_in', '2026-01-11 10:00:00', '192.168.1.101'),
(2, 2, 2, 'check_out', '2026-01-11 12:00:00', '192.168.1.101'),
(3, 3, 3, 'check_in', '2026-01-12 09:30:00', '192.168.1.102');
*/

-- ============================================
-- Fi Migration 005
-- ============================================
