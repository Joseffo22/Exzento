<?php
require_once '../assets/libs/phpqrcode/qrlib.php';

$id_ticket = $_GET['id'] ?? null;
if (!$id_ticket) {
    die("ID no válido.");
}

$url = site_url("visualizar-ticket?id=$id_ticket");
$directorioQR = __DIR__ . '/../archivos/qrs/';
$archivoQR = $directorioQR . "qr_" . $id_ticket . ".png";

// Crear la carpeta si no existe
if (!file_exists($directorioQR)) {
    mkdir($directorioQR, 0777, true);
}

// Generar QR
QRcode::png($url, $archivoQR, QR_ECLEVEL_H, 6);
header("Location: ../visualizar-ticket?id=$id_ticket");
exit;
