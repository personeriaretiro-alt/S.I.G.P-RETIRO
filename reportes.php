<?php
include 'conexion.php';
include 'includes/auth.php';

// Validar que sea admin (Rol 1) o Personero (Rol 2)
if (!isset($_SESSION['rol_id']) || !in_array($_SESSION['rol_id'], [1, 2])) {
    echo "<div class='alert alert-danger'>Acceso Denegado. Solo administradores y personero.</div>";
    include 'includes/footer.php';
    exit();
}

include 'includes/header.php';

// SQL para métricas básicas
$sql_tramites_total = "SELECT count(*) as total FROM radicados";
$res_tramites_total = $conn->query($sql_tramites_total)->fetch_assoc()['total'];

$sql_ciudadanos = "SELECT count(*) as total FROM ciudadanos";
$res_ciudadanos = $conn->query($sql_ciudadanos)->fetch_assoc()['total'];

// Año con más movimiento
$sql_anio = "SELECT YEAR(fecha_inicio) as anio, COUNT(*) as c FROM radicados GROUP BY anio ORDER BY c DESC LIMIT 1";
$res_anio = $conn->query($sql_anio)->fetch_assoc();
$anio_top = ($res_anio) ? $res_anio['anio'] : 'N/A';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Inteligencia de Datos Históricos</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-outline-primary ms-2" onclick="window.print()">
            <i class="fas fa-print"></i> PDF / Imprimir Informe
        </button>
    </div>
</div>

<!-- Resumen Ejecutivo -->
<div class="row text-center mb-4">
    <div class="col-md-4">
        <div class="card bg-primary text-white p-3">
            <h3><?php echo number_format($res_tramites_total); ?></h3>
            <small>Total Atenciones Históricas</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white p-3">
            <h3><?php echo number_format($res_ciudadanos); ?></h3>
            <small>Ciudadanos Únicos Atendidos</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-info text-white p-3">
            <h3><?php echo $anio_top; ?></h3>
            <small>Año con Mayor Demanda</small>
        </div>
    </div>
</div>

<div class="row">
    <!-- Gráfico 1: Evolución por Años -->
    <div class="col-md-12 mb-4">
        <div class="card shadow-sm">
            <div class="card-header font-weight-bold text-primary">Evolución de Atenciones por Año</div>
            <div class="card-body">
                <canvas id="chartAnios" height="100"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Gráfico 2: Trámites por Tipo -->
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header font-weight-bold text-dark">Top 10 Servicios Solicitados</div>
            <div class="card-body">
                <canvas id="chartTipos"></canvas>
            </div>
        </div>
    </div>

    <!-- Gráfico 3: Demografía (Barrios) -->
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header font-weight-bold text-success">Top Barrios / Veredas de Origen</div>
            <div class="card-body">
                <canvas id="chartBarrios"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Tabla Detallada -->
<div class="row">
    <div class="col-md-12">
        <div class="card shadow-sm">
            <div class="card-header">Top Funcionarios con Más Gestión</div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Funcionario</th>
                            <th>Total Asignados</th>
                            <th>Resueltos</th>
                            <th>Efectividad</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Query complejo para ranking
                        $sql_ranking = "SELECT u.nombre_completo, 
                                        COUNT(r.id) as total,
                                        SUM(CASE WHEN r.estado = 'Cerrado' THEN 1 ELSE 0 END) as cerrados
                                        FROM usuarios u
                                        LEFT JOIN radicados r ON u.id = r.usuario_asignado_id
                                        GROUP BY u.id
                                        ORDER BY cerrados DESC LIMIT 5";
                        $ranking = $conn->query($sql_ranking);
                        
                        while($r = $ranking->fetch_assoc()):
                            $efectividad = ($r['total'] > 0) ? round(($r['cerrados'] / $r['total']) * 100) : 0;
                        ?>
                        <tr>
                            <td><?php echo $r['nombre_completo']; ?></td>
                            <td><?php echo $r['total']; ?></td>
                            <td><?php echo $r['cerrados']; ?></td>
                            <td>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $efectividad; ?>%;">
                                        <?php echo $efectividad; ?>%
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

    <div class="col-md-12">
        <div class="card shadow-sm mb-4">
            <div class="card-header font-weight-bold bg-secondary text-white">Ciudadanos con Mayor Recurrencia (Top 10)</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Nombre Ciudadano</th>
                                <th>Documento</th>
                                <th>Tipos de Trámite Solicitados</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody id="listaCiudadanos">
                            <!-- Se llena con JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Cargar datos dinámicamente vía AJAX
document.addEventListener("DOMContentLoaded", function() {
    
    // Fetch Chart Data
    fetch('controllers/data_reportes.php')
    .then(response => response.json())
    .then(data => {

        // Llenar tabla ciudadanos
        if(data.top_ciudadanos) {
            let html = '';
            data.top_ciudadanos.forEach((c, index) => {
                html += `<tr>
                    <td>${index + 1}</td>
                    <td>${c.nombres}</td>
                    <td>${c.documento}</td>
                    <td><small class="text-muted">${c.tramites}</small></td>
                    <td><span class="badge bg-primary rounded-pill">${c.total}</span></td>
                </tr>`;
            });
            document.getElementById('listaCiudadanos').innerHTML = html;
        }
        
        // Chart: Tipos
        new Chart(document.getElementById("chartTipos"), {
            type: 'bar', // Horizontal bar? might be better
            data: {
                labels: data.tipos.labels,
                datasets: [{
                    label: "Trámites",
                    backgroundColor: "#4e73df",
                    data: data.tipos.values,
                }],
            },
            options: {
                indexAxis: 'y', // Hace las barras horizontales si es chart.js 3+
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Chart: Años (Histórico)
        new Chart(document.getElementById("chartAnios"), {
            type: 'line',
            data: {
                labels: data.anios.labels,
                datasets: [{
                    label: "Evolución por Año",
                    data: data.anios.values,
                    borderColor: '#1cc88a',
                    backgroundColor: 'rgba(28, 200, 138, 0.1)',
                    fill: true,
                    tension: 0.3
                }],
            },
            options: {
                responsive: true
            }
        });

         // Chart: Barrios
         new Chart(document.getElementById("chartBarrios"), {
            type: 'doughnut',
            data: {
                labels: data.barrios.labels,
                datasets: [{
                    data: data.barrios.values,
                    backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796', '#5a5c69', '#f8f9fc', '#2e59d9', '#17a673'],
                }],
            },
            options: {
                maintainAspectRatio: false,
                legend: { position: 'right' }
            }
        });

    });
});
</script>

<?php include 'includes/footer.php'; ?>
