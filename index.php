<?php
include 'conexion.php';
include 'includes/auth.php'; // Protege la ruta
include 'includes/header.php'; // Navbar

// --- ESTADÍSTICAS GENERALES DE USUARIO ---
$id_usuario = $_SESSION['usuario_id'];

// 1. Total mis casos activos
$sql_activos = "SELECT count(*) as total FROM radicados WHERE usuario_asignado_id = $id_usuario AND estado != 'Cerrado'";
$res_activos = $conn->query($sql_activos);
$activos = $res_activos ? $res_activos->fetch_assoc()['total'] : 0;

// 2. Total Casos Vencidos (Alerta Roja)
$sql_vencidos = "SELECT count(*) as total FROM radicados WHERE usuario_asignado_id = $id_usuario AND estado = 'Vencido'";
$res_vencidos = $conn->query($sql_vencidos);
$vencidos = $res_vencidos ? $res_vencidos->fetch_assoc()['total'] : 0;

// 3. Pendientes de hoy
$sql_nuevos = "SELECT count(*) as total FROM radicados WHERE usuario_asignado_id = $id_usuario AND fecha_inicio >= CURDATE()";
$res_nuevos = $conn->query($sql_nuevos);
$nuevos_hoy = $res_nuevos ? $res_nuevos->fetch_assoc()['total'] : 0;

// --- ANALÍTICA DE TUTELAS (GLOBAL) ---
// Solo visible para roles pertinentes
$kpi_tutelas_total = 0;
$kpi_tutelas_mes = 0;
$kpi_incidentes = 0;
$kpi_favor = 0;

if(in_array($rol, [1, 2, 3, 11])) {
    // 1. Total Tutelas
    $q = $conn->query("SELECT count(*) as total FROM tutelas");
    $kpi_tutelas_total = $q ? $q->fetch_assoc()['total'] : 0;

    // 2. Tutelas del Mes (Usando created_at)
    $q = $conn->query("SELECT count(*) as total FROM tutelas WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
    $kpi_tutelas_mes = $q ? $q->fetch_assoc()['total'] : 0;

    // 3. Incidentes Activos
    $q = $conn->query("SELECT count(*) as total FROM tutelas WHERE incidente_desacato IS NOT NULL AND incidente_desacato != '' AND incidente_desacato != 'No'");
    $kpi_incidentes = $q ? $q->fetch_assoc()['total'] : 0;

    // 4. Fallos a Favor
    $q = $conn->query("SELECT count(*) as total FROM tutelas WHERE concedio_tutela LIKE '%Favor%' OR concedio_tutela LIKE '%Concede%' OR concedio_tutela = 'SI'");
    $kpi_favor = $q ? $q->fetch_assoc()['total'] : 0;
}
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h2 class="fw-bold" style="color: var(--color-primary);">Tablero de Control</h2>
        <p class="text-muted">Bienvenido, <?php echo $_SESSION['usuario_nombre']; ?>. Aquí está el resumen de sus procesos e indicadores de gestión.</p>
    </div>
</div>

<!-- Resumen Personal (Mis Pendientes) -->
<div class="row mb-5">
    <div class="col-md-4">
        <div class="card card-dashboard h-100 border-start border-4 border-primary shadow-sm">
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
        <div class="card card-dashboard h-100 border-start border-4 border-danger shadow-sm">
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
        <div class="card card-dashboard h-100 border-start border-4 border-success shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase mb-2 text-muted small fw-bold">Nuevos Hoy</h6>
                        <div class="h2 mb-0 fw-bold text-success"><?php echo $nuevos_hoy; ?></div>
                    </div>
                    <div class="bg-success bg-opacity-10 p-3 rounded-circle text-success">
                        <i class="fas fa-calendar-check fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ANALITICA DE TÚTELAS (Visible según rol) -->
<?php if(in_array($rol, [1, 2, 3, 11])): ?>
<div class="mb-5">
    <h4 class="mb-3 fw-bold text-secondary border-bottom pb-2"><i class="fas fa-chart-bar me-2"></i>Analítica de Tutelas (Global)</h4>
    <div class="row g-3">
        <div class="col-md-3">
            <div class="card h-100 border-0 shadow-sm" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <div class="card-body text-center">
                    <h6 class="text-uppercase mb-2 opacity-75 small fw-bold">Total Histórico</h6>
                    <div class="display-4 fw-bold mb-0"><?= $kpi_tutelas_total ?></div>
                    <small class="opacity-75">Tutelas registradas</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card h-100 border-0 shadow-sm" style="background: linear-gradient(135deg, #89f7fe 0%, #66a6ff 100%); color: #004e92;">
                <div class="card-body text-center">
                    <h6 class="text-uppercase mb-2 opacity-75 small fw-bold">Radicadas Mes Actual</h6>
                    <div class="display-4 fw-bold mb-0"><?= $kpi_tutelas_mes ?></div>
                    <small class="opacity-75">Gestión reciente</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card h-100 border-0 shadow-sm bg-white">
                <div class="card-body text-center border-bottom border-success border-4 rounded">
                    <h6 class="text-uppercase mb-2 text-success small fw-bold">Fallos a Favor</h6>
                    <div class="display-4 fw-bold mb-0 text-success"><?= $kpi_favor ?></div>
                    <small class="text-muted">Éxito procesal</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card h-100 border-0 shadow-sm bg-white">
                <div class="card-body text-center border-bottom border-warning border-4 rounded">
                    <h6 class="text-uppercase mb-2 text-warning small fw-bold">Incidentes</h6>
                    <div class="display-4 fw-bold mb-0 text-warning"><?= $kpi_incidentes ?></div>
                    <small class="text-muted">Desacatos activos</small>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- LISTADO DE ALERTAS / PENDIENTES -->
<div class="row">
    <div class="col-lg-12 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-danger"><i class="fas fa-bell me-2"></i>Mis Próximos Vencimientos</h6>
                <a href="mis_casos.php" class="btn btn-sm btn-outline-primary">Ver Todo</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Radicado</th>
                                <th>Trámite</th>
                                <th>Vence El</th>
                                <th>Tiempo Restante</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql_alertas = "SELECT r.codigo_radicado, t.nombre, r.fecha_vencimiento 
                                            FROM radicados r 
                                            JOIN tipos_tramite t ON r.tipo_tramite_id = t.id 
                                            WHERE r.usuario_asignado_id = $id_usuario 
                                            AND r.estado NOT IN ('Cerrado', 'Finalizado')
                                            ORDER BY r.fecha_vencimiento ASC LIMIT 5";
                            $res_alertas = $conn->query($sql_alertas);
                            
                            if ($res_alertas && $res_alertas->num_rows > 0):
                                while($alerta = $res_alertas->fetch_assoc()):
                                    $fecha_vence = new DateTime($alerta['fecha_vencimiento']);
                                    $hoy = new DateTime();
                                    
                                    // Calculamos diff de hoy a vencimiento
                                    // %r pone signo negativo si ya pasó
                                    $interval = $hoy->diff($fecha_vence);
                                    $dias_int = (int)$interval->format('%r%a');
                                    
                                    // Lógica de semáforo
                                    $badgeClass = 'bg-success';
                                    $texto = $interval->format('%a días');
                                    
                                    if ($dias_int < 0) {
                                        $badgeClass = 'bg-danger';
                                        $texto = "Vencido hace " . abs($dias_int) . " días";
                                    } elseif ($dias_int == 0) {
                                        $badgeClass = 'bg-danger';
                                        $texto = "¡Vence Hoy!";
                                    } elseif ($dias_int <= 2) {
                                        $badgeClass = 'bg-warning text-dark';
                                    }
                            ?>
                            <tr>
                                <td class="fw-bold"><?php echo $alerta['codigo_radicado']; ?></td>
                                <td><?php echo $alerta['nombre']; ?></td>
                                <td><?php echo $fecha_vence->format('d/m/Y'); ?></td>
                                <td><span class="badge <?php echo $badgeClass; ?>"><?php echo $texto; ?></span></td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr><td colspan="4" class="text-center text-muted p-4">¡Excelente! No tienes vencimientos próximos pendientes.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
