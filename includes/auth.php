<?php
session_start();

// Validar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// Helper para verificar roles
function tienePermiso($rol_necesario) {
    // Lógica simple de roles: 1=Admin, 2=Personero, 3=Funcionario
    // Aquí podrías expandir la lógica
    return true; 
}
?>