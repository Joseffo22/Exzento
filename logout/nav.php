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
                <li class="nav-item">
                    <a class="nav-link" href="/login">
                        Iniciar sesión
                    </a>
                </li>
                <li class="nav-item ms-lg-2">
                    <a class="btn btn-exzento-nav" href="/login">Ingresar</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
