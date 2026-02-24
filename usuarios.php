<?php
include 'conexion.php';
include 'includes/auth.php';
include 'includes/header.php';

// Validar que sea admin (Rol 1)
if ($_SESSION['rol_id'] != 1) {
    echo "<div class='alert alert-danger'>Acceso Denegado. Solo administradores.</div>";
    include 'includes/footer.php';
    exit();
}

// Procesar Formulario de Nuevo Usuario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['crear_usuario'])) {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $password = $_POST['password']; // En prod usar password_hash
    $rol = $_POST['rol'];
    
    $check = $conn->query("SELECT id FROM usuarios WHERE email = '$email'");
    if ($check->num_rows > 0) {
        $error = "El correo ya está registrado.";
    } else {
        $stmt = $conn->prepare("INSERT INTO usuarios (nombre_completo, email, password_hash, rol_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $nombre, $email, $password, $rol);
        if ($stmt->execute()) {
            $msg = "Funcionario creado exitosamente.";
        } else {
            $error = "Error al crear: " . $conn->error;
        }
    }
}

// Procesar Eliminación
if (isset($_GET['delete_id'])) {
    $id_to_delete = $_GET['delete_id'];
    
    // Evitar auto-eliminación (asumiendo que en auth.php se guarda el id en sesión)
    if (isset($_SESSION['usuario_id']) && $id_to_delete == $_SESSION['usuario_id']) {
         $error = "No puedes eliminar tu propia cuenta.";
    } else {
        // Desvincular usuario de los radicados antes de eliminarlo
        $stmt_update = $conn->prepare("UPDATE radicados SET usuario_asignado_id = NULL WHERE usuario_asignado_id = ?");
        $stmt_update->bind_param("i", $id_to_delete);
        $stmt_update->execute();
        $stmt_update->close();

        // Eliminar el usuario
        $stmt_del = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt_del->bind_param("i", $id_to_delete);
        
        try {
            if ($stmt_del->execute()) {
                 $msg = "Usuario eliminado correctamente (se desvincularon sus casos asignados).";
            } else {
                 $error = "Error al eliminar.";
            }
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1451) { // Error de restricción de clave foránea
                $error = "No se puede eliminar este usuario porque tiene trámites o registros asignados en el sistema. Considere cambiar su estado a 'Inactivo' o reasignar sus casos.";
            } else {
                $error = "Error de base de datos: " . $e->getMessage();
            }
        }
    }
}

// Procesar Edición de Usuario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['editar_usuario'])) {
    $id_user = $_POST['id_user'];
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $rol = $_POST['rol'];
    
    // Si el password no está vacío, lo actualizamos, si no, lo dejamos igual
    if (!empty($_POST['password'])) {
        $password = $_POST['password']; 
        $stmt = $conn->prepare("UPDATE usuarios SET nombre_completo=?, email=?, password_hash=?, rol_id=? WHERE id=?");
        $stmt->bind_param("sssii", $nombre, $email, $password, $rol, $id_user);
    } else {
        $stmt = $conn->prepare("UPDATE usuarios SET nombre_completo=?, email=?, rol_id=? WHERE id=?");
        $stmt->bind_param("ssii", $nombre, $email, $rol, $id_user);
    }

    if ($stmt->execute()) {
        $msg = "Usuario actualizado correctamente.";
    } else {
        $error = "Error al actualizar: " . $conn->error;
    }
}

// Listar Usuarios
$sql_users = "SELECT u.*, r.nombre as rol_nombre FROM usuarios u JOIN roles r ON u.rol_id = r.id";
$users = $conn->query($sql_users);

// Listar Roles para el select del editar
$roles_query = $conn->query("SELECT * FROM roles");
$roles = [];
while ($row = $roles_query->fetch_assoc()) {
    $roles[] = $row;
}
?>

<div class="row">
    <div class="col-md-12 mb-4">
        <h2 class="text-primary"><i class="fas fa-users-cog"></i> Gestión de Funcionarios</h2>
        <p class="text-muted">Administre el equipo de trabajo de la Personería.</p>
    </div>
</div>

<?php if(isset($msg)) echo "<div class='alert alert-success'>$msg</div>"; ?>
<?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

<div class="row">
    <!-- Formulario Crear-->
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">Nuevo Funcionario</div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Nombre Completo</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Correo Institucional</label>
                        <!-- Se cambia type="email" a type="text" para permitir caracteres especiales como ñ o tildes en dominios IDN -->
                        <input type="text" name="email" class="form-control" required placeholder="nombre@entidad.gov.co">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contraseña</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rol / Cargo</label>
                        <select name="rol" class="form-select">
                            <?php foreach ($roles as $rol): ?>
                            <option value="<?php echo $rol['id']; ?>"><?php echo $rol['nombre']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="crear_usuario" class="btn btn-primary w-100">Crear Usuario</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Lista Usuarios -->
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-body">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Guardamos los usuarios en un array para iterar dos veces (tabla y modales)
                        // Esto evita renderizar modales dentro de la tabla que pueden romper el layout o causar lentitud
                        $users_array = [];
                        while($u = $users->fetch_assoc()) {
                            $users_array[] = $u;
                        }
                        
                        foreach($users_array as $u): 
                        ?>
                        <tr>
                            <td><?php echo $u['nombre_completo']; ?></td>
                            <td><?php echo $u['email']; ?></td>
                            <td><span class="badge bg-secondary"><?php echo $u['rol_nombre']; ?></span></td>
                            <td>
                                <?php if($u['estado'] == 'activo'): ?>
                                    <span class="text-success"><i class="fas fa-check-circle"></i> Activo</span>
                                <?php else: ?>
                                    <span class="text-danger">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <!-- Botón Editar -->
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $u['id']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                
                                <!-- Botón Eliminar -->
                                <a href="usuarios.php?delete_id=<?php echo $u['id']; ?>" 
                                   class="btn btn-sm btn-outline-danger" 
                                   onclick="return confirm('¿Está seguro de que desea eliminar este usuario permanentemente?');" 
                                   title="Eliminar Usuario">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modales fuera de la tabla para evitar problemas de rendimiento y conflictos de HTML -->
<?php foreach($users_array as $u): ?>
<div class="modal fade" id="editModal<?php echo $u['id']; ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id_user" value="<?php echo $u['id']; ?>">
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="nombre" class="form-control" value="<?php echo $u['nombre_completo']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="text" name="email" class="form-control" value="<?php echo $u['email']; ?>" required placeholder="nombre@entidad.gov.co">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contraseña (Dejar en blanco para no cambiar)</label>
                        <input type="password" name="password" class="form-control" placeholder="Nueva contraseña">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rol</label>
                        <select name="rol" class="form-select">
                            <?php foreach ($roles as $rol): ?>
                            <option value="<?php echo $rol['id']; ?>" <?php if($u['rol_id'] == $rol['id']) echo 'selected'; ?>>
                                <?php echo $rol['nombre']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" name="editar_usuario" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php include 'includes/footer.php'; ?>
