<?php
include 'conexion.php';

$mensaje = "";
$caso = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $codigo = $conn->real_escape_string($_POST['radicado']);
    
    // Solo mostrar información pública (Estado y Fechas, NO datos sensibles)
    $sql = "SELECT r.codigo_radicado, r.estado, r.fecha_inicio, r.fecha_vencimiento, t.nombre as tramite 
            FROM radicados r 
            JOIN tipos_tramite t ON r.tipo_tramite_id = t.id 
            WHERE r.codigo_radicado = '$codigo'";
            
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $caso = $result->fetch_assoc();
    } else {
        $mensaje = "No se encontró ningún trámite con el código $codigo.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta Ciudadana - Personería El Retiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 text-center mb-4">
            <h2 class="text-primary fw-bold"><i class="fas fa-search"></i> Consulta tu Trámite</h2>
            <p class="text-muted">Ingresa el código de radicado que te entregaron en la Personería.</p>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm p-4">
                <form method="POST" action="">
                    <div class="input-group mb-3">
                        <input type="text" class="form-control form-control-lg" name="radicado" placeholder="Ej: PER-2026-0001" required>
                        <button class="btn btn-primary" type="submit">Consultar</button>
                    </div>
                </form>

                <?php if($mensaje): ?>
                    <div class="alert alert-warning text-center"><?php echo $mensaje; ?></div>
                <?php endif; ?>

                <?php if($caso): ?>
                    <div class="card mt-3 border-success border-2">
                        <div class="card-body">
                            <h5 class="card-title text-success">Resultados de la Búsqueda</h5>
                            <ul class="list-group list-group-flush mt-3">
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Trámite:</span> <strong><?php echo $caso['tramite']; ?></strong>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Estado Actual:</span> 
                                    <span class="badge bg-primary rounded-pill"><?php echo $caso['estado']; ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Fecha Radicación:</span> 
                                    <span><?php echo date('d/m/Y', strtotime($caso['fecha_inicio'])); ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Fecha Estimada Respuesta:</span> 
                                    <span class="text-muted"><?php echo date('d/m/Y', strtotime($caso['fecha_vencimiento'])); ?></span>
                                </li>
                            </ul>
                            <div class="alert alert-info mt-3 small">
                                <i class="fas fa-info-circle"></i> Para más detalles, acérquese a nuestras oficinas.
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="text-center mt-4">
                    <a href="login.php" class="text-secondary small">Acceso Funcionarios</a>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="mt-5 text-center text-muted small pb-4">
    &copy; 2026 Personería Municipal de El Retiro | Transparencia y Servicio
</footer>

</body>
</html>
