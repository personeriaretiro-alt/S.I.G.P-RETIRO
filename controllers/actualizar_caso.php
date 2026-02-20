<?php
include '../conexion.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $radicado_id = $_POST['radicado_id'];
    $comentario = $_POST['comentario'];
    $usuario_id = $_SESSION['usuario_id'];
    $nuevo_estado = $_POST['nuevo_estado'] ?? '';
    
    $accion = $_POST['accion_tipo'] ?? 'Gestión'; // Default action type

    // 1. Registrar Trazabilidad
    $stmt = $conn->prepare("INSERT INTO trazabilidad (radicado_id, usuario_id, accion, comentario) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $radicado_id, $usuario_id, $accion, $comentario);
    $stmt->execute();

    // 2. Actualizar Estado si se seleccionó uno
    if (!empty($nuevo_estado)) {
        
        // Si el estado es "Cerrado", podríamos querer guardar la fecha de cierre si tuvieramos ese campo, 
        // pero por ahora solo el estado es suficiente.
        
        $stmt_upd = $conn->prepare("UPDATE radicados SET estado = ? WHERE id = ?");
        $stmt_upd->bind_param("si", $nuevo_estado, $radicado_id);
        $stmt_upd->execute();
        
        // Registrar en bitácora el cambio de estado también si es explícito
        if($accion != 'Cierre') { // Si es cierre ya lo registramos en el paso 1 implícitamente o explicitamente
             $msg_estado = "Cambio de estado a: " . $nuevo_estado;
             $conn->query("INSERT INTO trazabilidad (radicado_id, usuario_id, accion, comentario) VALUES ($radicado_id, $usuario_id, 'Cambio Estado', '$msg_estado')");
        }
    }

    // Redireccionar
    if ($nuevo_estado == 'Cerrado') {
        header("Location: ../mis_casos.php?msg=Caso cerrado exitosamente");
    } else {
        header("Location: ../ver_caso.php?id=$radicado_id&msg=Actualizado");
    }
}
?>