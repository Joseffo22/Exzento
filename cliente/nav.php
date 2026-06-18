<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="/">
            <img src="/assets/img/logo-white.png" alt="Exzento" class="navbar-logo">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($uri === '' || $uri === 'index') ? 'active' : ''; ?>" href="/">
                        <i class="fas fa-home me-1"></i>Inicio
                    </a>
                </li>
                <!-- Dropdown Tickets -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo in_array($uri, ['generar-ticket', 'lista-tickets', 'lector-qr']) ? 'active' : ''; ?>" href="#" id="ticketsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-ticket-alt me-1"></i>Tickets
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="ticketsDropdown">
                        <li><a class="dropdown-item" href="/generar-ticket"><i class="fas fa-plus-circle me-1"></i>Generar Ticket</a></li>
                        <li><a class="dropdown-item" href="/lista-tickets"><i class="fas fa-ticket-alt me-1"></i>Mis Tickets</a></li>
                        <li><a class="dropdown-item" href="/lector-qr"><i class="fas fa-qrcode me-1"></i>Lector QR</a></li>
                    </ul>
                </li>
                <!-- Dropdown Facturación -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo in_array($uri, ['facturas', 'registrar-datos-fiscales', 'editar-datos-fiscales']) ? 'active' : ''; ?>" href="#" id="facturacionDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-file-invoice-dollar me-1"></i>Facturación
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="facturacionDropdown">
                        <li><a class="dropdown-item" href="/facturas"><i class="fas fa-file-invoice-dollar me-1"></i>Facturas</a></li>
                        <li><a class="dropdown-item" href="/registrar-datos-fiscales"><i class="fas fa-plus-circle me-1"></i>Registrar Datos Fiscales</a></li>
                        <li><a class="dropdown-item" href="/editar-datos-fiscales"><i class="fas fa-edit me-1"></i>Editar Datos Fiscales</a></li>
                    </ul>
                </li>
                <!-- Contactos Frecuentes -->
                <li class="nav-item">
                    <a class="nav-link <?php echo ($uri === 'contactos-frecuentes') ? 'active' : ''; ?>" href="/contactos-frecuentes">
                        <i class="fas fa-star me-1"></i>Contactos Frecuentes
                    </a>
                </li>
                <!-- Dropdown Cuenta -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo in_array($uri, ['informacion-personal', 'invitacion-amigo']) ? 'active' : ''; ?>" href="#" id="cuentaDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user me-1"></i>Cuenta
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="cuentaDropdown">
                        <li><a class="dropdown-item" href="/informacion-personal"><i class="fas fa-user me-1"></i>Información Personal</a></li>
                        <li><a class="dropdown-item" href="/invitacion-amigo"><i class="fas fa-user-plus me-1"></i>Invitar Amigo</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($uri === 'login') ? 'active' : ''; ?>" href="funciones/logout.php">
                        <i class="fas fa-sign-out-alt me-1"></i>Cerrar sesión
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
