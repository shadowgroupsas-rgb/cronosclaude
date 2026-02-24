<?php
/**
 * Panel de Administración — Configuración SEO y Pixels de Marketing
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

// ── Guardar configuración SEO de una página ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_seo_page'])) {
    if (!verifyCsrf()) { $error = 'Token CSRF inválido.'; }
    else {
        $page        = sanitizeStr($_POST['seo_page'] ?? '', 50);
        $metaTitle   = sanitizeStr($_POST['meta_title'] ?? '', 255);
        $metaDesc    = sanitizeStr($_POST['meta_description'] ?? '', 500);
        $metaKw      = sanitizeStr($_POST['meta_keywords'] ?? '', 500);
        $ogTitle     = sanitizeStr($_POST['og_title'] ?? '', 255);
        $ogDesc      = sanitizeStr($_POST['og_description'] ?? '', 500);
        $twitterCard = in_array($_POST['twitter_card'] ?? '', ['summary','summary_large_image','app','player'])
                       ? $_POST['twitter_card'] : 'summary_large_image';
        $canonical   = sanitizeStr($_POST['canonical_url'] ?? '', 500);

        // Imagen OG de la página
        $currentOgImage = sanitizeStr($_POST['current_og_image'] ?? '');
        $ogImage = $currentOgImage;

        if (!empty($_FILES['og_image']['name'])) {
            if ($currentOgImage) deleteImage($currentOgImage);
            $res = adminUploadImage('og_image', 'seo');
            if ($res['success']) $ogImage = $res['filename'];
            else $error = $res['error'];
        }

        if (empty($error)) {
            dbExecute(
                'INSERT INTO ' . DB_PREFIX . 'seo (page, meta_title, meta_description, meta_keywords, og_title, og_description, og_image, twitter_card, canonical_url)
                 VALUES (?,?,?,?,?,?,?,?,?)
                 ON DUPLICATE KEY UPDATE
                 meta_title=VALUES(meta_title), meta_description=VALUES(meta_description),
                 meta_keywords=VALUES(meta_keywords), og_title=VALUES(og_title),
                 og_description=VALUES(og_description), og_image=VALUES(og_image),
                 twitter_card=VALUES(twitter_card), canonical_url=VALUES(canonical_url)',
                [$page, $metaTitle, $metaDesc, $metaKw, $ogTitle, $ogDesc, $ogImage, $twitterCard, $canonical]
            );
            $message = "SEO de la página '{$page}' guardado correctamente.";
        }
    }
}

// ── Guardar pixels y códigos de terceros ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_pixels'])) {
    if (!verifyCsrf()) { $error = 'Token CSRF inválido.'; }
    else {
        $pixelSettings = [
            'ga4_id'             => sanitizeStr($_POST['ga4_id'] ?? '', 50),
            'gtm_id'             => sanitizeStr($_POST['gtm_id'] ?? '', 30),
            'fb_pixel_id'        => sanitizeStr($_POST['fb_pixel_id'] ?? '', 50),
            'tiktok_pixel_id'    => sanitizeStr($_POST['tiktok_pixel_id'] ?? '', 50),
            'google_ads_snippet' => $_POST['google_ads_snippet'] ?? '',
            'gsc_verification'   => sanitizeStr($_POST['gsc_verification'] ?? '', 200),
            'head_custom_code'   => $_POST['head_custom_code'] ?? '',
            'body_custom_code'   => $_POST['body_custom_code'] ?? '',
        ];

        foreach ($pixelSettings as $key => $val) {
            setSetting($key, $val);
        }

        $message = 'Pixels y códigos de rastreo guardados correctamente.';
    }
}

// ── Guardar robots.txt ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_robots'])) {
    if (!verifyCsrf()) { $error = 'Token CSRF inválido.'; }
    else {
        $robotsContent = $_POST['robots_content'] ?? '';
        // Solo permitir contenido seguro
        $robotsContent = strip_tags($robotsContent);
        file_put_contents(SITE_PATH . '/robots.txt', $robotsContent);
        $message = 'robots.txt guardado.';
    }
}

// ── Cargar datos ───────────────────────────────────────────────────────────
$pages = ['home', 'menu', 'reservas', 'contacto'];
$seoData = [];
foreach ($pages as $pg) {
    $row = dbQueryOne('SELECT * FROM ' . DB_PREFIX . 'seo WHERE page=?', [$pg]);
    $seoData[$pg] = $row ?: ['page' => $pg];
}

$pixelConfig = getSettings([
    'ga4_id', 'gtm_id', 'fb_pixel_id', 'tiktok_pixel_id',
    'google_ads_snippet', 'gsc_verification',
    'head_custom_code', 'body_custom_code',
]);

$robotsContent = file_exists(SITE_PATH . '/robots.txt')
    ? file_get_contents(SITE_PATH . '/robots.txt')
    : "User-agent: *\nAllow: /\nDisallow: /admin/\nDisallow: /install.php\n\nSitemap: " . SITE_URL . "/sitemap.xml";

$activePage = $_GET['section'] ?? 'seo-home';
adminHeader('SEO & Pixels', 'seo');
?>

<?php if ($message): ?>
<div class="alert-admin alert-admin-success"><?= e($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert-admin alert-admin-danger"><?= e($error) ?></div>
<?php endif; ?>

<!-- Tabs de secciones SEO -->
<div class="admin-tabs" style="margin-bottom:24px; flex-wrap:wrap;">
    <button class="admin-tab <?= $activePage==='seo-home' ? 'active':'' ?>"
            onclick="switchSection('seo-home', this)">🏠 Inicio</button>
    <button class="admin-tab <?= $activePage==='seo-menu' ? 'active':'' ?>"
            onclick="switchSection('seo-menu', this)">🍽 Menú</button>
    <button class="admin-tab <?= $activePage==='seo-reservas' ? 'active':'' ?>"
            onclick="switchSection('seo-reservas', this)">📅 Reservas</button>
    <button class="admin-tab <?= $activePage==='seo-contacto' ? 'active':'' ?>"
            onclick="switchSection('seo-contacto', this)">📞 Contacto</button>
    <button class="admin-tab <?= $activePage==='pixels' ? 'active':'' ?>"
            onclick="switchSection('pixels', this)">📊 Pixels & Tracking</button>
    <button class="admin-tab <?= $activePage==='robots' ? 'active':'' ?>"
            onclick="switchSection('robots', this)">🤖 Robots & Sitemap</button>
</div>

<!-- SEO por página -->
<?php foreach ($pages as $pg):
    $d = $seoData[$pg];
    $sectionId = "seo-{$pg}";
?>
<div id="<?= $sectionId ?>" class="seo-section <?= $activePage === $sectionId ? 'active-section':'' ?>">
    <div class="admin-card">
        <div class="card-header">
            <h3 class="card-title">SEO — Página "<?= ucfirst($pg) ?>"</h3>
            <a href="<?= e(SITE_URL) ?>/<?= $pg === 'home' ? '' : $pg ?>" target="_blank"
               class="btn-admin-outline">👁 Ver página</a>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <?= csrfField() ?>
                <input type="hidden" name="seo_page" value="<?= e($pg) ?>">
                <input type="hidden" name="current_og_image" value="<?= e($d['og_image'] ?? '') ?>">

                <div class="form-group-admin">
                    <label class="form-label-admin">
                        Meta Título
                        <span class="char-counter" data-max="60" data-field="meta_title_<?= $pg ?>"></span>
                    </label>
                    <input type="text" id="meta_title_<?= $pg ?>" name="meta_title"
                           class="form-control-admin" maxlength="70"
                           value="<?= e($d['meta_title'] ?? '') ?>"
                           placeholder="La Chingada - Restaurante Mexicano en Cartagena"
                           oninput="updateCounter('meta_title_<?= $pg ?>', 60)">
                    <small style="color:var(--admin-muted)">Recomendado: 50-60 caracteres</small>
                </div>

                <div class="form-group-admin">
                    <label class="form-label-admin">
                        Meta Descripción
                        <span class="char-counter" data-max="160" data-field="meta_desc_<?= $pg ?>"></span>
                    </label>
                    <textarea id="meta_desc_<?= $pg ?>" name="meta_description"
                              class="form-control-admin" rows="3" maxlength="200"
                              oninput="updateCounter('meta_desc_<?= $pg ?>', 160)"
                              placeholder="Descripción del sitio para los buscadores..."><?= e($d['meta_description'] ?? '') ?></textarea>
                    <small style="color:var(--admin-muted)">Recomendado: 150-160 caracteres</small>
                </div>

                <div class="form-group-admin">
                    <label class="form-label-admin">Keywords (separadas por coma)</label>
                    <input type="text" name="meta_keywords" class="form-control-admin"
                           value="<?= e($d['meta_keywords'] ?? '') ?>"
                           placeholder="restaurante mexicano, tacos cartagena, comida mexicana">
                </div>

                <div class="form-group-admin">
                    <label class="form-label-admin">URL Canónica</label>
                    <input type="url" name="canonical_url" class="form-control-admin"
                           value="<?= e($d['canonical_url'] ?? '') ?>"
                           placeholder="<?= e(SITE_URL) ?>/<?= $pg === 'home' ? '' : $pg ?>">
                </div>

                <hr style="border-color:var(--admin-border);margin:20px 0;">
                <h4 style="margin-bottom:16px;">Open Graph (Redes Sociales)</h4>

                <div class="form-group-admin">
                    <label class="form-label-admin">OG Título (Facebook, WhatsApp, etc.)</label>
                    <input type="text" name="og_title" class="form-control-admin"
                           value="<?= e($d['og_title'] ?? '') ?>"
                           placeholder="Dejar vacío para usar el meta título">
                </div>

                <div class="form-group-admin">
                    <label class="form-label-admin">OG Descripción</label>
                    <textarea name="og_description" class="form-control-admin" rows="2"
                              placeholder="Dejar vacío para usar la meta descripción"><?= e($d['og_description'] ?? '') ?></textarea>
                </div>

                <div class="form-grid-2">
                    <div class="form-group-admin">
                        <label class="form-label-admin">Imagen OG (1200x630px recomendado)</label>
                        <?php if (!empty($d['og_image'])): ?>
                        <img src="<?= e(imgUrl($d['og_image'])) ?>" alt="OG Image"
                             style="width:100%;height:100px;object-fit:cover;border-radius:6px;margin-bottom:8px;">
                        <?php endif; ?>
                        <input type="file" name="og_image" class="form-control-admin" accept="image/*">
                    </div>
                    <div class="form-group-admin">
                        <label class="form-label-admin">Twitter Card</label>
                        <select name="twitter_card" class="form-control-admin">
                            <option value="summary" <?= ($d['twitter_card'] ?? '') === 'summary' ? 'selected':'' ?>>Summary</option>
                            <option value="summary_large_image" <?= ($d['twitter_card'] ?? 'summary_large_image') === 'summary_large_image' ? 'selected':'' ?>>Summary Large Image</option>
                        </select>
                    </div>
                </div>

                <button type="submit" name="save_seo_page" class="btn-admin-primary">
                    💾 Guardar SEO de "<?= ucfirst($pg) ?>"
                </button>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Pixels y Tracking -->
<div id="pixels" class="seo-section <?= $activePage === 'pixels' ? 'active-section':'' ?>">
    <form method="POST">
        <?= csrfField() ?>

        <div class="admin-grid-2">
            <div>
                <div class="admin-card" style="margin-bottom:20px;">
                    <div class="card-header"><h3 class="card-title">📊 Google</h3></div>
                    <div class="card-body">
                        <div class="form-group-admin">
                            <label class="form-label-admin">Google Analytics 4 — Measurement ID</label>
                            <input type="text" name="ga4_id" class="form-control-admin"
                                   value="<?= e($pixelConfig['ga4_id']) ?>"
                                   placeholder="G-XXXXXXXXXX">
                        </div>
                        <div class="form-group-admin">
                            <label class="form-label-admin">Google Tag Manager — GTM ID</label>
                            <input type="text" name="gtm_id" class="form-control-admin"
                                   value="<?= e($pixelConfig['gtm_id']) ?>"
                                   placeholder="GTM-XXXXXXX">
                            <small style="color:var(--admin-muted)">Si usas GTM, NO uses GA4 por separado — configúralo dentro de GTM.</small>
                        </div>
                        <div class="form-group-admin">
                            <label class="form-label-admin">Google Search Console — Meta Tag de verificación</label>
                            <input type="text" name="gsc_verification" class="form-control-admin"
                                   value="<?= e($pixelConfig['gsc_verification']) ?>"
                                   placeholder="xxxxxxxxxxxxxxxxx">
                            <small style="color:var(--admin-muted)">Solo el valor del content="...", no el tag completo.</small>
                        </div>
                        <div class="form-group-admin">
                            <label class="form-label-admin">Google Ads — Snippet de conversión</label>
                            <textarea name="google_ads_snippet" class="form-control-admin" rows="4"
                                      placeholder="<!-- Pegar snippet completo de Google Ads -->"><?= e($pixelConfig['google_ads_snippet']) ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="admin-card" style="margin-bottom:20px;">
                    <div class="card-header"><h3 class="card-title">📱 Meta & TikTok</h3></div>
                    <div class="card-body">
                        <div class="form-group-admin">
                            <label class="form-label-admin">Meta (Facebook) Pixel ID</label>
                            <input type="text" name="fb_pixel_id" class="form-control-admin"
                                   value="<?= e($pixelConfig['fb_pixel_id']) ?>"
                                   placeholder="000000000000000">
                        </div>
                        <div class="form-group-admin">
                            <label class="form-label-admin">TikTok Pixel ID</label>
                            <input type="text" name="tiktok_pixel_id" class="form-control-admin"
                                   value="<?= e($pixelConfig['tiktok_pixel_id']) ?>"
                                   placeholder="XXXXXXXXXXXXXXXXXX">
                        </div>
                    </div>
                </div>

                <div class="admin-card">
                    <div class="card-header"><h3 class="card-title">💻 Código Personalizado</h3></div>
                    <div class="card-body">
                        <div class="form-group-admin">
                            <label class="form-label-admin">Código personalizado en &lt;head&gt;</label>
                            <textarea name="head_custom_code" class="form-control-admin code-textarea" rows="5"
                                      placeholder="<!-- Scripts, meta tags personalizados -->"><?= e($pixelConfig['head_custom_code']) ?></textarea>
                        </div>
                        <div class="form-group-admin">
                            <label class="form-label-admin">Código personalizado antes de &lt;/body&gt;</label>
                            <textarea name="body_custom_code" class="form-control-admin code-textarea" rows="5"
                                      placeholder="<!-- Scripts que van al final -->"><?= e($pixelConfig['body_custom_code']) ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" name="save_pixels" class="btn-admin-primary" style="margin-top:8px;">
            💾 Guardar Pixels y Tracking
        </button>
    </form>
</div>

<!-- Robots.txt y Sitemap -->
<div id="robots" class="seo-section <?= $activePage === 'robots' ? 'active-section':'' ?>">
    <div class="admin-grid-2">
        <div class="admin-card">
            <div class="card-header">
                <h3 class="card-title">🤖 Robots.txt</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <?= csrfField() ?>
                    <div class="form-group-admin">
                        <textarea name="robots_content" class="form-control-admin code-textarea" rows="14"><?= e($robotsContent) ?></textarea>
                    </div>
                    <button type="submit" name="save_robots" class="btn-admin-primary">
                        💾 Guardar robots.txt
                    </button>
                </form>
            </div>
        </div>

        <div class="admin-card">
            <div class="card-header">
                <h3 class="card-title">🗺 Sitemap XML</h3>
            </div>
            <div class="card-body">
                <p style="color:var(--admin-muted); margin-bottom:16px;">
                    El sitemap se genera automáticamente en:
                </p>
                <code style="display:block;background:var(--admin-bg);padding:12px;border-radius:6px;margin-bottom:16px;font-size:.9rem;">
                    <?= e(SITE_URL) ?>/sitemap.xml
                </code>
                <p style="color:var(--admin-muted); font-size:.875rem; margin-bottom:16px;">
                    Incluye automáticamente todas las páginas del sitio con sus fechas de actualización.
                </p>
                <a href="<?= e(SITE_URL) ?>/sitemap.xml" target="_blank" class="btn-admin-outline">
                    👁 Ver Sitemap
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function switchSection(name, btn) {
    document.querySelectorAll('.seo-section').forEach(s => s.classList.remove('active-section'));
    document.querySelectorAll('.admin-tab').forEach(b => b.classList.remove('active'));
    document.getElementById(name).classList.add('active-section');
    btn.classList.add('active');
}

function updateCounter(fieldId, max) {
    const field = document.getElementById(fieldId);
    const counter = document.querySelector(`.char-counter[data-field="${fieldId}"]`);
    if (!field || !counter) return;
    const len = field.value.length;
    counter.textContent = `(${len}/${max})`;
    counter.style.color = len > max ? '#ef4444' : len > max * 0.9 ? '#f59e0b' : 'var(--admin-muted)';
}

// Inicializar contadores
document.querySelectorAll('[oninput^="updateCounter"]').forEach(el => {
    el.dispatchEvent(new Event('input'));
});
</script>

<style>
.seo-section { display: none; }
.seo-section.active-section { display: block; }
.code-textarea { font-family: monospace; font-size: .85rem; }
</style>

<?php adminFooter(); ?>
