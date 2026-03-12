<?php
include 'conexion.php';

// Definir los nuevos usuarios oficiales
$users = [
    ['Nathaly Garcia Buitrago', 'Abg.nathaly@hotmail.com', 'Retiro2026', 1],
    ['MARÍA ANGÉLICA CASTAÑEDA CASTAÑEDA', 'Angcast0328@gmail.com', 'Retiro2026', 1],
    ['Maria Andrea Giraldo Mejía', 'Mandrea.170294@gmail.com', 'Retiro2026', 11],
    ['CATALINA CASTAÑO ARROYAVE', 'Catalate2616@gmail.com', 'Retiro2026', 13]
];

$emails_to_keep = [];

echo "Configurando usuarios...\n";

foreach ($users as $u) {
    $nombre = $u[0];
    $email = strtolower(trim($u[1]));
    $pass = $u[2]; // Tal como lo guarda el sistema actual (en texto plano según veo)
    $rol = $u[3];
    
    $emails_to_keep[] = "'$email'";

    // Verificar si existe
    $check = $conn->query("SELECT id FROM usuarios WHERE LOWER(email) = '$email'");
    if ($check->num_rows > 0) {
        $row = $check->fetch_assoc();
        $stmt = $conn->prepare("UPDATE usuarios SET nombre_completo = ?, password_hash = ?, rol_id = ? WHERE id = ?");
        $stmt->bind_param("ssii", $nombre, $pass, $rol, $row['id']);
        $stmt->execute();
        echo "Actualizado: $email\n";
    } else {
        $stmt = $conn->prepare("INSERT INTO usuarios (nombre_completo, email, password_hash, rol_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $nombre, $email, $pass, $rol);
        if($stmt->execute()) {
             echo "Creado: $email\n";
        } else {
             echo "Error creando $email: " . $conn->error . "\n";
        }
    }
}

// Limpiar los demás usuarios
$keep_str = implode(',', $emails_to_keep);

// Buscar usuarios que no estén en la lista a conservar
$res = $conn->query("SELECT id, email FROM usuarios WHERE LOWER(email) NOT IN ($keep_str)");
$eliminados = 0;
while ($row = $res->fetch_assoc()) {
    $id = $row['id'];
    
    // Desvincular de los registros para evitar errores de llave foránea (Foreign Keys)
    $tablas_desvincular = [
        "UPDATE radicados SET usuario_asignado_id = NULL WHERE usuario_asignado_id = $id",
        "UPDATE tutelas SET usuario_responsable_id = NULL WHERE usuario_responsable_id = $id",
        "UPDATE tutelas SET usuario_registra_id = NULL WHERE usuario_registra_id = $id",
        "UPDATE asesorias SET usuario_responsable_id = NULL WHERE usuario_responsable_id = $id",
        "UPDATE trazabilidad SET usuario_id = NULL WHERE usuario_id = $id"
    ];
    
    foreach($tablas_desvincular as $sql_des) {
        try {
            $conn->query($sql_des);
        } catch(Exception $e) {}
    }
    
    // Si la trazabilidad no permite nulos (en algunas BD antiguas), borrar la traza
    try {
        $conn->query("DELETE FROM trazabilidad WHERE usuario_id = $id");
    } catch(Exception $e) {}
    
    // Intentar borrar
    try {
        if ($conn->query("DELETE FROM usuarios WHERE id = $id")) {
            echo "Eliminado: " . $row['email'] . "<br>\n";
            $eliminados++;
        }
    } catch(Exception $e) {
        echo "No se pudo eliminar el usuario " . $row['email'] . " por restricciones de BD (Tiene historial). Se marcará con un correo borrado.<br>\n";
        // Si no se puede borrar, lo "desactivamos" ofuscando su correo y contraseña para que no pueda entrar
        $conn->query("UPDATE usuarios SET email = CONCAT('eliminado_', id, '_', email), password_hash = 'NO_ACCESO' WHERE id = $id");
    }
}

echo "Proceso finalizado. Puedes iniciar sesión con las nuevas credenciales.\n";
?>