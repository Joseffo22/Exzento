<?php
require('assets/php/conexiones/conexionMySqli.php');

// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

// Obtener información del usuario
$query_usuario = "SELECT c.* FROM usuarios u
INNER JOIN clientes c ON c.id_usuario=u.id
 WHERE u.id = ?";
$stmt_usuario = $conn->prepare($query_usuario);
$stmt_usuario->bind_param("i", $id_usuario);
$stmt_usuario->execute();
$usuario = $stmt_usuario->get_result()->fetch_assoc();

// Obtener datos fiscales del usuario
$query_datos_fiscales = "SELECT df.*, rf.descripcion as regimen_fiscal, uc.clave as uso_cfdi_clave, uc.descripcion as uso_cfdi_descripcion 
                        FROM datosFiscales df 
                        LEFT JOIN regimenesFiscales rf ON df.regimen = rf.id 
                        LEFT JOIN usosCfdi uc ON df.usoFavorito = uc.id 
                        WHERE df.id_usuario = ? 
                        ORDER BY df.id DESC";
$stmt_datos = $conn->prepare($query_datos_fiscales);
$stmt_datos->bind_param("i", $id_usuario);
$stmt_datos->execute();
$datos_fiscales = $stmt_datos->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Información Personal y Datos Fiscales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .btn-action {
            margin: 0 2px;
        }
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .card-header {
            background: #2563EB;
            color: #FFFFFF;
            border: none;
        }
        .btn-primary {
            background: #2563EB;
            border: none;
            font-weight: 700;
            color: #FFFFFF !important;
        }
        .btn-primary:hover {
            background: #1D4ED8;
            color: #FFFFFF !important;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <!-- Mensajes de éxito y error -->
        <div id="alertas-perfil">
        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?= htmlspecialchars($_SESSION['mensaje']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['mensaje']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        </div>

        <!-- Información Personal -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0"><i class="bi bi-person-circle"></i> Información Personal</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">
                            Aquí solo actualizas cómo te llamamos en Exzento. Tus datos fiscales (RFC, razón social, etc.) están en la sección de abajo.
                        </p>
                        <form id="formPersonal" action="../funciones/actualizar_personal.php" method="POST">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="nombre" class="form-label">Nombre</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" 
                                           value="<?= htmlspecialchars($usuario['nombre'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="apellido" class="form-label">Apellido</label>
                                    <input type="text" class="form-control" id="apellido" name="apellido" 
                                           value="<?= htmlspecialchars($usuario['apellido'] ?? '') ?>" required>
                                </div>
                                
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Guardar nombre y apellido
                                    </button>
                                    <p class="form-text text-muted mb-0 mt-2">
                                        Al guardar verás un mensaje de confirmación arriba. No se despliega ningún otro formulario.
                                    </p>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Datos Fiscales -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="mb-0"><i class="bi bi-file-earmark-text"></i> Mis Datos Fiscales</h3>
                        <a href="registrar-datos-fiscales" class="btn btn-light">
                            <i class="bi bi-plus-circle"></i> Registrar Nuevos Datos
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if ($datos_fiscales->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Razón Social</th>
                                            <th>RFC</th>
                                            <th>Régimen Fiscal</th>
                                            <th>Uso CFDI</th>
                                            <th>Correo</th>
                                            <th>Teléfono</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($dato = $datos_fiscales->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($dato['razonSocial']) ?></td>
                                                <td><span class="badge bg-secondary"><?= htmlspecialchars($dato['rfc']) ?></span></td>
                                                <td><?= htmlspecialchars($dato['regimen_fiscal']) ?></td>
                                                <td>
                                                    <small class="text-muted"><?= htmlspecialchars($dato['uso_cfdi_clave']) ?></small><br>
                                                    <?= htmlspecialchars($dato['uso_cfdi_descripcion']) ?>
                                                </td>
                                                <td><?= htmlspecialchars($dato['correo']) ?></td>
                                                <td><?= htmlspecialchars($dato['telefono']) ?></td>
                                                <td><?= htmlspecialchars($dato['estado']) ?></td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-sm btn-outline-primary btn-action" 
                                                                onclick="verDetalles(<?= $dato['id'] ?>)" 
                                                                title="Ver detalles">
                                                            <i class="bi bi-eye"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-warning btn-action" 
                                                                onclick="editarDatos(<?= $dato['id'] ?>)" 
                                                                title="Editar">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-danger btn-action" 
                                                                onclick="eliminarDatos(<?= $dato['id'] ?>)" 
                                                                title="Eliminar">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-file-earmark-text display-1 text-muted"></i>
                                <h4 class="mt-3 text-muted">No tienes datos fiscales registrados</h4>
                                <p class="text-muted">Comienza registrando tus primeros datos fiscales</p>
                                <a href="registrar-datos-fiscales" class="btn btn-primary">
                                    <i class="bi bi-plus-circle"></i> Registrar Datos Fiscales
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para ver detalles -->
    <div class="modal fade" id="modalDetalles" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalles de Datos Fiscales</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalDetallesBody">
                    <!-- Contenido dinámico -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Función para ver detalles
        function verDetalles(id) {
            fetch(`../funciones/obtener_datos_fiscales.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert('Error: ' + data.error);
                        return;
                    }
                    
                    const modalBody = document.getElementById('modalDetallesBody');
                    modalBody.innerHTML = `
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Datos Básicos</h6>
                                <p><strong>Razón Social:</strong> ${data.razon_social}</p>
                                <p><strong>RFC:</strong> ${data.rfc}</p>
                                <p><strong>Régimen Fiscal:</strong> ${data.regimen_fiscal}</p>
                                <p><strong>Uso CFDI:</strong> ${data.nombre_usoFavorito}</p>
                            </div>
                            <div class="col-md-6">
                                <h6>Datos de Contacto</h6>
                                <p><strong>Correo:</strong> ${data.correo}</p>
                                <p><strong>Teléfono:</strong> ${data.telefono}</p>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <h6>Dirección Fiscal</h6>
                                <p><strong>Dirección:</strong> ${data.calle}, ${data.colonia}</p>
                                <p><strong>Código Postal:</strong> ${data.cp}</p>
                                <p><strong>Municipio:</strong> ${data.municipio}</p>
                                <p><strong>Estado:</strong> ${data.estado}</p>
                            </div>
                        </div>
                    `;
                    
                    new bootstrap.Modal(document.getElementById('modalDetalles')).show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cargar los detalles');
                });
        }

        // Función para editar datos
        function editarDatos(id) {
            if (confirm('¿Deseas editar estos datos fiscales?')) {
                window.location.href = `editar-datos-fiscales?id=${id}`;
            }
        }

        // Función para eliminar datos
        function eliminarDatos(id) {
            if (confirm('¿Estás seguro de que deseas eliminar estos datos fiscales? Esta acción no se puede deshacer.')) {
                fetch('../funciones/eliminar_datos_fiscales.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${id}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Datos fiscales eliminados correctamente');
                        location.reload();
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al eliminar los datos fiscales');
                });
            }
        }

        // Validación del formulario personal
        document.getElementById('formPersonal').addEventListener('submit', function(e) {
            const nombre = document.getElementById('nombre').value.trim();
            const apellido = document.getElementById('apellido').value.trim();
            
            if (!nombre || !apellido) {
                e.preventDefault();
                alert('Por favor completa todos los campos');
                return false;
            }
        });
    </script>
</body>
</html>