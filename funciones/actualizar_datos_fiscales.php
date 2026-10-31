<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../assets/php/conexiones/conexionMySqli.php';
require_once __DIR__ . '/../verificacion.php';
require_once __DIR__ . '/helpers_constancia.php';

estaAutenticado();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: /login');
    exit();
}

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        throw new Exception('Método no permitido');
    }

    exzento_detectar_fallo_post_upload();

    $id_usuario = (int) $_SESSION['id_usuario'];
    $id_datos = isset($_POST['id_datos']) ? (int) $_POST['id_datos'] : 0;
    if ($id_datos <= 0) {
        throw new Exception('ID de datos fiscales no proporcionado');
    }

    $stmt_verificar = $conn->prepare('SELECT id, constancia FROM datosFiscales WHERE id = ? AND id_usuario = ?');
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
    $constancia_actual = (string) ($datos_actuales['constancia'] ?? '');
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
        && (string) ($_FILES['constancia']['name'] ?? '') !== ''
        && (int) ($_FILES['constancia']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

    if ($huboNuevaConstancia) {
        $ruta_constancia = exzento_guardar_constancia($_FILES['constancia'], $id_usuario);
        if ($constancia_actual !== '' && $constancia_actual !== $ruta_constancia) {
            exzento_eliminar_constancia_fs($constancia_actual);
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
    $stmt->close();

    if ($huboNuevaConstancia) {
        $_SESSION['mensaje'] = 'Constancia actualizada correctamente (' . $ruta_constancia . ')';
    } else {
        $_SESSION['mensaje'] = 'Datos fiscales actualizados correctamente';
    }

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
