-- FASE 4: Execució i tancament (assignacions)
-- Estat d'execució i observacions del professor

ALTER TABLE assignacions
ADD COLUMN estat_execucio ENUM('programada', 'realitzada', 'cancel·lada', 'absencia') 
DEFAULT 'programada' AFTER data_assignacio;

ALTER TABLE assignacions
ADD COLUMN observacions_prof TEXT NULL AFTER estat_execucio;
