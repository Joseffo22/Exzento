<?php
require('assets/php/conexiones/conexionMySqli.php');
$cliente_id = $_SESSION['id_usuario'];

$mesesEs = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
];

if (!function_exists('exzento_parse_descripcion_factura')) {
    /**
     * Separa el nombre del comercio de la metadata "Recibida manualmente…".
     * @return array{comercio: string, manual: ?array{cuando: string, canal: string}}
     */
    function exzento_parse_descripcion_factura(?string $descripcion): array
    {
        $raw = trim((string) $descripcion);
        $manual = null;
        $comercio = $raw;

        if ($raw !== '' && preg_match(
            '/^(.*?)(?:\s*[-–—]\s*)?Recibida manualmente el\s+(.+?)\s+por:\s*(.+)$/isu',
            $raw,
            $m
        )) {
            $comercio = trim($m[1]);
            $manual = [
                'cuando' => trim($m[2]),
                'canal' => trim($m[3]),
            ];
        }

        return [
            'comercio' => $comercio,
            'manual' => $manual,
        ];
    }
}

if (!function_exists('facturas_url_con_filtros')) {
    function facturas_url_con_filtros(string $filtro, string $mesParam, int $cuentaId): string
    {
        $q = ['filtro' => $filtro];
        if ($mesParam !== '') {
            $q['mes'] = $mesParam;
        }
        if ($cuentaId > 0) {
            $q['cuenta'] = $cuentaId;
        }
        return '/facturas?' . http_build_query($q);
    }
}

$filtro = $_GET['filtro'] ?? 'todas';
if (!in_array($filtro, ['todas', 'facturadas', 'recibidas_manual'], true)) {
    $filtro = 'todas';
}

$mesParam = $_GET['mes'] ?? '';
$anioSel = 0;
$mesSel = 0;
if ($mesParam !== '' && preg_match('/^\d{4}-\d{2}$/', $mesParam)) {
    [$anioSel, $mesSel] = array_map('intval', explode('-', $mesParam));
    if ($mesSel < 1 || $mesSel > 12 || $anioSel < 2000) {
        $mesParam = '';
        $anioSel = 0;
        $mesSel = 0;
    }
} elseif ($mesParam !== '' && ctype_digit((string) $mesParam)) {
    // Compatibilidad con el filtro viejo (?mes=9)
    $mesSel = (int) $mesParam;
    $anioSel = (int) date('Y');
    $mesParam = sprintf('%04d-%02d', $anioSel, $mesSel);
} else {
    $mesParam = '';
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
for ($i = 0; $i < 24; $i++) {
    $y = (int) $cursor->format('Y');
    $m = (int) $cursor->format('n');
    $opcionesMes[] = [
        'valor' => $cursor->format('Y-m'),
        'etiqueta' => ($mesesEs[$m] ?? $cursor->format('F')) . ' ' . $y,
    ];
    $cursor->modify('-1 month');
}

$filtroBaseSql = " WHERE t.id_cliente = ?
  AND (
    EXISTS (SELECT 1 FROM facturas f0 WHERE f0.ticket_id = t.id)
    OR COALESCE(t.descripcion, '') LIKE '%Recibida manualmente%'
  )";
$paramsBase = [$cliente_id];
$typesBase = 's';

if ($cuentaId > 0) {
    $filtroBaseSql .= ' AND t.id_datos = ?';
    $paramsBase[] = $cuentaId;
    $typesBase .= 'i';
}

if ($mesSel > 0 && $anioSel > 0) {
    $filtroBaseSql .= ' AND MONTH(t.fecha) = ? AND YEAR(t.fecha) = ?';
    $paramsBase[] = $mesSel;
    $paramsBase[] = $anioSel;
    $typesBase .= 'ii';
}

$conteos = [
    'todas' => 0,
    'facturadas' => 0,
    'recibidas_manual' => 0,
];

$queryConteos = "SELECT
    COUNT(*) AS todas,
    SUM(CASE WHEN EXISTS (SELECT 1 FROM facturas f WHERE f.ticket_id = t.id) THEN 1 ELSE 0 END) AS facturadas,
    SUM(CASE
        WHEN NOT EXISTS (SELECT 1 FROM facturas f WHERE f.ticket_id = t.id)
         AND COALESCE(t.descripcion, '') LIKE '%Recibida manualmente%'
        THEN 1 ELSE 0 END) AS recibidas_manual
FROM ticket t
{$filtroBaseSql}";

$stmtConteos = $conn->prepare($queryConteos);
if ($stmtConteos) {
    $stmtConteos->bind_param($typesBase, ...$paramsBase);
    $stmtConteos->execute();
    $rowConteo = $stmtConteos->get_result()->fetch_assoc();
    if ($rowConteo) {
        $conteos['todas'] = (int) ($rowConteo['todas'] ?? 0);
        $conteos['facturadas'] = (int) ($rowConteo['facturadas'] ?? 0);
        $conteos['recibidas_manual'] = (int) ($rowConteo['recibidas_manual'] ?? 0);
    }
    $stmtConteos->close();
}

$query = "SELECT t.id, t.monto, t.fecha, t.descripcion, t.numeroTicket, t.id_datos,
                 df.razonSocial, df.rfc,
                 e.nombre AS empresa_nombre,
                 f.id AS factura_id, f.nombre_archivo, f.archivo_pdf, f.archivo_xml, f.creado_en AS fecha_factura,
                 CASE
                     WHEN f.id IS NOT NULL THEN 'facturada'
                     ELSE 'recibida_manual'
                 END AS estado
          FROM ticket t
          LEFT JOIN datosFiscales df ON t.id_datos = df.id
          LEFT JOIN empresas e ON t.id_empresa = e.id
          LEFT JOIN facturas f ON f.id = (
              SELECT f2.id FROM facturas f2 WHERE f2.ticket_id = t.id ORDER BY f2.id DESC LIMIT 1
          )
          {$filtroBaseSql}";

$params = $paramsBase;
$types = $typesBase;

if ($filtro === 'facturadas') {
    $query .= ' AND EXISTS (SELECT 1 FROM facturas fx WHERE fx.ticket_id = t.id)';
} elseif ($filtro === 'recibidas_manual') {
    $query .= " AND NOT EXISTS (SELECT 1 FROM facturas fx WHERE fx.ticket_id = t.id)
                AND COALESCE(t.descripcion, '') LIKE '%Recibida manualmente%'";
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

?>

<link href="/assets/css/facturas-cliente.css" rel="stylesheet">

<main class="facturas-page">
    <header class="facturas-header">
        <h1>Mis facturas</h1>
        <p>
            Gastos que ya quedaron facturados. Los pendientes están en
            <a href="/lista-tickets">Mis tickets</a>.
        </p>
    </header>

    <form class="facturas-filters" method="GET" action="/facturas" id="facturasFiltros">
        <input type="hidden" name="filtro" value="<?= htmlspecialchars($filtro) ?>">

        <div class="facturas-selects">
            <select name="mes" class="form-select" aria-label="Mes" onchange="this.form.submit()">
                <option value="" <?= $mesParam === '' ? 'selected' : '' ?>>Todos los meses</option>
                <?php foreach ($opcionesMes as $op): ?>
                    <option value="<?= htmlspecialchars($op['valor']) ?>" <?= $op['valor'] === $mesParam ? 'selected' : '' ?>>
                        <?= htmlspecialchars($op['etiqueta']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="cuenta" class="form-select" aria-label="Cuenta fiscal" onchange="this.form.submit()">
                <option value="" <?= $cuentaId === 0 ? 'selected' : '' ?>>Cuenta</option>
                <?php foreach ($cuentasFiscales as $cuenta): ?>
                    <option value="<?= (int) $cuenta['id'] ?>" <?= $cuentaId === (int) $cuenta['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cuenta['razonSocial']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="facturas-chips" role="tablist" aria-label="Filtrar por estado">
            <a href="<?= htmlspecialchars(facturas_url_con_filtros('todas', $mesParam, $cuentaId)) ?>"
               class="facturas-chip<?= $filtro === 'todas' ? ' active' : '' ?>">
                Todas
                <?php if ($conteos['todas'] > 0): ?>
                    <span class="facturas-chip-count"><?= $conteos['todas'] ?></span>
                <?php endif; ?>
            </a>
            <a href="<?= htmlspecialchars(facturas_url_con_filtros('facturadas', $mesParam, $cuentaId)) ?>"
               class="facturas-chip<?= $filtro === 'facturadas' ? ' active' : '' ?>">
                Facturadas
                <?php if ($conteos['facturadas'] > 0): ?>
                    <span class="facturas-chip-count"><?= $conteos['facturadas'] ?></span>
                <?php endif; ?>
            </a>
            <a href="<?= htmlspecialchars(facturas_url_con_filtros('recibidas_manual', $mesParam, $cuentaId)) ?>"
               class="facturas-chip<?= $filtro === 'recibidas_manual' ? ' active' : '' ?>">
                Recibidas manualmente
                <?php if ($conteos['recibidas_manual'] > 0): ?>
                    <span class="facturas-chip-count"><?= $conteos['recibidas_manual'] ?></span>
                <?php endif; ?>
            </a>
        </div>
    </form>

    <?php if (!empty($tickets)): ?>
        <div class="facturas-list">
            <?php foreach ($tickets as $ticket): ?>
                <?php
                $esFacturada = ($ticket['estado'] ?? '') === 'facturada';
                $parsed = exzento_parse_descripcion_factura($ticket['descripcion'] ?? '');
                $comercio = $parsed['comercio'];
                if ($comercio === '') {
                    $comercio = trim((string) ($ticket['empresa_nombre'] ?? ''));
                }
                if ($comercio === '') {
                    $comercio = $ticket['numeroTicket']
                        ? ('Ticket ' . $ticket['numeroTicket'])
                        : ('Ticket #' . $ticket['id']);
                }

                $cuentaFiscal = trim((string) ($ticket['razonSocial'] ?? ''));
                if ($cuentaFiscal === '') {
                    $cuentaFiscal = 'Sin cuenta fiscal';
                }

                $ts = strtotime((string) $ticket['fecha']);
                $fechaHora = $ts ? date('d/m/Y · H:i', $ts) : '—';
                $collapseId = 'factura-detalle-' . (int) $ticket['id'];
                ?>
                <article class="factura-card" data-factura-card>
                    <button type="button"
                            class="factura-card-main"
                            aria-expanded="false"
                            aria-controls="<?= $collapseId ?>"
                            data-factura-toggle>
                        <div class="factura-folio" aria-hidden="true">
                            <span class="factura-folio-label">TICKET</span>
                            <span class="factura-folio-num">#<?= (int) $ticket['id'] ?></span>
                        </div>

                        <div class="factura-body">
                            <div class="factura-row">
                                <span class="factura-label">Comercio:</span>
                                <span class="factura-comercio"><?= htmlspecialchars($comercio) ?></span>
                            </div>
                            <div class="factura-row">
                                <span class="factura-label">Cuenta fiscal:</span>
                                <span class="factura-cuenta"><?= htmlspecialchars($cuentaFiscal) ?></span>
                            </div>
                            <div class="factura-fecha">
                                <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                                <?= htmlspecialchars($fechaHora) ?>
                            </div>
                        </div>

                        <div class="factura-side">
                            <div class="factura-monto">$<?= number_format((float) $ticket['monto'], 2) ?></div>
                            <?php if ($esFacturada): ?>
                                <span class="factura-badge factura-badge--ok">
                                    <i class="fas fa-check" aria-hidden="true"></i> Facturada
                                </span>
                            <?php else: ?>
                                <span class="factura-badge factura-badge--manual">
                                    <i class="fas fa-hand-pointer" aria-hidden="true"></i> Recibida manualmente
                                </span>
                            <?php endif; ?>
                            <i class="fas fa-chevron-down factura-chevron" aria-hidden="true"></i>
                        </div>
                    </button>

                    <div class="factura-detail" id="<?= $collapseId ?>" hidden>
                        <dl class="factura-detail-grid">
                            <div class="factura-detail-item">
                                <dt>Folio</dt>
                                <dd>#<?= (int) $ticket['id'] ?></dd>
                            </div>
                            <?php if (!empty($ticket['numeroTicket'])): ?>
                            <div class="factura-detail-item">
                                <dt>Núm. ticket</dt>
                                <dd><?= htmlspecialchars($ticket['numeroTicket']) ?></dd>
                            </div>
                            <?php endif; ?>
                            <div class="factura-detail-item">
                                <dt>RFC</dt>
                                <dd><?= htmlspecialchars($ticket['rfc'] ?: '—') ?></dd>
                            </div>
                            <?php if ($esFacturada && !empty($ticket['fecha_factura'])): ?>
                            <div class="factura-detail-item">
                                <dt>Factura subida</dt>
                                <dd><?= htmlspecialchars(date('d/m/Y H:i', strtotime($ticket['fecha_factura']))) ?></dd>
                            </div>
                            <?php endif; ?>
                            <?php if (!$esFacturada && $parsed['manual']): ?>
                            <div class="factura-detail-item">
                                <dt>Recibida el</dt>
                                <dd><?= htmlspecialchars($parsed['manual']['cuando']) ?></dd>
                            </div>
                            <div class="factura-detail-item">
                                <dt>Canal</dt>
                                <dd><?= htmlspecialchars($parsed['manual']['canal']) ?></dd>
                            </div>
                            <?php elseif (!$esFacturada): ?>
                            <div class="factura-detail-item">
                                <dt>Nota</dt>
                                <dd>Marcada como recibida manualmente</dd>
                            </div>
                            <?php endif; ?>
                        </dl>

                        <div class="factura-actions">
                            <?php if ($esFacturada): ?>
                                <?php if (!empty($ticket['archivo_pdf'])): ?>
                                    <a href="<?= site_url(htmlspecialchars($ticket['archivo_pdf'])) ?>"
                                       target="_blank"
                                       rel="noopener noreferrer"
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-file-pdf me-1"></i>PDF
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($ticket['archivo_xml'])): ?>
                                    <a href="<?= site_url(htmlspecialchars($ticket['archivo_xml'])) ?>"
                                       download
                                       class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-file-code me-1"></i>XML
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                            <a href="/visualizar-ticket?id=<?= (int) $ticket['id'] ?>"
                               class="btn btn-outline-info btn-sm">
                                <i class="fas fa-receipt me-1"></i>Ver ticket
                            </a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="facturas-empty">
            <i class="fas fa-inbox" aria-hidden="true"></i>
            <h2>No se encontraron facturas</h2>
            <p>
                Aún no hay resultados con estos filtros. Revisa tus pendientes en
                <a href="/lista-tickets">Mis tickets</a>.
            </p>
        </div>
    <?php endif; ?>
</main>

<script>
document.querySelectorAll('[data-factura-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var card = btn.closest('[data-factura-card]');
        if (!card) return;
        var detail = card.querySelector('.factura-detail');
        var open = card.classList.toggle('open');
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (detail) {
            if (open) {
                detail.removeAttribute('hidden');
            } else {
                detail.setAttribute('hidden', '');
            }
        }
    });
});
</script>
<?php
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>
