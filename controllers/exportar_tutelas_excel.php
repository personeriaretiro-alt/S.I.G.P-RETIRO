<?php
include '../conexion.php';
session_start();

// Control de Acceso: Solo Roles autorizados
if (!isset($_SESSION['rol_id']) || !in_array($_SESSION['rol_id'], [1, 2, 3, 11])) {
    die("Acceso Denegado");
}

// Configuración de encabezados para descarga de Excel (XLS)
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=reporte_tutelas_" . date("Y-m-d_H-i") . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// Consulta completa
$sql = "SELECT 
            t.id, 
            t.codigo_radicado_interno, 
            t.tipo_tramite,
            t.radicado_tutela, 
            t.created_at as fecha_registro, 
            t.derecho_amparado, 
            t.juzgado, 
            t.persona_vinculada, 
            
            t.admitida, 
            t.fecha_admision, 
            t.concedio_tutela as tipo_fallo, 
            t.incidente_desacato,
            t.fecha_radicacion_desacato,
            t.sancion_desacato,
            
            t.es_radicado,
            t.pendiente_respuesta,
            t.fecha_estimada_respuesta,
            t.recibe_respuesta_email,
            t.observaciones,

            c.tipo_documento, 
            c.numero_documento, 
            CONCAT(c.nombres, ' ', c.apellidos) as nombre_ciudadano, 
            c.telefono, 
            c.email, 
            c.direccion, 
            c.genero, 
            c.grupo_poblacional, 
            c.zona_residencia, 
            c.barrio_vereda,

            u_reg.nombre_completo as registrado_por,
            u_resp.nombre_completo as responsable
        FROM tutelas t 
        JOIN ciudadanos c ON t.ciudadano_id = c.id 
        LEFT JOIN usuarios u_reg ON t.usuario_registra_id = u_reg.id
        LEFT JOIN usuarios u_resp ON t.usuario_responsable_id = u_resp.id
        ORDER BY t.created_at DESC";

$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #000; padding: 5px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
    </style>
</head>
<body>
    <table>
        <thead>
            <tr>
                <th colspan="29" style="background-color: #0d6efd; color: white; text-align: center; font-size: 16px;">
                    REPORTE DETALLADO DE TUTELAS Y DERECHOS DE PETICIÓN
                </th>
            </tr>
            <tr>
                <!-- Identificación del Caso -->
                <th style="background-color: #cfe2ff;">ID</th>
                <th style="background-color: #cfe2ff;">Radicado Interno</th>
                <th style="background-color: #cfe2ff;">Tipo Trámite</th>
                <th style="background-color: #cfe2ff;">Radicado Juzgado</th>
                <th style="background-color: #cfe2ff;">Fecha Registro</th>
                
                <!-- Datos del Ciudadano -->
                <th style="background-color: #e2e3e5;">Tipo Doc.</th>
                <th style="background-color: #e2e3e5;">Documento</th>
                <th style="background-color: #e2e3e5;">Ciudadano</th>
                <th style="background-color: #e2e3e5;">Teléfono</th>
                <th style="background-color: #e2e3e5;">Email</th>
                <th style="background-color: #e2e3e5;">Dirección</th>
                <th style="background-color: #e2e3e5;">Género</th>
                <th style="background-color: #e2e3e5;">Grupo Pob.</th>
                <th style="background-color: #e2e3e5;">Zona</th>
                <th style="background-color: #e2e3e5;">Barrio/Vereda</th>

                <!-- Detalles de la Tutela -->
                <th style="background-color: #fff3cd;">Derecho Amparado</th>
                <th style="background-color: #fff3cd;">Juzgado</th>
                <th style="background-color: #fff3cd;">Accionado/Vinculado</th>
                <th style="background-color: #fff3cd;">Admitida</th>
                <th style="background-color: #fff3cd;">Fecha Admisión</th>
                <th style="background-color: #fff3cd;">Fallo</th>
                
                <!-- Desacato -->
                <th style="background-color: #ffcccc;">Incidente</th>
                <th style="background-color: #ffcccc;">F. Radicación Desacato</th>
                <th style="background-color: #ffcccc;">Sanción</th>
                
                <!-- Estado Trámite (Nuevos campos) -->
                <th style="background-color: #d1e7dd;">Es Radicado</th>
                <th style="background-color: #d1e7dd;">Pendiente Resp.</th>
                <th style="background-color: #d1e7dd;">Fecha Est. Resp.</th>
                <th style="background-color: #d1e7dd;">Resp. Email</th>
                <th style="background-color: #d1e7dd;">Observaciones</th>

                <!-- Responsables -->
                <th style="background-color: #f8d7da;">Registrado Por</th>
                <th style="background-color: #f8d7da;">Responsable</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row['id'] . "</td>";
                    echo "<td>" . ($row['codigo_radicado_interno'] ?? '') . "</td>";
                    echo "<td>" . ($row['tipo_tramite'] ?? 'Tutela') . "</td>";
                    echo "<td>" . ($row['radicado_tutela'] ?? '') . "</td>";
                    echo "<td>" . $row['fecha_registro'] . "</td>";
                    
                    echo "<td>" . $row['tipo_documento'] . "</td>";
                    echo "<td>" . $row['numero_documento'] . "</td>";
                    echo "<td>" . mb_convert_case($row['nombre_ciudadano'], MB_CASE_UPPER, "UTF-8") . "</td>";
                    echo "<td>" . $row['telefono'] . "</td>";
                    echo "<td>" . strtolower($row['email']) . "</td>";
                    echo "<td>" . $row['direccion'] . "</td>";
                    echo "<td>" . $row['genero'] . "</td>";
                    echo "<td>" . $row['grupo_poblacional'] . "</td>";
                    echo "<td>" . $row['zona_residencia'] . "</td>";
                    echo "<td>" . $row['barrio_vereda'] . "</td>";

                    echo "<td>" . $row['derecho_amparado'] . "</td>";
                    echo "<td>" . $row['juzgado'] . "</td>";
                    echo "<td>" . $row['persona_vinculada'] . "</td>";
                    echo "<td>" . ($row['admitida'] ?? 'Pendiente') . "</td>";
                    echo "<td>" . $row['fecha_admision'] . "</td>";
                    echo "<td>" . $row['tipo_fallo'] . "</td>";
                    
                    echo "<td>" . $row['incidente_desacato'] . "</td>";
                    echo "<td>" . $row['fecha_radicacion_desacato'] . "</td>";
                    echo "<td>" . $row['sancion_desacato'] . "</td>";
                    
                    echo "<td>" . ($row['es_radicado'] ?? 'NO') . "</td>";
                    echo "<td>" . ($row['pendiente_respuesta'] ?? 'NO') . "</td>";
                    echo "<td>" . $row['fecha_estimada_respuesta'] . "</td>";
                    echo "<td>" . ($row['recibe_respuesta_email'] ?? 'NO') . "</td>";
                    echo "<td>" . nl2br($row['observaciones']) . "</td>";

                    echo "<td>" . ($row['registrado_por'] ?? 'Sistema') . "</td>";
                    echo "<td>" . ($row['responsable'] ?? 'Sin asignar') . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='28'>No hay registros para mostrar</td></tr>";
            }
            ?>
        </tbody>
    </table>
</body>
</html>
