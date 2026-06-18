<?php
$uriActual = $uri ?? trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

$tabsTickets = ['generar-ticket', 'lista-tickets', 'lector-qr', 'visualizar-ticket'];
$tabsFacturas = ['facturas'];
$tabsPerfil = ['informacion-personal', 'registrar-datos-fiscales', 'editar-datos-fiscales', 'contactos-frecuentes', 'invitacion-amigo'];

$tabs = [
    [
        'href' => '/',
        'icon' => 'fa-house',
        'label' => 'Inicio',
        'active' => in_array($uriActual, ['', 'index'], true),
    ],
    [
        'href' => '/lista-tickets',
        'icon' => 'fa-ticket-alt',
        'label' => 'Tickets',
        'active' => in_array($uriActual, $tabsTickets, true),
    ],
    [
        'href' => '/facturas',
        'icon' => 'fa-file-invoice-dollar',
        'label' => 'Facturas',
        'active' => in_array($uriActual, $tabsFacturas, true),
    ],
    [
        'href' => '/informacion-personal',
        'icon' => 'fa-user',
        'label' => 'Perfil',
        'active' => in_array($uriActual, $tabsPerfil, true),
    ],
];
?>
<nav class="cliente-bottom-nav" aria-label="Navegación principal">
    <div class="cliente-bottom-nav-inner">
        <?php foreach ($tabs as $tab): ?>
        <a href="<?= htmlspecialchars($tab['href']) ?>"
           class="cliente-tab<?= $tab['active'] ? ' active' : '' ?>"
           <?= $tab['active'] ? 'aria-current="page"' : '' ?>>
            <i class="fas <?= htmlspecialchars($tab['icon']) ?>" aria-hidden="true"></i>
            <span><?= htmlspecialchars($tab['label']) ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</nav>
