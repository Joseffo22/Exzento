<?php
require_once('assets/php/conexiones/conexionMySqli.php');

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
$mensaje = "📋 *Datos para Facturación*\n\n";

// $mensaje .= "Constancia de situacion fiscal: $urlConstancia\n\n";

$mensaje .= "📝 *Datos Fiscales:*\n";
$mensaje .= "Nombre/Razón Social: {$datosFacturacion['Nombre o Razón Social']}\n";
$mensaje .= "ID de Ticket: {$datosFacturacion['ID de Ticket']}\n";
$mensaje .= "Régimen Fiscal: {$datosFacturacion['Régimen Fiscal']}\n";
$mensaje .= "RFC: {$datosFacturacion['RFC']}\n";
$mensaje .= "Metodo de Pago: {$datosFacturacion['metodoPago']}\n";
$mensaje .= "Uso de CFDI: {$datosFacturacion['Uso de CFDI']}\n\n";

$mensaje .= "👤 *Datos de Contacto:*\n";


$mensaje .= "Teléfono: {$datosFacturacion['Teléfono']}\n\n";

$mensaje .= "📍 *Dirección Fiscal:*\n";
$mensaje .= "Calle y Número: {$datosFacturacion['Calle y Número']}\n";
$mensaje .= "Colonia: {$datosFacturacion['Colonia']}\n";
$mensaje .= "C.P.: {$datosFacturacion['Código Postal']}\n";
$mensaje .= "Municipio/Alcaldía: {$datosFacturacion['Municipio/Alcaldía']}\n";
$mensaje .= "Estado: {$datosFacturacion['Estado']}\n";
$mensaje .= "País: {$datosFacturacion['País']}\n\n";

$mensaje .= "🔗 *Enlaces:*\n";
$mensaje .= "Para ver el ticket de compra, puede ingresar a mi espacio, por medio del siguiente enlace:
:*\n";
$mensaje .= "Ver Ticket: $urlTicket\n";
$mensaje .= "⚠️ *Nota:*Por favor, puedes mandar la factura a mi espacio, a través del mismo enlace que compartí anteriormente.";

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
                <!-- Sección principal de datos para facturación -->
                <div class="card shadow-sm mb-4" style="border-radius: 15px; border: none;">
                    <div class="card-header bg-primary text-white" style="border-radius: 15px 15px 0 0; background: #2563EB;">
                        <h3 class="mb-0 text-center">Datos para Facturación</h3>
                        <p class="text-center mb-0 mt-2" style="font-size: 0.9rem; opacity: 0.9;">
                            Envia la factura a este espacio de forma rápida y simple
                        </p>
                    </div>
                    <div class="card-body">
                        <!-- Botones principales -->
                        <div class="row mb-4">
                            <div class="col-md-6 mb-3">
                                <button class="btn btn-outline-dark w-100" type="button" data-bs-toggle="collapse" data-bs-target="#ticketCollapse">
                                    <i class="bi bi-receipt me-2"></i>Ver Ticket
                                    <i class="bi bi-chevron-down ms-2"></i>
                                </button>
                            </div>
                            <div class="col-md-6 mb-3">
                                <button class="btn btn-outline-dark w-100" type="button" data-bs-toggle="collapse" data-bs-target="#datosFiscalesCollapse">
                                    <i class="bi bi-file-text me-2"></i>Ver Datos de Facturación
                                    <i class="bi bi-chevron-down ms-2"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Botones de acción -->
                        <div class="row mb-4">
                            <div class="col-12 mb-3">
                                <button onclick="copiarTodo()" class="btn btn-outline-primary w-100" style="border: 2px solid #2563EB; background-color: #f8f9fa;">
                                    <i class="bi bi-clipboard me-2"></i>Copiar todos los datos
                                </button>
                            </div>
                            <div class="col-12">
                                <?php if ($constancia): ?>
                                <a href="<?= $urlConstancia ?>" class="btn btn-primary w-100" target="_blank" style="background: #2563EB;">
                                    <i class="bi bi-download me-2"></i>Descargar Constancia de Situacion Fiscal
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Información de contacto -->
                        <div class="alert alert-light border" style="border-radius: 10px;">
                            <p class="mb-2"><strong>En caso de alguna duda o falta de información, puedes contactar al usuario:</strong></p>
                            <div class="row">
                                <div class="col-md-6">
                                    <i class="bi bi-envelope me-2"></i><?= htmlspecialchars($datosFacturacion['Correo Electrónico']) ?>
                                </div>
                                <div class="col-md-6">
                                    <i class="bi bi-telephone me-2"></i><?= htmlspecialchars($datosFacturacion['Teléfono']) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sección colapsable para el ticket -->
                <?php if ($datos['imagen_ticket'] || $datos['foto_ticket']): ?>
                <div class="collapse mb-4" id="ticketCollapse">
                    <div class="card shadow-sm" style="border-radius: 15px;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0">Ticket</h5>
                                <button class="btn btn-sm btn-outline-secondary" 
                                        type="button" 
                                        onclick="document.getElementById('ticketCollapse').classList.remove('show')">
                                    <i class="bi bi-x-lg"></i> Ocultar Ticket
                                </button>
                            </div>
                            <div class="text-center">
                                <?php if ($datos['imagen_ticket']): ?>
                                    <?php
                                    $ruta_ticket = "https://movilistica.com/archivos/tickets/" . $datos['imagen_ticket'];
                                    ?>
                                    <img src="<?= $ruta_ticket ?>" 
                                         class="img-fluid mb-3" 
                                         style="max-height: 500px; border-radius: 10px;"
                                         alt="Ticket"
                                         onerror="this.style.display='none';">
                                <?php endif; ?>
                                
                                <?php if ($datos['foto_ticket']): ?>
                                    <?php
                                    $ruta_foto = "https://movilistica.com/archivos/fotos_tickets/" . $datos['foto_ticket'];
                                    ?>
                                    <img src="<?= $ruta_foto ?>" 
                                         class="img-fluid" 
                                         style="max-height: 500px; border-radius: 10px;"
                                         alt="Foto del Ticket"
                                         onerror="this.style.display='none';">
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Sección colapsable para datos fiscales -->
                <div class="collapse mb-4" id="datosFiscalesCollapse">
                    <div class="card shadow-sm" style="border-radius: 15px;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0">Datos de Facturación</h5>
                                <button class="btn btn-sm btn-outline-secondary" 
                                        type="button" 
                                        onclick="document.getElementById('datosFiscalesCollapse').classList.remove('show')">
                                    <i class="bi bi-x-lg"></i> Ocultar Datos
                                </button>
                            </div>

                            <!-- Información del Ticket -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6 class="border-bottom pb-2 text-primary">Información del Ticket</h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">ID de Ticket</label>
                                            <p class="copiable form-control" onclick="copiarTexto(this)"><?= htmlspecialchars($datosFacturacion['ID de Ticket']) ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Monto</label>
                                            <p class="copiable form-control" onclick="copiarTexto(this)">$<?= number_format($datos['monto'], 2) ?></p>
                                        </div>
                                        <?php if ($datos['descripcion']) { ?>
                                        <div class="col-12">
                                            <label class="form-label">Descripción</label>
                                            <p class="copiable form-control" onclick="copiarTexto(this)"><?= htmlspecialchars($datos['descripcion']) ?></p>
                                        </div>
                                        <?php } ?>
                                        <?php if ($datos['numeroTicket']) { ?>
                                        <div class="col-md-6">
                                            <label class="form-label">Número de Ticket</label>
                                            <p class="copiable form-control" onclick="copiarTexto(this)"><?= htmlspecialchars($datos['numeroTicket']) ?></p>
                                        </div>
                                        <?php } ?>
                                        <div class="col-md-6">
                                            <label class="form-label">Fecha</label>
                                            <p class="copiable form-control" onclick="copiarTexto(this)"><?= date('d/m/Y H:i', strtotime($datos['fecha'])) ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Datos Fiscales -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6 class="border-bottom pb-2 text-primary">Datos Fiscales</h6>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Régimen Fiscal</label>
                                    <p class="copiable form-control" onclick="copiarTexto(this)"><?= htmlspecialchars($datosFacturacion['Régimen Fiscal']) ?></p>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">RFC</label>
                                    <p class="copiable form-control" onclick="copiarTexto(this)"><?= htmlspecialchars($datosFacturacion['RFC']) ?></p>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Uso de CFDI</label>
                                    <p class="copiable form-control" onclick="copiarTexto(this)"><?= htmlspecialchars($datosFacturacion['Uso de CFDI']) ?></p>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Metodo de Pago</label>
                                    <p class="copiable form-control" onclick="copiarTexto(this)"><?= htmlspecialchars($datos['metodopago']) ?></p>
                                </div>
                            </div>

                            <?php 
                            if(!isset($_SESSION['tipoUsuario'])){
                            ?>
                            <!-- Datos de Contacto -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6 class="border-bottom pb-2 text-primary">Datos de Contacto</h6>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Nombre o Razón Social</label>
                                    <p class="copiable form-control" onclick="copiarTexto(this)"><?= htmlspecialchars($datosFacturacion['Nombre o Razón Social']) ?></p>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Correo Electrónico</label>
                                    <p class="copiable form-control" onclick="copiarTexto(this)"><?= htmlspecialchars($datosFacturacion['Correo Electrónico']) ?></p>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Teléfono</label>
                                    <p class="copiable form-control" onclick="copiarTexto(this)"><?= htmlspecialchars($datosFacturacion['Teléfono']) ?></p>
                                </div>
                            </div>

                            <!-- Dirección Fiscal -->
                            <div class="row">
                                <div class="col-12">
                                    <h6 class="border-bottom pb-2 text-primary">Dirección Fiscal</h6>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Calle y Número</label>
                                    <p class="copiable form-control" onclick="copiarTexto(this)"><?= htmlspecialchars($datosFacturacion['Calle y Número']) ?></p>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Colonia</label>
                                    <p class="copiable form-control" onclick="copiarTexto(this)"><?= htmlspecialchars($datosFacturacion['Colonia']) ?></p>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Código Postal</label>
                                    <p class="copiable form-control" onclick="copiarTexto(this)"><?= htmlspecialchars($datosFacturacion['Código Postal']) ?></p>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Municipio/Alcaldía</label>
                                    <p class="copiable form-control" onclick="copiarTexto(this)"><?= htmlspecialchars($datosFacturacion['Municipio/Alcaldía']) ?></p>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Estado</label>
                                    <p class="copiable form-control" onclick="copiarTexto(this)"><?= htmlspecialchars($datosFacturacion['Estado']) ?></p>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">País</label>
                                    <p class="copiable form-control" onclick="copiarTexto(this)"><?= htmlspecialchars($datosFacturacion['País']) ?></p>
                                </div>
                            </div>
                            <?php  
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <!-- QR y botones de acción
                <div class="card shadow-sm mb-4" style="border-radius: 15px;">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 text-center mb-3">
                                <img src="<?= $archivoQR ?>" class="img-thumbnail" style="max-width: 200px; border-radius: 10px;">
                            </div>
                            <div class="col-md-6">
                                <div class="d-grid gap-2">
                                    <button onclick="enviarWhatsApp()" class="btn btn-success">
                                        <i class="bi bi-whatsapp"></i> Enviar por WhatsApp
                                    </button>
                                </div>
                                <div class="mt-3">
                                    <label for="telefono" class="form-label">Enviar por WhatsApp (con código de país)</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-whatsapp"></i></span>
                                        <input type="tel" class="form-control" id="telefono" placeholder="Ej. 5215555555555">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> -->

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
                                <!-- <label for="ticket_id" class="form-label">ID del Ticket</label> -->
                                <input type="hidden" class="form-control" id="ticket_id" name="ticket_id" value='<?php echo $id_ticket;?>' placeholder="Ej. 12345" required>
                                <!-- <p class="form-control-plaintext"><?php echo $id_ticket;?></p> -->
                            </div>

                            <div class="mb-3">
                                <label for="archivo_pdf" class="form-label">Archivo PDF de la factura</label>
                                <input class="form-control" type="file" id="archivo_pdf" name="archivo_pdf" accept=".pdf" required>
                            </div>

                            <div class="mb-3">
                                <label for="archivo_xml" class="form-label">Archivo XML de la factura</label>
                                <input class="form-control" type="file" id="archivo_xml" name="archivo_xml" accept=".xml" required>
                            </div>

                            <button type="submit" class="btn btn-primary w-100" style="background: #2563EB; border: none;">
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
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>