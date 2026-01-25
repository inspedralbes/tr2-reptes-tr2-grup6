-- ============================================================
-- Esquema de Base de Dades - KAIROS (Gestió Programa ENGINY)
-- ============================================================
-- Data: Gener 2026
-- Versió: 1.0.0
-- ============================================================

-- Seleccionar la base de dades
USE kairos_db;

-- ============================================================
-- 1. TAULA: USUARIS I ROLS
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'center_coord', 'teacher') DEFAULT 'teacher',
    full_name VARCHAR(100),
    phone VARCHAR(20),
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
);

-- ============================================================
-- 2. TAULA: CENTRES EDUCATIUS
-- ============================================================
CREATE TABLE IF NOT EXISTS centers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    code VARCHAR(20) UNIQUE NOT NULL,
    city VARCHAR(100),
    address VARCHAR(255),
    postal_code VARCHAR(10),
    phone VARCHAR(20),
    contact_email VARCHAR(100),
    coordinator_id INT,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (coordinator_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_code (code),
    INDEX idx_city (city)
);

-- ============================================================
-- 3. TAULA: CATÀLEG - TALLERS
-- ============================================================
CREATE TABLE IF NOT EXISTS workshops (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    modality ENUM('A', 'B', 'C') NOT NULL,
    capacity INT NOT NULL DEFAULT 16,
    ambit VARCHAR(50) COMMENT 'Àmbit: Artístic, Industrial, etc.',
    duration_hours INT DEFAULT 20,
    duration_days INT DEFAULT 10,
    hours_per_day INT DEFAULT 2,
    provider_name VARCHAR(100),
    provider_contact VARCHAR(100),
    modality_color VARCHAR(20) COMMENT 'Codi de color per a visualització',
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_modality (modality),
    INDEX idx_active (is_active),
    INDEX idx_ambit (ambit)
);

-- ============================================================
-- 4. TAULA: CURS ACADÈMIC / PERÍODES
-- ============================================================
CREATE TABLE IF NOT EXISTS academic_years (
    id INT AUTO_INCREMENT PRIMARY KEY,
    year_code VARCHAR(10) UNIQUE NOT NULL COMMENT '2025-2026',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_year_code (year_code),
    INDEX idx_active (is_active)
);

-- ============================================================
-- 5. TAULA: TRIMESTRES/PERÍODES
-- ============================================================
CREATE TABLE IF NOT EXISTS periods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    academic_year_id INT NOT NULL,
    name VARCHAR(50) COMMENT 'P1, P2, P3, etc.',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE,
    INDEX idx_academic_year (academic_year_id)
);

-- ============================================================
-- 6. TAULA: SOL·LICITUDS (Fase 2 - Llista de Desitjos)
-- ============================================================
CREATE TABLE IF NOT EXISTS requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    center_id INT NOT NULL,
    workshop_id INT NOT NULL,
    period_id INT NOT NULL,
    num_students INT NOT NULL,
    priority_level INT DEFAULT 0 COMMENT 'Prioritat assignada pel centre',
    status ENUM('pending', 'assigned', 'rejected', 'cancelled') DEFAULT 'pending',
    rejection_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (center_id) REFERENCES centers(id) ON DELETE CASCADE,
    FOREIGN KEY (workshop_id) REFERENCES workshops(id) ON DELETE RESTRICT,
    FOREIGN KEY (period_id) REFERENCES periods(id) ON DELETE CASCADE,
    INDEX idx_center (center_id),
    INDEX idx_workshop (workshop_id),
    INDEX idx_status (status),
    INDEX idx_period (period_id),
    UNIQUE KEY unique_request (center_id, workshop_id, period_id)
);

-- ============================================================
-- 7. TAULA: ASSIGNACIONS (Fase 3 - La Concordança)
-- ============================================================
CREATE TABLE IF NOT EXISTS allocations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    assigned_teacher_id INT,
    assigned_center_id INT NOT NULL,
    assigned_workshop_id INT NOT NULL,
    status ENUM('provisional', 'confirmed', 'completed', 'cancelled') DEFAULT 'provisional',
    algorithm_priority_score DECIMAL(8, 2),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_teacher_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_center_id) REFERENCES centers(id) ON DELETE RESTRICT,
    FOREIGN KEY (assigned_workshop_id) REFERENCES workshops(id) ON DELETE RESTRICT,
    INDEX idx_request (request_id),
    INDEX idx_teacher (assigned_teacher_id),
    INDEX idx_status (status)
);

-- ============================================================
-- 8. TAULA: ASSIGNACIONS DE TALLERS (Alternativa a allocations)
-- ============================================================
CREATE TABLE IF NOT EXISTS workshop_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workshop_id INT NOT NULL,
    center_id INT NOT NULL,
    assigned_teacher_id INT,
    status ENUM('provisional', 'confirmed', 'completed', 'cancelled') DEFAULT 'provisional',
    assignment_date DATE,
    start_date DATE,
    end_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (workshop_id) REFERENCES workshops(id) ON DELETE CASCADE,
    FOREIGN KEY (center_id) REFERENCES centers(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_teacher_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_workshop (workshop_id),
    INDEX idx_center (center_id),
    INDEX idx_status (status),
    INDEX idx_assignment_date (assignment_date)
);

-- ============================================================
-- 9. TAULA: SLOTS DE TALLERS (Fase 4 - Calendari)
-- ============================================================
CREATE TABLE IF NOT EXISTS workshop_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workshop_id INT NOT NULL,
    allocation_id INT,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    is_booked BOOLEAN DEFAULT 0,
    booked_by_user_id INT,
    booked_at TIMESTAMP NULL,
    locked_by_user_id INT COMMENT 'Usuari que ha blocat el slot (temps real)',
    locked_at TIMESTAMP NULL,
    lock_expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (workshop_id) REFERENCES workshops(id) ON DELETE CASCADE,
    FOREIGN KEY (allocation_id) REFERENCES allocations(id) ON DELETE SET NULL,
    FOREIGN KEY (booked_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (locked_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_workshop (workshop_id),
    INDEX idx_allocation (allocation_id),
    INDEX idx_booked (is_booked),
    INDEX idx_start_time (start_time)
);

-- ============================================================
-- 10. TAULA: HISTÒRIC D'ÚS (Per ponderació de prioritat)
-- ============================================================
CREATE TABLE IF NOT EXISTS workshop_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    center_id INT NOT NULL,
    workshop_id INT NOT NULL,
    period_id INT NOT NULL,
    allocation_id INT,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (center_id) REFERENCES centers(id) ON DELETE CASCADE,
    FOREIGN KEY (workshop_id) REFERENCES workshops(id) ON DELETE RESTRICT,
    FOREIGN KEY (period_id) REFERENCES periods(id) ON DELETE CASCADE,
    FOREIGN KEY (allocation_id) REFERENCES allocations(id) ON DELETE SET NULL,
    INDEX idx_center (center_id),
    INDEX idx_workshop (workshop_id),
    UNIQUE KEY unique_history (center_id, workshop_id, period_id)
);

-- ============================================================
-- 11. TAULA: ENQUESTES / AVALUACIÓ
-- ============================================================
CREATE TABLE IF NOT EXISTS surveys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    allocation_id INT NOT NULL,
    respondent_user_id INT,
    respondent_role ENUM('student', 'teacher', 'center') DEFAULT 'student',
    overall_score INT CHECK (overall_score >= 1 AND overall_score <= 5),
    content_score INT CHECK (content_score >= 1 AND content_score <= 5),
    organization_score INT CHECK (organization_score >= 1 AND organization_score <= 5),
    recommendation_score INT CHECK (recommendation_score >= 1 AND recommendation_score <= 5),
    comments TEXT,
    is_completed BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    submitted_at TIMESTAMP NULL,
    FOREIGN KEY (allocation_id) REFERENCES allocations(id) ON DELETE CASCADE,
    FOREIGN KEY (respondent_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_allocation (allocation_id),
    INDEX idx_role (respondent_role)
);

-- ============================================================
-- 11. TAULA: LOG D'ACTIVITAT (Audit Trail)
-- ============================================================
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50),
    entity_id INT,
    old_value JSON,
    new_value JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
);

-- ============================================================
-- 12. TAULA: NOTIFICACIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    notification_type ENUM('info', 'warning', 'error', 'success') DEFAULT 'info',
    related_entity_type VARCHAR(50),
    related_entity_id INT,
    is_read BOOLEAN DEFAULT 0,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_read (is_read),
    INDEX idx_created (created_at)
);

-- ============================================================
-- INSERTS INICIALS - DADES DE PROVA
-- ============================================================

-- 1. Curs Acadèmic
INSERT INTO academic_years (year_code, start_date, end_date, is_active) 
VALUES ('2025-2026', '2025-09-01', '2026-06-30', 1);

-- 2. Períodes (Trimestres)
INSERT INTO periods (academic_year_id, name, start_date, end_date) 
VALUES 
    (1, 'P1', '2025-09-01', '2025-11-30'),
    (1, 'P2', '2025-12-01', '2026-03-31'),
    (1, 'P3', '2026-04-01', '2026-06-30');

-- 3. Super Admin
INSERT INTO users (email, password_hash, role, full_name, is_active) 
VALUES ('admin@kairos.cat', '$2y$10$V6I0P6G6I0P6G6I0P6G6I0P6G6I0P6G6I0P6G6I0P6G6I0P6G6I0P6', 'admin', 'Admin KAIROS', 1);

-- 4. Tallers de Mostra
INSERT INTO workshops (name, description, modality, capacity, ambit, duration_hours, provider_name, is_active) 
VALUES 
    ('Circ i Oficis', 'Descobreix les arts circenses i oficis tradicionals.', 'A', 16, 'Artístic', 20, 'Empresa Circ XYZ', 1),
    ('Robòtica Avançada', 'Aprèn fonaments de robòtica i programació.', 'B', 20, 'Tecnologia', 20, 'Centre de Tecnologia', 1),
    ('Ofici Compartit: Fusteria', 'Coneixements de fusteria per a alumnes especialitzats.', 'C', 12, 'Industrial', 30, 'Taller Fusteria ABC', 1);

-- ============================================================
-- FI DE L'ESQUEMA
-- ============================================================
-- Data de creació: Gener 2026
-- Versió: 1.0.0
-- Status: ✅ COMPLET
