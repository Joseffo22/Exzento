<nav class="navbar navbar-expand-lg navbar-dark exzento-navbar">
    <div class="container">
        <a class="navbar-brand" href="/">
            <img src="/assets/img/logo.svg" alt="Exzento" class="navbar-logo" width="140" height="28">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Abrir menú">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($uri === '' || $uri === 'index') ? 'active' : ''; ?>" href="/">
                        Inicio
                    </a>
                </li>
                <!-- Dropdown Tickets -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo in_array($uri, ['generar-ticket', 'lista-tickets', 'lector-qr']) ? 'active' : ''; ?>" href="#" id="ticketsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Tickets
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="ticketsDropdown">
                        <li><a class="dropdown-item" href="/generar-ticket">Generar Ticket</a></li>
                        <li><a class="dropdown-item" href="/lista-tickets">Mis Tickets</a></li>
                        <li><a class="dropdown-item" href="/lector-qr">Lector QR</a></li>
                    </ul>
                </li>
                <!-- Dropdown Facturación -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo in_array($uri, ['facturas', 'registrar-datos-fiscales', 'editar-datos-fiscales']) ? 'active' : ''; ?>" href="#" id="facturacionDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Facturación
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="facturacionDropdown">
                        <li><a class="dropdown-item" href="/facturas">Facturas</a></li>
                        <li><a class="dropdown-item" href="/registrar-datos-fiscales">Registrar Datos Fiscales</a></li>
                        <li><a class="dropdown-item" href="/editar-datos-fiscales">Editar Datos Fiscales</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($uri === 'contactos-frecuentes') ? 'active' : ''; ?>" href="/contactos-frecuentes">
                        Contactos
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo in_array($uri, ['informacion-personal', 'invitacion-amigo']) ? 'active' : ''; ?>" href="#" id="cuentaDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Cuenta
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="cuentaDropdown">
                        <li><a class="dropdown-item" href="/informacion-personal">Información Personal</a></li>
                        <li><a class="dropdown-item" href="/invitacion-amigo">Invitar Amigo</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="funciones/logout.php">Cerrar sesión</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
