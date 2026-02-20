<?php
include '../conexion.php';
// Headers para forzar descarga como Excel (CSV)
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename=Reporte_Personeria_'.date('Y-m-d').'.xls');

// Nota: .xls es para abrir fácil, pero el contenido será una tabla HTML
// Esto es un truco común en PHP antiguo pero efectivo.

echo "
<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
<table border='1'>
    <tr style='background-color: #3366CC; color: white;'>
        <th>Radicado</th>
        <th>Tipo Trámite</th>
        <th>Estado</th>
        <th>Fecha Vencimiento</th>
        <th>Funcionario Asignado</th>
        <th>Ciudadano</th>
        <th>Documento</th>
    </tr>
";

$sql = "SELECT r.codigo_radicado, t.nombre as tipo, r.estado, r.fecha_vencimiento, u.nombre_completo as funcionario,
        c.nombres, c.apellidos, c.numero_documento
        FROM radicados r
        JOIN tipos_tramite t ON r.tipo_tramite_id = t.id
        JOIN usuarios u ON r.usuario_asignado_id = u.id
        JOIN ciudadanos c ON r.ciudadano_id = c.id
        ORDER BY r.fecha_vencimiento ASC";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>".$row['codigo_radicado']."</td>";
        echo "<td>".utf8_decode($row['tipo'])."</td>";
        echo "<td>".$row['estado']."</td>";
        echo "<td>".$row['fecha_vencimiento']."</td>";
        echo "<td>".utf8_decode($row['funcionario'])."</td>";
        echo "<td>".utf8_decode($row['nombres']." ".$row['apellidos'])."</td>";
        echo "<td>".$row['numero_documento']."</td>";
        echo "</tr>";
    }
}

echo "</table>";
?>