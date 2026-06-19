<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../assets/php/conexiones/conexionMySqli.php';

$id_ticket = $_GET['id'] ?? null;
if (!$id_ticket) {
    die('Falta el ID del ticket.');
}

$enviado = isset($_GET['enviado']) && $_GET['enviado'] === '1';

$query = "SELECT t.*,
                 df.razonSocial, df.rfc, df.correo, df.telefono,
                 df.calle, df.colonia, df.codigoPostal, df.municipio, df.estado,
                 rf.descripcion AS regimen_fiscal, df.constancia,
                 mp.nombre AS metodopago,
                 uc.clave AS clave_cfdi, uc.descripcion AS descripcion_cfdi
          FROM ticket t
          LEFT JOIN datosFiscales df ON t.id_datos = df.id
          LEFT JOIN regimenesFiscales rf ON df.regimen = rf.id
          LEFT JOIN usosCfdi uc ON t.usoCfdi = uc.id
          LEFT JOIN metodosPago mp ON mp.id = t.metodoPago
          WHERE t.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $id_ticket);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Ticket no encontrado.');
}

$datos = $result->fetch_assoc();
$stmt->close();

$yaFacturado = false;
$stmtFact = $conn->prepare('SELECT id FROM facturas WHERE ticket_id = ? LIMIT 1');
$stmtFact->bind_param('s', $id_ticket);
$stmtFact->execute();
if ($stmtFact->get_result()->num_rows > 0) {
    $yaFacturado = true;
}
$stmtFact->close();

if (!empty($datos['estado_factura']) && $datos['estado_factura'] === 'facturada') {
    $yaFacturado = true;
}

$constancia = $datos['constancia'] ?? '';
$urlConstancia = $constancia ? site_url($constancia) : '';
$tieneTicket = !empty($datos['imagen_ticket']) || !empty($datos['foto_ticket']);
$rutaTicket = !empty($datos['imagen_ticket'])
    ? site_url('archivos/tickets/' . $datos['imagen_ticket'])
    : (!empty($datos['foto_ticket']) ? site_url('archivos/fotos_tickets/' . $datos['foto_ticket']) : '');

$datosFiscalesCopia = [
    'RFC' => $datos['rfc'] ?? '',
    'Razón social' => $datos['razonSocial'] ?? '',
    'Régimen fiscal' => $datos['regimen_fiscal'] ?? '',
    'Uso CFDI' => ($datos['clave_cfdi'] ?? '') . ' - ' . ($datos['descripcion_cfdi'] ?? ''),
    'Forma de pago' => $datos['metodopago'] ?? '',
    'CP fiscal' => $datos['codigoPostal'] ?? '',
];

$esComercio = !isset($_SESSION['tipoUsuario']);
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Facturar — Exzento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/assets/css/comercio-ticket.css" rel="stylesheet">
</head>
<body class="comercio-page">

<div class="comercio-topbar">
    <img src="/assets/img/logo.svg" alt="Exzento">
</div>

<div class="container comercio-wrap">

    <?php if ($enviado): ?>
    <div class="comercio-success">
        <div class="comercio-success-icon"><i class="bi bi-check-lg"></i></div>
        <h2>Factura enviada</h2>
        <p>El cliente ya puede verla en su buzón de Exzento.</p>
    </div>

    <?php else: ?>

    <!-- Nombre + badges -->
    <div class="comercio-hero">
        <h1><?= htmlspecialchars($datos['razonSocial'] ?? 'Cliente') ?></h1>
        <div class="comercio-badges">
            <?php if ($yaFacturado): ?>
                <span class="comercio-badge comercio-badge-facturado"><i class="bi bi-check-circle"></i> Facturado</span>
            <?php else: ?>
                <span class="comercio-badge comercio-badge-pendiente"><i class="bi bi-clock"></i> Pendiente</span>
            <?php endif; ?>
            <span class="comercio-badge comercio-badge-monto"><i class="bi bi-receipt"></i> $<?= number_format((float)($datos['monto'] ?? 0), 2) ?> MXN</span>
        </div>
    </div>

    <!-- Pasos -->
    <div class="comercio-steps">
        <div class="comercio-step active">
            <span class="comercio-step-num">1</span>
            <span class="comercio-step-label">Ver datos</span>
        </div>
        <div class="comercio-step">
            <span class="comercio-step-num">2</span>
            <span class="comercio-step-label">Facturar</span>
        </div>
        <div class="comercio-step">
            <span class="comercio-step-num">3</span>
            <span class="comercio-step-label">Subir</span>
        </div>
    </div>

    <!-- Datos fiscales -->
    <div class="comercio-section">
        <div class="comercio-section-head">
            <h2><i class="bi bi-person"></i> Datos fiscales</h2>
            <p>Usa estos datos para emitir la factura</p>
        </div>

        <div class="comercio-dato-row" onclick="copiarFila(this)">
            <label>RFC</label>
            <span><?= htmlspecialchars($datos['rfc'] ?? '—') ?></span>
        </div>
        <div class="comercio-dato-row" onclick="copiarFila(this)">
            <label>Razón social</label>
            <span><?= htmlspecialchars($datos['razonSocial'] ?? '—') ?></span>
        </div>
        <div class="comercio-dato-row" onclick="copiarFila(this)">
            <label>Régimen fiscal</label>
            <span><?= htmlspecialchars($datos['regimen_fiscal'] ?? '—') ?></span>
        </div>
        <div class="comercio-dato-row" onclick="copiarFila(this)">
            <label>Uso CFDI</label>
            <span><?= htmlspecialchars(($datos['clave_cfdi'] ?? '') . ' - ' . ($datos['descripcion_cfdi'] ?? '')) ?></span>
        </div>
        <div class="comercio-dato-row" onclick="copiarFila(this)">
            <label>Forma de pago</label>
            <span><?= htmlspecialchars($datos['metodopago'] ?? '—') ?></span>
        </div>
        <div class="comercio-dato-row" onclick="copiarFila(this)">
            <label>CP fiscal</label>
            <span><?= htmlspecialchars($datos['codigoPostal'] ?? '—') ?></span>
        </div>

        <div class="comercio-actions">
            <button type="button" class="comercio-btn comercio-btn-ghost" onclick="copiarDatosFiscales()">
                <i class="bi bi-clipboard"></i> Copiar todos los datos
            </button>
            <?php if ($constancia): ?>
            <a href="<?= htmlspecialchars($urlConstancia) ?>" class="comercio-btn comercio-btn-ghost" target="_blank" rel="noopener">
                <i class="bi bi-download"></i> Descargar constancia fiscal
            </a>
            <?php endif; ?>
        </div>

        <button class="comercio-collapse-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#direccionCollapse">
            Ver dirección completa <i class="bi bi-chevron-down"></i>
        </button>
        <div class="collapse" id="direccionCollapse">
            <div class="comercio-collapse-body">
                <div class="comercio-dato-row" onclick="copiarFila(this)">
                    <label>Calle</label>
                    <span><?= htmlspecialchars($datos['calle'] ?? '—') ?></span>
                </div>
                <div class="comercio-dato-row" onclick="copiarFila(this)">
                    <label>Colonia</label>
                    <span><?= htmlspecialchars($datos['colonia'] ?? '—') ?></span>
                </div>
                <div class="comercio-dato-row" onclick="copiarFila(this)">
                    <label>Municipio</label>
                    <span><?= htmlspecialchars($datos['municipio'] ?? '—') ?></span>
                </div>
                <div class="comercio-dato-row" onclick="copiarFila(this)">
                    <label>Estado</label>
                    <span><?= htmlspecialchars($datos['estado'] ?? '—') ?></span>
                </div>
            </div>
        </div>
    </div>

    <?php if ($tieneTicket): ?>
    <div class="comercio-section">
        <button class="comercio-collapse-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#ticketCollapse">
            Ver fotografía del ticket <i class="bi bi-chevron-down"></i>
        </button>
        <div class="collapse" id="ticketCollapse">
            <div class="comercio-collapse-body text-center">
                <?php if (!empty($datos['imagen_ticket'])): ?>
                    <img src="<?= site_url('archivos/tickets/' . htmlspecialchars($datos['imagen_ticket'])) ?>" class="comercio-ticket-img" alt="Ticket">
                <?php endif; ?>
                <?php if (!empty($datos['foto_ticket'])): ?>
                    <img src="<?= site_url('archivos/fotos_tickets/' . htmlspecialchars($datos['foto_ticket'])) ?>" class="comercio-ticket-img" alt="Ticket">
                <?php endif; ?>
                <?php if ($rutaTicket): ?>
                <a href="<?= htmlspecialchars($rutaTicket) ?>" class="comercio-btn comercio-btn-ghost" download target="_blank">
                    <i class="bi bi-download"></i> Descargar ticket
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($esComercio && !$yaFacturado): ?>
    <div class="comercio-section" id="seccion-subir">
        <div class="comercio-section-head">
            <h2><i class="bi bi-cloud-upload"></i> Subir factura</h2>
            <p>Paso 3 — Sube el PDF y el XML generados</p>
        </div>

        <form action="/funciones/subir_factura.php" method="POST" enctype="multipart/form-data" id="formFactura">
            <input type="hidden" name="ticket_id" value="<?= htmlspecialchars($id_ticket) ?>">
            <input type="hidden" name="nombre_archivo" id="nombre_archivo" value="factura_<?= htmlspecialchars($id_ticket) ?>">

            <div class="comercio-upload-zone" id="zonePdf">
                <i class="bi bi-file-earmark-pdf"></i>
                Arrastra o selecciona el <strong>PDF</strong>
                <div class="file-name" id="namePdf"></div>
                <input type="file" id="archivo_pdf" name="archivo_pdf" accept=".pdf" required>
            </div>

            <div class="comercio-upload-zone" id="zoneXml">
                <i class="bi bi-file-earmark-code"></i>
                Arrastra o selecciona el <strong>XML</strong>
                <div class="file-name" id="nameXml"></div>
                <input type="file" id="archivo_xml" name="archivo_xml" accept=".xml" required>
            </div>

            <button type="submit" class="comercio-btn comercio-btn-primary" id="btnEnviar" disabled>
                <i class="bi bi-send"></i> Enviar factura al cliente
            </button>
        </form>
    </div>
    <?php elseif ($esComercio && $yaFacturado): ?>
    <div class="comercio-section comercio-ya-facturado">
        <i class="bi bi-check-circle me-1"></i> Este ticket ya tiene factura registrada
    </div>
    <?php endif; ?>

    <?php if ($esComercio): ?>
    <div class="comercio-contacto">
        ¿Dudas?
        <?php if (!empty($datos['correo'])): ?>
            <a href="mailto:<?= htmlspecialchars($datos['correo']) ?>"><?= htmlspecialchars($datos['correo']) ?></a>
        <?php endif; ?>
        <?php if (!empty($datos['correo']) && !empty($datos['telefono'])): ?> · <?php endif; ?>
        <?php if (!empty($datos['telefono'])): ?>
            <a href="tel:<?= htmlspecialchars($datos['telefono']) ?>"><?= htmlspecialchars($datos['telefono']) ?></a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</div>

<script>
const datosFiscales = <?= json_encode($datosFiscalesCopia, JSON_UNESCAPED_UNICODE) ?>;

function copiarFila(row) {
    const val = row.querySelector('span').innerText.trim();
    navigator.clipboard.writeText(val).then(() => {
        row.classList.add('copied');
        setTimeout(() => row.classList.remove('copied'), 1000);
    });
}

function copiarDatosFiscales() {
    const texto = Object.entries(datosFiscales).map(([k, v]) => k + ': ' + v).join('\n');
    navigator.clipboard.writeText(texto);
}

<?php if ($esComercio && !$yaFacturado && !$enviado): ?>
let pdfOk = false, xmlOk = false;

function checkReady() {
    document.getElementById('btnEnviar').disabled = !(pdfOk && xmlOk);
}

function setupZone(inputId, zoneId, nameId, flag) {
    const input = document.getElementById(inputId);
    const zone = document.getElementById(zoneId);
    const nameEl = document.getElementById(nameId);

    zone.addEventListener('click', () => input.click());

    function setFile(file) {
        if (!file) return;
        nameEl.textContent = file.name;
        zone.classList.add('loaded');
        if (flag === 'pdf') pdfOk = true; else xmlOk = true;
        checkReady();
        if (flag === 'pdf') {
            document.getElementById('nombre_archivo').value = file.name.replace(/\.[^.]+$/, '');
        }
    }

    input.addEventListener('change', () => setFile(input.files[0]));
    zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragover'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
    zone.addEventListener('drop', e => {
        e.preventDefault();
        zone.classList.remove('dragover');
        const file = e.dataTransfer.files[0];
        if (file) {
            const dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;
            setFile(file);
        }
    });
}

setupZone('archivo_pdf', 'zonePdf', 'namePdf', 'pdf');
setupZone('archivo_xml', 'zoneXml', 'nameXml', 'xml');
<?php endif; ?>
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
