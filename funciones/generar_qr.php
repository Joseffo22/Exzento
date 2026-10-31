<?php
require_once __DIR__ . '/../assets/php/config.php';
require_once __DIR__ . '/../assets/libs/phpqrcode/qrlib.php';

$id_ticket = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id_ticket <= 0) {
    die('ID no válido.');
}

$url = site_url("visualizar-ticket?id=$id_ticket");
$directorioQR = exzento_archivos_ensure_dir('qrs');
$archivoQR = $directorioQR . DIRECTORY_SEPARATOR . 'qr_' . $id_ticket . '.png';

QRcode::png($url, $archivoQR, QR_ECLEVEL_H, 6);
header('Location: /visualizar-ticket?id=' . $id_ticket);
exit;
