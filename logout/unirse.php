<?php
require_once('assets/php/conexiones/conexionMySqli.php');

$id_invitacion = $_GET['id'] ?? null;
$invitacion_valida = false;
$mensaje_invitacion = '';
$error = '';

if ($id_invitacion) {
    // Verificar si la invitación existe y no ha sido usada
    $stmt = $conn->prepare("SELECT i.*, c.nombre, c.apellido 
                           FROM invitaciones i 
                           JOIN usuarios u ON i.id_usuario = u.id 
                           JOIN clientes c ON u.id= c.id_usuario
                           WHERE i.id = ? AND i.usada = 0");
    $stmt->bind_param("i", $id_invitacion);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $invitacion = $result->fetch_assoc();
        $invitacion_valida = true;
        $mensaje_invitacion = $invitacion['mensaje'];
        $nombre_invitador = $invitacion['nombre'] . ' ' . $invitacion['apellido'];
        $id_usuario_invitador = $invitacion['id_usuario'];
    } else {
        $error = 'Link de invitación inválido o ya utilizado.';
    }
}

// Procesar registro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $invitacion_valida) {
    try {
        $nombre = trim($_POST['nombre']);
        $apellido = trim($_POST['apellido']);
        $correo = strtolower(trim($_POST['correo']));
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validaciones
        if (empty($nombre) || empty($apellido) || empty($correo) || empty($password)) {
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
        
        // Verificar nuevamente que la invitación no haya sido usada (doble verificación)
        $stmt = $conn->prepare("SELECT id FROM invitaciones WHERE id = ? AND usada = 0");
        $stmt->bind_param("i", $id_invitacion);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            throw new Exception('Esta invitación ya ha sido utilizada por otra persona');
        }
        
        // Hash de la contraseña
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Iniciar transacción
        $conn->begin_transaction();
        
        try {
            // Insertar usuario
            $fecha_registro = date('Y-m-d H:i:s');
            $fecha_corte = date('Y-m-d', strtotime('+1 year')); // 1 año de corte
            
            $stmt = $conn->prepare("INSERT INTO usuarios (correo, pass, tipo, fechaRegistro, fechaCorte, estatus) VALUES (?, ?, 2, ?, ?, 1)");
            $stmt->bind_param("ssss", $correo, $password_hash, $fecha_registro, $fecha_corte);
            $stmt->execute();
            $id_usuario_nuevo = $stmt->insert_id;
            
            // Insertar cliente
            $stmt = $conn->prepare("INSERT INTO clientes (nombre, apellido, id_usuario) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $nombre, $apellido, $id_usuario_nuevo);
            $stmt->execute();
            
            // Marcar invitación como usada y asignar referido
            $stmt = $conn->prepare("UPDATE invitaciones SET usada = 1, id_referido = ? WHERE id = ?");
            $stmt->bind_param("ii", $id_usuario_nuevo, $id_invitacion);
            $stmt->execute();
            
            $conn->commit();
            
            // Redirigir al login con mensaje de éxito
            header("Location: login?registro=exitoso&invitacion=1");
            exit();
            
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
    <title>Unirse a Exzento - Invitación</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #2563EB;
            min-height: 100vh;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-weight: 700;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }
        
        .btn-primary {
            background: #2563EB;
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 700;
        }
        
        .btn-primary:hover {
            background: #1D4ED8;
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
        }
        
        .form-control:focus {
            border-color: #2563EB;
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.25);
        }
        
        .invitation-banner {
            background: #2563EB;
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .logo img {
            height: 56px;
            width: auto;
        }
        
        .security-notice {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="text-center mb-4">
                    <div class="logo">
                        <img src="/assets/img/logo.jpeg" alt="Exzento">
                    </div>
                    <p class="text-white-50 mt-2">La mejor plataforma de facturación</p>
                </div>
                
                <div class="card">
                    <div class="card-body p-4">
                        <?php if ($invitacion_valida): ?>
                            <!-- Banner de invitación -->
                            <div class="invitation-banner text-center">
                                <h5><i class="fas fa-gift me-2"></i>¡Has sido invitado!</h5>
                                <p class="mb-0"><?php echo htmlspecialchars($nombre_invitador); ?> te invita a unirte a Exzento</p>
                                <?php if ($mensaje_invitacion): ?>
                                    <div class="mt-3 p-3 bg-white bg-opacity-25 rounded">
                                        <em>"<?php echo htmlspecialchars($mensaje_invitacion); ?>"</em>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Aviso de seguridad -->
                            <div class="security-notice">
                                <h6><i class="fas fa-shield-alt me-2"></i>Seguridad</h6>
                                <p class="mb-0 small">Este link de invitación es único y solo puede ser usado una vez. Una vez que te registres, el link quedará invalidado.</p>
                            </div>
                            
                            <!-- Formulario de registro -->
                            <h4 class="text-center mb-4">Crear tu cuenta</h4>
                            
                            <?php if ($error): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="nombre" class="form-label">
                                            <i class="fas fa-user me-2"></i>Nombre
                                        </label>
                                        <input 
                                            type="text" 
                                            class="form-control" 
                                            id="nombre" 
                                            name="nombre" 
                                            value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>"
                                            required
                                        >
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="apellido" class="form-label">
                                            <i class="fas fa-user me-2"></i>Apellido
                                        </label>
                                        <input 
                                            type="text" 
                                            class="form-control" 
                                            id="apellido" 
                                            name="apellido" 
                                            value="<?php echo htmlspecialchars($_POST['apellido'] ?? ''); ?>"
                                            required
                                        >
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="correo" class="form-label">
                                        <i class="fas fa-envelope me-2"></i>Correo electrónico
                                    </label>
                                    <input 
                                        type="email" 
                                        class="form-control" 
                                        id="correo" 
                                        name="correo" 
                                        value="<?php echo htmlspecialchars($_POST['correo'] ?? ''); ?>"
                                        required
                                    >
                                    <div class="form-text">Este será tu usuario para iniciar sesión</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label">
                                        <i class="fas fa-lock me-2"></i>Contraseña
                                    </label>
                                    <input 
                                        type="password" 
                                        class="form-control" 
                                        id="password" 
                                        name="password" 
                                        required
                                        minlength="6"
                                    >
                                    <div class="form-text">Mínimo 6 caracteres</div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="confirm_password" class="form-label">
                                        <i class="fas fa-lock me-2"></i>Confirmar contraseña
                                    </label>
                                    <input 
                                        type="password" 
                                        class="form-control" 
                                        id="confirm_password" 
                                        name="confirm_password" 
                                        required
                                    >
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-user-plus me-2"></i>Crear cuenta
                                    </button>
                                </div>
                            </form>
                            
                            <div class="text-center mt-4">
                                <p class="text-muted">
                                    ¿Ya tienes una cuenta? 
                                    <a href="/pages/login.php" class="text-decoration-none">Iniciar sesión</a>
                                </p>
                            </div>
                            
                        <?php else: ?>
                            <!-- Error de invitación -->
                            <div class="text-center">
                                <div class="mb-4">
                                    <i class="fas fa-exclamation-triangle text-warning" style="font-size: 4rem;"></i>
                                </div>
                                <h4 class="text-danger">Link de invitación inválido</h4>
                                <p class="text-muted">
                                    <?php echo htmlspecialchars($error); ?>
                                </p>
                                <div class="mt-4">
                                    <a href="/pages/login.php" class="btn btn-primary">
                                        <i class="fas fa-sign-in-alt me-2"></i>Ir al login
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Información adicional -->
                <div class="text-center mt-4">
                    <p class="text-white-50">
                        <small>
                            Al crear tu cuenta, aceptas nuestros 
                            <a href="#" class="text-white">Términos y Condiciones</a> y 
                            <a href="#" class="text-white">Política de Privacidad</a>
                        </small>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validar que las contraseñas coincidan
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Las contraseñas no coinciden');
            } else {
                this.setCustomValidity('');
            }
        });
        
        document.getElementById('password').addEventListener('input', function() {
            const confirmPassword = document.getElementById('confirm_password');
            if (confirmPassword.value) {
                confirmPassword.dispatchEvent(new Event('input'));
            }
        });
        
        // Prevenir envío múltiple del formulario
        document.querySelector('form').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creando cuenta...';
        });
    </script>
</body>
</html> 