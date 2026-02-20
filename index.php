<?php
include 'conexion.php';
include 'includes/auth.php'; // Protege la ruta
include 'includes/header.php'; // Navbar

// Obtener estadísticas rápidas
$id_usuario = $_SESSION['usuario_id'];

// 1. Total mis casos activos
$sql_activos = "SELECT count(*) as total FROM radicados WHERE usuario_asignado_id = $id_usuario AND estado != 'Cerrado'";
$res_activos = $conn->query($sql_activos);
$activos = $res_activos->fetch_assoc()['total'];

// 2. Total Casos Vencidos (Alerta Roja)
$sql_vencidos = "SELECT count(*) as total FROM radicados WHERE usuario_asignado_id = $id_usuario AND estado = 'Vencido'";
$vencidos = $conn->query($sql_vencidos)->fetch_assoc()['total'];

// 3. Pendientes de hoy
$sql_nuevos = "SELECT count(*) as total FROM radicados WHERE usuario_asignado_id = $id_usuario AND fecha_inicio >= CURDATE()";
$res_nuevos = $conn->query($sql_nuevos)->fetch_assoc()['total'];

?>

<div class="row mb-4">
    <div class="col-md-12">
        <h2 class="fw-bold" style="color: var(--color-primary);">Tablero de Control</h2>
        <p class="text-muted">Bienvenido, <?php echo $_SESSION['usuario_nombre']; ?>. Aquí está el resumen de sus procesos.</p>
    </div>
</div>


<?php
// Obtener el rol para condicionar la vista
$rol = $_SESSION['rol_id'] ?? 0;
?>

<!-- Resumen General -->
<div class="row mb-5">
    <div class="col-md-4">
        <div class="card card-dashboard h-100 border-start border-4 border-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase mb-2 text-muted small fw-bold">Mis Casos Activos</h6>
                        <div class="h2 mb-0 fw-bold text-primary"><?php echo $activos; ?></div>
                    </div>
                    <div class="bg-primary bg-opacity-10 p-3 rounded-circle text-primary">
                        <i class="fas fa-folder-open fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card card-dashboard h-100 border-start border-4 border-danger">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase mb-2 text-muted small fw-bold">Vencidos / Riesgo</h6>
                        <div class="h2 mb-0 fw-bold text-danger"><?php echo $vencidos; ?></div>
                    </div>
                    <div class="bg-danger bg-opacity-10 p-3 rounded-circle text-danger">
                        <i class="fas fa-exclamation-triangle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-dashboard h-100 border-start border-4 border-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase mb-2 text-muted small fw-bold">Nuevos Hoy</h6>
                        <div class="h2 mb-0 fw-bold text-success"><?php echo $res_nuevos; ?></div>
                    </div>
                    <div class="bg-success bg-opacity-10 p-3 rounded-circle text-success">
                        <i class="fas fa-calendar-check fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Detalle por Procesos (Condicional según rol) -->
<h4 class="mb-3 fw-bold text-secondary"><i class="fas fa-chart-pie me-2"></i>Gestión por Áreas</h4>
<div class="row g-4 mb-4">
    
    <!-- Tutelas: Visible para Admin (1), Personero (2), Funcionario (3), Tutelas (11) -->
    <?php if(in_array($rol, [1, 2, 3, 11])): ?>
    <div class="col-md-6 col-lg-3">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-header bg-white border-bottom-0 pb-0">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark m-0">Tutelas</h6>
                    <span class="badge bg-primary rounded-pill">Activas: 12</span>
                </div>
            </div>
            <div class="card-body text-center">
                <div class="display-6 fw-bold my-2" style="color: var(--color-primary);">85%</div>
                <div class="progress mb-2" style="height: 6px;">
                    <div class="progress-bar" style="width: 85%; background-color: var(--color-primary);"></div>
                </div>
                <small class="text-muted">Efectividad mensual</small>
                <hr>
                <div class="row text-center mt-3">
                    <div class="col-6 border-end">
                        <h5 class="mb-0 fw-bold">45</h5>
                        <small class="text-secondary" style="font-size: 0.75rem;">Total Año</small>
                    </div>
                    <div class="col-6">
                        <h5 class="mb-0 fw-bold text-success">2</h5>
                        <small class="text-secondary" style="font-size: 0.75rem;">Impugnadas</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Asesorías: Visible para Admin (1), Personero (2), Funcionario (3), Asesorias (12) -->
    <?php if(in_array($rol, [1, 2, 3, 12])): ?>
    <div class="col-md-6 col-lg-3">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-header bg-white border-bottom-0 pb-0">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark m-0">Asesorías</h6>
                    <span class="badge bg-success rounded-pill">Hoy: 5</span>
                </div>
            </div>
            <div class="card-body text-center">
                <div class="display-6 fw-bold my-2 text-success">120</div>
                <small class="d-block text-muted mb-2">Atendidas este mes</small>
                <div class="progress mb-2" style="height: 6px;">
                    <div class="progress-bar bg-success" style="width: 100%;"></div>
                </div>
                <hr>
                <div class="d-grid gap-2">
                    <button class="btn btn-sm btn-outline-success">Ver Agenda</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Derechos de Petición y Disciplinarios: Solo Admin (1), Personero (2), Funcionario (3) -->
    <?php if(in_array($rol, [1, 2, 3])): ?>
    <div class="col-md-6 col-lg-3">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-header bg-white border-bottom-0 pb-0">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark m-0">Der. Petición</h6>
                    <span class="badge bg-info text-dark rounded-pill">En trámite: 8</span>
                </div>
            </div>
            <div class="card-body text-center">
                <div class="display-6 fw-bold my-2 text-info">92%</div>
                <div class="progress mb-2" style="height: 6px;">
                    <div class="progress-bar bg-info" style="width: 92%;"></div>
                </div>
                <small class="text-muted">Respondidos a tiempo</small>
                <hr>
                <div class="row text-center mt-3">
                    <div class="col-6 border-end">
                        <h5 class="mb-0 fw-bold">28</h5>
                        <small class="text-secondary" style="font-size: 0.75rem;">Recibidos</small>
                    </div>
                    <div class="col-6">
                        <h5 class="mb-0 fw-bold text-danger">1</h5>
                        <small class="text-secondary" style="font-size: 0.75rem;">Vencidos</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quejas -->
    <div class="col-md-6 col-lg-3">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-header bg-white border-bottom-0 pb-0">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark m-0">Disciplinarios</h6>
                    <span class="badge bg-secondary rounded-pill">Activos: 3</span>
                </div>
            </div>
            <div class="card-body text-center">
                <div class="py-3">
                    <i class="fas fa-gavel fa-3x text-secondary mb-2"></i>
                </div>
                <p class="small text-muted mb-3">Expedientes en etapa probatoria o investigación.</p>
                <hr>
                <a href="#" class="btn btn-sm btn-light w-100">Consultar Autos</a>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<div class="row">
    <!-- Lista de Alertas -->
    <div class="col-lg-12 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="m-0 fw-bold text-danger"><i class="fas fa-bell me-2"></i>Próximos Vencimientos (Reales)</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Radicado</th>
                                <th>Trámite</th>
                                <th>Vence</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Query para traer los próximos a vencer -->
                            <?php
                            $sql_alertas = "SELECT r.codigo_radicado, t.nombre, r.fecha_vencimiento 
                                            FROM radicados r 
                                            JOIN tipos_tramite t ON r.tipo_tramite_id = t.id 
                                            WHERE r.usuario_asignado_id = $id_usuario 
                                            AND r.estado NOT IN ('Cerrado')
                                            ORDER BY r.fecha_vencimiento ASC LIMIT 5";
                            $res_alertas = $conn->query($sql_alertas);
                            
                            if ($res_alertas->num_rows > 0):
                                while($alerta = $res_alertas->fetch_assoc()):
                                    $dias = (new DateTime())->diff(new DateTime($alerta['fecha_vencimiento']))->format("%r%a");
                                    $class = $dias < 0 ? 'bg-danger' : ($dias <= 2 ? 'bg-warning text-dark' : 'bg-success');
                            ?>
                            <tr>
                                <td class="fw-bold"><?php echo $alerta['codigo_radicado']; ?></td>
                                <td><?php echo $alerta['nombre']; ?></td>
                                <td><?php echo $alerta['fecha_vencimiento']; ?></td>
                                <td><span class="badge <?php echo $class; ?>"><?php echo $dias; ?> días</span></td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr><td colspan="4" class="text-center text-muted p-3">¡Excelente! No tienes vencimientos próximos.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
