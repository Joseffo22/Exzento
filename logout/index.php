<head>
    <meta charset="UTF-8">
    <title>Exzento - Compartir Tickets y Recibir Facturas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --exzento-blue: #2563EB;
            --exzento-black: #000000;
            --exzento-white: #FFFFFF;
            --bs-primary-dark: #1D4ED8;
        }
        
        body {
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-weight: 700;
            background-color: var(--exzento-white);
            color: var(--exzento-black);
            margin: 0;
            padding: 0;
        }
        
        .header {
            background: var(--exzento-blue);
            color: var(--exzento-white);
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(37, 99, 235, 0.3);
        }
        
        .header .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .header .menu-btn {
            background: none;
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
        }
        
        .top-actions {
            background: white;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e9ecef;
        }
        
        .top-actions .register-link {
            color: var(--exzento-blue);
            text-decoration: underline;
            font-weight: 500;
        }
        
        .top-actions .login-btn {
            background: var(--exzento-blue);
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 25px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .top-actions .login-btn:hover {
            background: var(--bs-primary-dark);
            transform: translateY(-2px);
        }
        
        .hero-section {
            background: white;
            padding: 2rem 1rem;
            text-align: center;
        }
        
        .hero-section h1 {
            color: var(--exzento-black);
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 1rem;
        }
        
        .hero-section p {
            color: #6c757d;
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        
        .features-list {
            list-style: none;
            padding: 0;
            margin: 0 0 2rem 0;
        }
        
        .features-list li {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
            color: #495057;
            font-size: 1rem;
        }
        
        .features-list i {
            color: var(--exzento-blue);
            font-size: 1.2rem;
            width: 20px;
        }
        
        .user-types {
            padding: 2rem 1rem;
            background: #f8f9fa;
        }
        
        .user-type-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-left: 4px solid var(--exzento-blue);
        }
        
        .user-type-card h3 {
            color: var(--exzento-blue);
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 1rem;
        }
        
        .user-type-card ul {
            list-style: none;
            padding: 0;
            margin: 0 0 1.5rem 0;
        }
        
        .user-type-card li {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            margin-bottom: 0.8rem;
            color: #495057;
            font-size: 0.95rem;
        }
        
        .user-type-card i {
            color: var(--exzento-blue);
            font-size: 1rem;
            width: 16px;
        }
        
        .register-btn {
            background: var(--exzento-blue);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 25px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            font-size: 1rem;
        }
        
        .register-btn:hover {
            background: var(--bs-primary-dark);
            transform: translateY(-2px);
        }
        
        .illustration-section {
            padding: 2rem 1rem;
            text-align: center;
            background: white;
        }
        
        .illustration-img {
            width: 100%;
            max-width: 400px;
            height: 200px;
            background: #EFF6FF;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            color: var(--exzento-blue);
            font-size: 3rem;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.2);
        }
        
        .search-section {
            padding: 2rem 1rem;
            background: #f8f9fa;
            text-align: center;
        }
        
        .search-section h3 {
            color: var(--exzento-black);
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }
        
        .search-form {
            max-width: 400px;
            margin: 0 auto;
        }
        
        .search-input-group {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .search-input {
            flex: 1;
            padding: 0.8rem;
            border: 2px solid #e9ecef;
            border-radius: 25px;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.3s ease;
        }
        
        .search-input:focus {
            border-color: var(--exzento-blue);
        }
        
        .search-btn {
            background: var(--exzento-blue);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 25px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        
        .search-btn:hover {
            background: var(--bs-primary-dark);
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .hero-section h1 {
                font-size: 1.5rem;
            }
            
            .user-type-card {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>

 

    <!-- Top Actions -->
    <div class="top-actions">
        <span>¿Aún no tienes cuenta?</span>
        <div style="display: flex; gap: 1rem; align-items: center;">
            <a href="#" class="register-link">Regístrate</a>
            <button class="login-btn">Ingresar</button>
        </div>
    </div>

    <!-- Hero Section -->
    <div class="hero-section">
        <h1>¿Qué es Exzento?</h1>
        <p>La nueva forma para compartir tickets y recibir facturas, sin complicaciones, en un solo espacio.</p>
        
        <ul class="features-list">
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

    <!-- User Types -->
    <div class="user-types">
        <div class="row">
            <div class="col-md-6">
                <div class="user-type-card">
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
                    <button class="register-btn">Quiero registrarme como cliente</button>
                </div>
            </div>
            <div class="col-md-6">
                <div class="user-type-card">
                    <h3>Para Comercios</h3>
                    <ul>
                        <li>
                            <i class="fas fa-file-alt"></i>
                            <span>Accede al ticket del cliente, sus datos fiscales y factura en un solo click.</span>
                        </li>
                        <li>
                            <i class="fas fa-star"></i>
                            <span>Sube la factura directamente, Sin correos. Sin WhatsApp. Sin errores.</span>
                        </li>
                    </ul>
                    <button class="register-btn">Quiero registrarme como comercio</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Illustration -->
    <div class="illustration-section">
        <img src="assets/img/papeles.jpg" class="illustration-img" alt="Facturación electrónica">
    </div>

    <!-- Search Section -->
    <div class="search-section">
        <h3>¿Ya tienes un ID de Ticket? Ingresa aquí para consultarlo.</h3>
        <form action="visualizar-ticket" method="GET" class="search-form">
            <div class="search-input-group">
                <input type="text" name="id" class="search-input" placeholder="ID de ticket" required>
                <button class="search-btn" type="submit">Buscar</button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
