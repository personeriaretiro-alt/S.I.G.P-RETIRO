<?php
// Ocultar errores en pantalla y loguearlos a archivo para evitar romper JSON
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '../php_error_log');
// No incluir conexion.php aun, asegurarse de que no imprima nada
ob_start(); // Iniciar buffer de salida por si acaso
include '../conexion.php';
// Limpiar cualquier cosa que conexion.php haya impreso (ej: BOM, espacios)
ob_clean(); 
session_start();


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Asignación de Responsable
    $usuario_actual = $_SESSION['usuario_id'];
    $usuario_asignado = !empty($_POST['usuario_asignado']) ? $_POST['usuario_asignado'] : $usuario_actual;
    
    // 1. Datos del Ciudadano
    $doc = isset($_POST['documento']) ? trim($_POST['documento']) : '';
    // Eliminar puntos o espacios internos que puedan causar duplicados (ej: 1.234.567 vs 1234567)
    $doc = str_replace(['.', ' '], '', $doc); 
    
    $tipo_doc = isset($_POST['tipo_documento']) ? $_POST['tipo_documento'] : 'CC';
    
    // Normalización de datos para consistencia (Mayúsculas / Minúsculas)
    $nombres = isset($_POST['nombres']) ? mb_strtoupper(trim($_POST['nombres']), 'UTF-8') : 'DESCONOCIDO';
    $apellidos = isset($_POST['apellidos']) ? mb_strtoupper(trim($_POST['apellidos']), 'UTF-8') : '';
    $direccion = isset($_POST['direccion']) ? mb_strtoupper(trim($_POST['direccion']), 'UTF-8') : '';
    $email = isset($_POST['email']) ? strtolower(trim($_POST['email'])) : '';
    $telefono = isset($_POST['telefono']) ? trim($_POST['telefono']) : '';
    $edad = !empty($_POST['edad']) ? (int)$_POST['edad'] : NULL;
    
    // Nuevos Campos Demográficos
    $genero = isset($_POST['genero']) ? $_POST['genero'] : '';
    $grupo_poblacional = isset($_POST['grupo_poblacional']) ? $_POST['grupo_poblacional'] : '';
    $zona_residencia = isset($_POST['zona_residencia']) ? $_POST['zona_residencia'] : '';
    $barrio_vereda = isset($_POST['barrio_vereda']) ? mb_strtoupper(trim($_POST['barrio_vereda']), 'UTF-8') : '';
    $habeas_data = isset($_POST['habeas_data_aceptado']) ? (int)$_POST['habeas_data_aceptado'] : 0;

    $cid_id = null;

    // Verificar si ya existe el ciudadano
    $check = $conn->query("SELECT id FROM ciudadanos WHERE numero_documento = '$doc'");
    $modo_operacion = $_POST['modo_operacion'] ?? 'nuevo';

    // Autodetección de seguridad para modo Actuación Previa
    if (!empty($_POST['tipo_actuacion_previa']) || !empty($_POST['parent_id'])) {
        $modo_operacion = 'actuacion_previa';
    }

        if ($check->num_rows > 0) {
        $row = $check->fetch_assoc();
        $cid_id = $row['id'];
        
        // Si el modo es 'actuacion_previa', NO actualizamos datos ni preguntamos, usamos el ID existente
        // Puesto que el formulario oculta los datos del ciudadano, enviarlos vacíos borraría la info si actualizamos.
        if ($modo_operacion == 'actuacion_previa') {
             // Silenciosamente usar $cid_id y continuar
        }
        else if ((isset($_POST['actualizar_ciudadano']) && $_POST['actualizar_ciudadano'] == '1') || $modo_operacion == 'solo_actualizacion') {
            // ACTUALIZAR DATOS
            $stmt_upd = $conn->prepare("UPDATE ciudadanos SET nombres=?, apellidos=?, telefono=?, email=?, direccion=?, edad=?, genero=?, grupo_poblacional=?, zona_residencia=?, barrio_vereda=?, habeas_data_aceptado=? WHERE id=?");
            $stmt_upd->bind_param("sssssissisii", $nombres, $apellidos, $telefono, $email, $direccion, $edad, $genero, $grupo_poblacional, $zona_residencia, $barrio_vereda, $habeas_data, $cid_id);
            $stmt_upd->execute();
            $stmt_upd->close();
            
            // Si es solo actualización, terminamos aquí
            if ($modo_operacion == 'solo_actualizacion') {
                 header('Content-Type: application/json');
                 echo json_encode(['status' => 'success', 'message' => "Datos del ciudadano actualizados correctamente."]);
                 exit;
            }

        } else {
            // Si NO se envió confirmación de actualización, devolvemos alerta para preguntar (Solo en modo registro nuevo)
             if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {

                 header('Content-Type: application/json');
                 echo json_encode([
                     'status' => 'confirm_update', 
                     'message' => 'El ciudadano con documento ' . $doc . ' ya existe en la base de datos.',
                     'confirm_text' => '¿Desea actualizar los datos del ciudadano con la información ingresada y continuar con el trámite?'
                 ]);
                 exit;
            } else {
                 header('Content-Type: application/json');
                 echo json_encode(['status' => 'error', 'message' => "Error: El ciudadano ya existe. Use la opción de actualizar."]);
                 exit;
            }
        }
        
    } else {
        // Crear Ciudadano
        $stmt_c = $conn->prepare("INSERT INTO ciudadanos (tipo_documento, numero_documento, nombres, apellidos, telefono, email, direccion, edad, genero, grupo_poblacional, zona_residencia, barrio_vereda, habeas_data_aceptado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_c->bind_param("sssssssissssi", $tipo_doc, $doc, $nombres, $apellidos, $telefono, $email, $direccion, $edad, $genero, $grupo_poblacional, $zona_residencia, $barrio_vereda, $habeas_data);
        $stmt_c->execute();
        $cid_id = $conn->insert_id;
        $stmt_c->close();

        // Si por alguna razón estamos en 'solo_actualizacion' pero el ciudadano no existía, lo creamos y paramos.
        // ("Actualizar Datos" de alguien que en realidad no existía, se convierte en registro de persona sin caso)
        if ($modo_operacion == 'solo_actualizacion') {
             header('Content-Type: application/json');
             echo json_encode(['status' => 'success', 'message' => "Ciudadano registrado correctamente en la base de datos."]);
             exit;
        }
    }

    // 2. Datos del Trámite
    // -- CAMBIO: Se regresa a uso de Radicado Automático --
    // $codigo = trim($_POST['radicado']); 
    // if (empty($codigo)) { die("Error: El radicado es obligatorio."); }

    $nombre_tramite = isset($_POST['tipo_tramite']) ? $_POST['tipo_tramite'] : '';
    
    // Validar si estamos en un contexto de Actuación Previa de forma más robusta
    // Si hay un parent_id o un tipo_actuacion_previa, asumimos que es actuación
    $es_actuacion_previa = ($modo_operacion == 'actuacion_previa') || !empty($_POST['parent_id']) || !empty($_POST['tipo_actuacion_previa']);

    // Si viene de actuacion_previa, el select de tipo_tramite está oculto y vacío. Asignamos 'Tutelas' por defecto.
    if (empty($nombre_tramite) && $es_actuacion_previa) {
        // Asignaremos 'Tutelas' genéricamente para obtener SLA y demás configuraciones
        $nombre_tramite = 'Tutelas'; 
        // Forzamos modo_operacion para consistencia futura (aunque ya no se use arriba)
        $modo_operacion = 'actuacion_previa';
    }

    if (empty($nombre_tramite)) {
         if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
             header('Content-Type: application/json');
             echo json_encode(['status' => 'error', 'message' => "Error: El tipo de trámite es obligatorio."]);
             exit;
         } else {
             die("Error: El tipo de trámite es obligatorio.");
         }
    }

    $area_atencion = isset($_POST['area_atencion']) ? $_POST['area_atencion'] : ''; // Nuevo campo Area
    
    // Concatenar proceso interno si existe (Ej: AMPARO DE POBREZA - LABORAL)
    if (!empty($_POST['procesos_internos'])) {
        $area_atencion .= " - " . $_POST['procesos_internos'];
    }
    
    // Concatenar solicitud de ayuda humanitaria si existe
    if (!empty($_POST['solicita_ayuda_humanitaria'])) {
        $area_atencion .= " - Solicitud Ayuda: " . $_POST['solicita_ayuda_humanitaria'];
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
    if ($nombre_tramite == 'Tutelas' || $nombre_tramite == 'Derecho de Peticion') {
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
        
        // --- LOGICA ESPECIFICA PARA TUTELAS Y DERECHOS DE PETICIÓN ---
        // Ambos van a la tabla de tutelas para gestión centralizada
        if ($nombre_tramite == 'Tutelas' || $nombre_tramite == 'Derecho de Peticion' || $nombre_tramite == 'Incidentes' || !empty($_POST['parent_id'])) {
            
            // Verificación AUTOMÁTICA de columnas para evitar fallos si no se corrió el setup
            // Esto asegura que el sistema sea resiliente a faltas de actualización manual de DB
            $db_check_cols = ['fecha_radicacion_actuacion', 'decision_actuacion', 'estado_actuacion', 'tipo_atencion', 'parent_id'];
            foreach($db_check_cols as $chk_col) {
                $check_q = $conn->query("SHOW COLUMNS FROM tutelas LIKE '$chk_col'");
                if ($check_q && $check_q->num_rows == 0) {
                     $def = "VARCHAR(255) DEFAULT NULL";
                     if($chk_col == 'fecha_radicacion_actuacion') $def = "DATE DEFAULT NULL";
                     if($chk_col == 'parent_id') $def = "INT(11) DEFAULT NULL";
                     $conn->query("ALTER TABLE tutelas ADD COLUMN $chk_col $def");
                }
            }

            $radicado_juzgado_vacio = ""; 
            
            // USUARIO QUE REGISTRA: Es el mismo usuario_actual que inició sesión
            $usuario_que_registra = $_SESSION['usuario_id'];
            
            // TIPO TRAMITE: 'Tutela' o 'Derecho de Peticion', normalizar para BD
            $tipo_db = ($nombre_tramite == 'Derecho de Peticion') ? 'Derecho de Petición' : 'Tutela';
            if($nombre_tramite == 'Incidentes') $tipo_db = 'Incidente';

            // Datos de Actuación Previa (Sub-casos)
            $parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : null;
            $fecha_rad_actuacion = !empty($_POST['fecha_radicacion_actuacion']) ? $_POST['fecha_radicacion_actuacion'] : null;
            $decision_actuacion = !empty($_POST['decision_actuacion']) ? $_POST['decision_actuacion'] : null;
            $estado_actuacion = !empty($_POST['estado_actuacion']) ? $_POST['estado_actuacion'] : null;
            
            // Si viene de modo actuación previa, el tipo de atención se toma del selector
            if (!empty($_POST['tipo_actuacion_previa'])) {
                $area_atencion = $_POST['tipo_actuacion_previa']; // Usamos columna derecho_amparado/tipo_atencion para guardar qué es
                $tipo_db = 'Actuación Previa'; 
            }

            // El estado principal del registro
            $estado_principal = $estado_actuacion ? $estado_actuacion : 'Admitida';

            // intentaremos hacer un INSERT simple
            // Asegurarse de haber corrido el script setup_add_actuaciones.php
            
            // Si es actuación, usamos parent_id y los nuevos campos. Si no, van null.
            $tipo_atencion_para_bd = !empty($_POST['tipo_actuacion_previa']) ? $_POST['tipo_actuacion_previa'] : $area_atencion;
            
            // Si es actuación, el tipo_tramite (o derecho amparado) debería ser "ACTUACIÓN - TIPO" para que sea facil de ver
            // O dejamos derecho_amparado como null? Mejor dejamos el Tipo de Actuacion como derecho_amparado tambien por si usan ese campo en reportes
            if(!empty($_POST['tipo_actuacion_previa'])) {
                $area_atencion = 'ACTUACIÓN: ' . $_POST['tipo_actuacion_previa'];
            }

            $query = "INSERT INTO tutelas (ciudadano_id, radicado_tutela, codigo_radicado_interno, created_at, estado, usuario_responsable_id, usuario_registra_id, derecho_amparado, tipo_tramite, parent_id, fecha_radicacion_actuacion, decision_actuacion, estado_actuacion, tipo_atencion) VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt_tute = $conn->prepare($query);
            if ($stmt_tute) {
                // Tipos: i s s s i i s s i s s s s
                // Validar parent_id para bind_param (debe ser null o entero)
                $parent_id_val = ($parent_id === "" || $parent_id === null) ? null : (int)$parent_id;
                
                // Tipos corregidos: tipo_db es s, parent_id es i
                // isssiississss (13 caracteres)
                $stmt_tute->bind_param("isssiississss", 
                    $cid_id, 
                    $radicado_juzgado_vacio, 
                    $codigo, 
                    $estado_principal, 
                    $usuario_asignado, 
                    $usuario_que_registra, 
                    $area_atencion,     // derecho_amparado
                    $tipo_db, 
                    $parent_id_val, 
                    $fecha_rad_actuacion, 
                    $decision_actuacion, 
                    $estado_actuacion,
                    $tipo_atencion_para_bd // tipo_atencion real en la columna correcta
                );
                
                if (!$stmt_tute->execute()) {
                     if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                         header('Content-Type: application/json');
                         echo json_encode(['status' => 'error', 'message' => "Error al guardar tutela/actuación: " . $stmt_tute->error]);
                         exit;
                     } else {
                         die("Error al guardar tutela/actuación: " . $stmt_tute->error);
                     }
                }
                $stmt_tute->close();
            } else {
                 if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                     header('Content-Type: application/json');
                     // Error en prepare() suele ser sintaxis SQL o columna faltante
                     echo json_encode(['status' => 'error', 'message' => "Error preparando consulta SQL: " . $conn->error]);
                     exit;
                 } else {
                     die("Error preparando consulta SQL: " . $conn->error); 
                 }
            }
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
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
             header('Content-Type: application/json');
             echo json_encode(['status' => 'error', 'message' => "Error al guardar radicado: " . $stmt_r->error]);
             exit;
        } else {
             echo "Error: " . $stmt_r->error;
        }
    }
}
?>