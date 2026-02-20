<?php
include 'conexion.php';
include 'includes/auth.php';

// Control de Acceso: Solo Roles autorizados (Admin, Personero, Funcionario, Abg. Asesorias)
if (!isset($_SESSION['rol_id']) || !in_array($_SESSION['rol_id'], [1, 2, 3, 12])) {
    header("Location: index.php");
    exit();
}

include 'includes/header.php';

// Paginación simple
$limit = 50;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Búsqueda
$search = isset($_GET['q']) ? $_GET['q'] : '';
$where = "WHERE 1=1";
if ($search) {
    // Busca por nombre, documento o radicado
    $where .= " AND (c.nombres LIKE '%$search%' OR c.numero_documento LIKE '%$search%' OR a.codigo_radicado LIKE '%$search%')";
}

// Consulta Especifica para Asesorías
// Une la tabla 'asesorias' con 'ciudadanos'
// Se obtienen todos los campos de ciudadanos para el modal de detalle
$sql = "SELECT a.*, 
        c.nombres, c.apellidos, c.numero_documento, c.tipo_documento, c.telefono, c.email,
        c.genero, c.grupo_poblacional, c.zona_residencia, c.barrio_vereda
        FROM asesorias a 
        JOIN ciudadanos c ON a.ciudadano_id = c.id 
        $where 
        ORDER BY a.fecha_registro DESC 
        LIMIT $start, $limit";

$result = $conn->query($sql);

$sql_count = "SELECT count(a.id) as total FROM asesorias a JOIN ciudadanos c ON a.ciudadano_id = c.id $where";
$total_records = $conn->query($sql_count)->fetch_assoc()['total'] ?? 0;
?>

<!-- Título Principal -->
<div class="row mb-4">
    <div class="col-md-12">
        <h2 class="fw-bold" style="color: var(--color-primary);"><i class="fas fa-chalkboard-teacher"></i> Gestión de Asesorías</h2>
        <p class="text-muted">Tablero de control y seguimiento de atenciones al ciudadano.</p>
    </div>
</div>

<!-- Sección Analítica (Dashboard) -->
<div class="mb-4">
    <?php include 'includes/analitica_asesorias.php'; ?>
</div>

<!-- Listado y Búsqueda -->
<div class="row mb-3 align-items-end">
    <div class="col-md-6">
        <h4 class="text-primary mb-0"><i class="fas fa-list"></i> Listado de Registros</h4>
        <small class="text-muted">Mostrando resultados filtrados (Total: <?= $total_records ?>)</small>
    </div>
    <div class="col-md-6 text-end">
        <form action="" method="GET" class="d-flex">
            <input type="text" name="q" class="form-control me-2" placeholder="Buscar por Nombre, Cédula o Radicado..." value="<?= $search ?>">
            <button class="btn btn-primary" type="submit">Buscar</button>
            <?php if($search): ?>
                <a href="panel_asesorias.php" class="btn btn-secondary ms-2">Limpiar</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive" style="max-height: 700px; overflow-y: auto;">
            <table class="table table-striped table-hover table-sm text-nowrap align-middle">
                <thead class="table-dark sticky-top">
                    <tr>
                        <th class="text-center" style="width: 50px;">Acción</th> <!-- Mover Acción al inicio -->
                        <th colspan="2" class="text-center border-end">Ciudadano</th>
                        <th colspan="4" class="text-center border-end">Datos de la Asesoría</th>
                        <th class="text-center">Estado</th>
                    </tr>
                    <tr>
                        <!-- Acción -->
                        <th class="text-center"><i class="fas fa-cogs"></i></th>

                        <!-- Ciudadano -->
                        <th>Documento</th>
                        <th class="border-end">Nombre Completo</th>
                        
                        <!-- Asesoría -->
                        <th>Radicado</th>
                        <th>Fecha Registro</th>
                        <th>País Trámite</th>
                        <th class="border-end">Observación Final</th>
                        
                        <!-- Estado -->
                        <th class="text-center">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <!-- Acciones (Al inicio para facil acceso) -->
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-sm btn-outline-primary" title="Gestionar Asesoría" onclick='gestionarAsesoria(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, "UTF-8"); ?>)'>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-info" title="Ver Detalle Ciudadano" onclick='verCiudadano(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, "UTF-8"); ?>)'>
                                            <i class="fas fa-user-eye"></i>
                                        </button>
                                    </div>
                                </td>
                                
                                <!-- Ciudadano -->
                                <td><span class="badge bg-light text-dark"><?= $row['tipo_documento'] ?> <?= $row['numero_documento'] ?></span></td>
                                <td class="fw-bold border-end text-truncate" style="max-width: 200px;" title="<?= $row['nombres'] ?> <?= $row['apellidos'] ?>">
                                    <?= $row['nombres'] ?> <?= $row['apellidos'] ?>
                                </td>
                                
                                <!-- Asesoría -->
                                <td><span class="badge bg-primary bg-opacity-10 text-primary"><?= $row['codigo_radicado'] ?></span></td>
                                <td><?= date('Y-m-d', strtotime($row['fecha_registro'])) ?></td>
                                <td><?= $row['pais_tramite'] ?? 'N/A' ?></td>
                                <td class="border-end text-truncate" style="max-width: 150px;" title="<?= $row['observacion_final'] ?>"><?= $row['observacion_final'] ?></td>
                                
                                <!-- Estado -->
                                <td class="text-center">
                                    <?php 
                                        $badges = [
                                            'Pendiente' => 'bg-warning text-dark',
                                            'En Proceso' => 'bg-info text-dark',
                                            'Finalizado' => 'bg-success'
                                        ];
                                        $bg = $badges[$row['estado']] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?= $bg ?>"><?= $row['estado'] ?></span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center text-muted py-3">No hay asesorías registradas con ese criterio.</td></tr>
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

<!-- Modal para Gestionar Asesoría -->
<div class="modal fade" id="modalGestion" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Actualizar Asesoría</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formAsesoria" action="controllers/actualizar_asesoria.php" method="POST">
                    <input type="hidden" name="id" id="asesoria_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Radicado</label>
                        <input type="text" class="form-control" id="mod_radicado" readonly disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Estado del Trámite</label>
                        <select name="estado" id="mod_estado" class="form-select">
                            <option value="Pendiente">Pendiente</option>
                            <option value="En Proceso">En Proceso</option>
                            <option value="Finalizado">Finalizado</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">País del Trámite (Si aplica)</label>
                        <input type="text" name="pais_tramite" id="mod_pais" class="form-control" placeholder="Ej: Colombia, España...">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Observaciones Finales / Notas de Gestión</label>
                        <textarea name="notas_gestion" id="mod_notas" class="form-control" rows="4"></textarea>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </div>
                </form>
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

function gestionarAsesoria(data) {
    if(!data) return;
    document.getElementById('asesoria_id').value = data.id;
    document.getElementById('mod_radicado').value = data.codigo_radicado;
    document.getElementById('mod_estado').value = data.estado;
    document.getElementById('mod_pais').value = data.pais_tramite || '';
    document.getElementById('mod_notas').value = data.observacion_final || '';
    
    new bootstrap.Modal(document.getElementById('modalGestion')).show();
}
</script>

<?php include 'includes/footer.php'; ?>