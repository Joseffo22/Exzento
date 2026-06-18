<link rel="stylesheet" href="assets/css/footer-styles.css">


<footer class="footer">
    <div class="container">
        <div class="row">
            <!-- Información de copyright -->
            <div class="col-md-3">
                <div class="mb-3">
                    <h6 class="text-light mb-2">
                        <img src="/assets/img/logo.svg" alt="Exzento" class="footer-logo">
                    </h6>
                    <p class="text-muted small">
                        Sistema integral de facturación y gestión de tickets fiscales
                    </p>
                </div>
                <p class="text-muted small">
                    <i class="fas fa-copyright"></i>
                    <?php echo date('Y'); ?> Exzento | Todos los derechos reservados
                </p>
            </div>
            
            <!-- Enlaces principales -->
            <div class="col-md-2">
                <h6 class="text-light mb-3">Navegación</h6>
                <div class="footer-links">
                    <a href="/" class="footer-link" style="color: #ffffff !important; text-decoration: none !important;">
                        <i class="fas fa-home me-1"></i> Inicio
                    </a>
                    <a href="/lista-tickets" class="footer-link" style="color: #ffffff !important; text-decoration: none !important;">
                        <i class="fas fa-ticket-alt me-1"></i> Mis Tickets
                    </a>
                    <a href="/facturas" class="footer-link" style="color: #ffffff !important; text-decoration: none !important;">
                        <i class="fas fa-file-invoice-dollar me-1"></i> Facturas
                    </a>
                    <a href="/informacion-personal" class="footer-link" style="color: #ffffff !important; text-decoration: none !important;">
                        <i class="fas fa-user me-1"></i> Perfil
                    </a>
                </div>
            </div>
            
            <!-- Gestión de datos -->
            <div class="col-md-2">
                <h6 class="text-light mb-3">Datos Fiscales</h6>
                <div class="footer-links">
                    <a href="/registrar-datos-fiscales" class="footer-link" style="color: #ffffff !important; text-decoration: none !important;">
                        <i class="fas fa-plus-circle me-1"></i> Registrar Datos
                    </a>
                    <a href="/editar-datos-fiscales" class="footer-link" style="color: #ffffff !important; text-decoration: none !important;">
                        <i class="fas fa-edit me-1"></i> Editar Datos
                    </a>
                    <a href="/informacion-personal" class="footer-link" style="color: #ffffff !important; text-decoration: none !important;">
                        <i class="fas fa-list me-1"></i> Ver Todos
                    </a>
                </div>
            </div>
            
            <!-- Herramientas -->
            <div class="col-md-2">
                <h6 class="text-light mb-3">Herramientas</h6>
                <div class="footer-links">
                    <a href="/generar-ticket" class="footer-link" style="color: #ffffff !important; text-decoration: none !important;">
                        <i class="fas fa-plus me-1"></i> Nuevo Ticket
                    </a>
                    <a href="/lector-qr" class="footer-link" style="color: #ffffff !important; text-decoration: none !important;">
                        <i class="fas fa-qrcode me-1"></i> Lector QR
                    </a>
                    <a href="/visualizar-ticket" class="footer-link" style="color: #ffffff !important; text-decoration: none !important;">
                        <i class="fas fa-eye me-1"></i> Ver Ticket
                    </a>
                </div>
            </div>
            
            <!-- Soporte y ayuda -->
            <div class="col-md-2">
                <h6 class="text-light mb-3">Soporte</h6>
                <div class="footer-links">
                    <a href="#" onclick="mostrarAyuda()" class="footer-link" style="color: #ffffff !important; text-decoration: none !important;">
                        <i class="fas fa-question-circle me-1"></i> Ayuda
                    </a>
                    <a href="#" onclick="mostrarContacto()" class="footer-link" style="color: #ffffff !important; text-decoration: none !important;">
                        <i class="fas fa-envelope me-1"></i> Contacto
                    </a>
                    <a href="#" onclick="mostrarFAQ()" class="footer-link" style="color: #ffffff !important; text-decoration: none !important;">
                        <i class="fas fa-info-circle me-1"></i> FAQ
                    </a>
                    <a href="funciones/logout.php" class="footer-link" style="color: #ffffff !important; text-decoration: none !important;">
                        <i class="fas fa-sign-out-alt me-1"></i> Cerrar Sesión
                    </a>
                </div>
            </div>
            
            <!-- Próximas Funciones
            <div class="col-md-1">
                <h6 class="text-light mb-3">
                    <i class="fas fa-rocket me-1"></i> Próximas
                </h6>
                <div class="footer-links">
                    <a href="#" onclick="mostrarProximasFunciones()" class="footer-link" style="color: #ffffff !important; text-decoration: none !important;">
                        <i class="fas fa-eye me-1"></i> Ver Lista
                    </a>
                </div>
            </div>
        </div> -->
        
        <!-- Línea separadora -->
        <hr class="my-4" style="border-color: rgba(255,255,255,0.1);">
        
        <!-- Información adicional -->
        <div class="row">
            <div class="col-md-6">
                <p class="text-muted small mb-0">
                    <i class="fas fa-shield-alt me-1"></i>
                    Tus datos están protegidos con encriptación SSL
                </p>
            </div>
            <div class="col-md-6 text-end">
                <p class="text-muted small mb-0">
                    <i class="fas fa-clock me-1"></i>
                    Última actualización: <?php echo date('d/m/Y H:i'); ?>
                </p>
            </div>
        </div>
    </div>
</footer>


<!-- Modal de Ayuda -->
<div class="modal fade" id="modalAyuda" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-question-circle me-2"></i>Centro de Ayuda
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-ticket-alt me-2"></i>Gestión de Tickets</h6>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-arrow-right me-1"></i>Crear tickets de facturación</li>
                            <li><i class="fas fa-arrow-right me-1"></i>Ver historial de tickets</li>
                            <li><i class="fas fa-arrow-right me-1"></i>Descargar facturas</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-user me-2"></i>Datos Fiscales</h6>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-arrow-right me-1"></i>Registrar datos fiscales</li>
                            <li><i class="fas fa-arrow-right me-1"></i>Editar información</li>
                            <li><i class="fas fa-arrow-right me-1"></i>Gestionar múltiples perfiles</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Contacto -->
<div class="modal fade" id="modalContacto" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-envelope me-2"></i>Contacto
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><i class="fas fa-envelope me-2"></i><strong>Email:</strong> soporte@exzento.com</p>
                <p><i class="fas fa-phone me-2"></i><strong>Teléfono:</strong> +52 (55) 1234-5678</p>
                <p><i class="fas fa-clock me-2"></i><strong>Horario:</strong> Lunes a Viernes 9:00 - 18:00</p>
                <p><i class="fas fa-map-marker-alt me-2"></i><strong>Ubicación:</strong> Ciudad de México, México</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal de FAQ -->
<div class="modal fade" id="modalFAQ" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle me-2"></i>Preguntas Frecuentes
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                ¿Cómo crear un ticket de facturación?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Ve a "Generar Ticket" en el menú principal, completa los datos requeridos y sube la imagen del ticket.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                ¿Cómo registrar mis datos fiscales?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Accede a "Registrar Datos Fiscales" y completa todos los campos con tu información fiscal.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                ¿Cuánto tiempo tarda la facturación?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                El proceso de facturación tarda entre 24-48 horas hábiles una vez que se suben los archivos.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Próximas Funciones -->
<div class="modal fade" id="modalProximasFunciones" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-rocket me-2"></i>Próximas Funciones - Roadmap
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Funciones de Gestión Avanzada -->
                    <div class="col-md-6 mb-4">
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0"><i class="fas fa-chart-line me-2"></i>Gestión Avanzada</h6>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="fas fa-chart-bar text-primary me-2"></i>
                                        <strong>Dashboard Analítico:</strong> Estadísticas de facturación y gastos
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-calendar-alt text-primary me-2"></i>
                                        <strong>Calendario de Vencimientos:</strong> Recordatorios de pagos y fechas importantes
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-bell text-primary me-2"></i>
                                        <strong>Notificaciones Push:</strong> Alertas en tiempo real de cambios de estado
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-history text-primary me-2"></i>
                                        <strong>Historial Completo:</strong> Log de todas las actividades del usuario
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Automatización e IA -->
                    <div class="col-md-6 mb-4">
                        <div class="card border-success">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0"><i class="fas fa-robot me-2"></i>Automatización e IA</h6>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="fas fa-magic text-success me-2"></i>
                                        <strong>OCR Inteligente:</strong> Lectura automática de tickets con IA
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-brain text-success me-2"></i>
                                        <strong>Predicción de Gastos:</strong> Análisis predictivo de patrones de consumo
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-sync-alt text-success me-2"></i>
                                        <strong>Sincronización Automática:</strong> Integración con bancos y sistemas contables
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-cogs text-success me-2"></i>
                                        <strong>Workflows Automatizados:</strong> Procesos automáticos de facturación
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Comunicación y Colaboración -->
                    <div class="col-md-6 mb-4">
                        <div class="card border-info">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0"><i class="fas fa-comments me-2"></i>Comunicación</h6>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="fas fa-comment-dots text-info me-2"></i>
                                        <strong>Chat en Vivo:</strong> Soporte técnico integrado
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-share-alt text-info me-2"></i>
                                        <strong>Compartir Tickets:</strong> Envío directo a contadores o asesores
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-video text-info me-2"></i>
                                        <strong>Videollamadas:</strong> Consultas virtuales con especialistas
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-users text-info me-2"></i>
                                        <strong>Equipos Colaborativos:</strong> Gestión de múltiples usuarios
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Integración y Conectividad -->
                    <div class="col-md-6 mb-4">
                        <div class="card border-warning">
                            <div class="card-header bg-warning text-dark">
                                <h6 class="mb-0"><i class="fas fa-plug me-2"></i>Integración</h6>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="fas fa-mobile-alt text-warning me-2"></i>
                                        <strong>App Móvil:</strong> Aplicación nativa para iOS y Android
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-cloud text-warning me-2"></i>
                                        <strong>API Pública:</strong> Integración con sistemas externos
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-database text-warning me-2"></i>
                                        <strong>Backup Automático:</strong> Respaldo en la nube
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-sync text-warning me-2"></i>
                                        <strong>Sincronización Multi-dispositivo:</strong> Datos actualizados en tiempo real
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Reportes y Análisis -->
                    <div class="col-md-6 mb-4">
                        <div class="card border-danger">
                            <div class="card-header bg-danger text-white">
                                <h6 class="mb-0"><i class="fas fa-file-alt me-2"></i>Reportes</h6>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="fas fa-file-pdf text-danger me-2"></i>
                                        <strong>Reportes Personalizados:</strong> Generación de informes a medida
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-download text-danger me-2"></i>
                                        <strong>Exportación Masiva:</strong> Descarga de múltiples facturas
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-chart-pie text-danger me-2"></i>
                                        <strong>Análisis Fiscal:</strong> Reportes para declaraciones
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-print text-danger me-2"></i>
                                        <strong>Impresión Remota:</strong> Envío a impresoras de red
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Seguridad y Cumplimiento -->
                    <div class="col-md-6 mb-4">
                        <div class="card border-dark">
                            <div class="card-header bg-dark text-white">
                                <h6 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Seguridad</h6>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="fas fa-fingerprint text-dark me-2"></i>
                                        <strong>Autenticación Biométrica:</strong> Login con huella digital
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-key text-dark me-2"></i>
                                        <strong>2FA Avanzado:</strong> Autenticación de dos factores
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-eye-slash text-dark me-2"></i>
                                        <strong>Encriptación End-to-End:</strong> Protección total de datos
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-audit text-dark me-2"></i>
                                        <strong>Auditoría Completa:</strong> Trazabilidad de todas las acciones
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Timeline de Lanzamiento -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-calendar me-2"></i>Timeline de Lanzamiento</h6>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-md-3">
                                        <div class="border rounded p-3">
                                            <h6 class="text-primary">Fase 1</h6>
                                            <small>Q1 2024</small>
                                            <p class="mb-0">Dashboard y Notificaciones</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="border rounded p-3">
                                            <h6 class="text-success">Fase 2</h6>
                                            <small>Q2 2024</small>
                                            <p class="mb-0">OCR y App Móvil</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="border rounded p-3">
                                            <h6 class="text-info">Fase 3</h6>
                                            <small>Q3 2024</small>
                                            <p class="mb-0">IA y Automatización</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="border rounded p-3">
                                            <h6 class="text-warning">Fase 4</h6>
                                            <small>Q4 2024</small>
                                            <p class="mb-0">Integración Completa</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function mostrarAyuda() {
    new bootstrap.Modal(document.getElementById('modalAyuda')).show();
}

function mostrarContacto() {
    new bootstrap.Modal(document.getElementById('modalContacto')).show();
}

function mostrarFAQ() {
    new bootstrap.Modal(document.getElementById('modalFAQ')).show();
}

function mostrarProximasFunciones() {
    new bootstrap.Modal(document.getElementById('modalProximasFunciones')).show();
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/session-keepalive.js"></script>
</body>
</html>
