-- database/kairos_db.sql

-- Eliminar tablas existentes (en orden inverso por foreign keys)
DROP TABLE IF EXISTS checklist_items;
DROP TABLE IF EXISTS assignacions;
DROP TABLE IF EXISTS sollicituds;
DROP TABLE IF EXISTS tallers;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS usuaris;
DROP TABLE IF EXISTS roles;

CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL -- 'admin', 'centre', 'professor'
);

CREATE TABLE usuaris (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom_complet VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    rol_id INT NOT NULL,
    codi_centre VARCHAR(20) NULL, -- Solo para centros educativos
    actiu BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rol_id) REFERENCES roles(id)
);

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL, -- 'Tecnologia', 'Ciències', 'Art'
    color VARCHAR(20) DEFAULT '#0F172A' -- Para pintar la etiqueta en el frontend
);

CREATE TABLE tallers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    descripcio TEXT,
    modalitat ENUM('A', 'B', 'C') NOT NULL, -- A=Centre ve, B=Profe va, C=Online
    durada_minuts INT DEFAULT 60,
    capacitat_max INT DEFAULT 30,
    imatge_url VARCHAR(255),
    categoria_id INT,
    estat ENUM('actiu', 'inactiu') DEFAULT 'actiu',
    FOREIGN KEY (categoria_id) REFERENCES categories(id)
);

CREATE TABLE sollicituds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    centre_id INT NOT NULL,
    taller_id INT NOT NULL,
    data_preferent DATE,
    nombre_alumnes INT,
    comentaris TEXT,
    estat ENUM('pendent', 'assignada', 'rebutjada', 'realitzada') DEFAULT 'pendent',
    data_creacio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (centre_id) REFERENCES usuaris(id),
    FOREIGN KEY (taller_id) REFERENCES tallers(id)
);

-- Tabla para el "Smart Match" y asignación de profesores
CREATE TABLE assignacions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sollicitud_id INT NOT NULL,
    professor_id INT NOT NULL, -- Referente 1 o 2
    es_principal BOOLEAN DEFAULT 1, -- Para distinguir referente principal
    data_assignacio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sollicitud_id) REFERENCES sollicituds(id),
    FOREIGN KEY (professor_id) REFERENCES usuaris(id)
);

-- Tabla para Checklists (Subida de evidencias)
CREATE TABLE checklist_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sollicitud_id INT NOT NULL,
    item_nom VARCHAR(100) NOT NULL, -- Ej: "Autorització Menors"
    completat BOOLEAN DEFAULT 0,
    fitxer_url VARCHAR(255) NULL, -- Ruta del archivo subido
    FOREIGN KEY (sollicitud_id) REFERENCES sollicituds(id)
);

-- Insertar Roles Básicos
INSERT INTO roles (nom) VALUES ('admin'), ('centre'), ('professor');

-- Insertar Usuarios (password: admin123)
INSERT INTO usuaris (nom_complet, email, password_hash, rol_id) VALUES 
('Super Admin', 'admin@kairos.cat', '$2y$10$exSIX8KrYG9UdY5.4NpTUOf9K.K9emMR5FxTGYcrdm/PhacHXmQcm', 1),
('Institut Escola Blanca', 'contacte@escolablanca.cat', '$2y$10$exSIX8KrYG9UdY5.4NpTUOf9K.K9emMR5FxTGYcrdm/PhacHXmQcm', 2),
('Albert Einstein', 'albert@kairos.cat', '$2y$10$exSIX8KrYG9UdY5.4NpTUOf9K.K9emMR5FxTGYcrdm/PhacHXmQcm', 3),
('Marie Curie', 'marie@kairos.cat', '$2y$10$exSIX8KrYG9UdY5.4NpTUOf9K.K9emMR5FxTGYcrdm/PhacHXmQcm', 3),
('Nikola Tesla', 'nikola@kairos.cat', '$2y$10$exSIX8KrYG9UdY5.4NpTUOf9K.K9emMR5FxTGYcrdm/PhacHXmQcm', 3);
