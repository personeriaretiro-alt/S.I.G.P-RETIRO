<?php
include '../conexion.php';
// Solo permitir acceso autenticado, pero es una API JSON
session_start();
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    die(json_encode(['error' => 'No autorizado']));
}

$response = [
    'tipos' => ['labels' => [], 'values' => []],
    'anios' => ['labels' => [], 'values' => []],
    'barrios' => ['labels' => [], 'values' => []]
];

// Query 1: Tipos de Servicio / Trámites
$sql1 = "SELECT t.nombre, COUNT(r.id) as c 
         FROM tipos_tramite t 
         LEFT JOIN radicados r ON t.id = r.tipo_tramite_id 
         GROUP BY t.id
         ORDER BY c DESC LIMIT 10"; // Top 10 servicios

$res1 = $conn->query($sql1);
if ($res1) {
    while($row = $res1->fetch_assoc()) {
        $response['tipos']['labels'][] = $row['nombre'];
        $response['tipos']['values'][] = (int)$row['c'];
    }
}

// Query 2: Atenciones por Año
$sql2 = "SELECT YEAR(fecha_inicio) as anio, COUNT(*) as c FROM radicados GROUP BY YEAR(fecha_inicio) ORDER BY anio ASC";
$res2 = $conn->query($sql2);
if ($res2) {
    while($row = $res2->fetch_assoc()) {
        $val = empty($row['anio']) ? 'Sin fecha' : $row['anio'];
        $response['anios']['labels'][] = $val;
        $response['anios']['values'][] = (int)$row['c'];
    }
}

// Query 3: Top Barrios (Nuevo dato demográfico)
// Join con ciudadanos para sacar el barrio
$sql3 = "SELECT c.barrio_vereda, COUNT(*) as c 
         FROM radicados r
         JOIN ciudadanos c ON r.ciudadano_id = c.id
         GROUP BY c.barrio_vereda 
         ORDER BY c DESC LIMIT 10";
         
$res3 = $conn->query($sql3);
if ($res3) {
    while($row = $res3->fetch_assoc()) {
        $val = empty($row['barrio_vereda']) ? 'No registrado' : $row['barrio_vereda'];
        $response['barrios']['labels'][] = $val;
        $response['barrios']['values'][] = (int)$row['c'];
    }
}

// Query 4: Top Ciudadanos Recurrentes (¿Quién viene más?)
// Agregamos GROUP_CONCAT para listar los tipos de trámite, mostrando cuántas veces cada uno
$sql4 = "SELECT c.nombres, c.apellidos, c.numero_documento, COUNT(r.id) as c,
         GROUP_CONCAT(DISTINCT t.nombre SEPARATOR ', ') as tramites
         FROM ciudadanos c 
         JOIN radicados r ON c.id = r.ciudadano_id 
         JOIN tipos_tramite t ON r.tipo_tramite_id = t.id
         GROUP BY c.id 
         ORDER BY c DESC LIMIT 10";

$res4 = $conn->query($sql4);
if ($res4) {
    while($row = $res4->fetch_assoc()) {
        $response['top_ciudadanos'][] = [
            'nombres' => $row['nombres'] . ' ' . $row['apellidos'],
            'documento' => $row['numero_documento'],
            'total' => $row['c'],
            'tramites' => $row['tramites'] // Lista separada por comas
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?>