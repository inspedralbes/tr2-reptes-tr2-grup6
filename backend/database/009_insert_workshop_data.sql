-- ============================================================
-- Migració 009: Inserció de Dades de Tallers amb Temàtiques i Cursos
-- ============================================================
-- Data: 14 de Gener 2026
-- Descripció: Afegir tallers amb les temàtiques, cursos i modalitats
--             definides al document descriptiu del programa ENGINY
-- ============================================================

-- Verificar que la base de dades existeix
USE kairos_db;

-- Eliminar dades anteriors si existeixen (OPCIONAL - comentar si vols mantenir)
-- DELETE FROM workshops WHERE name IN ('Soldadura Bàsica', 'Robòtica Educativa', 'Programació Web', 'Electrònica Digital', 'Impressió 3D', 'Energies Renovables', 'Mecatrònica Bàsica', 'IoT i Sensors', 'Intel·ligència Artificial');

-- ============================================================
-- TAULA: TALLERS - INSERCIÓ DE DADES
-- ============================================================

-- Taller 1: Soldadura Bàsica
INSERT INTO workshops (name, description, modality, ambit, capacity, duration_hours, is_active, max_capacity, available_slots, instructor, location, theme, course, start_date, end_date, allowed_days, time_slots, images, created_at)
VALUES (
    'Soldadura Bàsica',
    'Aprèn els fonaments de la soldadura i les tècniques bàsiques de seguretat',
    'A',
    'Fabricació',
    15,
    20,
    1,
    15,
    15,
    'Joan Martí',
    'Laboratori de Fabricació A',
    'fabricacio',
    'eso3',
    '2026-11-11',
    '2027-01-25',
    '["Dilluns", "Dimecres", "Dijous"]',
    '["09:00-11:00", "14:00-16:00"]',
    '["https://picsum.photos/seed/ws1/800/400"]',
    CURRENT_TIMESTAMP
);

-- Taller 2: Robòtica Educativa
INSERT INTO workshops (name, description, modality, ambit, capacity, duration_hours, is_active, max_capacity, available_slots, instructor, location, theme, course, start_date, end_date, allowed_days, time_slots, images, created_at)
VALUES (
    'Robòtica Educativa',
    'Introducció a la robòtica amb kits educatius i programació bàsica',
    'B',
    'Robòtica',
    12,
    20,
    1,
    12,
    12,
    'Maria González',
    'Laboratori de Robòtica',
    'robotica',
    'eso2',
    '2026-11-15',
    '2027-02-10',
    '["Dilluns", "Dimarts", "Divendres"]',
    '["10:00-12:00", "15:00-17:00"]',
    '["https://picsum.photos/seed/ws2/800/400"]',
    CURRENT_TIMESTAMP
);

-- Taller 3: Programació Web
INSERT INTO workshops (name, description, modality, ambit, capacity, duration_hours, is_active, max_capacity, available_slots, instructor, location, theme, course, start_date, end_date, allowed_days, time_slots, images, created_at)
VALUES (
    'Programació Web',
    'Crea pàgines web amb HTML, CSS i JavaScript',
    'B',
    'Programació',
    16,
    20,
    1,
    16,
    16,
    'Pere Sanchez',
    'Aula d\'Informàtica 1',
    'programacio',
    'eso3',
    '2026-11-20',
    '2027-01-30',
    '["Dimarts", "Dijous"]',
    '["09:00-11:00"]',
    '["https://picsum.photos/seed/ws3/800/400"]',
    CURRENT_TIMESTAMP
);

-- Taller 4: Electrònica Digital
INSERT INTO workshops (name, description, modality, ambit, capacity, duration_hours, is_active, max_capacity, available_slots, instructor, location, theme, course, start_date, end_date, allowed_days, time_slots, images, created_at)
VALUES (
    'Electrònica Digital',
    'Circuits digitals, Arduino i microcontroladors',
    'A',
    'Electrònica',
    10,
    20,
    1,
    10,
    10,
    'Anna Rosell',
    'Laboratori d\'Electrònica',
    'electronica',
    'eso4',
    '2026-12-02',
    '2027-02-05',
    '["Dilluns", "Dimecres"]',
    '["14:00-16:00"]',
    '["https://picsum.photos/seed/ws4/800/400"]',
    CURRENT_TIMESTAMP
);

-- Taller 5: Impressió 3D i Disseny
INSERT INTO workshops (name, description, modality, ambit, capacity, duration_hours, is_active, max_capacity, available_slots, instructor, location, theme, course, start_date, end_date, allowed_days, time_slots, images, created_at)
VALUES (
    'Impressió 3D i Disseny',
    'Disseny 3D amb FreeCAD i impressió 3D',
    'C',
    'Disseny',
    8,
    20,
    1,
    8,
    8,
    'Laura Puig',
    'Laboratori d\'Innovació',
    'disseny3d',
    'eso3',
    '2026-11-18',
    '2027-02-08',
    '["Dijous", "Divendres"]',
    '["10:00-12:00", "14:00-16:00"]',
    '["https://picsum.photos/seed/ws5/800/400"]',
    CURRENT_TIMESTAMP
);

-- Taller 6: Energies Renovables
INSERT INTO workshops (name, description, modality, ambit, capacity, duration_hours, is_active, max_capacity, available_slots, instructor, location, theme, course, start_date, end_date, allowed_days, time_slots, images, created_at)
VALUES (
    'Energies Renovables',
    'Fonts d\'energia renovable: solar, eòlica i altres',
    'B',
    'Sostenibilitat',
    15,
    20,
    1,
    15,
    15,
    'David Ferrer',
    'Aula de Ciències',
    'energies',
    'eso2',
    '2026-12-10',
    '2027-02-10',
    '["Dimarts", "Divendres"]',
    '["09:00-11:00"]',
    '["https://picsum.photos/seed/ws6/800/400"]',
    CURRENT_TIMESTAMP
);

-- Taller 7: Mecatrònica Bàsica
INSERT INTO workshops (name, description, modality, ambit, capacity, duration_hours, is_active, max_capacity, available_slots, instructor, location, theme, course, start_date, end_date, allowed_days, time_slots, images, created_at)
VALUES (
    'Mecatrònica Bàsica',
    'Integració de mecànica, electrònica i control automàtic',
    'A',
    'Mecatrònica',
    12,
    20,
    1,
    12,
    12,
    'Carles López',
    'Laboratori de Mecatrònica',
    'mecatronica',
    'eso4',
    '2026-11-25',
    '2027-02-15',
    '["Dilluns", "Dimecres", "Divendres"]',
    '["15:00-17:00"]',
    '["https://picsum.photos/seed/ws7/800/400"]',
    CURRENT_TIMESTAMP
);

-- Taller 8: IoT i Sensors
INSERT INTO workshops (name, description, modality, ambit, capacity, duration_hours, is_active, max_capacity, available_slots, instructor, location, theme, course, start_date, end_date, allowed_days, time_slots, images, created_at)
VALUES (
    'IoT i Sensors',
    'Internet de les coses: sensors, comunicació i aplicacions',
    'B',
    'Internet of Things',
    10,
    20,
    1,
    10,
    10,
    'Sara Iglesias',
    'Aula d\'Informàtica 2',
    'iot',
    'eso3',
    '2026-12-08',
    '2027-02-12',
    '["Dimarts", "Dijous"]',
    '["10:00-12:00"]',
    '["https://picsum.photos/seed/ws8/800/400"]',
    CURRENT_TIMESTAMP
);

-- Taller 9: Intel·ligència Artificial Bàsica
INSERT INTO workshops (name, description, modality, ambit, capacity, duration_hours, is_active, max_capacity, available_slots, instructor, location, theme, course, start_date, end_date, allowed_days, time_slots, images, created_at)
VALUES (
    'Intel·ligència Artificial Bàsica',
    'Introducció a IA, machine learning i aplicacions pràctiques',
    'C',
    'Inteligència Artificial',
    14,
    20,
    1,
    14,
    14,
    'Javier Ruiz',
    'Aula d\'Informàtica 3',
    'ia',
    'eso4',
    '2026-11-22',
    '2027-02-20',
    '["Dimecres", "Divendres"]',
    '["14:00-16:00", "16:30-18:30"]',
    '["https://picsum.photos/seed/ws9/800/400"]',
    CURRENT_TIMESTAMP
);

-- ============================================================
-- RESUM DE LA INSERCIÓ
-- ============================================================
-- Total de tallers insertats: 9
-- Temàtiques cobertes: 9
--   - Robòtica (1)
--   - Programació (1)
--   - Electrònica (1)
--   - Fabricació Digital (1)
--   - Disseny 3D (1)
--   - Energies Renovables (1)
--   - Mecatrònica (1)
--   - Internet of Things (1)
--   - Intel·ligència Artificial (1)
--
-- Cursos coberts:
--   - ESO 2 (3 tallers)
--   - ESO 3 (4 tallers)
--   - ESO 4 (2 tallers)
--
-- Modalitats:
--   - A: 3 tallers
--   - B: 3 tallers
--   - C: 3 tallers
-- ============================================================

-- Verificar dades insertades
SELECT id, name, theme, course, modality, max_capacity, instructor, start_date, end_date 
FROM workshops 
WHERE theme IS NOT NULL 
ORDER BY id;
