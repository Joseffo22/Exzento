<?php
if (isset($_SESSION['tipoUsuario'])) {
    header("Location: index");
    exit;
}else{
 
require('assets/php/conexiones/conexionMySqli.php');

// Función para generar token seguro
function generarTokenSeguro($longitud = 64) {
    return bin2hex(random_bytes($longitud / 2)); // 64 caracteres
}

// Verificar si viene de un registro exitoso por invitación
$registro_exitoso = isset($_GET['registro']) && $_GET['registro'] === 'exitoso';
$invitacion = isset($_GET['invitacion']) && $_GET['invitacion'] === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $clave = $_POST['clave'] ?? '';
    $deviceId = $_POST['device_id'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'];
    $userAgent = $_SERVER['HTTP_USER_AGENT'];

    // Buscar al usuario
    $stmt = $conn->prepare("SELECT id, clave FROM usuarios WHERE usuario = ?");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $stmt->bind_result($id_usuario, $clave_hash);

    if ($stmt->fetch()) {
        // Validar contraseña (usa password_verify si está hasheada)
        if ($clave === $clave_hash) {
            $token = generarTokenSeguro();
            $expiracion = date('Y-m-d H:i:s', time() + 86400); // 24 horas

            // Guardar sesión en la BD
            $insert = $conn->prepare("INSERT INTO sesiones 
                (id_usuario, token, expiracion, user_agent, ip, device_id, estatus) 
                VALUES (?, ?, ?, ?, ?, ?, 1)");
            $insert->bind_param("isssss", $id_usuario, $token, $expiracion, $userAgent, $ip, $deviceId);
            $insert->execute();

            // Guardar cookies
            setcookie("token", $token, time() + 86400, "/", "", false, true);
            setcookie("device_id", $deviceId, time() + 86400, "/", "", false, false);

            $_SESSION['id_usuario'] = $id_usuario;
            header("Location: index.php");
            exit;
        } else {
            $error = "Contraseña incorrecta.";
        }
    } else {
        $error = "Usuario no encontrado.";
    }

    $stmt->close();
}
?>

<div class="login-section">
    <div class="login-container">
        <div class="login-header">
            <img src="/assets/img/logo.jpeg" alt="Exzento" class="login-logo">
            <h2>Bienvenido</h2>
            <p>Ingresa tus credenciales para continuar</p>
        </div>

        <?php if ($registro_exitoso && $invitacion): ?>
        <div class="welcome-message show">
            <div class="welcome-icon">
                <i class="fas fa-heart"></i>
            </div>
            <h4>¡Te damos la bienvenida!</h4>
            <p>Tu cuenta ha sido creada exitosamente. Inicia sesión para comenzar esta increíble historia con Exzento.</p>
            <div class="welcome-features">
                <div class="feature-item">
                    <i class="fas fa-check-circle"></i>
                    <span>Facturación simplificada</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-check-circle"></i>
                    <span>Gestión de tickets</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-check-circle"></i>
                    <span>Reportes detallados</span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
        <div class="error-message show">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="funciones/login.php">
            <div class="form-floating">
                <input type="email" class="form-control" id="correo" name="correo" placeholder="Correo electrónico" required>
                <label for="correo"><i class="fas fa-envelope me-2"></i>Correo electrónico</label>
            </div>
            
            <div class="form-floating">
                <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" required>
                <label for="password"><i class="fas fa-lock me-2"></i>Contraseña</label>
            </div>

            <input type="hidden" name="device_id" id="device_id">
            
            <button type="submit" class="btn btn-login text-white">
                <i class="fas fa-sign-in-alt me-2"></i>
                Iniciar Sesión
            </button>
        </form>
    </div>
</div>

<style>
.welcome-message {
    background: #2563EB;
    color: #FFFFFF;
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 25px;
    text-align: center;
    box-shadow: 0 10px 30px rgba(37, 99, 235, 0.3);
    animation: slideInDown 0.6s ease-out;
}

.welcome-icon {
    font-size: 3rem;
    margin-bottom: 15px;
    animation: pulse 2s infinite;
}

.welcome-message h4 {
    margin-bottom: 10px;
    font-weight: bold;
}

.welcome-message p {
    margin-bottom: 20px;
    opacity: 0.9;
    font-size: 1.1rem;
}

.welcome-features {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 20px;
}

.feature-item {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    background: rgba(255, 255, 255, 0.2);
    padding: 8px 15px;
    border-radius: 25px;
    font-size: 0.9rem;
}

.feature-item i {
    color: #fff;
    font-size: 1rem;
}

@keyframes slideInDown {
    from {
        transform: translateY(-30px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

@keyframes pulse {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.1);
    }
    100% {
        transform: scale(1);
    }
}

/* Responsive para móviles */
@media (max-width: 768px) {
    .welcome-features {
        flex-direction: column;
    }
    
    .feature-item {
        font-size: 0.8rem;
        padding: 6px 12px;
    }
}
</style>

<script>
    function generarDeviceId() {
        return btoa(navigator.userAgent + screen.width + screen.height + Math.random()).substring(0, 64);
    }

    function leerCookie(nombre) {
        let match = document.cookie.match(new RegExp('(^| )' + nombre + '=([^;]+)'));
        return match ? match[2] : null;
    }

    let deviceId = leerCookie('device_id');
    if (!deviceId) {
        deviceId = generarDeviceId();
        document.cookie = `device_id=${deviceId}; path=/; max-age=31536000; SameSite=Strict`;
    }

    document.getElementById('device_id').value = deviceId;
    
    // Auto-scroll al formulario si hay mensaje de bienvenida
    <?php if ($registro_exitoso && $invitacion): ?>
    setTimeout(function() {
        document.querySelector('form').scrollIntoView({ 
            behavior: 'smooth', 
            block: 'center' 
        });
    }, 1000);
    <?php endif; ?>
</script>
<?php
}