<?php
define('DB_SERVER', '31.170.166.158');
define('DB_USERNAME', 'u811517622_osFEN');
define('DB_PASSWORD', 'I>;|rDDKj!0b');
define('DB_NAME', 'u811517622_DVukR');

$conexion = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$conexion->query("SET time_zone = '-05:00'");

$conexion->set_charset("utf8mb4");
