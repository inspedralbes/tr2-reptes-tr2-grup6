-- Semilla de datos inicial (Seed Data)
-- Necesario para que las migraciones de historial y alumnos funcionen

-- 1. Insertar Categorias (si no existen)
INSERT IGNORE INTO categories (id, nom, color) VALUES
(1, 'Tecnologia', '#0F172A'),
(2, 'Sostenibilitat', '#10B981'),
(3, 'Fabricació', '#F59E0B');

-- 2. Insertar Talleres Base (si no existe id 1, 2, 3...)
INSERT IGNORE INTO tallers (id, nom, descripcio, modalitat, categoria_id) VALUES
(1, 'Robòtica Educativa', 'Introducció a la robòtica amb Lego Spike', 'B', 1),
(2, 'Disseny 3D', 'Creació de peces 3D amb Tinkercad', 'B', 1),
(3, 'Horts Urbans', 'Gestió de cultius sostenibles', 'A', 2),
(4, 'Energies Renovables', 'Taller de plaques solars i eòlica', 'B', 2),
(5, 'Tall Fusta', 'Iniciació al tall làser', 'A', 3),
(6, 'Impressió 3D', 'Manteniment i ús d''impressores 3D', 'A', 3);

-- 3. Insertar Solicitud Base (para alumnos_inscrits)
-- Asume que existen usuarios 2 (Escola) y talleres 1
INSERT IGNORE INTO sollicituds (id, centre_id, taller_id, data_preferent, nombre_alumnes, estat) VALUES
(1, 2, 1, '2025-02-15', 25, 'pendent');
