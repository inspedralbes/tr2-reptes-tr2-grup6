-- Asegurar que la columna data existe en tallers
DROP PROCEDURE IF EXISTS upgrade_tallers_date;
DELIMITER //
CREATE PROCEDURE upgrade_tallers_date()
BEGIN
    IF NOT EXISTS(SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='kairos_db' AND TABLE_NAME='tallers' AND COLUMN_NAME='data') THEN
        ALTER TABLE tallers ADD COLUMN data DATE AFTER sector_id;
    END IF;
END //
DELIMITER ;
CALL upgrade_tallers_date();
DROP PROCEDURE upgrade_tallers_date;
