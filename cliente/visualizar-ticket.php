<?php
require_once('assets/php/conexiones/conexionMySqli.php');
require_once('funciones/buscar_contacto_frecuente.php');

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

// Debug de la consulta
echo "<!-- Query: " . htmlspecialchars($query) . " -->";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Error en la preparación de la consulta: " . $conn->error);
}

if (!$stmt->bind_param("i", $id_ticket)) {
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

// Construir el mensaje con todos los datos
$mensaje = "📋 Datos para Facturación\n\n";

$mensaje .= "🧾 Ticket: ID #{$datosFacturacion['ID de Ticket']}\n";
$mensaje .= "💳 Pago: {$datosFacturacion['metodoPago']}\n";
$mensaje .= "💼 Uso CFDI: {$datosFacturacion['Uso de CFDI']}\n\n";

$mensaje .= "📌 RFC: {$datosFacturacion['RFC']}\n";
$mensaje .= "🏛 Régimen: {$datosFacturacion['Régimen Fiscal']}\n\n";

$mensaje .= "📞 Contacto: {$datosFacturacion['Teléfono']}\n";
$mensaje .= "🌐 Ver Ticket y Datos Fiscales:\n";
$mensaje .= "$urlTicket\n\n";

$mensaje .= "📎 Importante:\n";
$mensaje .= "Puedes ver mi ticket, mis datos fiscales y subir la factura directamente en el enlace anterior.\n\n";

$mensaje .= "☁ Por favor, NO enviar por correo. Sube la factura ahí mismo para almacenarla en mi espacio.\n\n";

$mensaje .= "🔁 ¿No puedes abrir el link?\n";
$mensaje .= "Responde este mensaje con la factura o pídeme reenviar por otro medio.";

// Cerrar la conexión
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>QR Factura</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <!-- 1. DATOS PARA FACTURACIÓN -->
                <div class="card shadow-sm mb-4" style="border-radius: 15px; border: none;">
                    <div class="card-header bg-primary text-white" style="border-radius: 15px 15px 0 0; background: #2563EB;">
                        <h3 class="mb-0 text-center">DATOS PARA FACTURACIÓN</h3>
                    </div>
                    <div class="card-body">
                        <!-- 2. Descripción -->
                        <div class="alert alert-info border-0" style="background: linear-gradient(135deg, #e3f2fd, #f3e5f5); border-radius: 10px;">
                            <p class="mb-0 text-center">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Comparte el ticket y tus datos fiscales fácilmente.</strong><br>
                                No olvides revisar la información del ticket generado.
                            </p>
                        </div>

                        <!-- 3. QR -->
                        <div class="text-center mb-4">
                            <img src="<?= $archivoQR ?>" class="img-thumbnail" style="max-width: 200px; border-radius: 15px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                        </div>

                        <!-- 4. Botones de acción -->
                        <div class="row g-3 mb-4">
                            <!-- Copiar todos los datos -->
                            <div class="col-12">
                                <button onclick="copiarTodo()" class="btn btn-outline-primary w-100" style="border: 2px solid #2563EB; background-color: #f8f9fa; border-radius: 10px;">
                                    <i class="bi bi-clipboard me-2"></i>Copiar todos los datos
                                </button>
                            </div>

                            <!-- Enviar por correo -->
                            <div class="col-12">
                                <button onclick="enviarCorreo()" class="btn btn-outline-success w-100" style="border: 2px solid #28a745; background-color: #f8f9fa; border-radius: 10px;">
                                    <i class="bi bi-envelope me-2"></i>Enviar por correo
                                </button>
                            </div>

                            <!-- Enviar por WhatsApp -->
                            <div class="col-12">
                                <button onclick="enviarWhatsApp()" class="btn btn-success w-100" style="background: linear-gradient(135deg, #25d366, #128c7e); border: none; border-radius: 10px;">
                                    <i class="bi bi-whatsapp me-2"></i>Enviar por WhatsApp
                                </button>
                            </div>

                            <!-- Buscador de Contactos Frecuentes -->
                            <div class="col-12">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-people me-2"></i>Seleccionar Contacto Frecuente
                                </label>
                                <select class="form-select" id="contactoFrecuente" onchange="seleccionarContactoFrecuente()">
                                    <option value="">Selecciona un contacto frecuente...</option>
                                </select>
                                <small class="text-muted">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Al seleccionar un contacto, se llenará automáticamente el campo de teléfono
                                </small>
                            </div>

                            <!-- Campo de teléfono (solo para mostrar) -->
                            <div class="col-12">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-telephone me-2"></i>Teléfono del Comercio
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                    <input type="tel" 
                                           class="form-control" 
                                           id="telefono" 
                                           placeholder="Teléfono del comercio para envío de WhatsApp"
                                           style="border-radius: 0 10px 10px 0;"
                                           readonly>
                                    <button class="btn btn-outline-secondary" 
                                            type="button"
                                            onclick="document.getElementById('telefono').readOnly = false; document.getElementById('telefono').focus();">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                </div>
                                <small class="text-muted">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Este campo se llena automáticamente al seleccionar un contacto frecuente
                                </small>
                            </div>

                            <!-- Descargar Constancia Fiscal -->
                            <?php if ($constancia): ?>
                            <div class="col-12">
                                <a href="<?= $urlConstancia ?>" class="btn btn-secondary w-100" target="_blank" style="background: linear-gradient(135deg, #6c757d, #495057); border: none; border-radius: 10px;">
                                    <i class="bi bi-download me-2"></i>Descargar Constancia Fiscal
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- 9. Ver Ticket (desplegable) -->
                        <?php if ($datos['imagen_ticket'] || $datos['foto_ticket']): ?>
                        <div class="mb-4">
                            <button class="btn btn-outline-dark w-100" type="button" data-bs-toggle="collapse" data-bs-target="#ticketCollapse" style="border-radius: 10px;">
                                <i class="bi bi-receipt me-2"></i>Ver Ticket
                                <i class="bi bi-chevron-down ms-2"></i>
                            </button>
                            
                            <div class="collapse mt-3" id="ticketCollapse">
                                <div class="card shadow-sm" style="border-radius: 15px;">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h5 class="card-title mb-0">Información del Ticket</h5>
                                            <button class="btn btn-sm btn-outline-secondary" 
                                                    type="button" 
                                                    onclick="document.getElementById('ticketCollapse').classList.remove('show')"
                                                    style="border-radius: 8px;">
                                                <i class="bi bi-x-lg"></i> Ocultar
                                            </button>
                                        </div>
                                        
                                        <!-- Foto del Ticket -->
                                        <div class="text-center mb-4">
                                            <?php if ($datos['imagen_ticket']): ?>
                                                <?php
                                                $ruta_ticket = "https://movilistica.com/archivos/tickets/" . $datos['imagen_ticket'];
                                                ?>
                                                <img src="<?= $ruta_ticket ?>" 
                                                     class="img-fluid mb-3" 
                                                     style="max-height: 400px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"
                                                     alt="Ticket"
                                                     onerror="this.style.display='none';">
                                            <?php endif; ?>
                                            
                                            <?php if ($datos['foto_ticket']): ?>
                                                <?php
                                                $ruta_foto = "https://movilistica.com/archivos/fotos_tickets/" . $datos['foto_ticket'];
                                                ?>
                                                <img src="<?= $ruta_foto ?>" 
                                                     class="img-fluid" 
                                                     style="max-height: 400px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"
                                                     alt="Foto del Ticket"
                                                     onerror="this.style.display='none';">
                                            <?php endif; ?>
                                        </div>

                                        <!-- Información del ticket -->
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Folio</label>
                                                <p class="copiable form-control" onclick="copiarTexto(this)"><?= htmlspecialchars($datos['id']) ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="label">Fecha</label>
                                                <p class="copiable form-control" onclick="copiarTexto(this)"><?= date('d/m/Y H:i', strtotime($datos['fecha'])) ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Monto</label>
                                                <p class="copiable form-control" onclick="copiarTexto(this)">$<?= number_format($datos['monto'], 2) ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Descripción</label>
                                                <p class="copiable form-control" onclick="copiarTexto(this)"><?= htmlspecialchars($datos['descripcion'] ?: 'Prueba') ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- 10. Ver Datos Fiscales (desplegable) -->
                        <div class="mb-4">
                            <button class="btn btn-outline-dark w-100" type="button" data-bs-toggle="collapse" data-bs-target="#datosFiscalesCollapse" style="border-radius: 10px;">
                                <i class="bi bi-file-text me-2"></i>Ver Datos Fiscales
                                <i class="bi bi-chevron-down ms-2"></i>
                            </button>
                            
                            <div class="collapse mt-3" id="datosFiscalesCollapse">
                                <div class="card shadow-sm" style="border-radius: 15px;">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h5 class="card-title mb-0">Datos Fiscales</h5>
                                            <button class="btn btn-sm btn-outline-secondary" 
                                                    type="button" 
                                                    onclick="document.getElementById('datosFiscalesCollapse').classList.remove('show')"
                                                    style="border-radius: 8px;">
                                                <i class="bi bi-x-lg"></i> Ocultar
                                            </button>
                                        </div>

                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">RFC</label>
                                                <p class="copiable form-control" onclick="copiarTexto(this)"><?= htmlspecialchars($datosFacturacion['RFC']) ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Régimen Fiscal</label>
                                                <p class="copiable form-control" onclick="copiarTexto(this)"><?= htmlspecialchars($datosFacturacion['Régimen Fiscal']) ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Uso de CFDI</label>
                                                <p class="copiable form-control" onclick="copiarTexto(this)"><?= htmlspecialchars($datosFacturacion['Uso de CFDI']) ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Método de Pago</label>
                                                <p class="copiable form-control" onclick="copiarTexto(this)"><?= htmlspecialchars($datos['metodopago']) ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
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

    <style>
    .copiable {
        cursor: pointer;
        user-select: all;
        transition: all 0.2s ease;
        background-color: #f8f9fa;
        margin-bottom: 0;
        padding: 0.375rem 0.75rem;
        border-radius: 8px;
        border: 1px solid #dee2e6;
    }
    .copiable:hover {
        background-color: #e9ecef;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .card {
        border-radius: 15px;
        border: none;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .card-header {
        border-radius: 15px 15px 0 0 !important;
    }
    .btn {
        border-radius: 10px;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    .form-control {
        border-radius: 8px;
        border: 1px solid #dee2e6;
    }
    .form-control:focus {
        border-color: #2563EB;
        box-shadow: 0 0 0 0.2rem rgba(111, 66, 193, 0.25);
    }
    .input-group-text {
        border-radius: 10px 0 0 10px;
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
    }
    </style>

    <script>
    function copiarTexto(elemento) {
        const texto = elemento.innerText;
        navigator.clipboard.writeText(texto).then(() => {
            const original = elemento.innerText;
            elemento.innerText = '✅ Copiado';
            elemento.style.backgroundColor = '#d4edda';
            setTimeout(() => {
                elemento.innerText = original;
                elemento.style.backgroundColor = '#f8f9fa';
            }, 1500);
        }).catch(err => {
            alert("No se pudo copiar");
            console.error(err);
        });
    }

    function copiarTodo() {
        const datos = <?= json_encode($datosFacturacion) ?>;
        const texto = Object.entries(datos)
            .map(([key, value]) => `${key}: ${value}`)
            .join('\n');

        navigator.clipboard.writeText(texto).then(() => {
            alert('Todos los datos han sido copiados al portapapeles');
        }).catch(err => {
            alert("No se pudieron copiar los datos");
            console.error(err);
        });
    }

    function enviarCorreo() {
        const asunto = "Datos para Facturación - Ticket #<?= $id_ticket ?>";
        const mensaje = <?= json_encode($mensaje) ?>;
        const mailtoLink = `mailto:?subject=${encodeURIComponent(asunto)}&body=${encodeURIComponent(mensaje)}`;
        window.open(mailtoLink);
    }

    function enviarWhatsApp() {
        const telefono = document.getElementById('telefono').value.trim();
        const mensaje = <?= json_encode($mensaje) ?>;
        
        if (telefono === '') {
            const url = `https://wa.me/?text=${encodeURIComponent(mensaje)}`;
            window.open(url, '_blank');
            return;
        }
        
        const url = `https://wa.me/${telefono}?text=${encodeURIComponent(mensaje)}`;
        window.open(url, '_blank');
    }

    // Función para seleccionar un contacto frecuente
    function seleccionarContactoFrecuente() {
        const select = document.getElementById('contactoFrecuente');
        const contactoSeleccionado = select.value;

        if (contactoSeleccionado) {
            // Obtener el texto completo de la opción seleccionada
            const option = select.options[select.selectedIndex];
            const nombreContacto = option.textContent.split(' (')[0]; // Extraer solo el nombre
            
            document.getElementById('telefono').value = contactoSeleccionado;
            document.getElementById('telefono').readOnly = true; // Deshabilitar para edición

            // Mostrar mensaje de confirmación
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success alert-dismissible fade show';
            alertDiv.innerHTML = `
                <i class="bi bi-check-circle me-2"></i>
                <strong>¡Perfecto!</strong> Se ha seleccionado el contacto "${nombreContacto}" con teléfono ${contactoSeleccionado}.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            // Insertar después del campo de teléfono
            const telefonoField = document.getElementById('telefono').closest('.col-12');
            telefonoField.parentNode.insertBefore(alertDiv, telefonoField.nextSibling);
        } else {
            document.getElementById('telefono').value = '';
            document.getElementById('telefono').readOnly = false; // Habilitar para edición
        }
    }

    // Función para cargar contactos frecuentes
    function cargarContactosFrecuentes() {
        fetch('funciones/ajax_obtener_contactos_frecuentes.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.contactos) {
                    const select = document.getElementById('contactoFrecuente');
                    select.innerHTML = '<option value="">Selecciona un contacto frecuente...</option>';
                    
                    data.contactos.forEach(contacto => {
                        const option = document.createElement('option');
                        option.value = contacto.telefono;
                        option.textContent = `${contacto.nombre_empresa} (${contacto.telefono})`;
                        select.appendChild(option);
                    });
                } else {
                    const select = document.getElementById('contactoFrecuente');
                    select.innerHTML = '<option value="">No hay contactos frecuentes disponibles.</option>';
                }
            })
            .catch(error => {
                console.error('Error al cargar contactos frecuentes:', error);
                const select = document.getElementById('contactoFrecuente');
                select.innerHTML = '<option value="">Error al cargar contactos.</option>';
            });
    }

    // Cargar contactos al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        cargarContactosFrecuentes();
    });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>