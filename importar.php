<?php
include 'conexion.php'; // Asegúrate de que este archivo carga db.sql actualizado
include 'includes/auth.php'; // Verificar sesión
include 'includes/header.php'; // Header

/**
 * Función auxiliar para limpiar strings
 */
function clean($str) {
    global $conn;
    return $conn->real_escape_string(trim($str));
}

$errores = [];
$exitos = 0;

if (isset($_POST["import"])) {
    
    $fileName = $_FILES["file"]["tmp_name"];
    
    if ($_FILES["file"]["size"] > 0) {
        
        $file = fopen($fileName, "r");
        
        // Saltar la primera línea si tiene encabezados (opcional, ajusta según el CSV)
        $headers = fgetcsv($file, 10000, ","); 
        
        while (($column = fgetcsv($file, 10000, ",")) !== FALSE) {
            
            // Asignación de Indices según el orden que me diste:
            // 0: Marca temporal, 1: Nombres y Apellidos, 2: Tipo Identificación, 3: Numero Documento
            // 4: Celular, 5: Correo, 6: Genero, 7: Grupo Poblacional, 8: 40-60 (Rango Edad), 9: Zona
            // 10: Barrio, 11: Medio Atencion, 12: Tipo Atencion, 13: Area Atencion, 14: Atendido Por

            // 1. Datos del Ciudadano
            $nombre_completo = clean($column[1]);
            // Intentar separar nombres y apellidos (básicamente)
            $partes = explode(' ', $nombre_completo);
            $apellido = array_pop($partes); 
            $nombre = implode(' ', $partes); 
            if(empty($nombre)) { $nombre = $apellido; $apellido = "Sin Apellido"; } // Fallback

            $tipo_doc = clean($column[2]);
            
            // LIMPIEZA DE DOCUMENTO (Solo Números)
            $num_doc_sucio = clean($column[3]);
            // Eliminar todo lo que NO sea número
            $num_doc = preg_replace('/[^0-9]/', '', $num_doc_sucio);
            
            // Si quedó vacío por ser basura, generar uno temporal único
            if(empty($num_doc)) {
                $num_doc = "TEMP-" . uniqid();
            }

            $celular = clean($column[4]);
            $email = clean($column[5]);
            $genero = clean($column[6]);
            $grupo_pob = clean($column[7]);
            $rango_edad = clean($column[8]); // Campo "40 - 60"
            $zona = clean($column[9]);
            $barrio = clean($column[10]);

            // Insertar o Actualizar Ciudadano
            // Primero verificar si existe por documento
            $sql_check_c = "SELECT id FROM ciudadanos WHERE numero_documento = '$num_doc'";
            $res_c = $conn->query($sql_check_c);
            
            if ($res_c->num_rows > 0) {
                $ciudadano_id = $res_c->fetch_assoc()['id'];
            } else {
                $sql_insert_c = "INSERT INTO ciudadanos (nombres, apellidos, tipo_documento, numero_documento, telefono, email, genero, grupo_poblacional, rango_edad, zona_residencia, barrio_vereda) 
                VALUES ('$nombre', '$apellido', '$tipo_doc', '$num_doc', '$celular', '$email', '$genero', '$grupo_pob', '$rango_edad', '$zona', '$barrio')";
                
                if ($conn->query($sql_insert_c)) {
                    $ciudadano_id = $conn->insert_id;
                } else {
                    $errores[] = "Error creando ciudadano $num_doc: " . $conn->error;
                    continue; // Saltar al siguiente si falla el ciudadano
                }
            }

            // 2. Datos del Trámite (Radicado)
            $fecha_raw = clean($column[0]); 
            // Convertir fecha de Excel/Google (d/m/Y H:i:s a Y-m-d H:i:s)
            $fecha_obj = DateTime::createFromFormat('d/m/Y H:i:s', $fecha_raw);
            $fecha_inicio = ($fecha_obj) ? $fecha_obj->format('Y-m-d H:i:s') : date('Y-m-d H:i:s');
            
            $medio = clean($column[11]);
            $tipo_tramite_txt = clean($column[12]);
            $area = clean($column[13]);
            $atendido_por = clean($column[14]);

            // Buscar ID de Usuario "Atendido Por" o crear uno dummy
            $sql_u = "SELECT id FROM usuarios WHERE nombre_completo LIKE '%$atendido_por%' LIMIT 1";
            $res_u = $conn->query($sql_u);
            if ($res_u->num_rows > 0) {
                $usuario_id = $res_u->fetch_assoc()['id'];
            } else {
                // Crear usuario temporal si no existe
                $email_dummy = strtolower(str_replace(' ', '.', $atendido_por)) . "@personeria.gov.co";
                $conn->query("INSERT INTO usuarios (nombre_completo, email, password_hash, rol_id) VALUES ('$atendido_por', '$email_dummy', '123456', 3)");
                $usuario_id = $conn->insert_id;
            }

            // Buscar ID de Tipo Trámite o crear
            $sql_t = "SELECT id FROM tipos_tramite WHERE nombre LIKE '%$tipo_tramite_txt%' LIMIT 1";
            $res_t = $conn->query($sql_t);
            if ($res_t->num_rows > 0) {
                $tramite_id = $res_t->fetch_assoc()['id'];
            } else {
                // Crear tipo tramite y asignarle 24h por defecto
                $conn->query("INSERT INTO tipos_tramite (nombre, sla_horas) VALUES ('$tipo_tramite_txt', 24)");
                $tramite_id = $conn->insert_id;
            }

            // Generar codigo radicado retroactivo único
            // Usamos uniqid para evitar colisión si el ciudadano tiene múltiples casos
            $codigo = "HIST-" . strtoupper(substr(uniqid(), -8)); 

            // Insertar Radicado
            $sql_rad = "INSERT INTO radicados (codigo_radicado, ciudadano_id, tipo_tramite_id, usuario_asignado_id, fecha_inicio, estado, medio_atencion, observacion_inicial) 
                        VALUES ('$codigo', $ciudadano_id, $tramite_id, $usuario_id, '$fecha_inicio', 'Cerrado', '$medio', 'Importado Histórico - Área: $area')";
            
            if ($conn->query($sql_rad)) {
                $exitos++;
            } else {
               $errores[] = "Error radicando para $num_doc: " . $conn->error;
            }
        }
        fclose($file);
    }
}
?>

<div class="container mt-5">
    <div class="card shadow">
        <div class="card-header bg-success text-white">
            <h4><i class="fas fa-file-csv"></i> Importar Datos Históricos</h4>
        </div>
        <div class="card-body">
            
            <?php if($exitos > 0): ?>
                <div class="alert alert-success">Se han importado <strong><?php echo $exitos; ?></strong> registros correctamente.</div>
            <?php endif; ?>
            
            <?php if(!empty($errores)): ?>
                <div class="alert alert-danger" style="max-height: 200px; overflow-y: auto;">
                    <strong>Errores encontrados:</strong><br>
                    <?php foreach($errores as $e) { echo $e . "<br>"; } ?>
                </div>
            <?php endif; ?>

            <p>Seleccione el archivo <strong>.CSV (Delimitado por comas)</strong> generado desde Excel o Google Sheets.</p>
            <p class="small text-muted">Asegúrese que las columnas sigan estricatamente este orden:<br>
            Marca temporal | Nombres y Apellidos | Tipo Doc | Num Doc | Celular | Email | Género | Grupo Pob | Rango Edad | Zona | Barrio | Medio | Tipo Atención | Área | Atendido Por</p>
            
            <form class="form-horizontal" action="" method="post" name="upload_excel" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Archivo CSV</label>
                    <input type="file" name="file" id="file" class="form-control" accept=".csv" required>
                    <small class="text-muted">Si el archivo viene de Excel, asegúrese de guardarlo como "CSV (delimitado por comas)". Si viene de Google Sheets, descárguelo como CSV.</small>
                </div>
                <button type="submit" id="submit" name="import" class="btn btn-primary">Importar Registros</button>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
