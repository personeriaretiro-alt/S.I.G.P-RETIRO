-- Base de datos para Personería El Retiro - GovTech Solution

CREATE DATABASE IF NOT EXISTS personeria_retiro;
USE personeria_retiro;

-- 1. Tabla de Roles (Para escalabilidad y permisos)
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion TEXT
);

INSERT INTO roles (nombre, descripcion) VALUES 
('Administrador', 'Control total del sistema'),
('Personero', 'Visualización de reportes y alertas globales'),
('Funcionario', 'Gestión operativa de tramites');

-- 2. Tabla de Usuarios (Funcionarios de la Personería)
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol_id INT,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rol_id) REFERENCES roles(id)
);

-- 3. Tabla de Ciudadanos (Base de datos única de personas atendidas)
-- Esto evita tener que volver a digitar datos si la persona regresa.
CREATE TABLE ciudadanos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo_documento ENUM('CC', 'TI', 'CE', 'PPT', 'Otro') NOT NULL,
    numero_documento VARCHAR(20) NOT NULL UNIQUE,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    email VARCHAR(100),
    direccion VARCHAR(150),
    es_poblacion_vulnerable BOOLEAN DEFAULT FALSE,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. Tipos de Trámites (Parametrizable)
-- SLA = Service Level Agreement (Tiempo máximo de respuesta en horas)
-- Ejemplo: Una tutela son 48 horas (o lo que dicte la ley), un derecho de petición 15 días hábiles.
CREATE TABLE tipos_tramite (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    sla_horas INT DEFAULT 24, -- Tiempo límite en horas para alerta roja
    requiere_abogado BOOLEAN DEFAULT TRUE
);

INSERT INTO tipos_tramite (nombre, sla_horas) VALUES 
('Asesoría Jurídica', 1),
('Acción de Tutela', 48),
('Derecho de Petición', 360), -- 15 días * 24h aprox
('Incidente de Desacato', 72),
('Amparo de Pobreza', 24);

-- 5. Radicados / Casos (La tabla central)
CREATE TABLE radicados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo_radicado VARCHAR(20) UNIQUE, -- Ej: PER-2023-001
    ciudadano_id INT NOT NULL,
    tipo_tramite_id INT NOT NULL,
    area_atencion VARCHAR(100), -- Nuevo campo añadido (Juridica, Penal, etc.)
    usuario_asignado_id INT, -- Funcionario responsable
    estado ENUM('Abierto', 'En Proceso', 'Pendiente Información', 'Cerrado', 'Vencido') DEFAULT 'Abierto',
    prioridad ENUM('Alta', 'Media', 'Baja') DEFAULT 'Media',
    fecha_inicio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_vencimiento DATETIME, -- Se calcula al crear basado en el SLA
    observacion_inicial TEXT,
    FOREIGN KEY (ciudadano_id) REFERENCES ciudadanos(id),
    FOREIGN KEY (tipo_tramite_id) REFERENCES tipos_tramite(id),
    FOREIGN KEY (usuario_asignado_id) REFERENCES usuarios(id)
);

-- 6. Trazabilidad / Bitácora (Audit Trail)
-- Fundamental para gobierno: saber quién hizo qué y cuándo.
CREATE TABLE trazabilidad (
    id INT AUTO_INCREMENT PRIMARY KEY,
    radicado_id INT NOT NULL,
    usuario_id INT NOT NULL, -- Quién realizó la acción
    accion VARCHAR(50) NOT NULL, -- Ej: "Cambio Estado", "Adjunto Archivo"
    comentario TEXT,
    fecha_movimiento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (radicado_id) REFERENCES radicados(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- 7. Alertas del Sistema
CREATE TABLE alertas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    radicado_id INT NOT NULL,
    tipo ENUM('Vencimiento Próximo', 'Vencido', 'Sin Gestión'),
    mensaje VARCHAR(255),
    leida BOOLEAN DEFAULT FALSE,
    fecha_alerta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (radicado_id) REFERENCES radicados(id)
);

-- 8. Seed (Usuario por defecto)
-- Password: admin
INSERT INTO usuarios (nombre_completo, email, password_hash, rol_id) VALUES 
('Administrador Sistema', 'admin@personeria.gov.co', 'admin123', 1);

-- 9. Campos Adicionales para Importación Histórica
ALTER TABLE ciudadanos ADD COLUMN genero VARCHAR(20);
ALTER TABLE ciudadanos ADD COLUMN grupo_poblacional VARCHAR(100);
ALTER TABLE ciudadanos ADD COLUMN zona_residencia VARCHAR(50); -- Urbana/Rural
ALTER TABLE ciudadanos ADD COLUMN barrio_vereda VARCHAR(100);
ALTER TABLE ciudadanos ADD COLUMN rango_edad VARCHAR(50);

-- Campo para guardar el medio (Presencial, Telefónico, etc)
ALTER TABLE radicados ADD COLUMN medio_atencion VARCHAR(50);
