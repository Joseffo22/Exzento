<style>
    .footer {
        background: #000000;
        color: #FFFFFF;
        padding: 1.5rem 0;
        margin-top: auto;
        box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.2);
    }
    .footer p {
        margin: 0;
        font-weight: 700;
        opacity: 0.9;
    }
    .footer i {
        color: #fff;
        margin-right: 0.5rem;
    }
</style>

<footer class="footer">
    <div class="container d-flex justify-content-between align-items-center">
        <p>
            <i class="fas fa-copyright"></i>
            <?php echo date('Y'); ?> Exzento | Todos los derechos reservados
        </p>
        <button id="installButton" class="btn btn-outline-light btn-sm d-none">
            <i class="fas fa-mobile-alt me-2"></i>Instalar App
        </button>
    </div>
</footer>

<!-- Bootstrap 5 JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    let deferredPrompt;
    const installButton = document.getElementById('installButton');

    window.addEventListener('beforeinstallprompt', (e) => {
        // Previene que el mini-infobar aparezca automáticamente
        e.preventDefault();
        // Guarda el evento para que pueda ser activado más tarde
        deferredPrompt = e;
        // Muestra el botón de instalación
        installButton.classList.remove('d-none');
    });

    installButton.addEventListener('click', async () => {
        // Oculta el botón de instalación
        installButton.classList.add('d-none');
        // Muestra el prompt de instalación
        deferredPrompt.prompt();
        // Espera a que el usuario responda al prompt
        const { outcome } = await deferredPrompt.userChoice;
        // Opcionalmente, registra el resultado
        console.log(`User response to the install prompt: ${outcome}`);
        // Limpia el evento guardado, ya que solo se puede usar una vez
        deferredPrompt = null;
    });

    // Registrar Service Worker
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/service-worker.js')
                .then((registration) => {
                    console.log('Service Worker registrado con éxito:', registration);
                })
                .catch((error) => {
                    console.log('Fallo el registro del Service Worker:', error);
                });
        });
    }
</script>


