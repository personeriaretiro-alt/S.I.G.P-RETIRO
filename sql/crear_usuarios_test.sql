-- Script para crear usuarios de prueba con los Roles 11 y 12
-- Rol 11: Abogado Tutelas
-- Rol 12: Abogado Asesorias

INSERT INTO usuarios (nombre_completo, email, password_hash, rol_id, estado) VALUES 
('Prueba Abg Tutelas', 'tutelas_test@personeria.gov.co', '123456', 11, 'activo'),
('Prueba Abg Asesorias', 'asesorias_test@personeria.gov.co', '123456', 12, 'activo');

-- Si prefiere actualizar usuarios existentes de su lista (por ejemplo, Juan y Edwin):
-- UPDATE usuarios SET rol_id = 11 WHERE id = 3; -- Juan Manuel Ramírez ahora vería solo Tutelas
-- UPDATE usuarios SET rol_id = 12 WHERE id = 4; -- Edwin Montes ahora vería solo Asesorías
