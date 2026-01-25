-- Add center_id column to users table
ALTER TABLE users ADD COLUMN center_id INT DEFAULT NULL AFTER role;
ALTER TABLE users ADD CONSTRAINT fk_users_center FOREIGN KEY (center_id) REFERENCES centers(id) ON DELETE SET NULL;
CREATE INDEX idx_user_center ON users(center_id);
