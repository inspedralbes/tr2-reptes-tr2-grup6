-- ============================================================
-- Seed: Usuaris de prova per KAIROS
-- ============================================================

USE kairos_db;

-- Inserir usuaris de prova (password: "Admin1234" per tots)
-- Hash bcrypt de "Admin1234": $2y$10$nzmLpe05CMnP564udtus8.yCXPOnqr5Ku1ijhpH9a/qs8Deoskeyi

INSERT INTO users (email, password_hash, role, full_name, phone, is_active) VALUES
('admin@kairos.cat', '$2y$10$nzmLpe05CMnP564udtus8.yCXPOnqr5Ku1ijhpH9a/qs8Deoskeyi', 'admin', 'Administrador KAIROS', '600000001', 1),
('coord@escola.cat', '$2y$10$nzmLpe05CMnP564udtus8.yCXPOnqr5Ku1ijhpH9a/qs8Deoskeyi', 'center_coord', 'Coordinador Centre', '600000002', 1),
('profe@escola.cat', '$2y$10$nzmLpe05CMnP564udtus8.yCXPOnqr5Ku1ijhpH9a/qs8Deoskeyi', 'teacher', 'Professor Example', '600000003', 1),
('test@test.com', '$2y$10$nzmLpe05CMnP564udtus8.yCXPOnqr5Ku1ijhpH9a/qs8Deoskeyi', 'teacher', 'Usuari Test', '600000004', 1)
ON DUPLICATE KEY UPDATE email=email;

-- Inserir un centre de prova
INSERT INTO centers (name, code, city, address, postal_code, phone, contact_email, is_active) VALUES
('Institut de Prova', 'TEST001', 'Barcelona', 'Carrer de Prova 123', '08001', '931234567', 'info@test.cat', 1)
ON DUPLICATE KEY UPDATE code=code;

SELECT 'Users created successfully!' as Status;
