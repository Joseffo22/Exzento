<?php
 require('assets/php/conexiones/conexionMySqli.php');
$query_regimenes = "";
$query_usos_cfdi = "SELECT * FROM usosCfdi";
$result_usos_cfdi = $conn->query($query_usos_cfdi);
$id_usuario=$_SESSION['id_usuario'];
$query_datos_fiscales = "SELECT * FROM datosFiscales WHERE id_usuario = '$id_usuario'";
$result_datos_fiscales =  $conn->query($query_datos_fiscales);
$query_metodos_pago = "SELECT * FROM metodosPago ";
$result_metodos_pago =  $conn->query($query_metodos_pago);
?>

<link href="/assets/css/generar-ticket.css" rel="stylesheet">

<div class="container py-4 ticket-form-page">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10">
            <div class="ticket-form-header">
                <h1>Generar ticket de facturación</h1>
                <p>Completa los 4 pasos para solicitar tu factura.</p>
            </div>

            <form action="../funciones/procesar_ticket.php" method="POST" class="needs-validation" enctype="multipart/form-data" novalidate>
                <input type="hidden" id="foto_camara" name="foto_camara" value="">

                <!-- Paso 1: El gasto -->
                <section class="ticket-step-card" aria-labelledby="paso-1-titulo">
                    <div class="ticket-step-header">
                        <span class="ticket-step-num" aria-hidden="true">1</span>
                        <div>
                            <h2 class="ticket-step-title" id="paso-1-titulo">El gasto</h2>
                            <p class="ticket-step-desc">Monto y comercio</p>
                        </div>
                    </div>
                    <div class="ticket-step-body">
                        <div class="mb-3">
                            <label for="monto" class="form-label">Monto a facturar</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number"
                                       class="form-control"
                                       id="monto"
                                       name="monto"
                                       step="0.01"
                                       min="0"
                                       required>
                                <div class="invalid-feedback">
                                    Por favor ingresa un monto válido
                                </div>
                            </div>
                        </div>

                        <div class="mb-0">
                            <label for="descripcion" class="form-label">Comercio o descripción del gasto</label>
                            <input type="text"
                                   class="form-control"
                                   id="descripcion"
                                   name="descripcion"
                                   list="contactos-sugerencias"
                                   placeholder="Ej. Pollos Morales, Gasolina Shell, Walmart"
                                   maxlength="200"
                                   required
                                   autocomplete="off">
                            <datalist id="contactos-sugerencias"></datalist>
                            <div class="invalid-feedback">
                                Indica el comercio o una descripción del gasto
                            </div>
                            <small class="form-text text-muted">
                                Escribe el nombre del comercio o qué compraste. Tus contactos frecuentes aparecerán como sugerencias.
                            </small>
                        </div>
                    </div>
                </section>

                <!-- Paso 2: Foto del ticket -->
                <section class="ticket-step-card" aria-labelledby="paso-2-titulo">
                    <div class="ticket-step-header">
                        <span class="ticket-step-num" aria-hidden="true">2</span>
                        <div>
                            <h2 class="ticket-step-title" id="paso-2-titulo">Foto del ticket</h2>
                            <p class="ticket-step-desc">Opcional — JPG o PNG, máx. 5 MB</p>
                        </div>
                    </div>
                    <div class="ticket-step-body">
                        <input type="file"
                               id="imagen_ticket"
                               name="imagen_ticket"
                               accept=".jpg,.jpeg,.png"
                               class="d-none"
                               onchange="previewImage(this)">

                        <div class="ticket-photo-actions">
                            <button type="button" class="btn btn-outline-primary ticket-photo-btn" onclick="tomarFoto()">
                                <i class="fas fa-camera" aria-hidden="true"></i>
                                Tomar foto
                            </button>
                            <button type="button" class="btn btn-outline-primary ticket-photo-btn" onclick="elegirGaleria()">
                                <i class="fas fa-images" aria-hidden="true"></i>
                                Elegir de galería
                            </button>
                        </div>

                        <div id="preview_container" class="ticket-preview-wrap" style="display: none;">
                            <img id="preview_image" class="img-thumbnail" alt="Vista previa del ticket" onerror="this.style.display='none';">
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeImage()">
                                    <i class="fas fa-trash-alt me-1"></i> Eliminar imagen
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Paso 3: Datos fiscales -->
                <section class="ticket-step-card" aria-labelledby="paso-3-titulo">
                    <div class="ticket-step-header">
                        <span class="ticket-step-num" aria-hidden="true">3</span>
                        <div>
                            <h2 class="ticket-step-title" id="paso-3-titulo">Datos fiscales</h2>
                            <p class="ticket-step-desc">Razón social, RFC y uso de CFDI</p>
                        </div>
                    </div>
                    <div class="ticket-step-body">
                        <div class="mb-3">
                            <label for="datos_fiscales" class="form-label">Elige tu razón social y RFC para solicitar tu factura</label>
                            <select class="form-select" id="datos_fiscales" name="datos_fiscales" required onchange="cargarDatosFiscales(this.value)">
                                <option value="">Selecciona tus datos fiscales</option>
                                <?php
                                while ($datos = $result_datos_fiscales->fetch_assoc()) {
                                    echo "<option value='{$datos['id']}'>{$datos['razonSocial']} - {$datos['rfc']}</option>";
                                }
                                ?>
                            </select>
                            <div class="invalid-feedback">
                                Por favor selecciona tus datos fiscales
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="uso_cfdi" class="form-label">Selecciona el uso que le darás a esta factura</label>
                            <select class="form-select" id="uso_cfdi" name="uso_cfdi" required>
                                <option value="">Selecciona un uso de CFDI</option>
                                <?php
                                while ($uso = $result_usos_cfdi->fetch_assoc()) {
                                    echo "<option value='{$uso['id']}'>{$uso['clave']} - {$uso['descripcion']}</option>";
                                }
                                ?>
                            </select>
                            <div class="invalid-feedback">
                                Por favor selecciona un uso de CFDI
                            </div>
                        </div>

                        <input type="hidden" id="rfc" name="rfc">
                        <input type="hidden" id="razon_social" name="razon_social">
                        <input type="hidden" id="regimen_fiscal" name="regimen_fiscal">
                        <input type="hidden" id="correo" name="correo">
                        <input type="hidden" id="calle" name="calle">
                        <input type="hidden" id="cp" name="cp">
                        <input type="hidden" id="colonia" name="colonia">
                        <input type="hidden" id="municipio" name="municipio">
                        <input type="hidden" id="estado" name="estado">
                        <input type="hidden" id="telefono" name="telefono">

                        <div id="vista_previa" class="card bg-light" style="display: none;">
                            <div id="vista_previa_content"></div>
                        </div>
                    </div>
                </section>

                <!-- Paso 4: Forma de pago -->
                <section class="ticket-step-card" aria-labelledby="paso-4-titulo">
                    <div class="ticket-step-header">
                        <span class="ticket-step-num" aria-hidden="true">4</span>
                        <div>
                            <h2 class="ticket-step-title" id="paso-4-titulo">Forma de pago</h2>
                            <p class="ticket-step-desc">Indica cómo pagaste esta compra</p>
                        </div>
                    </div>
                    <div class="ticket-step-body">
                        <div class="mb-0">
                            <label for="metodopago" class="form-label">Seleccionar método de pago</label>
                            <select class="form-select" id="metodopago" name="metodopago" required>
                                <option value="">Selecciona tu método de pago</option>
                                <?php
                                while ($pago = $result_metodos_pago->fetch_assoc()) {
                                    echo "<option value='{$pago['id']}'>{$pago['nombre']} </option>";
                                }
                                ?>
                            </select>
                            <div class="invalid-feedback">
                                Por favor selecciona tu método de pago
                            </div>
                        </div>
                    </div>
                </section>

                <div class="ticket-form-actions">
                    <button type="reset" class="btn btn-outline-secondary">
                        <i class="fas fa-eraser me-1"></i> Limpiar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check-circle me-1"></i> Generar ticket
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para tomar foto -->
<div class="modal fade" id="modalCamara" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-camera me-2"></i>Tomar foto del ticket
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <video id="video" autoplay style="max-width: 100%; height: auto;"></video>
                    <canvas id="canvas" style="display: none;"></canvas>
                    <div id="fotoTomada" style="display: none;">
                        <img id="fotoPreview" class="img-fluid" style="max-height: 400px;" alt="Foto capturada" onerror="this.style.display='none';">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnCapturar" onclick="capturarFoto()">
                    <i class="fas fa-camera me-2"></i>Capturar foto
                </button>
                <button type="button" class="btn btn-success" id="btnUsarFoto" style="display: none;" onclick="usarFoto()">
                    <i class="fas fa-check me-2"></i>Usar esta foto
                </button>
                <button type="button" class="btn btn-warning" id="btnNuevaFoto" style="display: none;" onclick="nuevaFoto()">
                    <i class="fas fa-redo me-2"></i>Nueva foto
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let stream = null;
let fotoCapturada = null;

(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()

fetch('/funciones/ajax_obtener_contactos_frecuentes.php')
    .then(response => response.json())
    .then(data => {
        if (!data.success || !data.contactos || !data.contactos.length) {
            return;
        }
        const datalist = document.getElementById('contactos-sugerencias');
        const vistos = new Set();
        data.contactos.forEach(contacto => {
            const nombre = (contacto.nombre_empresa || '').trim();
            if (!nombre || vistos.has(nombre)) {
                return;
            }
            vistos.add(nombre);
            const option = document.createElement('option');
            option.value = nombre;
            datalist.appendChild(option);
        });
    })
    .catch(() => {});

document.getElementById('rfc').addEventListener('input', function() {
    this.value = this.value.toUpperCase();
});

document.getElementById('cp').addEventListener('input', function() {
    this.value = this.value.replace(/[^0-9]/g, '').substring(0, 5);
});

function cargarDatosFiscales(id) {
    const vistaPrevia = document.getElementById('vista_previa');
    const vistaPreviaContent = document.getElementById('vista_previa_content');

    if (!id) {
        vistaPrevia.style.display = 'none';
        return;
    }

    vistaPrevia.style.display = 'block';
    vistaPreviaContent.innerHTML = '<div class="text-center p-3"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></div>';

    fetch(`../funciones/obtener_datos_fiscales.php?id=${id}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }

            document.getElementById('rfc').value = data.rfc || '';
            document.getElementById('razon_social').value = data.razon_social || '';
            document.getElementById('regimen_fiscal').value = data.regimen_fiscal || '';
            document.getElementById('correo').value = data.correo || '';
            document.getElementById('calle').value = data.calle || '';
            document.getElementById('cp').value = data.cp || '';
            document.getElementById('colonia').value = data.colonia || '';
            document.getElementById('municipio').value = data.municipio || '';
            document.getElementById('estado').value = data.estado || '';
            document.getElementById('telefono').value = data.telefono || '';

            if (data.id_usoFavorito) {
                const selectUsoCfdi = document.getElementById('uso_cfdi');
                const opcion = Array.from(selectUsoCfdi.options).find(option => option.value === data.id_usoFavorito);
                if (opcion) {
                    selectUsoCfdi.value = data.id_usoFavorito;
                }
            }

            const direccion = [
                data.calle,
                data.colonia,
                data.municipio,
                data.estado,
                data.cp ? `CP ${data.cp}` : ''
            ].filter(Boolean).join(', ');

            vistaPreviaContent.innerHTML = `
                <div class="card-body">
                    <h6 class="card-title">Datos fiscales seleccionados</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>RFC:</strong> ${data.rfc || 'No disponible'}</p>
                            <p class="mb-1"><strong>Razón social:</strong> ${data.razon_social || 'No disponible'}</p>
                            <p class="mb-1"><strong>Régimen fiscal:</strong> ${data.regimen_fiscal || 'No disponible'}</p>
                            <p class="mb-1"><strong>Uso CFDI favorito:</strong> ${data.nombre_usoFavorito || 'No disponible'}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Correo:</strong> ${data.correo || 'No disponible'}</p>
                            <p class="mb-1"><strong>Teléfono:</strong> ${data.telefono || 'No disponible'}</p>
                            <p class="mb-1"><strong>Dirección:</strong> ${direccion || 'No disponible'}</p>
                        </div>
                    </div>
                </div>
            `;
        })
        .catch(error => {
            vistaPreviaContent.innerHTML = `
                <div class="card-body">
                    <div class="alert alert-danger mb-0">
                        <i class="fas fa-exclamation-triangle"></i> Error al cargar los datos fiscales: ${error.message}
                    </div>
                </div>
            `;
        });
}

document.getElementById('monto').addEventListener('input', function() {
    if (this.value < 0) {
        this.value = 0;
    }
});

function elegirGaleria() {
    document.getElementById('imagen_ticket').click();
}

function previewImage(input) {
    const previewContainer = document.getElementById('preview_container');
    const previewImageEl = document.getElementById('preview_image');
    const file = input.files[0];

    if (file) {
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        if (!validTypes.includes(file.type)) {
            alert('Por favor selecciona una imagen en formato JPG, JPEG o PNG');
            input.value = '';
            previewContainer.style.display = 'none';
            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            alert('La imagen no debe superar los 5MB');
            input.value = '';
            previewContainer.style.display = 'none';
            return;
        }

        document.getElementById('foto_camara').value = '';

        const reader = new FileReader();
        reader.onload = function(e) {
            previewImageEl.src = e.target.result;
            previewContainer.style.display = 'block';
        }
        reader.readAsDataURL(file);
    } else {
        previewContainer.style.display = 'none';
    }
}

function removeImage() {
    const input = document.getElementById('imagen_ticket');
    const previewContainer = document.getElementById('preview_container');
    input.value = '';
    document.getElementById('foto_camara').value = '';
    fotoCapturada = null;
    previewContainer.style.display = 'none';
}

function tomarFoto() {
    const modal = new bootstrap.Modal(document.getElementById('modalCamara'));
    modal.show();

    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
        .then(function(mediaStream) {
            stream = mediaStream;
            const video = document.getElementById('video');
            video.srcObject = mediaStream;

            document.getElementById('video').style.display = 'block';
            document.getElementById('fotoTomada').style.display = 'none';
            document.getElementById('btnCapturar').style.display = 'inline-block';
            document.getElementById('btnUsarFoto').style.display = 'none';
            document.getElementById('btnNuevaFoto').style.display = 'none';
        })
        .catch(function(err) {
            console.error('Error al acceder a la cámara:', err);
            alert('No se pudo acceder a la cámara. Asegúrate de dar permisos de cámara.');
        });
}

function capturarFoto() {
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const context = canvas.getContext('2d');

    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    context.drawImage(video, 0, 0, canvas.width, canvas.height);

    canvas.toBlob(function(blob) {
        fotoCapturada = blob;

        const fotoPreview = document.getElementById('fotoPreview');
        fotoPreview.src = URL.createObjectURL(blob);

        document.getElementById('video').style.display = 'none';
        document.getElementById('fotoTomada').style.display = 'block';
        document.getElementById('btnCapturar').style.display = 'none';
        document.getElementById('btnUsarFoto').style.display = 'inline-block';
        document.getElementById('btnNuevaFoto').style.display = 'inline-block';
    }, 'image/jpeg', 0.8);
}

function usarFoto() {
    if (!fotoCapturada) {
        return;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('foto_camara').value = e.target.result;

        const file = new File([fotoCapturada], 'ticket_foto.jpg', { type: 'image/jpeg' });
        const input = document.getElementById('imagen_ticket');
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        input.files = dataTransfer.files;

        previewImage(input);

        bootstrap.Modal.getInstance(document.getElementById('modalCamara')).hide();

        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }
    };
    reader.readAsDataURL(fotoCapturada);
}

function nuevaFoto() {
    fotoCapturada = null;

    document.getElementById('video').style.display = 'block';
    document.getElementById('fotoTomada').style.display = 'none';
    document.getElementById('btnCapturar').style.display = 'inline-block';
    document.getElementById('btnUsarFoto').style.display = 'none';
    document.getElementById('btnNuevaFoto').style.display = 'none';
}

document.getElementById('modalCamara').addEventListener('hidden.bs.modal', function () {
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
        stream = null;
    }
});
</script>
