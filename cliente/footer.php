<footer class="cliente-footer-minimal">
    <p class="mb-0">
        <i class="fas fa-copyright" aria-hidden="true"></i>
        <?= date('Y') ?> Exzento
    </p>
</footer>

<?php require __DIR__ . '/bottom-nav.php'; ?>

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
                                Usa el botón <strong>Nuevo ticket de facturación</strong> en el inicio o ve a la pestaña Tickets.
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
                                Ve a <strong>Perfil → Datos fiscales</strong> o usa el acceso rápido en el inicio.
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
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/session-keepalive.js"></script>
</body>
</html>
