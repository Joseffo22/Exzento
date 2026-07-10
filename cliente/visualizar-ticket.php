<?php
require_once('assets/php/conexiones/conexionMySqli.php');
require_once('funciones/buscar_contacto_frecuente.php');
require_once('funciones/mensaje_whatsapp_factura.php');

$id_ticket = $_GET['id'] ?? null;
if (!$id_ticket) die("Falta el ID.");

// Debug de conexión
if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}

// Consulta para obtener los datos del ticket y datos fiscales
$query = "SELECT t.*, 
                 df.razonSocial, df.rfc, df.correo, df.telefono,
                 df.calle, df.colonia, df.codigoPostal, df.municipio, df.estado,
                 rf.descripcion as regimen_fiscal,df.constancia,mp.nombre AS metodopago,
                 uc.clave as clave_cfdi, uc.descripcion as descripcion_cfdi
          FROM ticket t
          LEFT JOIN datosFiscales df ON t.id_datos = df.id
          LEFT JOIN regimenesFiscales rf ON df.regimen = rf.id
          LEFT JOIN usosCfdi uc ON t.usoCfdi = uc.id
          LEFT JOIN metodosPago mp ON mp.id = t.metodoPago

          WHERE t.id = ?";

if (isset($_SESSION['id_usuario'])) {
    $query .= " AND t.id_cliente = ?";
}

// Debug de la consulta
echo "<!-- Query: " . htmlspecialchars($query) . " -->";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Error en la preparación de la consulta: " . $conn->error);
}

if (isset($_SESSION['id_usuario'])) {
    $id_cliente = (int) $_SESSION['id_usuario'];
    if (!$stmt->bind_param("ii", $id_ticket, $id_cliente)) {
        die("Error al vincular parámetros: " . $stmt->error);
    }
} elseif (!$stmt->bind_param("i", $id_ticket)) {
    die("Error al vincular parámetros: " . $stmt->error);
}

if (!$stmt->execute()) {
    die("Error al ejecutar la consulta: " . $stmt->error);
}

$result = $stmt->get_result();
if (!$result) {
    die("Error al obtener resultados: " . $stmt->error);
}

if ($result->num_rows === 0) {
    die("Ticket no encontrado.");
}

$datos = $result->fetch_assoc();
if (!$datos) {
    die("Error al obtener datos: " . $stmt->error);
}

// Debug de datos obtenidos
echo "<!-- Datos obtenidos: " . print_r($datos, true) . " -->";
$constancia=$datos['constancia'];

// URLs
$archivoQR = "https://movilistica.com/archivos/qrs/qr_$id_ticket.png";
$urlTicket = "https://factu.movilistica.com/visualizar-ticket?id=$id_ticket";
$urlQR = "https://movilistica.com/archivos/qrs/qr_$id_ticket.png";
$urlConstancia = "https://movilistica.com/$constancia";

// Datos de facturación
$datosFacturacion = [
    'ID de Ticket' => $datos['id'],
    'Régimen Fiscal' => $datos['regimen_fiscal'],
    'RFC' => $datos['rfc'],
    'Uso de CFDI' => $datos['clave_cfdi'] . ' - ' . $datos['descripcion_cfdi'],
    'Nombre o Razón Social' => $datos['razonSocial'],
    'Correo Electrónico' => $datos['correo'],
    'Calle y Número' => $datos['calle'],
    'Colonia' => $datos['colonia'],
    'Código Postal' => $datos['codigoPostal'],
    'Municipio/Alcaldía' => $datos['municipio'],
    'Estado' => $datos['estado'],
    'País' => 'México',
    'metodoPago'=>$datos['metodopago'],
    'Teléfono' => $datos['telefono']
];

$mensaje = construirMensajeWhatsAppFactura($id_ticket, $datos, $urlTicket);

// Cerrar la conexión
$stmt->close();
$conn->close();
?>

<link href="/assets/css/datos-facturacion.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">

<div class="df-page">
    <div class="container py-4 pb-5">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="card df-card shadow-sm mb-4">
                    <div class="card-header df-card-header text-white">
                        <h1 class="mb-0">Datos para facturación</h1>
                    </div>
                    <div class="card-body p-3 p-md-4">
                        <div class="df-intro">
                            <p class="mb-0 text-center">
                                <i class="bi bi-share me-1"></i>
                                <strong>Comparte tu solicitud de factura con el comercio.</strong><br>
                                Lo más común es enviarla por WhatsApp; correo y QR también están disponibles.
                            </p>
                        </div>

                        <!-- WhatsApp: destinatario integrado + botón principal -->
                        <div class="df-whatsapp-block">
                            <button class="df-dest-toggle collapsed"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#destinatarioCollapse"
                                    aria-expanded="false"
                                    aria-controls="destinatarioCollapse"
                                    id="destinatarioToggle">
                                <span><i class="bi bi-person-lines-fill me-1"></i> Elegir contacto o teléfono</span>
                                <i class="bi bi-chevron-down"></i>
                            </button>

                            <div class="collapse" id="destinatarioCollapse">
                                <div class="df-dest-panel">
                                    <label for="contactoFrecuente" class="form-label small fw-semibold mb-1">Contacto frecuente</label>
                                    <select class="form-select form-select-sm mb-2" id="contactoFrecuente" onchange="seleccionarContactoFrecuente()">
                                        <option value="">Cargando contactos…</option>
                                    </select>

                                    <label for="telefono" class="form-label small fw-semibold mb-1">Teléfono del comercio</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                        <input type="tel"
                                               class="form-control"
                                               id="telefono"
                                               placeholder="Ej. 5215512345678"
                                               inputmode="tel">
                                    </div>
                                    <p class="df-dest-hint mb-0">
                                        Opcional: si no eliges contacto, WhatsApp te pedirá a quién enviar.
                                    </p>
                                    <p class="df-contacto-seleccionado mb-0 d-none" id="contactoSeleccionadoMsg"></p>
                                </div>
                            </div>

                            <button type="button" class="btn df-btn-whatsapp" onclick="enviarWhatsApp()">
                                <i class="bi bi-whatsapp me-2"></i>Enviar por WhatsApp
                            </button>
                        </div>

                        <!-- Acciones secundarias: correo + QR -->
                        <div class="df-actions-secondary">
                            <button type="button" class="btn df-btn-secondary" onclick="enviarCorreo()">
                                <i class="bi bi-envelope"></i> Correo
                            </button>
                            <button type="button" class="btn df-btn-secondary" data-bs-toggle="modal" data-bs-target="#modalQR">
                                <i class="bi bi-qr-code"></i> Ver QR
                            </button>
                        </div>

                        <!-- Ticket colapsado -->
                        <?php if ($datos['imagen_ticket'] || $datos['foto_ticket']): ?>
                        <button class="df-collapse-trigger collapsed"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#ticketCollapse"
                                aria-expanded="false"
                                aria-controls="ticketCollapse">
                            <span><i class="bi bi-receipt me-2"></i>Ver ticket</span>
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="collapse" id="ticketCollapse">
                            <div class="df-collapse-inner">
                                <div class="text-center mb-3">
                                    <?php if ($datos['imagen_ticket']): ?>
                                        <img src="https://movilistica.com/archivos/tickets/<?= htmlspecialchars($datos['imagen_ticket']) ?>"
                                             class="img-fluid mb-3"
                                             style="max-height: 400px; border-radius: 10px;"
                                             alt="Ticket"
                                             onerror="this.style.display='none';">
                                    <?php endif; ?>
                                    <?php if ($datos['foto_ticket']): ?>
                                        <img src="https://movilistica.com/archivos/fotos_tickets/<?= htmlspecialchars($datos['foto_ticket']) ?>"
                                             class="img-fluid"
                                             style="max-height: 400px; border-radius: 10px;"
                                             alt="Foto del ticket"
                                             onerror="this.style.display='none';">
                                    <?php endif; ?>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Folio</label>
                                        <p class="copiable form-control mb-0" onclick="copiarTexto(this)">#<?= htmlspecialchars($datos['id']) ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Fecha</label>
                                        <p class="copiable form-control mb-0" onclick="copiarTexto(this)"><?= date('d/m/Y H:i', strtotime($datos['fecha'])) ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Monto</label>
                                        <p class="copiable form-control mb-0" onclick="copiarTexto(this)">$<?= number_format($datos['monto'], 2) ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Comercio / descripción</label>
                                        <p class="copiable form-control mb-0" onclick="copiarTexto(this)"><?= htmlspecialchars($datos['descripcion'] ?: '—') ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Datos fiscales colapsados -->
                        <button class="df-collapse-trigger collapsed"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#datosFiscalesCollapse"
                                aria-expanded="false"
                                aria-controls="datosFiscalesCollapse">
                            <span><i class="bi bi-file-text me-2"></i>Ver datos fiscales</span>
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="collapse" id="datosFiscalesCollapse">
                            <div class="df-collapse-inner">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">RFC</label>
                                        <p class="copiable form-control mb-0" onclick="copiarTexto(this)"><?= htmlspecialchars($datosFacturacion['RFC']) ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Razón social</label>
                                        <p class="copiable form-control mb-0" onclick="copiarTexto(this)"><?= htmlspecialchars($datosFacturacion['Nombre o Razón Social']) ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Régimen fiscal</label>
                                        <p class="copiable form-control mb-0" onclick="copiarTexto(this)"><?= htmlspecialchars($datosFacturacion['Régimen Fiscal']) ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Uso de CFDI</label>
                                        <p class="copiable form-control mb-0" onclick="copiarTexto(this)"><?= htmlspecialchars($datosFacturacion['Uso de CFDI']) ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Método de pago</label>
                                        <p class="copiable form-control mb-0" onclick="copiarTexto(this)"><?= htmlspecialchars($datos['metodopago']) ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Código postal</label>
                                        <p class="copiable form-control mb-0" onclick="copiarTexto(this)"><?= htmlspecialchars($datosFacturacion['Código Postal']) ?></p>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Dirección</label>
                                        <p class="copiable form-control mb-0" onclick="copiarTexto(this)"><?= htmlspecialchars(implode(', ', array_filter([
                                            $datosFacturacion['Calle y Número'],
                                            $datosFacturacion['Colonia'],
                                            $datosFacturacion['Municipio/Alcaldía'],
                                            $datosFacturacion['Estado']
                                        ]))) ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Correo</label>
                                        <p class="copiable form-control mb-0" onclick="copiarTexto(this)"><?= htmlspecialchars($datosFacturacion['Correo Electrónico']) ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Teléfono fiscal</label>
                                        <p class="copiable form-control mb-0" onclick="copiarTexto(this)"><?= htmlspecialchars($datosFacturacion['Teléfono']) ?></p>
                                    </div>
                                </div>
                                <div class="df-collapse-actions">
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="copiarDatosFiscales()">
                                        <i class="bi bi-clipboard me-1"></i>Copiar datos fiscales
                                    </button>
                                    <?php if ($constancia): ?>
                                    <a href="<?= htmlspecialchars($urlConstancia) ?>" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">
                                        <i class="bi bi-download me-1"></i>Descargar constancia
                                    </a>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-sm btn-link df-link-muted" onclick="copiarTodo()">
                                        Copiar todos los datos
                                    </button>
                                </div>
                            </div>
                        </div>

                        <?php if (isset($_SESSION['id_usuario'])): ?>
                        <form method="POST"
                              action="/funciones/eliminar_ticket.php"
                              class="mt-3"
                              onsubmit="return confirm('¿Eliminar el ticket #<?= (int) $id_ticket ?>? Esta acción no se puede deshacer.');">
                            <input type="hidden" name="id_ticket" value="<?= (int) $id_ticket ?>">
                            <input type="hidden" name="redirect" value="/lista-tickets">
                            <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                                <i class="bi bi-trash me-1"></i>Eliminar ticket
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>

                <?php 
                if(!isset($_SESSION['tipoUsuario'])){
                ?>
                <!-- Formulario para subir factura -->
                <div class="card shadow-sm" style="border-radius: 15px;">
                    <div class="card-body">
                        <h4 class="mb-4">Subir factura (PDF + XML)</h4>
                        <form action="funciones/subir_factura.php" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="nombre_archivo" class="form-label">Nombre del archivo</label>
                                <input type="text" class="form-control" id="nombre_archivo" name="nombre_archivo" placeholder="Ej. factura_abril_001" required>
                            </div>

                            <div class="mb-3">
                                <input type="hidden" class="form-control" id="ticket_id" name="ticket_id" value='<?php echo $id_ticket;?>' required>
                            </div>

                            <div class="mb-3">
                                <label for="archivo_pdf" class="form-label">Archivo PDF de la factura</label>
                                <input class="form-control" type="file" id="archivo_pdf" name="archivo_pdf" accept=".pdf" required>
                            </div>

                            <div class="mb-3">
                                <label for="archivo_xml" class="form-label">Archivo XML de la factura</label>
                                <input class="form-control" type="file" id="archivo_xml" name="archivo_xml" accept=".xml" required>
                            </div>

                            <button type="submit" class="btn btn-primary w-100" style="background: #2563EB; border: none; border-radius: 10px;">
                                <i class="bi bi-upload me-2"></i>Subir archivos
                            </button>
                        </form>
                    </div>
                </div>
                <?php  
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Modal QR -->
    <div class="modal fade" id="modalQR" tabindex="-1" aria-labelledby="modalQRLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalQRLabel">Código QR del ticket</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body text-center">
                    <img src="<?= htmlspecialchars($archivoQR) ?>" class="img-fluid mb-3" style="max-width: 220px;" alt="QR del ticket">
                    <p class="text-muted small mb-3">El comercio puede escanear este código para ver tus datos.</p>
                    <a href="<?= htmlspecialchars($archivoQR) ?>" class="btn btn-sm btn-outline-primary" download target="_blank" rel="noopener">
                        <i class="bi bi-download me-1"></i>Descargar QR
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
    const datosFacturacionCompletos = <?= json_encode($datosFacturacion, JSON_UNESCAPED_UNICODE) ?>;
    const datosFiscalesResumen = <?= json_encode([
        'Régimen Fiscal' => $datosFacturacion['Régimen Fiscal'],
        'RFC' => $datosFacturacion['RFC'],
        'Uso de CFDI' => $datosFacturacion['Uso de CFDI'],
        'Nombre o Razón Social' => $datosFacturacion['Nombre o Razón Social'],
        'Correo Electrónico' => $datosFacturacion['Correo Electrónico'],
        'Calle y Número' => $datosFacturacion['Calle y Número'],
        'Colonia' => $datosFacturacion['Colonia'],
        'Código Postal' => $datosFacturacion['Código Postal'],
        'Municipio/Alcaldía' => $datosFacturacion['Municipio/Alcaldía'],
        'Estado' => $datosFacturacion['Estado'],
        'País' => $datosFacturacion['País'],
        'Teléfono' => $datosFacturacion['Teléfono'],
    ], JSON_UNESCAPED_UNICODE) ?>;

    let contactosCargados = false;

    function copiarTexto(elemento) {
        const texto = elemento.innerText;
        navigator.clipboard.writeText(texto).then(() => {
            const original = elemento.innerText;
            elemento.innerText = '✓ Copiado';
            elemento.style.backgroundColor = '#d4edda';
            setTimeout(() => {
                elemento.innerText = original;
                elemento.style.backgroundColor = '#f8f9fa';
            }, 1500);
        }).catch(err => {
            alert('No se pudo copiar');
            console.error(err);
        });
    }

    function textoDesdeObjeto(datos) {
        return Object.entries(datos)
            .map(([key, value]) => `${key}: ${value ?? ''}`)
            .join('\n');
    }

    function copiarTodo() {
        navigator.clipboard.writeText(textoDesdeObjeto(datosFacturacionCompletos))
            .then(() => alert('Todos los datos copiados al portapapeles'))
            .catch(() => alert('No se pudieron copiar los datos'));
    }

    function copiarDatosFiscales() {
        navigator.clipboard.writeText(textoDesdeObjeto(datosFiscalesResumen))
            .then(() => alert('Datos fiscales copiados al portapapeles'))
            .catch(() => alert('No se pudieron copiar los datos fiscales'));
    }

    function enviarCorreo() {
        const asunto = "Solicitud de factura - Ticket #<?= $id_ticket ?>";
        const mensaje = <?= json_encode($mensaje) ?>;
        window.open(`mailto:?subject=${encodeURIComponent(asunto)}&body=${encodeURIComponent(mensaje)}`);
    }

    function normalizarTelefonoWhatsApp(telefono) {
        const digits = (telefono || '').replace(/\D/g, '');
        if (!digits) return '';
        if (digits.length === 10) return '52' + digits;
        return digits;
    }

    function enviarWhatsApp() {
        const telefono = normalizarTelefonoWhatsApp(document.getElementById('telefono').value);
        const mensaje = <?= json_encode($mensaje) ?>;

        if (!telefono) {
            window.open(`https://wa.me/?text=${encodeURIComponent(mensaje)}`, '_blank');
            return;
        }

        window.open(`https://wa.me/${telefono}?text=${encodeURIComponent(mensaje)}`, '_blank');
    }

    function seleccionarContactoFrecuente() {
        const select = document.getElementById('contactoFrecuente');
        const msg = document.getElementById('contactoSeleccionadoMsg');
        const telefonoInput = document.getElementById('telefono');

        if (!select.value) {
            msg.classList.add('d-none');
            return;
        }

        const option = select.options[select.selectedIndex];
        const nombreContacto = option.textContent.split(' (')[0];
        telefonoInput.value = select.value;
        msg.textContent = `Contacto seleccionado: ${nombreContacto}`;
        msg.classList.remove('d-none');
    }

    function cargarContactosFrecuentes() {
        const select = document.getElementById('contactoFrecuente');
        select.innerHTML = '<option value="">Cargando contactos…</option>';

        fetch('/funciones/ajax_obtener_contactos_frecuentes.php')
            .then(response => response.json())
            .then(data => {
                select.innerHTML = '<option value="">Selecciona un contacto frecuente…</option>';

                if (!data.success) {
                    select.innerHTML = '<option value="">No se pudieron cargar los contactos</option>';
                    return;
                }

                if (!data.contactos || data.contactos.length === 0) {
                    select.innerHTML = '<option value="">No tienes contactos frecuentes aún</option>';
                    return;
                }

                data.contactos.forEach(contacto => {
                    const option = document.createElement('option');
                    option.value = contacto.telefono;
                    option.textContent = `${contacto.nombre_empresa} (${contacto.telefono})`;
                    select.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error al cargar contactos frecuentes:', error);
                select.innerHTML = '<option value="">Error al cargar contactos</option>';
            });
    }

    document.getElementById('destinatarioCollapse').addEventListener('show.bs.collapse', function () {
        if (!contactosCargados) {
            cargarContactosFrecuentes();
            contactosCargados = true;
        }
    });

    document.getElementById('telefono').addEventListener('focus', function () {
        const btnWa = document.querySelector('.df-btn-whatsapp');
        if (btnWa) {
            setTimeout(function () {
                btnWa.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }, 300);
        }
    });

    document.querySelectorAll('.df-dest-toggle, .df-collapse-trigger').forEach(function (trigger) {
        const target = document.querySelector(trigger.getAttribute('data-bs-target'));
        if (!target) {
            return;
        }

        target.addEventListener('shown.bs.collapse', function () {
            trigger.classList.remove('collapsed');
            trigger.setAttribute('aria-expanded', 'true');
        });

        target.addEventListener('hidden.bs.collapse', function () {
            trigger.classList.add('collapsed');
            trigger.setAttribute('aria-expanded', 'false');
        });
    });
    </script>
</div>