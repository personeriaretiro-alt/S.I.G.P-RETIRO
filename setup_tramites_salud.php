<?php
include 'conexion.php';

$sql = "CREATE TABLE IF NOT EXISTS tramites_salud (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ciudadano_id INT NOT NULL,
    fecha_atencion DATE,
    servicio_solicitado VARCHAR(255),
    eps VARCHAR(255),
    gestionado_a_traves_de VARCHAR(255),
    estado VARCHAR(100),
    realizado_por VARCHAR(255),
    observaciones TEXT,
    r_super_salud VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ciudadano_id) REFERENCES ciudadanos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn->query($sql) === TRUE) {
    echo "<h2>Tabla 'tramites_salud' creada exitosamente o ya existia.</h2>";
    echo "<p><a href='index.php'>Volver al Inicio</a></p>";
} else {
    echo "<h2>Error creando tabla: " . $conn->error . "</h2>";
}
?>