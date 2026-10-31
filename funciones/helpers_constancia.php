<?php

require_once __DIR__ . '/../assets/php/config.php';

function exzento_mensaje_error_subida(int $codigo): string
{
    switch ($codigo) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return 'El archivo supera el tamaño máximo permitido por el servidor';
        case UPLOAD_ERR_PARTIAL:
            return 'El archivo se subió parcialmente. Intenta de nuevo';
        case UPLOAD_ERR_NO_FILE:
            return 'No se seleccionó ningún archivo';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Falta la carpeta temporal del servidor';
        case UPLOAD_ERR_CANT_WRITE:
            return 'No se pudo escribir el archivo en el servidor';
        case UPLOAD_ERR_EXTENSION:
            return 'Una extensión de PHP bloqueó la subida';
        default:
            return 'Error al subir el archivo (código ' . $codigo . ')';
    }
}

function exzento_archivo_parece_pdf(string $tmpPath, string $clientMime = ''): bool
{
    if (!is_file($tmpPath) || !is_readable($tmpPath)) {
        return false;
    }

    $fh = fopen($tmpPath, 'rb');
    if ($fh === false) {
        return false;
    }
    $head = fread($fh, 5);
    fclose($fh);
    if ($head === '%PDF-') {
        return true;
    }

    $mime = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mime = (string) finfo_file($finfo, $tmpPath);
            finfo_close($finfo);
        }
    }
    if ($mime === '' && function_exists('mime_content_type')) {
        $mime = (string) mime_content_type($tmpPath);
    }
    if ($mime === '') {
        $mime = $clientMime;
    }

    $allowed = [
        'application/pdf',
        'application/x-pdf',
        'application/acrobat',
        'application/octet-stream',
    ];

    return in_array($mime, $allowed, true);
}

/**
 * Guarda la constancia en public_html/archivos/constancias y devuelve ruta web para BD.
 *
 * @param array $file Elemento de $_FILES['constancia']
 */
function exzento_guardar_constancia(array $file, int $idUsuario): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new Exception(exzento_mensaje_error_subida((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE)));
    }

    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new Exception('El archivo temporal de subida no es válido');
    }

    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0) {
        throw new Exception('El archivo está vacío');
    }
    if ($size > 8 * 1024 * 1024) {
        throw new Exception('El archivo es demasiado grande. Máximo 8MB');
    }

    $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    if ($extension !== 'pdf') {
        throw new Exception('Solo se permiten archivos PDF (.pdf)');
    }

    if (!exzento_archivo_parece_pdf($file['tmp_name'], (string) ($file['type'] ?? ''))) {
        throw new Exception('El archivo no parece un PDF válido');
    }

    // Siempre dentro de DOCUMENT_ROOT/archivos/constancias (= public_html/archivos/...)
    $constanciasDir = exzento_archivos_ensure_dir('constancias');

    $nombre = 'constancia_' . $idUsuario . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.pdf';
    $destino = $constanciasDir . DIRECTORY_SEPARATOR . $nombre;

    if (!move_uploaded_file($file['tmp_name'], $destino)) {
        if (!@copy($file['tmp_name'], $destino)) {
            $last = error_get_last();
            $detalle = $last['message'] ?? 'sin detalle';
            throw new Exception('Error al guardar el PDF en el servidor: ' . $detalle);
        }
        @unlink($file['tmp_name']);
    }

    @chmod($destino, 0644);

    if (!is_file($destino) || filesize($destino) <= 0) {
        throw new Exception('El archivo no quedó guardado en public_html/archivos/constancias/');
    }

    return 'archivos/constancias/' . $nombre;
}

function exzento_eliminar_constancia_fs(?string $rutaBd): void
{
    $publico = exzento_ruta_publica_archivo($rutaBd);
    if ($publico === '') {
        return;
    }
    $abs = exzento_archivos_fs($publico);
    if (is_file($abs)) {
        @unlink($abs);
    }
}

function exzento_detectar_fallo_post_upload(): void
{
    $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($contentLength > 0 && empty($_POST) && empty($_FILES)) {
        throw new Exception(
            'El archivo es demasiado grande para el servidor (post_max_size/upload_max_filesize). Prueba un PDF más pequeño.'
        );
    }
}
