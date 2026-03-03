<?php
include 'conexion.php';
include 'includes/auth.php';
include 'includes/header.php';

// Cargar Tipos de Trámite
$sql_tipos = "SELECT * FROM tipos_tramite";
$res_tipos = $conn->query($sql_tipos);
?>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<style>
/* CSS para la animación de tarjetas */
.hover-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.hover-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
    cursor: pointer;
}
</style>

<div class="row justify-content-center">
    <div class="col-md-10">
        <!-- Selector de Modo con Tarjetas Grandes -->
        <div class="row mb-4" id="selectorModo">
            <div class="col-md-4 mb-3">
                <div class="card h-100 border-0 shadow-sm hover-card bg-primary text-white" onclick="iniciarNuevoTramite('nuevo')">
                    <div class="card-body text-center p-5">
                        <i class="fas fa-folder-plus fa-3x mb-3"></i>
                        <h4>NUEVO CASO</h4>
                        <p class="small opacity-75">Crear un nuevo radicado para una persona nueva o existente.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card h-100 border-0 shadow-sm hover-card bg-info text-white" onclick="iniciarNuevoTramite('solo_actualizacion')">
                     <div class="card-body text-center p-5">
                        <i class="fas fa-user-edit fa-3x mb-3"></i>
                        <h4>ACTUALIZAR DATOS</h4>
                        <p class="small opacity-75">Modificar información personal de un ciudadano sin crear caso.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card h-100 border-0 shadow-sm hover-card bg-warning text-dark" onclick="iniciarNuevoTramite('actuacion_previa')">
                     <div class="card-body text-center p-5">
                        <i class="fas fa-link fa-3x mb-3"></i>
                        <h4>TRAMITE PREVIO</h4>
                         <p class="small opacity-75">Asociar recurso, impugnación o impedimento a un caso existente.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-lg" id="cardFormulario" style="display: none;">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center flex-wrap">
                <h4 class="mb-0 py-1" id="tituloFormulario"><i class="fas fa-user-plus"></i> Registro de Ciudadano</h4>
                <button class="btn btn-outline-light btn-sm ms-2" onclick="volverSeleccion()">
                    <i class="fas fa-arrow-left"></i> Volver
                </button>
            </div>
            <div class="card-body">
                <div id="buscadorInicial" class="mb-4 p-4 bg-light rounded border" style="display: none;">
                    <h5><i class="fas fa-search"></i> Buscar Ciudadano</h5>
                    <div class="input-group">
                        <input type="text" class="form-control form-control-lg" id="inputBusquedaDoc" placeholder="Ingrese número de documento">
                        <button class="btn btn-primary" type="button" onclick="buscarParaActualizar()">Buscar</button>
                    </div>
                    <small class="text-muted">Ingrese el documento para cargar los datos existentes.</small>
                </div>

                <form action="controllers/guardar_tramite.php" method="POST" id="formRadicado">
                    <!-- Flag para saber modo de operación -->
                    <input type="hidden" name="modo_operacion" id="modo_operacion" value="nuevo">
                    <input type="hidden" name="actualizar_ciudadano" id="force_update" value="0">
                    
                    <!-- Sección Buscar Ciudadano (Oculta en modo actualización estricta inicial, pero visible en formulario) -->
                    <div id="datos_basicos_ciudadano">
                        <h5 class="text-secondary border-bottom pb-2 mb-3">1. Datos del Ciudadano (Recepción)</h5>
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Número Documento</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="documento" id="documento" required placeholder="Cédula / TI">
                                    <!-- Botón lupa original oculto en modo actualización porque ya buscó al inicio -->
                                    <button class="btn btn-outline-primary" type="button" id="btnBuscar" style="display: none;"><i class="fas fa-search"></i></button>
                                </div>
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
                    </div>

                    <div id="contenedorDatosPersonales">
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
                        <div class="col-md-2">
                            <label class="form-label">Edad</label>
                            <input type="number" class="form-control" name="edad" id="edad" min="0" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Género</label>
                            <select class="form-select" name="genero" id="genero" required>
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
                </div> <!-- END ContenedorDatosPersonales -->

                <!-- SECCIÓN PARA ACTUACIÓN PREVIA (Sólo visible en este modo) -->
                <div id="seccionActuacionPrevia" class="p-3 mb-4 bg-warning bg-opacity-10 border border-warning rounded" style="display: none;">
                    <h5 class="text-warning-emphasis fw-bold border-bottom pb-2"><i class="fas fa-link"></i> Asociar a Trámite Existente</h5>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Seleccione el Caso Principal (Padre) <span class="text-danger">*</span></label>
                            <select class="form-select" name="parent_id" id="parent_id" onchange="verificarParent()">
                                <option value="">Busque un ciudadano primero...</option>
                            </select>
                            <small class="text-muted">Despliega Tutelas y Derechos de Petición activos del ciudadano.</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tipo de Actuación <span class="text-danger">*</span></label>
                            <select class="form-select" name="tipo_actuacion_previa" id="tipo_actuacion_previa">
                                <option value="">Seleccione...</option>
                                <option value="RECURSOS">RECURSOS</option>
                                <option value="IMPUGNACIÓN">IMPUGNACIÓN</option>
                                <option value="IMPEDIMENTO">IMPEDIMENTO</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fecha Radicación Actuación <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="fecha_radicacion_actuacion" id="fecha_radicacion_actuacion">
                        </div>
                        <div class="col-md-6 mb-3">
                             <label class="form-label">Estado</label>
                             <select class="form-select" name="estado_actuacion" id="estado_actuacion">
                                <option value="ESTUDIO">ESTUDIO</option>
                                <option value="PENDIENTE">PENDIENTE</option>
                                <option value="RADICADO">RADICADO</option>
                             </select>
                        </div>
                         <div class="col-md-6 mb-3">
                            <label class="form-label">Decisión</label>
                            <select class="form-select" name="decision_actuacion" id="decision_actuacion">
                                <option value="">Seleccione...</option>
                                <option value="SI">SI</option>
                                <option value="NO">NO</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div id="seccionTramiteCompleto">
                    <!-- Sección Trámite -->
                    <h5 class="text-secondary border-bottom pb-2 mb-3">2. Información de Atención</h5>
                    
                    <div class="row g-3 mb-3">
                         <div class="col-md-6">
                            <label class="form-label">Tipo de Atención / Actuación</label>
                            <select class="form-select" name="tipo_tramite" id="tipo_tramite" required onchange="actualizarAreasAtencion()">
                                <option selected disabled value="">Seleccione...</option>
                                <option value="Asesorias">ASESORIAS</option>
                                <option value="Demanda Ejecutiva">DEMANDA EJECUTIVA</option>
                                <option value="Derecho de Peticion">DERECHO DE PETICION</option>
                                <option value="Incidentes">INCIDENTES</option>
                                <option value="Quejas Disciplinarias">QUEJAS DISCIPLINARIAS</option>
                                <option value="TOMA DE DECLARACIONES RUV">TOMA DE DECLARACIONES RUV</option>
                                <option value="Tramite">TRAMITE</option>
                                <option value="Tutelas">TUTELAS</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Área de Atención</label>
                            <select class="form-select" name="area_atencion" id="area_atencion" required onchange="verificarProcesosInternos()">
                                <option selected disabled value="">Seleccione...</option>
                                <!-- Las opciones se llenarán dinámicamente según el tipo de atención -->
                            </select>
                        </div>
                    </div>
                    
                    <div class="row g-3 mb-3" id="divProcesosInternos" style="display: none;">
                        <div class="col-md-12">
                            <label class="form-label">Procesos Internos</label>
                            <select class="form-select" name="procesos_internos" id="procesos_internos">
                                <option value="">Seleccione...</option>
                                <option value="CIVIL/ FAMILIA">CIVIL/ FAMILIA</option>
                                <option value="LABORAL">LABORAL</option>
                                <option value="PENSIÓN">PENSIÓN</option>
                                <option value="PENAL">PENAL</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3" id="divAyudaHumanitaria" style="display: none;">
                        <div class="col-md-12">
                            <label class="form-label">¿Se solicita ayuda humanitaria?</label>
                            <select class="form-select" name="solicita_ayuda_humanitaria" id="solicita_ayuda_humanitaria">
                                <option value="">Seleccione...</option>
                                <option value="Si">Si</option>
                                <option value="No">No</option>
                            </select>
                        </div>
                    </div>

                    <!-- Eliminado bloque duplicado de campos de actuación -->
                    <!-- El bloque correcto es 'seccionActuacionPrevia' arriba -->



                    <!-- Asignación Inteligente (Automática por Sesión) -->
                    <!-- El sistema asigna automáticamente al usuario logueado en el backend -->
                    
                    <div class="mb-3">
                        <label class="form-label">Observación Inicial (Recepción) ></label>
                        <textarea class="form-control" name="observacion" rows="3" placeholder="Describa brevemente la solicitud del ciudadano..."></textarea>
                    </div>

                    <!-- Sección Habeas Data y Firma -->
                    <div class="mb-4 p-3 bg-light border rounded">
                        <h5 class="text-secondary"><i class="fas fa-file-contract"></i> Autorización y Firma</h5>
                        <p class="text-muted small">Es obligatorio que el ciudadano autorice el tratamiento de datos y firme digitalmente para continuar.</p>
                        
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-warning btn-lg" id="btnAbrirHabeas" data-bs-toggle="modal" data-bs-target="#modalHabeasData">
                                <i class="fas fa-signature"></i> Firmar y Autorizar Tratamiento de Datos
                            </button>
                        </div>
                        
                        <!-- Campos ocultos para almacenar la firma y la confirmación -->
                        <input type="hidden" name="firma_digital" id="firma_digital_data">
                        <input type="hidden" name="habeas_data_aceptado" id="habeas_data_check_input" value="0">
                    </div>
                </div>


                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="index.php" class="btn btn-secondary me-md-2">Cancelar</a>
                        <button type="submit" class="btn btn-primary" id="btnSubmitForm" onclick="return validarHabeasData()"><i class="fas fa-paper-plane"></i> Crear Caso y Notificar Responsable</button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'habes_data.php'; ?>

<script>
function validarHabeasData() {
    var operacion = document.getElementById('modo_operacion').value;
    // En modo solo actualización o actuación previa, no se requiere firma del ciudadano
    if (operacion === 'solo_actualizacion' || operacion === 'actuacion_previa') {
        return true;
    }

    var aceptado = document.getElementById('habeas_data_check_input').value;
    if (aceptado != '1') {
        Swal.fire({
            icon: 'warning',
            title: 'Falta Firma',
            text: 'El ciudadano debe firmar y autorizar el tratamiento de datos antes de crear el caso.'
        });
        return false;
    }
    return true;
}
</script>

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
// Variables globales
let currentMode = 'nuevo';

function iniciarNuevoTramite(modo) {
    currentMode = modo;
    document.getElementById('selectorModo').style.display = 'none';
    document.getElementById('cardFormulario').style.display = 'block';
    
    // resetear estados
    document.getElementById('formRadicado').reset();
    // En modo 'actuacion_previa' NO queremos actualizar datos del ciudadano accidentalmente, solo leer su ID
    document.getElementById('force_update').value = (modo === 'existente' || modo === 'solo_actualizacion') ? '1' : '0';
    
    // Opcionalmente Resetear visibilidad por defecto
    document.getElementById('seccionActuacionPrevia').style.display = 'none';
    document.getElementById('parent_id').removeAttribute('required');

    if (modo === 'nuevo') {
        document.getElementById('tituloFormulario').innerHTML = '<i class="fas fa-user-plus me-2"></i>Nuevo Registro';
        document.getElementById('buscadorInicial').style.display = 'none';
        
        // Mostrar form completo y habilitado
        document.getElementById('datos_basicos_ciudadano').style.display = 'block';
        document.getElementById('contenedorDatosPersonales').style.display = 'block';
        document.getElementById('seccionTramiteCompleto').style.display = 'block';
        toggleCampos(false); 
        document.getElementById('documento').readOnly = false;
        
        document.getElementById('modo_operacion').value = 'nuevo';
        var btn = document.getElementById('btnSubmitForm');
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Crear Caso y Notificar Responsable';
        btn.className = 'btn btn-primary';
        
        document.getElementById('btnBuscar').style.display = 'block';
        
        document.getElementById('tipo_tramite').setAttribute('required', 'true');
        document.getElementById('area_atencion').setAttribute('required', 'true');
        
    } else if (modo === 'existente' || modo === 'solo_actualizacion') {
        document.getElementById('tituloFormulario').innerHTML = '<i class="fas fa-edit me-2"></i>Actualizar Datos';
        document.getElementById('buscadorInicial').style.display = 'block';
        document.getElementById('btnBuscar').style.display = 'none';
        
        document.getElementById('datos_basicos_ciudadano').style.display = 'block';
        document.getElementById('contenedorDatosPersonales').style.display = 'block';
        document.getElementById('seccionTramiteCompleto').style.display = 'none';
        
        document.getElementById('modo_operacion').value = 'solo_actualizacion';
        var btn = document.getElementById('btnSubmitForm');
        btn.innerHTML = '<i class="fas fa-save"></i> Actualización de Datos';
        btn.className = 'btn btn-success';
        
        document.getElementById('tipo_tramite').removeAttribute('required');
        document.getElementById('area_atencion').removeAttribute('required');

        toggleCampos(true); 
        document.getElementById('documento').readOnly = true;

    } else if (modo === 'actuacion_previa') {
        document.getElementById('tituloFormulario').innerHTML = '<i class="fas fa-link me-2"></i>Asociar Trámite Previo';
        
        document.getElementById('buscadorInicial').style.display = 'block';
        document.getElementById('btnBuscar').style.display = 'none'; 
        
        // OCULTAR DATOS DE CIUDADANO QUE SOLICITÓ ELIMINAR 
        document.getElementById('datos_basicos_ciudadano').style.display = 'none'; 
        document.getElementById('contenedorDatosPersonales').style.display = 'none'; 
        document.getElementById('seccionTramiteCompleto').style.display = 'none';
        
        document.getElementById('modo_operacion').value = 'actuacion_previa';
        
        var btn = document.getElementById('btnSubmitForm');
        btn.innerHTML = '<i class="fas fa-link"></i> Registrar Actuación';
        btn.className = 'btn btn-warning text-dark';
        
        document.getElementById('tipo_tramite').removeAttribute('required'); 
        document.getElementById('area_atencion').removeAttribute('required'); 
        document.getElementById('parent_id').setAttribute('required', 'true');

        toggleCampos(true); 
    }
}

function volverSeleccion() {
    Swal.fire({
        title: '¿Volver al inicio?',
        text: "Se perderán los datos no guardados.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, volver'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('cardFormulario').style.display = 'none';
            document.getElementById('selectorModo').style.display = 'flex';
            document.getElementById('formRadicado').reset();
        }
    });
}

function buscarParaActualizar() {
    let doc = document.getElementById('inputBusquedaDoc').value;
    if(doc.length < 5) {
        Swal.fire('Error', 'Ingrese un documento válido para buscar.', 'warning');
        return;
    }
    
    // Recuperar modo si se perdió (Seguridad fallos JS)
    var titulo = document.getElementById('tituloFormulario').innerText;
    if (titulo.includes('Asociar')) {
        currentMode = 'actuacion_previa';
    } else if (titulo.includes('Actualizar')) {
        currentMode = 'solo_actualizacion';
    }

    fetch('controllers/buscar_ciudadano.php?cedula=' + doc)
    .then(response => response.json())
    .then(data => {
        if(data.found) {
            // Llenar formulario simple
            const d = data.data;
            document.getElementById('documento').value = d.numero_documento;
            document.getElementById('tipo_documento').value = d.tipo_documento;
            document.getElementById('nombres').value = d.nombres;
            document.getElementById('apellidos').value = d.apellidos;
            document.getElementById('telefono').value = d.telefono;
            document.getElementById('email').value = d.email;
            document.getElementById('direccion').value = d.direccion;
            
            // Llenar selects si existen valores
            if(d.edad) document.getElementById('edad').value = d.edad;
            if(d.genero) document.getElementById('genero').value = d.genero;
            if(d.grupo_poblacional) document.getElementById('grupo_poblacional').value = d.grupo_poblacional;
            if(d.zona_residencia) document.getElementById('zona_residencia').value = d.zona_residencia;
            if(d.barrio_vereda) document.getElementById('barrio_vereda').value = d.barrio_vereda;

            Swal.fire({
                icon: 'success',
                title: 'Ciudadano encontrado',
                text: 'Cargando información...',
                timer: 1000,
                showConfirmButton: false
            });

             // LÓGICA IMPORTANTE: Visualización según modo
             if (currentMode === 'actuacion_previa') {
                // 1. Mostrar Panel de Actuación (Forzado)
                var seccion = document.getElementById('seccionActuacionPrevia');
                if (seccion) {
                    seccion.style.display = 'block';
                    seccion.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }

                // 2. Ocultar TODO lo demás (Datos Ciudadano y Tramite Genérico)
                var toHide = ['datos_basicos_ciudadano', 'contenedorDatosPersonales', 'seccionTramiteCompleto'];
                toHide.forEach(id => {
                    var el = document.getElementById(id);
                    if(el) el.style.display = 'none';
                });

                // 3. Cargar select padre
                if (d && d.id) {
                    cargarTramitesPadre(d.id);
                }
                inicializarModoActuacionPrevia();
                
            } else {
                // Modo Actualización Normal o Nuevo (si existía)
                // Mostrar datos basicos
                var toShow = ['datos_basicos_ciudadano', 'contenedorDatosPersonales'];
                toShow.forEach(id => {
                    var el = document.getElementById(id);
                    if(el) el.style.display = 'block';
                });
                
                // Habilitar campos para editar
                toggleCampos(false); 
                document.getElementById('documento').readOnly = true; 
            }

        } else {
             if (currentMode === 'actuacion_previa') {
                 Swal.fire('Error', 'El ciudadano no existe. Debe registrarlo primero como Nuevo Caso.', 'error');
                 return;
             }
             
             Swal.fire({
                title: 'No encontrado',
                text: "El ciudadano no existe. ¿Desea registrarlo como nuevo?",
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, registrar nuevo'
            }).then((result) => {
                if (result.isConfirmed) {
                    iniciarNuevoTramite('nuevo');
                    document.getElementById('documento').value = doc;
                }
            });
        }
    })
    .catch(err => console.error(err));
}

function cargarTramitesPadre(ciudadanoId) {
    const selector = document.getElementById('parent_id');
    selector.innerHTML = '<option value="">Cargando casos...</option>';
    
    fetch('controllers/buscar_tutelas_ciudadano.php?id=' + ciudadanoId)
    .then(res => res.json())
    .then(data => {
        selector.innerHTML = '<option value="">Seleccione el caso asociado...</option>';
        if(data.length > 0) {
            data.forEach(caso => {
                let opt = document.createElement('option');
                opt.value = caso.id;
                opt.text = `${caso.tipo_atencion} - Rad: ${caso.radicado || 'S/N'} (${caso.fecha_radicacion}) - ${caso.estado}`;
                selector.add(opt);
            });
        } else {
            let opt = document.createElement('option');
            opt.text = "No se encontraron casos previos para este ciudadano";
            selector.add(opt);
        }
    })
    .catch(err => {
        console.error(err);
        selector.innerHTML = '<option value="">Error cargando casos</option>';
    });
}

function verificarParent() {
    // Si selecciona un padre, podría habilitar el boton de guardar o mostrar mas info
}

function inicializarModoActuacionPrevia() {
    // Esconder campos genericos que no se usan aqui pero pueden ser required
    document.getElementById('tipo_tramite').removeAttribute('required');
    document.getElementById('area_atencion').removeAttribute('required');
    
    // Poner required a los nuevos
    document.getElementById('fecha_radicacion_actuacion').setAttribute('required', 'true');
    document.getElementById('tipo_actuacion_previa').setAttribute('required', 'true');
}

function toggleCampos(disabled) {
    const fields = ['tipo_documento', 'nombres', 'apellidos', 'telefono', 'email', 'direccion', 'edad', 'genero', 'grupo_poblacional', 'zona_residencia', 'barrio_vereda'];
    fields.forEach(id => {
        const el = document.getElementById(id);
        if(el) el.disabled = disabled;
    });
}

function actualizarAreasAtencion() {
    const tipo = document.getElementById('tipo_tramite').value;
    const areaSelect = document.getElementById('area_atencion');
    const divProcesos = document.getElementById('divProcesosInternos');
    
    // Limpiar opciones previas
    areaSelect.innerHTML = '<option selected disabled value="">SELECCIONE...</option>';
    
    // Ocultar procesos internos por defecto al cambiar tipo
    if(divProcesos) divProcesos.style.display = 'none';
    
    const divAyuda = document.getElementById('divAyudaHumanitaria');
    if(divAyuda) divAyuda.style.display = 'none';

    let opciones = [];

    if (tipo === 'Asesorias') {
        opciones = [
            'PPL (PERSONA PRIVADA DE LA LIBERTAD)',
            'AMPARO DE POBREZA'
        ];
    } else if (tipo === 'Quejas Disciplinarias') {
        opciones = [
            'CIVIL Y/O FAMILIA',
            'CONSUMIDOR',
            'DERECHOS FUNDAMENTALES',
            'FINANCIERO O SEGUROS',
            'LABORAL',
            'PENAL',
            'PENSIONES',
            'SALUD',
            'VICTIMAS'
        ];
     } else if (tipo === 'TOMA DE DECLARACIONES RUV') { // Corregido para coincidir con el value
        opciones = ['TOMA DE DECLARACION', 'SOLICITUD DE AYUDA HUMANITARIA']; 
    } else {
        // Opciones por defecto para otros trámites
        opciones = [
            'CIVIL Y/O FAMILIA',
            'CONSUMIDOR',
            'DERECHOS FUNDAMENTALES',
            'FINANCIERO O SEGUROS',
            'LABORAL',
            'PENAL',
            'PENSIONES',
            'SALUD',
            'VICTIMAS'
        ];
    }

    opciones.forEach(op => {
        let option = document.createElement('option');
        option.value = op;
        option.text = op;
        areaSelect.add(option);
    });
}

function verificarProcesosInternos() {
    const area = document.getElementById('area_atencion').value;
    const divProcesos = document.getElementById('divProcesosInternos');
    const inputProcesos = document.getElementById('procesos_internos');
    const divAyuda = document.getElementById('divAyudaHumanitaria');
    const inputAyuda = document.getElementById('solicita_ayuda_humanitaria');
    
    // Lógica para AMPARO DE POBREZA
    if (area === 'AMPARO DE POBREZA') {
        if(divProcesos) divProcesos.style.display = 'block';
        if(inputProcesos) inputProcesos.setAttribute('required', 'true');
    } else {
        if(divProcesos) divProcesos.style.display = 'none';
        if(inputProcesos) {
            inputProcesos.removeAttribute('required');
            inputProcesos.value = "";
        }
    }

    // Lógica para SOLICITUD DE AYUDA HUMANITARIA
    if (area === 'SOLICITUD DE AYUDA HUMANITARIA') {
        if(divAyuda) divAyuda.style.display = 'block';
        if(inputAyuda) inputAyuda.setAttribute('required', 'true');
    } else {
        if(divAyuda) divAyuda.style.display = 'none';
        if(inputAyuda) {
            inputAyuda.removeAttribute('required');
            inputAyuda.value = "";
        }
    }
}

// Lupa interna (Solo modo nuevo)
document.getElementById('btnBuscar').addEventListener('click', function() {
    let doc = document.getElementById('documento').value;
    if(doc.length < 3) return;

    fetch('controllers/buscar_ciudadano.php?cedula=' + doc)
    .then(response => response.json())
    .then(data => {
        if(data.found) {
            Swal.fire('Aviso', 'Este ciudadano ya existe. Si desea actualizar datos, regrese y use la opción "Actualizar / Ciudadano Existente".', 'info');
        } else {
            Swal.fire('Info', 'Documento disponible para registro.', 'success');
        }
    })
    .catch(error => console.error('Error:', error));
});

// Forzar mayúsculas en campos de texto para consistencia
['direccion', 'barrio_vereda'].forEach(id => {
    let el = document.getElementById(id);
    if(el) {
        el.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    }
});

// VALIDACIONES DE ENTRADA (Solo Números)
['inputBusquedaDoc', 'documento', 'telefono', 'edad'].forEach(id => {
    let el = document.getElementById(id);
    if(el) {
        el.addEventListener('input', function() {
            // Reemplaza todo lo que NO sea número por vacío
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }
});

// VALIDACIONES DE ENTRADA (Solo Texto - Nombres y Apellidos)
// Permite letras, espacios y caracteres especiales de español
['nombres', 'apellidos'].forEach(id => {
    let el = document.getElementById(id);
    if(el) {
        el.addEventListener('input', function() {
            // Primero convertimos a mayúsculas (ya estaba arriba pero aseguramos)
            let start = this.selectionStart;
            let end = this.selectionEnd;
            
            // Permitir letras, espacios y tildes/ñ
            this.value = this.value.toUpperCase().replace(/[^A-ZÑÁÉÍÓÚÜ\s]/g, '');
            
            // Mantener posición del cursor
            this.setSelectionRange(start, end);
        });
    }
});

// Forzar minúsculas en email, no tiene id en el input pero asumimos que es el de email
const emailInput = document.getElementById('email');
if(emailInput) {
    emailInput.addEventListener('input', function() {
        this.value = this.value.toLowerCase();
    });
}

// Interceptar envío del formulario para usar SweetAlert
document.getElementById('formRadicado').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Obtener datos del formulario
    const formData = new FormData(this);
    
    // Enviar datos por AJAX
    fetch('controllers/guardar_tramite.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        // Verificar si la respuesta es JSON válida antes de parsear
        const contentType = response.headers.get("content-type");
        if (contentType && contentType.indexOf("application/json") !== -1) {
            return response.json();
        } else {
             // Si no es JSON, probablemente sea un error del servidor o redirección
             return response.text().then(text => {
                 throw new Error('Respuesta no válida del servidor: ' + text);
             });
        }
    })
    .then(data => {
        if (data.status === 'success') {
            Swal.fire({
                title: '¡Radicado Creado!',
                text: data.message,
                icon: 'success',
                confirmButtonText: 'Aceptar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'index.php'; // Redirigir al inicio o lista de casos
                }
            });
            // Limpiar formulario si se desea
            // this.reset();
        } else if (data.status === 'confirm_update') {
            Swal.fire({
                title: 'Ciudadano Existente',
                text: data.message + "\n" + data.confirm_text,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, actualizar y crear',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    formData.append('actualizar_ciudadano', '1');
                    fetch('controllers/guardar_tramite.php', {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(res => res.json())
                    .then(finalData => {
                        if (finalData.status === 'success') {
                            Swal.fire(
                                '¡Procesado!',
                                finalData.message,
                                'success'
                            ).then(() => {
                                window.location.href = 'index.php';
                            });
                        } else {
                            Swal.fire('Error', finalData.message || 'Ocurrió un error al actualizar.', 'error');
                        }
                    })
                    .catch(err => {
                         console.error(err);
                         Swal.fire('Error', 'Error de comunicación al confirmar.', 'error');
                    });
                }
            });
        } else {
             Swal.fire({
                title: 'Atención',
                text: data.message,
                icon: 'warning',
                confirmButtonText: 'Entendido'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            title: 'Error del Sistema',
            text: 'Hubo un problema al procesar la solicitud. ' + error.message,
            icon: 'error',
            confirmButtonText: 'Cerrar'
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
