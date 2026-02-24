<?php
// login.php
session_start();
include 'conexion.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // En un sistema real, usaríamos password_verify con hash
    // Aquí hacemos un login básico para desarrollo
    $sql = "SELECT id, nombre_completo, rol_id FROM usuarios WHERE email = ? AND password_hash = ?";
    
    // NOTA: Para este prototipo usaremos texto plano o hash simple. 
    // En producción SIEMPRE usar password_hash() y password_verify()
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $_SESSION['usuario_id'] = $row['id'];
        $_SESSION['usuario_nombre'] = $row['nombre_completo'];
        $_SESSION['rol_id'] = $row['rol_id'];
        
        header("Location: index.php");
        exit();
    } else {
        $error = "Credenciales incorrectas";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - Personería El Retiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .login-card { width: 100%; max-width: 400px; padding: 2rem; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); background: white; }
    </style>
</head>
<body>

<div class="login-card">
    <div class="text-center mb-4">
        <h3 class="text-primary fw-bold">Personería</h3>
        <p class="text-muted">El Retiro, Antioquia</p>
    </div>

    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label class="form-label">Correo Electrónico</label>
            <input type="text" name="email" class="form-control" required placeholder="funcionario@elretiro.gov.co">
        </div>
        <div class="mb-3">
            <label class="form-label">Contraseña</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Ingresar</button>
    </form>
    
    <div class="mt-3 text-center">
        <small class="text-muted">Sistema de Gestión de Procesos Públicos</small>
    </div>
</div>

</body>
</html>
