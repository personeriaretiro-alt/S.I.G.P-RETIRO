<?php
include 'conexion.php';
include 'includes/auth.php';
include 'includes/header.php';

function clean($str) {
    global $conn;
    return $conn->real_escape_string(trim($str));
}

// Función auxiliar para fechas de Excel d/m/Y
function parseDate($dateStr) {
    if (empty($dateStr)) return NULL;
    // Intentar formato d/m/Y
    $d = DateTime::createFromFormat('d/m/Y', $dateStr);
    // Si falla, probar Y-m-d o devolver original si es válida o NULL
    return ($d) ? $d->format('Y-m-d') : date('Y-m-d', strtotime(str_replace('/', '-', $dateStr)));
}

$msg = "";

if (isset($_POST["import"])) {
    $fileName = $_FILES["file"]["tmp_name"];
    if ($_FILES["file"]["size"] > 0) {
        $file = fopen($fileName, "r");
        
        // 1. Detección Inteligente del Separador (; o ,)
        $firstLine = fgets($file);
        $delimiter = (substr_count($firstLine, ";") > substr_count($firstLine, ",")) ? ";" : ",";
        rewind($file); // Volver al inicio del archivo

        // 2. Saltar encabezado
        fgetcsv($file, 10000, $delimiter); 
        
        $row = 0;
        $creados = 0;
        $actualizados = 0;
        
        while (($column = fgetcsv($file, 10000, $delimiter)) !== FALSE) {
            $row++;
            
            // Validación: Si la fila está vacía o tiene menos columnas de las necesarias, saltar
            if (count($column) < 5) continue; 

            // Mapeo de Columnas (Indices 0-25)
            // 0: Fecha Atencion, 1: Nombre, 2: TipoID, 3: NumID, 4: Contacto, 5: Email
            // 6: Genero, 7: Grp Pob, 8: Edad, 9: Zona, 10: Barrio
            
            // --- 1. PROCESAR CIUDADANO ---
            
            // Limpieza Basica
            $nombre_completo = isset($column[1]) ? clean($column[1]) : '';
            $tipo_doc = isset($column[2]) ? clean($column[2]) : '';
            $num_doc = isset($column[3]) ? preg_replace('/[^0-9]/', '', clean($column[3])) : ''; // Solo numeros
            
            if(empty($num_doc)) continue; // Sin documento no podemos hacer nada confiable

            $telefono = isset($column[4]) ? clean($column[4]) : '';
            // COLUMN 5 [Email] SE IGNORA EN CIUDADANO (Es específico de Tutela)
            $email = ""; // Dejar vacío para no ensuciar datos del ciudadano con correo de proceso
            
            $genero = isset($column[6]) ? clean($column[6]) : '';
            $grupo_pob = isset($column[7]) ? clean($column[7]) : '';
            $rango_edad = isset($column[8]) ? clean($column[8]) : '';
            $zona = isset($column[9]) ? clean($column[9]) : '';
            $barrio = isset($column[10]) ? clean($column[10]) : '';

            // Normalización Nombres
            $nombres = mb_strtoupper($nombre_completo, 'UTF-8');
            $apellidos = ""; // En este CSV viene todo junto, lo dejamos en Nombres o intentamos separar
            
            // Inteligencia simple para separar Apellido (asumiendo formato "APELLIDO APELLIDO NOMBRE")
            // O simplemente guardamos todo en "nombres" y dejamos apellidos vacio para no especular
            
            // Verificar si existe
            $check = $conn->query("SELECT id FROM ciudadanos WHERE numero_documento = '$num_doc'");
            if ($check->num_rows > 0) {
                // Ya existe, obtenemos ID
                $ciudadano_id = $check->fetch_assoc()['id'];
                // Podríamos actualizar datos si están vacíos, pero priorizamos velocidad
            } else {
                // Crear
                $stmt = $conn->prepare("INSERT INTO ciudadanos (nombres, apellidos, tipo_documento, numero_documento, telefono, email, genero, grupo_poblacional, rango_edad, zona_residencia, barrio_vereda) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssssssss", $nombres, $apellidos, $tipo_doc, $num_doc, $telefono, $email, $genero, $grupo_pob, $rango_edad, $zona, $barrio);
                if($stmt->execute()){
                    $ciudadano_id = $conn->insert_id;
                    $creados++;
                } else {
                    continue; // Fallo creacion
                }
            }

            // --- 2. PROCESAR TUTELA ---
            
            $fecha_atencion = parseDate(clean($column[0]));
            
            // EMAIL TUTELA (Col 5)
            $email_tutela = isset($column[5]) ? clean($column[5]) : '';
            
            $derecho = isset($column[11]) ? clean($column[11]) : '';
            $juzgado = isset($column[12]) ? clean($column[12]) : '';
            $radicado_tut = isset($column[13]) ? clean($column[13]) : '';
            $fecha_rad = parseDate(isset($column[14]) ? clean($column[14]) : '');
            $resp_tutela = isset($column[15]) ? clean($column[15]) : '';
            $admitida = isset($column[16]) ? clean($column[16]) : '';
            $fecha_adm = parseDate(isset($column[17]) ? clean($column[17]) : '');
            $persona_vinc = isset($column[18]) ? clean($column[18]) : '';
            $fecha_max = parseDate(isset($column[19]) ? clean($column[19]) : '');
            $dias = (int)(isset($column[20]) ? clean($column[20]) : 0);
            $fecha_sent = parseDate(isset($column[21]) ? clean($column[21]) : '');
            $concedio = isset($column[22]) ? clean($column[22]) : '';
            $cumplimiento = isset($column[23]) ? clean($column[23]) : '';
            $incidente = isset($column[24]) ? clean($column[24]) : '';
            $resp_incidente = isset($column[25]) ? clean($column[25]) : '';

            // Insertar Tutela
            $sql_tut = "INSERT INTO tutelas (
                ciudadano_id, fecha_atencion, derecho_amparado, juzgado, radicado_tutela, 
                fecha_radicado, responsable_tutela, admitida, fecha_admision, persona_vinculada, 
                fecha_maxima_sentencia, dias_termino, fecha_sentencia, concedio_tutela, cumplimiento, 
                incidente_desacato, responsable_incidente, email_tutela
            ) VALUES (
                '$ciudadano_id', '$fecha_atencion', '$derecho', '$juzgado', '$radicado_tut',
                '$fecha_rad', '$resp_tutela', '$admitida', '$fecha_adm', '$persona_vinc',
                '$fecha_max', '$dias', '$fecha_sent', '$concedio', '$cumplimiento',
                '$incidente', '$resp_incidente', '$email_tutela'
            )";

            if($conn->query($sql_tut)) {
                $actualizados++;
            }
        }
        $msg = "Importación Finalizada. Ciudadanos Nuevos: $creados, Tutelas Registradas: $actualizados.";
    }
}
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-header bg-success text-white">
                <h4><i class="fas fa-file-csv"></i> Importar Base de Datos de Tutelas</h4>
            </div>
            <div class="card-body">
                <?php if($msg): ?>
                    <div class="alert alert-info"><?= $msg ?></div>
                <?php endif; ?>
                
                <p>Sube el archivo Excel guardado como <b>CSV (delimitado por comas)</b>.</p>
                <form class="form-horizontal" action="" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label>Seleccionar Archivo CSV</label>
                        <input type="file" name="file" class="form-control" accept=".csv" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" name="import" class="btn btn-success btn-lg">Importar Datos y Crear Ciudadanos</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>