<?php
require('assets/php/conexiones/conexionMySqli.php');
$cliente_id = $_SESSION['id_usuario'];

// Obtener el filtro seleccionado (solo facturas reales: facturadas o recibidas manualmente)
$filtro = $_GET['filtro'] ?? 'todas';
if (!in_array($filtro, ['todas', 'facturadas', 'recibidas_manual'], true)) {
    $filtro = 'todas';
}
$mes = $_GET['mes'] ?? '';

// Obtener tickets del cliente usando solo la estructura existente
$query = "SELECT t.*, 
                 df.razonSocial, df.rfc,
                 f.id as factura_id, f.nombre_archivo, f.archivo_pdf, f.archivo_xml, f.creado_en as fecha_factura,
                 CASE 
                     WHEN f.id IS NOT NULL THEN 'facturada'
                     ELSE 'recibida_manual'
                 END as estado
          FROM ticket t
          LEFT JOIN datosFiscales df ON t.id_datos = df.id
          LEFT JOIN facturas f ON t.id = f.ticket_id
          WHERE t.id_cliente = ?
          AND (f.id IS NOT NULL OR t.descripcion LIKE '%Recibida manualmente%')";

$params = [$cliente_id];
$types = "s";

// Aplicar filtros
if ($filtro === 'facturadas') {
    $query .= " AND f.id IS NOT NULL";
} elseif ($filtro === 'recibidas_manual') {
    $query .= " AND f.id IS NULL AND t.descripcion LIKE '%Recibida manualmente%'";
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
                <p class="text-center text-muted">Facturas que ya recibiste: subidas por el comercio o marcadas manualmente. Los tickets pendientes están en <a href="/lista-tickets">Mis tickets</a>.</p>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card shadow-sm mb-4" style="border-radius: 15px;">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Filtrar por estado:</label>
                        <select class="form-select" onchange="cambiarFiltro(this.value)">
                            <option value="todas" <?= $filtro === 'todas' ? 'selected' : '' ?>>Todas</option>
                            <option value="facturadas" <?= $filtro === 'facturadas' ? 'selected' : '' ?>>Facturadas</option>
                            <option value="recibidas_manual" <?= $filtro === 'recibidas_manual' ? 'selected' : '' ?>>Recibidas manualmente</option>
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
                            <div class="card-header <?= $ticket['estado'] === 'facturada' ? 'bg-success text-white' : 'bg-info text-white' ?>" 
                                 style="border-radius: 15px 15px 0 0;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">
                                        <?php if ($ticket['estado'] === 'facturada'): ?>
                                            <i class="bi bi-check-circle me-2"></i>FACTURADA
                                        <?php else: ?>
                                            <i class="bi bi-hand-index me-2"></i>RECIBIDA MANUALMENTE
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
                                            <i class="bi bi-file-pdf me-2"></i>Ver factura PDF
                                        </a>
                                        <a href="<?= site_url(htmlspecialchars($ticket['archivo_xml'])) ?>" 
                                           download 
                                           class="btn btn-outline-secondary btn-sm">
                                            <i class="bi bi-file-code me-2"></i>Descargar XML
                                        </a>
                                        <a href="visualizar-ticket?id=<?= $ticket['id'] ?>" 
                                           class="btn btn-outline-info btn-sm">
                                            <i class="bi bi-receipt me-2"></i>Ver ticket
                                        </a>
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
                                            <i class="bi bi-receipt me-2"></i>Ver ticket
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
                <p class="text-muted">Aún no tienes facturas recibidas. Revisa tus solicitudes pendientes en <a href="/lista-tickets">Mis tickets</a>.</p>
            </div>
        <?php endif; ?>
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
    </script>
</body>
</html>
