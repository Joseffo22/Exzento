<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../assets/php/conexiones/conexionMySqli.php';

if (!isset($_SESSION['id_usuario'])) {
    header('Location: /login');
    exit();
}

function mensajeErrorSubida(int $codigo): string
{
    return match ($codigo) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'El archivo supera el tamaño máximo permitido',
        UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente. Intenta de nuevo',
        UPLOAD_ERR_NO_FILE => 'No se seleccionó ningún archivo',
        UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal del servidor',
        UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo en el servidor',
        UPLOAD_ERR_EXTENSION => 'Una extensión de PHP bloqueó la subida',
        default => 'Error al subir el archivo (código ' . $codigo . ')',
    };
}

function procesarConstancia(array $files, int $id_usuario): string
{
    if (!isset($files['constancia'])) {
        throw new Exception('No se recibió el archivo de constancia');
    }

    $file = $files['constancia'];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new Exception(mensajeErrorSubida((int) $file['error']));
    }

    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new Exception('El archivo es demasiado grande. Máximo 5MB');
    }

    $upload_dir = __DIR__ . '/../archivos/constancias/';
    if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true) && !is_dir($upload_dir)) {
        throw new Exception('Error al crear el directorio de constancias');
    }

    $mime_type = null;
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mime_type = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
        }
    }
    if (!$mime_type && function_exists('mime_content_type')) {
        $mime_type = mime_content_type($file['tmp_name']);
    }
    if (!$mime_type) {
        $mime_type = $file['type'] ?? '';
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_mimes = ['application/pdf', 'application/x-pdf', 'application/acrobat'];
    if ($extension !== 'pdf' || !in_array($mime_type, $allowed_mimes, true)) {
        throw new Exception('Solo se permiten archivos PDF');
    }

    $nombre_archivo = 'constancia_' . $id_usuario . '_' . time() . '.pdf';
    $ruta_completa = $upload_dir . $nombre_archivo;

    if (!is_uploaded_file($file['tmp_name']) || !move_uploaded_file($file['tmp_name'], $ruta_completa)) {
        throw new Exception('Error al guardar el archivo en el servidor');
    }

    return 'archivos/constancias/' . $nombre_archivo;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $id_usuario = (int) $_SESSION['id_usuario'];
    $id_datos = isset($_POST['id_datos']) ? (int) $_POST['id_datos'] : 0;

    if ($id_datos <= 0) {
        throw new Exception('ID de datos fiscales no proporcionado');
    }

    $query_verificar = 'SELECT id, constancia FROM datosFiscales WHERE id = ? AND id_usuario = ?';
    $stmt_verificar = $conn->prepare($query_verificar);
    if (!$stmt_verificar) {
        throw new Exception('Error al verificar datos fiscales: ' . $conn->error);
    }
    $stmt_verificar->bind_param('ii', $id_datos, $id_usuario);
    $stmt_verificar->execute();
    $resultado = $stmt_verificar->get_result();

    if ($resultado->num_rows === 0) {
        throw new Exception('Datos fiscales no encontrados o no autorizados');
    }

    $datos_actuales = $resultado->fetch_assoc();
    $constancia_actual = $datos_actuales['constancia'] ?? '';
    $stmt_verificar->close();

    $campos_requeridos = [
        'razonSocial', 'rfc', 'regimen', 'uso_cfdi', 'correo', 'telefono',
        'calle', 'colonia', 'codigoPostal', 'municipio', 'estado',
    ];

    foreach ($campos_requeridos as $campo) {
        if (!isset($_POST[$campo]) || trim((string) $_POST[$campo]) === '') {
            throw new Exception("El campo {$campo} es requerido");
        }
    }

    $razonSocial = trim($_POST['razonSocial']);
    $rfc = strtoupper(trim($_POST['rfc']));
    $regimen = (int) $_POST['regimen'];
    $uso_cfdi = (int) $_POST['uso_cfdi'];
    $correo = trim($_POST['correo']);
    $telefono = trim($_POST['telefono']);
    $calle = trim($_POST['calle']);
    $colonia = trim($_POST['colonia']);
    $codigoPostal = trim($_POST['codigoPostal']);
    $municipio = trim($_POST['municipio']);
    $estado = trim($_POST['estado']);

    if (!preg_match('/^[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}$/u', $rfc)) {
        throw new Exception('RFC inválido');
    }
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Correo electrónico inválido');
    }
    if (!preg_match('/^[0-9]{5}$/', $codigoPostal)) {
        throw new Exception('Código postal inválido');
    }
    if ($regimen <= 0 || $uso_cfdi <= 0) {
        throw new Exception('Selecciona régimen fiscal y uso de CFDI válidos');
    }

    $ruta_constancia = $constancia_actual;
    $huboNuevaConstancia = isset($_FILES['constancia'])
        && is_array($_FILES['constancia'])
        && ($_FILES['constancia']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

    if ($huboNuevaConstancia) {
        $ruta_constancia = procesarConstancia($_FILES, $id_usuario);

        if ($constancia_actual) {
            $rutaAnterior = __DIR__ . '/../' . ltrim($constancia_actual, '/');
            if (is_file($rutaAnterior)) {
                @unlink($rutaAnterior);
            }
        }
    }

    $query = 'UPDATE datosFiscales SET
              razonSocial = ?, rfc = ?, regimen = ?, usoFavorito = ?,
              correo = ?, telefono = ?, calle = ?, colonia = ?, codigoPostal = ?,
              municipio = ?, estado = ?, constancia = ?
              WHERE id = ? AND id_usuario = ?';

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Error en la preparación de la consulta: ' . $conn->error);
    }

    // s s i i s s s s s s s s i i
    $stmt->bind_param(
        'ssiissssssssii',
        $razonSocial,
        $rfc,
        $regimen,
        $uso_cfdi,
        $correo,
        $telefono,
        $calle,
        $colonia,
        $codigoPostal,
        $municipio,
        $estado,
        $ruta_constancia,
        $id_datos,
        $id_usuario
    );

    if (!$stmt->execute()) {
        throw new Exception('Error al actualizar los datos: ' . $stmt->error);
    }

    if ($stmt->affected_rows > 0 || $huboNuevaConstancia) {
        $_SESSION['mensaje'] = $huboNuevaConstancia
            ? 'Datos fiscales y constancia actualizados correctamente'
            : 'Datos fiscales actualizados correctamente';
    } else {
        $_SESSION['mensaje'] = 'No se realizaron cambios en los datos fiscales';
    }

    $stmt->close();
    header('Location: /informacion-personal');
    exit();
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    $idRedirect = isset($_POST['id_datos']) ? (int) $_POST['id_datos'] : 0;
    if ($idRedirect > 0) {
        header('Location: /editar-datos-fiscales?id=' . $idRedirect);
    } else {
        header('Location: /informacion-personal');
    }
    exit();
}
