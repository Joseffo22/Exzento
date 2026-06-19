<?php
require_once('assets/php/conexiones/conexionMySqli.php');
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
$urlTicket = "https://exzento.com/visualizar-ticket?id=$id_ticket";
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

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>QR Factura</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0 text-center">Datos para Facturación</h3>
                    </div>
                    <div class="card-body">
                        <!-- Información del Ticket -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2">Información del Ticket</h5>
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

                        <div class="row mb-4">
                            <div class="col-md-6 text-center mb-3">
                                <img src="<?= $archivoQR ?>" class="img-thumbnail" style="max-width: 200px;">
                            </div>
                            <div class="col-md-6">
                                <div class="d-grid gap-2">
                                    <button onclick="copiarTodo()" class="btn btn-outline-primary">
                                        <i class="bi bi-clipboard"></i> Copiar Todos los Datos
                                    </button>
                                    <button onclick="enviarWhatsApp()" class="btn btn-success">
                                        <i class="bi bi-whatsapp"></i> Enviar por WhatsApp
                                    </button>
                                    <?php if ($datos['imagen_ticket'] || $datos['foto_ticket']): ?>
                                    <button class="btn btn-info" type="button" data-bs-toggle="collapse" data-bs-target="#ticketCollapse">
                                        <i class="bi bi-receipt"></i> Ver Ticket
                                    </button>
                                    <?php endif; ?>
                                    <?php if ($constancia): ?>
                                    <a href="<?= $urlConstancia ?>" class="btn btn-secondary" target="_blank">
                                        <i class="bi bi-file-earmark-text"></i> Descargar Constancia Fiscal
                                    </a>
                                    <?php endif; ?>
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

                        <?php if ($datos['imagen_ticket'] || $datos['foto_ticket']): ?>
                        <!-- Sección colapsable para el ticket -->
                        <div class="collapse mb-4" id="ticketCollapse">
                            <div class="card card-body">
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
                                             style="max-height: 500px;"
                                             alt="Ticket"
                                             onerror="this.style.display='none';">
                                    <?php endif; ?>
                                    
                                    <?php if ($datos['foto_ticket']): ?>
                                        <?php
                                        $ruta_foto = "https://movilistica.com/archivos/fotos_tickets/" . $datos['foto_ticket'];
                                        ?>
                                        <img src="<?= $ruta_foto ?>" 
                                             class="img-fluid" 
                                             style="max-height: 500px;"
                                             alt="Foto del Ticket"
                                             onerror="this.style.display='none';">
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="row g-3">
                            <!-- Datos Fiscales -->
                            <div class="col-12">
                                <h5 class="border-bottom pb-2">Datos Fiscales</h5>
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
                            <?php 
if(!isset($_SESSION['tipoUsuario'])){
?>
                            <!-- Datos de Contacto -->
                            <div class="col-12 mt-4">
                                <h5 class="border-bottom pb-2">Datos de Contacto</h5>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Nombre o Razón Social</label>
                                <p class="copiable form-control" onclick="copiarTexto(this)"><?= htmlspecialchars($datosFacturacion['Nombre o Razón Social']) ?></p>
                            </div>

                          

                            <!-- Dirección Fiscal -->
                            <div class="col-12 mt-4">
                                <h5 class="border-bottom pb-2">Dirección Fiscal</h5>
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

                            <div class="col-md-6">
                                <label class="form-label">Teléfono</label>
                                <p class="copiable form-control" onclick="copiarTexto(this)"><?= htmlspecialchars($datosFacturacion['Teléfono']) ?></p>
                            </div>

                            <?php  
}
?>
                        </div>
                    </div>
                </div>
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
    }
    .copiable:hover {
        background-color: #e9ecef;
    }
    .card {
        border-radius: 15px;
    }
    .card-header {
        border-radius: 15px 15px 0 0 !important;
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



<?php 
if(!isset($_SESSION['tipoUsuario'])){
?>
<form action="funciones/subir_factura.php" method="POST" enctype="multipart/form-data" class="p-4 border rounded bg-white shadow-sm">
  <h4 class="mb-4">Subir factura (PDF + XML)</h4>

  <div class="mb-3">
    <label for="nombre_archivo" class="form-label">Nombre del archivo</label>
    <input type="text" class="form-control" id="nombre_archivo" name="nombre_archivo" placeholder="Ej. factura_abril_001" required>
  </div>

  <div class="mb-3">
    <label for="ticket_id" class="form-label">ID del Ticket</label>
    <input type="hidden" class="form-control" id="ticket_id" name="ticket_id"  value='<?php echo $id_ticket;?>' placeholder="Ej. 12345" required>
  </div>

  <div class="mb-3">
    <label for="archivo_pdf" class="form-label">Archivo PDF de la factura</label>
    <input class="form-control" type="file" id="archivo_pdf" name="archivo_pdf" accept=".pdf" required>
  </div>

  <div class="mb-3">
    <label for="archivo_xml" class="form-label">Archivo XML de la factura</label>
    <input class="form-control" type="file" id="archivo_xml" name="archivo_xml" accept=".xml" required>
  </div>

  <button type="submit" class="btn btn-primary">Subir archivos</button>
</form>
<?php  
}
?>
