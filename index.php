<?php
include 'conexion.php';
include 'includes/auth.php'; // Protege la ruta
include 'includes/header.php'; // Navbar

// --- ESTADÍSTICAS GENERALES DE USUARIO ---
$id_usuario = $_SESSION['usuario_id'];

// 1. Total mis casos activos (Radicados Generales)
$sql_activos = "SELECT count(*) as total FROM radicados WHERE usuario_asignado_id = $id_usuario AND estado != 'Cerrado'";
$res_activos = $conn->query($sql_activos);
$activos = $res_activos ? $res_activos->fetch_assoc()['total'] : 0;

// 2. Mis Asesorías Pendientes / En Trámite
$sql_asesorias = "SELECT count(*) as total FROM tutelas WHERE usuario_responsable_id = $id_usuario AND tipo_tramite = 'Asesoria' AND estado IN ('Pendiente', 'En Trámite')";
$res_asesorias = $conn->query($sql_asesorias);
$mis_asesorias = $res_asesorias ? $res_asesorias->fetch_assoc()['total'] : 0;

// 3. Pendientes de hoy (Nuevos radicados)
$sql_nuevos = "SELECT count(*) as total FROM radicados WHERE usuario_asignado_id = $id_usuario AND fecha_inicio >= CURDATE()";
$res_nuevos = $conn->query($sql_nuevos);
$nuevos_hoy = $res_nuevos ? $res_nuevos->fetch_assoc()['total'] : 0;

// --- ANALÍTICA GLOBAL DE TRÁMITES LEGALES Y ASESORÍAS ---
// Solo visible para roles pertinentes
$kpi_tramites_total = 0;
$kpi_tutelas = 0;
$kpi_asesorias = 0;
$kpi_incidentes = 0;

if(in_array($rol, [1, 2, 3, 11])) {
    // 1. Total de Trámites Judiciales y Asesorías
    $q = $conn->query("SELECT count(*) as total FROM tutelas");
    $kpi_tramites_total = $q ? $q->fetch_assoc()['total'] : 0;

    // 2. Total Tutelas
    $q = $conn->query("SELECT count(*) as total FROM tutelas WHERE tipo_tramite = 'Tutela'");
    $kpi_tutelas = $q ? $q->fetch_assoc()['total'] : 0;

    // 3. Total Asesorías
    $q = $conn->query("SELECT count(*) as total FROM tutelas WHERE tipo_tramite = 'Asesoria'");
    $kpi_asesorias = $q ? $q->fetch_assoc()['total'] : 0;

    // 4. Incidentes / Desacatos Activos
    $q = $conn->query("SELECT count(*) as total FROM tutelas WHERE incidente_desacato IS NOT NULL AND incidente_desacato NOT IN ('', 'NO')");
    $kpi_incidentes = $q ? $q->fetch_assoc()['total'] : 0;
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
        <div class="card card-dashboard h-100 border-start border-4 border-info shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase mb-2 text-muted small fw-bold">Mis Asesorías Pendientes</h6>
                        <div class="h2 mb-0 fw-bold text-info"><?php echo $mis_asesorias; ?></div>
                    </div>
                    <div class="bg-info bg-opacity-10 p-3 rounded-circle text-info">
                        <i class="fas fa-chalkboard-teacher fa-2x"></i>
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
    <h4 class="mb-3 fw-bold text-secondary border-bottom pb-2"><i class="fas fa-chart-bar me-2"></i>Analítica de Gestión Legal y Asesorías (Global)</h4>
    <div class="row g-3">
        <div class="col-md-3">
            <div class="card h-100 border-0 shadow-sm" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <div class="card-body text-center">
                    <h6 class="text-uppercase mb-2 opacity-75 small fw-bold">Total Gestión Histórica</h6>
                    <div class="display-4 fw-bold mb-0"><?= $kpi_tramites_total ?></div>
                    <small class="opacity-75">Trámites Legales Unificados</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card h-100 border-0 shadow-sm" style="background: linear-gradient(135deg, #89f7fe 0%, #66a6ff 100%); color: #004e92;">
                <div class="card-body text-center">
                    <h6 class="text-uppercase mb-2 opacity-75 small fw-bold">Total Asesorías</h6>
                    <div class="display-4 fw-bold mb-0"><?= $kpi_asesorias ?></div>
                    <small class="opacity-75">Registro global de asesorías</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card h-100 border-0 shadow-sm bg-white">
                <div class="card-body text-center border-bottom border-success border-4 rounded">
                    <h6 class="text-uppercase mb-2 text-success small fw-bold">Total Tutelas</h6>
                    <div class="display-4 fw-bold mb-0 text-success"><?= $kpi_tutelas ?></div>
                    <small class="text-muted">Procesos de tutela en curso/fallados</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card h-100 border-0 shadow-sm bg-white">
                <div class="card-body text-center border-bottom border-warning border-4 rounded">
                    <h6 class="text-uppercase mb-2 text-warning small fw-bold">Incidentes / Desacatos</h6>
                    <div class="display-4 fw-bold mb-0 text-warning"><?= $kpi_incidentes ?></div>
                    <small class="text-muted">Procesos con desacato activo</small>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- LISTADO DE ALERTAS / PENDIENTES (OCULTADO COMO SE SOLICITÓ PARA UNA FASE POSTERIOR) -->
<!-- 
<div class="row">
    ... (Tabla de Próximos Vencimientos se omite por ahora) ...
</div> 
-->

<?php include 'includes/footer.php'; ?>
