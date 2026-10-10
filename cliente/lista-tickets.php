<?php
require('assets/php/conexiones/conexionMySqli.php');
require_once('funciones/comercio_helpers.php');
$cliente_id = $_SESSION['id_usuario'];
$tieneEstadoCols = exzento_ticket_columnas_estado($conn);

$mesesAbrev = [
    1 => 'ene', 2 => 'feb', 3 => 'mar', 4 => 'abr',
    5 => 'may', 6 => 'jun', 7 => 'jul', 8 => 'ago',
    9 => 'sep', 10 => 'oct', 11 => 'nov', 12 => 'dic',
];

// Marcar ticket como factura recibida manualmente (sin contaminar la descripción)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'marcar_recibida') {
    $ticket_id = (int) ($_POST['ticket_id'] ?? 0);
    $canal = trim($_POST['canal'] ?? '');
    $otro_canal = trim($_POST['otro_canal'] ?? '');

    if ($ticket_id > 0 && $canal !== '') {
        $canalFinal = $canal;
        if ($canal === 'Otro' && $otro_canal !== '') {
            $canalFinal = $otro_canal;
        }

        $stmtGet = $conn->prepare('SELECT descripcion FROM ticket WHERE id = ? AND id_cliente = ? LIMIT 1');
        $descActual = '';
        if ($stmtGet) {
            $stmtGet->bind_param('is', $ticket_id, $cliente_id);
            $stmtGet->execute();
            $rowGet = $stmtGet->get_result()->fetch_assoc();
            $stmtGet->close();
            $descActual = (string) ($rowGet['descripcion'] ?? '');
        }
        $comercioLimpio = exzento_limpiar_nombre_comercio($descActual);
        $fechaNota = date('d/m/Y H:i');
        $nota = "Recibida manualmente el {$fechaNota} por: {$canalFinal}";
        $ok = false;

        if ($tieneEstadoCols) {
            $stmt = $conn->prepare(
                "UPDATE ticket
                 SET descripcion = ?,
                     estado_factura = 'recibida_manual',
                     canal_recepcion = ?,
                     fecha_recepcion_manual = NOW(),
                     nota_factura = ?
                 WHERE id = ? AND id_cliente = ?"
            );
            if ($stmt) {
                $stmt->bind_param('sssis', $comercioLimpio, $canalFinal, $nota, $ticket_id, $cliente_id);
                $ok = $stmt->execute() && $stmt->affected_rows >= 0;
                $stmt->close();
            }
        } else {
            // Legacy: conservar comercio limpio + metadata parseable (sin duplicar)
            $descNueva = $comercioLimpio !== ''
                ? ($comercioLimpio . ' - ' . $nota)
                : $nota;
            $stmt = $conn->prepare(
                'UPDATE ticket SET descripcion = ? WHERE id = ? AND id_cliente = ?'
            );
            if ($stmt) {
                $stmt->bind_param('sis', $descNueva, $ticket_id, $cliente_id);
                $ok = $stmt->execute() && $stmt->affected_rows > 0;
                $stmt->close();
            }
        }

        if ($ok) {
            $_SESSION['mensaje'] = "Ticket #{$ticket_id} marcado como factura recibida manualmente.";
        } else {
            $_SESSION['error'] = 'No se pudo marcar el ticket. Verifica que te pertenezca.';
        }
    } else {
        $_SESSION['error'] = 'Selecciona dónde recibiste la factura.';
    }

    $redir = '/lista-tickets';
    $qs = [];
    foreach (['filtro', 'cuenta', 'comercio', 'busqueda'] as $k) {
        if (!empty($_POST[$k])) {
            $qs[$k] = $_POST[$k];
        }
    }
    if ($qs) {
        $redir .= '?' . http_build_query($qs);
    }
    header('Location: ' . $redir);
    exit;
}

$filtro = $_GET['filtro'] ?? 'todos';
if (!in_array($filtro, ['todos', 'pendiente', 'recibida_manual'], true)) {
    $filtro = 'todos';
}

$busqueda = trim((string) ($_GET['busqueda'] ?? ''));
$comercioFiltro = trim((string) ($_GET['comercio'] ?? ''));
$cuentaParam = $_GET['cuenta'] ?? '';
$cuentaId = ($cuentaParam !== '' && ctype_digit((string) $cuentaParam)) ? (int) $cuentaParam : 0;

$cuentasFiscales = [];
$stmtCuentas = $conn->prepare(
    "SELECT id, razonSocial, rfc FROM datosFiscales WHERE id_usuario = ? AND estatus = 1 ORDER BY razonSocial ASC"
);
if ($stmtCuentas) {
    $stmtCuentas->bind_param('i', $cliente_id);
    $stmtCuentas->execute();
    $cuentasFiscales = $stmtCuentas->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtCuentas->close();
}
if ($cuentaId > 0) {
    $okCuenta = false;
    foreach ($cuentasFiscales as $c) {
        if ((int) $c['id'] === $cuentaId) {
            $okCuenta = true;
            break;
        }
    }
    if (!$okCuenta) {
        $cuentaId = 0;
    }
}

$comercios = [];
$stmtCom = $conn->prepare(
    "SELECT descripcion FROM ticket WHERE id_cliente = ? AND descripcion IS NOT NULL AND descripcion != ''"
);
if ($stmtCom) {
    $stmtCom->bind_param('s', $cliente_id);
    $stmtCom->execute();
    $resCom = $stmtCom->get_result();
    $vistos = [];
    while ($row = $resCom->fetch_assoc()) {
        $nombre = exzento_limpiar_nombre_comercio($row['descripcion'] ?? '');
        if ($nombre === '') {
            continue;
        }
        $key = mb_strtolower($nombre, 'UTF-8');
        if (isset($vistos[$key])) {
            continue;
        }
        $vistos[$key] = true;
        $comercios[] = $nombre;
    }
    $stmtCom->close();
    natcasesort($comercios);
    $comercios = array_values($comercios);
}

$sqlManual = exzento_sql_es_recibida_manual($conn);
$sqlPendiente = exzento_sql_es_pendiente($conn);

$filtroBase = ' WHERE t.id_cliente = ?';
$paramsBase = [$cliente_id];
$typesBase = 's';

if ($cuentaId > 0) {
    $filtroBase .= ' AND t.id_datos = ?';
    $paramsBase[] = $cuentaId;
    $typesBase .= 'i';
}
if ($comercioFiltro !== '') {
    $filtroBase .= " AND (t.descripcion = ? OR t.descripcion LIKE CONCAT(?, ' - Recibida manualmente%'))";
    $paramsBase[] = $comercioFiltro;
    $paramsBase[] = $comercioFiltro;
    $typesBase .= 'ss';
}
if ($busqueda !== '') {
    $filtroBase .= ' AND (t.descripcion LIKE ? OR t.numeroTicket LIKE ? OR df.razonSocial LIKE ? OR CAST(t.id AS CHAR) LIKE ?)';
    $like = '%' . $busqueda . '%';
    array_push($paramsBase, $like, $like, $like, $like);
    $typesBase .= 'ssss';
}

$conteos = ['todos' => 0, 'pendiente' => 0, 'recibida_manual' => 0];
$queryConteos = "SELECT
    COUNT(*) AS todos,
    SUM(CASE WHEN {$sqlPendiente} THEN 1 ELSE 0 END) AS pendiente,
    SUM(CASE WHEN {$sqlManual} AND NOT EXISTS (SELECT 1 FROM facturas f WHERE f.ticket_id = t.id) THEN 1 ELSE 0 END) AS recibida_manual
FROM ticket t
LEFT JOIN datosFiscales df ON t.id_datos = df.id
{$filtroBase}";

$stmtConteos = $conn->prepare($queryConteos);
if ($stmtConteos) {
    $stmtConteos->bind_param($typesBase, ...$paramsBase);
    $stmtConteos->execute();
    $rowC = $stmtConteos->get_result()->fetch_assoc();
    if ($rowC) {
        $conteos['todos'] = (int) ($rowC['todos'] ?? 0);
        $conteos['pendiente'] = (int) ($rowC['pendiente'] ?? 0);
        $conteos['recibida_manual'] = (int) ($rowC['recibida_manual'] ?? 0);
    }
    $stmtConteos->close();
}

$colsEstado = $tieneEstadoCols
    ? ', t.estado_factura, t.canal_recepcion, t.fecha_recepcion_manual, t.nota_factura'
    : '';

$query = "SELECT t.id, t.monto, t.fecha, t.numeroTicket, t.descripcion, t.id_datos, t.id_empresa,
                 df.razonSocial, df.rfc,
                 uc.clave AS clave_cfdi, uc.descripcion AS uso_cfdi_desc,
                 mp.nombre AS metodo_pago_nombre,
                 (SELECT COUNT(*) FROM facturas f WHERE f.ticket_id = t.id) AS num_facturas
                 {$colsEstado}
          FROM ticket t
          LEFT JOIN datosFiscales df ON t.id_datos = df.id
          LEFT JOIN usosCfdi uc ON t.usoCfdi = uc.id
          LEFT JOIN metodosPago mp ON t.metodoPago = mp.id
          {$filtroBase}";

$params = $paramsBase;
$types = $typesBase;

if ($filtro === 'pendiente') {
    $query .= " AND {$sqlPendiente}";
} elseif ($filtro === 'recibida_manual') {
    $query .= " AND {$sqlManual}
                AND NOT EXISTS (SELECT 1 FROM facturas f WHERE f.ticket_id = t.id)";
}

$query .= ' ORDER BY t.fecha DESC, t.id DESC';

$tickets = [];
$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $tickets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

if (!function_exists('tickets_url_filtros')) {
    function tickets_url_filtros(string $filtro, string $busqueda, string $comercio, int $cuentaId): string
    {
        $q = ['filtro' => $filtro];
        if ($busqueda !== '') {
            $q['busqueda'] = $busqueda;
        }
        if ($comercio !== '') {
            $q['comercio'] = $comercio;
        }
        if ($cuentaId > 0) {
            $q['cuenta'] = $cuentaId;
        }
        return '/lista-tickets?' . http_build_query($q);
    }
}
?>

<link href="/assets/css/tickets-cliente.css" rel="stylesheet">

<main class="tickets-page">
    <header class="tickets-header">
        <h1>Mis tickets</h1>
        <a href="/generar-ticket" class="tickets-btn-nuevo">
            <i class="fas fa-plus" aria-hidden="true"></i> Nuevo ticket
        </a>
    </header>

    <?php if (isset($_SESSION['mensaje'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['mensaje']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['mensaje']); endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['error']); endif; ?>

    <form class="tickets-filters" method="GET" action="/lista-tickets" id="ticketsFiltros">
        <input type="hidden" name="filtro" value="<?= htmlspecialchars($filtro) ?>">

        <div class="tickets-search">
            <input type="search"
                   class="form-control"
                   name="busqueda"
                   value="<?= htmlspecialchars($busqueda) ?>"
                   placeholder="Buscar comercio, folio…"
                   aria-label="Buscar">
        </div>

        <div class="tickets-selects">
            <select name="comercio" class="form-select" aria-label="Comercio" onchange="this.form.submit()">
                <option value="">Comercio</option>
                <?php foreach ($comercios as $nombreComercio): ?>
                    <option value="<?= htmlspecialchars($nombreComercio) ?>" <?= $comercioFiltro === $nombreComercio ? 'selected' : '' ?>>
                        <?= htmlspecialchars($nombreComercio) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="cuenta" class="form-select" aria-label="Cuenta fiscal" onchange="this.form.submit()">
                <option value="">Cuenta</option>
                <?php foreach ($cuentasFiscales as $cuenta): ?>
                    <option value="<?= (int) $cuenta['id'] ?>" <?= $cuentaId === (int) $cuenta['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cuenta['razonSocial']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="tickets-chips" role="tablist" aria-label="Filtrar por estado">
            <a href="<?= htmlspecialchars(tickets_url_filtros('todos', $busqueda, $comercioFiltro, $cuentaId)) ?>"
               class="tickets-chip<?= $filtro === 'todos' ? ' active' : '' ?>">
                Todos
                <?php if ($conteos['todos'] > 0): ?><span class="tickets-chip-count"><?= $conteos['todos'] ?></span><?php endif; ?>
            </a>
            <a href="<?= htmlspecialchars(tickets_url_filtros('pendiente', $busqueda, $comercioFiltro, $cuentaId)) ?>"
               class="tickets-chip<?= $filtro === 'pendiente' ? ' active' : '' ?>">
                <i class="fas fa-clock" aria-hidden="true"></i>
                Pendiente de facturación
                <?php if ($conteos['pendiente'] > 0): ?><span class="tickets-chip-count"><?= $conteos['pendiente'] ?></span><?php endif; ?>
            </a>
            <a href="<?= htmlspecialchars(tickets_url_filtros('recibida_manual', $busqueda, $comercioFiltro, $cuentaId)) ?>"
               class="tickets-chip<?= $filtro === 'recibida_manual' ? ' active' : '' ?>">
                <i class="fas fa-envelope" aria-hidden="true"></i>
                Recibida manualmente
                <?php if ($conteos['recibida_manual'] > 0): ?><span class="tickets-chip-count"><?= $conteos['recibida_manual'] ?></span><?php endif; ?>
            </a>
        </div>
    </form>

    <?php if (!empty($tickets)): ?>
        <div class="tickets-list">
            <?php foreach ($tickets as $ticket): ?>
                <?php
                $parsed = exzento_parse_recepcion_manual($ticket['descripcion'] ?? '', $ticket);
                $comercio = $parsed['comercio'];
                if ($comercio === '') {
                    $comercio = $ticket['numeroTicket']
                        ? ('Ticket ' . $ticket['numeroTicket'])
                        : ('Ticket #' . $ticket['id']);
                }
                $cuentaFiscal = trim((string) ($ticket['razonSocial'] ?? ''));
                $estado = exzento_ticket_estado($ticket);
                $ts = strtotime((string) $ticket['fecha']);
                $fechaHora = $ts ? date('d/m/Y · H:i', $ts) : '—';
                $via = '';
                if ($estado === 'recibida_manual' && !empty($parsed['manual']['canal'])) {
                    $via = 'vía ' . exzento_canal_corto($parsed['manual']['canal']);
                }
                $usoCfdi = trim(
                    (($ticket['clave_cfdi'] ?? '') ? ($ticket['clave_cfdi'] . ' - ') : '')
                    . ($ticket['uso_cfdi_desc'] ?? '')
                );
                $collapseId = 'ticket-detalle-' . (int) $ticket['id'];
                ?>
                <article class="ticket-card" data-ticket-card>
                    <button type="button"
                            class="ticket-card-main"
                            aria-expanded="false"
                            aria-controls="<?= $collapseId ?>"
                            data-ticket-toggle>
                        <div class="ticket-folio" aria-hidden="true">
                            <span class="ticket-folio-label">TICKET</span>
                            <span class="ticket-folio-num">#<?= (int) $ticket['id'] ?></span>
                        </div>

                        <div class="ticket-body">
                            <div class="ticket-row">
                                <span class="ticket-label">Comercio:</span>
                                <span class="ticket-comercio"><?= htmlspecialchars($comercio) ?></span>
                            </div>
                            <?php if ($cuentaFiscal !== ''): ?>
                            <div class="ticket-row">
                                <span class="ticket-label">Cuenta fiscal:</span>
                                <span class="ticket-cuenta"><?= htmlspecialchars($cuentaFiscal) ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="ticket-fecha">
                                <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                                <?= htmlspecialchars($fechaHora) ?>
                                <?php if ($via !== ''): ?>
                                    <span class="ticket-via">· <?= htmlspecialchars($via) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="ticket-side">
                            <div class="ticket-monto">$<?= number_format((float) $ticket['monto'], 2) ?></div>
                            <?php if ($estado === 'facturada'): ?>
                                <span class="ticket-badge ticket-badge--done">
                                    <i class="fas fa-check" aria-hidden="true"></i> Facturada
                                </span>
                            <?php elseif ($estado === 'recibida_manual'): ?>
                                <span class="ticket-badge ticket-badge--manual">
                                    <i class="fas fa-envelope" aria-hidden="true"></i> Recibida manualmente
                                </span>
                            <?php else: ?>
                                <span class="ticket-badge ticket-badge--pending">
                                    <i class="fas fa-clock" aria-hidden="true"></i> Pendiente
                                </span>
                            <?php endif; ?>
                            <i class="fas fa-chevron-down ticket-chevron" aria-hidden="true"></i>
                        </div>
                    </button>

                    <div class="ticket-detail" id="<?= $collapseId ?>" hidden>
                        <dl class="ticket-detail-grid">
                            <div class="ticket-detail-item">
                                <dt>RFC</dt>
                                <dd><?= htmlspecialchars($ticket['rfc'] ?: '—') ?></dd>
                            </div>
                            <div class="ticket-detail-item">
                                <dt>Uso CFDI</dt>
                                <dd><?= htmlspecialchars($usoCfdi !== '' ? $usoCfdi : '—') ?></dd>
                            </div>
                            <div class="ticket-detail-item">
                                <dt>Método de pago</dt>
                                <dd><?= htmlspecialchars($ticket['metodo_pago_nombre'] ?: '—') ?></dd>
                            </div>
                            <?php if ($estado === 'recibida_manual' && $parsed['manual']): ?>
                            <div class="ticket-detail-item">
                                <dt>Marcada como recibida</dt>
                                <dd>
                                    <?php
                                    $cuando = $parsed['manual']['cuando'];
                                    $canalManual = $parsed['manual']['canal'];
                                    $tsRec = strtotime(str_replace('/', '-', $cuando));
                                    if ($tsRec) {
                                        echo htmlspecialchars(
                                            ((int) date('j', $tsRec)) . ' '
                                            . ($mesesAbrev[(int) date('n', $tsRec)] ?? '')
                                            . ' · ' . $canalManual
                                        );
                                    } else {
                                        echo htmlspecialchars(trim($cuando . ' · ' . $canalManual));
                                    }
                                    ?>
                                </dd>
                            </div>
                            <?php endif; ?>
                        </dl>

                        <div class="ticket-actions">
                            <?php if ($estado === 'pendiente'): ?>
                                <a href="/visualizar-ticket?id=<?= (int) $ticket['id'] ?>"
                                   class="btn btn-outline-primary btn-sm btn-reenviar">
                                    <i class="fas fa-paper-plane me-1"></i>Reenviar solicitud
                                </a>
                                <button type="button"
                                        class="btn btn-outline-success btn-sm btn-recibida"
                                        onclick="mostrarModalRecibida(<?= (int) $ticket['id'] ?>)">
                                    <i class="fas fa-check-circle me-1"></i>Recibí factura
                                </button>
                            <?php elseif ($estado === 'recibida_manual'): ?>
                                <a href="/facturas?filtro=recibidas_manual"
                                   class="btn btn-outline-info btn-sm btn-reenviar">
                                    <i class="fas fa-receipt me-1"></i>Ver en Facturas
                                </a>
                                <a href="/visualizar-ticket?id=<?= (int) $ticket['id'] ?>"
                                   class="btn btn-outline-primary btn-sm btn-recibida">
                                    <i class="fas fa-eye me-1"></i>Ver ticket
                                </a>
                            <?php else: ?>
                                <a href="/facturas?filtro=facturadas"
                                   class="btn btn-outline-success btn-sm btn-reenviar">
                                    <i class="fas fa-file-invoice me-1"></i>Ver factura
                                </a>
                                <a href="/visualizar-ticket?id=<?= (int) $ticket['id'] ?>"
                                   class="btn btn-outline-primary btn-sm btn-recibida">
                                    <i class="fas fa-eye me-1"></i>Ver ticket
                                </a>
                            <?php endif; ?>

                            <form method="POST"
                                  action="/funciones/eliminar_ticket.php"
                                  class="d-inline"
                                  onsubmit="return confirm('¿Eliminar el ticket #<?= (int) $ticket['id'] ?>? Esta acción no se puede deshacer.');">
                                <input type="hidden" name="id_ticket" value="<?= (int) $ticket['id'] ?>">
                                <input type="hidden" name="redirect" value="/lista-tickets">
                                <button type="submit" class="ticket-btn-eliminar" title="Eliminar">
                                    <i class="fas fa-trash-alt" aria-hidden="true"></i>
                                </button>
                            </form>

                            <?php if ($estado === 'pendiente'): ?>
                            <p class="ticket-actions-hint">
                                Reenviar abre la pantalla de envío: ahí eliges WhatsApp, correo o QR — puede ser distinto al canal original.
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="tickets-empty">
            <i class="fas fa-ticket-alt" aria-hidden="true"></i>
            <h2>No se encontraron tickets</h2>
            <p>
                <?= ($busqueda !== '' || $comercioFiltro !== '' || $cuentaId > 0 || $filtro !== 'todos')
                    ? 'Prueba otros filtros o '
                    : 'Comienza creando tu primer ticket. ' ?>
                <a href="/generar-ticket">Nuevo ticket</a>
            </p>
        </div>
    <?php endif; ?>
</main>

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
                    <input type="hidden" name="filtro" value="<?= htmlspecialchars($filtro) ?>">
                    <input type="hidden" name="busqueda" value="<?= htmlspecialchars($busqueda) ?>">
                    <input type="hidden" name="comercio" value="<?= htmlspecialchars($comercioFiltro) ?>">
                    <input type="hidden" name="cuenta" value="<?= $cuentaId > 0 ? (int) $cuentaId : '' ?>">

                    <p class="text-muted small">
                        Úsalo cuando el comercio te envió la factura por otro medio y no la subió a Exzento.
                        El nombre del comercio se conserva limpio; la recepción queda como estado.
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

document.querySelectorAll('[data-ticket-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var card = btn.closest('[data-ticket-card]');
        if (!card) return;
        var detail = card.querySelector('.ticket-detail');
        var open = card.classList.toggle('open');
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (detail) {
            if (open) detail.removeAttribute('hidden');
            else detail.setAttribute('hidden', '');
        }
    });
});

document.getElementById('ticketsFiltros').addEventListener('submit', function () {
    // chips ya navegan; el search usa submit nativo
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
<?php
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>
