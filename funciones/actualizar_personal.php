<?php
session_start();
require('../assets/php/conexiones/conexionMySqli.php');

// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id_usuario = $_SESSION['id_usuario'];
        $nombre = trim($_POST['nombre']);
        $apellido = trim($_POST['apellido']);

        // Validar campos
        if (empty($nombre) || empty($apellido)) {
            throw new Exception('Todos los campos son requeridos');
        }

        // Actualizar información del usuario
        $query = "UPDATE clientes SET nombre = ?, apellido = ? WHERE id_usuario = ?";
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Error en la preparación de la consulta: " . $conn->error);
        }

        $stmt->bind_param("ssi", $nombre, $apellido, $id_usuario);

        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar los datos: " . $stmt->error);
        }

        if ($stmt->affected_rows > 0) {
            $_SESSION['mensaje'] = "Tu nombre y apellido han sido actualizados: {$nombre} {$apellido}.";
        } else {
            $_SESSION['mensaje'] = 'No hubo cambios en tu nombre y apellido.';
        }

        header('Location: /informacion-personal#alertas-perfil');
        exit();

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header('Location: /informacion-personal#alertas-perfil');
        exit();
    }
} else {
    header('Location: /informacion-personal');
    exit();
}
?> 