<?php
session_start();
require_once('../assets/php/conexiones/conexionMySqli.php');
require_once(__DIR__ . '/buscar_contacto_frecuente.php');
require_once(__DIR__ . '/comercio_helpers.php');

// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit;
}

// Función para validar y procesar la imagen
function procesarImagen($archivo) {
    $tiposPermitidos = ['image/jpeg', 'image/jpg', 'image/png'];
    $tamanoMaximo = 5 * 1024 * 1024; // 5MB en bytes
    
    // Verificar si se subió un archivo
    if (!isset($archivo) || $archivo['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Error al subir la imagen: ' . 
            ($archivo['error'] === UPLOAD_ERR_INI_SIZE || $archivo['error'] === UPLOAD_ERR_FORM_SIZE 
                ? 'La imagen excede el tamaño máximo permitido' 
                : 'Error al procesar la imagen'));
    }

    // Verificar tipo de archivo
    if (!in_array($archivo['type'], $tiposPermitidos)) {
        throw new Exception('Tipo de archivo no permitido. Solo se aceptan JPG, JPEG y PNG.');
    }

    // Verificar tamaño
    if ($archivo['size'] > $tamanoMaximo) {
        throw new Exception('La imagen excede el tamaño máximo de 5MB.');
    }

    // public_html/archivos/tickets
    require_once __DIR__ . '/../assets/php/config.php';
    $directorio = exzento_archivos_ensure_dir('tickets');

    // Generar nombre único para el archivo
    $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
    $nombreArchivo = uniqid('ticket_') . '.' . $extension;
    $rutaCompleta = $directorio . DIRECTORY_SEPARATOR . $nombreArchivo;

    // Mover el archivo
    if (!move_uploaded_file($archivo['tmp_name'], $rutaCompleta)) {
        throw new Exception('Error al guardar la imagen.');
    }

    return $nombreArchivo;
}

// Función para procesar foto tomada con la cámara
function procesarFotoCamara($datosBase64) {
    // Verificar que los datos no estén vacíos
    if (empty($datosBase64)) {
        return null;
    }

    // public_html/archivos/fotos_tickets
    require_once __DIR__ . '/../assets/php/config.php';
    $directorio = exzento_archivos_ensure_dir('fotos_tickets');

    // Generar nombre único para el archivo
    $nombreArchivo = uniqid('foto_ticket_') . '.jpg';
    $rutaCompleta = $directorio . DIRECTORY_SEPARATOR . $nombreArchivo;

    // Decodificar y guardar la imagen
    $datosImagen = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $datosBase64));
    
    if (file_put_contents($rutaCompleta, $datosImagen) === false) {
        throw new Exception('Error al guardar la foto de la cámara.');
    }

    return $nombreArchivo;
}

try {
    // Validar datos requeridos
    $camposRequeridos = ['monto', 'datos_fiscales', 'uso_cfdi', 'descripcion'];
    foreach ($camposRequeridos as $campo) {
        if (!isset($_POST[$campo]) || empty($_POST[$campo])) {
            throw new Exception("El campo {$campo} es requerido.");
        }
    }

    // Validar monto
    $monto = filter_var($_POST['monto'], FILTER_VALIDATE_FLOAT);
    if ($monto === false || $monto <= 0) {
        throw new Exception('El monto debe ser un número válido mayor a 0.');
    }

    // Procesar la imagen si se subió una
    $nombreImagen = null;
    if (isset($_FILES['imagen_ticket']) && $_FILES['imagen_ticket']['error'] !== UPLOAD_ERR_NO_FILE) {
        $nombreImagen = procesarImagen($_FILES['imagen_ticket']);
    }

    // Procesar foto de la cámara si se proporcionó
    $nombreFoto = null;
    if (isset($_POST['foto_camara']) && !empty($_POST['foto_camara'])) {
        $nombreFoto = procesarFotoCamara($_POST['foto_camara']);
    }

    // Obtener número de ticket (si se proporcionó)
    $numeroTicket = $_POST['numeroTicket'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $fecha = date('Y-m-d H:i:s');

    // Preparar la consulta SQL
    $query = "INSERT INTO ticket (
        id_cliente, 
        monto, 
        descripcion,
        usoCfdi,
        numeroTicket,
        fecha, 
        id_datos, 
        metodoPago,
        imagen_ticket,
        foto_ticket
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Error al preparar la consulta: ' . $conn->error);
    }

    // Vincular parámetros
    $stmt->bind_param(
        'idssssssss',
        $_SESSION['id_usuario'],
        $monto,
        $descripcion,
        $_POST['uso_cfdi'],
        $numeroTicket,
        $fecha,
        $_POST['datos_fiscales'],
        $_POST['metodopago'],
        $nombreImagen,
        $nombreFoto
    );

    // Ejecutar la consulta
    if (!$stmt->execute()) {
        throw new Exception('Error al guardar el ticket: ' . $stmt->error);
    }

    $idTicket = $stmt->insert_id;
    $stmt->close();

    // Vincular / guardar comercio favorito (contactos frecuentes)
    $nombreComercio = exzento_limpiar_nombre_comercio($descripcion);
    $idContacto = isset($_POST['id_contacto']) ? (int) $_POST['id_contacto'] : 0;
    $guardarFavorito = !empty($_POST['guardar_favorito']);
    $telefonoComercio = trim((string) ($_POST['telefono_comercio'] ?? ''));

    $contactoUsado = null;
    if ($idContacto > 0) {
        $misContactos = obtenerContactosFrecuentesUsuario($_SESSION['id_usuario']);
        foreach ($misContactos as $c) {
            if ((int) $c['id'] === $idContacto) {
                $contactoUsado = $c;
                break;
            }
        }
    }

    if (!$contactoUsado && $nombreComercio !== '') {
        $contactoUsado = buscarContactoPorNombre($nombreComercio, $_SESSION['id_usuario']);
    }

    if ($guardarFavorito && $telefonoComercio !== '' && $nombreComercio !== '') {
        $resultadoFav = agregarContactoFrecuente([
            'nombre_empresa' => $nombreComercio,
            'telefono' => $telefonoComercio,
            'categoria' => 'Comercio',
            'notas' => 'Guardado al generar ticket #' . $idTicket,
        ]);
        if (!empty($resultadoFav['success']) && !empty($resultadoFav['contacto_id'])) {
            $contactoUsado = [
                'id' => (int) $resultadoFav['contacto_id'],
                'nombre_empresa' => $nombreComercio,
                'telefono' => limpiarTelefono($telefonoComercio),
            ];
        }
    }

    if ($contactoUsado && !empty($contactoUsado['id'])) {
        actualizarFrecuenciaContacto((int) $contactoUsado['id']);
        registrarUsoContacto((int) $contactoUsado['id'], $contactoUsado, $idTicket);
    }

    // Redirigir al usuario con mensaje de éxito (string: las vistas usan htmlspecialchars)
    $_SESSION['mensaje'] = "Ticket #{$idTicket} generado exitosamente";
    header("Location: generar_qr.php?id={$idTicket}");
    exit;

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: /generar-ticket');
    exit;
} finally {
    // Cerrar la conexión
    if (isset($conn)) {
        $conn->close();
    }
}
?>
