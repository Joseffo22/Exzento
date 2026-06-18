<?php

/**
 * Mensaje de WhatsApp para solicitar factura al comercio.
 * Incluye datos fiscales clave + link + instrucciones positivas.
 */
function construirMensajeWhatsAppFactura($idTicket, array $datos, string $urlTicket): string
{
    $rfc = trim($datos['rfc'] ?? '');
    $razonSocial = trim($datos['razonSocial'] ?? '');
    $regimen = trim($datos['regimen_fiscal'] ?? '');
    $claveCfdi = trim($datos['clave_cfdi'] ?? '');
    $descCfdi = trim($datos['descripcion_cfdi'] ?? '');
    $usoCfdi = $claveCfdi && $descCfdi ? "{$claveCfdi} – {$descCfdi}" : ($claveCfdi ?: $descCfdi);
    $formaPago = trim($datos['metodopago'] ?? $datos['metodoPago'] ?? '');
    $cp = trim($datos['codigoPostal'] ?? '');

    $lineas = [
        "📋 Solicitud de factura · Ticket #{$idTicket}",
        "📌 RFC: {$rfc}",
        "🏛️ Razón Social: {$razonSocial}",
        "🏦 Régimen: {$regimen}",
        "💼 Uso CFDI: {$usoCfdi}",
        "💳 Forma de pago: {$formaPago}",
        "📮 CP fiscal: {$cp}",
        "",
        "🌐 Para ver Ticket y Constancia, y compartirme la Factura, puedes hacerlo ingresando a este link:",
        $urlTicket,
        "",
        "Sube la factura en el link anterior, y quedará guardada en mi espacio.",
        "",
        "🔁 ¿No puedes abrir el link? Contáctame y te ayudaré a resolverlo.",
    ];

    return implode("\n", $lineas);
}
