<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>S.I.G.P - Personería El Retiro</title>
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        :root {
            --color-primary: #3366CC;
            --color-secondary: #488704;
        }
        /* Override Bootstrap Colors */
        .text-primary { color: var(--color-primary) !important; }
        .bg-primary { background-color: var(--color-primary) !important; }
        .btn-primary { background-color: var(--color-primary) !important; border-color: var(--color-primary) !important; }
        .btn-outline-primary { color: var(--color-primary) !important; border-color: var(--color-primary) !important; }
        .btn-outline-primary:hover { background-color: var(--color-primary) !important; color: #fff !important; }
        
        .text-success { color: var(--color-secondary) !important; }
        .bg-success { background-color: var(--color-secondary) !important; }
        .btn-success { background-color: var(--color-secondary) !important; border-color: var(--color-secondary) !important; }
        .btn-outline-success { color: var(--color-secondary) !important; border-color: var(--color-secondary) !important; }
        .btn-outline-success:hover { background-color: var(--color-secondary) !important; color: #fff !important; }

        body { min-height: 100vh; overflow-x: hidden; background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        #wrapper { display: flex; width: 100%; min-height: 100vh;}
        
        #sidebar-wrapper {
            min-height: 100vh;
            width: 260px;
            background: linear-gradient(180deg, var(--color-primary) 0%, #2a5298 100%);
            color: white;
            transition: margin 0.25s ease-out;
            position: fixed;
            z-index: 1000;
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
        }
        
        #sidebar-wrapper .sidebar-heading { 
            padding: 1.5rem; 
            font-size: 1.2rem; 
            border-bottom: 1px solid rgba(255,255,255,0.1); 
            text-align: center;
        }
        
        .sidebar-logo {
            max-width: 80%;
            height: auto;
            margin-bottom: 10px;
            background: rgba(255,255,255,0.9);
            padding: 5px;
            border-radius: 8px;
        }

        #sidebar-wrapper .list-group-item { 
            background: transparent; 
            color: rgba(255,255,255,0.8); 
            border: none; 
            padding: 1rem 1.5rem; 
            transition: all 0.3s;
        }
        
        #sidebar-wrapper .list-group-item:hover { 
            background: rgba(255,255,255,0.1); 
            color: #fff; 
            padding-left: 2rem;
        }
        
        #sidebar-wrapper .list-group-item.active { 
            background: var(--color-secondary); 
            color: #fff; 
            border-left: 4px solid #fff;
        }
        
        #page-content-wrapper { width: 100%; margin-left: 260px; transition: margin 0.25s ease-out; background-color: #f8f9fa; min-height: 100vh; display: flex; flex-direction: column; }
        .content-container { padding: 30px; flex: 1; }
        
        /* Navbar Superior */
        .navbar-custom {
            background-color: var(--color-secondary); /* Verde Corporativo */
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 10px 20px;
            color: white;
        }
        .navbar-custom .btn-outline-light {
            border-color: rgba(255,255,255,0.5);
            color: white;
        }
        .navbar-custom .btn-outline-light:hover {
            background-color: rgba(255,255,255,0.2);
        }
        .dropdown-menu {
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        /* Cards */
        .card-dashboard {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }
        .card-dashboard:hover {
            transform: translateY(-5px);
        }

        /* Responsive */
        @media (max-width: 768px) {
            #sidebar-wrapper { margin-left: -260px; }
            #page-content-wrapper { margin-left: 0; }
            #wrapper.toggled #sidebar-wrapper { margin-left: 0; }
            #wrapper.toggled #page-content-wrapper { margin-left: 0; position: relative; } 
            #wrapper.toggled #page-content-wrapper::before { content: ""; position: absolute; top:0; left:0; right:0; bottom:0; background: rgba(0,0,0,0.5); z-index: 999; }
        }
    </style>
</head>
<body>

<div id="wrapper">
    <!-- Sidebar -->
    <div id="sidebar-wrapper">
        <div class="sidebar-heading">
            <img src="assets/img/logopersoneria.png" alt="Logo Personería" class="sidebar-logo">
            <div class="fw-bold fs-6">S.I.G.P RETIRO</div>
        </div>
        <div class="list-group list-group-flush mt-3">
            <a href="index.php" class="list-group-item list-group-item-action"><i class="fas fa-tachometer-alt me-2"></i> Tablero Principal</a>
            
            <?php 
            $rol = $_SESSION['rol_id'] ?? 0; 
            // Roles: 1=Admin, 2=Personero, 3=Funcionario, 11=Abg. Tutelas, 12=Abg. Asesorias
            ?>

            <!-- SECCIÓN GESTIÓN -->
            <?php if(in_array($rol, [1, 2, 3])): ?>
            <div class="text-white-50 px-3 mt-3 mb-1 small text-uppercase fw-bold">Gestión</div>
            <a href="nuevo_tramite.php" class="list-group-item list-group-item-action"><i class="fas fa-plus-circle me-2"></i> Nuevo Trámite</a>
            <a href="mis_casos.php" class="list-group-item list-group-item-action"><i class="fas fa-folder-open me-2"></i> Mis Casos</a>
            <?php endif; ?>
            
            <!-- SECCIÓN PROCESOS -->
            <div class="sidebar-heading fs-6 text-uppercase text-white-50 mt-2 py-1 ps-3" style="font-size: 0.8rem !important; border-bottom:none;">Gestión de Procesos</div>
            
            <!-- Solo Admin (1), Personero (2), Funcionario General (3) y Abg. Tutelas (11) -->
            <?php if(in_array($rol, [1, 2, 3, 11])): ?>
            <a href="seguimiento_tutelas.php" class="list-group-item list-group-item-action"><i class="fas fa-gavel me-2"></i> Tutelas</a>
            <?php endif; ?>

            <!-- Solo Admin (1), Personero (2), Funcionario General (3) y Abg. Asesorias (12) -->
            <?php if(in_array($rol, [1, 2, 3, 12])): ?>
            <a href="panel_asesorias.php" class="list-group-item list-group-item-action"><i class="fas fa-chalkboard-teacher me-2"></i> Asesorías</a>
            <?php endif; ?>

            <!-- Módulos Normalizados (Solo Admin por ahora) -->
            <?php if($rol == 1): ?>
            <a href="#" class="list-group-item list-group-item-action"><i class="fas fa-file-contract me-2"></i> Derechos de Petición</a>
            <a href="#" class="list-group-item list-group-item-action"><i class="fas fa-user-shield me-2"></i> Quejas Disciplinarias</a>
            <?php endif; ?>
            
            <!-- SECCIÓN ADMINISTRACIÓN (Solo Admin) -->
            <?php if(in_array($rol, [1, 2])): ?>
            <div class="sidebar-heading fs-6 text-uppercase text-white-50 mt-2 py-1 ps-3" style="font-size: 0.8rem !important; border-bottom:none;">Administración</div>
            <a href="reportes.php" class="list-group-item list-group-item-action"><i class="fas fa-chart-bar me-2"></i> Reportes Generales</a>
            <?php endif; ?>
            
            <?php if($rol == 1): ?>
            <a href="usuarios.php" class="list-group-item list-group-item-action"><i class="fas fa-users-cog me-2"></i> Usuarios</a>
            <?php endif; ?>

            <a href="logout.php" class="list-group-item list-group-item-action mt-4"><i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión</a>
        </div>
    </div>
    <!-- /#sidebar-wrapper -->

    <!-- Page Content -->
    <div id="page-content-wrapper">
        <nav class="navbar navbar-expand-lg navbar-dark navbar-custom px-3">
            <button class="btn btn-outline-light btn-sm" id="menu-toggle"><i class="fas fa-bars"></i></button>
            
            <div class="ms-auto d-flex align-items-center">
                <div class="dropdown">
                    <a class="nav-link dropdown-toggle text-white fw-bold" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle fa-lg me-1"></i> 
                        <?php echo $_SESSION['usuario_nombre'] ?? 'Funcionario'; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                       <li><a class="dropdown-item" href="#"><i class="fas fa-user-cog me-2"></i> Perfil</a></li>
                       <li><hr class="dropdown-divider"></li>
                       <li><a class="dropdown-item text-danger" href="logout.php">Salir</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <div class="content-container">
            <!-- Inicio del Contenido Principal -->
