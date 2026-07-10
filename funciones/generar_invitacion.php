<?php
session_start();
require('../assets/php/conexiones/conexionMySqli.php');
header('Content-Type: application/json');

// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

// Verificar que la tabla de invitaciones exista
function verificarTablaInvitaciones($conn) {
    $result = $conn->query("SHOW TABLES LIKE 'invitaciones'");
    if ($result->num_rows === 0) {
        $sql = "CREATE TABLE `invitaciones` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `id_usuario` int(11) NOT NULL COMMENT 'Usuario que envía la invitación',
            `usada` int(11) NOT NULL DEFAULT 0 COMMENT '0 = no usada, 1 = usada',
            `fecha_generacion` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Fecha y hora de generación',
            `id_referido` int(11) NOT NULL DEFAULT 0 COMMENT 'ID del usuario referido (se actualiza cuando se registra)',
            `mensaje` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Mensaje personalizado de la invitación',
            PRIMARY KEY (`id`),
            KEY `idx_id_usuario` (`id_usuario`),
            KEY `idx_usada` (`usada`),
            KEY `idx_id_referido` (`id_referido`),
            KEY `idx_fecha_generacion` (`fecha_generacion`),
            KEY `idx_invitacion_usuario` (`id_usuario`, `usada`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabla para gestionar invitaciones de usuarios'";
        
        return $conn->query($sql);
    }
    return true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verificar tabla de invitaciones
        if (!verificarTablaInvitaciones($conn)) {
            throw new Exception('Error al verificar la tabla de invitaciones');
        }
        
        $id_usuario = $_SESSION['id_usuario'];
        $mensaje = trim($_POST['mensaje'] ?? '');
        
        // Validar mensaje
        if (empty($mensaje)) {
            throw new Exception('El mensaje es requerido');
        }
        
        if (strlen($mensaje) > 200) {
            throw new Exception('El mensaje no puede exceder 200 caracteres');
        }
        
        // Obtener fecha y hora actual
        $fecha_generacion = date('Y-m-d H:i:s');
        
        // Insertar la invitación en la base de datos
        $query = "INSERT INTO invitaciones (id_usuario, usada, fecha_generacion, id_referido, mensaje) 
                  VALUES (?, 0, ?, 0, ?)";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Error en la preparación de la consulta: " . $conn->error);
        }
        
        $stmt->bind_param("iss", $id_usuario, $fecha_generacion, $mensaje);
        
        if (!$stmt->execute()) {
            throw new Exception("Error al generar la invitación: " . $stmt->error);
        }
        
        $id_invitacion = $stmt->insert_id;
        
        // Generar el link de invitación
        $link_invitacion = site_url("unirse?id=" . $id_invitacion);
        
        echo json_encode([
            'success' => true,
            'id_invitacion' => $id_invitacion,
            'link_invitacion' => $link_invitacion,
            'mensaje' => 'Invitación generada exitosamente'
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Método no permitido'
    ]);
}
?> 