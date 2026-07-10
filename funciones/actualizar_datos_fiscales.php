<?php
require('../assets/php/conexiones/conexionMySqli.php');

// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

function procesarConstancia($file, $id_usuario) {
    if ($file['constancia']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Error al subir el archivo: ' . $file['constancia']['error']);
    }

    $upload_dir = __DIR__ . '/../archivos/constancias/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Validar tipo de archivo
    $allowed_types = ['application/pdf'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['constancia']['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime_type, $allowed_types)) {
        throw new Exception('Solo se permiten archivos PDF');
    }

    // Generar nombre único para el archivo
    $extension = pathinfo($file['constancia']['name'], PATHINFO_EXTENSION);
    $nombre_archivo = 'constancia_' . $id_usuario . '_' . time() . '.' . $extension;
    $ruta_completa = $upload_dir . $nombre_archivo;

    // Mover el archivo
    if (!move_uploaded_file($file['constancia']['tmp_name'], $ruta_completa)) {
        throw new Exception('Error al guardar el archivo');
    }

    return 'archivos/constancias/' . $nombre_archivo;
}

try {
    $id_usuario = $_SESSION['id_usuario'];
    $id_datos = $_POST['id_datos'] ?? null;

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

    $datos_actuales = $resultado->fetch_assoc();
    $constancia_actual = $datos_actuales['constancia'];

    // Validar campos requeridos
    $campos_requeridos = ['razonSocial', 'rfc', 'regimen', 'uso_cfdi', 'correo', 'telefono', 
                         'calle', 'colonia', 'codigoPostal', 'municipio', 'estado'];
    
    foreach ($campos_requeridos as $campo) {
        if (empty($_POST[$campo])) {
            throw new Exception("El campo {$campo} es requerido");
        }
    }

    // Validar RFC
    if (!preg_match('/^[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}$/', $_POST['rfc'])) {
        throw new Exception('RFC inválido');
    }

    // Validar correo
    if (!filter_var($_POST['correo'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Correo electrónico inválido');
    }

    // Validar código postal
    if (!preg_match('/^[0-9]{5}$/', $_POST['codigoPostal'])) {
        throw new Exception('Código postal inválido');
    }

    // Procesar nueva constancia si se subió
    $ruta_constancia = $constancia_actual; // Mantener la actual por defecto
    if (isset($_FILES['constancia']) && $_FILES['constancia']['error'] !== UPLOAD_ERR_NO_FILE) {
        $ruta_constancia = procesarConstancia($_FILES, $id_usuario);
        
        // Eliminar constancia anterior si existe
        if ($constancia_actual && file_exists('../' . $constancia_actual)) {
            unlink('../' . $constancia_actual);
        }
    }

    // Preparar la consulta SQL para actualización
    $query = "UPDATE datosFiscales SET 
              razonSocial = ?, rfc = ?, regimen = ?, usoFavorito = ?, 
              correo = ?, telefono = ?, calle = ?, colonia = ?, codigoPostal = ?, 
              municipio = ?, estado = ?, constancia = ? 
              WHERE id = ? AND id_usuario = ?";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Error en la preparación de la consulta: " . $conn->error);
    }

    $stmt->bind_param("sssiisssssssii", 
        $_POST['razonSocial'], $_POST['rfc'], $_POST['regimen'], $_POST['uso_cfdi'], 
        $_POST['correo'], $_POST['telefono'], $_POST['calle'], $_POST['colonia'], 
        $_POST['codigoPostal'], $_POST['municipio'], $_POST['estado'], $ruta_constancia,
        $id_datos, $id_usuario
    );

    // Ejecutar la consulta
    if (!$stmt->execute()) {
        throw new Exception("Error al actualizar los datos: " . $stmt->error);
    }

    if ($stmt->affected_rows > 0) {
        $_SESSION['mensaje'] = "Datos fiscales actualizados correctamente";
    } else {
        $_SESSION['mensaje'] = "No se realizaron cambios en los datos fiscales";
    }

    header('Location: ../cliente/informacion-personal.php');
    exit();

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: ../cliente/informacion-personal.php');
    exit();
}
?> 