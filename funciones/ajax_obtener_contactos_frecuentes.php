<?php
/**
 * Endpoint AJAX para obtener todos los contactos frecuentes del usuario
 * Retorna JSON con la lista de contactos para llenar un select
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once('buscar_contacto_frecuente.php');

try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['id_usuario'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Sesión no válida',
            'contactos' => [],
            'total' => 0
        ]);
        exit;
    }

    $contactos = obtenerContactosFrecuentesUsuario($_SESSION['id_usuario']);

    echo json_encode([
        'success' => true,
        'message' => count($contactos) > 0
            ? 'Contactos obtenidos correctamente'
            : 'No hay contactos frecuentes disponibles',
        'contactos' => $contactos,
        'total' => count($contactos)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener contactos: ' . $e->getMessage(),
        'contactos' => [],
        'total' => 0
    ]);
}
?>
