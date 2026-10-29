<?php
require_once __DIR__ . '/../assets/php/config.php';
require_once __DIR__ . '/../assets/libs/phpqrcode/qrlib.php';

$id_ticket = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id_ticket <= 0) {
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
