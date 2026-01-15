-- FASE 1: SISTEMA DE NOTIFICACIONS
-- Migració per crear la taula de notificacions

-- Crear taula de notificacions
CREATE TABLE IF NOT EXISTS notificacions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuari_id INT NOT NULL,
    tipus ENUM('assignacio', 'rebuig', 'recordatori', 'canvi', 'checklist') NOT NULL,
    titol VARCHAR(200) NOT NULL,
    missatge TEXT,
    llegida BOOLEAN DEFAULT 0,
    sollicitud_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuari_id) REFERENCES usuaris(id) ON DELETE CASCADE,
    FOREIGN KEY (sollicitud_id) REFERENCES sollicituds(id) ON DELETE SET NULL,
    INDEX idx_usuari_llegida (usuari_id, llegida),
    INDEX idx_created (created_at DESC)
);

-- Insertar notificaciones de ejemplo para testing
INSERT INTO notificacions (usuari_id, tipus, titol, missatge, sollicitud_id) VALUES
(2, 'assignacio', 'Taller Assignat!', 'El taller "Robòtica Educativa" ha estat assignat al vostre centre.', NULL),
(3, 'recordatori', 'Recordatori de Taller', 'Recordatori: Tens un taller programat per demà a les 10:00h.', NULL),
(2, 'checklist', 'Checklist Pendent', 'Si us plau, completa el checklist del taller assignat.', NULL);

-- Verificació
SELECT 'Taula notificacions creada correctament' as status;
SELECT COUNT(*) as total_notificacions FROM notificacions;
