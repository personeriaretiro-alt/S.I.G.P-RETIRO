-- 1. Insertar nuevos roles específicos
INSERT INTO roles (nombre, descripcion) VALUES 
('Abogado Tutelas', 'Gestion exclusiva de tutelas y fallos'),
('Abogado Asesorias', 'Gestion exclusiva de asesorias y orientacion al ciudadano');

-- NOTA: Asumimos que los IDs generados son:
-- 4 para Abogado Tutelas
-- 5 para Abogado Asesorias
-- Si su base de datos tiene otros IDs, ajuste los valores en los insert de usuarios abajo.

-- 2. Crear usuarios de prueba con esos roles
-- La contraseña es '123456' (o lo que usted prefiera, aquí es texto plano para desarrollo)
INSERT INTO usuarios (nombre_completo, email, password_hash, rol_id, estado) VALUES 
('Abogado de Tutelas', 'tutelas@personeria.gov.co', '123456', 4, 'activo'),
('Abogado de Asesorias', 'asesorias@personeria.gov.co', '123456', 5, 'activo');

-- Para probar:
-- Inicie sesión con tutelas@personeria.gov.co / 123456 -> Solo verá Tutelas
-- Inicie sesión con asesorias@personeria.gov.co / 123456 -> Solo verá Asesorías
