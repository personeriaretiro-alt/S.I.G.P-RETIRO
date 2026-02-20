<?php
include 'conexion.php';
include 'includes/auth.php';
include 'includes/header.php';

$id_usuario = $_SESSION['usuario_id'];
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';

$where = "WHERE r.usuario_asignado_id = $id_usuario";
if ($filtro_estado) {
    if ($filtro_estado != 'Todos') {
        $where .= " AND r.estado = '$filtro_estado'";
    }
} else {
    // Por defecto ocultar cerrados
    $where .= " AND r.estado != 'Cerrado'";
}

// Consulta mejorada para incluir detalles completos del ciudadano
$sql = "SELECT r.*, t.nombre as tipo,
        c.nombres, c.apellidos, c.numero_documento, c.tipo_documento, c.telefono, c.email,
        c.genero, c.grupo_poblacional, c.zona_residencia, c.barrio_vereda
        FROM radicados r 
        JOIN ciudadanos c ON r.ciudadano_id = c.id
        JOIN tipos_tramite t ON r.tipo_tramite_id = t.id
        $where
        ORDER BY r.fecha_vencimiento ASC";

$result = $conn->query($sql);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-briefcase"></i> Mis Casos Asignados</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="controllers/exportar_excel.php" class="btn btn-sm btn-outline-success"><i class="fas fa-file-excel"></i> Exportar</a>
            <a href="mis_casos.php?estado=Todos" class="btn btn-sm btn-outline-secondary">Todos</a>
            <a href="mis_casos.php?estado=Abierto" class="btn btn-sm btn-outline-primary">Abiertos</a>
            <a href="mis_casos.php?estado=Vencido" class="btn btn-sm btn-outline-danger">Vencidos</a>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive" style="max-height: 700px; overflow-y: auto;">
            <table class="table table-striped table-hover table-sm text-nowrap align-middle">
                <thead class="table-dark sticky-top">
                    <tr>
                        <th class="text-center" style="width: 50px;">Acción</th>
                        <th colspan="2" class="text-center border-end">Ciudadano</th>
                        <th colspan="4" class="text-center">Detalle del Radicado</th>
                    </tr>
                    <tr>
                        <th class="text-center"><i class="fas fa-cogs"></i></th>
                        
                        <th>Documento</th>
                        <th class="border-end">Nombre Completo</th>
                        
                        <th>Radicado</th>
                        <th>Tipo Trámite</th>
                        <th>Estado</th>
                        <th>Vencimiento</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <?php 
                                $fecha_vence = new DateTime($row['fecha_vencimiento']);
                                $hoy = new DateTime();
                                $interval = $hoy->diff($fecha_vence);
                                $dias = $interval->format("%r%a");
                                
                                // Semáforo
                                $badge = "bg-secondary";
                                if ($row['estado'] == 'Vencido' || $dias < 0) {
                                    $badge = "bg-danger";
                                    $estado_txt = "VENCIDO ($dias días)";
                                } elseif ($dias <= 2) {
                                    $badge = "bg-warning text-dark";
                                    $estado_txt = "Próximo ($dias días)";
                                } else {
                                    $badge = "bg-success";
                                    $estado_txt = "A tiempo ($dias días)";
                                }
                            ?>
                            <tr>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="ver_caso.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary" title="Gestionar Caso">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button class="btn btn-sm btn-outline-info" title="Ver Ciudadano" onclick='verCiudadano(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, "UTF-8"); ?>)'>
                                            <i class="fas fa-user-eye"></i>
                                        </button>
                                    </div>
                                </td>
                                
                                <td><span class="badge bg-light text-dark"><?= $row['tipo_documento'] ?> <?= $row['numero_documento'] ?></span></td>
                                <td class="fw-bold border-end text-truncate" style="max-width: 200px;" title="<?= $row['nombres'] ?> <?= $row['apellidos'] ?>">
                                    <?= $row['nombres'] ?> <?= $row['apellidos'] ?>
                                </td>
                                
                                <td><strong><?= $row['codigo_radicado'] ?></strong></td>
                                <td><small><?= $row['tipo'] ?></small></td>
                                <td><span class='badge bg-info text-dark'><?= $row['estado'] ?></span></td>
                                <td><span class='badge <?= $badge ?>'><?= $estado_txt ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No hay casos activos con este criterio.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Ver Detalle Ciudadano -->
<div class="modal fade" id="modalVerCiudadano" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-user"></i> Información del Ciudadano</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <h4 id="view_nombre" class="fw-bold text-primary"></h4>
                    <span id="view_documento" class="badge bg-dark fs-6"></span>
                </div>
                <hr>
                <div class="row g-2">
                    <div class="col-6"><strong><i class="fas fa-phone"></i> Teléfono:</strong></div>
                    <div class="col-6" id="view_telefono"></div>
                    
                    <div class="col-6"><strong><i class="fas fa-envelope"></i> Email:</strong></div>
                    <div class="col-6 text-break" id="view_email"></div>
                    
                    <div class="col-6"><strong>Género:</strong></div>
                    <div class="col-6" id="view_genero"></div>
                    
                    <div class="col-6"><strong>Grupo Poblacional:</strong></div>
                    <div class="col-6" id="view_grupo"></div>
                    
                    <div class="col-6"><strong>Zona:</strong></div>
                    <div class="col-6" id="view_zona"></div>
                    
                    <div class="col-6"><strong>Barrio/Vereda:</strong></div>
                    <div class="col-6" id="view_barrio"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
function verCiudadano(data) {
    if(!data) return;
    
    // Función helper para texto seguro
    var txt = function(val) { return val ? val : '<span class="text-muted fst-italic">No registra</span>'; };
    var el = function(id) { return document.getElementById(id); };
    
    // Poblar Modal
    el('view_nombre').innerText = data.nombres + ' ' + data.apellidos;
    el('view_documento').innerText = data.tipo_documento + ' ' + data.numero_documento;
    
    el('view_telefono').innerHTML = txt(data.telefono);
    el('view_email').innerHTML = txt(data.email);
    
    el('view_genero').innerHTML = txt(data.genero);
    el('view_grupo').innerHTML = txt(data.grupo_poblacional);
    el('view_zona').innerHTML = txt(data.zona_residencia);
    el('view_barrio').innerHTML = txt(data.barrio_vereda);
    
    new bootstrap.Modal(el('modalVerCiudadano')).show();
}
</script>

<?php include 'includes/footer.php'; ?>
