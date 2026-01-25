-- ============================================
-- Migration 008: Afegir dates de disponibilitat a tallers
-- ============================================
-- Descripció: Afegir camps start_date i end_date
--             per especificar el període de disponibilitat
--             de cada taller (ex: 11 nov - 25 gen)
-- Data: 2026-01-14
-- ============================================

USE kairos_db;

-- Afegir columnes a la taula workshops
ALTER TABLE workshops
ADD COLUMN start_date DATE NULL COMMENT 'Data d''inici de disponibilitat',
ADD COLUMN end_date DATE NULL COMMENT 'Data de finalització de disponibilitat',
ADD COLUMN max_capacity INT DEFAULT 15 COMMENT 'Capacitat màxima de l''alumnat',
ADD COLUMN available_slots INT DEFAULT 15 COMMENT 'Llocs disponibles',
ADD COLUMN instructor VARCHAR(100) NULL COMMENT 'Nom del instructor/a',
ADD COLUMN location VARCHAR(200) NULL COMMENT 'Ubicació del taller',
ADD COLUMN theme VARCHAR(100) NULL COMMENT 'Temàtica del taller (Robòtica, Programació, etc.)',
ADD COLUMN course VARCHAR(100) NULL COMMENT 'Curs destinat (ESO1, Batx1, etc.)',
ADD COLUMN allowed_days JSON NULL COMMENT 'Dies de la setmana permesos (array de strings)',
ADD COLUMN time_slots JSON NULL COMMENT 'Franges horàries disponibles',
ADD COLUMN images JSON NULL COMMENT 'URLs de imatges de la galeria';

-- Crear índexs per a millor rendiment
CREATE INDEX idx_start_date ON workshops(start_date);
CREATE INDEX idx_end_date ON workshops(end_date);
CREATE INDEX idx_theme ON workshops(theme);
CREATE INDEX idx_course ON workshops(course);

-- Actualitzar els tallers existents amb dates per defecte (opcional)
-- Podeu descomentar aquestes línies si ho desitgeu
-- UPDATE workshops SET start_date = CURDATE() WHERE start_date IS NULL;
-- UPDATE workshops SET end_date = DATE_ADD(CURDATE(), INTERVAL 3 MONTH) WHERE end_date IS NULL;

-- Afegir índex compòs per a cerques per data i tema
CREATE INDEX idx_workshop_period ON workshops(start_date, end_date, theme);

COMMIT;
