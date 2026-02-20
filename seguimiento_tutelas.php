<?php
include 'conexion.php';
include 'includes/auth.php';

// Control de Acceso: Solo Roles autorizados (Admin, Personero, Funcionario, Abg. Tutelas)
if (!isset($_SESSION['rol_id']) || !in_array($_SESSION['rol_id'], [1, 2, 3, 11])) {
    // Si no tiene permiso, redirigir al dashboard con alerta (opcional) o silencio
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
    $where .= " AND (c.nombres LIKE '%$search%' OR c.numero_documento LIKE '%$search%' OR t.radicado_tutela LIKE '%$search%')";
}

// Consulta Principal
$sql = "SELECT t.*, c.nombres, c.apellidos, c.numero_documento, c.tipo_documento, c.telefono, c.email,
        c.genero, c.grupo_poblacional, c.zona_residencia, c.barrio_vereda
        FROM tutelas t 
        JOIN ciudadanos c ON t.ciudadano_id = c.id 
        $where 
        ORDER BY t.created_at DESC 
        LIMIT $start, $limit";

$result = $conn->query($sql);

// Conteo total para paginación (respetando filtros)
$sql_count = "SELECT count(t.id) as total FROM tutelas t JOIN ciudadanos c ON t.ciudadano_id = c.id $where";
$total_records = $conn->query($sql_count)->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

// --- ESTADÍSTICAS DEL TABLERO (Globales) ---
// 1. Total Tutelas
$kpi_total = $conn->query("SELECT count(*) as total FROM tutelas")->fetch_assoc()['total'];

// 2. Tutelas del Mes
$kpi_mes = $conn->query("SELECT count(*) as total FROM tutelas WHERE MONTH(fecha_atencion) = MONTH(CURRENT_DATE()) AND YEAR(fecha_atencion) = YEAR(CURRENT_DATE())")->fetch_assoc()['total'];

// 3. Incidentes Activos (Asumiendo que si el campo incidente tiene info, cuenta)
// Ajuste: El nombre real de la columna en BD es 'incidente_desacato'
$kpi_incidentes = $conn->query("SELECT count(*) as total FROM tutelas WHERE incidente_desacato IS NOT NULL AND incidente_desacato != '' AND incidente_desacato != 'No'")->fetch_assoc()['total'];

// 4. Fallos a Favor (Búsqueda aproximada si no es un ENUM estricto)
// Ajuste: El nombre real de la columna es 'concedio_tutela'
$kpi_favor = $conn->query("SELECT count(*) as total FROM tutelas WHERE concedio_tutela LIKE '%Favor%' OR concedio_tutela LIKE '%Concede%' OR concedio_tutela = 'SI'")->fetch_assoc()['total'];

?>

<div class="row mb-4">
    <div class="col-md-12">
        <h2 class="fw-bold" style="color: var(--color-primary);"><i class="fas fa-gavel"></i> Gestión de Tutelas</h2>
        <p class="text-muted">Tablero de control y seguimiento de acciones constitucionales.</p>
    </div>
</div>

<!-- KPIs Tutelas -->
<div class="row mb-5">
    <div class="col-md-3">
        <div class="card card-dashboard h-100 border-start border-4 border-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase mb-2 text-muted small fw-bold">Total Histórico</h6>
                        <div class="h2 mb-0 fw-bold text-primary"><?= $kpi_total ?></div>
                    </div>
                    <div class="bg-primary bg-opacity-10 p-3 rounded-circle text-primary">
                        <i class="fas fa-book fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card card-dashboard h-100 border-start border-4 border-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase mb-2 text-muted small fw-bold">Radicadas este Mes</h6>
                        <div class="h2 mb-0 fw-bold text-info"><?= $kpi_mes ?></div>
                    </div>
                    <div class="bg-info bg-opacity-10 p-3 rounded-circle text-info">
                        <i class="fas fa-calendar-day fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card card-dashboard h-100 border-start border-4 border-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase mb-2 text-muted small fw-bold">Fallos a Favor</h6>
                        <div class="h2 mb-0 fw-bold text-success"><?= $kpi_favor ?></div>
                    </div>
                    <div class="bg-success bg-opacity-10 p-3 rounded-circle text-success">
                        <i class="fas fa-check-double fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card card-dashboard h-100 border-start border-4 border-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase mb-2 text-muted small fw-bold">Incidentes</h6>
                        <div class="h2 mb-0 fw-bold text-warning"><?= $kpi_incidentes ?></div>
                    </div>
                    <div class="bg-warning bg-opacity-10 p-3 rounded-circle text-warning">
                        <i class="fas fa-exclamation-circle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-6 d-flex align-items-end">
        <h4 class="text-primary mb-0"><i class="fas fa-list"></i> Listado de Tutelas</h4>
    </div>
    <div class="col-md-6 text-end">
        <form action="" method="GET" class="d-flex">
            <input type="text" name="q" class="form-control me-2" placeholder="Buscar por Nombre, Cédula o Radicado..." value="<?= $search ?>">
            <button class="btn btn-primary" type="submit">Buscar</button>
            <?php if($search): ?>
                <a href="seguimiento_tutelas.php" class="btn btn-secondary ms-2">Limpiar</a>
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
                        <th colspan="4" class="text-center border-end">Datos de la Tutela</th>
                        <th colspan="3" class="text-center">Estado Procesal</th>
                    </tr>
                    <tr>
                        <!-- Acción -->
                        <th class="text-center"><i class="fas fa-cogs"></i></th>

                        <!-- Ciudadano -->
                        <th>Documento</th>
                        <th class="border-end">Nombre Completo</th>
                        
                        <!-- Tutela General -->
                        <th>F. Atención</th>
                        <th>Derecho Amparado</th>
                        <th>Radicado J.</th>
                        <th class="border-end">Juzgado</th>
                        
                        <!-- Seguimiento -->
                        <th>Admitida</th>
                        <th>Fallo</th>
                        <th>Incidente</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <!-- Acciones (Al inicio para facil acceso) -->
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-sm btn-outline-primary" title="Editar Tutela" onclick='editarTutela(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, "UTF-8"); ?>)'>
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
                                
                                <!-- Tutela -->
                                <td><small><?= date('Y-m-d', strtotime($row['created_at'])) ?></small></td>
                                <td class="text-truncate" style="max-width: 150px;" title="<?= $row['derecho_amparado'] ?>"><?= $row['derecho_amparado'] ?></td>
                                <td>
                                    <?php if(empty($row['radicado_tutela'])): ?>
                                        <span class="badge bg-secondary">Pendiente</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark"><?= $row['radicado_tutela'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="border-end text-truncate" style="max-width: 150px;" title="<?= $row['juzgado'] ?>"><?= $row['juzgado'] ?></td>
                                
                                <!-- Seguimiento -->
                                <td class="text-center">
                                    <?php 
                                        $admitidaClass = match($row['admitida']) {
                                            'SI' => 'bg-success',
                                            'NO' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                    ?>
                                    <span class="badge <?= $admitidaClass ?>"><?= $row['admitida'] ?: 'Pendiente' ?></span>
                                </td>
                                <td><?= $row['concedio_tutela'] ?></td>
                                <td><?= $row['incidente_desacato'] ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="10" class="text-center text-muted py-3">No se encontraron tutelas registradas.</td></tr>
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
                <hr>
                <h6 class="text-muted">Información Adicional del Caso</h6>
                 <div class="row g-2">
                    <div class="col-6"><strong>ID Interno:</strong></div>
                    <div class="col-6" id="view_id_interno"></div>
                    <div class="col-6"><strong>Responsable:</strong></div>
                    <div class="col-6" id="view_responsable"></div>
                    <div class="col-6"><strong>Email Tutela:</strong></div>
                    <div class="col-6" id="view_email_tutela"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edición Tutelas -->
<div class="modal fade" id="modalEditarTutela" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Gestión de Tutela</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formTutela" action="controllers/actualizar_tutela.php" method="POST">
                    <input type="hidden" name="id" id="tutela_id">
                    
                    <div class="row g-3 mb-3">
                         <div class="col-md-6">
                            <label class="form-label">Radicado Tutela (Juzgado)</label>
                            <input type="text" class="form-control" name="radicado_tutela" id="mod_radicado">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Derecho Amparado</label>
                            <input type="text" class="form-control" name="derecho_amparado" id="mod_derecho">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                         <div class="col-md-6">
                            <label class="form-label">Juzgado</label>
                            <input type="text" class="form-control" name="juzgado" id="mod_juzgado">
                        </div>
                         <div class="col-md-6">
                            <label class="form-label">Accionado / Vinculado</label>
                            <input type="text" class="form-control" name="persona_vinculada" id="mod_accionado">
                        </div>
                    </div>
                    
                    <h6 class="text-muted border-bottom pb-2 mt-4">Estado Procesal</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Admitida</label>
                            <select class="form-select" name="admitida" id="mod_admitida">
                                <option value="SI">SI</option>
                                <option value="NO">NO</option>
                                <option value="Pendiente">Pendiente</option>
                            </select>
                        </div>
                         <div class="col-md-4">
                            <label class="form-label">F. Admisión</label>
                            <input type="date" class="form-control" name="fecha_admision" id="mod_f_admision">
                        </div>
                         <div class="col-md-4">
                            <label class="form-label">Tipo Fallo</label>
                            <select class="form-select" name="concedio_tutela" id="mod_fallo">
                                <option value="">Seleccione...</option>
                                <option value="A favor">A favor</option>
                                <option value="En contra">En contra</option>
                                <option value="Parcial">Parcial</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Observaciones</label>
                        <textarea class="form-control" name="observaciones" rows="3"></textarea>
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
    
    // Datos adicionales del caso
    el('view_id_interno').innerHTML = '<span class="badge bg-secondary">' + data.id + '</span>';
    el('view_responsable').innerText = data.responsable_tutela || 'Sin Asignar';
    el('view_email_tutela').innerText = data.email_tutela || '-'; // Dato si existiera
    
    var modal = new bootstrap.Modal(el('modalVerCiudadano'));
    modal.show();
}

function editarTutela(data) {
    console.log(data); // Debug
    if (!data) return;

    var el = function(id) { return document.getElementById(id); };

    el('tutela_id').value = data.id || '';
    el('mod_radicado').value = data.radicado_tutela || '';
    el('mod_derecho').value = data.derecho_amparado || '';
    el('mod_juzgado').value = data.juzgado || '';
    el('mod_accionado').value = data.persona_vinculada || ''; 
    el('mod_admitida').value = data.admitida || 'Pendiente';
    el('mod_f_admision').value = data.fecha_admision || '';
    el('mod_fallo').value = data.concedio_tutela || ''; 
    
    var myModal = new bootstrap.Modal(document.getElementById('modalEditarTutela'));
    myModal.show();
}
</script>

<?php include 'includes/footer.php'; ?>