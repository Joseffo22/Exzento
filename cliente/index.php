<?php
require('assets/php/conexiones/conexionMySqli.php');

$id_usuario = $_SESSION['id_usuario'];
$cliente_id = $id_usuario;

$query_usuario = "SELECT c.nombre, c.apellido FROM usuarios u
                  INNER JOIN clientes c ON c.id_usuario = u.id
                  WHERE u.id = ?";
$stmt_usuario = $conn->prepare($query_usuario);
$stmt_usuario->bind_param('i', $id_usuario);
$stmt_usuario->execute();
$perfil = $stmt_usuario->get_result()->fetch_assoc();
$stmt_usuario->close();

$nombreCliente = trim(($perfil['nombre'] ?? '') . ' ' . ($perfil['apellido'] ?? ''));
if ($nombreCliente === '') {
    $nombreCliente = 'Cliente';
}

$hora = (int) date('G');
if ($hora < 12) {
    $saludo = 'Buenos días';
} elseif ($hora < 19) {
    $saludo = 'Buenas tardes';
} else {
    $saludo = 'Buenas noches';
}

$query_metricas = "SELECT
    SUM(CASE WHEN f.id IS NULL AND COALESCE(t.descripcion, '') NOT LIKE '%Recibida manualmente%' THEN 1 ELSE 0 END) AS pendientes,
    SUM(CASE WHEN f.id IS NOT NULL THEN 1 ELSE 0 END) AS facturadas
FROM ticket t
LEFT JOIN facturas f ON t.id = f.ticket_id
WHERE t.id_cliente = ?";
$stmt_metricas = $conn->prepare($query_metricas);
$stmt_metricas->bind_param('s', $cliente_id);
$stmt_metricas->execute();
$metricas = $stmt_metricas->get_result()->fetch_assoc();
$stmt_metricas->close();

$pendientes = (int) ($metricas['pendientes'] ?? 0);
$facturadas = (int) ($metricas['facturadas'] ?? 0);

$query_recientes = "SELECT t.id, t.monto, t.fecha, t.descripcion,
    CASE WHEN f.id IS NOT NULL THEN 'facturada' ELSE 'pendiente' END AS estado
FROM ticket t
LEFT JOIN facturas f ON t.id = f.ticket_id
WHERE t.id_cliente = ?
ORDER BY t.fecha DESC
LIMIT 5";
$stmt_recientes = $conn->prepare($query_recientes);
$stmt_recientes->bind_param('s', $cliente_id);
$stmt_recientes->execute();
$ticketsRecientes = $stmt_recientes->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_recientes->close();

$conn->close();
?>

<link href="/assets/css/dashboard-cliente.css" rel="stylesheet">

<main class="dashboard-page">
    <header class="dashboard-greeting">
        <h1><?= htmlspecialchars($saludo) ?>, <?= htmlspecialchars(explode(' ', $nombreCliente)[0]) ?></h1>
        <p>Tu resumen de facturación</p>
    </header>

    <div class="dashboard-metrics">
        <a href="/lista-tickets" class="dashboard-metric dashboard-metric--pending">
            <div class="dashboard-metric-value"><?= $pendientes ?></div>
            <div class="dashboard-metric-label">Pendientes</div>
        </a>
        <a href="/facturas?filtro=facturadas" class="dashboard-metric dashboard-metric--done">
            <div class="dashboard-metric-value"><?= $facturadas ?></div>
            <div class="dashboard-metric-label">Facturadas</div>
        </a>
    </div>

    <a href="/generar-ticket" class="btn btn-primary dashboard-cta">
        <i class="fas fa-plus-circle me-2"></i>Nuevo ticket de facturación
    </a>

    <section class="dashboard-activity" aria-labelledby="actividad-titulo">
        <h2 class="dashboard-section-title" id="actividad-titulo">Actividad reciente</h2>
        <?php if (empty($ticketsRecientes)): ?>
            <div class="dashboard-activity-list">
                <p class="dashboard-empty mb-0">Aún no tienes tickets. Crea el primero con el botón de arriba.</p>
            </div>
        <?php else: ?>
            <ul class="dashboard-activity-list">
                <?php foreach ($ticketsRecientes as $ticket): ?>
                <?php
                    $esFacturada = $ticket['estado'] === 'facturada';
                    $titulo = $ticket['descripcion'] ?: ('Ticket #' . $ticket['id']);
                    $fechaCorta = date('d/m/Y', strtotime($ticket['fecha']));
                ?>
                <li class="dashboard-activity-item">
                    <a href="/visualizar-ticket?id=<?= (int) $ticket['id'] ?>" class="dashboard-activity-link">
                        <div class="dashboard-activity-main">
                            <div class="dashboard-activity-title"><?= htmlspecialchars($titulo) ?></div>
                            <div class="dashboard-activity-meta">#<?= (int) $ticket['id'] ?> · <?= $fechaCorta ?></div>
                        </div>
                        <div class="dashboard-activity-right">
                            <div class="dashboard-activity-amount">$<?= number_format((float) $ticket['monto'], 2) ?></div>
                            <span class="dashboard-badge <?= $esFacturada ? 'dashboard-badge--done' : 'dashboard-badge--pending' ?>">
                                <?= $esFacturada ? 'Facturada' : 'Pendiente' ?>
                            </span>
                        </div>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
            <a href="/lista-tickets" class="dashboard-view-all">Ver todos los tickets</a>
        <?php endif; ?>
    </section>

    <section aria-labelledby="acceso-titulo">
        <h2 class="dashboard-section-title" id="acceso-titulo">Acceso rápido</h2>
        <div class="dashboard-quick-grid">
            <a href="/lista-tickets" class="dashboard-quick-card">
                <i class="fas fa-ticket-alt" aria-hidden="true"></i>
                Mis tickets
            </a>
            <a href="/facturas" class="dashboard-quick-card">
                <i class="fas fa-file-invoice-dollar" aria-hidden="true"></i>
                Facturas
            </a>
            <a href="/contactos-frecuentes" class="dashboard-quick-card">
                <i class="fas fa-address-book" aria-hidden="true"></i>
                Contactos
            </a>
            <a href="/informacion-personal" class="dashboard-quick-card">
                <i class="fas fa-id-card" aria-hidden="true"></i>
                Datos fiscales
            </a>
        </div>
    </section>

    <div class="cliente-support-bar">
        <a href="#" onclick="mostrarAyuda(); return false;">Ayuda</a>
        <span class="sep">·</span>
        <a href="#" onclick="mostrarContacto(); return false;">Contacto</a>
        <span class="sep">·</span>
        <a href="#" onclick="mostrarFAQ(); return false;">FAQ</a>
    </div>
</main>
