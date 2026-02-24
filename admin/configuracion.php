<?php
/**
 * Panel de Administración — Configuración General
 */

require_once dirname(__DIR__) . '/config.php';
require_once INCLUDES_PATH . '/db.php';
require_once INCLUDES_PATH . '/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

date_default_timezone_set(SITE_TIMEZONE);
requireAuth();

$message = '';
$error   = '';

// ── Eliminar install.php ───────────────────────────────────────────────────
if (isset($_GET['delete_install']) && verifyCsrf()) {
    $installFile = SITE_PATH . '/install.php';
    if (file_exists($installFile)) {
        unlink($installFile);
        $message = '✅ install.php eliminado correctamente.';
    }
}

// ── Guardar configuración general ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_config'])) {
    if (!verifyCsrf()) { $error = 'Token CSRF inválido.'; }
    else {
        $settingsToSave = [
            'site_name'             => sanitizeStr($_POST['site_name'] ?? '', 100),
            'site_slogan'           => sanitizeStr($_POST['site_slogan'] ?? '', 200),
            'site_description'      => sanitizeStr($_POST['site_description'] ?? '', 500),
            'restaurant_address'    => sanitizeStr($_POST['restaurant_address'] ?? '', 300),
            'restaurant_phone'      => sanitizeStr($_POST['restaurant_phone'] ?? '', 50),
            'restaurant_email'      => sanitizeEmail($_POST['restaurant_email'] ?? ''),
            'notification_email'    => sanitizeEmail($_POST['notification_email'] ?? ''),
            'restaurant_hours_text' => sanitizeStr($_POST['restaurant_hours_text'] ?? '', 1000),
            'restaurant_lat'        => sanitizeStr($_POST['restaurant_lat'] ?? '', 30),
            'restaurant_lng'        => sanitizeStr($_POST['restaurant_lng'] ?? '', 30),
            'maps_embed_url'        => sanitizeStr($_POST['maps_embed_url'] ?? '', 1000),
            'whatsapp_number'       => sanitizeStr($_POST['whatsapp_number'] ?? '', 30),
            'whatsapp_message'      => sanitizeStr($_POST['whatsapp_message'] ?? '', 500),
            'social_instagram'      => sanitizeStr($_POST['social_instagram'] ?? '', 300),
            'social_facebook'       => sanitizeStr($_POST['social_facebook'] ?? '', 300),
            'social_tiktok'         => sanitizeStr($_POST['social_tiktok'] ?? '', 300),
            'social_twitter'        => sanitizeStr($_POST['social_twitter'] ?? '', 300),
            'social_youtube'        => sanitizeStr($_POST['social_youtube'] ?? '', 300),
            'footer_text'           => sanitizeStr($_POST['footer_text'] ?? '', 300),
            'color_primary'         => preg_match('/^#[0-9A-Fa-f]{6}$/', $_POST['color_primary'] ?? '') ? $_POST['color_primary'] : '#C0392B',
            'color_secondary'       => preg_match('/^#[0-9A-Fa-f]{6}$/', $_POST['color_secondary'] ?? '') ? $_POST['color_secondary'] : '#E67E22',
            'reservation_info_extra'=> sanitizeStr($_POST['reservation_info_extra'] ?? '', 500),
        ];

        // Imágenes: logo y favicon
        if (!empty($_FILES['site_logo']['name'])) {
            $old = getSetting('site_logo');
            if ($old) deleteImage($old);
            $res = adminUploadImage('site_logo', 'brand');
            if ($res['success']) $settingsToSave['site_logo'] = $res['filename'];
            else $error = $res['error'];
        }
        if (!empty($_FILES['favicon']['name'])) {
            $old = getSetting('favicon');
            if ($old) deleteImage($old);
            $res = adminUploadImage('favicon', 'brand');
            if ($res['success']) $settingsToSave['favicon'] = $res['filename'];
        }
        if (!empty($_FILES['og_default_image']['name'])) {
            $old = getSetting('og_default_image');
            if ($old) deleteImage($old);
            $res = adminUploadImage('og_default_image', 'brand');
            if ($res['success']) $settingsToSave['og_default_image'] = $res['filename'];
        }

        if (empty($error)) {
            foreach ($settingsToSave as $key => $val) {
                setSetting($key, $val);
            }
            $message = 'Configuración guardada correctamente.';
        }
    }
}

// ── Cambiar contraseña ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!verifyCsrf()) { $error = 'Token CSRF inválido.'; }
    else {
        $newPw  = $_POST['new_password']     ?? '';
        $confPw = $_POST['confirm_password'] ?? '';

        if (strlen($newPw) < 8) {
            $error = 'La contraseña debe tener al menos 8 caracteres.';
        } elseif ($newPw !== $confPw) {
            $error = 'Las contraseñas no coinciden.';
        } else {
            if (changeAdminPassword($newPw)) {
                $message = '✅ Contraseña actualizada correctamente.';
            } else {
                $error = 'Error al actualizar la contraseña.';
            }
        }
    }
}

// ── Cargar configuración actual ────────────────────────────────────────────
$config = getSettings([
    'site_name', 'site_slogan', 'site_description', 'site_logo', 'favicon',
    'restaurant_address', 'restaurant_phone', 'restaurant_email',
    'notification_email', 'restaurant_hours_text', 'restaurant_lat',
    'restaurant_lng', 'maps_embed_url', 'whatsapp_number', 'whatsapp_message',
    'social_instagram', 'social_facebook', 'social_tiktok',
    'social_twitter', 'social_youtube', 'footer_text',
    'color_primary', 'color_secondary', 'og_default_image',
    'reservation_info_extra',
]);

adminHeader('Configuración General', 'configuracion');
?>

<?php if ($message): ?>
<div class="alert-admin alert-admin-success"><?= e($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert-admin alert-admin-danger"><?= e($error) ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <?= csrfField() ?>

    <div class="admin-grid-2">

        <!-- Columna izquierda -->
        <div>

            <!-- Información básica -->
            <div class="admin-card" style="margin-bottom:20px;">
                <div class="card-header"><h3 class="card-title">🏪 Información del Restaurante</h3></div>
                <div class="card-body">
                    <div class="form-group-admin">
                        <label class="form-label-admin">Nombre del restaurante</label>
                        <input type="text" name="site_name" class="form-control-admin"
                               value="<?= e($config['site_name']) ?>" placeholder="La Chingada">
                    </div>
                    <div class="form-group-admin">
                        <label class="form-label-admin">Slogan</label>
                        <input type="text" name="site_slogan" class="form-control-admin"
                               value="<?= e($config['site_slogan']) ?>"
                               placeholder="Sabores de México en Cartagena">
                    </div>
                    <div class="form-group-admin">
                        <label class="form-label-admin">Descripción breve (para SEO y footer)</label>
                        <textarea name="site_description" class="form-control-admin" rows="3"><?= e($config['site_description']) ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Contacto y ubicación -->
            <div class="admin-card" style="margin-bottom:20px;">
                <div class="card-header"><h3 class="card-title">📍 Contacto y Ubicación</h3></div>
                <div class="card-body">
                    <div class="form-group-admin">
                        <label class="form-label-admin">Dirección completa</label>
                        <input type="text" name="restaurant_address" class="form-control-admin"
                               value="<?= e($config['restaurant_address']) ?>"
                               placeholder="Calle 5 #3-25, Getsemaní, Cartagena">
                    </div>
                    <div class="form-grid-2">
                        <div class="form-group-admin">
                            <label class="form-label-admin">Teléfono</label>
                            <input type="text" name="restaurant_phone" class="form-control-admin"
                                   value="<?= e($config['restaurant_phone']) ?>"
                                   placeholder="+57 5 123 4567">
                        </div>
                        <div class="form-group-admin">
                            <label class="form-label-admin">Email del restaurante</label>
                            <input type="email" name="restaurant_email" class="form-control-admin"
                                   value="<?= e($config['restaurant_email']) ?>">
                        </div>
                    </div>
                    <div class="form-group-admin">
                        <label class="form-label-admin">Email para recibir notificaciones</label>
                        <input type="email" name="notification_email" class="form-control-admin"
                               value="<?= e($config['notification_email']) ?>"
                               placeholder="reservas@lachingada.com">
                        <small style="color:var(--admin-muted)">A este email llegarán las reservas y mensajes de contacto.</small>
                    </div>
                    <div class="form-group-admin">
                        <label class="form-label-admin">Horarios de atención</label>
                        <textarea name="restaurant_hours_text" class="form-control-admin" rows="7"
                                  placeholder="Lunes - Viernes: 12:00pm - 10:00pm&#10;Sábados: 11:00am - 11:00pm&#10;Domingos: 11:00am - 9:00pm"><?= e($config['restaurant_hours_text']) ?></textarea>
                        <small style="color:var(--admin-muted)">Un horario por línea. Formato: Día: hora - hora</small>
                    </div>
                    <div class="form-grid-2">
                        <div class="form-group-admin">
                            <label class="form-label-admin">Latitud (Google Maps)</label>
                            <input type="text" name="restaurant_lat" class="form-control-admin"
                                   value="<?= e($config['restaurant_lat']) ?>"
                                   placeholder="10.4236">
                        </div>
                        <div class="form-group-admin">
                            <label class="form-label-admin">Longitud (Google Maps)</label>
                            <input type="text" name="restaurant_lng" class="form-control-admin"
                                   value="<?= e($config['restaurant_lng']) ?>"
                                   placeholder="-75.5383">
                        </div>
                    </div>
                    <div class="form-group-admin">
                        <label class="form-label-admin">URL del mapa embebido de Google Maps</label>
                        <input type="url" name="maps_embed_url" class="form-control-admin"
                               value="<?= e($config['maps_embed_url']) ?>"
                               placeholder="https://www.google.com/maps/embed?pb=...">
                        <small style="color:var(--admin-muted)">Ve a Google Maps → Compartir → Insertar mapa → copia solo el src del iframe.</small>
                    </div>
                </div>
            </div>

            <!-- WhatsApp -->
            <div class="admin-card" style="margin-bottom:20px;">
                <div class="card-header"><h3 class="card-title">💬 WhatsApp</h3></div>
                <div class="card-body">
                    <div class="form-group-admin">
                        <label class="form-label-admin">Número de WhatsApp (con código de país)</label>
                        <input type="text" name="whatsapp_number" class="form-control-admin"
                               value="<?= e($config['whatsapp_number']) ?>"
                               placeholder="573001234567">
                        <small style="color:var(--admin-muted)">Ej: 573001234567 (sin +, guiones ni espacios)</small>
                    </div>
                    <div class="form-group-admin">
                        <label class="form-label-admin">Mensaje predeterminado de WhatsApp</label>
                        <textarea name="whatsapp_message" class="form-control-admin" rows="2"><?= e($config['whatsapp_message']) ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Nota adicional en reservas -->
            <div class="admin-card" style="margin-bottom:20px;">
                <div class="card-header"><h3 class="card-title">📅 Reservas</h3></div>
                <div class="card-body">
                    <div class="form-group-admin">
                        <label class="form-label-admin">Nota adicional en la página de reservas</label>
                        <textarea name="reservation_info_extra" class="form-control-admin" rows="3"
                                  placeholder="Ej: Para grupos de más de 10 personas, contactar directamente."><?= e($config['reservation_info_extra']) ?></textarea>
                    </div>
                </div>
            </div>

        </div>

        <!-- Columna derecha -->
        <div>

            <!-- Identidad visual -->
            <div class="admin-card" style="margin-bottom:20px;">
                <div class="card-header"><h3 class="card-title">🎨 Identidad Visual</h3></div>
                <div class="card-body">
                    <!-- Logo -->
                    <div class="form-group-admin">
                        <label class="form-label-admin">Logo del restaurante</label>
                        <?php if (!empty($config['site_logo'])): ?>
                        <img src="<?= e(imgUrl($config['site_logo'])) ?>" alt="Logo actual"
                             style="height:60px;object-fit:contain;margin-bottom:10px;background:#1a1a2e;padding:10px;border-radius:6px;display:block;">
                        <?php endif; ?>
                        <input type="file" name="site_logo" class="form-control-admin" accept="image/*">
                        <small style="color:var(--admin-muted)">PNG con transparencia recomendado. Máx 5MB.</small>
                    </div>

                    <!-- Favicon -->
                    <div class="form-group-admin">
                        <label class="form-label-admin">Favicon</label>
                        <?php if (!empty($config['favicon'])): ?>
                        <img src="<?= e(imgUrl($config['favicon'])) ?>" alt="Favicon"
                             style="width:32px;height:32px;object-fit:contain;margin-bottom:8px;display:block;">
                        <?php endif; ?>
                        <input type="file" name="favicon" class="form-control-admin"
                               accept="image/x-icon,image/png">
                    </div>

                    <!-- Imagen OG -->
                    <div class="form-group-admin">
                        <label class="form-label-admin">Imagen por defecto para redes sociales (Open Graph)</label>
                        <?php if (!empty($config['og_default_image'])): ?>
                        <img src="<?= e(imgUrl($config['og_default_image'])) ?>" alt="OG Image"
                             style="width:100%;height:120px;object-fit:cover;border-radius:8px;margin-bottom:8px;">
                        <?php endif; ?>
                        <input type="file" name="og_default_image" class="form-control-admin" accept="image/*">
                        <small style="color:var(--admin-muted)">Recomendado: 1200×630px</small>
                    </div>

                    <!-- Colores -->
                    <div class="form-group-admin">
                        <label class="form-label-admin">Colores del sitio</label>
                        <div class="color-pickers">
                            <div class="color-picker-group">
                                <label>Color Primario</label>
                                <input type="color" name="color_primary" class="color-input"
                                       value="<?= e($config['color_primary'] ?: '#C0392B') ?>">
                                <input type="text" class="form-control-admin color-hex"
                                       value="<?= e($config['color_primary'] ?: '#C0392B') ?>"
                                       maxlength="7"
                                       onchange="document.querySelector('[name=color_primary]').value=this.value">
                            </div>
                            <div class="color-picker-group">
                                <label>Color Secundario</label>
                                <input type="color" name="color_secondary" class="color-input"
                                       value="<?= e($config['color_secondary'] ?: '#E67E22') ?>">
                                <input type="text" class="form-control-admin color-hex"
                                       value="<?= e($config['color_secondary'] ?: '#E67E22') ?>"
                                       maxlength="7"
                                       onchange="document.querySelector('[name=color_secondary]').value=this.value">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Redes Sociales -->
            <div class="admin-card" style="margin-bottom:20px;">
                <div class="card-header"><h3 class="card-title">📱 Redes Sociales</h3></div>
                <div class="card-body">
                    <?php
                    $socialNetworks = [
                        'social_instagram' => ['label' => '📸 Instagram', 'placeholder' => 'https://instagram.com/lachingada'],
                        'social_facebook'  => ['label' => '👥 Facebook', 'placeholder' => 'https://facebook.com/lachingada'],
                        'social_tiktok'    => ['label' => '🎵 TikTok', 'placeholder' => 'https://tiktok.com/@lachingada'],
                        'social_twitter'   => ['label' => '🐦 Twitter/X', 'placeholder' => 'https://twitter.com/lachingada'],
                        'social_youtube'   => ['label' => '▶️ YouTube', 'placeholder' => 'https://youtube.com/@lachingada'],
                    ];
                    foreach ($socialNetworks as $key => $data): ?>
                    <div class="form-group-admin">
                        <label class="form-label-admin"><?= $data['label'] ?></label>
                        <input type="url" name="<?= $key ?>" class="form-control-admin"
                               value="<?= e($config[$key] ?? '') ?>"
                               placeholder="<?= $data['placeholder'] ?>">
                    </div>
                    <?php endforeach; ?>
                    <div class="form-group-admin">
                        <label class="form-label-admin">Texto del footer</label>
                        <input type="text" name="footer_text" class="form-control-admin"
                               value="<?= e($config['footer_text']) ?>"
                               placeholder="Todos los sabores de México">
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div style="margin-top:8px; padding:20px 0; border-top:1px solid var(--admin-border);">
        <button type="submit" name="save_config" class="btn-admin-primary btn-admin-lg">
            💾 Guardar Toda la Configuración
        </button>
    </div>
</form>

<!-- Cambiar contraseña -->
<div class="admin-card" id="cambiar-contrasena" style="margin-top:40px;">
    <div class="card-header">
        <h3 class="card-title">🔐 Cambiar Contraseña</h3>
    </div>
    <div class="card-body">
        <form method="POST" style="max-width:400px;">
            <?= csrfField() ?>
            <div class="form-group-admin">
                <label class="form-label-admin">Nueva Contraseña</label>
                <input type="password" name="new_password" class="form-control-admin"
                       placeholder="Mínimo 8 caracteres" required minlength="8">
            </div>
            <div class="form-group-admin">
                <label class="form-label-admin">Confirmar Contraseña</label>
                <input type="password" name="confirm_password" class="form-control-admin"
                       placeholder="Repetir contraseña" required>
            </div>
            <button type="submit" name="change_password" class="btn-admin-primary">
                🔐 Actualizar Contraseña
            </button>
        </form>
    </div>
</div>

<!-- Opciones de seguridad -->
<?php if (file_exists(SITE_PATH . '/install.php')): ?>
<div class="admin-card" style="margin-top:20px; border-left:4px solid #ef4444;">
    <div class="card-header">
        <h3 class="card-title" style="color:#ef4444;">⚠️ Seguridad</h3>
    </div>
    <div class="card-body">
        <p style="color:var(--admin-muted);margin-bottom:16px;">
            El archivo <code>install.php</code> sigue presente en el servidor. Esto es un riesgo de seguridad.
        </p>
        <a href="?delete_install=1&<?= CSRF_TOKEN_NAME ?>=<?= e(csrfToken()) ?>"
           class="btn-admin-danger"
           onclick="return confirm('¿Eliminar install.php? Esta acción no se puede deshacer.')">
            🗑 Eliminar install.php
        </a>
    </div>
</div>
<?php endif; ?>

<script>
// Sincronizar color picker con input de texto
document.querySelectorAll('.color-input').forEach(picker => {
    picker.addEventListener('input', function() {
        this.nextElementSibling.value = this.value;
    });
});
document.querySelectorAll('.color-hex').forEach(input => {
    input.addEventListener('input', function() {
        if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
            this.previousElementSibling.value = this.value;
        }
    });
});
</script>

<?php adminFooter(); ?>
