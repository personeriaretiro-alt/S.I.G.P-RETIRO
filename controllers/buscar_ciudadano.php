<?php
include '../conexion.php';

header('Content-Type: application/json');

if (isset($_GET['cedula'])) {
    $cedula = $conn->real_escape_string($_GET['cedula']);
    
    $sql = "SELECT * FROM ciudadanos WHERE numero_documento = '$cedula' LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        echo json_encode(['found' => true, 'data' => $result->fetch_assoc()]);
    } else {
        echo json_encode(['found' => false]);
    }
} else {
    echo json_encode(['error' => 'No Document provided']);
}
?>