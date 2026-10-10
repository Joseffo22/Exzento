<?php
/**
 * Helpers compartidos para comercio y estado de tickets.
 */

if (!function_exists('exzento_limpiar_nombre_comercio')) {
    function exzento_limpiar_nombre_comercio(?string $descripcion): string
    {
        $raw = trim((string) $descripcion);
        if ($raw === '') {
            return '';
        }

        if (preg_match(
            '/^(.*?)(?:\s*[-–—]\s*)?Recibida manualmente el\s+.+$/isu',
            $raw,
            $m
        )) {
            $raw = trim($m[1]);
        }

        return $raw;
    }
}

if (!function_exists('exzento_parse_recepcion_manual')) {
    /**
     * @return array{comercio: string, manual: ?array{cuando: string, canal: string}}
     */
    function exzento_parse_recepcion_manual(?string $descripcion, ?array $ticket = null): array
    {
        $raw = trim((string) $descripcion);
        $comercio = exzento_limpiar_nombre_comercio($raw);
        $manual = null;

        if ($ticket) {
            $estado = $ticket['estado_factura'] ?? null;
            $canalCol = trim((string) ($ticket['canal_recepcion'] ?? ''));
            $fechaCol = $ticket['fecha_recepcion_manual'] ?? null;
            if ($estado === 'recibida_manual' || $canalCol !== '' || $fechaCol) {
                $cuando = '';
                if ($fechaCol) {
                    $ts = strtotime((string) $fechaCol);
                    $cuando = $ts ? date('d/m/Y H:i', $ts) : (string) $fechaCol;
                }
                $manual = [
                    'cuando' => $cuando,
                    'canal' => $canalCol !== '' ? $canalCol : 'Otro',
                ];
            }
        }

        if ($manual === null && $raw !== '' && preg_match(
            '/^(.*?)(?:\s*[-–—]\s*)?Recibida manualmente el\s+(.+?)\s+por:\s*(.+)$/isu',
            $raw,
            $m
        )) {
            $comercio = trim($m[1]);
            $manual = [
                'cuando' => trim($m[2]),
                'canal' => trim($m[3]),
            ];
        }

        return [
            'comercio' => $comercio,
            'manual' => $manual,
        ];
    }
}

if (!function_exists('exzento_ticket_columnas_estado')) {
    function exzento_ticket_columnas_estado(?mysqli $conn = null): bool
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        if (!$conn) {
            global $conn;
        }
        if (!$conn) {
            $cache = false;
            return false;
        }
        try {
            $res = $conn->query("SHOW COLUMNS FROM ticket LIKE 'estado_factura'");
            $cache = $res && $res->num_rows > 0;
        } catch (Throwable $e) {
            $cache = false;
        }
        return $cache;
    }
}

if (!function_exists('exzento_ticket_es_recibida_manual')) {
    function exzento_ticket_es_recibida_manual(array $ticket): bool
    {
        if (($ticket['estado_factura'] ?? '') === 'recibida_manual') {
            return true;
        }
        if (!empty($ticket['canal_recepcion']) || !empty($ticket['fecha_recepcion_manual'])) {
            return true;
        }
        return stripos((string) ($ticket['descripcion'] ?? ''), 'Recibida manualmente') !== false;
    }
}

if (!function_exists('exzento_ticket_estado')) {
    /**
     * @return 'facturada'|'recibida_manual'|'pendiente'
     */
    function exzento_ticket_estado(array $ticket): string
    {
        $numFacturas = (int) ($ticket['num_facturas'] ?? 0);
        if ($numFacturas > 0 || ($ticket['estado_factura'] ?? '') === 'facturada' || !empty($ticket['factura_id'])) {
            return 'facturada';
        }
        if (exzento_ticket_es_recibida_manual($ticket)) {
            return 'recibida_manual';
        }
        return 'pendiente';
    }
}

if (!function_exists('exzento_sql_es_recibida_manual')) {
    /** Fragmento SQL (alias t) — compatible con o sin columnas nuevas. */
    function exzento_sql_es_recibida_manual(?mysqli $conn = null): string
    {
        if (exzento_ticket_columnas_estado($conn)) {
            return "(t.estado_factura = 'recibida_manual'
                OR COALESCE(t.descripcion, '') LIKE '%Recibida manualmente%')";
        }
        return "COALESCE(t.descripcion, '') LIKE '%Recibida manualmente%'";
    }
}

if (!function_exists('exzento_sql_es_pendiente')) {
    function exzento_sql_es_pendiente(?mysqli $conn = null): string
    {
        $manual = exzento_sql_es_recibida_manual($conn);
        return "(NOT EXISTS (SELECT 1 FROM facturas f WHERE f.ticket_id = t.id) AND NOT {$manual})";
    }
}

if (!function_exists('exzento_canal_corto')) {
    function exzento_canal_corto(string $canal): string
    {
        $c = mb_strtolower(trim($canal), 'UTF-8');
        if (str_contains($c, 'correo') || str_contains($c, 'email')) {
            return 'correo';
        }
        if (str_contains($c, 'whatsapp')) {
            return 'whatsapp';
        }
        if (str_contains($c, 'físic') || str_contains($c, 'fisic')) {
            return 'físico';
        }
        return $canal !== '' ? $canal : 'otro';
    }
}
