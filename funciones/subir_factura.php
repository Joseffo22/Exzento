<?php
require('../assets/php/conexiones/conexionMySqli.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /');
    exit;
}

$nombre_archivo = trim($_POST['nombre_archivo'] ?? '');
$ticket_id = trim($_POST['ticket_id'] ?? '');

if (empty($nombre_archivo) || empty($ticket_id)) {
    die('Datos incompletos.');
}

$pdf = $_FILES['archivo_pdf'] ?? null;
$xml = $_FILES['archivo_xml'] ?? null;
$errores = [];

if (!$pdf || $pdf['error'] !== UPLOAD_ERR_OK) {
    $errores[] = 'El archivo PDF es requerido.';
}
if (!$xml || $xml['error'] !== UPLOAD_ERR_OK) {
    $errores[] = 'El archivo XML es requerido.';
}

$ext_pdf = strtolower(pathinfo($pdf['name'] ?? '', PATHINFO_EXTENSION));
$ext_xml = strtolower(pathinfo($xml['name'] ?? '', PATHINFO_EXTENSION));

if ($ext_pdf !== 'pdf') {
    $errores[] = 'El archivo PDF no es válido.';
}
if ($ext_xml !== 'xml') {
    $errores[] = 'El archivo XML no es válido.';
}

if (!empty($errores)) {
    die(implode(' ', $errores));
}

$carpeta = __DIR__ . '/../archivos/facturas/';
if (!is_dir($carpeta)) {
    mkdir($carpeta, 0755, true);
}

$nombre_pdf = uniqid('pdf_') . '.pdf';
$nombre_xml = uniqid('xml_') . '.xml';
$ruta_pdf = $carpeta . $nombre_pdf;
$ruta_xml = $carpeta . $nombre_xml;

if (!move_uploaded_file($pdf['tmp_name'], $ruta_pdf) || !move_uploaded_file($xml['tmp_name'], $ruta_xml)) {
    die('Error al guardar los archivos.');
}

$ruta_pdf_db = 'archivos/facturas/' . $nombre_pdf;
$ruta_xml_db = 'archivos/facturas/' . $nombre_xml;

$stmt = $conn->prepare('INSERT INTO facturas (ticket_id, nombre_archivo, archivo_pdf, archivo_xml, creado_en) VALUES (?, ?, ?, ?, NOW())');
$stmt->bind_param('ssss', $ticket_id, $nombre_archivo, $ruta_pdf_db, $ruta_xml_db);

if (!$stmt->execute()) {
    die('Error al guardar en la base de datos.');
}
$stmt->close();

@$conn->query("UPDATE ticket SET estado_factura = 'facturada' WHERE id = " . (int)$ticket_id);

header("Location: /visualizar-ticket?id={$ticket_id}&enviado=1");
exit;
