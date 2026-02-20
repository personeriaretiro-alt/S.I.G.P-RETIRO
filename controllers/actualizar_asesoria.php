<?php
include '../conexion.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $estado = $_POST['estado'];
    $pais = $_POST['pais_tramite'];
    $notas = $_POST['notas_gestion'];
    
    // Aquí puedes agregar más campos específicos de Asesoría según necesites
    
    $sql = "UPDATE asesorias SET
            estado = '$estado',
            pais_tramite = '$pais',
            observacion_final = '$notas'
            WHERE id = $id";
            
     if ($conn->query($sql) === TRUE) {
        header("Location: ../panel_asesorias.php?msg=Actualizado correctamente");
     } else {
        echo "Error actualizando: " . $conn->error;
     }

     $conn->close();
}
?>