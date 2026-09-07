-- =========================================================
-- BALNEARIO - SISTEMA DE RESERVAS DE CARPAS
-- Base de datos para MySQL / MariaDB en XAMPP
-- =========================================================

CREATE DATABASE IF NOT EXISTS balneario_reservas
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE balneario_reservas;

DROP TABLE IF EXISTS reservas;
DROP TABLE IF EXISTS carpas;
DROP TABLE IF EXISTS clientes;
DROP TABLE IF EXISTS administradores;

CREATE TABLE administradores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    dni VARCHAR(30) NOT NULL UNIQUE,
    telefono VARCHAR(40) DEFAULT '',
    email VARCHAR(120) DEFAULT '',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE carpas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero VARCHAR(20) NOT NULL UNIQUE,
    sector VARCHAR(60) NOT NULL,
    fila INT NOT NULL DEFAULT 1,
    columna INT NOT NULL DEFAULT 1,
    pos_x INT NOT NULL DEFAULT 40,
    pos_y INT NOT NULL DEFAULT 40,
    precio_diario DECIMAL(10,2) NOT NULL DEFAULT 12000.00,
    precio_quincenal DECIMAL(10,2) NOT NULL DEFAULT 150000.00,
    precio_mensual DECIMAL(10,2) NOT NULL DEFAULT 250000.00,
    precio_temporada DECIMAL(10,2) NOT NULL DEFAULT 700000.00,
    activa TINYINT(1) NOT NULL DEFAULT 1,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_carpas_sector (sector)
);

CREATE TABLE reservas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    carpa_id INT NOT NULL,
    cliente_id INT NOT NULL,
    tipo_reserva ENUM('diaria','quincenal','mensual','temporada') NOT NULL DEFAULT 'diaria',
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    estado ENUM('pendiente','confirmada','cancelada','finalizada') NOT NULL DEFAULT 'pendiente',
    costo_total DECIMAL(12,2) NOT NULL DEFAULT 0,
    observaciones TEXT,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_reserva_carpa FOREIGN KEY (carpa_id) REFERENCES carpas(id) ON DELETE RESTRICT,
    CONSTRAINT fk_reserva_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT,
    INDEX idx_reserva_fechas (fecha_inicio, fecha_fin),
    INDEX idx_reserva_estado (estado)
);

-- Usuario inicial:
-- admin / admin123
-- Hash compatible con PHP password_verify().
INSERT INTO administradores (usuario, password, nombre)
VALUES (
    'admin',
    '$2y$12$EeQ5Op6rcV1IKWglICUsRu1HpeOy6Y/4PF3D0t/GkNdUzQ4vLb.ym',
    'Administrador'
);

INSERT INTO clientes (nombre, dni, telefono, email) VALUES
('Juan Pérez', '30111222', '11-5555-1111', 'juan@example.com'),
('María González', '28999888', '11-5555-2222', 'maria@example.com');

INSERT INTO carpas
(numero, sector, fila, columna, pos_x, pos_y, precio_diario, precio_quincenal, precio_mensual, precio_temporada)
VALUES
('01','Sector A',1,1,80,230,12000,150000,250000,700000),
('02','Sector A',1,2,190,230,12000,150000,250000,700000),
('03','Sector A',1,3,300,230,12500,155000,260000,720000),
('04','Sector A',1,4,410,230,12500,155000,260000,720000),
('05','Sector B',2,1,80,330,13000,160000,270000,740000),
('06','Sector B',2,2,190,330,13000,160000,270000,740000),
('07','Sector B',2,3,300,330,13500,165000,280000,760000),
('08','Sector B',2,4,410,330,13500,165000,280000,760000),
('09','Sector C',3,1,80,430,14000,170000,290000,780000),
('10','Sector C',3,2,190,430,14000,170000,290000,780000),
('11','Sector C',3,3,300,430,14500,175000,300000,800000),
('12','Sector C',3,4,410,430,14500,175000,300000,800000);

-- Reservas de ejemplo:
INSERT INTO reservas (carpa_id, cliente_id, tipo_reserva, fecha_inicio, fecha_fin, estado, costo_total, observaciones)
VALUES
(1, 1, 'diaria', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'confirmada', 36000, 'Reserva de ejemplo'),
(2, 2, 'diaria', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 4 DAY), 'pendiente', 60000, 'Pendiente de pago');
