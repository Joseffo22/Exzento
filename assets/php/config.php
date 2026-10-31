<?php
define('SITE_URL', 'https://exzento.com');

function site_url(string $path = ''): string
{
    $path = ltrim(str_replace('\\', '/', $path), '/');
    return $path ? SITE_URL . '/' . $path : SITE_URL;
}

/**
 * Raíz web pública (public_html). Todo lo servible debe vivir aquí.
 */
function exzento_document_root(): string
{
    $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
    if (is_string($docRoot) && $docRoot !== '') {
        $resolved = realpath($docRoot);
        if ($resolved !== false) {
            return $resolved;
        }
        return rtrim(str_replace('\\', '/', $docRoot), '/');
    }

    // Fallback: raíz del proyecto (carpeta que contiene assets/, funciones/, archivos/)
    $fallback = realpath(dirname(__DIR__, 2));
    return $fallback !== false ? $fallback : dirname(__DIR__, 2);
}

/**
 * Ruta absoluta en disco dentro de public_html/archivos[/subruta]
 */
function exzento_archivos_fs(string $subPath = ''): string
{
    $base = exzento_document_root() . DIRECTORY_SEPARATOR . 'archivos';
    $subPath = str_replace(['\\', '..'], ['/', ''], $subPath);
    $subPath = ltrim($subPath, '/');

    if ($subPath === '') {
        return $base;
    }

    // Si ya viene con "archivos/...", no duplicar
    if (stripos($subPath, 'archivos/') === 0) {
        $subPath = substr($subPath, strlen('archivos/'));
    }

    return $base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $subPath);
}

/**
 * Asegura carpeta bajo public_html/archivos y la deja escribible.
 */
function exzento_archivos_ensure_dir(string $subDir): string
{
    $dir = exzento_archivos_fs($subDir);
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new Exception('No se pudo crear la carpeta: ' . $subDir);
    }
    if (!is_writable($dir)) {
        @chmod($dir, 0755);
    }
    if (!is_writable($dir)) {
        throw new Exception('Sin permiso de escritura en: ' . $subDir);
    }
    return $dir;
}

/**
 * Normaliza rutas guardadas en BD (absolutas, ../, etc.) a ruta web pública.
 * Ej: "/home/.../public_html/archivos/constancias/a.pdf" → "archivos/constancias/a.pdf"
 */
function exzento_ruta_publica_archivo(?string $path): string
{
    if ($path === null) {
        return '';
    }

    $path = trim(str_replace('\\', '/', $path));
    if ($path === '') {
        return '';
    }

    // Quitar dominio si viniera completo
    $path = preg_replace('#^https?://[^/]+/#i', '', $path) ?? $path;

    // Si es absoluta del servidor, recortar desde /archivos/
    $pos = stripos($path, '/archivos/');
    if ($pos !== false) {
        return ltrim(substr($path, $pos + 1), '/'); // archivos/...
    }

    // Quitar ../ repetidos y barras iniciales
    $path = preg_replace('#^(\.\./)+#', '', $path) ?? $path;
    $path = ltrim($path, '/');

    if ($path === '' || stripos($path, 'archivos/') === 0) {
        return $path;
    }

    // Solo nombre de archivo suelto → asumir constancias (legado raro)
    if (strpos($path, '/') === false) {
        return 'archivos/constancias/' . $path;
    }

    return 'archivos/' . $path;
}

function exzento_url_archivo(?string $path): string
{
    $publico = exzento_ruta_publica_archivo($path);
    return $publico !== '' ? site_url($publico) : '';
}
