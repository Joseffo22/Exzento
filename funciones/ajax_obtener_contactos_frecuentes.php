<?php
/**
 * Endpoint AJAX: contactos frecuentes del usuario autenticado.
 */

ob_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function exzento_ajax_contactos_respond(array $payload, int $status = 200): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(
        $payload,
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );
    exit;
}

try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['id_usuario'])) {
        exzento_ajax_contactos_respond([
            'success' => false,
            'message' => 'Sesión no válida',
            'contactos' => [],
            'total' => 0,
        ], 401);
    }

    require_once __DIR__ . '/buscar_contacto_frecuente.php';

    $usuarioId = (int) $_SESSION['id_usuario'];

    if (!exzento_tabla_contactos_existe()) {
        exzento_ajax_contactos_respond([
            'success' => true,
            'message' => 'Aún no hay tabla de contactos frecuentes configurada',
            'contactos' => [],
            'total' => 0,
        ]);
    }

    $contactos = obtenerContactosFrecuentesUsuario($usuarioId);

    exzento_ajax_contactos_respond([
        'success' => true,
        'message' => count($contactos) > 0
            ? 'Contactos obtenidos correctamente'
            : 'No hay contactos frecuentes disponibles',
        'contactos' => $contactos,
        'total' => count($contactos),
    ]);
} catch (Throwable $e) {
    exzento_ajax_contactos_respond([
        'success' => false,
        'message' => 'Error al obtener contactos',
        'contactos' => [],
        'total' => 0,
    ], 500);
}
