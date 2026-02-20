<?php
include '../conexion.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    
    // Obtener datos del formulario
    $derecho = $_POST['derecho_amparado'];
    $juzgado = $_POST['juzgado'];
    $accionado = $_POST['accionado'];
    $admitida = $_POST['admitida'];
    $f_admision = !empty($_POST['fecha_admision']) ? $_POST['fecha_admision'] : NULL;
    $fallo = $_POST['fallo'];
    $obs = $_POST['observaciones'];

    // Preparar UPDATE
    $sql = "UPDATE tutelas SET 
            derecho_amparado = ?, 
            juzgado = ?, 
            accionado = ?, 
            admitida = ?, 
            fecha_admision = ?, 
            fallo = ? 
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssi", $derecho, $juzgado, $accionado, $admitida, $f_admision, $fallo, $id);

    if ($stmt->execute()) {
        header("Location: ../seguimiento_tutelas.php?msg=Tutela actualizada correctamente");
    } else {
        echo "Error al actualizar: " . $stmt->error;
    }
    
    $stmt->close();
    $conn->close();
}
?>