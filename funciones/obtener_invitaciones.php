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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $id_usuario = $_SESSION['id_usuario'];
        
        // Obtener todas las invitaciones del usuario
        $query = "SELECT i.*, 
                         c.nombre as nombre_referido, 
                         c.apellido as apellido_referido,
                         u.correo as correo_referido,
                         CASE 
                             WHEN i.usada = 1 THEN 'Usada'
                             ELSE 'Pendiente'
                         END as estado
                  FROM invitaciones i 
                  LEFT JOIN usuarios u ON i.id_referido = u.id
                  LEFT JOIN clientes c ON u.id = c.id_usuario
                  WHERE i.id_usuario = ?
                  ORDER BY i.fecha_generacion DESC";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Error en la preparación de la consulta: " . $conn->error);
        }
        
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $invitaciones = [];
        while ($row = $result->fetch_assoc()) {
            $invitaciones[] = [
                'id' => $row['id'],
                'mensaje' => $row['mensaje'],
                'fecha_generacion' => $row['fecha_generacion'],
                'usada' => $row['usada'],
                'estado' => $row['estado'],
                'link' => site_url("unirse?id=" . $row['id']),
                'referido' => $row['usada'] == 1 ? [
                    'nombre' => $row['nombre_referido'],
                    'apellido' => $row['apellido_referido'],
                    'correo' => $row['correo_referido']
                ] : null
            ];
        }
        
        echo json_encode([
            'success' => true,
            'invitaciones' => $invitaciones,
            'total' => count($invitaciones),
            'usadas' => array_sum(array_column($invitaciones, 'usada')),
            'pendientes' => count($invitaciones) - array_sum(array_column($invitaciones, 'usada'))
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