<?php
define('SITE_URL', 'https://exzento.com');

function site_url(string $path = ''): string
{
    $path = ltrim($path, '/');
    return $path ? SITE_URL . '/' . $path : SITE_URL;
}
