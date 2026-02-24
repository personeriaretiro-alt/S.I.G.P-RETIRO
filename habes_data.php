<!-- Modal Habeas Data y Firma -->
<div class="modal fade" id="modalHabeasData" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="staticBackdropLabel"><i class="fas fa-file-contract me-2"></i>Autorización de Tratamiento de Datos y Firma</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Columna Texto Legal -->
                    <div class="col-md-6 border-end">
                        <h5 class="text-primary mb-3">Política de Privacidad</h5>
                        <div class="p-3 bg-light rounded border" style="height: 300px; overflow-y: auto; font-size: 1.1em; line-height: 1.6;">
                            <p><strong>AUTORIZACIÓN TRATAMIENTO DE DATOS PERSONALES</strong></p>
                            <p>La Personería Municipal de El Retiro, en cumplimiento de la Ley 1581 de 2012 y sus decretos reglamentarios, le informa que sus datos personales serán recolectados y tratados para las siguientes finalidades:</p>
                            <ul>
                                <li>Gestión de trámites y servicios solicitados a la entidad.</li>
                                <li>Notificación sobre el estado de sus solicitudes.</li>
                                <li>Envío de información de interés público y reportes estadísticos.</li>
                                <li>Control y seguimiento a la gestión pública.</li>
                            </ul>
                            <p>Sus datos serán tratados de manera segura y confidencial. Usted tiene derecho a conocer, actualizar y rectificar su información.</p>
                            <p><strong>AUTORIZACIÓN DE FIRMA DIGITAL</strong></p>
                            <p>Al firmar en el recuadro adjunto, usted autoriza el uso de su firma digitalizada para la validación de este trámite específico ante la Personería de El Retiro.</p>
                        </div>
                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" id="checkAceptoTerminos" style="transform: scale(1.5);">
                            <label class="form-check-label ms-2 fw-bold" for="checkAceptoTerminos" style="font-size: 1.1em;">
                                He leído, entiendo y ACEPTO el tratamiento de mis datos personales y autorizo mi firma digital.
                            </label>
                        </div>
                    </div>

                    <!-- Columna Firma -->
                    <div class="col-md-6 d-flex flex-column align-items-center justify-content-center">
                        <h5 class="text-primary mb-3"><i class="fas fa-pen-nib"></i> Su Firma</h5>
                        <p class="text-muted text-center">Por favor, firme dentro del recuadro usando su dedo (móvil/tablet) o el ratón.</p>
                        
                        <div class="border border-2 border-dark rounded bg-white shadow-sm position-relative" style="width: 100%; height: 250px; cursor: crosshair;">
                            <canvas id="signature-pad" style="width: 100%; height: 100%; touch-action: none;"></canvas>
                            <div class="position-absolute bottom-0 end-0 p-2 text-muted fst-italic non-selectable" style="pointer-events: none; opacity: 0.5;">
                                Espacio para firmar
                            </div>
                        </div>

                        <div class="mt-3 w-100 d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-danger" id="clear-signature">
                                <i class="fas fa-eraser"></i> Borrar Firma
                            </button>
                            <!-- El botón aceptar se habilitará por JS -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-lg" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success btn-lg px-5" id="btnConfirmarFirma" disabled>
                    <i class="fas fa-check-circle me-2"></i> CONFIRMAR Y CONTINUAR
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Librería Signature Pad (CDN) -->
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar Canvas
    var canvas = document.getElementById('signature-pad');
    var signaturePad = new SignaturePad(canvas, {
        backgroundColor: 'rgba(255, 255, 255, 0)',
        penColor: 'rgb(0, 0, 0)'
    });

    // Ajustar tamaño del canvas al redimensionar ventana
    function resizeCanvas() {
        var ratio =  Math.max(window.devicePixelRatio || 1, 1);
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        canvas.getContext("2d").scale(ratio, ratio);
        signaturePad.clear(); // Limpiar al redimensionar para evitar distorsión
    }
    window.addEventListener("resize", resizeCanvas);
    // Llamar al abrir el modal para asegurar tamaño correcto
    var myModalEl = document.getElementById('modalHabeasData')
    myModalEl.addEventListener('shown.bs.modal', function (event) {
        resizeCanvas();
    })

    // Botón Borrar
    document.getElementById('clear-signature').addEventListener('click', function () {
        signaturePad.clear();
        validarFormularioModal();
    });

    // Validaciones para habilitar botón confirmar
    var checkTerminos = document.getElementById('checkAceptoTerminos');
    var btnConfirmar = document.getElementById('btnConfirmarFirma');

    function validarFormularioModal() {
        if (!signaturePad.isEmpty() && checkTerminos.checked) {
            btnConfirmar.disabled = false;
        } else {
            btnConfirmar.disabled = true;
        }
    }

    checkTerminos.addEventListener('change', validarFormularioModal);
    signaturePad.addEventListener("endStroke", validarFormularioModal);

    // Botón Confirmar
    btnConfirmar.addEventListener('click', function() {
        if (signaturePad.isEmpty()) {
            alert("Por favor ingrese su firma.");
            return;
        } 
        if (!checkTerminos.checked) {
            alert("Debe aceptar los términos y condiciones.");
            return;
        }

        // Guardar firma en input oculto
        var dataURL = signaturePad.toDataURL();
        document.getElementById('firma_digital_data').value = dataURL;
        document.getElementById('habeas_data_check_input').value = '1';

        // Actualizar UI del formulario principal para mostrar que está firmado
        document.getElementById('btnAbrirHabeas').classList.remove('btn-warning');
        document.getElementById('btnAbrirHabeas').classList.add('btn-success');
        document.getElementById('btnAbrirHabeas').innerHTML = '<i class="fas fa-check-circle"></i> Firma y Datos Autorizados';
        
        // Cerrar modal
        var modal = bootstrap.Modal.getInstance(myModalEl);
        modal.hide();

        // (Opcional) Enviar formulario automáticamente o dejar que el usuario de click en Crear Caso
        // document.getElementById('formRadicado').submit();
    });
});
</script>
