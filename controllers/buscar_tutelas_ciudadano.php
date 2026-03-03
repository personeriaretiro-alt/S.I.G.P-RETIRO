<?php
include '../conexion.php';

if (!isset($_GET['id']) && !isset($_GET['ciudadano_id'])) {
    die(json_encode([]));
}

$cid = isset($_GET['id']) ? (int)$_GET['id'] : (int)$_GET['ciudadano_id'];

// Obtener Tutelas y D. Petición del ciudadano
$whereParent = "AND (parent_id IS NULL OR parent_id = 0)";
if (isset($_GET['type']) && $_GET['type'] == 'all') {
    $whereParent = ""; // Sin filtro de padre para ver todo el historial
} else if (isset($_GET['type']) && $_GET['type'] == 'sub') {
     $whereParent = "AND parent_id > 0"; // Solo Sub-tramites
}

$sql = "SELECT 
            id, 
            COALESCE(radicado_tutela, codigo_radicado_interno) as radicado, 
            COALESCE(fecha_radicacion_actuacion, DATE(created_at)) as fecha_radicacion, 
            COALESCE(estado_actuacion, estado) as estado, 
            tipo_tramite,
            COALESCE(tipo_atencion, derecho_amparado, 'Sin Detalle') as tipo_atencion,
            parent_id
        FROM tutelas 
        WHERE ciudadano_id = $cid 
        $whereParent
        ORDER BY created_at DESC";

$res = $conn->query($sql);

$tutelas = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $tutelas[] = $row;
    }
}
echo json_encode($tutelas);
?>
