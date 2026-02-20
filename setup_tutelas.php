<?php
include 'conexion.php';

$sql = "CREATE TABLE IF NOT EXISTS tutelas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ciudadano_id INT NOT NULL,
    fecha_atencion DATE,
    derecho_amparado VARCHAR(255),
    juzgado VARCHAR(255),
    radicado_tutela VARCHAR(100),
    fecha_radicado DATE,
    responsable_tutela VARCHAR(255),
    admitida VARCHAR(50),
    fecha_admision DATE,
    persona_vinculada VARCHAR(255),
    fecha_maxima_sentencia DATE,
    dias_termino INT,
    fecha_sentencia DATE,
    concedio_tutela VARCHAR(255),
    cumplimiento VARCHAR(255),
    incidente_desacato VARCHAR(255),
    responsable_incidente VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ciudadano_id) REFERENCES ciudadanos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn->query($sql) === TRUE) {
    echo "Tabla 'tutelas' creada exitosamente.";
} else {
    echo "Error creando tabla: " . $conn->error;
}
?>