-- FASE 2: ACTUALIZAR TABLA SOLICITUDES
-- Añadir campos pedagógicos para el nuevo modelo de inscripción

-- Actualizamos la tabla de solicitudes para el nuevo modelo
ALTER TABLE sollicituds
ADD COLUMN curs_grup VARCHAR(50) NULL COMMENT 'Ej: 3r ESO A' AFTER nombre_alumnes;

ALTER TABLE sollicituds
ADD COLUMN necessitats_especifiques TEXT NULL COMMENT 'Alumnes amb NEE o mobilitat reduïda' AFTER curs_grup;

ALTER TABLE sollicituds
ADD COLUMN preferencia_dates TEXT NULL COMMENT 'Ex: Preferiblement dimarts o primer trimestre' AFTER data_preferent;

-- Verificación: Ver estructura actualizada
DESCRIBE sollicituds;

-- Verificación: Ver solicitudes existentes con nuevos campos
SELECT id, centre_id, taller_id, nombre_alumnes, curs_grup, preferencia_dates, necessitats_especifiques, estat 
FROM sollicituds 
ORDER BY data_creacio DESC 
LIMIT 5;
