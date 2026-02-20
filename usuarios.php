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

// Listar Usuarios
$sql_users = "SELECT u.*, r.nombre as rol_nombre FROM usuarios u JOIN roles r ON u.rol_id = r.id";
$users = $conn->query($sql_users);
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
    <!-- Formulario Crear -->
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
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contraseña</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rol / Cargo</label>
                        <select name="rol" class="form-select">
                            <option value="3">Funcionario (Abogado/Asistente)</option>
                            <option value="2">Personero (Auditor)</option>
                            <option value="1">Administrador IT</option>
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
                        <?php while($u = $users->fetch_assoc()): ?>
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
                                <button class="btn btn-sm btn-outline-danger" title="Desactivar"><i class="fas fa-ban"></i></button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
