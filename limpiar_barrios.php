<?php
include 'conexion.php';

echo "Iniciando limpieza de datos de barrios y veredas...\n";

// 1. Convertir todo a mayúsculas y quitar espacios extra a los lados y múltiples espacios internos
$query1 = "UPDATE ciudadanos SET barrio_vereda = TRIM(UPPER(barrio_vereda)) WHERE barrio_vereda IS NOT NULL";
$conn->query($query1);

// Mapeo de reemplazos comunes para consistencia
$reemplazos = [
    // El Salado
    "SALADO" => "EL SALADO",
    "SALADOS" => "EL SALADO",
    "LOS SALADOS" => "EL SALADO",
    
    // Centro
    "CENTRO" => "EL CENTRO",
    
    // Bicentenario
    "BICENTENARIO" => "BICENTENARIO", // Ya estará en mayúscula, asegura coincidencia
    
    // El Pino
    "PINO" => "EL PINO",
    
    // El Rosario
    "ROSARIO" => "EL ROSARIO",

    // Nazareth
    "NAZARET" => "NAZARETH",
    
    // San Jose
    "SAN JOSE" => "SAN JOSE",
    "SAN JOSÉ" => "SAN JOSE",

    // El plan
    "EL PLAN" => "EL PLAN",
    "PLAN" => "EL PLAN",
    
    // Guanteros
    "GUANTEROS" => "GUANTEROS",
    
    // Lejos del Nido
    "LEJOS DEL NIDO" => "LEJOS DEL NIDO"
];

$count = 0;
foreach ($reemplazos as $inconsistente => $correcto) {
    // Usar LIKE '%...%' si se requiere, pero por seguridad mejor igualdad o LIKE específico
    $sql = "UPDATE ciudadanos SET barrio_vereda = '$correcto' WHERE barrio_vereda = '$inconsistente'";
    $conn->query($sql);
    $count += $conn->affected_rows;
}

// Remover puntos y comas sueltas, corregir prefijos innecesarios
$conn->query("UPDATE ciudadanos SET barrio_vereda = REPLACE(barrio_vereda, '.', '')");
$conn->query("UPDATE ciudadanos SET barrio_vereda = REPLACE(barrio_vereda, 'VEREDA ', '')");
$conn->query("UPDATE ciudadanos SET barrio_vereda = REPLACE(barrio_vereda, 'BARRIO ', '')");
// Al quitar VEREDA o BARRIO, pueden quedar espacios al inicio
$conn->query("UPDATE ciudadanos SET barrio_vereda = TRIM(barrio_vereda)");

echo "Limpieza completada. Filas afectadas por mapeo directo: $count. Todo unificado a mayúsculas.\n";
?>