<?php
include 'conexion.php';
include 'includes/auth.php';
include 'includes/header.php';

if (!isset($_GET['id'])) {
    header("Location: mis_casos.php");
    exit();
}

$id_radicado = $_GET['id'];

// Consultar datos del caso y del ciudadano
$sql = "SELECT r.*, c.nombres, c.apellidos, c.numero_documento, c.telefono, c.email, c.firma_digital, t.nombre as tipo_tramite_nombre 
        FROM radicados r 
        JOIN ciudadanos c ON r.ciudadano_id = c.id
        JOIN tipos_tramite t ON r.tipo_tramite_id = t.id
        WHERE r.id = $id_radicado";

$result = $conn->query($sql);
if (!$result) {
    die("Error en la consulta: " . $conn->error);
}

if ($result->num_rows == 0) {
    die("Caso no encontrado.");
}

$caso = $result->fetch_assoc();

// Consultar historial de movimientos (Bitácora)
$sql_historial = "SELECT tra.*, u.nombre_completo 
                  FROM trazabilidad tra 
                  JOIN usuarios u ON tra.usuario_id = u.id 
                  WHERE tra.radicado_id = $id_radicado 
                  ORDER BY tra.fecha_movimiento DESC";
$historial = $conn->query($sql_historial);

?>

<div class="row mb-4">
    <div class="col-md-12 d-flex justify-content-between align-items-center">
        <h2 class="text-primary fw-bold">Gestión del Caso #<?php echo $caso['codigo_radicado']; ?></h2>
        <div>
            <!-- Botones de Acción Rápida -->
            <?php if($caso['estado'] != 'Cerrado'): ?>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalCerrar"><i class="fas fa-check-circle"></i> Cerrar Caso</button>
            <?php else: ?>
                <span class="badge bg-secondary p-2">CASO CERRADO</span>
            <?php endif; ?>
            <a href="mis_casos.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
        </div>
    </div>
</div>

<div class="row">
    <!-- Columna Izquierda: Información del Ciudadano y Detalle -->
    <div class="col-md-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0 text-dark"><i class="fas fa-user-circle"></i> Datos del Ciudadano</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><strong>Nombre:</strong> <?php echo $caso['nombres'] . " " . $caso['apellidos']; ?></li>
                    <li class="list-group-item"><strong>Documento:</strong> <?php echo $caso['numero_documento']; ?></li>
                    <li class="list-group-item"><strong>Teléfono:</strong> <?php echo $caso['telefono'] ?? 'N/A'; ?></li>
                    <li class="list-group-item"><strong>Email:</strong> <?php echo $caso['email'] ?? 'N/A'; ?></li>
                </ul>
                <div class="mt-3 text-center">
                    <a href="mailto:<?php echo $caso['email']; ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-envelope"></i> Contactar</a>
                    <a href="tel:<?php echo $caso['telefono']; ?>" class="btn btn-sm btn-outline-success"><i class="fas fa-phone"></i> Llamar</a>
                </div>
            </div>
            <?php if (!empty($caso['firma_digital']) && file_exists($caso['firma_digital'])): ?>
            <div class="card-footer bg-white text-center">
                <small class="text-muted d-block mb-2">Firma Digital Autorizada</small>
                <img src="<?php echo $caso['firma_digital']; ?>" alt="Firma del Ciudadano" class="img-fluid border rounded p-1" style="max-height: 80px;">
            </div>
            <?php endif; ?>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-info-circle"></i> Detalles del Trámite</h5>
            </div>
            <div class="card-body">
                <p><strong>Tipo:</strong> <?php echo $caso['tipo_tramite_nombre']; ?></p>
                <p><strong>Estado Actual:</strong> <span class="badge bg-primary"><?php echo $caso['estado']; ?></span></p>
                <p><strong>Fecha Inicio:</strong> <?php echo $caso['fecha_inicio']; ?></p>
                <p><strong>Vence el:</strong> <span class="text-danger fw-bold"><?php echo $caso['fecha_vencimiento']; ?></span></p>
                <hr>
                <p class="small text-muted"><strong>Observación Inicial:</strong><br> <?php echo nl2br($caso['observacion_inicial']); ?></p>
            </div>
        </div>
    </div>

    <!-- Columna Derecha: Bitácora de Actuaciones -->
    <div class="col-md-8">
        <!-- Formulario para Nueva Actuación -->
        <?php if($caso['estado'] != 'Cerrado'): ?>
        <div class="card shadow-sm mb-4 border-start border-primary border-4">
            <div class="card-body">
                <h5 class="card-title text-primary">Registrar Nueva Actuación / Avance</h5>
                <form action="controllers/actualizar_caso.php" method="POST">
                    <input type="hidden" name="radicado_id" value="<?php echo $id_radicado; ?>">
                    <div class="mb-3">
                        <textarea class="form-control" name="comentario" rows="3" placeholder="Describa la gestión realizada (ej: Se redactó documento, se llamó al usuario, se radicó ante entidad...)" required></textarea>
                    </div>
                    <div class="d-flex justify-content-between">
                         <select class="form-select w-auto" name="nuevo_estado">
                            <option value="">Mantener estado actual</option>
                            <option value="En Proceso">En Proceso</option>
                            <option value="Pendiente Información">Pendiente Información (Detiene reloj - Futuro)</option>
                         </select>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Gestión</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Historial / Timeline -->
        <h5 class="mb-3"><i class="fas fa-history"></i> Historial de Actuaciones</h5>
        <div class="timeline">
            <?php if ($historial->num_rows > 0): ?>
                <?php while($h = $historial->fetch_assoc()): ?>
                    <div class="card mb-3 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <h6 class="card-subtitle mb-2 text-muted small">
                                    <i class="fas fa-user"></i> <?php echo $h['nombre_completo']; ?> &bull; 
                                    <i class="fas fa-clock"></i> <?php echo $h['fecha_movimiento']; ?>
                                </h6>
                                <span class="badge bg-light text-dark border"><?php echo $h['accion']; ?></span>
                            </div>
                            <p class="card-text mt-2"><?php echo nl2br($h['comentario']); ?></p>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-info">Aún no se han registrado gestiones sobre este caso.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal para Cerrar Caso -->
<div class="modal fade" id="modalCerrar" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">Finalizar Trámite</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="controllers/actualizar_caso.php" method="POST">
          <div class="modal-body">
            <input type="hidden" name="radicado_id" value="<?php echo $id_radicado; ?>">
            <input type="hidden" name="nuevo_estado" value="Cerrado">
            <input type="hidden" name="accion_tipo" value="Cierre">
            
            <p>¿Está seguro que desea cerrar este caso? Esto indicará que la gestión ha finalizado exitosamente.</p>
            <div class="mb-3">
                <label class="form-label">Conclusión Final</label>
                <textarea class="form-control" name="comentario" required placeholder="Resumen del cierre..."></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-success">Confirmar Cierre</button>
          </div>
      </form>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
