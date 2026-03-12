<?php
// Configuración Cpanel a reemplazar por credenciales del hosting en el archivo final
$host = "localhost";
$user = "tu_usuario_cpanel";
$pass = "tu_password";
$dbname = "nombre_bd_produccion";

// 1. Exportar la BD local
$command_export = "mysqldump -u root personeria_retiro > " . __DIR__ . "/backup_local.sql";
exec($command_export);

echo "Base de datos exportada a: backup_local.sql\n\n";

echo "INSTRUCCIONES PARA PRODUCCION (CPANEL):\n";
echo "1. En tu Cpanel entra a 'Bases de Datos MySQL' o 'MySQL Databases'.\n";
echo "2. Crea una nueva base de datos y un usuario, y vinculalos otorgando Todos los Privilegios.\n";
echo "3. Entra a phpMyAdmin en tu Cpanel.\n";
echo "4. Selecciona tu nueva base de datos a la izquierda.\n";
echo "5. Ve a la pestaña 'Importar' arriba.\n";
echo "6. Selecciona el archivo 'backup_local.sql' que se acaba de crear en la carpeta de este proyecto y dale a Continuar.\n";
echo "\nFINALMENTE: No olvides subir todos tus archivos PHP al public_html o directorio principal y cambiar 'conexion.php' para poner tus nuevas credenciales del hosting.\n";
?>