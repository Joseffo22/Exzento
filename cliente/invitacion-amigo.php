<?php
require('assets/php/conexiones/conexionMySqli.php');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitar Amigo - Exzento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

</head>
<body class="bg-light">
 

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <!-- Estadísticas -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="stats-card">
                            <div class="stats-number" id="totalInvitaciones">0</div>
                            <div>Total Invitaciones</div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="stats-card">
                            <div class="stats-number" id="invitacionesUsadas">0</div>
                            <div>Invitaciones Usadas</div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="stats-card">
                            <div class="stats-number" id="invitacionesPendientes">0</div>
                            <div>Invitaciones Pendientes</div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Panel de generar invitación -->
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header bg-primary text-white text-center">
                                <h4 class="mb-0">
                                    <i class="fas fa-user-plus me-2"></i>Nueva Invitación
                                </h4>
                                <p class="mb-0">Comparte Exzento con tus amigos</p>
                            </div>
                            <div class="card-body p-4">
                                <!-- Formulario de invitación -->
                                <form id="formInvitacion">
                                    <div class="mb-4">
                                        <label for="mensaje" class="form-label">
                                            <i class="fas fa-comment me-2"></i>Mensaje para tu amigo
                                        </label>
                                        <textarea 
                                            class="form-control" 
                                            id="mensaje" 
                                            name="mensaje" 
                                            rows="4" 
                                            placeholder="Escribe un mensaje personalizado..."
                                            maxlength="200"
                                            required
                                        ></textarea>
                                        <div class="form-text">
                                            <span id="contador">0</span>/200 caracteres
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label for="whatsapp" class="form-label">
                                            <i class="fab fa-whatsapp me-2"></i>Número de WhatsApp (opcional)
                                        </label>
                                        <div class="input-group">
                                            <input 
                                                type="tel" 
                                                class="form-control" 
                                                id="whatsapp" 
                                                name="whatsapp" 
                                                placeholder="+52 123 456 7890"
                                            >
                                            <button 
                                                type="button" 
                                                class="btn btn-outline-secondary" 
                                                id="btnContactos"
                                                onclick="mostrarContactos()"
                                            >
                                                <i class="fas fa-address-book"></i>
                                            </button>
                                        </div>
                                        <div class="contact-selector" id="contactSelector">
                                            <div class="contact-item" onclick="seleccionarContacto('+52 123 456 7890', 'Juan Pérez')">
                                                <strong>Juan Pérez</strong><br>
                                                <small>+52 123 456 7890</small>
                                            </div>
                                            <div class="contact-item" onclick="seleccionarContacto('+52 987 654 3210', 'María García')">
                                                <strong>María García</strong><br>
                                                <small>+52 987 654 3210</small>
                                            </div>
                                            <div class="contact-item" onclick="seleccionarContacto('+52 555 123 4567', 'Carlos López')">
                                                <strong>Carlos López</strong><br>
                                                <small>+52 555 123 4567</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="fas fa-magic me-2"></i>Generar Invitación
                                        </button>
                                    </div>
                                </form>

                                <!-- Resultado de la invitación -->
                                <div id="resultadoInvitacion" class="mt-4" style="display: none;">
                                    <div class="alert alert-success">
                                        <h6><i class="fas fa-check-circle me-2"></i>¡Invitación generada!</h6>
                                        <p class="mb-0">Tu link está listo para compartir.</p>
                                    </div>

                                    <div class="link-container mb-3">
                                        <label class="form-label"><strong>Link de invitación:</strong></label>
                                        <div class="input-group">
                                            <input 
                                                type="text" 
                                                class="form-control" 
                                                id="linkInvitacion" 
                                                readonly
                                            >
                                            <button 
                                                type="button" 
                                                class="btn btn-success" 
                                                onclick="copiarLink()"
                                            >
                                                <i class="fas fa-copy me-1"></i>Copiar
                                            </button>
                                        </div>
                                    </div>

                                    <div class="text-center">
                                        <a 
                                            href="#" 
                                            class="whatsapp-btn" 
                                            id="btnWhatsApp"
                                            target="_blank"
                                        >
                                            <i class="fab fa-whatsapp me-2"></i>Enviar por WhatsApp
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Panel de invitaciones enviadas -->
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header bg-success text-white text-center">
                                <h4 class="mb-0">
                                    <i class="fas fa-list me-2"></i>Invitaciones Enviadas
                                </h4>
                                <p class="mb-0">Historial de tus invitaciones</p>
                            </div>
                            <div class="card-body p-4">
                                <div id="listaInvitaciones">
                                    <div class="text-center py-4">
                                        <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                                        <p class="mt-2 text-muted">Cargando invitaciones...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información adicional -->
                <div class="card">
                    <div class="card-body">
                        <h6><i class="fas fa-info-circle me-2"></i>¿Cómo funciona el sistema de invitaciones?</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <ul class="mb-0">
                                    <li>Genera un link único para cada invitación</li>
                                    <li>Tu amigo se registra usando el link</li>
                                    <li>Se vincula automáticamente a tu cuenta</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="mb-0">
                                    <li>Puedes hacer seguimiento del estado</li>
                                    <li>Los links no expiran</li>
                                    <li>Comparte fácilmente por WhatsApp</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Cargar invitaciones al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            cargarInvitaciones();
        });

        // Contador de caracteres
        document.getElementById('mensaje').addEventListener('input', function() {
            const contador = document.getElementById('contador');
            contador.textContent = this.value.length;
        });

        // Mostrar/ocultar selector de contactos
        function mostrarContactos() {
            const selector = document.getElementById('contactSelector');
            selector.style.display = selector.style.display === 'none' ? 'block' : 'none';
        }

        // Seleccionar contacto
        function seleccionarContacto(telefono, nombre) {
            document.getElementById('whatsapp').value = telefono;
            document.getElementById('contactSelector').style.display = 'none';
        }

        // Cargar invitaciones
        function cargarInvitaciones() {
            fetch('../funciones/obtener_invitaciones.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    actualizarEstadisticas(data);
                    mostrarInvitaciones(data.invitaciones);
                } else {
                    document.getElementById('listaInvitaciones').innerHTML = 
                        '<div class="alert alert-danger">Error al cargar las invitaciones</div>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('listaInvitaciones').innerHTML = 
                    '<div class="alert alert-danger">Error al cargar las invitaciones</div>';
            });
        }

        // Actualizar estadísticas
        function actualizarEstadisticas(data) {
            document.getElementById('totalInvitaciones').textContent = data.total;
            document.getElementById('invitacionesUsadas').textContent = data.usadas;
            document.getElementById('invitacionesPendientes').textContent = data.pendientes;
        }

        // Mostrar lista de invitaciones
        function mostrarInvitaciones(invitaciones) {
            const container = document.getElementById('listaInvitaciones');
            
            if (invitaciones.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-2x text-muted"></i>
                        <p class="mt-2 text-muted">No has enviado invitaciones aún</p>
                    </div>
                `;
                return;
            }

            let html = '';
            invitaciones.forEach(invitacion => {
                const fecha = new Date(invitacion.fecha_generacion).toLocaleDateString('es-ES');
                const statusClass = invitacion.usada ? 'status-usada' : 'status-pendiente';
                
                html += `
                    <div class="invitation-item">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="status-badge ${statusClass}">${invitacion.estado}</span>
                            <small class="text-muted">${fecha}</small>
                        </div>
                        <p class="mb-2"><strong>Mensaje:</strong> ${invitacion.mensaje}</p>
                        <div class="input-group mb-2">
                            <input type="text" class="form-control form-control-sm" value="${invitacion.link}" readonly>
                            <button class="btn btn-outline-secondary btn-sm" onclick="copiarLinkEspecifico('${invitacion.link}')">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                        ${invitacion.referido ? `
                            <div class="alert alert-success py-2 mb-0">
                                <small><i class="fas fa-user-check me-1"></i>Registrado: ${invitacion.referido.nombre} ${invitacion.referido.apellido}</small>
                            </div>
                        ` : ''}
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        // Manejar envío del formulario
        document.getElementById('formInvitacion').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('../funciones/generar_invitacion.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mostrar resultado
                    document.getElementById('linkInvitacion').value = data.link_invitacion;
                    document.getElementById('resultadoInvitacion').style.display = 'block';
                    
                    // Configurar botón de WhatsApp
                    const mensaje = document.getElementById('mensaje').value;
                    const whatsapp = document.getElementById('whatsapp').value;
                    const link = data.link_invitacion;
                    
                    let textoWhatsApp = `¡Hola! Te invito a unirte a Exzento, la mejor plataforma de facturación. ${mensaje}\n\nRegístrate aquí: ${link}`;
                    
                    if (whatsapp) {
                        const urlWhatsApp = `https://wa.me/${whatsapp.replace(/\D/g, '')}?text=${encodeURIComponent(textoWhatsApp)}`;
                        document.getElementById('btnWhatsApp').href = urlWhatsApp;
                    } else {
                        const urlWhatsApp = `https://wa.me/?text=${encodeURIComponent(textoWhatsApp)}`;
                        document.getElementById('btnWhatsApp').href = urlWhatsApp;
                    }
                    
                    // Recargar invitaciones
                    cargarInvitaciones();
                    
                    // Limpiar formulario
                    this.reset();
                    document.getElementById('contador').textContent = '0';
                    
                    // Scroll al resultado
                    document.getElementById('resultadoInvitacion').scrollIntoView({ behavior: 'smooth' });
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al generar la invitación. Por favor, intenta de nuevo.');
            });
        });

        // Copiar link al portapapeles
        function copiarLink() {
            const linkInput = document.getElementById('linkInvitacion');
            linkInput.select();
            linkInput.setSelectionRange(0, 99999);
            
            navigator.clipboard.writeText(linkInput.value).then(function() {
                const btn = event.target;
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check me-1"></i>Copiado';
                btn.classList.remove('btn-success');
                btn.classList.add('btn-secondary');
                
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.classList.remove('btn-secondary');
                    btn.classList.add('btn-success');
                }, 2000);
            }).catch(function(err) {
                console.error('Error al copiar: ', err);
                alert('Error al copiar el link. Por favor, cópialo manualmente.');
            });
        }

        // Copiar link específico
        function copiarLinkEspecifico(link) {
            navigator.clipboard.writeText(link).then(function() {
                const btn = event.target;
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i>';
                btn.classList.remove('btn-outline-secondary');
                btn.classList.add('btn-success');
                
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-outline-secondary');
                }, 2000);
            }).catch(function(err) {
                console.error('Error al copiar: ', err);
                alert('Error al copiar el link. Por favor, cópialo manualmente.');
            });
        }

        // Cerrar selector de contactos al hacer clic fuera
        document.addEventListener('click', function(e) {
            const selector = document.getElementById('contactSelector');
            const btnContactos = document.getElementById('btnContactos');
            
            if (!selector.contains(e.target) && !btnContactos.contains(e.target)) {
                selector.style.display = 'none';
            }
        });
    </script>
</body>
</html>
