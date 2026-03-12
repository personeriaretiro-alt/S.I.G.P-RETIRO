<?php
include 'conexion.php';

// 1. Crear el nuevo rol
$sql_rol = "INSERT IGNORE INTO roles (id, nombre) VALUES (13, 'Auxiliar Salud')";
$conn->query($sql_rol);

// 2. Crear usuario de prueba
$pwd = 'salud123';
$sql_user = "INSERT INTO usuarios (nombre_completo, email, password_hash, rol_id) VALUES ('Prueba Salud', 'salud@personeria.com', '$pwd', 13)";
try {
    $conn->query($sql_user);
    echo "¡Usuario y Rol creados exitosamente!<br>";
    echo "<b>Email:</b> salud@personeria.com<br>";
    echo "<b>Contraseña:</b> salud123<br>";
} catch (Exception $e) {
    echo "El usuario ya existe o hubo un error: " . $e->getMessage();
}
?>