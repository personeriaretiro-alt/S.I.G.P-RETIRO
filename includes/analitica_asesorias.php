<?php
// consulta_analitica_asesorias.php
// Este archivo se incluye en panel_asesorias.php

// --- KPIS ---
// 1. Total Histórico
$kpi_total_a = $conn->query("SELECT count(*) as total FROM asesorias")->fetch_assoc()['total'];

// 2. Asesorías del Mes
$kpi_mes_a = $conn->query("SELECT count(*) as total FROM asesorias WHERE MONTH(fecha_registro) = MONTH(CURRENT_DATE()) AND YEAR(fecha_registro) = YEAR(CURRENT_DATE())")->fetch_assoc()['total'];

// 3. Usuarios Atendidos (Distintos)
$kpi_users_a = $conn->query("SELECT count(DISTINCT ciudadano_id) as total FROM asesorias")->fetch_assoc()['total'];

// 4. Zona Rural (Enfoque poblacional)
$kpi_rural_a = $conn->query("SELECT count(a.id) as total FROM asesorias a JOIN ciudadanos c ON a.ciudadano_id = c.id WHERE c.zona_residencia = 'Rural'")->fetch_assoc()['total'];


// --- DATOS PARA GRÁFICOS ---
// 1. Conteo por Estado
$sql_estado = "SELECT estado, count(*) as cantidad FROM asesorias GROUP BY estado";
$res_estado = $conn->query($sql_estado);
$labels_estado = []; // Inicializar etiquetas
$values_estado = []; // Inicializar valores

while($row = $res_estado->fetch_assoc()) {
    $labels_estado[] = $row['estado'];
    $values_estado[] = $row['cantidad'];
}

// 2. Conteo por Género (Uniendo con ciudadanos)
$sql_genero = "SELECT c.genero, count(a.id) as cantidad 
               FROM asesorias a 
               JOIN ciudadanos c ON a.ciudadano_id = c.id 
               GROUP BY c.genero";
$res_genero = $conn->query($sql_genero);
$labels_g = []; 
$data_g = [];
while($r = $res_genero->fetch_assoc()) { 
    $labels_g[] = $r['genero'] ?? 'No Registrado'; 
    $data_g[] = $r['cantidad']; 
}
?>

<div class="row mb-5">
    <div class="col-md-3">
        <div class="card card-dashboard h-100 border-start border-4 border-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase mb-2 text-muted small fw-bold">Total Asesorías</h6>
                        <div class="h2 mb-0 fw-bold text-primary"><?= $kpi_total_a ?></div>
                    </div>
                    <div class="bg-primary bg-opacity-10 p-3 rounded-circle text-primary">
                        <i class="fas fa-history fa-2x"></i>
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
                        <h6 class="text-uppercase mb-2 text-muted small fw-bold">Gestión del Mes</h6>
                        <div class="h2 mb-0 fw-bold text-success"><?= $kpi_mes_a ?></div>
                    </div>
                    <div class="bg-success bg-opacity-10 p-3 rounded-circle text-success">
                        <i class="fas fa-calendar-check fa-2x"></i>
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
                        <h6 class="text-uppercase mb-2 text-muted small fw-bold">Ciudadanos Únicos</h6>
                        <div class="h2 mb-0 fw-bold text-info"><?= $kpi_users_a ?></div>
                    </div>
                    <div class="bg-info bg-opacity-10 p-3 rounded-circle text-info">
                        <i class="fas fa-users fa-2x"></i>
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
                        <h6 class="text-uppercase mb-2 text-muted small fw-bold">Población Rural</h6>
                        <div class="h2 mb-0 fw-bold text-warning"><?= $kpi_rural_a ?></div>
                    </div>
                    <div class="bg-warning bg-opacity-10 p-3 rounded-circle text-warning">
                        <i class="fas fa-tractor fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <!-- Card Analítica Estado -->
    <div class="col-md-6 mb-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-white fw-bold border-bottom-0"><i class="fas fa-chart-pie text-primary"></i> Estado de Trámites</div>
            <div class="card-body">
                <div style="height: 250px;">
                    <canvas id="chartEstado"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Card Demografía -->
    <div class="col-md-6 mb-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-white fw-bold border-bottom-0"><i class="fas fa-venus-mars text-info"></i> Distribución por Género</div>
            <div class="card-body">
                 <div style="height: 250px;">
                    <canvas id="chartGenero"></canvas>
                 </div>
            </div>
        </div>
    </div>
</div>

<!-- Cargar Chart.js desde CDN si no existe localmente -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Configuración Gráfico Estado
const ctxEstado = document.getElementById('chartEstado').getContext('2d');
new Chart(ctxEstado, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($labels_estado) ?>,
        datasets: [{
            data: <?= json_encode($values_estado) ?>,
            backgroundColor: ['#3366CC', '#488704', '#ffc107', '#dc3545', '#6c757d'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'right' }
        }
    }
});

// Configuración Gráfico Género
const ctxGenero = document.getElementById('chartGenero').getContext('2d');
new Chart(ctxGenero, {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels_g) ?>,
        datasets: [{
            label: 'Cantidad',
            data: <?= json_encode($data_g) ?>,
            backgroundColor: '#007bff'
        }]
    },
    options: { 
        responsive: true,
        maintainAspectRatio: false,
        scales: { y: { beginAtZero: true } } 
    }
});

// Configuración Gráfico Zona
const ctxZona = document.getElementById('chartZona').getContext('2d');
new Chart(ctxZona, {
    type: 'pie',
    data: {
        labels: <?= json_encode($labels_z) ?>,
        datasets: [{
            data: <?= json_encode($data_z) ?>,
            backgroundColor: ['#6610f2', '#e83e8c', '#fd7e14', '#20c997']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});
</script>