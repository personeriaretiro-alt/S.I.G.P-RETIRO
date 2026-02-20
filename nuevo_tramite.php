<?php
include 'conexion.php';
include 'includes/auth.php';
include 'includes/header.php';

// Cargar Tipos de Trámite
$sql_tipos = "SELECT * FROM tipos_tramite";
$res_tipos = $conn->query($sql_tipos);
?>

<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card shadow-lg">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-user-plus"></i> Formulario Registro de Ciudadanos y Atención</h4>
            </div>
            <div class="card-body">
                <form action="controllers/guardar_tramite.php" method="POST" id="formRadicado">
                    
                    <!-- Sección Buscar Ciudadano -->
                    <h5 class="text-secondary border-bottom pb-2 mb-3">1. Datos del Ciudadano (Recepción)</h5>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Número Documento</label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="documento" id="documento" required placeholder="Cédula / TI">
                                <button class="btn btn-outline-primary" type="button" id="btnBuscar"><i class="fas fa-search"></i></button>
                            </div>
                            <small class="text-muted">Presione la lupa para buscar.</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tipo Documento</label>
                            <select class="form-select" name="tipo_documento" id="tipo_documento" required>
                                <option value="CC">Cédula de Ciudadanía</option>
                                <option value="TI">Tarjeta de Identidad</option>
                                <option value="CE">Cédula Extranjería</option>
                                <option value="PPT">Permiso P. Temporal</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombres</label>
                            <input type="text" class="form-control" name="nombres" id="nombres" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Apellidos</label>
                            <input type="text" class="form-control" name="apellidos" id="apellidos" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                         <div class="col-md-4">
                            <label class="form-label">Teléfono / Celular</label>
                            <input type="text" class="form-control" name="telefono" id="telefono">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="email">
                        </div>
                         <div class="col-md-4">
                            <label class="form-label">Dirección</label>
                            <input type="text" class="form-control" name="direccion" id="direccion">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Género</label>
                            <select class="form-select" name="genero" id="genero">
                                <option value="">Seleccione...</option>
                                <option value="Masculino">Masculino</option>
                                <option value="Femenino">Femenino</option>
                                <option value="LGTBIQ+">LGTBIQ+</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Grupo Poblacional</label>
                            <select class="form-select" name="grupo_poblacional" id="grupo_poblacional">
                                <option value="">Seleccione...</option>
                                <option value="Ninguno">Ninguno</option>
                                <option value="Adulto Mayor">Adulto Mayor</option>
                                <option value="Jovenes">Jovenes</option>
                                <option value="Mujeres Cabeza de Familia">Mujeres Cabeza de Familia</option>
                                <option value="Victimas del Conflicto">Victimas del Conflicto</option>
                                <option value="Discapacidad">Discapacidad</option>
                                <option value="LGTBI">LGTBI</option>
                                <option value="Etnicos">Etnicos</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Zona Residencia</label>
                            <select class="form-select" name="zona_residencia" id="zona_residencia">
                                <option value="">Seleccione...</option>
                                <option value="Urbana">Urbana</option>
                                <option value="Rural">Rural</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Barrio o Vereda</label>
                            <input type="text" class="form-control" name="barrio_vereda" id="barrio_vereda">
                        </div>
                    </div>

                    <!-- Sección Trámite -->
                    <h5 class="text-secondary border-bottom pb-2 mb-3">2. Información de Atención</h5>
                    
                    <div class="row g-3 mb-3">
                         <div class="col-md-6">
                            <label class="form-label">Tipo de Atención</label>
                            <select class="form-select" name="tipo_tramite" id="tipo_tramite" required>
                                <option selected disabled value="">Seleccione...</option>
                                <option value="Asesorias">Asesorias</option>
                                <option value="Amparo de Pobreza">Amparo de Pobreza</option>
                                <option value="Demanda Ejecutiva">Demanda Ejecutiva</option>
                                <option value="Derecho de Peticion">Derecho de Peticion</option>
                                <option value="Incidentes">Incidentes</option>
                                <option value="Quejas Disciplinarias">Quejas Disciplinarias</option>
                                <option value="Recursos/Impugnaciones">Recursos/Impugnaciones</option>
                                <option value="Tramite">Tramite</option>
                                <option value="Tutelas">Tutelas</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Área de Atención</label>
                            <select class="form-select" name="area_atencion" id="area_atencion" required>
                                <option selected disabled value="">Seleccione...</option>
                                <option value="Civil y/o Familia">Civil y/o Familia</option>
                                <option value="Consumidor">Consumidor</option>
                                <option value="Derechos Fundamentales">Derechos Fundamentales</option>
                                <option value="Financiero o seguros">Financiero o seguros</option>
                                <option value="Laboral">Laboral</option>
                                <option value="Penal">Penal</option>
                                <option value="Pensiones">Pensiones</option>
                                <option value="Salud">Salud</option>
                                <option value="Victimas">Victimas</option>
                            </select>
                        </div>
                       
                    </div>

                    <!-- Asignación Inteligente -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Funcionario de Atención (Quien Registra)</label>
                            <select class="form-select" name="usuario_asignado" id="usuario_asignado" required>
                                <option value="">Seleccionar...</option>
                                <?php
                                $current_user = $_SESSION['usuario_id'] ?? 0;
                                $sql_users = "SELECT id, nombre_completo, rol_id FROM usuarios WHERE estado='activo'";
                                $res_users = $conn->query($sql_users);
                                while($u = $res_users->fetch_assoc()) {
                                    $selected = ($u['id'] == $current_user) ? "selected" : "";
                                    echo "<option value='".$u['id']."' $selected>".$u['nombre_completo']."</option>";
                                }
                                ?>
                            </select>
                            <small class="text-muted">Por defecto el sistema asigna el caso a quien lo está registrando.</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Observación Inicial (Recepción)</label>
                        <textarea class="form-control" name="observacion" rows="3" placeholder="Describa brevemente la solicitud del ciudadano..." required></textarea>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="index.php" class="btn btn-secondary me-md-2">Cancelar</a>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Crear Caso y Notificar Responsable</button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Datos de Tipos de Tramite desde PHP para JS
// La carga dinamica se ha desactivado por solicitud de modificar las opciones manualmente
/*
const tiposTramite = [
    <?php
    $res_tipos->data_seek(0);
    while($row = $res_tipos->fetch_assoc()) {
        echo "{id: ".$row['id'].", nombre: '".$row['nombre']."', sla: ".$row['sla_horas']."},";
    }
    ?>
];

// Lógica de Áreas y Tipos
document.getElementById('area_atencion').addEventListener('change', function() {
    let area = this.value;
    let selectTipo = document.getElementById('tipo_tramite');
    selectTipo.innerHTML = '<option value="">Seleccione...</option>';
    
    // Por ahora mostramos todos, pero aquí podrías filtrar específicamente
    // Por ejemplo, si area == 'Juridica' solo mostrar 'Acción de Tutela'
    
    tiposTramite.forEach(t => {
        let opt = document.createElement('option');
        opt.value = t.id;
        opt.text = t.nombre + ' (' + t.sla + 'h)'; // Use .text instead of .label for better compatibility
        selectTipo.add(opt);
    });
});
*/
</script>

<script>
// Script AJAX Simple para autocompletar
document.getElementById('btnBuscar').addEventListener('click', function() {
    let doc = document.getElementById('documento').value;
    if(doc.length < 3) return;

    fetch('controllers/buscar_ciudadano.php?cedula=' + doc)
    .then(response => response.json())
    .then(data => {
        if(data.found) {
            document.getElementById('nombres').value = data.data.nombres;
            document.getElementById('apellidos').value = data.data.apellidos;
            document.getElementById('telefono').value = data.data.telefono;
            document.getElementById('email').value = data.data.email;
            document.getElementById('direccion').value = data.data.direccion;
            document.getElementById('tipo_documento').value = data.data.tipo_documento;
            
            // Nuevos Campos
            if(data.data.genero) document.getElementById('genero').value = data.data.genero;
            if(data.data.grupo_poblacional) document.getElementById('grupo_poblacional').value = data.data.grupo_poblacional;
            if(data.data.zona_residencia) document.getElementById('zona_residencia').value = data.data.zona_residencia;
            if(data.data.barrio_vereda) document.getElementById('barrio_vereda').value = data.data.barrio_vereda;

            // Visual feedback
            alert('Ciudadano encontrado en base de datos.');
        } else {
            alert('Ciudadano nuevo. Por favor complete los datos.');
        }
    })
    .catch(error => console.error('Error:', error));
});

// Forzar mayúsculas en campos de texto para consistencia
['nombres', 'apellidos', 'direccion', 'barrio_vereda'].forEach(id => {
    document.getElementById(id).addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });
});

// Forzar minúsculas en email
document.getElementById('email').addEventListener('input', function() {
    this.value = this.value.toLowerCase();
});
</script>

<?php include 'includes/footer.php'; ?>
