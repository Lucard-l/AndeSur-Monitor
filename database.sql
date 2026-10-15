CREATE DATABASE IF NOT EXISTS andesur_monitor
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE andesur_monitor;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS fuentes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(30) NOT NULL UNIQUE,
    nombre VARCHAR(120) NOT NULL,
    tipo VARCHAR(40) NOT NULL,
    ubicacion VARCHAR(80) NOT NULL,
    estado ENUM('sin_senal', 'activa', 'inactiva') NOT NULL DEFAULT 'sin_senal',
    ultima_senal DATETIME(6) NULL,
    incidente_actual CHAR(32) NULL,
    fecha_registro DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL UNIQUE,
    contrasena_hash VARCHAR(255) NOT NULL,
    rol ENUM('lector', 'administrador') NOT NULL DEFAULT 'lector',
    activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS eventos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL UNIQUE,
    fuente_id INT UNSIGNED NOT NULL,
    codigo VARCHAR(30) NOT NULL,
    usuario VARCHAR(80) NULL,
    descripcion VARCHAR(255) NOT NULL,
    severidad ENUM('Informativa', 'Media', 'Alta', 'Critica') NOT NULL,
    fecha DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    es_simulado TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (fuente_id) REFERENCES fuentes(id),
    INDEX idx_fecha (fecha),
    INDEX idx_fuente_fecha (fuente_id, fecha),
    INDEX idx_tipo_usuario_fecha (fuente_id, codigo, usuario, fecha)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS alertas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fuente_id INT UNSIGNED NOT NULL,
    tipo VARCHAR(30) NOT NULL,
    nombre VARCHAR(120) NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    usuario VARCHAR(80) NULL,
    severidad ENUM('Alta', 'Critica') NOT NULL,
    estado ENUM('Abierta', 'Resuelta') NOT NULL DEFAULT 'Abierta',
    fecha DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    correlacion CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL UNIQUE,
    primer_evento_id BIGINT UNSIGNED NOT NULL,
    ultimo_evento_id BIGINT UNSIGNED NOT NULL,
    cantidad_eventos INT UNSIGNED NOT NULL DEFAULT 1,
    FOREIGN KEY (fuente_id) REFERENCES fuentes(id),
    FOREIGN KEY (primer_evento_id) REFERENCES eventos(id),
    FOREIGN KEY (ultimo_evento_id) REFERENCES eventos(id),
    INDEX idx_alerta_usuario (fuente_id, tipo, usuario, ultimo_evento_id),
    INDEX idx_alerta_estado (estado, severidad)
) ENGINE=InnoDB;

INSERT INTO fuentes (codigo, nombre, tipo, ubicacion, estado)
SELECT 'PORTAL-LPZ', 'Portal de empleados', 'Portal', 'La Paz', 'activa'
WHERE NOT EXISTS (SELECT 1 FROM fuentes WHERE codigo = 'PORTAL-LPZ');
INSERT INTO fuentes (codigo, nombre, tipo, ubicacion, estado)
SELECT 'ADMIN-CBB', 'Sistema administrativo', 'Administración', 'Cochabamba', 'activa'
WHERE NOT EXISTS (SELECT 1 FROM fuentes WHERE codigo = 'ADMIN-CBB');
INSERT INTO fuentes (codigo, nombre, tipo, ubicacion)
SELECT 'INV-SCZ', 'Centro logístico — Inventarios', 'Inventarios', 'Santa Cruz'
WHERE NOT EXISTS (SELECT 1 FROM fuentes WHERE codigo = 'INV-SCZ');

-- Usuario inicial con rol lector.
INSERT INTO usuarios (nombre, contrasena_hash, rol)
SELECT 'empleado', '$2y$12$En3FCaOH4cjiUF.LJTbBdu7ZGo7NRYQJvwbxa4xC.IQD6dzYAhZD6', 'lector'
WHERE NOT EXISTS (SELECT 1 FROM usuarios WHERE nombre = 'empleado');
