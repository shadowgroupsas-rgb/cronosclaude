<?php
/**
 * Footer global del sitio
 */

$footerConfig = getSettings([
    'site_name', 'site_description', 'site_logo',
    'restaurant_address', 'restaurant_phone', 'restaurant_email',
    'restaurant_hours_text',
    'social_instagram', 'social_facebook', 'social_tiktok',
    'social_twitter', 'social_youtube',
    'whatsapp_number', 'whatsapp_message',
    'footer_text', 'maps_embed_url',
]);

$siteName   = $footerConfig['site_name'] ?: 'La Chingada';
$wpNumber   = preg_replace('/[^0-9]/', '', $footerConfig['whatsapp_number'] ?: '');
$wpMessage  = urlencode($footerConfig['whatsapp_message'] ?: '¡Hola! Me gustaría hacer una reserva en ' . $siteName);
$wpUrl      = $wpNumber ? "https://wa.me/{$wpNumber}?text={$wpMessage}" : '#';
$year       = date('Y');
?>

<!-- ══════════════════════════════════════════════════
     FOOTER PRINCIPAL
════════════════════════════════════════════════════ -->
<footer class="footer" role="contentinfo">
    <!-- Separador papel picado decorativo -->
    <div class="papel-picado-strip" aria-hidden="true">
        <div class="papel-picado-flags">
            <?php
            $colores = ['#C0392B','#E67E22','#F1C40F','#27AE60','#8E44AD','#2980B9','#E74C3C','#16A085'];
            for ($i = 0; $i < 20; $i++) {
                $color = $colores[$i % count($colores)];
                echo "<div class=\"bandera\" style=\"background:{$color}\"></div>";
            }
            ?>
        </div>
    </div>

    <div class="footer-main">
        <div class="footer-container">

            <!-- Columna 1: Logo + descripción -->
            <div class="footer-col footer-brand">
                <?php if (!empty($footerConfig['site_logo'])): ?>
                    <img src="<?= e(imgUrl($footerConfig['site_logo'])) ?>"
                         alt="<?= e($siteName) ?>" class="footer-logo">
                <?php else: ?>
                    <div class="footer-logo-text">
                        <span class="footer-la">La</span>
                        <span class="footer-name">Chingada</span>
                    </div>
                <?php endif; ?>
                <p class="footer-desc">
                    <?= e($footerConfig['site_description'] ?: 'Auténtica cocina mexicana en el corazón de Cartagena. Sabores, tradición y pasión en cada plato.') ?>
                </p>

                <!-- Redes sociales -->
                <div class="footer-social" role="list" aria-label="Redes sociales">
                    <?php if (!empty($footerConfig['social_instagram'])): ?>
                    <a href="<?= e($footerConfig['social_instagram']) ?>" target="_blank" rel="noopener noreferrer"
                       role="listitem" aria-label="Instagram" class="social-link social-instagram">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                        </svg>
                    </a>
                    <?php endif; ?>

                    <?php if (!empty($footerConfig['social_facebook'])): ?>
                    <a href="<?= e($footerConfig['social_facebook']) ?>" target="_blank" rel="noopener noreferrer"
                       role="listitem" aria-label="Facebook" class="social-link social-facebook">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                    </a>
                    <?php endif; ?>

                    <?php if (!empty($footerConfig['social_tiktok'])): ?>
                    <a href="<?= e($footerConfig['social_tiktok']) ?>" target="_blank" rel="noopener noreferrer"
                       role="listitem" aria-label="TikTok" class="social-link social-tiktok">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.33-6.34V8.66a8.17 8.17 0 004.78 1.52V6.72a4.85 4.85 0 01-1.01-.03z"/>
                        </svg>
                    </a>
                    <?php endif; ?>

                    <?php if ($wpNumber): ?>
                    <a href="<?= e($wpUrl) ?>" target="_blank" rel="noopener noreferrer"
                       role="listitem" aria-label="WhatsApp" class="social-link social-whatsapp">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Columna 2: Navegación -->
            <div class="footer-col footer-nav">
                <h3 class="footer-title">Explorar</h3>
                <nav aria-label="Navegación footer">
                    <ul>
                        <li><a href="<?= e(SITE_URL) ?>/">Inicio</a></li>
                        <li><a href="<?= e(SITE_URL) ?>/menu">Nuestro Menú</a></li>
                        <li><a href="<?= e(SITE_URL) ?>/reservas">Reservar Mesa</a></li>
                        <li><a href="<?= e(SITE_URL) ?>/contacto">Contacto</a></li>
                        <li><a href="<?= e(SITE_URL) ?>/contacto#historia">Nuestra Historia</a></li>
                    </ul>
                </nav>
            </div>

            <!-- Columna 3: Contacto -->
            <div class="footer-col footer-contact">
                <h3 class="footer-title">Encuéntranos</h3>
                <address>
                    <?php if (!empty($footerConfig['restaurant_address'])): ?>
                    <div class="contact-item">
                        <span class="contact-icon" aria-hidden="true">📍</span>
                        <span><?= e($footerConfig['restaurant_address']) ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($footerConfig['restaurant_phone'])): ?>
                    <div class="contact-item">
                        <span class="contact-icon" aria-hidden="true">📞</span>
                        <a href="tel:<?= e(preg_replace('/[^+0-9]/', '', $footerConfig['restaurant_phone'])) ?>">
                            <?= e($footerConfig['restaurant_phone']) ?>
                        </a>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($footerConfig['restaurant_email'])): ?>
                    <div class="contact-item">
                        <span class="contact-icon" aria-hidden="true">✉️</span>
                        <a href="mailto:<?= e($footerConfig['restaurant_email']) ?>">
                            <?= e($footerConfig['restaurant_email']) ?>
                        </a>
                    </div>
                    <?php endif; ?>
                </address>

                <?php if (!empty($footerConfig['restaurant_hours_text'])): ?>
                <div class="footer-hours">
                    <h4>Horarios</h4>
                    <div class="hours-text"><?= nl2br(e($footerConfig['restaurant_hours_text'])) ?></div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Columna 4: CTA Reservas -->
            <div class="footer-col footer-cta">
                <h3 class="footer-title">¿Listo para comer?</h3>
                <p>Haz tu reserva y vive la experiencia de la auténtica cocina mexicana.</p>
                <a href="<?= e(SITE_URL) ?>/reservas" class="btn-footer-reserva">
                    Reservar Ahora
                </a>
                <?php if ($wpNumber): ?>
                <a href="<?= e($wpUrl) ?>" target="_blank" rel="noopener noreferrer"
                   class="btn-footer-wp">
                    💬 Escríbenos
                </a>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- Footer bottom bar -->
    <div class="footer-bottom">
        <div class="footer-container">
            <p>
                &copy; <?= $year ?> <?= e($siteName) ?>. Todos los derechos reservados.
                <?php if (!empty($footerConfig['footer_text'])): ?>
                · <?= e($footerConfig['footer_text']) ?>
                <?php endif; ?>
            </p>
            <p class="footer-made">
                Hecho con 🌮 en Cartagena, Colombia
            </p>
        </div>
    </div>
</footer>
<!-- FIN FOOTER -->

<!-- ══════════════════════════════════════════════════
     BOTÓN FLOTANTE DE WHATSAPP
════════════════════════════════════════════════════ -->
<?php if ($wpNumber): ?>
<a href="<?= e($wpUrl) ?>"
   target="_blank"
   rel="noopener noreferrer"
   class="whatsapp-float"
   aria-label="Chatea con nosotros por WhatsApp"
   title="¿Necesitas ayuda? ¡Escríbenos!">
    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
    </svg>
    <span class="wp-tooltip">¡Escríbenos!</span>
</a>
<?php endif; ?>

<!-- Scripts del sitio -->
<script src="<?= e(SITE_URL) ?>/assets/js/main.js"></script>

<!-- Scripts de terceros antes del </body> -->
<?php renderBodyScripts(); ?>

</body>
</html>
