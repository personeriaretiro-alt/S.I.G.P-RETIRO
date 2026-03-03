<?php
include '../conexion.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    // Validar ID
    if ($id <= 0) {
        die("Error: ID inválido.");
    }

    // Identificar tipo de actualización (Normal vs. Actuación Previa)
    // El frontend envía 'tipo_tramite_actual' = 'NORMAL' o 'ACTUACION'
    $tipo_tramite = isset($_POST['tipo_tramite_actual']) ? $_POST['tipo_tramite_actual'] : 'NORMAL';
    $observaciones = isset($_POST['observaciones']) ? trim($_POST['observaciones']) : '';

    if ($tipo_tramite === 'ACTUACION') {
        // --- ACTUALIZACIÓN DE SUB-CASO (Trámite Previo) ---
        
        $fecha_rad = !empty($_POST['fecha_radicacion_actuacion']) ? $_POST['fecha_radicacion_actuacion'] : NULL;
        $decision = isset($_POST['decision_actuacion']) ? $_POST['decision_actuacion'] : ''; // SI / NO
        $estado = isset($_POST['estado_actuacion']) ? $_POST['estado_actuacion'] : 'PENDIENTE'; // ESTUDIO / PENDIENTE / RADICADO
        $tipo_atenc = isset($_POST['tipo_atencion_actuacion']) ? $_POST['tipo_atencion_actuacion'] : ''; // RECURSOS / IMPUGNACION

        // Actualizamos las columnas especificas y también el estado general
        // El 'estado' general será igual al 'estado_actuacion' para que se vea en listas globales
        // La columna 'tipo_atencion' guarda el subtipo (RECURSOS, etc.)
        
        $sql = "UPDATE tutelas SET 
                fecha_radicacion_actuacion = ?, 
                decision_actuacion = ?, 
                estado_actuacion = ?, 
                tipo_atencion = ?,
                estado = ?,
                observaciones = ?
                WHERE id = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            // s s s s s s i
            $stmt->bind_param("ssssssi", 
                $fecha_rad, 
                $decision, 
                $estado, 
                $tipo_atenc,
                $estado, // Reflejamos estado
                $observaciones, 
                $id
            );
            
            if ($stmt->execute()) {
                 header("Location: ../seguimiento_tutelas.php?msg=Actuación actualizada correctamente");
            } else {
                 echo "Error al actualizar actuación: " . $stmt->error;
            }
            $stmt->close();
        } else {
             echo "Error preparando consulta de actuación: " . $conn->error;
        }

    } else { 
        // --- ACTUALIZACIÓN DE TUTELA NORMAL ---
        
        // Obtener datos del formulario
        $radicado_tutela = isset($_POST['radicado_tutela']) ? trim($_POST['radicado_tutela']) : '';
        $derecho_amparado = isset($_POST['derecho_amparado']) ? trim($_POST['derecho_amparado']) : '';
        $juzgado = isset($_POST['juzgado']) ? trim($_POST['juzgado']) : '';
        $persona_vinculada = isset($_POST['persona_vinculada']) ? trim($_POST['persona_vinculada']) : '';
        
        $admitida = isset($_POST['admitida']) ? $_POST['admitida'] : 'Pendiente';
        $fecha_admision = !empty($_POST['fecha_admision']) ? $_POST['fecha_admision'] : NULL;
        $concedio_tutela = isset($_POST['concedio_tutela']) ? $_POST['concedio_tutela'] : '';
        
        // Nuevos campos
        $es_radicado = isset($_POST['es_radicado']) ? $_POST['es_radicado'] : 'NO';
        $pendiente_respuesta = isset($_POST['pendiente_respuesta']) ? $_POST['pendiente_respuesta'] : 'NO';
        
        $fecha_estimada_respuesta = NULL;
        if ($pendiente_respuesta == 'SI' && !empty($_POST['fecha_estimada_respuesta'])) {
            $fecha_estimada_respuesta = $_POST['fecha_estimada_respuesta'];
        }

        $recibe_respuesta_email = isset($_POST['recibe_respuesta_email']) ? $_POST['recibe_respuesta_email'] : 'NO';
        
        // Campos Desacato
        $incidente_desacato = isset($_POST['incidente_desacato']) ? $_POST['incidente_desacato'] : 'NO';
        $fecha_radicacion_desacato = !empty($_POST['fecha_radicacion_desacato']) ? $_POST['fecha_radicacion_desacato'] : NULL;
        $sancion_desacato = isset($_POST['sancion_desacato']) ? $_POST['sancion_desacato'] : 'NO';

        // Preparar UPDATE original
        $sql = "UPDATE tutelas SET 
                radicado_tutela = ?, 
                derecho_amparado = ?, 
                juzgado = ?, 
                persona_vinculada = ?, 
                admitida = ?, 
                fecha_admision = ?, 
                concedio_tutela = ?, 
                observaciones = ?,
                es_radicado = ?,
                pendiente_respuesta = ?,
                fecha_estimada_respuesta = ?,
                recibe_respuesta_email = ?,
                incidente_desacato = ?,
                fecha_radicacion_desacato = ?,
                sancion_desacato = ?
                WHERE id = ?";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sssssssssssssssi", 
                $radicado_tutela, $derecho_amparado, $juzgado, $persona_vinculada, 
                $admitida, $fecha_admision, $concedio_tutela, $observaciones,
                $es_radicado, $pendiente_respuesta, $fecha_estimada_respuesta, $recibe_respuesta_email,
                $incidente_desacato, $fecha_radicacion_desacato, $sancion_desacato,
                $id
            );

            if ($stmt->execute()) {
                header("Location: ../seguimiento_tutelas.php?msg=Tutela actualizada correctamente");
            } else {
                echo "Error al actualizar tutela: " . $stmt->error;
            }
            $stmt->close();
        } else {
             echo "Error preparando consulta de tutela: " . $conn->error;
        }
    }
    
    $conn->close();
}
?>