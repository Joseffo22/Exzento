<section class="landing-hero">
    <div class="container">
        <h1>¿Qué es Exzento?</h1>
        <p class="lead">La nueva forma para compartir tickets y recibir facturas, sin complicaciones, en un solo espacio.</p>

        <ul class="landing-features">
            <li>
                <i class="fas fa-file-alt"></i>
                <span>Ticket + Datos fiscales + Factura = Un solo espacio</span>
            </li>
            <li>
                <i class="fas fa-check-circle"></i>
                <span>Olvídate del correo.</span>
            </li>
            <li>
                <i class="fas fa-star"></i>
                <span>Simplifica. Organiza. Automatiza.</span>
            </li>
        </ul>
    </div>
</section>

<section class="landing-cards">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-6">
                <div class="landing-card">
                    <h3>Para Clientes</h3>
                    <ul>
                        <li>
                            <i class="fas fa-clock"></i>
                            <span>Recibe tus facturas sin perseguir a nadie.</span>
                        </li>
                        <li>
                            <i class="fas fa-file-alt"></i>
                            <span>Guarda todo en un solo lugar, automáticamente.</span>
                        </li>
                    </ul>
                    <a href="/login" class="btn landing-btn">Quiero registrarme como cliente</a>
                </div>
            </div>
            <div class="col-md-6">
                <div class="landing-card">
                    <h3>Para Comercios</h3>
                    <ul>
                        <li>
                            <i class="fas fa-file-alt"></i>
                            <span>Accede al ticket del cliente, sus datos fiscales y factura en un solo click.</span>
                        </li>
                        <li>
                            <i class="fas fa-star"></i>
                            <span>Sube la factura directamente. Sin correos. Sin WhatsApp. Sin errores.</span>
                        </li>
                    </ul>
                    <a href="/login" class="btn landing-btn">Quiero registrarme como comercio</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="landing-illustration">
    <div class="container">
        <img src="/assets/img/papeles.jpg" alt="Facturación electrónica simplificada">
    </div>
</section>

<section class="landing-search">
    <div class="container">
        <h3>¿Ya tienes un ID de Ticket? Ingresa aquí para consultarlo.</h3>
        <form action="/visualizar-ticket" method="GET" class="landing-search-form">
            <input type="text" name="id" placeholder="ID de ticket" required>
            <button type="submit">Buscar</button>
        </form>
    </div>
</section>
