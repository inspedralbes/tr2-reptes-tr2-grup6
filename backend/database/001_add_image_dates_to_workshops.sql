-- Migration: Afegir camps image, start_date i end_date a workshops
-- Data: 2026-01-16
-- Descripció: Afegeix camp per imatges i dates de disponibilitat dels tallers

ALTER TABLE workshops 
ADD COLUMN image VARCHAR(255) NULL AFTER provider_contact,
ADD COLUMN start_date DATE NULL AFTER hours_per_day,
ADD COLUMN end_date DATE NULL AFTER start_date;
