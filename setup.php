<?php
include 'conexion.php';

$sql_file = file_get_contents('sql/db.sql');

// Separar consultas por ;
$queries = explode(';', $sql_file);

echo "<h1>Instalación del Sistema...</h1>";

foreach ($queries as $query) {
    $query = trim($query);
    if (!empty($query)) {
        try {
            $conn->query($query);
            echo "<p style='color:green'>Query ejecutado correctamente: " . htmlspecialchars(substr($query, 0, 50)) . "...</p>";
        } catch (mysqli_sql_exception $e) {
            // Ignorar error de "Tabla ya existe" (Código 1050) o "Entrada duplicada" (Código 1062)
            if ($e->getCode() == 1050 || strpos($e->getMessage(), "already exists") !== false) {
                 echo "<p style='color:orange'>Tabla ya existe (Saltado): " . htmlspecialchars(substr($query, 0, 50)) . "...</p>";
            } elseif ($e->getCode() == 1062 || strpos($e->getMessage(), "Duplicate entry") !== false) {
                 echo "<p style='color:orange'>Registro duplicado (Saltado): " . htmlspecialchars(substr($query, 0, 50)) . "...</p>";
            } else {
                 echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
            }
        }
    }
}

// Crear admin si no existe
$check = $conn->query("SELECT * FROM usuarios WHERE email = 'admin@personeria.gov.co'");
if ($check->num_rows == 0) {
    $conn->query("INSERT INTO usuarios (nombre_completo, email, password_hash, rol_id) VALUES ('Administrador Sistema', 'admin@personeria.gov.co', 'admin123', 1)");
    echo "<p style='color:blue'>Usuario Admin creado (admin@personeria.gov.co / admin123)</p>";
} else {
    echo "<p style='color:blue'>Usuario Admin ya existe.</p>";
}

echo "<h3>Instalación completada. <a href='index.php'>Ir al Inicio</a></h3>";
?>