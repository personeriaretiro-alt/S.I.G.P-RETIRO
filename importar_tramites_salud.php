<?php
include 'conexion.php';
include 'includes/auth.php';
include 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file']['tmp_name'];
    
    // Auto-detect delimiter
    $mimes = array('application/vnd.ms-excel','text/plain','text/csv','text/tsv');
    $handle = fopen($file, "r");
    
    // Leer primera línea para detectar separador
    $first_line = fgets($handle);
    $delimiter = strpos($first_line, ';') !== false ? ';' : ',';
    rewind($handle);
    
    // Asumimos que la primera fila es de encabezados
    fgetcsv($handle, 10000, $delimiter);
    
    $creados = 0;
    $tramites = 0;

    while (($data = fgetcsv($handle, 10000, $delimiter)) !== FALSE) {
        // Asegurarnos de que el array tenga al menos 11 elementos
        $data = array_pad($data, 11, '');
        
        $fecha = trim($data[0]);
        $nombres = trim($data[1]) === '' ? 'N/A' : trim($data[1]);
        $documento = trim($data[2]);
        $telefono = trim($data[3]) === '' ? 'N/A' : trim($data[3]);
        $servicio = trim($data[4]) === '' ? 'N/A' : trim($data[4]);
        $eps = trim($data[5]) === '' ? 'N/A' : trim($data[5]);
        $gestionado = trim($data[6]) === '' ? 'N/A' : trim($data[6]);
        
        $estado_tmp = isset($data[7]) ? trim($data[7]) : '';
        $estado = $estado_tmp === '' ? 'Pendiente' : $estado_tmp;
        
        $realizado_por = trim($data[8] ?? '') === '' ? 'N/A' : trim($data[8] ?? '');
        $observaciones = trim($data[9] ?? '') === '' ? 'N/A' : trim($data[9] ?? '');
        $r_super = trim($data[10] ?? '') === '' ? 'N/A' : trim($data[10] ?? '');

        if(empty($documento)) {
            // Si el documento está vacío en la columna 3, tal vez esté en otra (Error de orden de CSV)
            // Procedemos a usar un comodín o saltamos la fila
            if (!empty($nombres)) {
                 $documento = "SN-" . rand(1000, 999999);
            } else {
                 continue;
            }
        }

        // 1. Buscar si el ciudadano existe
        $stmt_check = $conn->prepare("SELECT id FROM ciudadanos WHERE numero_documento = ?");
        $stmt_check->bind_param("s", $documento);
        $stmt_check->execute();
        $res = $stmt_check->get_result();
        
        if ($res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $ciudadano_id = $row['id'];
        } else {
            // Crear el ciudadano si no existe
            // Separamos nombres de apellidos de forma basica
            $partes = explode(' ', $nombres, 2);
            $n_nombres = $partes[0];
            $n_apellidos = $partes[1] ?? '';

            $stmt_insert_c = $conn->prepare("INSERT INTO ciudadanos (numero_documento, nombres, apellidos, telefono) VALUES (?, ?, ?, ?)");
            $stmt_insert_c->bind_param("ssss", $documento, $n_nombres, $n_apellidos, $telefono);
            try {
                $stmt_insert_c->execute();
                $ciudadano_id = $conn->insert_id;
                $creados++;
            } catch (mysqli_sql_exception $e) {
                // Si aún así falla por duplicado por alguna razón de formato, buscamos el que falló
                $stmt_fallback = $conn->prepare("SELECT id FROM ciudadanos WHERE numero_documento = ?");
                $stmt_fallback->bind_param("s", $documento);
                $stmt_fallback->execute();
                $res_fb = $stmt_fallback->get_result();
                if ($res_fb->num_rows > 0) {
                    $row_fb = $res_fb->fetch_assoc();
                    $ciudadano_id = $row_fb['id'];
                } else {
                    continue; // Saltar si hay otro tipo de error
                }
            }
        }

        // 2. Insertar el Tramite de Salud
        $stmt_tramite = $conn->prepare("INSERT INTO tramites_salud (ciudadano_id, fecha_atencion, servicio_solicitado, eps, gestionado_a_traves_de, estado, realizado_por, observaciones, r_super_salud) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        // Convertir formato fecha si es necesario. (Asumir YYYY-MM-DD para MySQL)
        if(empty($fecha) || $fecha == '') $fecha = date('Y-m-d');
        
        $stmt_tramite->bind_param("issssssss", $ciudadano_id, $fecha, $servicio, $eps, $gestionado, $estado, $realizado_por, $observaciones, $r_super);
        
        if ($stmt_tramite->execute()) {
            $tramites++;
        }
    }
    
    fclose($handle);
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire('Importación Completa', 'Se crearon $creados ciudadanos y se importaron $tramites trámites de salud.', 'success')
            .then(() => { window.location.href = 'Tramites_salud.php'; });
        });
    </script>";
}
?>

<div class="container mt-5">
    <div class="card shadow">
        <div class="card-header bg-success text-white">
            <h4 class="mb-0"><i class="fas fa-file-excel"></i> Importar Trámites de Salud desde CSV</h4>
        </div>
        <div class="card-body">
            <p>Sube tu archivo con formato CSV separando las columnas por comas. El archivo debe tener exactamente este orden de columnas (sin importar el nombre del encabezado, pero sí la posición):</p>
            <ol>
                <li>Fecha (YYYY-MM-DD)</li>
                <li>Nombres y Apellidos</li>
                <li>Documento</li>
                <li>Teléfono</li>
                <li>Servicio Solicitado</li>
                <li>EPS</li>
                <li>Gestionado a Través de</li>
                <li>Estado</li>
                <li>Realizado Por</li>
                <li>Observaciones</li>
                <li>R. Super Salud</li>
            </ol>
            
            <form action="importar_tramites_salud.php" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Archivo CSV</label>
                    <input type="file" class="form-control" name="file" accept=".csv" required>
                </div>
                <button type="submit" class="btn btn-success"><i class="fas fa-upload"></i> Subir e Importar</button>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>