<?php
include 'conexion.php';
include 'includes/auth.php';

// Solo admin
if ($_SESSION['rol_id'] != 1) {
    die("ACCESO DENEGADO");
}

if (isset($_POST['confirmar'])) {
    // Desactivar chequeo de llaves foráneas temporalmente para vaciar sin líos
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    
    // Vaciar tablas de datos (TRUNCATE reinicia los contadores de ID a 1)
    $conn->query("TRUNCATE TABLE trazabilidad");
    $conn->query("TRUNCATE TABLE alertas");
    $conn->query("TRUNCATE TABLE radicados");
    $conn->query("TRUNCATE TABLE ciudadanos");
    
    // Opcional: Si quieres limpiar también tipos de tramites "basura" creados
    // $conn->query("TRUNCATE TABLE tipos_tramite"); 
    // (Mejor no, para no borrar los oficiales configurados en db.sql)

    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "<div style='color:green; font-size:20px; text-align:center; margin-top:50px;'>
            <h1>¡Limpieza Exitosa! ✨</h1>
            <p>Se han eliminado todos los casos y ciudadanos duplicados.</p>
            <p>Ahora tienes 0 registros. Puedes volver a importar tu archivo CSV (una sola vez).</p>
            <a href='importar.php'>Ir a Importar Nuevamente</a>
          </div>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reiniciar Base de Datos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-warning d-flex align-items-center justify-content-center" style="height: 100vh;">

    <div class="card shadow-lg p-5 text-center" style="max-width: 500px;">
        <h1 class="text-danger mb-4">⚠️ ZONA DE PELIGRO</h1>
        <p class="lead">Estás a punto de eliminar <strong>TODOS</strong> los ciudadanos y casos del sistema.</p>
        <p>Esto se usa para corregir errores de duplicación en la importación. Los usuarios (funcionarios) NO se borrarán.</p>
        
        <form method="POST">
            <button type="submit" name="confirmar" class="btn btn-danger btn-lg w-100 mt-4">
                CONFIRMAR Y BORRAR TODO
            </button>
        </form>
        <a href="index.php" class="btn btn-link mt-3">Cancelar y Salir</a>
    </div>

</body>
</html>
