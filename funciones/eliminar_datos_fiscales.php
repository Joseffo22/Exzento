<?php
session_start();
require('../assets/php/conexiones/conexionMySqli.php');
require_once __DIR__ . '/helpers_constancia.php';
header('Content-Type: application/json');

// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id_usuario = $_SESSION['id_usuario'];
        $id_datos = $_POST['id'] ?? null;

        if (!$id_datos) {
            throw new Exception('ID de datos fiscales no proporcionado');
        }

        // Verificar que los datos fiscales pertenezcan al usuario
        $query_verificar = "SELECT id, constancia FROM datosFiscales WHERE id = ? AND id_usuario = ?";
        $stmt_verificar = $conn->prepare($query_verificar);
        $stmt_verificar->bind_param("ii", $id_datos, $id_usuario);
        $stmt_verificar->execute();
        $resultado = $stmt_verificar->get_result();

        if ($resultado->num_rows === 0) {
            throw new Exception('Datos fiscales no encontrados o no autorizados');
        }

        $datos = $resultado->fetch_assoc();
        $ruta_constancia = $datos['constancia'];

        // Eliminar el archivo dentro de public_html/archivos (no usar ../ fuera del docroot)
        exzento_eliminar_constancia_fs($ruta_constancia);

        // Eliminar el registro de la base de datos
        $query_eliminar = "DELETE FROM datosFiscales WHERE id = ? AND id_usuario = ?";
        $stmt_eliminar = $conn->prepare($query_eliminar);
        $stmt_eliminar->bind_param("ii", $id_datos, $id_usuario);

        if (!$stmt_eliminar->execute()) {
            throw new Exception("Error al eliminar los datos: " . $stmt_eliminar->error);
        }

        if ($stmt_eliminar->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Datos fiscales eliminados correctamente']);
        } else {
            throw new Exception('No se pudieron eliminar los datos fiscales');
        }

    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}
?> 