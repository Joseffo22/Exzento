<?php
require('assets/php/conexiones/conexionMySqli.php');
$cliente_id = $_SESSION['id_usuario'];

// Marcar ticket como factura recibida manualmente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'marcar_recibida') {
    $ticket_id = (int) ($_POST['ticket_id'] ?? 0);
    $canal = trim($_POST['canal'] ?? '');
    $otro_canal = trim($_POST['otro_canal'] ?? '');

    if ($ticket_id > 0 && $canal !== '') {
        $nota = 'Recibida manualmente el ' . date('d/m/Y H:i') . ' por: ' . $canal;
        if ($canal === 'Otro' && $otro_canal !== '') {
            $nota .= ' - ' . $otro_canal;
        }

        $stmt = $conn->prepare("UPDATE ticket SET descripcion = CONCAT(COALESCE(descripcion, ''), ' - ', ?) WHERE id = ? AND id_cliente = ?");
        if ($stmt) {
            $stmt->bind_param('sis', $nota, $ticket_id, $cliente_id);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $_SESSION['mensaje'] = "Ticket #{$ticket_id} marcado como factura recibida manualmente.";
            } else {
                $_SESSION['error'] = 'No se pudo marcar el ticket. Verifica que te pertenezca.';
            }
            $stmt->close();
        } else {
            $_SESSION['error'] = 'Error al preparar la actualización.';
        }
    } else {
        $_SESSION['error'] = 'Selecciona dónde recibiste la factura.';
    }

    header('Location: /lista-tickets');
    exit;
}

// Obtener parámetros de filtro
$busqueda = $_GET['busqueda'] ?? '';
$empresa = $_GET['empresa'] ?? '';
$vista = $_GET['vista'] ?? 'mosaico'; // mosaico o lista

// Obtener lista de empresas para el filtro
$query_empresas = "SELECT DISTINCT id_empresa FROM ticket WHERE id_cliente = ? AND id_empresa IS NOT NULL";
$stmt_empresas = $conn->prepare($query_empresas);
$empresas = [];
if ($stmt_empresas) {
    $stmt_empresas->bind_param("s", $cliente_id);
    $stmt_empresas->execute();
    $result_empresas = $stmt_empresas->get_result();
    while ($row = $result_empresas->fetch_assoc()) {
        $empresas[] = $row['id_empresa'];
    }
    $stmt_empresas->close();
}
?>

<body class="bg-light">

<div class="container py-5">
    <div class="row mb-4">
        <div class="col">
            <h2 class="mb-3">
                <i class="fas fa-ticket-alt me-2"></i>Mis Tickets
            </h2>
            <p class="text-muted">Aquí puedes ver todos tus tickets de facturación</p>
        </div>
        <div class="col-auto">
            <a href="/generar-ticket" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Nuevo Ticket
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['mensaje'])): ?>
    <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
        <?= htmlspecialchars($_SESSION['mensaje']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['mensaje']); endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
        <?= htmlspecialchars($_SESSION['error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['error']); endif; ?>

    <!-- Filtros y controles de vista -->
    <div class="row mb-4">
        <div class="col-md-8">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" 
                           class="form-control" 
                           name="busqueda" 
                           placeholder="Buscar por descripción, número..." 
                           value="<?= htmlspecialchars($busqueda) ?>">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="empresa">
                        <option value="">Todas las empresas</option>
                        <option value="sin_registrar" <?= $empresa === 'sin_registrar' ? 'selected' : '' ?>>
                            Sin empresa registrada
                        </option>
                        <?php foreach ($empresas as $emp_id): ?>
                            <option value="<?= $emp_id ?>" <?= $empresa == $emp_id ? 'selected' : '' ?>>
                                Empresa <?= $emp_id ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="?vista=<?= $vista === 'mosaico' ? 'lista' : 'mosaico' ?>&busqueda=<?= urlencode($busqueda) ?>&empresa=<?= urlencode($empresa) ?>" 
                       class="btn btn-outline-secondary w-100">
                        <i class="fas fa-<?= $vista === 'mosaico' ? 'list' : 'th-large' ?>"></i>
                        <?= $vista === 'mosaico' ? 'Lista' : 'Mosaico' ?>
                    </a>
                </div>
            </form>
        </div>
        <div class="col-md-4 text-end">
            <a href="?" class="btn btn-outline-danger btn-sm">
                <i class="fas fa-times"></i> Limpiar filtros
            </a>
        </div>
    </div>

<?php
if ($cliente_id !== '') {
    // Construir la consulta base
    $query_base = "SELECT t.id, t.monto, t.fecha, t.numeroTicket, t.usoCfdi, t.metodoPago, t.descripcion, t.id_empresa,
                           df.razonSocial, df.rfc, uc.descripcion as uso_cfdi_desc,
                           mp.nombre as metodo_pago_nombre,
                           (SELECT COUNT(*) FROM facturas f WHERE f.ticket_id = t.id) as num_facturas
                    FROM ticket t
                    LEFT JOIN datosFiscales df ON t.id_datos = df.id
                    LEFT JOIN usosCfdi uc ON t.usoCfdi = uc.id
                    LEFT JOIN metodosPago mp ON t.metodoPago = mp.id
                    WHERE t.id_cliente = ?";
    
    $params = [$cliente_id];
    $types = "s";
    
    // Agregar filtros
    if (!empty($busqueda)) {
        $query_base .= " AND (t.descripcion LIKE ? OR t.numeroTicket LIKE ? OR df.razonSocial LIKE ?)";
        $busqueda_param = "%$busqueda%";
        $params[] = $busqueda_param;
        $params[] = $busqueda_param;
        $params[] = $busqueda_param;
        $types .= "sss";
    }
    
    if (!empty($empresa)) {
        if ($empresa === 'sin_registrar') {
            $query_base .= " AND t.id_empresa IS NULL";
        } else {
            $query_base .= " AND t.id_empresa = ?";
            $params[] = $empresa;
            $types .= "s";
        }
    }
    
    $query_base .= " ORDER BY t.fecha DESC";
    
    $stmt = $conn->prepare($query_base);
    if ($stmt === false) {
        echo '<div class="alert alert-danger">Error en la consulta SQL: ' . $conn->error . '</div>';
    } else {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows > 0) { ?>
            <!-- Vista Mosaico -->
            <?php if ($vista === 'mosaico'): ?>
            <div class="row">
                <?php while ($ticket = $res->fetch_assoc()) { ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">
                                        <i class="fas fa-ticket-alt me-1"></i>Ticket #<?= $ticket['id'] ?>
                                    </h6>
                                    <span class="badge bg-light text-dark">
                                        <?= $ticket['num_facturas'] ?> factura<?= $ticket['num_facturas'] != 1 ? 's' : '' ?>
                                    </span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <h5 class="card-title text-primary">$<?= number_format($ticket['monto'], 2) ?></h5>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?= date('d/m/Y H:i', strtotime($ticket['fecha'])) ?>
                                    </small>
                                </div>
                                
                                <?php if ($ticket['descripcion']) { ?>
                                    <div class="mb-2">
                                        <small class="text-muted">Descripción:</small>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($ticket['descripcion']) ?></div>
                                    </div>
                                <?php } ?>
                                
                                <?php if ($ticket['numeroTicket']) { ?>
                                    <div class="mb-2">
                                        <small class="text-muted">Número de Ticket:</small>
                                        <div class="fw-bold"><?= htmlspecialchars($ticket['numeroTicket']) ?></div>
                                    </div>
                                <?php } ?>
                                
                                <div class="mb-2">
                                    <small class="text-muted">Datos Fiscales:</small>
                                    <div class="fw-bold"><?= htmlspecialchars($ticket['razonSocial']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($ticket['rfc']) ?></small>
                                </div>
                                
                                <div class="mb-2">
                                    <small class="text-muted">Uso CFDI:</small>
                                    <div class="fw-bold"><?= htmlspecialchars($ticket['uso_cfdi_desc']) ?></div>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted">Método de Pago:</small>
                                    <div class="fw-bold"><?= htmlspecialchars($ticket['metodo_pago_nombre']) ?></div>
                                </div>
                                
                                <?php if ($ticket['id_empresa']) { ?>
                                    <div class="mb-2">
                                        <small class="text-muted">Empresa:</small>
                                        <div class="fw-bold text-info"><?= htmlspecialchars($ticket['id_empresa']) ?></div>
                                    </div>
                                <?php } else { ?>
                                    <div class="mb-2">
                                        <small class="text-muted">Empresa:</small>
                                        <div class="fw-bold text-warning">Sin empresa registrada</div>
                                    </div>
                                <?php } ?>
                            </div>
                            <div class="card-footer bg-transparent">
                                <div class="d-grid gap-2">
                                    <a href="/visualizar-ticket?id=<?= $ticket['id'] ?>" 
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-eye me-1"></i>Ver Detalles
                                    </a>
                                    <?php
                                    $esRecibidaManual = stripos((string) ($ticket['descripcion'] ?? ''), 'Recibida manualmente') !== false;
                                    $esPendiente = (int) $ticket['num_facturas'] === 0 && !$esRecibidaManual;
                                    ?>
                                    <?php if ($ticket['num_facturas'] > 0) { ?>
                                        <a href="/facturas?filtro=facturadas" 
                                           class="btn btn-outline-success btn-sm">
                                            <i class="fas fa-file-invoice me-1"></i>Ver Facturas
                                        </a>
                                    <?php } elseif ($esRecibidaManual) { ?>
                                        <a href="/facturas?filtro=recibidas_manual" 
                                           class="btn btn-outline-info btn-sm">
                                            <i class="fas fa-hand-index me-1"></i>Recibida manualmente
                                        </a>
                                    <?php } else { ?>
                                        <button class="btn btn-outline-warning btn-sm" disabled>
                                            <i class="fas fa-clock me-1"></i>Pendiente de Facturación
                                        </button>
                                        <button type="button"
                                                class="btn btn-outline-success btn-sm"
                                                onclick="mostrarModalRecibida(<?= (int) $ticket['id'] ?>)">
                                            <i class="fas fa-check-circle me-1"></i>Recibí esta factura
                                        </button>
                                    <?php } ?>
                                    <form method="POST"
                                          action="/funciones/eliminar_ticket.php"
                                          onsubmit="return confirm('¿Eliminar el ticket #<?= (int) $ticket['id'] ?>? Esta acción no se puede deshacer.');">
                                        <input type="hidden" name="id_ticket" value="<?= (int) $ticket['id'] ?>">
                                        <input type="hidden" name="redirect" value="/lista-tickets">
                                        <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                                            <i class="fas fa-trash-alt me-1"></i>Eliminar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
            
            <!-- Vista Lista -->
            <?php else: ?>
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-primary">
                                <tr>
                                    <th>ID</th>
                                    <th>Monto</th>
                                    <th>Descripción</th>
                                    <th>Fecha</th>
                                    <th>Empresa</th>
                                    <th>Facturas</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $res->data_seek(0); // Resetear el puntero del resultado
                                while ($ticket = $res->fetch_assoc()) { ?>
                                <tr>
                                    <td>
                                        <strong>#<?= $ticket['id'] ?></strong>
                                        <?php if ($ticket['numeroTicket']): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($ticket['numeroTicket']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong class="text-primary">$<?= number_format($ticket['monto'], 2) ?></strong>
                                    </td>
                                    <td>
                                        <?php if ($ticket['descripcion']): ?>
                                            <div class="fw-bold"><?= htmlspecialchars($ticket['descripcion']) ?></div>
                                        <?php endif; ?>
                                        <small class="text-muted"><?= htmlspecialchars($ticket['razonSocial']) ?></small>
                                    </td>
                                    <td>
                                        <div><?= date('d/m/Y', strtotime($ticket['fecha'])) ?></div>
                                        <small class="text-muted"><?= date('H:i', strtotime($ticket['fecha'])) ?></small>
                                    </td>
                                    <td>
                                        <?php if ($ticket['id_empresa']): ?>
                                            <span class="badge bg-info"><?= htmlspecialchars($ticket['id_empresa']) ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Sin empresa</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $ticket['num_facturas'] > 0 ? 'success' : 'secondary' ?>">
                                            <?= $ticket['num_facturas'] ?> factura<?= $ticket['num_facturas'] != 1 ? 's' : '' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="/visualizar-ticket?id=<?= $ticket['id'] ?>" 
                                               class="btn btn-outline-primary" title="Ver Detalles">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php
                                            $esRecibidaManual = stripos((string) ($ticket['descripcion'] ?? ''), 'Recibida manualmente') !== false;
                                            $esPendiente = (int) $ticket['num_facturas'] === 0 && !$esRecibidaManual;
                                            ?>
                                            <?php if ($ticket['num_facturas'] > 0) { ?>
                                                <a href="/facturas?filtro=facturadas" 
                                                   class="btn btn-outline-success" title="Ver Facturas">
                                                    <i class="fas fa-file-invoice"></i>
                                                </a>
                                            <?php } elseif ($esPendiente) { ?>
                                                <button type="button"
                                                        class="btn btn-outline-success"
                                                        title="Recibí esta factura"
                                                        onclick="mostrarModalRecibida(<?= (int) $ticket['id'] ?>)">
                                                    <i class="fas fa-check-circle"></i>
                                                </button>
                                            <?php } ?>
                                            <form method="POST"
                                                  action="/funciones/eliminar_ticket.php"
                                                  class="d-inline"
                                                  onsubmit="return confirm('¿Eliminar el ticket #<?= (int) $ticket['id'] ?>? Esta acción no se puede deshacer.');">
                                                <input type="hidden" name="id_ticket" value="<?= (int) $ticket['id'] ?>">
                                                <input type="hidden" name="redirect" value="/lista-tickets">
                                                <button type="submit" class="btn btn-outline-danger" title="Eliminar">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Resumen estadístico -->
            <div class="row mt-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <h4 class="mb-0"><?= $res->num_rows ?></h4>
                            <small>Tickets encontrados</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <?php
                            $stmt->execute();
                            $res2 = $stmt->get_result();
                            $total_facturas = 0;
                            while ($t = $res2->fetch_assoc()) {
                                $total_facturas += $t['num_facturas'];
                            }
                            ?>
                            <h4 class="mb-0"><?= $total_facturas ?></h4>
                            <small>Total de Facturas</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center">
                            <?php
                            $stmt->execute();
                            $res3 = $stmt->get_result();
                            $total_monto = 0;
                            while ($t = $res3->fetch_assoc()) {
                                $total_monto += $t['monto'];
                            }
                            ?>
                            <h4 class="mb-0">$<?= number_format($total_monto, 2) ?></h4>
                            <small>Monto Total</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-dark">
                        <div class="card-body text-center">
                            <?php
                            $stmt->execute();
                            $res4 = $stmt->get_result();
                            $sin_empresa = 0;
                            while ($t = $res4->fetch_assoc()) {
                                if (!$t['id_empresa']) $sin_empresa++;
                            }
                            ?>
                            <h4 class="mb-0"><?= $sin_empresa ?></h4>
                            <small>Sin Empresa</small>
                        </div>
                    </div>
                </div>
            </div>
            
        <?php
        } else {
            echo '<div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>
                    <h5>No se encontraron tickets</h5>
                    <p>' . (!empty($busqueda) || !empty($empresa) ? 'Intenta con otros filtros de búsqueda' : 'Comienza creando tu primer ticket de facturación') . '</p>
                    ' . (!empty($busqueda) || !empty($empresa) ? '<a href="?" class="btn btn-primary">Limpiar filtros</a>' : '<a href="/generar-ticket" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Crear Primer Ticket</a>') . '
                  </div>';
        }
        $stmt->close();
    }
}
?>
</div>

<!-- Modal: marcar factura recibida manualmente -->
<div class="modal fade" id="modalRecibida" tabindex="-1" aria-labelledby="modalRecibidaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 15px;">
            <div class="modal-header">
                <h5 class="modal-title" id="modalRecibidaLabel">
                    <i class="fas fa-check-circle me-2 text-success"></i>
                    ¿Dónde recibiste esta factura?
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form method="POST" action="/lista-tickets">
                <div class="modal-body">
                    <input type="hidden" name="action" value="marcar_recibida">
                    <input type="hidden" name="ticket_id" id="ticket_id_modal">

                    <p class="text-muted small">
                        Úsalo cuando el comercio te envió la factura por otro medio y no la subió a Exzento.
                    </p>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="canal" id="canal_email" value="Correo electrónico" required>
                            <label class="form-check-label" for="canal_email">
                                <i class="fas fa-envelope me-2"></i>Correo electrónico
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="canal" id="canal_whatsapp" value="WhatsApp" required>
                            <label class="form-check-label" for="canal_whatsapp">
                                <i class="fab fa-whatsapp me-2"></i>WhatsApp
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="canal" id="canal_fisico" value="Físicamente" required>
                            <label class="form-check-label" for="canal_fisico">
                                <i class="fas fa-file-alt me-2"></i>Físicamente
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="canal" id="canal_otro" value="Otro" required>
                            <label class="form-check-label" for="canal_otro">
                                <i class="fas fa-ellipsis-h me-2"></i>Otro
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
                        <i class="fas fa-check-circle me-2"></i>Marcar como recibida
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function mostrarModalRecibida(ticketId) {
    document.getElementById('ticket_id_modal').value = ticketId;
    new bootstrap.Modal(document.getElementById('modalRecibida')).show();
}

document.querySelectorAll('input[name="canal"]').forEach(function (radio) {
    radio.addEventListener('change', function () {
        const otroDiv = document.getElementById('otro_canal_div');
        const otroInput = document.getElementById('otro_canal');
        if (this.value === 'Otro') {
            otroDiv.style.display = 'block';
            otroInput.required = true;
        } else {
            otroDiv.style.display = 'none';
            otroInput.required = false;
            otroInput.value = '';
        }
    });
});

document.addEventListener('DOMContentLoaded', function () {
    const params = new URLSearchParams(window.location.search);
    const marcarId = params.get('marcar');
    if (marcarId) {
        mostrarModalRecibida(marcarId);
        params.delete('marcar');
        const clean = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        window.history.replaceState({}, '', clean);
    }
});
</script>

</body>
