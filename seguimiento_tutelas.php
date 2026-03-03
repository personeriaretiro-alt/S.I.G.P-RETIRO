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
$sql = "SELECT t.*, 
        c.nombres, c.apellidos, c.numero_documento, c.tipo_documento, c.telefono, c.email,
        u.nombre_completo as nombre_registra,
        c.genero, c.grupo_poblacional, c.zona_residencia, c.barrio_vereda, c.firma_digital
        FROM tutelas t 
        JOIN ciudadanos c ON t.ciudadano_id = c.id 
        LEFT JOIN usuarios u ON t.usuario_registra_id = u.id
        $where 
        ORDER BY t.created_at DESC 
        LIMIT $start, $limit";

$result = $conn->query($sql);

// Conteo total para paginación (respetando filtros)
$sql_count = "SELECT count(t.id) as total FROM tutelas t JOIN ciudadanos c ON t.ciudadano_id = c.id $where";
$total_records = $conn->query($sql_count)->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);


?>

<div class="d-flex justify-content-between flex-wrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div class="mb-2 mb-md-0">
        <h2 class="fw-bold text-primary"><i class="fas fa-gavel me-2"></i>Gestión de Casos</h2>
    </div>
    <div class="d-flex gap-2">
        <a href="controllers/exportar_tutelas_excel.php" class="btn btn-success text-white shadow-sm">
            <i class="fas fa-file-excel me-1"></i> Exportar a Excel
        </a>
        <a href="nuevo_tramite.php" class="btn btn-primary shadow-sm">
            <i class="fas fa-plus me-1"></i> Nueva Tutela
        </a>
    </div>
</div>



<div class="row mb-3">
    <div class="col-md-6 d-flex align-items-end">
        <h4 class="text-primary mb-0"><i class="fas fa-list"></i> Listado</h4>
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
            <table class="table table-striped table-hover table-sm align-middle" style="font-size: 0.95rem;">
                <thead class="table-dark text-white sticky-top align-middle">
                    <tr class="text-center">
                        <th style="width: 4%"><i class="fas fa-cogs"></i></th>
                        <th style="width: 6%">Radicado</th>
                        <th style="width: 5%">Tipo</th>
                        <th style="width: 7%">Documento</th>
                        <th style="width: 13%">Ciudadano</th>
                        <th style="width: 7%">Fecha</th>
                        <th style="width: 13%">Derecho / Asunto</th>
                        <th style="width: 8%">Radicado J.</th>
                        <th style="width: 10%">Juzgado</th>
                        <th style="width: 5%">Est.</th>
                        <th style="width: 4%">Adm.</th>
                        <th style="width: 5%">Fallo</th>
                        <th style="width: 4%">Inc.</th>
                        <th style="width: 5%">Resp.</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <!-- Estilo diferente para sub-tramites -->
                            <tr class="<?= $row['parent_id'] ? 'table-warning' : '' ?>">
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

                        <!-- Radicado Interno -->
                        <td>
                            <span class="badge bg-primary text-white text-wrap" style="width: 6.5rem; font-size: 0.85rem;"><?= $row['codigo_radicado_interno'] ?? 'N/A' ?></span>
                            <?php if($row['parent_id']): ?>
                                <br><small class="text-muted d-block text-wrap fw-bold" style="width: 6.5rem; font-size: 0.75rem;"><i class="fas fa-level-up-alt"></i> Sub: <?= $row['parent_id'] ?></small>
                            <?php endif; ?>
                        </td>
                                
                        <!-- Tipo Trámite -->
                        <td class="text-center">
                            <?php 
                            $tipo = $row['tipo_tramite'] ?? 'Tutela'; // Valor default para registros viejos
                            $bgTipo = 'bg-secondary text-white';
                            if ($tipo == 'Derecho de Petición') { $bgTipo = 'bg-info text-dark'; $tipo = 'D. Pet'; }
                            if ($tipo == 'Actuación Previa') { $bgTipo = 'bg-warning text-dark'; $tipo = 'Actuac.'; }
                            if ($tipo == 'Tutela') { $tipo = 'Tutela'; }
                            if ($tipo == 'Incidente') { $tipo = 'Inci.'; }
                            ?>
                            <span class="badge <?= $bgTipo ?>"><?= $tipo ?></span>
                        </td>


                        <!-- Documento -->
                        <td class="text-center">
                            <span class="badge bg-light text-dark border fw-bold" style="font-size: 0.85rem;"><?= $row['tipo_documento'] ?></span>
                            <div class="fw-bold mt-1" style="font-size: 0.9rem;"><?= $row['numero_documento'] ?></div>
                        </td>

                        <!-- Ciudadano -->
                        <td class="text-wrap">
                            <span class="fw-bold"><?= $row['nombres'] ?> <?= $row['apellidos'] ?></span>
                        </td>
                        
                        <!-- Fecha -->
                        <td class="text-center"><span class="fw-bold"><?= date('d/m/Y', strtotime($row['created_at'])) ?></span></td>
                        
                        <!-- Derecho/Asunto -->
                        <td class="text-wrap">
                            <?php if($row['tipo_atencion']): ?>
                                <strong class="fw-bold"><?= $row['tipo_atencion'] ?></strong>
                            <?php else: ?>
                                <span class="fw-semibold"><?= $row['derecho_amparado'] ?></span>
                            <?php endif; ?>
                        </td>

                        <!-- Radicado Juzgado -->
                        <td class="text-wrap">
                            <?php if(empty($row['radicado_tutela'])): ?>
                                <span class="badge bg-light text-muted border" style="font-size: 0.8rem;">Pendiente</span>
                            <?php else: ?>
                                <span class="text-break fw-bold"><?= $row['radicado_tutela'] ?></span>
                            <?php endif; ?>
                        </td>
                        
                        <!-- Juzgado -->
                        <td class="text-wrap"><?= $row['juzgado'] ?: '<span class="text-muted fst-italic">Sin asignar</span>' ?></td>

                        <!-- Estados -->
                        <td class="text-center">
                            <span class="badge bg-primary text-wrap" style="font-size: 0.85rem;"><?= $row['estado'] ?></span>
                        </td>

                        <td class="text-center" style="font-size: 1.1rem;">
                             <?php 
                                $admitidaClass = match($row['admitida']) {
                                    'SI' => 'text-success',
                                    'NO' => 'text-danger',
                                    default => 'text-muted'
                                };
                                echo $row['admitida'] ? "<i class='fas fa-circle $admitidaClass' title='{$row['admitida']}'></i>" : '-'; 
                            ?>
                        </td>
                        <td class="text-center"><?= $row['concedio_tutela'] ? substr($row['concedio_tutela'], 0, 10).'...' : '-' ?></td>
                        <td class="text-center fw-bold"><?= $row['incidente_desacato'] ?></td>

                        <!-- Usuario Registra -->
                        <td class="text-center"><span class="text-muted" style="font-size: 0.8rem;"><?= $row['nombre_registra'] ?? 'Syst' ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="14" class="text-center text-muted py-3">No se encontraron tutelas registradas.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
<nav aria-label="Page navigation" class="mt-3">
    <ul class="pagination pagination-sm justify-content-center">
        <!-- Previous -->
        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
            <a class="page-link" href="?page=<?= $page - 1 ?>&q=<?= urlencode($search) ?>" aria-label="Previous">
                <span aria-hidden="true">&laquo;</span>
            </a>
        </li>
        
        <!-- Pages -->
        <?php 
        $start_page = max(1, $page - 2);
        $end_page = min($total_pages, $page + 2);
        
        if($start_page > 1) { 
            echo '<li class="page-item"><a class="page-link" href="?page=1&q='.urlencode($search).'">1</a></li>';
            if($start_page > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }

        for ($i = $start_page; $i <= $end_page; $i++): 
        ?>
            <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>&q=<?= urlencode($search) ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>

        <?php 
        if($end_page < $total_pages) {
            if($end_page < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            echo '<li class="page-item"><a class="page-link" href="?page='.$total_pages.'&q='.urlencode($search).'">'.$total_pages.'</a></li>';
        }
        ?>

        <!-- Next -->
        <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
            <a class="page-link" href="?page=<?= $page + 1 ?>&q=<?= urlencode($search) ?>" aria-label="Next">
                <span aria-hidden="true">&raquo;</span>
            </a>
        </li>
    </ul>
</nav>
<?php endif; ?>

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

                    <div class="col-12 mt-2"><strong><i class="fas fa-signature"></i> Firma Digital:</strong></div>
                    <div class="col-12 text-center border rounded p-2 bg-light">
                        <img id="view_firma" src="" alt="Firma no disponible" class="img-fluid" style="max-height: 80px; display: none;">
                        <span id="view_firma_msg" class="text-muted small fst-italic">No registra firma digital.</span>
                    </div>
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
                
                <hr>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-muted mb-0"><i class="fas fa-history"></i> Historial de Trámites</h6>
                    <span class="badge bg-secondary" id="count_historial">0</span>
                </div>
                <div class="border rounded p-2 bg-light" style="max-height: 200px; overflow-y: auto;">
                    <table class="table table-sm table-striped mb-0" style="font-size: 0.85rem;">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Estado</th>
                                <th>Radicado</th>
                            </tr>
                        </thead>
                        <tbody id="lista_historial_tramites">
                            <tr><td colspan="4" class="text-center text-muted">Cargando...</td></tr>
                        </tbody>
                    </table>
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
                    <input type="hidden" name="tipo_tramite_actual" id="tipo_tramite_actual">
                    
                    <!-- SECCIÓN ESPECIAL PARA ACTUACIONES PREVIAS (Edit Mode) -->
                    <div id="seccion_actuacion_previa" style="display: none;" class="bg-warning bg-opacity-10 p-3 rounded mb-3 border border-warning">
                         <h6 class="text-dark fw-bold border-bottom border-warning pb-2"><i class="fas fa-link"></i> Datos de la Actuación</h6>
                         <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Tipo Actuación</label>
                                <select class="form-select" name="tipo_atencion_actuacion" id="mod_tipo_actuacion">
                                    <option value="RECURSOS">RECURSOS</option>
                                    <option value="IMPUGNACIÓN">IMPUGNACIÓN</option>
                                    <option value="IMPEDIMENTO">IMPEDIMENTO</option>
                                </select>
                            </div>
                             <div class="col-md-4">
                                <label class="form-label">Fecha Radicación</label>
                                <input type="date" class="form-control" name="fecha_radicacion_actuacion" id="mod_fecha_rad_actuacion">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Estado</label>
                                <select class="form-select" name="estado_actuacion" id="mod_estado_actuacion">
                                    <option value="ESTUDIO">ESTUDIO</option>
                                    <option value="PENDIENTE">PENDIENTE</option>
                                    <option value="RADICADO">RADICADO</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Decisión</label>
                                <select class="form-select" name="decision_actuacion" id="mod_decision_actuacion">
                                    <option value="">Seleccione...</option>
                                    <option value="SI">SI</option>
                                    <option value="NO">NO</option>
                                </select>
                            </div>
                         </div>
                    </div>

                    <div id="seccion_tutela_normal">
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
                    
                    <h6 class="text-muted border-bottom pb-2 mt-4">Detalles del Trámite</h6>
                    <div class="row g-3 mb-3">
                         <div class="col-md-3">
                            <label class="form-label">Radicado</label>
                            <select class="form-select" name="es_radicado" id="mod_es_radicado">
                                <option value="NO">NO</option>
                                <option value="SI">SI</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Pendiente Respuesta</label>
                            <select class="form-select" name="pendiente_respuesta" id="mod_pendiente_resp" onchange="toggleFechaResp()">
                                <option value="NO">NO</option>
                                <option value="SI">SI</option>
                            </select>
                        </div>
                        <div class="col-md-3" id="divFechaResp" style="display:none;">
                            <label class="form-label">F. Est. Respuesta</label>
                            <input type="date" class="form-control" name="fecha_estimada_respuesta" id="mod_fecha_resp">
                        </div>
                         <div class="col-md-3">
                            <label class="form-label">Respuesta Email</label>
                            <select class="form-select" name="recibe_respuesta_email" id="mod_resp_email">
                                <option value="NO">NO</option>
                                <option value="SI">SI</option>
                            </select>
                        </div>
                    </div>

                    <h6 class="text-muted border-bottom pb-2 mt-4">Incidente de Desacato</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">¿Inicia Incidente?</label>
                            <select class="form-select" name="incidente_desacato" id="mod_incidente" onchange="toggleIncidente()">
                                <option value="NO">NO</option>
                                <option value="SI">SI</option>
                                <option value="Pendiente">Pendiente</option>
                            </select>
                        </div>
                        <div class="col-md-4" id="divFechaRadicacionDesacato" style="display:none;">
                            <label class="form-label">Fecha Radicación</label>
                            <input type="date" class="form-control" name="fecha_radicacion_desacato" id="mod_f_radicacion_desacato">
                        </div>
                        <div class="col-md-4" id="divSancionDesacato" style="display:none;">
                            <label class="form-label">¿Hubo Sanción?</label>
                            <select class="form-select" name="sancion_desacato" id="mod_sancion_desacato">
                                <option value="NO">NO</option>
                                <option value="SI">SI</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Observaciones</label>
                        <textarea class="form-control" name="observaciones" rows="3"></textarea>
                    </div>

                    </div> <!-- Fin Sección Normal -->

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
    
    // Firma Digital
    var imgFirma = el('view_firma');
    var msgFirma = el('view_firma_msg');
    
    if (data.firma_digital && data.firma_digital.trim() !== "") {
        imgFirma.src = data.firma_digital;
        imgFirma.style.display = 'inline-block';
        msgFirma.style.display = 'none';
        // Agrandar la visualizacion al dar click
        imgFirma.style.cursor = 'pointer';
        imgFirma.onclick = function() {
             Swal.fire({
                imageUrl: data.firma_digital,
                imageAlt: 'Firma Ciudadano',
                showConfirmButton: false,
                width: 600
             });
        };
    } else {
        imgFirma.src = '#';
        imgFirma.style.display = 'none';
        msgFirma.style.display = 'block';
    }

    // Datos adicionales del caso
    el('view_id_interno').innerHTML = '<span class="badge bg-secondary">' + data.id + '</span>';
    el('view_responsable').innerText = data.nombre_registra || 'Sin Asignar'; // Use nombre_registra from SQL
    
    // Cargar Historial
    cargarHistorialEnModal(data.ciudadano_id);

    var myModal = new bootstrap.Modal(document.getElementById('modalVerCiudadano'));
    myModal.show();
}

function cargarHistorialEnModal(cid) {
    if(!cid) return;
    
    var tbody = document.getElementById('lista_historial_tramites');
    var badge = document.getElementById('count_historial');
    
    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>';
    badge.innerText = '0';
    
    fetch('controllers/buscar_tutelas_ciudadano.php?type=all&id=' + cid)
    .then(res => res.json())
    .then(data => {
        tbody.innerHTML = '';
        if(data && data.length > 0) {
            badge.innerText = data.length;
            data.forEach(t => {
                let row = `
                    <tr>
                        <td><small>${t.fecha_radicacion || 'N/A'}</small></td>
                        <td><small class="text-truncate d-inline-block" style="max-width: 150px;" title="${t.tipo_atencion}">${t.tipo_atencion}</small></td>
                        <td><span class="badge bg-light text-dark border">${t.estado}</span></td>
                        <td><small>${t.radicado || 'Int: ' + (t.codigo_radicado_interno || 'N/A')}</small></td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No registra historial previo.</td></tr>';
        }
    })
    .catch(err => {
        console.error(err);
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Error al cargar historial.</td></tr>';
    });
}

function toggleFechaResp() {
    var sel = document.getElementById('mod_pendiente_resp');
    var div = document.getElementById('divFechaResp');
    if(sel.value === 'SI') {
        div.style.display = 'block';
    } else {
        div.style.display = 'none';
        document.getElementById('mod_fecha_resp').value = '';
    }
}

function toggleIncidente() {
    var sel = document.getElementById('mod_incidente');
    var divFecha = document.getElementById('divFechaRadicacionDesacato');
    var divSancion = document.getElementById('divSancionDesacato');
    
    // Si selecciona SI o cualquier valor distinto de NO/Vacio
    if(sel.value === 'SI' || sel.value === 'Pendiente') {
        divFecha.style.display = 'block';
        divSancion.style.display = 'block';
    } else {
        divFecha.style.display = 'none';
        divSancion.style.display = 'none';
        // Limpiar campos si se ocultan (opcional, pero buena práctica)
        document.getElementById('mod_f_radicacion_desacato').value = '';
        document.getElementById('mod_sancion_desacato').value = 'NO';
    }
}

function editarTutela(data) {
    console.log(data); // Debug
    if (!data) return;

    var el = function(id) { return document.getElementById(id); };

    // Common ID
    el('tutela_id').value = data.id || '';
    
    // Check if it is a Sub-case (Actuación Previa)
    var isSubCase = data.parent_id && data.parent_id != '0';
    
    var secNormal = document.getElementById('seccion_tutela_normal');
    var secActuacion = document.getElementById('seccion_actuacion_previa');
    
    // Helper to toggle inputs disabled state
    var toggleInputs = function(container, disable) {
        var inputs = container.querySelectorAll('input, select, textarea');
        inputs.forEach(function(input) {
            input.disabled = disable;
        });
    };

    if (isSubCase) {
        // Mode: Actuación Previa
        secNormal.style.display = 'none';
        secActuacion.style.display = 'block';
        
        toggleInputs(secNormal, true); // Disable normal fields
        toggleInputs(secActuacion, false); // Enable sub-case fields
        
        // Populate Sub-case fields
        el('mod_tipo_actuacion').value = data.tipo_atencion || 'RECURSOS';
        el('mod_fecha_rad_actuacion').value = data.fecha_radicacion_actuacion || '';
        el('mod_estado_actuacion').value = data.estado_actuacion || 'ESTUDIO';
        el('mod_decision_actuacion').value = data.decision_actuacion || '';
        
        // Set hidden flag
        if(el('tipo_tramite_actual')) el('tipo_tramite_actual').value = 'ACTUACION';

    } else {
        // Mode: Tutela Normal
        secNormal.style.display = 'block';
        secActuacion.style.display = 'none';
        
        toggleInputs(secNormal, false); // Enable normal fields
        toggleInputs(secActuacion, true); // Disable sub-case fields
        
        // Populate Normal fields
        el('mod_radicado').value = data.radicado_tutela || '';
        el('mod_derecho').value = data.derecho_amparado || '';
        el('mod_juzgado').value = data.juzgado || '';
        el('mod_accionado').value = data.persona_vinculada || ''; 
        el('mod_admitida').value = data.admitida || 'Pendiente';
        el('mod_f_admision').value = data.fecha_admision || '';
        el('mod_fallo').value = data.concedio_tutela || ''; 
        
        // Nuevos campos
        el('mod_es_radicado').value = data.es_radicado || 'NO';
        el('mod_pendiente_resp').value = data.pendiente_respuesta || 'NO';
        el('mod_fecha_resp').value = data.fecha_estimada_respuesta || '';
        el('mod_resp_email').value = data.recibe_respuesta_email || 'NO';
        
        // Campos Desacato
        el('mod_incidente').value = data.incidente_desacato || 'NO';
        el('mod_f_radicacion_desacato').value = data.fecha_radicacion_desacato || '';
        el('mod_sancion_desacato').value = data.sancion_desacato || 'NO';
        
        toggleFechaResp(); 
        toggleIncidente(); // Inicializar visibilidad desacato
        
        // Set hidden flag
        if(el('tipo_tramite_actual')) el('tipo_tramite_actual').value = 'NORMAL';
    }
    
    // Obervations
    if(document.querySelector('textarea[name="observaciones"]')) {
        document.querySelector('textarea[name="observaciones"]').value = data.observaciones || '';
    }

    var myModal = new bootstrap.Modal(document.getElementById('modalEditarTutela'));
    myModal.show();
}
</script>

<?php include 'includes/footer.php'; ?>