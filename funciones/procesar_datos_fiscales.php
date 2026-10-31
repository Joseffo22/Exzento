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

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: /registrar-datos-fiscales');
    exit();
}

try {
    if (!$conn) {
        throw new Exception('Error de conexión a la base de datos');
    }

    exzento_detectar_fallo_post_upload();

    $campos_requeridos = [
        'razonSocial', 'rfc', 'regimen', 'uso_cfdi', 'correo', 'telefono',
        'calle', 'colonia', 'codigoPostal', 'municipio', 'estado',
    ];
    foreach ($campos_requeridos as $campo) {
        if (!isset($_POST[$campo]) || trim((string) $_POST[$campo]) === '') {
            throw new Exception("El campo {$campo} es requerido");
        }
    }

    if (
        !isset($_FILES['constancia'])
        || !is_array($_FILES['constancia'])
        || (string) ($_FILES['constancia']['name'] ?? '') === ''
        || (int) ($_FILES['constancia']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE
    ) {
        throw new Exception('Por favor suba su constancia de situación fiscal en PDF');
    }

    $id_usuario = (int) $_SESSION['id_usuario'];
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

    $ruta_constancia = exzento_guardar_constancia($_FILES['constancia'], $id_usuario);

    $query = 'INSERT INTO datosFiscales (
                id_usuario, razonSocial, rfc, regimen, usoFavorito,
                correo, telefono, calle, colonia, codigoPostal, municipio, estado, constancia
              ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Error en la preparación de la consulta: ' . $conn->error);
    }

    $stmt->bind_param(
        'issiissssssss',
        $id_usuario,
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
        $ruta_constancia
    );

    if (!$stmt->execute()) {
        throw new Exception('Error al guardar los datos: ' . $stmt->error);
    }
    $stmt->close();

    $_SESSION['mensaje'] = 'Datos fiscales registrados correctamente. Constancia: ' . $ruta_constancia;
    header('Location: /informacion-personal');
    exit();
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: /registrar-datos-fiscales');
    exit();
}
