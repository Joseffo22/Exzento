<?php
require_once('assets/php/conexiones/conexionMySqli.php');
require_once('funciones/mensaje_whatsapp_factura.php');
require_once('funciones/comercio_helpers.php');

$cliente_id = $_SESSION['id_usuario'];
$sqlManual = exzento_sql_es_recibida_manual($conn);
$sqlPendiente = exzento_sql_es_pendiente($conn);
$sqlFacturado = "(EXISTS (SELECT 1 FROM facturas f WHERE f.ticket_id = t.id) OR {$sqlManual})";

$mesesEs = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
];
$mesesAbrev = [
    1 => 'ene', 2 => 'feb', 3 => 'mar', 4 => 'abr',
    5 => 'may', 6 => 'jun', 7 => 'jul', 8 => 'ago',
    9 => 'sep', 10 => 'oct', 11 => 'nov', 12 => 'dic',
];

$mesParam = $_GET['mes'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $mesParam)) {
    $mesParam = date('Y-m');
}
[$anioSel, $mesSel] = array_map('intval', explode('-', $mesParam));
if ($mesSel < 1 || $mesSel > 12 || $anioSel < 2000) {
    $anioSel = (int) date('Y');
    $mesSel = (int) date('n');
    $mesParam = sprintf('%04d-%02d', $anioSel, $mesSel);
}

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
    $cuentaValida = false;
    foreach ($cuentasFiscales as $c) {
        if ((int) $c['id'] === $cuentaId) {
            $cuentaValida = true;
            break;
        }
    }
    if (!$cuentaValida) {
        $cuentaId = 0;
    }
}

$opcionesMes = [];
$cursor = new DateTime('first day of this month');
for ($i = 0; $i < 18; $i++) {
    $y = (int) $cursor->format('Y');
    $m = (int) $cursor->format('n');
    $valor = $cursor->format('Y-m');
    $opcionesMes[] = [
        'valor' => $valor,
        'etiqueta' => ($mesesEs[$m] ?? $cursor->format('F')) . ' ' . $y,
    ];
    $cursor->modify('-1 month');
}

$filtroCuentaSql = '';
$paramsBase = [$cliente_id, $mesSel, $anioSel];
$typesBase = 'sii';
if ($cuentaId > 0) {
    $filtroCuentaSql = ' AND t.id_datos = ?';
    $paramsBase[] = $cuentaId;
    $typesBase .= 'i';
}

$queryTotales = "SELECT
    COALESCE(SUM(CAST(t.monto AS DECIMAL(12,2))), 0) AS total_monto,
    COUNT(*) AS total_tickets,
    COALESCE(SUM(CASE
        WHEN {$sqlFacturado}
        THEN CAST(t.monto AS DECIMAL(12,2)) ELSE 0 END), 0) AS monto_facturado,
    COALESCE(SUM(CASE
        WHEN {$sqlPendiente}
        THEN CAST(t.monto AS DECIMAL(12,2)) ELSE 0 END), 0) AS monto_pendiente,
    SUM(CASE WHEN {$sqlFacturado} THEN 1 ELSE 0 END) AS count_facturado,
    SUM(CASE WHEN {$sqlPendiente} THEN 1 ELSE 0 END) AS count_pendiente
FROM ticket t
WHERE t.id_cliente = ?
  AND MONTH(t.fecha) = ?
  AND YEAR(t.fecha) = ?
  {$filtroCuentaSql}";

$totales = [
    'total_monto' => 0,
    'total_tickets' => 0,
    'monto_facturado' => 0,
    'monto_pendiente' => 0,
    'count_facturado' => 0,
    'count_pendiente' => 0,
];

$stmtTotales = $conn->prepare($queryTotales);
if ($stmtTotales) {
    $stmtTotales->bind_param($typesBase, ...$paramsBase);
    $stmtTotales->execute();
    $row = $stmtTotales->get_result()->fetch_assoc();
    if ($row) {
        $totales = $row;
    }
    $stmtTotales->close();
}

$totalMonto = (float) $totales['total_monto'];
$montoFacturado = (float) $totales['monto_facturado'];
$montoPendiente = (float) $totales['monto_pendiente'];
$countFacturado = (int) ($totales['count_facturado'] ?? 0);
$countPendiente = (int) ($totales['count_pendiente'] ?? 0);
$totalTickets = (int) ($totales['total_tickets'] ?? 0);
$pctFacturado = $totalMonto > 0 ? (int) round(($montoFacturado / $totalMonto) * 100) : 0;

$finMes = sprintf('%04d-%02d-%02d', $anioSel, $mesSel, (int) date('t', mktime(0, 0, 0, $mesSel, 1, $anioSel)));
$hoy = date('Y-m-d');
$diasParaCierre = (int) floor((strtotime($finMes) - strtotime($hoy)) / 86400);
$nombreMesSel = $mesesEs[$mesSel] ?? '';
$nombreMesLower = mb_strtolower($nombreMesSel, 'UTF-8');

$queryPendientes = "SELECT
    t.id, t.monto, t.fecha, t.descripcion, t.numeroTicket, t.id_datos,
    df.razonSocial, df.rfc, df.correo, df.telefono, df.calle, df.colonia,
    df.codigoPostal, df.municipio, df.estado, df.constancia,
    rf.descripcion AS regimen_fiscal,
    uc.clave AS clave_cfdi, uc.descripcion AS descripcion_cfdi,
    mp.nombre AS metodopago,
    e.nombre AS empresa_nombre
FROM ticket t
LEFT JOIN datosFiscales df ON t.id_datos = df.id
LEFT JOIN regimenesFiscales rf ON df.regimen = rf.id
LEFT JOIN usosCfdi uc ON t.usoCfdi = uc.id
LEFT JOIN metodosPago mp ON t.metodoPago = mp.id
LEFT JOIN empresas e ON t.id_empresa = e.id
WHERE t.id_cliente = ?
  AND MONTH(t.fecha) = ?
  AND YEAR(t.fecha) = ?
  AND {$sqlPendiente}
  {$filtroCuentaSql}
ORDER BY t.fecha ASC, t.id ASC";

$pendientes = [];
$stmtPend = $conn->prepare($queryPendientes);
if ($stmtPend) {
    $stmtPend->bind_param($typesBase, ...$paramsBase);
    $stmtPend->execute();
    $pendientes = $stmtPend->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtPend->close();
}

$conn->close();
?>

<link href="/assets/css/resumen-cliente.css" rel="stylesheet">

<main class="resumen-page">
    <form class="resumen-filters" method="GET" action="/resumen" id="resumenFiltros">
        <div class="resumen-filter">
            <select name="mes" id="filtroMes" class="form-select" aria-label="Mes" onchange="this.form.submit()">
                <?php foreach ($opcionesMes as $op): ?>
                    <option value="<?= htmlspecialchars($op['valor']) ?>" <?= $op['valor'] === $mesParam ? 'selected' : '' ?>>
                        <?= htmlspecialchars($op['etiqueta']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="resumen-filter">
            <select name="cuenta" id="filtroCuenta" class="form-select" aria-label="Cuenta fiscal" onchange="this.form.submit()">
                <option value="" <?= $cuentaId === 0 ? 'selected' : '' ?>>Todas las cuentas</option>
                <?php foreach ($cuentasFiscales as $cuenta): ?>
                    <option value="<?= (int) $cuenta['id'] ?>" <?= $cuentaId === (int) $cuenta['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cuenta['razonSocial']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <section class="resumen-hero" aria-labelledby="gastos-titulo">
        <p class="resumen-hero-label" id="gastos-titulo">Gastos registrados en <?= htmlspecialchars($nombreMesLower) ?></p>
        <div class="resumen-hero-amount">$<?= number_format($totalMonto, 2) ?></div>
        <p class="resumen-hero-sub">
            <?= $totalTickets ?> ticket<?= $totalTickets === 1 ? '' : 's' ?> en total
        </p>
        <div class="resumen-progress" role="progressbar" aria-valuenow="<?= $pctFacturado ?>" aria-valuemin="0" aria-valuemax="100" aria-label="<?= $pctFacturado ?>% facturado">
            <div class="resumen-progress-bar" style="width: <?= $pctFacturado ?>%"></div>
        </div>
        <div class="resumen-progress-meta">
            <span class="resumen-progress-pct"><?= $pctFacturado ?>% facturado</span>
            <span class="resumen-progress-goal">Meta: 100% antes del cierre</span>
        </div>
    </section>

    <section class="resumen-split" aria-label="Facturado vs por facturar">
        <div class="resumen-stat resumen-stat--done">
            <div class="resumen-stat-label">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                Facturado
            </div>
            <div class="resumen-stat-amount">$<?= number_format($montoFacturado, 2) ?></div>
            <div class="resumen-stat-count">
                <?= $countFacturado ?> factura<?= $countFacturado === 1 ? '' : 's' ?> recibida<?= $countFacturado === 1 ? '' : 's' ?>
            </div>
        </div>
        <div class="resumen-stat resumen-stat--pending">
            <div class="resumen-stat-label">
                <i class="fas fa-clock" aria-hidden="true"></i>
                Por facturar
            </div>
            <div class="resumen-stat-amount">$<?= number_format($montoPendiente, 2) ?></div>
            <div class="resumen-stat-count">
                <?= $countPendiente ?> ticket<?= $countPendiente === 1 ? '' : 's' ?> pendiente<?= $countPendiente === 1 ? '' : 's' ?>
            </div>
        </div>
    </section>

    <aside class="resumen-precierre" role="note">
        <i class="fas fa-calendar-day resumen-precierre-icon" aria-hidden="true"></i>
        <p class="mb-0">
            <?php if ($diasParaCierre > 0): ?>
                <strong>Faltan <?= $diasParaCierre ?> día<?= $diasParaCierre === 1 ? '' : 's' ?> para el cierre de <?= htmlspecialchars($nombreMesLower) ?>.</strong>
                <?php if ($montoPendiente > 0): ?>
                    Tienes $<?= number_format($montoPendiente, 2) ?> en gastos sin factura.
                    Reenvía las solicitudes desde <a href="#pendientes-titulo">aquí</a>.
                <?php else: ?>
                    No tienes montos pendientes de factura.
                <?php endif; ?>
            <?php elseif ($diasParaCierre === 0): ?>
                <strong>Hoy cierra <?= htmlspecialchars($nombreMesLower) ?>.</strong>
                <?php if ($montoPendiente > 0): ?>
                    Tienes $<?= number_format($montoPendiente, 2) ?> en gastos sin factura.
                    Reenvía las solicitudes desde <a href="#pendientes-titulo">aquí</a>.
                <?php else: ?>
                    No tienes montos pendientes de factura.
                <?php endif; ?>
            <?php else: ?>
                <strong>El cierre de <?= htmlspecialchars($nombreMesLower) ?> ya pasó.</strong>
                <?php if ($montoPendiente > 0): ?>
                    Aún tienes $<?= number_format($montoPendiente, 2) ?> en gastos sin factura.
                    Reenvía las solicitudes desde <a href="#pendientes-titulo">aquí</a>.
                <?php else: ?>
                    Quedó todo facturado.
                <?php endif; ?>
            <?php endif; ?>
        </p>
    </aside>

    <section class="resumen-pendientes" aria-labelledby="pendientes-titulo">
        <div class="resumen-pendientes-head">
            <h2 id="pendientes-titulo">Pendientes de facturar</h2>
            <span class="resumen-pendientes-hint">Más antiguos primero</span>
        </div>

        <?php if (empty($pendientes)): ?>
            <div class="resumen-empty">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                <p class="mb-0">Nada pendiente de facturar en este periodo.</p>
            </div>
        <?php else: ?>
            <ul class="resumen-pendientes-list">
                <?php foreach ($pendientes as $ticket): ?>
                    <?php
                    $titulo = trim((string) ($ticket['empresa_nombre'] ?? ''));
                    if ($titulo === '') {
                        $desc = trim((string) ($ticket['descripcion'] ?? ''));
                        if ($desc !== '' && stripos($desc, 'Recibida manualmente') === false) {
                            $titulo = $desc;
                        } else {
                            $titulo = $ticket['numeroTicket']
                                ? ('Ticket ' . $ticket['numeroTicket'])
                                : ('Ticket #' . $ticket['id']);
                        }
                    }

                    $cuentaNombre = trim((string) ($ticket['razonSocial'] ?? ''));
                    $ts = strtotime((string) $ticket['fecha']);
                    $diasSin = $ts ? max(0, (int) floor((time() - $ts) / 86400)) : 0;
                    $fechaCorta = '';
                    if ($ts) {
                        $fechaCorta = ((int) date('j', $ts)) . ' ' . ($mesesAbrev[(int) date('n', $ts)] ?? date('M', $ts));
                    }

                    $metaParts = ['#' . (int) $ticket['id']];
                    if ($fechaCorta !== '') {
                        $metaParts[] = $fechaCorta;
                    }
                    if ($cuentaNombre !== '') {
                        $metaParts[] = $cuentaNombre;
                    }

                    $urlTicket = site_url('visualizar-ticket?id=' . (int) $ticket['id']);
                    $mensajeWa = construirMensajeWhatsAppFactura((int) $ticket['id'], $ticket, $urlTicket);
                    $waUrl = 'https://wa.me/?text=' . rawurlencode($mensajeWa);
                    ?>
                    <li class="resumen-pendiente">
                        <div class="resumen-pendiente-body">
                            <div class="resumen-pendiente-top">
                                <a href="/visualizar-ticket?id=<?= (int) $ticket['id'] ?>" class="resumen-pendiente-title">
                                    <?= htmlspecialchars($titulo) ?>
                                </a>
                                <div class="resumen-pendiente-amount">$<?= number_format((float) $ticket['monto'], 2) ?></div>
                            </div>
                            <div class="resumen-pendiente-bottom">
                                <div class="resumen-pendiente-meta">
                                    <?= htmlspecialchars(implode(' · ', $metaParts)) ?>
                                </div>
                                <div class="resumen-pendiente-actions">
                                    <span class="resumen-dias"><?= $diasSin ?> día<?= $diasSin === 1 ? '' : 's' ?> sin factura</span>
                                    <a href="<?= htmlspecialchars($waUrl) ?>"
                                       class="resumen-btn-reenviar"
                                       target="_blank"
                                       rel="noopener noreferrer">
                                        <i class="fas fa-paper-plane" aria-hidden="true"></i> Reenviar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</main>
