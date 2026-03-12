<?php
include 'conexion.php';

// Limpiar la tabla de tramites de salud
$sql = "TRUNCATE TABLE tramites_salud";

if ($conn->query($sql) === TRUE) {
    echo "<h2>Se han eliminado todos los tramites de salud correctamente.</h2>";
    echo "<p>Los registros de ciudadanos (nombres, documentos) permanecen intactos para que no haya problemas con radicados o casos previos, pero el panel de salud queda limpio.</p>";
    echo "<p><a href='importar_tramites_salud.php'>Volver a Importar</a></p>";
} else {
    echo "Error eliminando datos: " . $conn->error;
}
?>