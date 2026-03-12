<?php
include 'conexion.php';
include 'includes/auth.php';

// Validar acceso: Admin(1), Personero(2), Funcionario(3), Aux. Salud(13)
if (!in_array($_SESSION['rol_id'], [1, 2, 3, 13])) {
    echo "<div class='alert alert-danger'>Acceso Denegado. No tiene permisos para ver este módulo.</div>";
    exit();
}

include 'includes/header.php';

// Búsqueda
$search = isset($_GET['q']) ? $_GET['q'] : '';
$where = "1=1";
if ($search) {
    // Escapar para evitar inyección SQL
    $s_db = $conn->real_escape_string($search);
    $where .= " AND (c.nombres LIKE '%$s_db%' OR c.apellidos LIKE '%$s_db%' OR c.numero_documento LIKE '%$s_db%')";
}

// Filtros para la tabla
if (isset($_GET['estado']) && $_GET['estado'] != '') {
    $where .= " AND t.estado = '" . $conn->real_escape_string($_GET['estado']) . "'";
}

$sql_tramites = "SELECT t.*, 
                 c.numero_documento, c.nombres, c.apellidos, c.telefono, c.email, 
                 c.genero, c.grupo_poblacional, c.zona_residencia, c.barrio_vereda, c.firma_digital
                 FROM tramites_salud t 
                 JOIN ciudadanos c ON t.ciudadano_id = c.id 
                 WHERE $where 
                 ORDER BY t.fecha_atencion DESC, t.id DESC";
$res_tramites = $conn->query($sql_tramites);
?>

<div class="content-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-primary"><i class="fas fa-heartbeat"></i> Panel de Trámites en Salud</h2>
        <a href="nuevo_tramite.php" class="btn btn-primary"><i class="fas fa-plus"></i> Nuevo Trámite</a>
    </div>

    <!-- Búsqueda y Filtros -->
    <div class="row mb-3">
        <div class="col-md-6 d-flex align-items-end">
            <h4 class="text-primary mb-0"><i class="fas fa-list"></i> Listado de Trámites</h4>
        </div>
        <div class="col-md-6 text-end">
            <form action="Tramites_salud.php" method="GET" class="d-flex">
                <input type="hidden" name="estado" value="<?= isset($_GET['estado']) ? htmlspecialchars($_GET['estado']) : '' ?>">
                <input type="text" name="q" class="form-control me-2" placeholder="Buscar por Nombre, Apellido o Documento..." value="<?= htmlspecialchars($search) ?>">
                <button class="btn btn-primary" type="submit">Buscar</button>
                <?php if($search || (isset($_GET['estado']) && $_GET['estado'] != '')): ?>
                    <a href="Tramites_salud.php" class="btn btn-secondary ms-2">Limpiar</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Tabla -->
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-end align-items-center">
            <form method="GET" class="d-flex" action="Tramites_salud.php">
                <input type="hidden" name="q" value="<?= htmlspecialchars($search) ?>">
                <select name="estado" class="form-select form-select-sm me-2" onchange="this.form.submit()">
                    <option value="">Todos los estados</option>
                    <option value="Pendiente" <?= (isset($_GET['estado']) && $_GET['estado']=='Pendiente')?'selected':'' ?>>Pendiente</option>
                    <option value="En Trámite" <?= (isset($_GET['estado']) && $_GET['estado']=='En Trámite')?'selected':'' ?>>En Trámite</option>
                    <option value="Resuelto" <?= (isset($_GET['estado']) && $_GET['estado']=='Resuelto')?'selected':'' ?>>Resuelto</option>
                    <option value="Cerrado" <?= (isset($_GET['estado']) && $_GET['estado']=='Cerrado')?'selected':'' ?>>Cerrado</option>
                </select>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Ciudadano</th>
                            <th>Servicio Solicitado</th>
                            <th>EPS</th>
                            <th>Estado</th>
                            <th>Gestionado por</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($res_tramites->num_rows > 0): ?>
                            <?php while($t = $res_tramites->fetch_assoc()): ?>
                            <tr>
                                <td>#<?= $t['id'] ?></td>
                                <td><?= empty($t['fecha_atencion']) || $t['fecha_atencion'] == '0000-00-00' ? 'N/A' : date('d/m/Y', strtotime($t['fecha_atencion'])) ?></td>
                                <td>
                                    <strong><?= $t['nombres'] ?> <?= $t['apellidos'] ?></strong><br>
                                    <small class="text-muted"><i class="fas fa-id-card"></i> <?= $t['numero_documento'] ?></small>
                                </td>
                                <td><?= mb_strimwidth($t['servicio_solicitado'], 0, 30, '...') ?></td>
                                <td><?= $t['eps'] ?></td>
                                <td>
                                    <?php 
                                        $bg = 'bg-secondary';
                                        if (strtolower($t['estado']) == 'pendiente') $bg = 'bg-warning text-dark';
                                        if (strtolower($t['estado']) == 'en trámite' || strtolower($t['estado']) == 'en proceso') $bg = 'bg-info text-dark';
                                        if (strtolower($t['estado']) == 'resuelto' || strtolower($t['estado']) == 'cerrado') $bg = 'bg-success';
                                    ?>
                                    <span class="badge <?= $bg ?>"><?= $t['estado'] ?></span>
                                </td>
                                <td><?= $t['gestionado_a_traves_de'] ?></td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-sm btn-outline-info" title="Ver Detalle Ciudadano" onclick='verDetalle(<?= htmlspecialchars(json_encode($t), ENT_QUOTES, "UTF-8") ?>)'>
                                            <i class="fas fa-user-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-primary" title="Editar Trámite" onclick='editarTramiteSalud(<?= htmlspecialchars(json_encode($t), ENT_QUOTES, "UTF-8") ?>)'>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="text-center py-4">No hay registros de trámites de salud.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edición -->
<div class="modal fade" id="modalEditarSalud" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="controllers/actualizar_salud.php" method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Editar Trámite Salud #<span id="modal_id_display"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="tramite_id" id="modal_tramite_id">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Servicio Solicitado</label>
                            <input type="text" class="form-control" name="servicio_solicitado" id="modal_servicio_solicitado">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">EPS</label>
                            <input type="text" class="form-control" name="eps" id="modal_eps">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Gestionado a través de</label>
                            <input type="text" class="form-control" name="gestionado_a_traves_de" id="modal_gestionado">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estado</label>
                            <select class="form-select" name="estado" id="modal_estado">
                                <option value="Pendiente">Pendiente</option>
                                <option value="En Trámite">En Trámite</option>
                                <option value="Resuelto">Resuelto</option>
                                <option value="Cerrado">Cerrado</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Realizado por</label>
                            <input type="text" class="form-control" name="realizado_por" id="modal_realizado_por">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Respuesta Super Salud</label>
                            <input type="text" class="form-control" name="r_super_salud" id="modal_r_super_salud">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Observaciones</label>
                            <textarea class="form-control" name="observaciones" id="modal_observaciones" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Ver Detalle Ciudadano -->
<div class="modal fade" id="modalVerDetalle" tabindex="-1">
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
                <h6 class="text-muted">Información Adicional del Caso de Salud</h6>
                 <div class="row g-2">
                    <div class="col-6"><strong>ID Interno:</strong></div>
                    <div class="col-6" id="view_id_interno"></div>
                    <div class="col-6"><strong>Fecha Atención:</strong></div>
                    <div class="col-6" id="view_fecha"></div>
                    <div class="col-6"><strong>Realizado Por:</strong></div>
                    <div class="col-6" id="view_realizado"></div>
                </div>
                
                <hr>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-muted mb-0">Observaciones del Trámite</h6>
                </div>
                <div class="bg-light p-2 border rounded" style="min-height: 50px;" id="view_observaciones">
                </div>
                
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
    function verDetalle(data) {
        document.getElementById('view_nombre').innerText = data.nombres + ' ' + (data.apellidos ? data.apellidos : '');
        document.getElementById('view_documento').innerText = data.numero_documento;
        document.getElementById('view_telefono').innerText = data.telefono || 'N/A';
        document.getElementById('view_email').innerText = data.email || 'N/A';
        document.getElementById('view_genero').innerText = data.genero || 'N/A';
        document.getElementById('view_grupo').innerText = data.grupo_poblacional || 'N/A';
        document.getElementById('view_zona').innerText = data.zona_residencia || 'N/A';
        document.getElementById('view_barrio').innerText = data.barrio_vereda || 'N/A';
        
        let firmaImg = document.getElementById('view_firma');
        let firmaMsg = document.getElementById('view_firma_msg');
        if (data.firma_digital && data.firma_digital !== '') {
            firmaImg.src = data.firma_digital;
            firmaImg.style.display = 'inline-block';
            firmaMsg.style.display = 'none';
        } else {
            firmaImg.style.display = 'none';
            firmaImg.src = '';
            firmaMsg.style.display = 'inline-block';
        }
        
        document.getElementById('view_id_interno').innerText = data.id;
        document.getElementById('view_fecha').innerText = data.fecha_atencion || 'N/A';
        document.getElementById('view_realizado').innerText = data.realizado_por || 'N/A';
        document.getElementById('view_observaciones').innerText = data.observaciones || 'No registra observaciones iniciales.';
        
        var myModal = new bootstrap.Modal(document.getElementById('modalVerDetalle'));
        myModal.show();
    }

    function editarTramiteSalud(data) {
        document.getElementById('modal_id_display').innerText = data.id;
        document.getElementById('modal_tramite_id').value = data.id;
        document.getElementById('modal_servicio_solicitado').value = data.servicio_solicitado;
        document.getElementById('modal_eps').value = data.eps;
        document.getElementById('modal_gestionado').value = data.gestionado_a_traves_de;
        
        let estadoSelect = document.getElementById('modal_estado');
        // Check if value exists, otherwise keep select but add new option dynamically
        let exists = false;
        for (let i = 0; i < estadoSelect.options.length; i++) {
            if (estadoSelect.options[i].value.toLowerCase() === data.estado.toLowerCase()) {
                estadoSelect.selectedIndex = i;
                exists = true;
                break;
            }
        }
        if(!exists && data.estado) {
            let opt = document.createElement('option');
            opt.value = data.estado;
            opt.text = data.estado;
            estadoSelect.add(opt);
            estadoSelect.value = data.estado;
        }

        document.getElementById('modal_realizado_por').value = data.realizado_por;
        document.getElementById('modal_r_super_salud').value = data.r_super_salud;
        document.getElementById('modal_observaciones').value = data.observaciones;

        var myModal = new bootstrap.Modal(document.getElementById('modalEditarSalud'));
        myModal.show();
    }
</script>

<?php include 'includes/footer.php'; ?>
