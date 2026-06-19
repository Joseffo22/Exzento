<?php
require('assets/php/conexiones/conexionMySqli.php');
$cliente_id = $_SESSION['id_usuario'];

// Obtener el filtro seleccionado
$filtro = $_GET['filtro'] ?? 'todas';
$mes = $_GET['mes'] ?? '';

// Procesar marcado como recibida manualmente
if (isset($_POST['action']) && $_POST['action'] === 'marcar_recibida') {
    $ticket_id = $_POST['ticket_id'];
    $canal = $_POST['canal'];
    $otro_canal = $_POST['otro_canal'] ?? '';
    $nota = "Recibida manualmente el " . date('d/m/Y H:i') . " por: " . $canal;
    if ($otro_canal) $nota .= " - " . $otro_canal;
    
    // Actualizar la descripción del ticket con la nota
    $stmt = $conn->prepare("UPDATE ticket SET descripcion = CONCAT(COALESCE(descripcion, ''), ' - ', ?) WHERE id = ? AND id_cliente = ?");
    if ($stmt) {
        $stmt->bind_param("sis", $nota, $ticket_id, $cliente_id);
        $stmt->execute();
        $stmt->close();
    }
    
    // Redirigir para evitar reenvío del formulario
    header("Location: facturas?filtro=" . $filtro);
    exit;
}

// Obtener tickets del cliente usando solo la estructura existente
$query = "SELECT t.*, 
                 df.razonSocial, df.rfc,
                 f.id as factura_id, f.nombre_archivo, f.archivo_pdf, f.archivo_xml, f.creado_en as fecha_factura,
                 CASE 
                     WHEN f.id IS NOT NULL THEN 'facturada'
                     WHEN t.descripcion LIKE '%Recibida manualmente%' THEN 'recibida_manual'
                     ELSE 'pendiente'
                 END as estado
          FROM ticket t
          LEFT JOIN datosFiscales df ON t.id_datos = df.id
          LEFT JOIN facturas f ON t.id = f.ticket_id
          WHERE t.id_cliente = ?";

$params = [$cliente_id];
$types = "s";

// Aplicar filtros
if ($filtro === 'facturadas') {
    $query .= " AND f.id IS NOT NULL";
} elseif ($filtro === 'pendientes') {
    $query .= " AND f.id IS NULL AND t.descripcion NOT LIKE '%Recibida manualmente%'";
} elseif ($filtro === 'recibidas_manual') {
    $query .= " AND t.descripcion LIKE '%Recibida manualmente%'";
}

if ($mes) {
    $query .= " AND MONTH(t.fecha) = ? AND YEAR(t.fecha) = ?";
    $params[] = intval($mes);
    $params[] = date('Y');
    $types .= "ii";
}

$query .= " ORDER BY t.fecha DESC";

$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = null;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Facturas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="text-center mb-3">
                    <i class="bi bi-receipt me-2"></i>Mis Facturas
                </h2>
                <p class="text-center text-muted">Gestiona y consulta todas tus facturas</p>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card shadow-sm mb-4" style="border-radius: 15px;">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Filtrar por estado:</label>
                        <select class="form-select" onchange="cambiarFiltro(this.value)">
                            <option value="todas" <?= $filtro === 'todas' ? 'selected' : '' ?>>Todas las facturas</option>
                            <option value="facturadas" <?= $filtro === 'facturadas' ? 'selected' : '' ?>>Facturadas</option>
                            <option value="pendientes" <?= $filtro === 'pendientes' ? 'selected' : '' ?>>Pendientes</option>
                            <option value="recibidas_manual" <?= $filtro === 'recibidas_manual' ? 'selected' : '' ?>>Recibidas Manualmente</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Filtrar por mes:</label>
                        <select class="form-select" onchange="cambiarMes(this.value)">
                            <option value="">Todos los meses</option>
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?= $i ?>" <?= $mes == $i ? 'selected' : '' ?>>
                                    <?= date('F', mktime(0, 0, 0, $i, 1)) ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenido -->
        <?php if ($result && $result->num_rows > 0): ?>
            <div class="row g-4">
                <?php while ($ticket = $result->fetch_assoc()): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card shadow-sm h-100" style="border-radius: 15px; border: none;">
                            <!-- Header de la tarjeta con color según estado -->
                            <div class="card-header <?= $ticket['estado'] === 'facturada' ? 'bg-success text-white' : ($ticket['estado'] === 'pendiente' ? 'bg-warning text-dark' : 'bg-info text-white') ?>" 
                                 style="border-radius: 15px 15px 0 0;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">
                                        <?php if ($ticket['estado'] === 'facturada'): ?>
                                            <i class="bi bi-check-circle me-2"></i>FACTURADA
                                        <?php elseif ($ticket['estado'] === 'pendiente'): ?>
                                            <i class="bi bi-hourglass-split me-2"></i>PENDIENTE
                                        <?php else: ?>
                                            <i class="bi bi-hand-index me-2"></i>RECIBIDA MANUAL
                                        <?php endif; ?>
                                    </h6>
                                    <span class="badge bg-light text-dark">#<?= $ticket['id'] ?></span>
                                </div>
                            </div>

                            <div class="card-body">
                                <!-- Información del ticket -->
                                <h6 class="card-title">
                                    <?= htmlspecialchars($ticket['descripcion'] ?: $ticket['razonSocial'] ?: 'Ticket #' . $ticket['id']) ?>
                                </h6>
                                
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <small class="text-muted">Monto:</small>
                                        <div class="fw-bold">$<?= number_format($ticket['monto'], 2) ?></div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Folio:</small>
                                        <div class="fw-bold">#<?= $ticket['id'] ?></div>
                                    </div>
                                </div>

                                <!-- Acciones según estado -->
                                <?php if ($ticket['estado'] === 'facturada'): ?>
                                    <!-- FACTURADAS -->
                                    <div class="d-grid gap-2">
                                        <a href="<?= site_url(htmlspecialchars($ticket['archivo_pdf'])) ?>" 
                                           target="_blank" 
                                           class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-file-pdf me-2"></i>Ver Factura PDF
                                        </a>
                                        <a href="<?= site_url(htmlspecialchars($ticket['archivo_xml'])) ?>" 
                                           download 
                                           class="btn btn-outline-secondary btn-sm">
                                            <i class="bi bi-file-code me-2"></i>Descargar XML
                                        </a>
                                        <a href="visualizar-ticket?id=<?= $ticket['id'] ?>" 
                                           class="btn btn-outline-info btn-sm">
                                            <i class="bi bi-receipt me-2"></i>Ver Ticket
                                        </a>
                                    </div>

                                <?php elseif ($ticket['estado'] === 'pendiente'): ?>
                                    <!-- PENDIENTES -->
                                    <div class="alert alert-warning py-2 mb-3" style="font-size: 0.9rem;">
                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                        Aún no has recibido la factura de este ticket
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <button onclick="reenviarSolicitud(<?= $ticket['id'] ?>)" 
                                                class="btn btn-warning btn-sm">
                                            <i class="bi bi-arrow-repeat me-2"></i>Reenviar solicitud
                                        </button>
                                        <button onclick="mostrarModalRecibida(<?= $ticket['id'] ?>)" 
                                                class="btn btn-outline-success btn-sm">
                                            <i class="bi bi-check-circle me-2"></i>Recibí esta factura
                                        </button>
                                    </div>

                                <?php else: ?>
                                    <!-- RECIBIDAS MANUALMENTE -->
                                    <div class="alert alert-info py-2 mb-3" style="font-size: 0.9rem;">
                                        <i class="bi bi-info-circle me-2"></i>
                                        <?= htmlspecialchars($ticket['descripcion']) ?>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <a href="visualizar-ticket?id=<?= $ticket['id'] ?>" 
                                           class="btn btn-outline-info btn-sm">
                                            <i class="bi bi-receipt me-2"></i>Ver Ticket
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Footer con fecha -->
                            <div class="card-footer bg-transparent" style="border-top: 1px solid #dee2e6;">
                                <small class="text-muted">
                                    <i class="bi bi-calendar me-1"></i>
                                    <?= date('d/m/Y H:i', strtotime($ticket['fecha'])) ?>
                                </small>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <!-- Sin resultados -->
            <div class="text-center py-5">
                <i class="bi bi-inbox display-1 text-muted mb-3"></i>
                <h4 class="text-muted">No se encontraron facturas</h4>
                <p class="text-muted">No hay facturas que coincidan con los filtros seleccionados.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal para marcar como recibida -->
    <div class="modal fade" id="modalRecibida" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content" style="border-radius: 15px;">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-check-circle me-2 text-success"></i>
                        ¿Dónde recibiste esta factura?
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="marcar_recibida">
                        <input type="hidden" name="ticket_id" id="ticket_id_modal">
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="canal" id="canal_email" value="Correo electrónico" required>
                                <label class="form-check-label" for="canal_email">
                                    <i class="bi bi-envelope me-2"></i>Correo electrónico
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="canal" id="canal_whatsapp" value="WhatsApp" required>
                                <label class="form-check-label" for="canal_whatsapp">
                                    <i class="bi bi-whatsapp me-2"></i>WhatsApp
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="canal" id="canal_fisico" value="Físicamente" required>
                                <label class="form-check-label" for="canal_fisico">
                                    <i class="bi bi-file-earmark me-2"></i>Físicamente
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="canal" id="canal_otro" value="Otro" required>
                                <label class="form-check-label" for="canal_otro">
                                    <i class="bi bi-three-dots me-2"></i>Otro
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3" id="otro_canal_div" style="display: none;">
                            <label for="otro_canal" class="form-label">Especificar:</label>
                            <input type="text" class="form-control" id="otro_canal" name="otro_canal" placeholder="Ej: Telegram, SMS, etc.">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle me-2"></i>Marcar como Recibida
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
    .card {
        transition: all 0.3s ease;
    }
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
    }
    .btn {
        border-radius: 8px;
        font-weight: 500;
    }
    .form-select, .form-control {
        border-radius: 8px;
    }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function cambiarFiltro(filtro) {
        const url = new URL(window.location);
        url.searchParams.set('filtro', filtro);
        window.location.href = url.toString();
    }

    function cambiarMes(mes) {
        const url = new URL(window.location);
        if (mes) {
            url.searchParams.set('mes', mes);
        } else {
            url.searchParams.delete('mes');
        }
        window.location.href = url.toString();
    }

    function reenviarSolicitud(ticketId) {
        // Guardar el medio de envío en localStorage
        const medio = localStorage.getItem('medio_envio_' + ticketId) || 'whatsapp';
        
        if (medio === 'whatsapp') {
            window.open(`visualizar-ticket?id=${ticketId}`, '_blank');
        } else {
            window.open(`visualizar-ticket?id=${ticketId}`, '_blank');
        }
    }

    function mostrarModalRecibida(ticketId) {
        document.getElementById('ticket_id_modal').value = ticketId;
        new bootstrap.Modal(document.getElementById('modalRecibida')).show();
    }

    // Mostrar campo "Otro" cuando se selecciona
    document.querySelectorAll('input[name="canal"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const otroDiv = document.getElementById('otro_canal_div');
            const otroInput = document.getElementById('otro_canal');
            
            if (this.value === 'Otro') {
                otroDiv.style.display = 'block';
                otroInput.required = true;
            } else {
                otroDiv.style.display = 'none';
                otroInput.required = false;
            }
        });
    });
    </script>
</body>
</html>
