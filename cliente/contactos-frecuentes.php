<?php
// Verificar si la sesión ya está iniciada para evitar duplicación
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}

// Usar rutas absolutas desde la raíz del proyecto
require_once('assets/php/conexiones/conexionMySqli.php');
require_once('funciones/buscar_contacto_frecuente.php');

$mensaje = '';
$tipo_mensaje = '';

// Procesar formulario para agregar contacto
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'agregar') {
    $resultado = agregarContactoFrecuente($_POST);
    $mensaje = $resultado['message'];
    $tipo_mensaje = $resultado['success'] ? 'success' : 'danger';
}

// Obtener contactos frecuentes del usuario
$contactos = obtenerContactosFrecuentesUsuario($_SESSION['id_usuario']);
$estadisticas = obtenerEstadisticasContactos($_SESSION['id_usuario']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contactos Frecuentes - Exzento</title>

</head>
<body class="bg-light">


    <div class="container py-5">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0">
                        <i class="bi bi-star me-2 text-warning"></i>
                        Contactos Frecuentes
                    </h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAgregarContacto">
                        <i class="bi bi-plus-circle me-2"></i>Agregar Contacto
                    </button>
                </div>

                <!-- Estadísticas -->
                <?php if ($estadisticas): ?>
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-center border-0 shadow-sm">
                            <div class="card-body">
                                <i class="bi bi-people-fill text-primary" style="font-size: 2rem;"></i>
                                <h4 class="mt-2 mb-1"><?= $estadisticas['total_contactos'] ?? 0 ?></h4>
                                <small class="text-muted">Total Contactos</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center border-0 shadow-sm">
                            <div class="card-body">
                                <i class="bi bi-arrow-repeat text-success" style="font-size: 2rem;"></i>
                                <h4 class="mt-2 mb-1"><?= $estadisticas['total_usos'] ?? 0 ?></h4>
                                <small class="text-muted">Total Usos</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center border-0 shadow-sm">
                            <div class="card-body">
                                <i class="bi bi-person-check text-info" style="font-size: 2rem;"></i>
                                <h4 class="mt-2 mb-1"><?= $estadisticas['total_usos_usuario'] ?? 0 ?></h4>
                                <small class="text-muted">Mis Usos</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center border-0 shadow-sm">
                            <div class="card-body">
                                <i class="bi bi-calendar-event text-warning" style="font-size: 2rem;"></i>
                                <h4 class="mt-2 mb-1">
                                    <?= $estadisticas['ultimo_uso_usuario'] ? date('d/m', strtotime($estadisticas['ultimo_uso_usuario'])) : 'N/A' ?>
                                </h4>
                                <small class="text-muted">Último Uso</small>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Mensajes -->
                <?php if ($mensaje): ?>
                <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show" role="alert">
                    <i class="bi bi-<?= $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
                    <?= htmlspecialchars($mensaje) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Lista de contactos -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h5 class="mb-0">Mis Contactos Frecuentes</h5>
                            </div>
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text" class="form-control" id="buscarContacto" placeholder="Buscar por nombre, categoría...">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($contactos)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-star text-muted" style="font-size: 3rem;"></i>
                            <h5 class="mt-3 text-muted">No tienes contactos frecuentes</h5>
                            <p class="text-muted">Agrega contactos para reutilizar datos automáticamente</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAgregarContacto">
                                <i class="bi bi-plus-circle me-2"></i>Agregar Primer Contacto
                            </button>
                        </div>
                        <?php else: ?>
                        <div class="row g-3" id="listaContactos">
                            <?php foreach ($contactos as $contacto): ?>
                            <div class="col-md-6 col-lg-4 contacto-item" 
                                 data-nombre="<?= strtolower($contacto['nombre_empresa']) ?>"
                                 data-categoria="<?= strtolower($contacto['categoria'] ?? '') ?>">
                                <div class="card h-100 border-0 shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="card-title mb-0 text-truncate" title="<?= htmlspecialchars($contacto['nombre_empresa']) ?>">
                                                <?= htmlspecialchars($contacto['nombre_empresa']) ?>
                                            </h6>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                                    <i class="bi bi-three-dots-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="#" onclick="editarContacto(<?= $contacto['id'] ?>)">
                                                        <i class="bi bi-pencil me-2"></i>Editar
                                                    </a></li>
                                                    <li><a class="dropdown-item text-danger" href="#" onclick="eliminarContacto(<?= $contacto['id'] ?>)">
                                                        <i class="bi bi-trash me-2"></i>Eliminar
                                                    </a></li>
                                                </ul>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-2">
                                            <small class="text-muted">
                                                <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($contacto['telefono']) ?>
                                            </small>
                                        </div>
                                        
                                        <?php if ($contacto['categoria']): ?>
                                        <div class="mb-2">
                                            <span class="badge bg-primary"><?= htmlspecialchars($contacto['categoria']) ?></span>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($contacto['notas']): ?>
                                        <div class="mb-2">
                                            <small class="text-muted">
                                                <i class="bi bi-sticky me-1"></i><?= htmlspecialchars($contacto['notas']) ?>
                                            </small>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <div class="row text-center mt-3">
                                            <div class="col-6">
                                                <small class="text-muted">
                                                    <i class="bi bi-arrow-repeat me-1"></i><?= $contacto['frecuencia_uso'] ?> usos
                                                </small>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">
                                                    <i class="bi bi-calendar me-1"></i>
                                                    <?= $contacto['ultimo_uso'] ? date('d/m/Y', strtotime($contacto['ultimo_uso'])) : 'Nunca' ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para agregar contacto -->
    <div class="modal fade" id="modalAgregarContacto" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-plus-circle me-2 text-primary"></i>
                        Agregar Contacto Frecuente
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="agregar">
                        
                        <div class="mb-3">
                            <label for="nombre_empresa" class="form-label">Nombre de la empresa *</label>
                            <input type="text" class="form-control" id="nombre_empresa" name="nombre_empresa" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="telefono" class="form-label">Teléfono *</label>
                            <input type="tel" class="form-control" id="telefono" name="telefono" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="categoria" class="form-label">Categoría</label>
                            <select class="form-select" id="categoria" name="categoria">
                                <option value="">Seleccionar...</option>
                                <option value="Restaurante">Restaurante</option>
                                <option value="Farmacia">Farmacia</option>
                                <option value="Supermercado">Supermercado</option>
                                <option value="Gasolinera">Gasolinera</option>
                                <option value="Servicios">Servicios</option>
                                <option value="Otros">Otros</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notas" class="form-label">Notas</label>
                            <textarea class="form-control" id="notas" name="notas" rows="3" placeholder="Información adicional sobre el contacto..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-2"></i>Agregar Contacto
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Búsqueda en tiempo real
    document.getElementById('buscarContacto').addEventListener('input', function() {
        const busqueda = this.value.toLowerCase();
        const contactos = document.querySelectorAll('.contacto-item');
        
        contactos.forEach(contacto => {
            const nombre = contacto.dataset.nombre;
            const categoria = contacto.dataset.categoria;
            
            if (nombre.includes(busqueda) || categoria.includes(busqueda)) {
                contacto.style.display = 'block';
            } else {
                contacto.style.display = 'none';
            }
        });
    });

    // Función para editar contacto
    function editarContacto(id) {
        // Implementar edición de contacto
        alert('Función de edición en desarrollo');
    }

    // Función para eliminar contacto
    function eliminarContacto(id) {
        if (confirm('¿Estás seguro de que quieres eliminar este contacto?')) {
            // Implementar eliminación de contacto
            alert('Función de eliminación en desarrollo');
        }
    }
    </script>

</body>
</html>
