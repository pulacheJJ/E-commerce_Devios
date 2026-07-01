<footer class="store-footer">
    {{-- INSIGNIAS DE CONFIANZA (Pre-footer) --}}
    <div class="footer-trust-badges">
        <div class="trust-badges-grid">
            <div class="trust-badge-item">
                <i class="fas fa-shipping-fast"></i>
                <div class="trust-badge-content">
                    <h5>Envíos a todo el Perú</h5>
                    <p>Entregas seguras y garantizadas.</p>
                </div>
            </div>
            <div class="trust-badge-item">
                <i class="fas fa-shield-alt"></i>
                <div class="trust-badge-content">
                    <h5>Garantía DEVIOZ</h5>
                    <p>Soporte técnico especializado.</p>
                </div>
            </div>
            <div class="trust-badge-item">
                <i class="fas fa-lock"></i>
                <div class="trust-badge-content">
                    <h5>Pago 100% Seguro</h5>
                    <p>Tus datos están encriptados.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- FOOTER PRINCIPAL --}}
    <div class="footer-main">
        {{-- Columna 1: Branding --}}
        <div class="footer-col footer-brand">
            <img src="{{ asset('img/logo-devioz.webp') }}" alt="DEVIOZ Logo" class="footer-logo">
            <p>Elevamos tu experiencia tecnológica con los mejores componentes y equipos de alto rendimiento para tu
                setup.</p>
            <div class="footer-social">
                {{-- Enlaces extraídos de config/social.php --}}
                <a href="{{ config('social.website', '#') }}" target="_blank" aria-label="Página Principal">
                    <i class="fas fa-globe"></i>
                </a>
                <a href="{{ config('social.facebook', '#') }}" target="_blank" aria-label="Facebook">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="{{ config('social.instagram', '#') }}" target="_blank" aria-label="Instagram">
                    <i class="fab fa-instagram"></i>
                </a>
                <a href="{{ config('social.tiktok', '#') }}" target="_blank" aria-label="TikTok">
                    <i class="fab fa-tiktok"></i>
                </a>
            </div>
        </div>

        {{-- Columna 2: Categorías Rápidas --}}
        <div class="footer-col">
            <h4>Lo más buscado</h4>
            <ul>
                <li><a href="#"><i class="fas fa-angle-right" style="font-size: 0.8em; margin-right: 5px;"></i>
                        Laptops Gamer</a></li>
                <li><a href="#"><i class="fas fa-angle-right" style="font-size: 0.8em; margin-right: 5px;"></i>
                        Periféricos</a></li>
                <li><a href="#"><i class="fas fa-angle-right" style="font-size: 0.8em; margin-right: 5px;"></i>
                        Componentes PC</a></li>
                <li><a href="#"><i class="fas fa-angle-right" style="font-size: 0.8em; margin-right: 5px;"></i>
                        Monitores</a></li>
            </ul>
        </div>

        {{-- Columna 3: Enlaces Legales --}}
        <div class="footer-col">
            <h4>Atención al Cliente</h4>
            <ul>
                <li><a href="#">Centro de Ayuda</a></li>
                <li><a href="#">Estado de mi pedido</a></li>
                <li><a href="#">Políticas de Garantía</a></li>
                <li><a href="#">Términos y Condiciones</a></li>
            </ul>
        </div>

        {{-- Columna 4: Contacto --}}
        <div class="footer-col footer-contact-info">
            <h4>DEVIOZ S.A.C.</h4>
            <p>
                <i class="fas fa-map-marker-alt"></i>
                <span>Lima, Perú</span>
            </p>
            <p>
                <i class="fas fa-envelope"></i>
                <span>soporte@devioz.com</span>
            </p>
            <p>
                <i class="fab fa-whatsapp"></i>
                <span>+51 987 654 321</span>
            </p>
            <p>
                <i class="far fa-clock"></i>
                <span>Lunes a Sábado<br>09:00 AM - 06:00 PM</span>
            </p>
        </div>
    </div>

    {{-- FOOTER INFERIOR --}}
    <div class="footer-bottom">
        <div class="footer-bottom-content">
            <div class="footer-copyright">
                &copy; {{ date('Y') }} DEVIOZ S.A.C. Todos los derechos reservados.
            </div>
            <div class="footer-payments">
                <i class="fab fa-cc-visa" title="Visa"></i>
                <i class="fab fa-cc-mastercard" title="Mastercard"></i>
                <i class="fab fa-cc-paypal" title="PayPal"></i>
                <i class="fab fa-cc-amex" title="American Express"></i>
            </div>
        </div>
    </div>
</footer>
