<?php
/**
 * La Chingada Restaurant — Página de Reservas
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

$currentPage = 'reservas';
$seoPage     = 'reservas';

// ── Procesar formulario de reserva (AJAX) ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json; charset=UTF-8');

    // Verificar CSRF
    if (!verifyCsrf()) {
        jsonError('Token de seguridad inválido. Recarga la página.', 403);
    }

    // Obtener y sanitizar datos
    $name     = sanitizeStr($_POST['name'] ?? '', 150);
    $email    = sanitizeEmail($_POST['email'] ?? '');
    $phone    = sanitizeStr($_POST['phone'] ?? '', 30);
    $date     = sanitizeStr($_POST['date'] ?? '', 10);
    $time     = sanitizeStr($_POST['time'] ?? '', 8);
    $guests   = (int)($_POST['guests'] ?? 0);
    $occasion = sanitizeStr($_POST['occasion'] ?? '', 100);
    $comments = sanitizeStr($_POST['comments'] ?? '', 1000);

    // Validaciones
    $errors = [];
    if (empty($name))           $errors[] = 'El nombre es requerido';
    if (!isValidEmail($email))  $errors[] = 'El email no es válido';
    if (empty($phone))          $errors[] = 'El teléfono es requerido';
    if (empty($date))           $errors[] = 'La fecha es requerida';
    if (empty($time))           $errors[] = 'La hora es requerida';
    if ($guests < 1 || $guests > 20) $errors[] = 'Número de personas inválido';

    // Validar que la fecha no sea en el pasado
    if (!empty($date) && strtotime($date) < strtotime(date('Y-m-d'))) {
        $errors[] = 'La fecha no puede ser en el pasado';
    }

    // Validar formato de fecha y hora
    if (!empty($date) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $errors[] = 'Formato de fecha inválido';
    }

    if (!empty($errors)) {
        jsonError(implode('. ', $errors));
    }

    // Guardar en base de datos
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    dbExecute(
        'INSERT INTO ' . DB_PREFIX . 'reservations
         (name, email, phone, date, time, guests, occasion, comments, ip_address)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [$name, $email, $phone, $date, $time, $guests, $occasion, $comments, $ip]
    );

    $reservaId = (int)dbLastId();

    // Enviar emails
    $restaurantEmail = getSetting('notification_email', getSetting('restaurant_email', ''));
    $siteName        = getSetting('site_name', 'La Chingada');
    $dateFormatted   = formatDate($date);
    $timeFormatted   = formatTime($time);

    // Email al restaurante
    if ($restaurantEmail) {
        $bodyRestaurante = emailTemplate(
            "Nueva Reserva #{$reservaId}",
            "<h2>Nueva Reserva Recibida</h2>
            <div class='info-box'>
                <p><strong>ID Reserva:</strong> #{$reservaId}</p>
                <p><strong>Nombre:</strong> {$name}</p>
                <p><strong>Email:</strong> {$email}</p>
                <p><strong>Teléfono:</strong> {$phone}</p>
                <p><strong>Fecha:</strong> {$dateFormatted}</p>
                <p><strong>Hora:</strong> {$timeFormatted}</p>
                <p><strong>Personas:</strong> {$guests}</p>
                " . ($occasion ? "<p><strong>Ocasión:</strong> {$occasion}</p>" : '') . "
                " . ($comments ? "<p><strong>Comentarios:</strong> {$comments}</p>" : '') . "
            </div>
            <p><a href='" . SITE_URL . "/admin/reservas.php' class='btn'>Ver en Panel Admin</a></p>"
        );
        sendEmail($restaurantEmail, "Nueva Reserva #{$reservaId} - {$name}", $bodyRestaurante);
    }

    // Email de confirmación al cliente
    $bodyCliente = emailTemplate(
        "Reserva Confirmada - {$siteName}",
        "<h2>¡Reserva Recibida, {$name}!</h2>
        <p>Hemos recibido tu solicitud de reserva. La confirmaremos en breve.</p>
        <div class='info-box'>
            <p><strong>Fecha:</strong> {$dateFormatted}</p>
            <p><strong>Hora:</strong> {$timeFormatted}</p>
            <p><strong>Personas:</strong> {$guests}</p>
            " . ($occasion ? "<p><strong>Ocasión:</strong> {$occasion}</p>" : '') . "
        </div>
        <p>Si necesitas hacer algún cambio, contáctanos directamente.</p>
        <p>¡Te esperamos con los brazos abiertos! 🌮</p>"
    );
    sendEmail($email, "¡Reserva Recibida! - {$siteName}", $bodyCliente, $restaurantEmail);

    jsonSuccess(['reserva_id' => $reservaId], '¡Reserva enviada exitosamente!');
}

// ── Cargar datos para el formulario ───────────────────────────────────────
$settings = getSettings([
    'site_name', 'restaurant_address', 'restaurant_phone',
    'restaurant_hours_text', 'whatsapp_number', 'whatsapp_message',
    'reservation_info_extra',
]);

// Horarios disponibles
$slots = dbQuery(
    'SELECT DISTINCT time_slot FROM ' . DB_PREFIX . 'reservation_slots
     WHERE active = 1 ORDER BY time_slot ASC'
);

// Si no hay slots configurados, horarios por defecto
if (empty($slots)) {
    $defaultTimes = ['12:00', '12:30', '13:00', '13:30', '14:00', '14:30',
                     '19:00', '19:30', '20:00', '20:30', '21:00', '21:30', '22:00'];
    $slots = array_map(fn($t) => ['time_slot' => $t], $defaultTimes);
}

require_once INCLUDES_PATH . '/header.php';
?>

<main class="reservas-page">

    <!-- Hero -->
    <div class="reservas-hero">
        <div class="container">
            <p class="section-tag" style="color:var(--color-secondary);">🍽 Reservaciones</p>
            <h1 style="color:white;">Reserva tu Mesa</h1>
            <p style="color:rgba(255,255,255,.75); max-width:550px; margin:12px auto 0; font-size:1.05rem;">
                Garantiza tu lugar y déjanos preparar la mejor experiencia para ti.
                Cupos limitados cada día.
            </p>
        </div>
    </div>

    <section class="reservas-section">
        <div class="container">
            <div class="reservas-layout">

                <!-- Formulario de reserva -->
                <div>
                    <div class="reserva-form-card">
                        <h2 style="margin-bottom:8px; font-size:1.8rem;">Haz tu Reserva</h2>
                        <p style="color:var(--color-text-light); margin-bottom:28px; font-size:.9rem;">
                            * Campos obligatorios. Recibirás confirmación por email.
                        </p>

                        <?= showFlash() ?>

                        <form id="reserva-form"
                              action="<?= e(SITE_URL) ?>/reservas"
                              method="POST"
                              novalidate>
                            <?= csrfField() ?>

                            <!-- Nombre y email -->
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="reserva-name" class="form-label">
                                        Nombre completo <span class="required">*</span>
                                    </label>
                                    <input type="text"
                                           id="reserva-name"
                                           name="name"
                                           class="form-control"
                                           placeholder="Juan García"
                                           required
                                           maxlength="150"
                                           autocomplete="name">
                                    <span class="form-error" role="alert"></span>
                                </div>

                                <div class="form-group">
                                    <label for="reserva-email" class="form-label">
                                        Email <span class="required">*</span>
                                    </label>
                                    <input type="email"
                                           id="reserva-email"
                                           name="email"
                                           class="form-control"
                                           placeholder="correo@ejemplo.com"
                                           required
                                           autocomplete="email">
                                    <span class="form-error" role="alert"></span>
                                </div>
                            </div>

                            <!-- Teléfono y personas -->
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="reserva-phone" class="form-label">
                                        Teléfono / WhatsApp <span class="required">*</span>
                                    </label>
                                    <input type="tel"
                                           id="reserva-phone"
                                           name="phone"
                                           class="form-control"
                                           placeholder="+57 300 123 4567"
                                           required
                                           autocomplete="tel">
                                    <span class="form-error" role="alert"></span>
                                </div>

                                <div class="form-group">
                                    <label for="reserva-guests" class="form-label">
                                        Número de personas <span class="required">*</span>
                                    </label>
                                    <select id="reserva-guests" name="guests"
                                            class="form-control form-select" required>
                                        <option value="">Seleccionar...</option>
                                        <?php for ($i = 1; $i <= 20; $i++): ?>
                                        <option value="<?= $i ?>"><?= $i ?> persona<?= $i > 1 ? 's' : '' ?></option>
                                        <?php endfor; ?>
                                    </select>
                                    <span class="form-error" role="alert"></span>
                                </div>
                            </div>

                            <!-- Fecha y hora -->
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="reserva-date" class="form-label">
                                        Fecha <span class="required">*</span>
                                    </label>
                                    <input type="date"
                                           id="reserva-date"
                                           name="date"
                                           class="form-control"
                                           required>
                                    <span class="form-error" role="alert"></span>
                                </div>

                                <div class="form-group">
                                    <label for="reserva-time" class="form-label">
                                        Hora preferida <span class="required">*</span>
                                    </label>
                                    <select id="reserva-time" name="time"
                                            class="form-control form-select" required>
                                        <option value="">Seleccionar hora...</option>
                                        <?php foreach ($slots as $slot):
                                            $t = $slot['time_slot'];
                                            $display = date('g:i A', strtotime($t));
                                        ?>
                                        <option value="<?= e($t) ?>"><?= e($display) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span class="form-error" role="alert"></span>
                                </div>
                            </div>

                            <!-- Ocasión especial -->
                            <div class="form-group">
                                <label for="reserva-occasion" class="form-label">
                                    Ocasión especial
                                </label>
                                <select id="reserva-occasion" name="occasion"
                                        class="form-control form-select">
                                    <option value="">Ninguna</option>
                                    <option value="Cumpleaños">🎂 Cumpleaños</option>
                                    <option value="Aniversario">💑 Aniversario</option>
                                    <option value="Cena romántica">🌹 Cena romántica</option>
                                    <option value="Reunión de negocios">💼 Reunión de negocios</option>
                                    <option value="Celebración familiar">👨‍👩‍👧‍👦 Celebración familiar</option>
                                    <option value="Despedida">🥂 Despedida</option>
                                    <option value="Otra">Otra</option>
                                </select>
                            </div>

                            <!-- Comentarios adicionales -->
                            <div class="form-group">
                                <label for="reserva-comments" class="form-label">
                                    Comentarios o solicitudes especiales
                                </label>
                                <textarea id="reserva-comments"
                                          name="comments"
                                          class="form-control"
                                          rows="3"
                                          placeholder="Alergias, decoración especial, silla para bebé..."></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg" style="width:100%;">
                                🍽 Confirmar Reserva
                            </button>

                            <p style="font-size:.8rem; color:var(--color-text-light); text-align:center; margin-top:12px;">
                                Al enviar, aceptas nuestras políticas. Tu reserva está sujeta a confirmación.
                            </p>
                        </form>

                        <!-- Mensaje de éxito -->
                        <div class="reserva-success" id="reserva-success">
                            <span class="success-icon">🎉</span>
                            <h2 style="color:var(--color-primary); margin-bottom:12px;">
                                ¡Reserva Enviada!
                            </h2>
                            <p style="font-size:1.1rem; margin-bottom:8px;">
                                Hola <strong class="success-name"></strong>,<br>
                                hemos recibido tu solicitud.
                            </p>
                            <p style="color:var(--color-text-light); margin-bottom:24px;">
                                Te enviaremos un email de confirmación en breve.
                                Cualquier duda, no dudes en escribirnos.
                            </p>
                            <a href="<?= e(SITE_URL) ?>" class="btn btn-primary">
                                Volver al Inicio
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Info lateral -->
                <div>
                    <div class="reservas-info-card">
                        <h3>📋 Información Útil</h3>

                        <div class="info-detail">
                            <span class="info-detail-icon">⏰</span>
                            <div class="info-detail-text">
                                <strong>Horarios</strong>
                                <?php if (!empty($settings['restaurant_hours_text'])): ?>
                                    <?= nl2br(e($settings['restaurant_hours_text'])) ?>
                                <?php else: ?>
                                    Lun-Vie: 12pm - 10pm<br>
                                    Sáb-Dom: 11am - 11pm
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (!empty($settings['restaurant_address'])): ?>
                        <div class="info-detail">
                            <span class="info-detail-icon">📍</span>
                            <div class="info-detail-text">
                                <strong>Dirección</strong>
                                <?= e($settings['restaurant_address']) ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($settings['restaurant_phone'])): ?>
                        <div class="info-detail">
                            <span class="info-detail-icon">📞</span>
                            <div class="info-detail-text">
                                <strong>Teléfono</strong>
                                <a href="tel:<?= e(preg_replace('/[^+0-9]/', '', $settings['restaurant_phone'])) ?>"
                                   style="color:rgba(255,255,255,.8);">
                                    <?= e($settings['restaurant_phone']) ?>
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="info-detail">
                            <span class="info-detail-icon">ℹ️</span>
                            <div class="info-detail-text">
                                <strong>Política de Reservas</strong>
                                Las reservas deben hacerse con al menos 2 horas de anticipación.
                                Te contactaremos para confirmar tu reserva.
                            </div>
                        </div>

                        <?php if (!empty($settings['reservation_info_extra'])): ?>
                        <div class="info-detail">
                            <span class="info-detail-icon">📌</span>
                            <div class="info-detail-text">
                                <strong>Nota importante</strong>
                                <?= nl2br(e($settings['reservation_info_extra'])) ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php
                        $wpNum = preg_replace('/[^0-9]/', '', $settings['whatsapp_number'] ?? '');
                        $wpMsg = urlencode($settings['whatsapp_message'] ?? '¡Hola! Quiero hacer una reserva.');
                        if ($wpNum):
                        ?>
                        <div style="margin-top:24px; padding-top:20px; border-top:1px solid rgba(255,255,255,.1);">
                            <p style="color:rgba(255,255,255,.6); font-size:.85rem; margin-bottom:12px;">
                                ¿Prefieres reservar por WhatsApp?
                            </p>
                            <a href="https://wa.me/<?= e($wpNum) ?>?text=<?= e($wpMsg) ?>"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="btn btn-lg"
                               style="width:100%;background:#25D366;color:#fff;justify-content:center;border:none;">
                                💬 Reservar por WhatsApp
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </section>

</main>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
