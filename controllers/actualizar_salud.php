<?php
include '../conexion.php';
include '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tramite_id'])) {
    
    // Solo permitir edición a roles autorizados (Admin=1, Personero=2, Funcionario=3, AuxSalud=13)
    if (!in_array($_SESSION['rol_id'], [1, 2, 3, 13])) {
        die("Acceso denegado");
    }

    $id = $_POST['tramite_id'];
    $servicio = trim($_POST['servicio_solicitado']);
    $eps = trim($_POST['eps']);
    $gestionado = trim($_POST['gestionado_a_traves_de']);
    $estado = trim($_POST['estado']);
    $realizado_por = trim($_POST['realizado_por']);
    $r_super = trim($_POST['r_super_salud']);
    $observaciones = trim($_POST['observaciones']);

    $stmt = $conn->prepare("UPDATE tramites_salud SET servicio_solicitado=?, eps=?, gestionado_a_traves_de=?, estado=?, realizado_por=?, r_super_salud=?, observaciones=? WHERE id=?");
    $stmt->bind_param("sssssssi", $servicio, $eps, $gestionado, $estado, $realizado_por, $r_super, $observaciones, $id);

    if ($stmt->execute()) {
        header("Location: ../Tramites_salud.php?msg=actualizado");
        exit();
    } else {
        echo "Error actualizando: " . $conn->error;
    }
} else {
    header("Location: ../Tramites_salud.php");
    exit();
}
?>