CREATE DATABASE IF NOT EXISTS api_electrofix;
USE api_electrofix;
 
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
 
-- Inserción de un registro de prueba (password: 123456)
INSERT INTO usuarios (nombre, email, password) VALUES
('Estudiante', 'estudiante@example.com', '123456');

-- Reemplazá el hash por el que generaste vos
INSERT INTO usuarios (nombre, email, password)
VALUES ('Admin', 'admin@example.com', '$2y$10$vvEF8KGcnEqyRYLZpfRunujnwk6x85AAtmYqOioMq.6QevymOsIxW');
