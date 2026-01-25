-- ============================================
-- Migration 007: Galeria Pública d'Imatges
-- ============================================
-- Descripció: Sistema de galeria per compartir fotos
--             dels tallers de forma pública
-- Data: 2026-01-14
-- ============================================

-- Taula: workshop_gallery
-- Propòsit: Emmagatzemar imatges de tallers amb metadades
CREATE TABLE IF NOT EXISTS workshop_gallery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workshop_id INT NOT NULL,
    assignment_id INT NULL,
    center_id INT NULL,
    
    -- Arxiu
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255),
    file_path VARCHAR(500) NOT NULL,
    file_size INT NULL,
    mime_type VARCHAR(100),
    width INT NULL,
    height INT NULL,
    
    -- Metadades
    title VARCHAR(200),
    description TEXT,
    alt_text VARCHAR(255),
    caption TEXT,
    taken_at TIMESTAMP NULL,
    
    -- Autor
    uploaded_by_user_id INT NULL,
    photographer_name VARCHAR(100),
    
    -- Visibilitat
    is_public BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    is_approved BOOLEAN DEFAULT FALSE,
    approved_by_admin_id INT NULL,
    approved_at TIMESTAMP NULL,
    
    -- Estadístiques
    view_count INT DEFAULT 0,
    download_count INT DEFAULT 0,
    
    -- Tags i categorització
    tags JSON,
    academic_year VARCHAR(10),
    
    -- Ordre
    display_order INT DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (workshop_id) REFERENCES workshops(id) ON DELETE CASCADE,
    FOREIGN KEY (assignment_id) REFERENCES workshop_assignments(id) ON DELETE SET NULL,
    FOREIGN KEY (center_id) REFERENCES centers(id) ON DELETE SET NULL,
    FOREIGN KEY (uploaded_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by_admin_id) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_workshop (workshop_id),
    INDEX idx_center (center_id),
    INDEX idx_is_public (is_public),
    INDEX idx_is_featured (is_featured),
    INDEX idx_is_approved (is_approved),
    INDEX idx_academic_year (academic_year),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Taula: gallery_albums
-- Propòsit: Agrupar imatges en àlbums temàtics
CREATE TABLE IF NOT EXISTS gallery_albums (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(200) UNIQUE NOT NULL,
    description TEXT,
    cover_image_id INT NULL,
    
    -- Visibilitat
    is_public BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    
    -- Dates
    start_date DATE NULL,
    end_date DATE NULL,
    academic_year VARCHAR(10),
    
    -- Estadístiques
    image_count INT DEFAULT 0,
    view_count INT DEFAULT 0,
    
    -- Metadades
    created_by_user_id INT NULL,
    display_order INT DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (cover_image_id) REFERENCES workshop_gallery(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_is_public (is_public),
    INDEX idx_academic_year (academic_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Taula: gallery_album_images
-- Propòsit: Relació N:M entre àlbums i imatges
CREATE TABLE IF NOT EXISTS gallery_album_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    album_id INT NOT NULL,
    image_id INT NOT NULL,
    display_order INT DEFAULT 0,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (album_id) REFERENCES gallery_albums(id) ON DELETE CASCADE,
    FOREIGN KEY (image_id) REFERENCES workshop_gallery(id) ON DELETE CASCADE,
    UNIQUE KEY unique_album_image (album_id, image_id),
    INDEX idx_album (album_id),
    INDEX idx_image (image_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Trigger: Actualitzar comptador d'imatges d'àlbum
DELIMITER //
DROP TRIGGER IF EXISTS trg_update_album_image_count_insert//
CREATE TRIGGER trg_update_album_image_count_insert
AFTER INSERT ON gallery_album_images
FOR EACH ROW
BEGIN
    UPDATE gallery_albums
    SET image_count = (
        SELECT COUNT(*) 
        FROM gallery_album_images 
        WHERE album_id = NEW.album_id
    )
    WHERE id = NEW.album_id;
END//
DELIMITER ;

DELIMITER //
DROP TRIGGER IF EXISTS trg_update_album_image_count_delete//
CREATE TRIGGER trg_update_album_image_count_delete
AFTER DELETE ON gallery_album_images
FOR EACH ROW
BEGIN
    UPDATE gallery_albums
    SET image_count = (
        SELECT COUNT(*) 
        FROM gallery_album_images 
        WHERE album_id = OLD.album_id
    )
    WHERE id = OLD.album_id;
END//
DELIMITER ;

-- Vista: v_public_gallery
-- Propòsit: Imatges públiques amb informació enriquida
CREATE OR REPLACE VIEW v_public_gallery AS
SELECT 
    wg.id,
    wg.filename,
    wg.file_path,
    wg.title,
    wg.description,
    wg.alt_text,
    wg.caption,
    wg.taken_at,
    wg.photographer_name,
    wg.width,
    wg.height,
    wg.view_count,
    wg.is_featured,
    wg.academic_year,
    wg.created_at,
    w.id AS workshop_id,
    w.name AS workshop_name,
    c.id AS center_id,
    c.name AS center_name,
    u.full_name AS uploaded_by
FROM workshop_gallery wg
LEFT JOIN workshops w ON wg.workshop_id = w.id
LEFT JOIN centers c ON wg.center_id = c.id
LEFT JOIN users u ON wg.uploaded_by_user_id = u.id
WHERE wg.is_public = TRUE AND wg.is_approved = TRUE
ORDER BY wg.is_featured DESC, wg.created_at DESC;

-- Vista: v_gallery_statistics
-- Propòsit: Estadístiques de galeria per taller
CREATE OR REPLACE VIEW v_gallery_statistics AS
SELECT 
    w.id AS workshop_id,
    w.name AS workshop_name,
    COUNT(wg.id) AS total_images,
    SUM(wg.view_count) AS total_views,
    SUM(wg.download_count) AS total_downloads,
    MAX(wg.created_at) AS last_upload,
    COUNT(DISTINCT wg.center_id) AS contributing_centers
FROM workshops w
LEFT JOIN workshop_gallery wg ON wg.workshop_id = w.id AND wg.is_public = TRUE
GROUP BY w.id, w.name;

-- Stored Procedure: Incrementar comptador de vistes
DELIMITER //
DROP PROCEDURE IF EXISTS sp_increment_image_views//
CREATE PROCEDURE sp_increment_image_views(
    IN p_image_id INT
)
BEGIN
    UPDATE workshop_gallery
    SET view_count = view_count + 1
    WHERE id = p_image_id;
END//
DELIMITER ;

-- Stored Procedure: Aprovar imatge
DELIMITER //
DROP PROCEDURE IF EXISTS sp_approve_gallery_image//
CREATE PROCEDURE sp_approve_gallery_image(
    IN p_image_id INT,
    IN p_admin_id INT
)
BEGIN
    UPDATE workshop_gallery
    SET is_approved = TRUE,
        approved_by_admin_id = p_admin_id,
        approved_at = NOW()
    WHERE id = p_image_id;
    
    SELECT 'Imatge aprovada correctament' AS message;
END//
DELIMITER ;

-- Dades mock: Àlbums
-- DESCOMENTAR después de que existan workshops y centers con IDs adecuados
/*
INSERT INTO gallery_albums (title, slug, description, is_public, is_featured, academic_year, image_count) VALUES
('Robòtica Educativa 2025', 'robotica-2025', 'Col·lecció d\'imatges dels tallers de robòtica durant el curs 2024-2025', TRUE, TRUE, '2024-2025', 5),
('Impressió 3D', 'impressio-3d', 'Projectes realitzats amb impressores 3D', TRUE, FALSE, '2024-2025', 3),
('Programació per a Joves', 'programacio', 'Sessions de programació amb Scratch i Python', TRUE, TRUE, '2024-2025', 4),
('STEM per a Petits', 'stem-petits', 'Activitats STEM per a educació infantil i primària', TRUE, FALSE, '2024-2025', 6);

-- Dades mock: Imatges de galeria
INSERT INTO workshop_gallery (
    workshop_id, center_id, filename, original_filename, file_path, 
    title, description, caption, photographer_name,
    is_public, is_featured, is_approved, academic_year,
    width, height, mime_type, file_size, view_count
) VALUES
(1, 1, 'robotica_01.jpg', 'taller_robots.jpg', '/uploads/gallery/2025/robotica_01.jpg',
 'Muntatge de Robot mBot', 'Alumnes muntant el seu primer robot mBot durant el taller de robòtica educativa.',
 'Els estudiants de 4t ESO treballen en equip', 'Maria Garcia',
 TRUE, TRUE, TRUE, '2024-2025', 1920, 1080, 'image/jpeg', 245680, 127),

(1, 1, 'robotica_02.jpg', 'programacio_robot.jpg', '/uploads/gallery/2025/robotica_02.jpg',
 'Programació amb Scratch', 'Sessió de programació visual amb Scratch per controlar robots.',
 'Aprenent a programar de forma visual', 'Maria Garcia',
 TRUE, TRUE, TRUE, '2024-2025', 1920, 1080, 'image/jpeg', 198750, 89),

(2, 2, 'impressio3d_01.jpg', 'disseny_3d.jpg', '/uploads/gallery/2025/impressio3d_01.jpg',
 'Disseny 3D amb TinkerCAD', 'Alumnes creant els seus propis dissenys amb TinkerCAD.',
 'Creativitat i tecnologia en acció', 'Joan Puig',
 TRUE, FALSE, TRUE, '2024-2025', 1920, 1080, 'image/jpeg', 312450, 56),

(2, 2, 'impressio3d_02.jpg', 'impressora_funcionant.jpg', '/uploads/gallery/2025/impressio3d_02.jpg',
 'Impressora 3D en Funcionament', 'La impressora 3D imprimint un dels projectes dels alumnes.',
 'De la pantalla a la realitat', 'Joan Puig',
 TRUE, TRUE, TRUE, '2024-2025', 1920, 1080, 'image/jpeg', 278900, 93),

(3, 3, 'programacio_01.jpg', 'scratch_project.jpg', '/uploads/gallery/2025/programacio_01.jpg',
 'Projecte Scratch: Joc Espacial', 'Alumnes presentant el seu joc espacial creat amb Scratch.',
 'Programació visual per a joves', 'Laura Martínez',
 TRUE, TRUE, TRUE, '2024-2025', 1920, 1080, 'image/jpeg', 189320, 142),

(3, 3, 'programacio_02.jpg', 'python_code.jpg', '/uploads/gallery/2025/programacio_02.jpg',
 'Introducció a Python', 'Primera presa de contacte amb el llenguatge Python.',
 'Del bloc visual al codi real', 'Laura Martínez',
 TRUE, FALSE, TRUE, '2024-2025', 1920, 1080, 'image/jpeg', 156780, 71),

(4, 1, 'stem_01.jpg', 'experiments.jpg', '/uploads/gallery/2025/stem_01.jpg',
 'Experiments Científics', 'Alumnes de primària realitzant experiments de ciències.',
 'La ciència és divertida', 'Anna Soler',
 TRUE, FALSE, TRUE, '2024-2025', 1920, 1080, 'image/jpeg', 223450, 48),

(5, 2, 'electronica_01.jpg', 'circuits.jpg', '/uploads/gallery/2025/electronica_01.jpg',
 'Circuits Electrònics amb Arduino', 'Muntatge de circuits electrònics amb plaques Arduino.',
 'Aprenent electrònica de forma pràctica', 'Pere Vidal',
 TRUE, TRUE, TRUE, '2024-2025', 1920, 1080, 'image/jpeg', 267890, 115);

-- Relacions àlbum-imatges
INSERT INTO gallery_album_images (album_id, image_id, display_order) VALUES
(1, 1, 1),
(1, 2, 2),
(2, 3, 1),
(2, 4, 2),
(3, 5, 1),
(3, 6, 2),
(4, 7, 1);

-- Actualitzar cover_image_id dels àlbums
UPDATE gallery_albums SET cover_image_id = 1 WHERE id = 1;
UPDATE gallery_albums SET cover_image_id = 3 WHERE id = 2;
UPDATE gallery_albums SET cover_image_id = 5 WHERE id = 3;
UPDATE gallery_albums SET cover_image_id = 7 WHERE id = 4;
*/

-- ============================================
-- Fi Migration 007
-- ============================================
