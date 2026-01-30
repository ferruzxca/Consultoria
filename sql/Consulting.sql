-- ============================================================
-- Base de datos: consulting
-- Motor: MySQL 8.x (InnoDB)
-- Status: 1 = Activo, 0 = Inactivo
-- ============================================================

DROP DATABASE IF EXISTS consulting;
CREATE DATABASE consulting
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_0900_ai_ci;

USE consulting;

-- ============================================================
-- TABLA: categoria
-- ============================================================
DROP TABLE IF EXISTS categoria;
CREATE TABLE categoria (
  id_categoria INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(100) NOT NULL,
  status TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id_categoria),
  UNIQUE KEY uq_categoria_nombre (nombre)
) ENGINE=InnoDB;

-- ============================================================
-- TABLA: servicios
-- ============================================================
DROP TABLE IF EXISTS servicios;
CREATE TABLE servicios (
  id_servicio INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(120) NOT NULL,
  descripcion TEXT NULL,
  costo DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  referencia VARCHAR(120) NULL,
  calificacion DECIMAL(3,2) NOT NULL DEFAULT 0.00,
  id_categoria INT UNSIGNED NOT NULL,
  status TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id_servicio),
  KEY idx_servicios_categoria (id_categoria),
  CONSTRAINT fk_servicios_categoria
    FOREIGN KEY (id_categoria) REFERENCES categoria(id_categoria)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT chk_servicios_calificacion
    CHECK (calificacion >= 0.00 AND calificacion <= 5.00)
) ENGINE=InnoDB;

-- ============================================================
-- TABLA: clientes_registrados
-- ============================================================
DROP TABLE IF EXISTS clientes_registrados;
CREATE TABLE clientes_registrados (
  id_cliente_reg INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(120) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  direccion VARCHAR(200) NULL,
  telefono VARCHAR(20) NULL,
  mail VARCHAR(150) NOT NULL,
  pagina_web VARCHAR(200) NULL,
  nivel ENUM('BASICO','PRO','EMPRESA') NOT NULL DEFAULT 'BASICO',
  status TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id_cliente_reg),
  UNIQUE KEY uq_clientes_mail (mail),
  KEY idx_clientes_telefono (telefono)
) ENGINE=InnoDB;

-- ============================================================
-- TABLA: servicios_contratados
-- ============================================================
DROP TABLE IF EXISTS servicios_contratados;
CREATE TABLE servicios_contratados (
  id_servicio_cont INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_servicio INT UNSIGNED NOT NULL,
  id_cliente_reg INT UNSIGNED NOT NULL,
  fecha_inicio DATE NOT NULL,
  fecha_final DATE NULL,
  descripcion TEXT NULL,
  status TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id_servicio_cont),
  KEY idx_sc_servicio (id_servicio),
  KEY idx_sc_cliente (id_cliente_reg),
  CONSTRAINT fk_sc_servicio
    FOREIGN KEY (id_servicio) REFERENCES servicios(id_servicio)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_sc_cliente
    FOREIGN KEY (id_cliente_reg) REFERENCES clientes_registrados(id_cliente_reg)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
-- TABLA: clientes_potenciales
-- ============================================================
DROP TABLE IF EXISTS clientes_potenciales;
CREATE TABLE clientes_potenciales (
  id_cliente_pot INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(120) NOT NULL,
  correo VARCHAR(150) NOT NULL,
  telefono VARCHAR(20) NULL,
  id_servicio INT UNSIGNED NULL,
  status TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id_cliente_pot),
  UNIQUE KEY uq_potenciales_correo (correo),
  KEY idx_potenciales_servicio (id_servicio),
  CONSTRAINT fk_potenciales_servicio
    FOREIGN KEY (id_servicio) REFERENCES servicios(id_servicio)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- INSERTS (registros de ejemplo)
-- ============================================================

-- CATEGORIA (5)
INSERT INTO categoria (nombre, status) VALUES
('Desarrollo de Software', 1),
('Automatización', 1),
('Aplicaciones Móviles', 1),
('Bases de Datos', 1),
('Soporte y Asistencia', 1);

-- SERVICIOS (5) - servicios que dará la consultoría
-- Nota: id_categoria referenciado a los inserts anteriores (1..5)
INSERT INTO servicios (nombre, descripcion, costo, referencia, calificacion, id_categoria, status) VALUES
('Desarrollo Web',
 'Sitios web, landing pages, paneles administrativos y APIs para empresas.',
 25000.00, 'WEB-DEV-BASE', 4.70, 1, 1),
('Implementación de RPA',
 'Automatización de procesos repetitivos con robots de software (RPA).',
 40000.00, 'RPA-IMPL-STD', 4.60, 2, 1),
('Asistencia App Moviles',
 'Soporte, mejoras y liberación de apps iOS/Android; integración con backend.',
 30000.00, 'APP-SUP-MOB', 4.50, 3, 1),
('Control y gestión de Bases de Datos',
 'Diseño, optimización, respaldos, seguridad y mantenimiento de BD.',
 28000.00, 'DBA-MGMT-01', 4.55, 4, 1),
('Fix Developer',
 'Asistente de programación para empresas y estudiantes; debugging y guía técnica.',
 15000.00, 'FIXDEV-ASST', 4.80, 5, 1);

-- CLIENTES REGISTRADOS (8)
-- password_hash: ejemplo (NO guardar contraseñas en texto plano)
INSERT INTO clientes_registrados (nombre, password_hash, direccion, telefono, mail, pagina_web, nivel, status) VALUES
('Grupo Nébula SA de CV', '$2y$10$EXAMPLEhashNebula', 'Av. Reforma 100, CDMX', '5551002001', 'contacto@nebula.mx', 'https://nebula.mx', 'EMPRESA', 1),
('Comercializadora Atlas', '$2y$10$EXAMPLEhashAtlas', 'Calle 5 #123, Guadalajara', '3332001122', 'it@atlas.com.mx', 'https://atlas.com.mx', 'PRO', 1),
('Innova Labs', '$2y$10$EXAMPLEhashInnova', 'Parque Tecnológico 77, Monterrey', '8189007788', 'admin@innovalabs.io', 'https://innovalabs.io', 'EMPRESA', 1),
('Café Aurora', '$2y$10$EXAMPLEhashAurora', 'Centro 45, Puebla', '2224567890', 'dueño@cafeaurora.mx', 'https://cafeaurora.mx', 'BASICO', 1),
('Estudio Pixel', '$2y$10$EXAMPLEhashPixel', 'Col. Roma, CDMX', '5559876543', 'hola@estudiopixel.mx', 'https://estudiopixel.mx', 'PRO', 0),
('Finanzas Delta', '$2y$10$EXAMPLEhashDelta', 'Av. Insurgentes 200, CDMX', '5554432100', 'finanzas@delta.mx', 'https://delta.mx', 'PRO', 1),
('HealthNext Clinics', '$2y$10$EXAMPLEhashHealth', 'Circuito Médico 88, Querétaro', '4421012020', 'tech@healthnext.mx', 'https://healthnext.mx', 'EMPRESA', 1),
('LogiFast Express', '$2y$10$EXAMPLEhashLogi', 'Parque Industrial 12, Toluca', '7229988776', 'ops@logifast.mx', 'https://logifast.mx', 'BASICO', 1);

-- SERVICIOS CONTRATADOS (10)
-- id_servicio (1..5) y id_cliente_reg (1..5)
INSERT INTO servicios_contratados (id_servicio, id_cliente_reg, fecha_inicio, fecha_final, descripcion, status) VALUES
(1, 1, '2026-01-05', '2026-03-05', 'Desarrollo de portal web corporativo + API de catálogo.', 1),
(4, 2, '2026-01-10', NULL, 'Administración MySQL: optimización de consultas y plan de respaldos.', 1),
(2, 3, '2026-01-15', '2026-02-15', 'Automatización RPA para facturación y conciliación básica.', 1),
(3, 4, '2026-01-20', '2026-02-20', 'Soporte app móvil: corrección de bugs y publicación en tiendas.', 1),
(5, 5, '2026-01-22', '2026-01-30', 'Fix Developer: soporte intensivo para cierre de sprint (cliente inactivo).', 0),
(2, 6, '2026-02-01', NULL, 'RPA para cierres contables y validación fiscal.', 1),
(4, 7, '2026-02-02', NULL, 'PostgreSQL HA, backups verificados y monitoreo proactivo.', 1),
(5, 7, '2026-02-10', '2026-02-20', 'FixDeveloper: estabilización de API clínica y manejo de errores.', 1),
(1, 6, '2026-02-15', NULL, 'Landing y calculadora de productos financieros.', 1),
(3, 8, '2026-02-05', NULL, 'Soporte a app logística: tracking en tiempo real y releases.', 1);

-- CLIENTES POTENCIALES (8)
-- id_servicio apunta a servicios (1..5); puede ser NULL si aún no decide
INSERT INTO clientes_potenciales (nombre, correo, telefono, id_servicio, status) VALUES
('Logística Boreal', 'contacto@boreal-log.com', '5540010020', 2, 1),
('Clínica San Miguel', 'direccion@sanmiguel.clinic', '3311122233', 4, 1),
('Startup Lumen', 'ceo@lumenstartup.io', '8180101010', 1, 1),
('Universidad Horizonte', 'proyectos@horizonte.edu.mx', '2221010101', 5, 1),
('Tienda Nova', 'ventas@tiendanova.mx', '5552223344', NULL, 0),
('Mercurio Retail', 'cto@mercurio-retail.mx', '5554443322', 1, 1),
('AeroCargo', 'it@aerocargo.mx', '5580010010', 2, 1),
('GreenBank', 'tecnologia@greenbank.mx', '5566677788', 4, 1);
