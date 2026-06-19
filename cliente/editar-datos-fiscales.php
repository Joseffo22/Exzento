<?php
require_once('assets/php/conexiones/conexionMySqli.php');

// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit();
}

$id_usuario = $_SESSION['id_usuario'];
$id_datos = $_GET['id'] ?? null;

if (!$id_datos) {
    header('Location: informacion-personal');
    exit();
}

// Verificar que los datos fiscales pertenezcan al usuario
$query_verificar = "SELECT * FROM datosFiscales WHERE id = ? AND id_usuario = ?";
$stmt_verificar = $conn->prepare($query_verificar);
$stmt_verificar->bind_param("ii", $id_datos, $id_usuario);
$stmt_verificar->execute();
$datos_fiscales = $stmt_verificar->get_result();

if ($datos_fiscales->num_rows === 0) {
    header('Location: informacion-personal');
    exit();
}

$datos = $datos_fiscales->fetch_assoc();

// Obtener los regímenes fiscales
$query_regimenes = "SELECT id, descripcion FROM regimenesFiscales ORDER BY descripcion";
$regimenes = $conn->query($query_regimenes);

// Obtener los usos de CFDI
$query_usos = "SELECT id, clave, descripcion FROM usosCfdi ORDER BY clave";
$usos = $conn->query($query_usos);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Datos Fiscales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
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
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h3 class="mb-0"><i class="bi bi-pencil-square"></i> Editar Datos Fiscales</h3>
                    </div>
                    <div class="card-body">
                        <form action="../funciones/actualizar_datos_fiscales.php" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                            <input type="hidden" name="id_datos" value="<?= $datos['id'] ?>">
                            
                            <!-- Datos Básicos -->
                            <div class="mb-4">
                                <h5 class="border-bottom pb-2">Datos Básicos</h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="razonSocial" class="form-label">Razón Social</label>
                                        <input type="text" class="form-control" id="razonSocial" name="razonSocial" 
                                               value="<?= htmlspecialchars($datos['razonSocial']) ?>" required>
                                        <div class="invalid-feedback">Por favor ingrese la razón social</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="rfc" class="form-label">RFC</label>
                                        <input type="text" class="form-control" id="rfc" name="rfc" 
                                               pattern="^[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}$" 
                                               value="<?= htmlspecialchars($datos['rfc']) ?>" required>
                                        <div class="invalid-feedback">Ingrese un RFC válido</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="regimen" class="form-label">Régimen Fiscal</label>
                                        <select class="form-select" id="regimen" name="regimen" required>
                                            <option value="">Seleccione un régimen</option>
                                            <?php 
                                            $regimenes->data_seek(0);
                                            while ($regimen = $regimenes->fetch_assoc()): 
                                            ?>
                                                <option value="<?= $regimen['id'] ?>" 
                                                        <?= ($regimen['id'] == $datos['regimen']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($regimen['descripcion']) ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <div class="invalid-feedback">Seleccione un régimen fiscal</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="uso_cfdi" class="form-label">Uso de CFDI</label>
                                        <select class="form-select" id="uso_cfdi" name="uso_cfdi" required>
                                            <option value="">Seleccione un uso de CFDI</option>
                                            <?php 
                                            $usos->data_seek(0);
                                            while ($uso = $usos->fetch_assoc()): 
                                            ?>
                                                <option value="<?= $uso['id'] ?>" 
                                                        <?= ($uso['id'] == $datos['usoFavorito']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($uso['clave'] . ' - ' . $uso['descripcion']) ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <div class="invalid-feedback">Seleccione un uso de CFDI</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Datos de Contacto -->
                            <div class="mb-4">
                                <h5 class="border-bottom pb-2">Datos de Contacto</h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="correo" class="form-label">Correo Electrónico</label>
                                        <input type="email" class="form-control" id="correo" name="correo" 
                                               value="<?= htmlspecialchars($datos['correo']) ?>" required>
                                        <div class="invalid-feedback">Ingrese un correo electrónico válido</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="telefono" class="form-label">Teléfono</label>
                                        <input type="tel" class="form-control" id="telefono" name="telefono" 
                                               value="<?= htmlspecialchars($datos['telefono']) ?>" required>
                                        <div class="invalid-feedback">Ingrese un número de teléfono</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Dirección Fiscal -->
                            <div class="mb-4">
                                <h5 class="border-bottom pb-2">Dirección Fiscal</h5>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="calle" class="form-label">Calle y Número</label>
                                        <input type="text" class="form-control" id="calle" name="calle" 
                                               value="<?= htmlspecialchars($datos['calle']) ?>" required>
                                        <div class="invalid-feedback">Ingrese la calle y número</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="colonia" class="form-label">Colonia</label>
                                        <input type="text" class="form-control" id="colonia" name="colonia" 
                                               value="<?= htmlspecialchars($datos['colonia']) ?>" required>
                                        <div class="invalid-feedback">Ingrese la colonia</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="codigoPostal" class="form-label">Código Postal</label>
                                        <input type="text" class="form-control" id="codigoPostal" name="codigoPostal" 
                                               pattern="[0-9]{5}" value="<?= htmlspecialchars($datos['codigoPostal']) ?>" required>
                                        <div class="invalid-feedback">Ingrese un código postal válido (5 dígitos)</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="municipio" class="form-label">Municipio/Alcaldía</label>
                                        <input type="text" class="form-control" id="municipio" name="municipio" 
                                               value="<?= htmlspecialchars($datos['municipio']) ?>" required>
                                        <div class="invalid-feedback">Ingrese el municipio o alcaldía</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="estado" class="form-label">Estado</label>
                                        <input type="text" class="form-control" id="estado" name="estado" 
                                               value="<?= htmlspecialchars($datos['estado']) ?>" required>
                                        <div class="invalid-feedback">Ingrese el estado</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Constancia Fiscal -->
                            <div class="mb-4">
                                <h5 class="border-bottom pb-2">Constancia de Situación Fiscal</h5>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <?php if ($datos['constancia']): ?>
                                            <div class="alert alert-info">
                                                <i class="bi bi-file-earmark-pdf"></i> 
                                                Constancia actual: <a href="<?= site_url($datos['constancia']) ?>" target="_blank">Ver archivo</a>
                                            </div>
                                        <?php endif; ?>
                                        <label for="constancia" class="form-label">Nueva Constancia (PDF) - Opcional</label>
                                        <input type="file" class="form-control" id="constancia" name="constancia" accept=".pdf">
                                        <div class="form-text">Suba una nueva constancia solo si desea reemplazar la actual</div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Actualizar Datos Fiscales
                                </button>
                                <a href="informacion-personal.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left"></i> Volver
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validación del formulario
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()

        // Validación del RFC
        document.getElementById('rfc').addEventListener('input', function(e) {
            this.value = this.value.toUpperCase();
        });

        // Validación del código postal
        document.getElementById('codigoPostal').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 5);
        });
    </script>
</body>
</html> 