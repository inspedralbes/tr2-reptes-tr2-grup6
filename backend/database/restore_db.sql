-- ============================================================
-- RESTAURACIÓ COMPLETA DE LA BD KAIROS
-- ============================================================
-- Script que aplica totes les migracions en ordre

-- Crear la BD si no existeix
-- Neteja inicial
DROP DATABASE IF EXISTS kairos_db;
CREATE DATABASE IF NOT EXISTS kairos_db;
USE kairos_db;

-- 1. Esquema base
SOURCE /docker-entrypoint-initdb.d/000_schema.sql;

-- 2. Migracions en ordre
-- SOURCE /docker-entrypoint-initdb.d/001_add_image_dates_to_workshops.sql; -- CONFLICTE amb 008
-- SOURCE /docker-entrypoint-initdb.d/001_add_image_to_workshops.sql; -- CONFLICTE amb 008
-- 002 sembla que no existeix en la llista actual
SOURCE /docker-entrypoint-initdb.d/003_participation_history.sql;
SOURCE /docker-entrypoint-initdb.d/004_center_priority.sql;
SOURCE /docker-entrypoint-initdb.d/005_qr_attendance.sql;
SOURCE /docker-entrypoint-initdb.d/006_feedback_system.sql;
SOURCE /docker-entrypoint-initdb.d/007_gallery_system.sql;
SOURCE /docker-entrypoint-initdb.d/008_add_workshop_dates.sql;

-- 3. Dades de tallers (usem la versió arreglada en lloc de la 009 original)
SOURCE /docker-entrypoint-initdb.d/repopulate_workshops_fixed.sql;

-- 4. Altres estructures i dades
SOURCE /docker-entrypoint-initdb.d/009a_add_category_hours.sql;
SOURCE /docker-entrypoint-initdb.d/010_seed_users.sql;
SOURCE /docker-entrypoint-initdb.d/011_teachers_table.sql;
SOURCE /docker-entrypoint-initdb.d/012_phases_table.sql;
SOURCE /docker-entrypoint-initdb.d/013_fix_phases_utf8.sql;
SOURCE /docker-entrypoint-initdb.d/014_fix_email_constraint.sql;
SOURCE /docker-entrypoint-initdb.d/015_center_requests_table.sql;
SOURCE /docker-entrypoint-initdb.d/016_add_teacher_details.sql;
