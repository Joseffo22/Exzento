<?php
require_once('../assets/php/conexiones/conexionMySqli.php');
session_start();

// Debug de sesión
echo "<!-- Debug Sesión: ";
print_r($_SESSION);
echo " -->\n";

// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    die("Error: No hay sesión de usuario activa");
}

// Debug de POST
echo "<!-- Debug POST: ";
print_r($_POST);
echo " -->\n";

// Debug de FILES
echo "<!-- Debug FILES: ";
print_r($_FILES);
echo " -->\n";

// Verificar si es una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Error: Método no permitido");
}

// Función para validar y procesar el archivo de constancia
function procesarConstancia($archivo, $id_usuario) {
    // Debug de archivo
    echo "<!-- Debug Archivo: ";
    print_r($archivo);
    echo " -->\n";

    // Verificar si se subió un archivo
    if (!isset($archivo['constancia']) || $archivo['constancia']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Por favor suba su constancia de situación fiscal');
    }

    $file = $archivo['constancia'];
    
    // Debug de tipo de archivo
    echo "<!-- Debug Tipo de archivo: " . $file['type'] . " -->\n";
    
    // Validar tipo de archivo
    $allowed_types = ['application/pdf'];
    if (!in_array($file['type'], $allowed_types)) {
        throw new Exception('Solo se permiten archivos PDF');
    }

    // Validar tamaño (máximo 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('El archivo es demasiado grande. Máximo 5MB');
    }

    // Crear directorio si no existe
    $upload_dir = __DIR__ . '/../archivos/constancias/';
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            throw new Exception('Error al crear el directorio de constancias');
        }
    }

    // Generar nombre único para el archivo
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $nombre_archivo = 'constancia_' . $id_usuario . '_' . time() . '.' . $extension;
    $ruta_completa = $upload_dir . $nombre_archivo;

    // Debug de ruta
    echo "<!-- Debug Ruta archivo: " . $ruta_completa . " -->\n";

    // Mover el archivo
    if (!move_uploaded_file($file['tmp_name'], $ruta_completa)) {
        throw new Exception('Error al guardar el archivo: ' . error_get_last()['message']);
    }

    return 'archivos/constancias/' . $nombre_archivo;
}

try {
    // Debug de conexión
    if (!$conn) {
        throw new Exception("Error de conexión: " . mysqli_connect_error());
    }

    // Validar campos requeridos
    $campos_requeridos = ['razonSocial', 'rfc', 'regimen', 'uso_cfdi', 'correo', 'telefono', 
                         'calle', 'colonia', 'codigoPostal', 'municipio', 'estado'];
    
    foreach ($campos_requeridos as $campo) {
        if (empty($_POST[$campo])) {
            throw new Exception("El campo {$campo} es requerido");
        }
    }

    // Debug de campos validados
    echo "<!-- Debug Campos validados correctamente -->\n";

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

    // Procesar constancia
    $ruta_constancia = procesarConstancia($_FILES, $_SESSION['id_usuario']);

    // Debug de ruta constancia
    echo "<!-- Debug Ruta constancia: " . $ruta_constancia . " -->\n";

    // Preparar la consulta SQL para inserción
    $query = "INSERT INTO datosFiscales (id_usuario, razonSocial, rfc, regimen, usoFavorito, 
              correo, telefono, calle, colonia, codigoPostal, municipio, estado, constancia) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    // Debug de query
    echo "<!-- Debug Query: " . $query . " -->\n";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Error en la preparación de la consulta: " . $conn->error);
    }

    $stmt->bind_param("isssissssssss", 
        $_SESSION['id_usuario'], $_POST['razonSocial'], $_POST['rfc'], $_POST['regimen'], 
        $_POST['uso_cfdi'], $_POST['correo'], $_POST['telefono'], $_POST['calle'], 
        $_POST['colonia'], $_POST['codigoPostal'], $_POST['municipio'], $_POST['estado'],
        $ruta_constancia
    );

    // Debug de parámetros
    echo "<!-- Debug Parámetros: ";
    echo "id_usuario: " . $_SESSION['id_usuario'] . ", ";
    echo "razonSocial: " . $_POST['razonSocial'] . ", ";
    echo "rfc: " . $_POST['rfc'] . ", ";
    echo "regimen: " . $_POST['regimen'] . ", ";
    echo "uso_cfdi: " . $_POST['uso_cfdi'] . ", ";
    echo "correo: " . $_POST['correo'] . ", ";
    echo "telefono: " . $_POST['telefono'] . ", ";
    echo "calle: " . $_POST['calle'] . ", ";
    echo "colonia: " . $_POST['colonia'] . ", ";
    echo "codigoPostal: " . $_POST['codigoPostal'] . ", ";
    echo "municipio: " . $_POST['municipio'] . ", ";
    echo "estado: " . $_POST['estado'] . ", ";
    echo "constancia: " . $ruta_constancia;
    echo " -->\n";

    // Ejecutar la consulta
    if (!$stmt->execute()) {
        throw new Exception("Error al guardar los datos: " . $stmt->error);
    }

    // Debug de inserción exitosa
    echo "<!-- Debug: Inserción exitosa -->\n";

    // Redirigir con mensaje de éxito
    $_SESSION['mensaje'] = "Datos fiscales registrados correctamente";
    header('Location: ../index');
    exit();

} catch (Exception $e) {
    // Debug de error
    echo "<!-- Debug Error: " . $e->getMessage() . " -->\n";
    echo "<!-- Debug Error Trace: " . $e->getTraceAsString() . " -->\n";
    
    // Guardar error en sesión y redirigir
    $_SESSION['error'] = $e->getMessage();
    header('Location: ../registrar-datos-fiscales');
    exit();
}
?> 