-- Fix email constraint to allow NULL values
-- This allows creating coordinators without email initially

ALTER TABLE users 
MODIFY email VARCHAR(100) UNIQUE NULL;

-- Remove duplicate empty email entries
DELETE FROM users WHERE email = '' OR email IS NULL;
