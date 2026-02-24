<?php
include '../conexion.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Asignación de Responsable
    $usuario_actual = $_SESSION['usuario_id'];
    $usuario_asignado = !empty($_POST['usuario_asignado']) ? $_POST['usuario_asignado'] : $usuario_actual;
    
    // 1. Datos del Ciudadano
    $doc = trim($_POST['documento']);
    // Eliminar puntos o espacios internos que puedan causar duplicados (ej: 1.234.567 vs 1234567)
    $doc = str_replace(['.', ' '], '', $doc); 
    
    $tipo_doc = $_POST['tipo_documento'];
    
    // Normalización de datos para consistencia (Mayúsculas / Minúsculas)
    $nombres = mb_strtoupper(trim($_POST['nombres']), 'UTF-8');
    $apellidos = mb_strtoupper(trim($_POST['apellidos']), 'UTF-8');
    $direccion = mb_strtoupper(trim($_POST['direccion']), 'UTF-8');
    $email = strtolower(trim($_POST['email']));
    $telefono = trim($_POST['telefono']);
    $edad = !empty($_POST['edad']) ? (int)$_POST['edad'] : NULL;
    
    // Nuevos Campos Demográficos
    $genero = isset($_POST['genero']) ? $_POST['genero'] : '';
    $grupo_poblacional = isset($_POST['grupo_poblacional']) ? $_POST['grupo_poblacional'] : '';
    $zona_residencia = isset($_POST['zona_residencia']) ? $_POST['zona_residencia'] : '';
    $barrio_vereda = isset($_POST['barrio_vereda']) ? mb_strtoupper(trim($_POST['barrio_vereda']), 'UTF-8') : '';

    $cid_id = null;

    // Verificar si ya existe el ciudadano
    $check = $conn->query("SELECT id FROM ciudadanos WHERE numero_documento = '$doc'");
    if ($check->num_rows > 0) {
        $row = $check->fetch_assoc();
        $cid_id = $row['id'];
        
        // Si se recibe el flag 'actualizar_ciudadano', procedemos a actualizar
        if (isset($_POST['actualizar_ciudadano']) && $_POST['actualizar_ciudadano'] == '1') {
            // ACTUALIZAR DATOS
            $stmt_upd = $conn->prepare("UPDATE ciudadanos SET nombres=?, apellidos=?, telefono=?, email=?, direccion=?, edad=?, genero=?, grupo_poblacional=?, zona_residencia=?, barrio_vereda=? WHERE id=?");
            $stmt_upd->bind_param("sssssissisi", $nombres, $apellidos, $telefono, $email, $direccion, $edad, $genero, $grupo_poblacional, $zona_residencia, $barrio_vereda, $cid_id);
            $stmt_upd->execute();
            $stmt_upd->close();
        } else {
            // Si NO se envió confirmación de actualización, devolvemos alerta para preguntar
             if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                 header('Content-Type: application/json');
                 echo json_encode([
                     'status' => 'confirm_update', 
                     'message' => 'El ciudadano con documento ' . $doc . ' ya existe en la base de datos.',
                     'confirm_text' => '¿Desea actualizar los datos del ciudadano con la información ingresada y continuar con el trámite?'
                 ]);
                 exit;
            } else {
                 die("Error: El ciudadano ya existe. Use la opción de actualizar.");
            }
        }
        
    } else {
        // Crear Ciudadano
        $stmt_c = $conn->prepare("INSERT INTO ciudadanos (tipo_documento, numero_documento, nombres, apellidos, telefono, email, direccion, edad, genero, grupo_poblacional, zona_residencia, barrio_vereda) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_c->bind_param("sssssssissss", $tipo_doc, $doc, $nombres, $apellidos, $telefono, $email, $direccion, $edad, $genero, $grupo_poblacional, $zona_residencia, $barrio_vereda);
        $stmt_c->execute();
        $cid_id = $conn->insert_id;
        $stmt_c->close();
    }

    // 2. Datos del Trámite
    // -- CAMBIO: Se regresa a uso de Radicado Automático --
    // $codigo = trim($_POST['radicado']); 
    // if (empty($codigo)) { die("Error: El radicado es obligatorio."); }

    $nombre_tramite = $_POST['tipo_tramite']; // Recibe el nombre del trámite (String)
    $area_atencion = isset($_POST['area_atencion']) ? $_POST['area_atencion'] : ''; // Nuevo campo Area
    
    // Concatenar proceso interno si existe (Ej: AMPARO DE POBREZA - LABORAL)
    if (!empty($_POST['procesos_internos'])) {
        $area_atencion .= " - " . $_POST['procesos_internos'];
    }
    
    $obs = $_POST['observacion'];
    
    // -- LOGICA NUEVA: Resolver ID de Trámite por Nombre y Asignar Responsable --
    
    // Buscar el ID del trámite en la BD basado en el nombre seleccionado
    $stmt_t = $conn->prepare("SELECT id, sla_horas FROM tipos_tramite WHERE nombre = ?");
    $stmt_t->bind_param("s", $nombre_tramite);
    $stmt_t->execute();
    $res_t = $stmt_t->get_result();

    if ($res_t->num_rows > 0) {
        $row_t = $res_t->fetch_assoc();
        $tipo_tramite_id = $row_t['id'];
        $sla = $row_t['sla_horas'];
    } else {
        // TRAMITE NUEVO: Si no existe, lo creamos dinámicamente
        $sla = 48; // SLA por defecto (48 horas)
        // Ajustes específicos de SLA para los nuevos tipos
        if ($nombre_tramite == 'Derecho de Peticion') $sla = 360; // 15 días
        if ($nombre_tramite == 'Tutelas') $sla = 48; 
        if ($nombre_tramite == 'Incidentes') $sla = 72;
        
        $stmt_new = $conn->prepare("INSERT INTO tipos_tramite (nombre, sla_horas) VALUES (?, ?)");
        $stmt_new->bind_param("si", $nombre_tramite, $sla);
        $stmt_new->execute();
        $tipo_tramite_id = $conn->insert_id;
    }
    $stmt_t->close();

    // LÓGICA DE ASIGNACIÓN INTELIGENTE
    // "Si seleccionan Tutelas, este debe de ir en los datos de la responsable de las tutelas"
    if ($nombre_tramite == 'Tutelas') {
        // Buscar usuario responsable de Tutelas (Por ejemplo, usuario con rol específico o nombre)
        // AQUI SE DEBE AJUSTAR EL CRITERIO DE BUSQUEDA SEGUN LA REALIDAD DE LA EMPRESA
        $sql_resp = "SELECT id FROM usuarios WHERE rol_id IN (SELECT id FROM roles WHERE nombre LIKE '%Juridica%' OR nombre LIKE '%Abogado%') LIMIT 1";
        $res_resp = $conn->query($sql_resp);
        if ($res_resp->num_rows > 0) {
            $usuario_asignado = $res_resp->fetch_assoc()['id'];
        }
        // Si no se encuentra un 'Juridica', se mantiene el usuario que registra o se podría definir un ID fijo
    }


    // Generar Código Radicado Único (Ej: PER-2023-X)
    $year = date('Y');
    $last_id_res = $conn->query("SELECT id FROM radicados ORDER BY id DESC LIMIT 1");
    $last_id = ($last_id_res->num_rows > 0) ? $last_id_res->fetch_assoc()['id'] + 1 : 1;
    $codigo = "PER-$year-" . str_pad($last_id, 4, "0", STR_PAD_LEFT);
    
    $fecha_inicio = new DateTime();
    $fecha_vence = clone $fecha_inicio;
    $fecha_vence->modify("+{$sla} hours");
    $fecha_vence_str = $fecha_vence->format('Y-m-d H:i:s');

    // Insertar Radicado usando el ID resuelto
    // IMPORTANTE: Asegurate de que la tabla 'radicados' tenga la columna 'area_atencion'
    // Ejecuta en tu DB: ALTER TABLE radicados ADD COLUMN area_atencion VARCHAR(100) AFTER tipo_tramite_id;
    $stmt_r = $conn->prepare("INSERT INTO radicados (codigo_radicado, ciudadano_id, tipo_tramite_id, area_atencion, usuario_asignado_id, fecha_vencimiento, observacion_inicial) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt_r->bind_param("siisiss", $codigo, $cid_id, $tipo_tramite_id, $area_atencion, $usuario_asignado, $fecha_vence_str, $obs);
    
    if ($stmt_r->execute()) {
        $id_radicado = $conn->insert_id;
        
        // --- LOGICA ESPECIFICA PARA TUTELAS ---
        // Si el trámite es una Tutela, debemos crear el registro correspondiente en la tabla 'tutelas'
        // para que aparezca en el módulo de Seguimiento de Tutelas.
        if ($nombre_tramite == 'Tutelas') {
            // Verificamos si la tabla tutelas tiene la columna radicado_id, si no, se deberá agregar.
            // Asumimos una estructura estándar basada en el módulo de seguimiento:
            // ciudadano_id, radicado_id, fecha_registro, estado, usuario_responsable
            
            // Intentamos insertar. Si falla por falta de columnas, el usuario deberá ajustar la DB.
            // Usamos IGNORE o ON DUPLICATE KEY UPDATE para prevenir errores si ya existe lógica previa
            // Intentaremos hacer un INSERT simple asumiendo una tabla 'tutelas' básica
            // para que aparezca en el panel de seguimiento.
            // MODIFICACIÓN SOLICITADA: radicado_tutela en blanco al inicio.
            // Se usará el codigo interno solo para referencia si es necesario, pero el campo 'radicado_tutela' que es el del juzgado va NULL/Vacio.
            $radicado_juzgado_vacio = ""; 
            $stmt_tute = $conn->prepare("INSERT INTO tutelas (ciudadano_id, radicado_tutela, created_at, estado, usuario_responsable_id) VALUES (?, ?, NOW(), 'Admitida', ?)");
            $stmt_tute->bind_param("isi", $cid_id, $radicado_juzgado_vacio, $usuario_asignado);
            $stmt_tute->execute();
            $stmt_tute->close();
        }
        
        // --- LOGICA ESPECIFICA PARA ASESORIAS ---
        // Si el trámite es Asesorias, creamos el registro en su propia tabla
        // para gestión centralizada en su módulo específico
        if ($nombre_tramite == 'Asesorias') {
             $stmt_ases = $conn->prepare("INSERT INTO asesorias (ciudadano_id, radicado_id, codigo_radicado, fecha_registro, estado, usuario_responsable_id) VALUES (?, ?, ?, NOW(), 'Pendiente', ?)");
             if ($stmt_ases) {
                 $stmt_ases->bind_param("iisi", $cid_id, $id_radicado, $codigo, $usuario_asignado);
                 $stmt_ases->execute();
                 $stmt_ases->close();
             }
        }
        // ---------------------------------------
        
        // Agregar traza
        
        // Agregar traza
        $conn->query("INSERT INTO trazabilidad (radicado_id, usuario_id, accion, comentario) VALUES ($id_radicado, $usuario_asignado, 'Creación', 'Radicado creado exitosamente.')");
        
        // --- LOGICA FIRMA DIGITAL ---
        if (isset($_POST['firma_digital']) && !empty($_POST['firma_digital'])) {
            $data_uri = $_POST['firma_digital'];
            $encoded_image = explode(",", $data_uri)[1];
            $decoded_image = base64_decode($encoded_image);
            
            // Crear directorio si no existe
            $dir_firmas = '../assets/firmas/';
            if (!file_exists($dir_firmas)) {
                mkdir($dir_firmas, 0777, true);
            }
            
            // Nombre de archivo único
            $nombre_archivo = 'firma_ciudadano_' . $cid_id . '_' . time() . '.png';
            $ruta_completa = $dir_firmas . $nombre_archivo;
            
            if (file_put_contents($ruta_completa, $decoded_image)) {
                // Actualizar DB. Si falla porque no existe columna, intentamos crearla.
                $ruta_relativa = 'assets/firmas/' . $nombre_archivo;
                
                // Intento 1: Actualizar asumiendo que existe columna
                $sql_firma = "UPDATE ciudadanos SET firma_digital = '$ruta_relativa' WHERE id = $cid_id";
                if (!$conn->query($sql_firma)) {
                    // Si falla, intentamos agregar la columna y reintentar
                    $conn->query("ALTER TABLE ciudadanos ADD firma_digital VARCHAR(255) DEFAULT NULL");
                    $conn->query($sql_firma);
                }
            }
        }
        // ------------------------------

        // Redireccionar con éxito
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
             header('Content-Type: application/json');
             echo json_encode(['status' => 'success', 'message' => "Radicado $codigo creado exitosamente."]);
             exit;
        }
        header("Location: ../index.php?msg=Radicado $codigo creado exitosamente");
    } else {
        echo "Error: " . $stmt_r->error;
    }
}
?>