<?php
/**
 * La Chingada Restaurant — Página de Contacto
 */

require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . '/db.php';
require_once INCLUDES_PATH . '/functions.php';
require_once INCLUDES_PATH . '/seo.php';

date_default_timezone_set(SITE_TIMEZONE);
initSession();

if (getSetting('maintenance_mode') === '1') {
    require_once __DIR__ . '/maintenance.php';
    exit;
}

$currentPage = 'contacto';
$seoPage     = 'contacto';

// ── Procesar formulario de contacto (AJAX) ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json; charset=UTF-8');

    if (!verifyCsrf()) {
        jsonError('Token de seguridad inválido.', 403);
    }

    $name    = sanitizeStr($_POST['name'] ?? '', 150);
    $email   = sanitizeEmail($_POST['email'] ?? '');
    $subject = sanitizeStr($_POST['subject'] ?? '', 200);
    $message = sanitizeStr($_POST['message'] ?? '', 2000);

    if (empty($name) || !isValidEmail($email) || empty($message)) {
        jsonError('Por favor completa todos los campos requeridos.');
    }

    $restaurantEmail = getSetting('notification_email', getSetting('restaurant_email', ''));
    $siteName        = getSetting('site_name', 'La Chingada');

    if ($restaurantEmail) {
        $body = emailTemplate(
            'Nuevo mensaje de contacto',
            "<h2>Nuevo Mensaje de Contacto</h2>
            <div class='info-box'>
                <p><strong>Nombre:</strong> {$name}</p>
                <p><strong>Email:</strong> {$email}</p>
                <p><strong>Asunto:</strong> " . e($subject) . "</p>
                <p><strong>Mensaje:</strong></p>
                <p>" . nl2br(e($message)) . "</p>
            </div>"
        );
        $sent = sendEmail($restaurantEmail, "[Contacto] " . ($subject ?: "Mensaje de {$name}"), $body, $email);
    } else {
        $sent = false;
    }

    if ($sent || !$restaurantEmail) {
        // Respuesta automática al cliente
        $autoReplyBody = emailTemplate(
            "Hemos recibido tu mensaje - {$siteName}",
            "<h2>¡Gracias por contactarnos, {$name}!</h2>
            <p>Hemos recibido tu mensaje y te responderemos a la brevedad.</p>
            <div class='info-box'>
                <p>Si tu consulta es urgente, también puedes llamarnos directamente.</p>
            </div>
            <p>¡Buen provecho! 🌮</p>"
        );
        sendEmail($email, "Recibimos tu mensaje - {$siteName}", $autoReplyBody);

        jsonSuccess([], '¡Mensaje enviado! Te responderemos pronto.');
    } else {
        jsonError('Error al enviar el mensaje. Por favor intenta nuevamente o llámanos directamente.');
    }
}

// ── Cargar datos del restaurante ───────────────────────────────────────────
$settings = getSettings([
    'site_name', 'restaurant_address', 'restaurant_phone',
    'restaurant_email', 'restaurant_hours_text',
    'maps_embed_url', 'restaurant_lat', 'restaurant_lng',
    'social_instagram', 'social_facebook', 'social_tiktok',
    'whatsapp_number', 'whatsapp_message',
]);

$historia = [];
if (function_exists('dbQueryOne')) {
    $row = dbQueryOne(
        'SELECT content FROM ' . DB_PREFIX . 'page_sections WHERE page = ? AND section = ?',
        ['home', 'historia']
    );
    $historia = $row ? (json_decode($row['content'], true) ?: []) : [];
}

require_once INCLUDES_PATH . '/header.php';
?>

<main class="contacto-page">

    <!-- Hero -->
    <div class="contacto-hero">
        <div class="container">
            <p class="section-tag" style="color:var(--color-secondary);">📍 Contacto</p>
            <h1>Contáctanos</h1>
            <p style="color:rgba(255,255,255,.75); max-width:550px; margin:12px auto 0; font-size:1.05rem;">
                Estamos aquí para atenderte. Escríbenos, llámanos o visítanos directamente.
            </p>
        </div>
    </div>

    <!-- Historia del restaurante (sección "nosotros") -->
    <?php if (!empty($historia['text_1']) || !empty($historia['text_2'])): ?>
    <section class="section historia-section" id="historia">
        <div class="container">
            <div class="historia-grid">
                <div class="historia-image fade-in-left">
                    <?php if (!empty($historia['image'])): ?>
                        <img src="<?= e(imgUrl($historia['image'])) ?>"
                             alt="<?= e($historia['image_alt'] ?? 'Nuestra Historia') ?>"
                             loading="lazy">
                    <?php else: ?>
                        <img src="<?= e(SITE_URL) ?>/assets/images/historia-default.jpg"
                             alt="Nuestra Historia" loading="lazy">
                    <?php endif; ?>
                </div>

                <div class="historia-content fade-in-right">
                    <p class="section-tag">Sobre Nosotros</p>
                    <h2 class="section-title">
                        <?= e($historia['title'] ?? 'De México al Caribe con Amor') ?>
                    </h2>
                    <?php if (!empty($historia['text_1'])): ?>
                    <p><?= e($historia['text_1']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($historia['text_2'])): ?>
                    <p><?= e($historia['text_2']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($historia['text_3'])): ?>
                    <p><?= e($historia['text_3']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Contacto grid: formulario + datos -->
    <section class="section" style="background:var(--color-cream);">
        <div class="container">
            <div class="contacto-grid">

                <!-- Formulario de contacto -->
                <div class="fade-in-left">
                    <div class="reserva-form-card">
                        <h2 style="margin-bottom:8px; font-size:1.8rem;">Envíanos un Mensaje</h2>
                        <p style="color:var(--color-text-light); margin-bottom:28px; font-size:.9rem;">
                            Responderemos a tu consulta en menos de 24 horas.
                        </p>

                        <form id="contacto-form"
                              action="<?= e(SITE_URL) ?>/contacto"
                              method="POST"
                              novalidate>
                            <?= csrfField() ?>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="contact-name" class="form-label">
                                        Nombre <span class="required">*</span>
                                    </label>
                                    <input type="text"
                                           id="contact-name"
                                           name="name"
                                           class="form-control"
                                           placeholder="Tu nombre"
                                           required
                                           autocomplete="name">
                                </div>

                                <div class="form-group">
                                    <label for="contact-email" class="form-label">
                                        Email <span class="required">*</span>
                                    </label>
                                    <input type="email"
                                           id="contact-email"
                                           name="email"
                                           class="form-control"
                                           placeholder="correo@ejemplo.com"
                                           required
                                           autocomplete="email">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="contact-subject" class="form-label">Asunto</label>
                                <input type="text"
                                       id="contact-subject"
                                       name="subject"
                                       class="form-control"
                                       placeholder="¿En qué podemos ayudarte?"
                                       maxlength="200">
                            </div>

                            <div class="form-group">
                                <label for="contact-message" class="form-label">
                                    Mensaje <span class="required">*</span>
                                </label>
                                <textarea id="contact-message"
                                          name="message"
                                          class="form-control"
                                          rows="5"
                                          placeholder="Escribe tu mensaje aquí..."
                                          required></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg" style="width:100%;">
                                Enviar Mensaje
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Datos de contacto -->
                <div class="fade-in-right">
                    <div style="padding: 10px 0;">
                        <h3 style="margin-bottom:24px; font-size:1.6rem;">Información de Contacto</h3>

                        <div class="ubicacion-details">
                            <?php if (!empty($settings['restaurant_address'])): ?>
                            <div class="ubicacion-detail">
                                <span class="detail-icon">📍</span>
                                <div class="detail-text">
                                    <strong>Dirección</strong>
                                    <?= e($settings['restaurant_address']) ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($settings['restaurant_phone'])): ?>
                            <div class="ubicacion-detail">
                                <span class="detail-icon">📞</span>
                                <div class="detail-text">
                                    <strong>Teléfono</strong>
                                    <a href="tel:<?= e(preg_replace('/[^+0-9]/', '', $settings['restaurant_phone'])) ?>">
                                        <?= e($settings['restaurant_phone']) ?>
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($settings['restaurant_email'])): ?>
                            <div class="ubicacion-detail">
                                <span class="detail-icon">✉️</span>
                                <div class="detail-text">
                                    <strong>Email</strong>
                                    <a href="mailto:<?= e($settings['restaurant_email']) ?>">
                                        <?= e($settings['restaurant_email']) ?>
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php
                            $wpNum = preg_replace('/[^0-9]/', '', $settings['whatsapp_number'] ?? '');
                            $wpMsg = urlencode($settings['whatsapp_message'] ?? '¡Hola! Tengo una consulta.');
                            if ($wpNum):
                            ?>
                            <div class="ubicacion-detail">
                                <span class="detail-icon">💬</span>
                                <div class="detail-text">
                                    <strong>WhatsApp</strong>
                                    <a href="https://wa.me/<?= e($wpNum) ?>?text=<?= e($wpMsg) ?>"
                                       target="_blank" rel="noopener noreferrer">
                                        Escríbenos en WhatsApp
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Horarios -->
                        <?php if (!empty($settings['restaurant_hours_text'])): ?>
                        <div class="horario-list" style="margin-top:32px;">
                            <h4 style="color:var(--color-primary); margin-bottom:16px;">⏰ Horarios de Atención</h4>
                            <?php
                            $horarioLines = explode("\n", trim($settings['restaurant_hours_text']));
                            foreach ($horarioLines as $line):
                                $line = trim($line);
                                if (empty($line)) continue;
                                $parts = explode(':', $line, 2);
                                $dia  = trim($parts[0] ?? $line);
                                $hora = isset($parts[1]) ? trim(implode(':', array_slice(explode(':', $line), 1))) : '';
                            ?>
                            <div class="horario-item">
                                <span class="horario-dia"><?= e($dia) ?></span>
                                <span class="<?= stripos($hora, 'cerrado') !== false ? 'horario-cerrado' : 'horario-hora' ?>">
                                    <?= e($hora ?: $dia) ?>
                                </span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Redes sociales -->
                        <div style="margin-top:32px;">
                            <h4 style="margin-bottom:16px;">Síguenos en Redes Sociales</h4>
                            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                                <?php if (!empty($settings['social_instagram'])): ?>
                                <a href="<?= e($settings['social_instagram']) ?>" target="_blank"
                                   rel="noopener noreferrer" class="social-link social-instagram"
                                   aria-label="Instagram">
                                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                    </svg>
                                </a>
                                <?php endif; ?>
                                <?php if (!empty($settings['social_facebook'])): ?>
                                <a href="<?= e($settings['social_facebook']) ?>" target="_blank"
                                   rel="noopener noreferrer" class="social-link social-facebook"
                                   aria-label="Facebook">
                                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                    </svg>
                                </a>
                                <?php endif; ?>
                                <?php if (!empty($settings['social_tiktok'])): ?>
                                <a href="<?= e($settings['social_tiktok']) ?>" target="_blank"
                                   rel="noopener noreferrer" class="social-link social-tiktok"
                                   aria-label="TikTok">
                                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                        <path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.33-6.34V8.66a8.17 8.17 0 004.78 1.52V6.72a4.85 4.85 0 01-1.01-.03z"/>
                                    </svg>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Mapa -->
            <?php if (!empty($settings['maps_embed_url'])): ?>
            <div class="contacto-mapa fade-in" style="margin-top:40px;" id="ubicacion">
                <h3 style="margin-bottom:20px; font-size:1.6rem;">📍 Encuéntranos</h3>
                <iframe src="<?= e($settings['maps_embed_url']) ?>"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        title="Ubicación del Restaurante">
                </iframe>
            </div>
            <?php endif; ?>

        </div>
    </section>

</main>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
