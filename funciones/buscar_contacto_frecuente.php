<?php
/**
 * Función para buscar contactos frecuentes por número de teléfono
 * Si se encuentra, retorna los datos del contacto
 * Si no se encuentra, retorna null
 */

// Verificar si la sesión está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/../assets/php/conexiones/conexionMySqli.php');

function buscarContactoFrecuente($telefono) {
    global $conn;
    
    if (!$conn) {
        return null;
    }
    
    // Limpiar y formatear el teléfono
    $telefono = limpiarTelefono($telefono);
    
    // Buscar en la tabla de contactos frecuentes
    $query = "SELECT 
                    id,
                    nombre_empresa,
                    telefono,
                    categoria,
                    notas
                FROM contactosFrecuentes 
                WHERE telefono = ?
                AND estatus = 1
                LIMIT 1";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return null;
    }
    
    $stmt->bind_param("s", $telefono);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $contacto = $result->fetch_assoc();
        
        // Actualizar frecuencia de uso
        actualizarFrecuenciaContacto($contacto['id']);
        
        // Registrar en historial
        registrarUsoContacto($contacto['id'], $contacto);
        
        return $contacto;
    }
    
    $stmt->close();
    return null;
}

/**
 * Función para limpiar y formatear números de teléfono
 */
function limpiarTelefono($telefono) {
    // Remover todos los caracteres no numéricos
    $telefono = preg_replace('/[^0-9]/', '', $telefono);
    
    // Si empieza con 52 (código de México), removerlo para búsqueda local
    if (strlen($telefono) >= 12 && substr($telefono, 0, 2) === '52') {
        $telefono = substr($telefono, 2);
    }
    
    // Si empieza con 1 (código de larga distancia), removerlo
    if (strlen($telefono) >= 10 && substr($telefono, 0, 1) === '1') {
        $telefono = substr($telefono, 1);
    }
    
    return $telefono;
}

/**
 * Función para actualizar la frecuencia de uso de un contacto
 */
function actualizarFrecuenciaContacto($contacto_id) {
    global $conn;
    
    $query = "UPDATE contactosFrecuentes 
              SET frecuencia_uso = frecuencia_uso + 1,
                  ultimo_uso = NOW()
              WHERE id = ?";
    
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("i", $contacto_id);
        $stmt->execute();
        $stmt->close();
    }
}

/**
 * Función para registrar el uso de un contacto en el historial
 */
function registrarUsoContacto($contacto_id, $datos_usados) {
    global $conn;
    
    // Solo registrar si el usuario está logueado
    if (!isset($_SESSION['id_usuario'])) {
        return;
    }
    
    $query = "INSERT INTO historialContactos 
              (id_contacto, id_ticket, id_usuario, datos_usados) 
              VALUES (?, NULL, ?, ?)";
    
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $datos_json = json_encode($datos_usados);
        $usuario_id = $_SESSION['id_usuario'];
        $stmt->bind_param("iis", $contacto_id, $usuario_id, $datos_json);
        $stmt->execute();
        $stmt->close();
    }
}

/**
 * Función para obtener todos los contactos frecuentes de un usuario
 */
function obtenerContactosFrecuentesUsuario($usuario_id = null) {
    global $conn;
    
    if (!$usuario_id && isset($_SESSION['id_usuario'])) {
        $usuario_id = $_SESSION['id_usuario'];
    }
    
    if (!$usuario_id) {
        return [];
    }
    
    $query = "SELECT 
                    cf.*,
                    COUNT(hc.id) as total_usos,
                    MAX(hc.fecha_uso) as ultimo_uso_real
                FROM contactosFrecuentes cf
                LEFT JOIN historialContactos hc ON cf.id = hc.id_contacto 
                    AND hc.id_usuario = ?
                WHERE cf.estatus = 1
                GROUP BY cf.id
                ORDER BY cf.frecuencia_uso DESC, cf.ultimo_uso DESC";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return [];
    }
    
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $contactos = [];
    while ($row = $result->fetch_assoc()) {
        $contactos[] = $row;
    }
    
    $stmt->close();
    return $contactos;
}

/**
 * Función para agregar un nuevo contacto frecuente
 */
function agregarContactoFrecuente($datos) {
    global $conn;
    
    if (!isset($_SESSION['id_usuario'])) {
        return ['success' => false, 'message' => 'Usuario no autenticado'];
    }
    
    // Validar datos requeridos
    if (empty($datos['nombre_empresa']) || empty($datos['telefono'])) {
        return ['success' => false, 'message' => 'Nombre de empresa y teléfono son requeridos'];
    }
    
    // Limpiar teléfono
    $telefono = limpiarTelefono($datos['telefono']);
    
    // Verificar si ya existe un contacto con ese teléfono
    $query = "SELECT id FROM contactosFrecuentes WHERE telefono = ? AND estatus = 1";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("s", $telefono);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $stmt->close();
            return ['success' => false, 'message' => 'Ya existe un contacto con ese número de teléfono'];
        }
        $stmt->close();
    }
    
    // Insertar nuevo contacto
    $query = "INSERT INTO contactosFrecuentes 
              (nombre_empresa, telefono, categoria, notas, creado_por) 
              VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return ['success' => false, 'message' => 'Error al preparar la consulta'];
    }
    
    // Crear variables locales para evitar problemas de referencia
    $nombre_empresa = $datos['nombre_empresa'];
    $categoria = $datos['categoria'] ?? null;
    $notas = $datos['notas'] ?? null;
    $usuario_id = $_SESSION['id_usuario'];
    
    $stmt->bind_param("ssssi", 
        $nombre_empresa,
        $telefono,
        $categoria,
        $notas,
        $usuario_id
    );
    
    if ($stmt->execute()) {
        $contacto_id = $conn->insert_id;
        $stmt->close();
        
        return [
            'success' => true, 
            'message' => 'Contacto agregado exitosamente',
            'contacto_id' => $contacto_id
        ];
    } else {
        $stmt->close();
        return ['success' => false, 'message' => 'Error al insertar el contacto'];
    }
}

/**
 * Función para buscar contactos por nombre o categoría
 */
function buscarContactosPorTexto($texto, $usuario_id = null) {
    global $conn;
    
    if (!$usuario_id && isset($_SESSION['id_usuario'])) {
        $usuario_id = $_SESSION['id_usuario'];
    }
    
    $texto = "%$texto%";
    
    $query = "SELECT 
                    cf.*,
                    COUNT(hc.id) as total_usos_usuario
                FROM contactosFrecuentes cf
                LEFT JOIN historialContactos hc ON cf.id = hc.id_contacto 
                    AND hc.id_usuario = ?
                WHERE cf.estatus = 1 
                AND (cf.nombre_empresa LIKE ? 
                     OR cf.categoria LIKE ?)
                GROUP BY cf.id
                ORDER BY cf.frecuencia_uso DESC, cf.ultimo_uso DESC
                LIMIT 10";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return [];
    }
    
    $stmt->bind_param("iss", $usuario_id, $texto, $texto);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $contactos = [];
    while ($row = $result->fetch_assoc()) {
        $contactos[] = $row;
    }
    
    $stmt->close();
    return $contactos;
}

/**
 * Función para obtener todos los contactos frecuentes del usuario
 */
function obtenerTodosLosContactosFrecuentes($usuario_id = null) {
    global $conn;
    
    if (!$usuario_id && isset($_SESSION['id_usuario'])) {
        $usuario_id = $_SESSION['id_usuario'];
    }
    
    if (!$usuario_id) {
        return [];
    }
    
    $query = "SELECT 
                    cf.id,
                    cf.nombre_empresa,
                    cf.telefono,
                    cf.categoria,
                    cf.notas,
                    cf.frecuencia_uso,
                    cf.ultimo_uso,
                    COUNT(hc.id) as total_usos_usuario
                FROM contactosFrecuentes cf
                LEFT JOIN historialContactos hc ON cf.id = hc.id_contacto 
                    AND hc.id_usuario = ?
                WHERE cf.estatus = 1
                GROUP BY cf.id
                ORDER BY cf.frecuencia_uso DESC, cf.ultimo_uso DESC, cf.nombre_empresa ASC";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return [];
    }
    
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $contactos = [];
    while ($row = $result->fetch_assoc()) {
        $contactos[] = $row;
    }
    
    $stmt->close();
    return $contactos;
}

/**
 * Función para buscar contactos por texto y/o categoría
 */
function buscarContactosPorTextoYCategoria($texto = null, $categoria = null, $usuario_id = null) {
    global $conn;
    
    if (!$usuario_id && isset($_SESSION['id_usuario'])) {
        $usuario_id = $_SESSION['id_usuario'];
    }
    
    if (!$usuario_id) {
        return [];
    }
    
    // Construir la consulta SQL dinámicamente
    $where_conditions = [];
    $params = [];
    $types = '';
    
    // Agregar condición de usuario
    $where_conditions[] = "cf.estatus = 1";
    
    // Agregar condición de texto si se proporciona
    if ($texto && strlen($texto) >= 2) {
        $where_conditions[] = "cf.nombre_empresa LIKE ?";
        $params[] = "%$texto%";
        $types .= 's';
    }
    
    // Agregar condición de categoría si se proporciona
    if ($categoria && !empty($categoria)) {
        $where_conditions[] = "cf.categoria = ?";
        $params[] = $categoria;
        $types .= 's';
    }
    
    $query = "SELECT 
                    cf.*,
                    COUNT(hc.id) as total_usos_usuario
                FROM contactosFrecuentes cf
                LEFT JOIN historialContactos hc ON cf.id = hc.id_contacto 
                    AND hc.id_usuario = ?
                WHERE " . implode(' AND ', $where_conditions) . "
                GROUP BY cf.id
                ORDER BY cf.frecuencia_uso DESC, cf.ultimo_uso DESC
                LIMIT 20";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return [];
    }
    
    // Agregar el usuario_id al inicio de los parámetros
    array_unshift($params, $usuario_id);
    $types = 'i' . $types;
    
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $contactos = [];
    while ($row = $result->fetch_assoc()) {
        $contactos[] = $row;
    }
    
    $stmt->close();
    return $contactos;
}

/**
 * Función para obtener estadísticas de uso de contactos
 */
function obtenerEstadisticasContactos($usuario_id = null) {
    global $conn;
    
    if (!$usuario_id && isset($_SESSION['id_usuario'])) {
        $usuario_id = $_SESSION['id_usuario'];
    }
    
    if (!$usuario_id) {
        return [];
    }
    
    $query = "SELECT 
                    COUNT(DISTINCT cf.id) as total_contactos,
                    SUM(cf.frecuencia_uso) as total_usos,
                    COUNT(DISTINCT hc.id) as total_usos_usuario,
                    MAX(cf.ultimo_uso) as ultimo_uso_global,
                    MAX(hc.fecha_uso) as ultimo_uso_usuario
                FROM contactosFrecuentes cf
                LEFT JOIN historialContactos hc ON cf.id = hc.id_contacto 
                    AND hc.id_usuario = ?
                WHERE cf.estatus = 1";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return [];
    }
    
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $stats = $result->fetch_assoc();
    $stmt->close();
    
    return $stats;
}

/**
 * Función para obtener todas las categorías disponibles
 */
function obtenerCategoriasDisponibles($usuario_id = null) {
    global $conn;
    
    if (!$usuario_id && isset($_SESSION['id_usuario'])) {
        $usuario_id = $_SESSION['id_usuario'];
    }
    
    if (!$usuario_id) {
        return [];
    }
    
    $query = "SELECT DISTINCT categoria, COUNT(*) as total
              FROM contactosFrecuentes 
              WHERE estatus = 1 AND categoria IS NOT NULL AND categoria != ''
              GROUP BY categoria
              ORDER BY total DESC, categoria ASC";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return [];
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $categorias = [];
    while ($row = $result->fetch_assoc()) {
        $categorias[] = $row;
    }
    
    $stmt->close();
    return $categorias;
}
?>
