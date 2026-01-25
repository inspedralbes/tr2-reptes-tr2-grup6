-- ============================================
-- Migration 004: Center Priority
-- ============================================
-- Taula per calcular i emmagatzemar la prioritat
-- de cada centre basada en el seu historial

CREATE TABLE IF NOT EXISTS center_priority (
  id INT AUTO_INCREMENT PRIMARY KEY,
  center_id INT NOT NULL,
  academic_year VARCHAR(10) NOT NULL,  -- Any per al qual es calcula la prioritat
  
  -- Mètriques de càlcul
  total_requests INT DEFAULT 0,  -- Total de sol·licituds fetes
  total_assignments INT DEFAULT 0,  -- Total de tallers assignats
  total_participations INT DEFAULT 0,  -- Total de participacions reals
  avg_attendance_rate DECIMAL(5,2) DEFAULT 0.00,  -- Mitjana assistència
  avg_satisfaction DECIMAL(3,2) DEFAULT 0.00,  -- Mitjana satisfacció
  
  -- Prioritat calculada (0-100)
  priority_score DECIMAL(5,2) NOT NULL DEFAULT 50.00,
  
  -- Factors de bonificació/penalització
  bonus_attendance DECIMAL(5,2) DEFAULT 0.00,  -- +punts per bona assistència
  bonus_satisfaction DECIMAL(5,2) DEFAULT 0.00,  -- +punts per bona valoració
  penalty_low_participation DECIMAL(5,2) DEFAULT 0.00,  -- -punts per baixa participació
  penalty_cancellations DECIMAL(5,2) DEFAULT 0.00,  -- -punts per cancel·lacions
  
  -- Notes internes
  notes TEXT DEFAULT NULL,
  
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (center_id) REFERENCES centers(id) ON DELETE CASCADE,
  
  -- Un centre només pot tenir una prioritat per any
  UNIQUE KEY unique_center_year (center_id, academic_year),
  
  INDEX idx_priority_score (priority_score DESC),
  INDEX idx_academic_year (academic_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Triggers per recalcular prioritat
-- ============================================

DELIMITER //

DROP TRIGGER IF EXISTS trg_priority_updated//

CREATE TRIGGER trg_priority_updated
BEFORE UPDATE ON center_priority
FOR EACH ROW
BEGIN
  SET NEW.updated_at = CURRENT_TIMESTAMP;
END//

DELIMITER ;

-- ============================================
-- Vista per ranking de centres
-- ============================================

CREATE OR REPLACE VIEW v_center_ranking AS
SELECT 
  cp.center_id,
  c.name AS center_name,
  c.code AS center_code,
  cp.academic_year,
  cp.priority_score,
  cp.total_requests,
  cp.total_assignments,
  cp.total_participations,
  cp.avg_attendance_rate,
  cp.avg_satisfaction,
  RANK() OVER (PARTITION BY cp.academic_year ORDER BY cp.priority_score DESC) AS ranking,
  cp.updated_at
FROM center_priority cp
INNER JOIN centers c ON cp.center_id = c.id
ORDER BY cp.academic_year DESC, cp.priority_score DESC;

-- ============================================
-- Stored Procedure: Recalcular prioritat
-- ============================================

DELIMITER //

DROP PROCEDURE IF EXISTS sp_calculate_priority//

CREATE PROCEDURE sp_calculate_priority(
  IN p_center_id INT,
  IN p_academic_year VARCHAR(10)
)
BEGIN
  DECLARE v_total_requests INT DEFAULT 0;
  DECLARE v_total_assignments INT DEFAULT 0;
  DECLARE v_total_participations INT DEFAULT 0;
  DECLARE v_avg_attendance DECIMAL(5,2) DEFAULT 0;
  DECLARE v_avg_satisfaction DECIMAL(3,2) DEFAULT 0;
  DECLARE v_priority_score DECIMAL(5,2) DEFAULT 50.00;
  DECLARE v_bonus_attendance DECIMAL(5,2) DEFAULT 0;
  DECLARE v_bonus_satisfaction DECIMAL(5,2) DEFAULT 0;
  DECLARE v_penalty_participation DECIMAL(5,2) DEFAULT 0;
  DECLARE v_participation_rate DECIMAL(5,2) DEFAULT 0;
  
  -- Obtenir mètriques de l'historial
  SELECT 
    COUNT(*) AS total_req,
    SUM(CASE WHEN assigned = TRUE THEN 1 ELSE 0 END) AS total_asg,
    SUM(CASE WHEN participated = TRUE THEN 1 ELSE 0 END) AS total_par,
    AVG(CASE WHEN attendance_rate IS NOT NULL THEN attendance_rate ELSE NULL END) AS avg_att,
    AVG(CASE WHEN participated = TRUE AND satisfaction_score IS NOT NULL THEN satisfaction_score ELSE NULL END) AS avg_sat
  INTO 
    v_total_requests,
    v_total_assignments,
    v_total_participations,
    v_avg_attendance,
    v_avg_satisfaction
  FROM participation_history
  WHERE center_id = p_center_id 
    AND academic_year < p_academic_year;
  
  -- Càlcul base: 50 punts
  SET v_priority_score = 50.00;
  
  -- Bonus per alta assistència (fins a +15 punts)
  IF v_avg_attendance >= 95 THEN
    SET v_bonus_attendance = 15.00;
  ELSEIF v_avg_attendance >= 85 THEN
    SET v_bonus_attendance = 10.00;
  ELSEIF v_avg_attendance >= 75 THEN
    SET v_bonus_attendance = 5.00;
  END IF;
  
  -- Bonus per alta satisfacció (fins a +10 punts)
  IF v_avg_satisfaction >= 4.5 THEN
    SET v_bonus_satisfaction = 10.00;
  ELSEIF v_avg_satisfaction >= 4.0 THEN
    SET v_bonus_satisfaction = 7.00;
  ELSEIF v_avg_satisfaction >= 3.5 THEN
    SET v_bonus_satisfaction = 4.00;
  END IF;
  
  -- Penalització per baixa participació (fins a -20 punts)
  IF v_total_assignments > 0 THEN
    SET v_participation_rate = (v_total_participations / v_total_assignments) * 100;
    
    IF v_participation_rate < 50 THEN
      SET v_penalty_participation = 20.00;
    ELSEIF v_participation_rate < 70 THEN
      SET v_penalty_participation = 10.00;
    ELSEIF v_participation_rate < 85 THEN
      SET v_penalty_participation = 5.00;
    END IF;
  END IF;
  
  -- Càlcul final
  SET v_priority_score = v_priority_score + v_bonus_attendance + v_bonus_satisfaction - v_penalty_participation;
  
  -- Límits: 0-100
  IF v_priority_score < 0 THEN
    SET v_priority_score = 0;
  ELSEIF v_priority_score > 100 THEN
    SET v_priority_score = 100;
  END IF;
  
  -- Insertar o actualitzar
  INSERT INTO center_priority (
    center_id, academic_year, total_requests, total_assignments, 
    total_participations, avg_attendance_rate, avg_satisfaction,
    priority_score, bonus_attendance, bonus_satisfaction, 
    penalty_low_participation
  ) VALUES (
    p_center_id, p_academic_year, v_total_requests, v_total_assignments,
    v_total_participations, v_avg_attendance, v_avg_satisfaction,
    v_priority_score, v_bonus_attendance, v_bonus_satisfaction,
    v_penalty_participation
  )
  ON DUPLICATE KEY UPDATE
    total_requests = v_total_requests,
    total_assignments = v_total_assignments,
    total_participations = v_total_participations,
    avg_attendance_rate = v_avg_attendance,
    avg_satisfaction = v_avg_satisfaction,
    priority_score = v_priority_score,
    bonus_attendance = v_bonus_attendance,
    bonus_satisfaction = v_bonus_satisfaction,
    penalty_low_participation = v_penalty_participation;
    
END//

DELIMITER ;

-- ============================================
-- Dades de prova
-- ============================================

-- Calcular prioritats per 2025-2026 basades en històric
-- DESCOMENTAR después de que existan centros
/*
CALL sp_calculate_priority(1, '2025-2026');
CALL sp_calculate_priority(2, '2025-2026');
CALL sp_calculate_priority(3, '2025-2026');
*/

-- ============================================
-- Comentaris
-- ============================================

ALTER TABLE center_priority COMMENT = 'Prioritat calculada per a cada centre basada en historial';
