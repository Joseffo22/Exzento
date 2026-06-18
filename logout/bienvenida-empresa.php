<?php
require_once('../assets/php/conexiones/conexionMySqli.php');

// Variable para el nav
$uri = 'bienvenida-empresa';

// Obtener el ID del ticket de la URL
$ticket_id = $_GET['id'] ?? null;

if (!$ticket_id) {
    die("Error: No se proporcionó ID de ticket");
}

// Verificar que el ticket existe
$query_ticket = "SELECT t.*, df.razonSocial, df.rfc, df.correo 
                 FROM ticket t 
                 LEFT JOIN datosFiscales df ON t.id_datos = df.id 
                 WHERE t.id = ?";
$stmt_ticket = $conn->prepare($query_ticket);
$stmt_ticket->bind_param("i", $ticket_id);
$stmt_ticket->execute();
$result_ticket = $stmt_ticket->get_result();

if ($result_ticket->num_rows === 0) {
    die("Error: Ticket no encontrado");
}

$ticket = $result_ticket->fetch_assoc();

// Procesar registro de empresa
$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nombre_empresa = trim($_POST['nombre_empresa']);
        $direccion = trim($_POST['direccion']);
        $telefono = trim($_POST['telefono']);
        $correo = strtolower(trim($_POST['correo']));
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validaciones
        if (empty($nombre_empresa) || empty($direccion) || empty($telefono) || empty($correo) || empty($password)) {
            throw new Exception('Todos los campos son requeridos');
        }
        
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Correo electrónico inválido');
        }
        
        if (strlen($password) < 6) {
            throw new Exception('La contraseña debe tener al menos 6 caracteres');
        }
        
        if ($password !== $confirm_password) {
            throw new Exception('Las contraseñas no coinciden');
        }
        
        // Verificar si el correo ya existe
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE correo = ?");
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception('El correo electrónico ya está registrado');
        }
        
        // Hash de la contraseña
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Iniciar transacción
        $conn->begin_transaction();
        
        try {
            // Insertar usuario (tipo 3 = empresa)
            $fecha_registro = date('Y-m-d H:i:s');
            $fecha_corte = date('Y-m-d', strtotime('+1 year')); // 1 año de corte
            
            $stmt = $conn->prepare("INSERT INTO usuarios (correo, pass, tipo, fechaRegistro, fechaCorte, estatus) VALUES (?, ?, 3, ?, ?, 1)");
            $stmt->bind_param("ssss", $correo, $password_hash, $fecha_registro, $fecha_corte);
            $stmt->execute();
            $id_empresa_nuevo = $stmt->insert_id;
            
            // Insertar empresa
            $stmt = $conn->prepare("INSERT INTO empresas (nombre, direccion, telefono, id_usuario) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $nombre_empresa, $direccion, $telefono, $id_empresa_nuevo);
            $stmt->execute();
            
            // Actualizar el ticket con el ID de la empresa
            $stmt = $conn->prepare("UPDATE ticket SET id_empresa = ? WHERE id = ?");
            $stmt->bind_param("ii", $id_empresa_nuevo, $ticket_id);
            $stmt->execute();
            
            $conn->commit();
            
            $mensaje = "¡Cuenta creada exitosamente! Ya puedes iniciar sesión.";
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenida - Exzento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
      
        
        body {
            background: #2563EB;
            min-height: 100vh;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-weight: 700;
            display: flex;
            flex-direction: column;
        }
        
     
        
        
        .welcome-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            margin: 2rem auto;
            max-width: 1200px;
            flex: 1;
        }
        
        .hero-section {
            background: #000000;
            color: #FFFFFF;
            border-radius: 20px 20px 0 0;
            padding: 3rem 2rem;
            text-align: center;
        }
        
        .hero-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            animation: pulse 2s infinite;
        }
        
        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin: 1rem 0;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
        }
        
        .feature-icon {
            font-size: 2.5rem;
            color: #2563EB;
            margin-bottom: 1rem;
        }
        
        .ticket-info {
            background: #FFFFFF;
            border-radius: 15px;
            padding: 2rem;
            margin: 2rem 0;
            border-left: 5px solid #2563EB;
        }
        
        .register-form {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .btn-primary {
            background: #2563EB;
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 700;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: #1D4ED8;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.3);
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #2563EB;
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.25);
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .stats-section {
            background: #2563EB;
            color: #FFFFFF;
            border-radius: 15px;
            padding: 2rem;
            margin: 2rem 0;
        }
        
        .stat-item {
            text-align: center;
            padding: 1rem;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 1.1rem;
            opacity: 0.9;
        }
   
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>
    
    <div class="container py-5">
        <div class="welcome-container">
            <!-- Hero Section -->
            <div class="hero-section">
                <div class="hero-icon">
                    <i class="fas fa-building"></i>
                </div>
                <h1 class="display-4 fw-bold mb-3">¡Bienvenida a Exzento!</h1>
                <p class="lead mb-0">Has subido exitosamente una factura. Ahora descubre cómo podemos ayudarte a centralizar y gestionar todas tus facturas de manera eficiente.</p>
            </div>
            
            <div class="p-4">
                <!-- Información del Ticket -->
                <div class="ticket-info">
                    <h4 class="mb-3">
                        <i class="fas fa-ticket-alt me-2"></i>
                        Información del Ticket Procesado
                    </h4>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>ID del Ticket:</strong> #<?= htmlspecialchars($ticket['id']) ?></p>
                            <p><strong>Monto:</strong> $<?= number_format($ticket['monto'], 2) ?></p>
                            <p><strong>Fecha:</strong> <?= date('d/m/Y H:i', strtotime($ticket['fecha'])) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Cliente:</strong> <?= htmlspecialchars($ticket['razonSocial'] ?? 'N/A') ?></p>
                            <p><strong>RFC:</strong> <?= htmlspecialchars($ticket['rfc'] ?? 'N/A') ?></p>
                            
                        </div>
                    </div>
                </div>

                <!-- Estadísticas del Sistema -->
                <div class="stats-section">
                    <h3 class="text-center mb-4">
                        <i class="fas fa-chart-line me-2"></i>
                        Exzento en Números
                    </h3>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="stat-item">
                                <div class="stat-number">10,000+</div>
                                <div class="stat-label">Facturas Procesadas</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-item">
                                <div class="stat-number">500+</div>
                                <div class="stat-label">Empresas Activas</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-item">
                                <div class="stat-number">99.9%</div>
                                <div class="stat-label">Tiempo de Disponibilidad</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-item">
                                <div class="stat-number">24/7</div>
                                <div class="stat-label">Soporte Técnico</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Características del Sistema -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="feature-card text-center">
                            <div class="feature-icon">
                                <i class="fas fa-cloud"></i>
                            </div>
                            <h5>Centralización de Facturas</h5>
                            <p>Mantén todas tus facturas organizadas en un solo lugar, accesible desde cualquier dispositivo.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="feature-card text-center">
                            <div class="feature-icon">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                            <h5>Reportes Detallados</h5>
                            <p>Genera reportes completos de facturación, ventas y análisis financiero en tiempo real.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="feature-card text-center">
                            <div class="feature-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <h5>Seguridad Garantizada</h5>
                            <p>Protección de datos de nivel empresarial con encriptación SSL y respaldos automáticos.</p>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="feature-card text-center">
                            <div class="feature-icon">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <h5>Acceso Móvil</h5>
                            <p>Gestiona tus facturas desde cualquier lugar con nuestra aplicación móvil optimizada.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="feature-card text-center">
                            <div class="feature-icon">
                                <i class="fas fa-sync"></i>
                            </div>
                            <h5>Sincronización Automática</h5>
                            <p>Todos tus datos se sincronizan automáticamente entre dispositivos en tiempo real.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="feature-card text-center">
                            <div class="feature-icon">
                                <i class="fas fa-headset"></i>
                            </div>
                            <h5>Soporte Personalizado</h5>
                            <p>Equipo de soporte especializado disponible para resolver cualquier consulta o problema.</p>
                        </div>
                    </div>
                </div>

                <!-- Mensajes de éxito/error -->
                <?php if ($mensaje): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= htmlspecialchars($mensaje) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Formulario de Registro -->
                <div class="register-form">
                    <h3 class="text-center mb-4">
                        <i class="fas fa-user-plus me-2"></i>
                        Crea tu Cuenta Gratuita
                    </h3>
                    <p class="text-center text-muted mb-4">
                        Únete a miles de empresas que ya confían en Exzento para gestionar sus facturas
                    </p>
                    
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nombre_empresa" class="form-label">
                                    <i class="fas fa-building me-1"></i>Nombre de la Empresa
                                </label>
                                <input type="text" class="form-control" id="nombre_empresa" name="nombre_empresa" required>
                                <div class="invalid-feedback">Por favor ingresa el nombre de tu empresa</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="correo" class="form-label">
                                    <i class="fas fa-envelope me-1"></i>Correo Electrónico
                                </label>
                                <input type="email" class="form-control" id="correo" name="correo" required>
                                <div class="invalid-feedback">Por favor ingresa un correo válido</div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="direccion" class="form-label">
                                    <i class="fas fa-map-marker-alt me-1"></i>Dirección
                                </label>
                                <input type="text" class="form-control" id="direccion" name="direccion" required>
                                <div class="invalid-feedback">Por favor ingresa la dirección de tu empresa</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="telefono" class="form-label">
                                    <i class="fas fa-phone me-1"></i>Teléfono
                                </label>
                                <input type="tel" class="form-control" id="telefono" name="telefono" required>
                                <div class="invalid-feedback">Por favor ingresa el teléfono de tu empresa</div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock me-1"></i>Contraseña
                                </label>
                                <input type="password" class="form-control" id="password" name="password" required minlength="6">
                                <div class="invalid-feedback">La contraseña debe tener al menos 6 caracteres</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">
                                    <i class="fas fa-lock me-1"></i>Confirmar Contraseña
                                </label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                <div class="invalid-feedback">Las contraseñas no coinciden</div>
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-rocket me-2"></i>
                                Crear Cuenta Gratuita
                            </button>
                        </div>
                        
                        <div class="text-center mt-3">
                            <small class="text-muted">
                                Al crear tu cuenta, aceptas nuestros 
                                <a href="#" class="text-decoration-none">Términos y Condiciones</a> y 
                                <a href="#" class="text-decoration-none">Política de Privacidad</a>
                            </small>
                        </div>
                    </form>
                </div>

                <!-- Beneficios Adicionales -->
                <div class="row mt-5">
                    <div class="col-12">
                        <h4 class="text-center mb-4">
                            <i class="fas fa-gift me-2"></i>
                            Beneficios Incluidos en tu Cuenta Gratuita
                        </h4>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <i class="fas fa-infinity text-primary" style="font-size: 2rem;"></i>
                            <h6 class="mt-2">Facturas Ilimitadas</h6>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <i class="fas fa-users text-primary" style="font-size: 2rem;"></i>
                            <h6 class="mt-2">Múltiples Usuarios</h6>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <i class="fas fa-download text-primary" style="font-size: 2rem;"></i>
                            <h6 class="mt-2">Descargas Ilimitadas</h6>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <i class="fas fa-clock text-primary" style="font-size: 2rem;"></i>
                            <h6 class="mt-2">Soporte </h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validación del formulario
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();

        // Validación de contraseñas
        document.getElementById('confirm_password').addEventListener('input', function() {
            var password = document.getElementById('password').value;
            var confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Las contraseñas no coinciden');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
