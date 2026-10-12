<?php
/**
 * Portada del DOF con diseño moderno.
 * Fuente: https://dof.gob.mx/
 */

declare(strict_types=1);

const DOF_BASE = 'https://dof.gob.mx';
const DOF_URL  = 'https://dof.gob.mx/';

function dof_fetch(string $url): array
{
    $headers = [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language: es-MX,es;q=0.9,en;q=0.8',
        'Cache-Control: no-cache',
        'Connection: keep-alive',
        'Upgrade-Insecure-Requests: 1',
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_ENCODING       => '',
    ]);

    $html = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    return [$html, $httpCode, $error];
}

function dof_abs(string $href): string
{
    $href = html_entity_decode($href, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    if ($href === '' || (strpos($href, '#') === 0) || (strpos($href, 'javascript:') === 0)) {
        return $href;
    }
    if (preg_match('#^https?://#i', $href)) {
        return $href;
    }
    if ((strpos($href, '//') === 0)) {
        return 'https:' . $href;
    }
    if ((strpos($href, '/') === 0)) {
        return DOF_BASE . $href;
    }
    return DOF_BASE . '/' . ltrim($href, '/');
}

function dof_text(string $html): string
{
    $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
    return trim($text);
}

function dof_parse(string $rawHtml): array
{
    // El DOF publica en ISO-8859-1
    $html = @mb_convert_encoding($rawHtml, 'UTF-8', 'ISO-8859-1');
    if ($html === false || $html === '') {
        $html = $rawHtml;
    }

    $data = [
        'fecha'       => '',
        'edicion'     => '',
        'pdf'         => '',
        'portada'     => '',
        'indice_img'  => '',
        'indicadores' => [],
        'destacados'  => ['dia' => [], 'semana' => [], 'mes' => []],
        'secciones'   => [],
        'accesos'     => [],
    ];

    if (preg_match('/Fecha:\s*([0-9]{2}\/[0-9]{2}\/[0-9]{4})\s*-\s*Edici[oó]n\s+([^<]+)/iu', $html, $m)) {
        $data['fecha'] = trim($m[1]);
        $data['edicion'] = trim(html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    if (preg_match("/nota_to_pdf\.php\?fecha=([^&\"']+)&(?:amp;)?edicion=([^&\"']+)/i", $html, $m)) {
        $data['pdf'] = DOF_BASE . '/nota_to_pdf.php?fecha=' . urlencode(html_entity_decode($m[1])) . '&edicion=' . urlencode(html_entity_decode($m[2]));
    }

    if (preg_match('/nota_to_imagen_fs\.php\?cod_diario=(\d+)&(?:amp;)?pagina=0&(?:amp;)?fecha=([^"\']+)/i', $html, $m)) {
        $data['indice_img'] = DOF_BASE . '/nota_to_imagen_fs.php?cod_diario=' . $m[1] . '&pagina=0&fecha=' . urlencode(html_entity_decode($m[2]));
    }

    if (preg_match('/nota_to_imagen_fs\.php\?cod_diario=(\d+)&(?:amp;)?pagina=-1&(?:amp;)?fecha=([^"\']+)/i', $html, $m)) {
        $data['portada'] = DOF_BASE . '/nota_to_imagen_fs.php?cod_diario=' . $m[1] . '&pagina=-1&fecha=' . urlencode(html_entity_decode($m[2]));
    }

    if (preg_match_all('/<span class="tituloBloque4">([^<]+)<\/span>\s*<br\s*\/?>\s*([^<]+)/i', $html, $m, PREG_SET_ORDER)) {
        foreach ($m as $row) {
            $label = trim(html_entity_decode($row[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $value = trim(html_entity_decode($row[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($label !== '' && $value !== '') {
                $data['indicadores'][] = ['label' => $label, 'value' => $value];
            }
        }
    }

    foreach (['dia_lateral' => 'dia', 'semana_lateral' => 'semana', 'mes_lateral' => 'mes'] as $id => $key) {
        if (!preg_match('/id="' . preg_quote($id, '/') . '"[^>]*>(.*?)<\/div>/is', $html, $block)) {
            continue;
        }
        if (preg_match_all('/<a class="enlaces_leido" href="([^"]+)">\s*(?:<img[^>]*>)?\s*(.*?)<\/a>/is', $block[1], $links, PREG_SET_ORDER)) {
            foreach ($links as $link) {
                $title = dof_text($link[2]);
                if ($title === '') {
                    continue;
                }
                $data['destacados'][$key][] = [
                    'title' => $title,
                    'url'   => dof_abs($link[1]),
                ];
            }
        }
    }

    $accesosMap = [
        'Crear Usuario'              => 'admin_usuarios.php',
        'Búsqueda Avanzada'          => 'busqueda_avanzada.php?pb=S&cod_orga=TODOS',
        'Top Notas'                  => 'index.php?vertopnotas=1',
        'Normas Oficiales'           => 'normasOficiales.php',
        'Quejas y Sugerencias'       => 'quejas.php',
        'Obtener Copia del DOF'      => 'copias_cert.php',
        'Verificar Copia del DOF'    => 'copias_verif.php',
        'Enlaces Relevantes'         => 'enlaces_dependencias.php',
        'Contáctenos'                => 'contactenos.php',
        'Historia del Diario Oficial'=> 'historia.php',
        'Vacantes en Gobierno'       => 'vacantes.php',
        'Indicadores'                => 'indicadores.php',
    ];
    foreach ($accesosMap as $label => $path) {
        $data['accesos'][] = ['label' => $label, 'url' => dof_abs($path)];
    }

    // Índice del día: poderes → dependencias → notas
    $secciones = [];
    $poderActual = 'General';
    $depActual = 'Publicaciones';

    // Recorte del bloque principal del índice
    $slice = $html;
    if (preg_match('/Fecha:\s*[0-9]{2}\/[0-9]{2}\/[0-9]{4}.*?INDICADORES/is', $html, $sliceMatch)) {
        $slice = $sliceMatch[0];
    }

    $tokens = [];
    if (preg_match_all(
        '/class="txt_blanco2"[^>]*>\s*&nbsp;?\s*([^<]+)|class="subtitle_azul"[^>]*>\s*(?:<!--.*?-->)?\s*&nbsp;?\s*([^<]+)|class="enlaces"[^>]*href="([^"]+)"[^>]*>(.*?)<\/a>|href="([^"]*nota_detalle\.php[^"]*)"[^>]*class="enlaces"[^>]*>(.*?)<\/a>/is',
        $slice,
        $matches,
        PREG_SET_ORDER
    )) {
        $tokens = $matches;
    }

    foreach ($tokens as $t) {
        if (!empty($t[1])) {
            $poderActual = dof_text($t[1]);
            $depActual = $poderActual;
            if (!isset($secciones[$poderActual])) {
                $secciones[$poderActual] = [];
            }
            continue;
        }
        if (!empty($t[2])) {
            $depActual = dof_text($t[2]);
            if (!isset($secciones[$poderActual])) {
                $secciones[$poderActual] = [];
            }
            if (!isset($secciones[$poderActual][$depActual])) {
                $secciones[$poderActual][$depActual] = [];
            }
            continue;
        }

        $href = trim((string) ($t[3] ?? '')) !== '' ? (string) $t[3] : (string) ($t[5] ?? '');
        $titleHtml = trim((string) ($t[4] ?? '')) !== '' ? (string) $t[4] : (string) ($t[6] ?? '');
        $title = dof_text($titleHtml);
        if ($href === '' || $title === '' || stripos($href, 'nota_detalle.php') === false) {
            continue;
        }

        if (!isset($secciones[$poderActual])) {
            $secciones[$poderActual] = [];
        }
        if (!isset($secciones[$poderActual][$depActual])) {
            $secciones[$poderActual][$depActual] = [];
        }

        $codigo = '';
        if (preg_match('/codigo=(\d+)/', $href, $cm)) {
            $codigo = $cm[1];
        }

        $imagen = '';
        if ($codigo !== '' && preg_match('/nota_to_imagen_fs\.php\?codnota=' . preg_quote($codigo, '/') . '[^"\']*/i', $html, $im)) {
            $imagen = dof_abs($im[0]);
        }

        $secciones[$poderActual][$depActual][] = [
            'title'  => $title,
            'url'    => dof_abs($href),
            'codigo' => $codigo,
            'imagen' => $imagen,
        ];
    }

    // Fallback si el regex de tokens falló: todas las notas del índice
    if ($secciones === []) {
        if (preg_match_all('/<a href="([^"]*nota_detalle\.php[^"]*)"[^>]*class="enlaces"[^>]*>(.*?)<\/a>/is', $html, $links, PREG_SET_ORDER)
            || preg_match_all('/class="enlaces"[^>]*href="([^"]*nota_detalle\.php[^"]*)"[^>]*>(.*?)<\/a>/is', $html, $links, PREG_SET_ORDER)
        ) {
            $secciones['Publicaciones del día']['Índice'] = [];
            foreach ($links as $link) {
                $title = dof_text($link[2]);
                if ($title === '') {
                    continue;
                }
                $secciones['Publicaciones del día']['Índice'][] = [
                    'title'  => $title,
                    'url'    => dof_abs($link[1]),
                    'codigo' => preg_match('/codigo=(\d+)/', $link[1], $cm) ? $cm[1] : '',
                    'imagen' => '',
                ];
            }
        }
    }

    $data['secciones'] = $secciones;
    return $data;
}

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function dof_clean_nota_html(string $html): string
{
    $html = preg_replace('#<(script|style|iframe|object|embed)[^>]*>.*?</\1>#is', '', $html) ?? $html;
    $html = preg_replace('#</?(html|head|body|meta|link|!DOCTYPE)[^>]*>#is', '', $html) ?? $html;
    $html = preg_replace_callback(
        '/\s(src|href)=([\'"])([^\'"]*)\2/i',
        static function (array $m): string {
            return ' ' . $m[1] . '=' . $m[2] . dof_abs($m[3]) . $m[2];
        },
        $html
    ) ?? $html;

    return trim($html);
}

function dof_parse_nota(string $rawHtml): array
{
    $html = @mb_convert_encoding($rawHtml, 'UTF-8', 'ISO-8859-1');
    if ($html === false || $html === '') {
        $html = $rawHtml;
    }

    $title = '';
    $fecha = '';
    $body = '';

    if (preg_match('/DOF:\s*([0-9]{2}\/[0-9]{2}\/[0-9]{4})/i', $html, $m)) {
        $fecha = $m[1];
    }

    if (preg_match('/id=[\'"]DivDetalleNota[\'"][^>]*>(.*?)<\/div>\s*<br\s*\/?>\s*<table/is', $html, $m)
        || preg_match('/id=[\'"]DivDetalleNota[\'"][^>]*>(.*)/is', $html, $m)
    ) {
        $chunk = $m[1];
        if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $chunk, $bm)) {
            $chunk = $bm[1];
        }
        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $chunk, $tm)) {
            $title = dof_text($tm[1]);
        }
        $body = dof_clean_nota_html($chunk);
    }

    if ($title === '' && preg_match_all('/<title>(.*?)<\/title>/is', $html, $tm) && count($tm[1]) > 1) {
        $title = dof_text($tm[1][count($tm[1]) - 1]);
    }

    if ($body === '' && preg_match('/<body[^>]*>(.*?)<\/body>/is', $html, $bm)) {
        $body = dof_clean_nota_html($bm[1]);
    }

    return [
        'title' => $title !== '' ? $title : 'Nota del DOF',
        'fecha' => $fecha,
        'body'  => $body,
    ];
}

// Endpoint AJAX: carga una nota para el modal
if (isset($_GET['ajax']) && $_GET['ajax'] === 'nota') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');

    $codigo = isset($_GET['codigo']) ? preg_replace('/\D+/', '', (string) $_GET['codigo']) : '';
    $fecha = isset($_GET['fecha']) ? (string) $_GET['fecha'] : '';
    $urlIn = isset($_GET['url']) ? (string) $_GET['url'] : '';

    if ($codigo !== '') {
        if ($fecha === '' || !preg_match('#^\d{2}/\d{2}/\d{4}$#', $fecha)) {
            $fecha = date('d/m/Y');
        }
        $url = DOF_BASE . '/nota_detalle.php?codigo=' . rawurlencode($codigo) . '&fecha=' . rawurlencode($fecha);
    } elseif ($urlIn !== '') {
        $parts = parse_url($urlIn);
        $host = isset($parts['host']) ? strtolower($parts['host']) : '';
        if ($host !== '' && $host !== 'dof.gob.mx' && $host !== 'www.dof.gob.mx') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'URL no permitida'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $url = dof_abs($urlIn);
        if (preg_match('/codigo=(\d+)/', $url, $cm)) {
            $codigo = $cm[1];
        }
        if (preg_match('/fecha=([^&]+)/', $url, $fm)) {
            $fecha = urldecode($fm[1]);
        }
    } else {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Falta código o URL'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    list($html, $httpCode, $error) = dof_fetch($url);
    if ($html === false || $httpCode >= 400) {
        http_response_code(502);
        echo json_encode([
            'ok'    => false,
            'error' => $error !== '' ? $error : ('HTTP ' . $httpCode),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $nota = dof_parse_nota($html);
    echo json_encode([
        'ok'     => true,
        'title'  => $nota['title'],
        'fecha'  => $nota['fecha'] !== '' ? $nota['fecha'] : $fecha,
        'codigo' => $codigo,
        'source' => $url,
        'body'   => $nota['body'],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

[$html, $httpCode, $error] = dof_fetch(DOF_URL);

if ($html === false || $httpCode >= 400) {
    http_response_code(502);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>DOF — error</title></head><body>';
    echo '<p>No se pudo obtener el Diario Oficial.</p>';
    echo '<p>HTTP: ' . htmlspecialchars((string) $httpCode) . '</p>';
    if ($error !== '') {
        echo '<p>cURL: ' . htmlspecialchars($error) . '</p>';
    }
    echo '</body></html>';
    exit;
}

$dof = dof_parse($html);
$fechaLabel = $dof['fecha'] !== '' ? $dof['fecha'] : date('d/m/Y');
$edicionLabel = $dof['edicion'] !== '' ? $dof['edicion'] : 'Edición del día';
$totalNotas = 0;
foreach ($dof['secciones'] as $deps) {
    foreach ($deps as $notas) {
        $totalNotas += count($notas);
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>DOF · <?= h($fechaLabel) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Literata:opsz,wght@7..72,400;7..72,500;7..72,600;7..72,700&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --ink: #12241c;
      --ink-soft: #3d5248;
      --paper: #f3efe6;
      --paper-2: #ebe4d6;
      --leaf: #1f6b4a;
      --leaf-deep: #0f3d2c;
      --brass: #b08a3c;
      --line: rgba(18, 36, 28, 0.12);
      --glow: rgba(31, 107, 74, 0.18);
      --radius: 18px;
      --shadow: 0 18px 50px rgba(15, 40, 28, 0.12);
      --font-display: "Literata", "Source Serif 4", Georgia, serif;
      --font-body: "Source Sans 3", "Segoe UI", system-ui, sans-serif;
      --font-read: "Literata", Georgia, serif;
    }

    * { box-sizing: border-box; }
    html { scroll-behavior: smooth; }
    body {
      margin: 0;
      color: var(--ink);
      font-family: var(--font-body);
      background:
        radial-gradient(1200px 600px at 10% -10%, rgba(176, 138, 60, 0.16), transparent 55%),
        radial-gradient(900px 500px at 100% 0%, rgba(31, 107, 74, 0.14), transparent 50%),
        linear-gradient(180deg, #f7f3ea 0%, var(--paper) 40%, #e8e1d2 100%);
      min-height: 100vh;
    }

    body::before {
      content: "";
      position: fixed;
      inset: 0;
      pointer-events: none;
      opacity: 0.35;
      background-image:
        linear-gradient(rgba(18,36,28,0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(18,36,28,0.03) 1px, transparent 1px);
      background-size: 48px 48px;
      mask-image: radial-gradient(circle at center, black, transparent 85%);
    }

    a { color: inherit; }

    .wrap {
      width: 100%;
      max-width: none;
      margin: 0;
      padding: 0 clamp(1rem, 2.2vw, 2.5rem);
      position: relative;
      z-index: 1;
    }

    .topbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      padding: 1.1rem 0 0.4rem;
      opacity: 0;
      animation: rise 0.7s ease forwards;
    }

    .brand-mark {
      display: flex;
      align-items: baseline;
      gap: 0.55rem;
      text-decoration: none;
    }

    .brand-mark strong {
      font-family: var(--font-display);
      font-size: clamp(1.6rem, 3vw, 2.1rem);
      font-weight: 700;
      letter-spacing: -0.03em;
    }

    .brand-mark span {
      color: var(--ink-soft);
      font-size: 0.85rem;
      font-weight: 500;
    }

    .top-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 0.55rem;
    }

    .btn {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      padding: 0.7rem 1rem;
      border-radius: 999px;
      border: 1px solid var(--line);
      background: rgba(255,255,255,0.55);
      color: var(--ink);
      text-decoration: none;
      font-size: 0.88rem;
      font-weight: 600;
      transition: transform 0.2s ease, background 0.2s ease, border-color 0.2s ease;
      backdrop-filter: blur(8px);
    }

    .btn:hover { transform: translateY(-1px); border-color: rgba(31,107,74,0.35); }
    .btn-primary {
      background: linear-gradient(135deg, var(--leaf) 0%, var(--leaf-deep) 100%);
      color: #f7f3ea;
      border-color: transparent;
      box-shadow: 0 10px 24px var(--glow);
    }

    .hero {
      padding: 1.4rem 0 1.8rem;
      opacity: 0;
      animation: rise 0.8s ease 0.08s forwards;
    }

    .hero-panel {
      position: relative;
      overflow: hidden;
      border-radius: calc(var(--radius) + 8px);
      padding: clamp(1.4rem, 4vw, 2.6rem);
      background:
        linear-gradient(135deg, rgba(15,61,44,0.96), rgba(31,107,74,0.88)),
        url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
      color: #f4f0e6;
      box-shadow: var(--shadow);
    }

    .hero-panel::after {
      content: "";
      position: absolute;
      right: -80px;
      top: -60px;
      width: 280px;
      height: 280px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(176,138,60,0.35), transparent 70%);
      pointer-events: none;
    }

    .hero-kicker {
      display: inline-block;
      margin-bottom: 0.85rem;
      font-size: 0.78rem;
      letter-spacing: 0.16em;
      text-transform: uppercase;
      color: rgba(244,240,230,0.72);
      font-weight: 600;
    }

    .hero h1 {
      margin: 0 0 0.55rem;
      font-family: var(--font-display);
      font-size: clamp(2.1rem, 4.5vw, 3.8rem);
      line-height: 1.05;
      letter-spacing: -0.03em;
      font-weight: 700;
      max-width: none;
    }

    .hero p {
      margin: 0;
      max-width: 52rem;
      color: rgba(244,240,230,0.84);
      font-size: 1.05rem;
      line-height: 1.55;
    }

    .hero-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 0.7rem;
      margin-top: 1.4rem;
    }

    .chip {
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      padding: 0.45rem 0.8rem;
      border-radius: 999px;
      background: rgba(255,255,255,0.12);
      border: 1px solid rgba(255,255,255,0.16);
      font-size: 0.84rem;
      font-weight: 600;
    }

    .metrics {
      display: grid;
      grid-template-columns: repeat(7, minmax(0, 1fr));
      gap: 0.75rem;
      margin: 1.4rem 0 0.4rem;
      opacity: 0;
      animation: rise 0.8s ease 0.16s forwards;
    }

    .metric {
      padding: 0.95rem 1rem;
      border-radius: var(--radius);
      background: rgba(255,255,255,0.62);
      border: 1px solid var(--line);
      backdrop-filter: blur(8px);
      transition: transform 0.25s ease, box-shadow 0.25s ease;
    }

    .metric:hover {
      transform: translateY(-3px);
      box-shadow: 0 12px 28px rgba(15, 40, 28, 0.1);
    }

    .metric .label {
      display: block;
      font-size: 0.72rem;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: var(--ink-soft);
      font-weight: 700;
      margin-bottom: 0.25rem;
    }

    .metric .value {
      font-family: var(--font-display);
      font-size: 1.35rem;
      font-weight: 700;
      color: var(--leaf-deep);
      letter-spacing: -0.02em;
    }

    .layout {
      display: grid;
      grid-template-columns: minmax(0, 1fr) 340px;
      gap: 1.25rem;
      padding: 0.4rem 0 3rem;
      opacity: 0;
      animation: rise 0.85s ease 0.24s forwards;
      align-items: start;
    }

    .panel {
      background: rgba(255,255,255,0.58);
      border: 1px solid var(--line);
      border-radius: calc(var(--radius) + 4px);
      backdrop-filter: blur(10px);
      box-shadow: 0 10px 30px rgba(15, 40, 28, 0.06);
    }

    .panel-head {
      display: flex;
      align-items: end;
      justify-content: space-between;
      gap: 1rem;
      padding: 1.15rem 1.25rem 0.85rem;
      border-bottom: 1px solid var(--line);
    }

    .panel-head h2 {
      margin: 0;
      font-family: var(--font-display);
      font-size: 1.45rem;
      letter-spacing: -0.02em;
    }

    .panel-head p {
      margin: 0.2rem 0 0;
      color: var(--ink-soft);
      font-size: 0.9rem;
    }

    .search {
      width: min(100%, 260px);
      padding: 0.7rem 0.9rem;
      border-radius: 12px;
      border: 1px solid var(--line);
      background: rgba(255,255,255,0.8);
      font: inherit;
      color: var(--ink);
      outline: none;
    }

    .search:focus {
      border-color: rgba(31,107,74,0.45);
      box-shadow: 0 0 0 3px var(--glow);
    }

    .filters {
      display: flex;
      flex-wrap: wrap;
      gap: 0.45rem;
      padding: 0.9rem 1.25rem;
      border-bottom: 1px solid var(--line);
      position: sticky;
      top: 0;
      background: rgba(247,243,234,0.92);
      backdrop-filter: blur(10px);
      z-index: 2;
      border-radius: calc(var(--radius) + 4px) calc(var(--radius) + 4px) 0 0;
    }

    .filter-btn {
      border: 1px solid var(--line);
      background: transparent;
      color: var(--ink-soft);
      border-radius: 999px;
      padding: 0.4rem 0.75rem;
      font: inherit;
      font-size: 0.8rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .filter-btn:hover,
    .filter-btn.active {
      background: var(--leaf-deep);
      color: #f4f0e6;
      border-color: var(--leaf-deep);
    }

    .index {
      padding: 0.4rem 0.5rem 1rem;
    }

    .poder {
      margin: 0.55rem 0.5rem 1rem;
    }

    .poder-title {
      margin: 0 0 0.7rem;
      padding: 0.55rem 0.75rem;
      border-radius: 12px;
      background: linear-gradient(90deg, rgba(15,61,44,0.1), transparent);
      font-size: 0.78rem;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      font-weight: 700;
      color: var(--leaf-deep);
    }

    .dep {
      margin: 0 0 1rem;
      padding: 0 0.35rem;
    }

    .dep-title {
      margin: 0 0 0.45rem 0.35rem;
      font-family: var(--font-display);
      font-size: 1.05rem;
      font-weight: 600;
      color: var(--ink);
    }

    .note {
      display: grid;
      grid-template-columns: auto 1fr auto;
      gap: 0.75rem;
      align-items: start;
      padding: 0.85rem 0.9rem;
      margin: 0.35rem 0;
      border-radius: 14px;
      text-decoration: none;
      border: 1px solid transparent;
      transition: background 0.2s ease, border-color 0.2s ease, transform 0.2s ease;
    }

    .note:hover {
      background: rgba(255,255,255,0.85);
      border-color: rgba(31,107,74,0.18);
      transform: translateX(3px);
    }

    .note-num {
      width: 2rem;
      height: 2rem;
      border-radius: 10px;
      display: grid;
      place-items: center;
      background: rgba(15,61,44,0.08);
      color: var(--leaf-deep);
      font-size: 0.78rem;
      font-weight: 700;
      flex-shrink: 0;
    }

    .note-title {
      margin: 0;
      font-family: var(--font-read);
      font-size: 1.02rem;
      line-height: 1.55;
      font-weight: 500;
      letter-spacing: 0.005em;
    }

    .note-code {
      margin-top: 0.25rem;
      font-size: 0.75rem;
      color: var(--ink-soft);
    }

    .note-link {
      align-self: center;
      font-size: 0.78rem;
      font-weight: 700;
      color: var(--brass);
      white-space: nowrap;
    }

    .side {
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }

    .side .panel { overflow: hidden; }

    .tabs {
      display: flex;
      gap: 0.35rem;
      padding: 0.85rem 1rem 0;
    }

    .tab {
      flex: 1;
      border: 0;
      background: transparent;
      padding: 0.65rem 0.4rem;
      border-bottom: 2px solid transparent;
      color: var(--ink-soft);
      font: inherit;
      font-weight: 700;
      font-size: 0.85rem;
      cursor: pointer;
    }

    .tab.active {
      color: var(--leaf-deep);
      border-bottom-color: var(--leaf);
    }

    .tab-panel { display: none; padding: 0.4rem 0.6rem 1rem; }
    .tab-panel.active { display: block; }

    .hot-item {
      display: block;
      padding: 0.8rem 0.7rem;
      margin: 0.25rem 0;
      border-radius: 12px;
      text-decoration: none;
      border-left: 3px solid transparent;
      transition: background 0.2s ease, border-color 0.2s ease;
    }

    .hot-item:hover {
      background: rgba(255,255,255,0.75);
      border-left-color: var(--brass);
    }

    .hot-item p {
      margin: 0;
      font-family: var(--font-read);
      font-size: 0.95rem;
      line-height: 1.5;
      font-weight: 500;
    }

    .access-list {
      list-style: none;
      margin: 0;
      padding: 0.5rem 0.75rem 1rem;
    }

    .access-list a {
      display: flex;
      justify-content: space-between;
      gap: 0.75rem;
      padding: 0.7rem 0.55rem;
      text-decoration: none;
      border-bottom: 1px solid var(--line);
      font-size: 0.9rem;
      font-weight: 600;
      color: var(--ink);
    }

    .access-list a:last-child { border-bottom: 0; }
    .access-list a span { color: var(--brass); }

    .empty {
      padding: 2rem 1.25rem;
      text-align: center;
      color: var(--ink-soft);
    }

    .footer {
      padding: 0 0 2.5rem;
      color: var(--ink-soft);
      font-size: 0.82rem;
      text-align: center;
    }

    .footer a { color: var(--leaf-deep); font-weight: 600; }

    .note[hidden], .dep[hidden], .poder[hidden] { display: none !important; }

    @keyframes rise {
      from { opacity: 0; transform: translateY(14px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 1200px) {
      .metrics { grid-template-columns: repeat(4, minmax(0, 1fr)); }
      .layout { grid-template-columns: minmax(0, 1fr) 300px; }
    }

    @media (max-width: 920px) {
      .metrics { grid-template-columns: repeat(2, minmax(0, 1fr)); }
      .layout { grid-template-columns: 1fr; }
      .panel-head { flex-direction: column; align-items: stretch; }
      .search { width: 100%; }
      .note { grid-template-columns: auto 1fr; }
      .note-link { display: none; }
    }

    @media (max-width: 560px) {
      .metrics { grid-template-columns: 1fr 1fr; }
      .wrap { padding: 0 0.85rem; }
    }

    /* Modal de nota */
    .modal-root {
      position: fixed;
      inset: 0;
      z-index: 1000;
      display: none;
      align-items: stretch;
      justify-content: center;
      padding: clamp(0.75rem, 2vw, 1.5rem);
    }
    .modal-root.open { display: flex; }
    .modal-backdrop {
      position: absolute;
      inset: 0;
      background: rgba(10, 28, 20, 0.58);
      backdrop-filter: blur(6px);
      animation: fadeIn 0.2s ease;
    }
    .modal-dialog {
      position: relative;
      z-index: 1;
      width: min(980px, 100%);
      max-height: 100%;
      display: flex;
      flex-direction: column;
      border-radius: calc(var(--radius) + 6px);
      background: linear-gradient(180deg, #fbf8f1 0%, #f3efe6 100%);
      border: 1px solid rgba(255,255,255,0.35);
      box-shadow: 0 30px 80px rgba(8, 28, 18, 0.35);
      overflow: hidden;
      animation: modalIn 0.28s ease;
    }
    .modal-top {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 1rem;
      padding: 1.15rem 1.25rem 1rem;
      border-bottom: 1px solid var(--line);
      background: rgba(255,255,255,0.45);
    }
    .modal-kicker {
      margin: 0 0 0.35rem;
      font-size: 0.72rem;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      color: var(--ink-soft);
      font-weight: 700;
    }
    .modal-title {
      margin: 0;
      font-family: var(--font-read);
      font-size: clamp(1.2rem, 2.4vw, 1.6rem);
      line-height: 1.35;
      letter-spacing: -0.01em;
      font-weight: 600;
      color: var(--leaf-deep);
      max-width: 48rem;
    }
    .modal-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 0.45rem;
      margin-top: 0.7rem;
    }
    .modal-meta span,
    .modal-meta a {
      display: inline-flex;
      align-items: center;
      padding: 0.28rem 0.65rem;
      border-radius: 999px;
      background: rgba(15, 61, 44, 0.08);
      color: var(--ink-soft);
      font-size: 0.78rem;
      font-weight: 600;
      text-decoration: none;
    }
    .modal-meta a:hover { background: rgba(15, 61, 44, 0.14); color: var(--leaf-deep); }
    .modal-close {
      flex-shrink: 0;
      width: 2.4rem;
      height: 2.4rem;
      border-radius: 999px;
      border: 1px solid var(--line);
      background: rgba(255,255,255,0.8);
      color: var(--ink);
      font-size: 1.25rem;
      line-height: 1;
      cursor: pointer;
      transition: background 0.2s ease, transform 0.2s ease;
    }
    .modal-close:hover { background: #fff; transform: scale(1.04); }
    .modal-body {
      overflow: auto;
      padding: 1.25rem clamp(1rem, 3vw, 2rem) 2rem;
      flex: 1;
    }
    .modal-body.loading,
    .modal-body.error {
      display: grid;
      place-items: center;
      min-height: 220px;
      color: var(--ink-soft);
      font-weight: 600;
    }
    .modal-content {
      max-width: 42rem;
      margin: 0 auto;
      color: var(--ink);
      font-family: var(--font-read);
      font-size: 1.125rem;
      line-height: 1.8;
      font-weight: 400;
      letter-spacing: 0.01em;
      font-optical-sizing: auto;
    }
    .modal-content h1,
    .modal-content h2,
    .modal-content .Titulo_1,
    .modal-content .Titulo_2 {
      font-family: var(--font-read);
      color: var(--leaf-deep);
      border: 0 !important;
      margin: 0 0 1rem;
      line-height: 1.4;
      font-size: 1.35rem;
      font-weight: 700;
      text-indent: 0 !important;
      letter-spacing: -0.01em;
    }
    .modal-content h2,
    .modal-content .Titulo_2 {
      font-size: 1.08rem;
      color: var(--ink-soft);
      font-weight: 600;
      margin-bottom: 1.35rem;
      padding-bottom: 0.9rem;
      border-bottom: 1px solid var(--line) !important;
    }
    .modal-content .Texto,
    .modal-content p,
    .modal-content div {
      margin: 0 0 1.1rem;
      text-align: justify;
      hyphens: auto;
      text-indent: 0 !important;
      font-family: var(--font-read) !important;
      font-size: 1.125rem !important;
      line-height: 1.8 !important;
      font-weight: 400 !important;
      letter-spacing: 0.01em !important;
    }
    .modal-content span {
      font-family: inherit !important;
      font-size: inherit !important;
      line-height: inherit !important;
      letter-spacing: inherit !important;
    }
    .modal-content table {
      width: 100%;
      border-collapse: collapse;
      margin: 1rem 0;
      font-size: 0.95rem;
    }
    .modal-content td,
    .modal-content th {
      border: 1px solid var(--line);
      padding: 0.45rem 0.6rem;
    }
    body.modal-open { overflow: hidden; }
    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    @keyframes modalIn {
      from { opacity: 0; transform: translateY(18px) scale(0.98); }
      to { opacity: 1; transform: translateY(0) scale(1); }
    }
  </style>
</head>
<body>
  <div class="wrap">
    <header class="topbar">
      <a class="brand-mark" href="<?= h(DOF_URL) ?>" target="_blank" rel="noopener">
        <strong>DOF</strong>
        <span>versión casa</span>
      </a>
      <div class="top-actions">
        <?php if ($dof['pdf'] !== ''): ?>
          <a class="btn btn-primary" href="<?= h($dof['pdf']) ?>" target="_blank" rel="noopener">PDF del ejemplar</a>
        <?php endif; ?>
        <a class="btn" href="<?= h(DOF_URL) ?>" target="_blank" rel="noopener">Sitio oficial</a>
      </div>
    </header>

    <section class="hero">
      <div class="hero-panel">
        <div class="hero-kicker">Diario Oficial de la Federación</div>
        <h1><?= h($fechaLabel) ?></h1>
        <p>Índice del ejemplar reinterpretado con una lectura más clara: por poder, dependencia y nota, sin el ruido visual del sitio original.</p>
        <div class="hero-meta">
          <span class="chip"><?= h($edicionLabel) ?></span>
          <span class="chip"><?= (int) $totalNotas ?> publicaciones</span>
          <?php if ($dof['portada'] !== ''): ?>
            <a class="chip" href="<?= h($dof['portada']) ?>" target="_blank" rel="noopener">Ver portada</a>
          <?php endif; ?>
          <?php if ($dof['indice_img'] !== ''): ?>
            <a class="chip" href="<?= h($dof['indice_img']) ?>" target="_blank" rel="noopener">Ver índice escaneado</a>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <?php if ($dof['indicadores'] !== []): ?>
      <section class="metrics" aria-label="Indicadores del día">
        <?php foreach ($dof['indicadores'] as $ind): ?>
          <div class="metric">
            <span class="label"><?= h($ind['label']) ?></span>
            <span class="value"><?= h($ind['value']) ?></span>
          </div>
        <?php endforeach; ?>
      </section>
    <?php endif; ?>

    <div class="layout">
      <main class="panel">
        <div class="panel-head">
          <div>
            <h2>Índice del día</h2>
            <p>Filtra por poder o busca por palabra clave.</p>
          </div>
          <input class="search" id="q" type="search" placeholder="Buscar en el índice…" autocomplete="off">
        </div>

        <?php if ($dof['secciones'] !== []): ?>
          <div class="filters" id="filters">
            <button type="button" class="filter-btn active" data-filter="*">Todo</button>
            <?php foreach (array_keys($dof['secciones']) as $poder): ?>
              <button type="button" class="filter-btn" data-filter="<?= h($poder) ?>"><?= h($poder) ?></button>
            <?php endforeach; ?>
          </div>

          <div class="index" id="index">
            <?php $i = 1; foreach ($dof['secciones'] as $poder => $deps): ?>
              <section class="poder" data-poder="<?= h($poder) ?>">
                <h3 class="poder-title"><?= h($poder) ?></h3>
                <?php foreach ($deps as $dep => $notas): ?>
                  <?php if ($notas === []) continue; ?>
                  <div class="dep" data-dep="<?= h($dep) ?>">
                    <h4 class="dep-title"><?= h($dep) ?></h4>
                    <?php foreach ($notas as $nota): ?>
                      <?php
                        $notaFecha = '';
                        if (preg_match('/fecha=([^&]+)/', $nota['url'], $fm)) {
                            $notaFecha = urldecode(html_entity_decode($fm[1]));
                        }
                      ?>
                      <a class="note js-dof-modal"
                         href="<?= h($nota['url']) ?>"
                         data-codigo="<?= h($nota['codigo']) ?>"
                         data-fecha="<?= h($notaFecha) ?>"
                         data-text="<?= h(mb_strtolower($nota['title'] . ' ' . $dep . ' ' . $poder, 'UTF-8')) ?>">
                        <span class="note-num"><?= $i++ ?></span>
                        <span>
                          <p class="note-title"><?= h($nota['title']) ?></p>
                          <?php if ($nota['codigo'] !== ''): ?>
                            <div class="note-code">Código <?= h($nota['codigo']) ?></div>
                          <?php endif; ?>
                        </span>
                        <span class="note-link">Leer</span>
                      </a>
                    <?php endforeach; ?>
                  </div>
                <?php endforeach; ?>
              </section>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="empty">No se pudo estructurar el índice. Revisa el sitio oficial.</div>
        <?php endif; ?>
      </main>

      <aside class="side">
        <section class="panel">
          <div class="panel-head">
            <div>
              <h2>Lo más leído</h2>
              <p>Destacados del DOF</p>
            </div>
          </div>
          <div class="tabs" role="tablist">
            <button class="tab active" type="button" data-tab="dia">Día</button>
            <button class="tab" type="button" data-tab="semana">Semana</button>
            <button class="tab" type="button" data-tab="mes">Mes</button>
          </div>
          <?php foreach (['dia' => 'Día', 'semana' => 'Semana', 'mes' => 'Mes'] as $key => $label): ?>
            <div class="tab-panel <?= $key === 'dia' ? 'active' : '' ?>" data-panel="<?= h($key) ?>">
              <?php if ($dof['destacados'][$key] === []): ?>
                <div class="empty">Sin datos de <?= h(mb_strtolower($label, 'UTF-8')) ?>.</div>
              <?php else: ?>
                <?php foreach ($dof['destacados'][$key] as $item): ?>
                  <?php
                    $hotCodigo = '';
                    $hotFecha = '';
                    if (preg_match('/codigo=(\d+)/', $item['url'], $cm)) {
                        $hotCodigo = $cm[1];
                    }
                    if (preg_match('/fecha=([^&]+)/', $item['url'], $fm)) {
                        $hotFecha = urldecode(html_entity_decode($fm[1]));
                        // El DOF a veces manda año 19xx por error; corregimos a 20xx si aplica
                        if (preg_match('#^(\d{2}/\d{2}/)19(\d{2})$#', $hotFecha, $yf)) {
                            $hotFecha = $yf[1] . '20' . $yf[2];
                        }
                    }
                  ?>
                  <a class="hot-item js-dof-modal"
                     href="<?= h($item['url']) ?>"
                     data-codigo="<?= h($hotCodigo) ?>"
                     data-fecha="<?= h($hotFecha) ?>">
                    <p><?= h($item['title']) ?></p>
                  </a>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </section>

        <section class="panel">
          <div class="panel-head">
            <div>
              <h2>Accesos</h2>
              <p>Atajos útiles</p>
            </div>
          </div>
          <ul class="access-list">
            <?php foreach ($dof['accesos'] as $acceso): ?>
              <li>
                <a href="<?= h($acceso['url']) ?>" target="_blank" rel="noopener">
                  <?= h($acceso['label']) ?>
                  <span>↗</span>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </section>
      </aside>
    </div>

    <footer class="footer">
      Contenido obtenido de <a href="<?= h(DOF_URL) ?>" target="_blank" rel="noopener">dof.gob.mx</a>.
      Esta es una lectura alternativa no oficial.
    </footer>
  </div>

  <div class="modal-root" id="notaModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="modal-backdrop" data-close-modal></div>
    <div class="modal-dialog">
      <div class="modal-top">
        <div>
          <p class="modal-kicker">Nota del DOF</p>
          <h2 class="modal-title" id="modalTitle">Cargando…</h2>
          <div class="modal-meta" id="modalMeta"></div>
        </div>
        <button type="button" class="modal-close" data-close-modal aria-label="Cerrar">×</button>
      </div>
      <div class="modal-body loading" id="modalBody">Cargando nota…</div>
    </div>
  </div>

  <script>
    (() => {
      const q = document.getElementById('q');
      const filterBtns = [...document.querySelectorAll('.filter-btn')];
      let activeFilter = '*';

      const apply = () => {
        const term = (q?.value || '').trim().toLowerCase();
        document.querySelectorAll('.poder').forEach((poderEl) => {
          const poderName = poderEl.dataset.poder || '';
          const poderMatch = activeFilter === '*' || activeFilter === poderName;
          let visibleDeps = 0;

          poderEl.querySelectorAll('.dep').forEach((depEl) => {
            let visibleNotes = 0;
            depEl.querySelectorAll('.note').forEach((note) => {
              const text = note.dataset.text || '';
              const show = poderMatch && (!term || text.includes(term));
              note.hidden = !show;
              if (show) visibleNotes++;
            });
            depEl.hidden = visibleNotes === 0;
            if (visibleNotes > 0) visibleDeps++;
          });

          poderEl.hidden = !poderMatch || visibleDeps === 0;
        });
      };

      filterBtns.forEach((btn) => {
        btn.addEventListener('click', () => {
          filterBtns.forEach((b) => b.classList.remove('active'));
          btn.classList.add('active');
          activeFilter = btn.dataset.filter || '*';
          apply();
        });
      });

      q?.addEventListener('input', apply);

      document.querySelectorAll('.tab').forEach((tab) => {
        tab.addEventListener('click', () => {
          const key = tab.dataset.tab;
          document.querySelectorAll('.tab').forEach((t) => t.classList.toggle('active', t === tab));
          document.querySelectorAll('.tab-panel').forEach((panel) => {
            panel.classList.toggle('active', panel.dataset.panel === key);
          });
        });
      });

      const modal = document.getElementById('notaModal');
      const modalTitle = document.getElementById('modalTitle');
      const modalMeta = document.getElementById('modalMeta');
      const modalBody = document.getElementById('modalBody');
      const endpoint = <?= json_encode(basename(__FILE__), JSON_UNESCAPED_UNICODE) ?>;

      const openModal = () => {
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
      };

      const closeModal = () => {
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
      };

      const showLoading = (titleHint) => {
        modalTitle.textContent = titleHint || 'Cargando…';
        modalMeta.innerHTML = '';
        modalBody.className = 'modal-body loading';
        modalBody.textContent = 'Cargando nota…';
        openModal();
      };

      const loadNota = async (el) => {
        const codigo = el.dataset.codigo || '';
        const fecha = el.dataset.fecha || '';
        const href = el.getAttribute('href') || '';
        const hint = (el.querySelector('.note-title, p') || el).textContent.trim();

        showLoading(hint);

        const params = new URLSearchParams({ ajax: 'nota' });
        if (codigo) {
          params.set('codigo', codigo);
          if (fecha) params.set('fecha', fecha);
        } else {
          params.set('url', href);
        }

        try {
          const res = await fetch(endpoint + '?' + params.toString(), {
            headers: { 'Accept': 'application/json' },
          });
          const data = await res.json();
          if (!data.ok) {
            throw new Error(data.error || 'No se pudo cargar la nota');
          }

          modalTitle.textContent = data.title || hint || 'Nota del DOF';
          modalMeta.innerHTML = '';
          if (data.fecha) {
            const s = document.createElement('span');
            s.textContent = 'DOF: ' + data.fecha;
            modalMeta.appendChild(s);
          }
          if (data.codigo) {
            const s = document.createElement('span');
            s.textContent = 'Código ' + data.codigo;
            modalMeta.appendChild(s);
          }
          if (data.source) {
            const a = document.createElement('a');
            a.href = data.source;
            a.target = '_blank';
            a.rel = 'noopener';
            a.textContent = 'Ver en oficial';
            modalMeta.appendChild(a);
          }

          modalBody.className = 'modal-body';
          modalBody.innerHTML = '<div class="modal-content">' + (data.body || '<p>Sin contenido.</p>') + '</div>';
          modalBody.scrollTop = 0;
        } catch (err) {
          modalBody.className = 'modal-body error';
          modalBody.textContent = err.message || 'Error al cargar la nota';
        }
      };

      document.addEventListener('click', (e) => {
        const link = e.target.closest('.js-dof-modal');
        if (link) {
          e.preventDefault();
          loadNota(link);
          return;
        }
        if (e.target.closest('[data-close-modal]')) {
          closeModal();
        }
      });

      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.classList.contains('open')) {
          closeModal();
        }
      });
    })();
  </script>
</body>
</html>
