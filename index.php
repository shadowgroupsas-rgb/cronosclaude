<?php
/**
 * La Chingada Restaurant — Página de Inicio (Landing Page)
 */

// ── Bootstrap ──────────────────────────────────────────────────────────────
if (!file_exists(__DIR__ . '/config.php')) {
    header('Location: install.php');
    exit;
}

require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . '/db.php';
require_once INCLUDES_PATH . '/functions.php';
require_once INCLUDES_PATH . '/seo.php';

date_default_timezone_set(SITE_TIMEZONE);
initSession();

// Modo mantenimiento
if (getSetting('maintenance_mode') === '1') {
    require_once __DIR__ . '/maintenance.php';
    exit;
}

$currentPage = 'home';
$seoPage     = 'home';

// ── Cargar contenido de secciones ──────────────────────────────────────────
function getSection(string $page, string $section): array {
    $row = dbQueryOne(
        'SELECT content FROM ' . DB_PREFIX . 'page_sections WHERE page = ? AND section = ?',
        [$page, $section]
    );
    return $row ? (json_decode($row['content'], true) ?: []) : [];
}

$hero         = getSection('home', 'hero');
$historia     = getSection('home', 'historia');
$testimonios  = dbQuery('SELECT * FROM ' . DB_PREFIX . 'testimonios WHERE active = 1 ORDER BY sort_order ASC LIMIT 6');
$galeria      = dbQuery('SELECT * FROM ' . DB_PREFIX . 'gallery WHERE active = 1 ORDER BY sort_order ASC LIMIT 7');
$horarios     = getSection('home', 'horarios');

// Platos destacados en landing
$destacados = dbQuery(
    'SELECT mi.*, mc.name AS category_name, mc.slug AS category_slug
     FROM ' . DB_PREFIX . 'menu_items mi
     JOIN ' . DB_PREFIX . 'menu_categories mc ON mc.id = mi.category_id
     WHERE mi.featured = 1 AND mi.active = 1
     ORDER BY mi.sort_order ASC LIMIT 6'
);

$settings = getSettings([
    'site_name', 'restaurant_address', 'restaurant_phone',
    'maps_embed_url', 'restaurant_hours_text',
]);

require_once INCLUDES_PATH . '/header.php';
?>

<!-- ══════════════════════════════════════════════════
     HERO SECTION
════════════════════════════════════════════════════ -->
<section class="hero" id="inicio">
    <div class="hero-bg">
        <?php if (!empty($hero['video_url'])): ?>
            <video autoplay muted loop playsinline>
                <source src="<?= e($hero['video_url']) ?>" type="video/mp4">
            </video>
        <?php elseif (!empty($hero['bg_image'])): ?>
            <img src="<?= e(imgUrl($hero['bg_image'])) ?>"
                 alt="<?= e($hero['bg_image_alt'] ?? 'La Chingada Restaurant') ?>"
                 loading="eager">
        <?php else: ?>
            <img src="<?= e(SITE_URL) ?>/assets/images/hero-default.jpg"
                 alt="Restaurante Mexicano La Chingada"
                 loading="eager">
        <?php endif; ?>
    </div>
    <div class="hero-overlay"></div>

    <div class="hero-content">
        <p class="hero-tag">
            <?= e($hero['tag'] ?? '🌮 Restaurante Mexicano · Cartagena') ?>
        </p>
        <h1>
            <?= e($hero['headline'] ?? 'Sabores Auténticos') ?><br>
            <em><?= e($hero['headline_highlight'] ?? 'de México') ?></em>
        </h1>
        <p class="hero-subtitle">
            <?= e($hero['subtitle'] ?? 'Bienvenido a La Chingada, donde la tradición mexicana se encuentra con la calidez caribeña. Tacos, burritos y mucho más en el corazón de Cartagena.') ?>
        </p>
        <div class="hero-actions">
            <a href="<?= e(SITE_URL) ?>/reservas" class="btn btn-primary btn-lg">
                🍽 Reservar Mesa
            </a>
            <a href="<?= e(SITE_URL) ?>/menu" class="btn btn-outline btn-lg">
                Ver Menú
            </a>
        </div>
    </div>

    <div class="hero-scroll" aria-hidden="true">
        <span>Descubrir</span>
        <div class="hero-scroll-arrow"></div>
    </div>
</section>

<!-- ══════════════════════════════════════════════════
     STATS BAR
════════════════════════════════════════════════════ -->
<div class="stats-bar">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-item fade-in">
                <strong><?= e($historia['years'] ?? '5') ?>+</strong>
                <span>Años de Sabor</span>
            </div>
            <div class="stat-item fade-in delay-1">
                <strong>50+</strong>
                <span>Platos Auténticos</span>
            </div>
            <div class="stat-item fade-in delay-2">
                <strong>10K+</strong>
                <span>Clientes Felices</span>
            </div>
            <div class="stat-item fade-in delay-3">
                <strong>4.9⭐</strong>
                <span>Calificación</span>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════
     NUESTRA HISTORIA
════════════════════════════════════════════════════ -->
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
                         alt="Nuestra Historia"
                         loading="lazy">
                <?php endif; ?>
                <div class="historia-badge">
                    <strong><?= e($historia['years'] ?? '5') ?></strong>
                    <span>años</span>
                </div>
            </div>

            <div class="historia-content fade-in-right">
                <p class="section-tag">Nuestra Historia</p>
                <h2 class="section-title">
                    <?= e($historia['title'] ?? 'De México al Caribe con Amor') ?>
                </h2>
                <p>
                    <?= e($historia['text_1'] ?? 'La Chingada nació del sueño de traer los auténticos sabores de México al corazón de Cartagena. Fundada por familia mexicana con raíces en Oaxaca, nuestra cocina es un homenaje a las recetas que han pasado de generación en generación.') ?>
                </p>
                <p>
                    <?= e($historia['text_2'] ?? 'Cada plato que servimos lleva el alma de México: ingredientes frescos, salsas elaboradas con chiles importados y la calidez de quien cocina con pasión. Aquí no vendemos comida rápida; vendemos experiencias que perduran en el paladar.') ?>
                </p>

                <div class="historia-features">
                    <div class="historia-feature">
                        <span class="feature-icon">🌽</span>
                        <div class="feature-text">
                            <strong>Ingredientes Frescos</strong>
                            <span>Seleccionados cada día</span>
                        </div>
                    </div>
                    <div class="historia-feature">
                        <span class="feature-icon">🌶</span>
                        <div class="feature-text">
                            <strong>Chiles Importados</strong>
                            <span>Directamente de México</span>
                        </div>
                    </div>
                    <div class="historia-feature">
                        <span class="feature-icon">👨‍🍳</span>
                        <div class="feature-text">
                            <strong>Chef Mexicano</strong>
                            <span>20 años de experiencia</span>
                        </div>
                    </div>
                    <div class="historia-feature">
                        <span class="feature-icon">🤝</span>
                        <div class="feature-text">
                            <strong>Familia Colombiana</strong>
                            <span>Amamos Cartagena</span>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <a href="<?= e(SITE_URL) ?>/contacto#historia" class="btn btn-ghost">
                        Conocer más →
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════════
     DESTACADOS DEL MENÚ
════════════════════════════════════════════════════ -->
<?php if (!empty($destacados)): ?>
<section class="section menu-section" id="menu-destacados">
    <div class="container">
        <div class="section-header">
            <p class="section-tag">Lo Mejor de La Chingada</p>
            <h2 class="section-title">Nuestros Favoritos</h2>
            <p class="section-subtitle">
                Una selección de los platos más amados por nuestros clientes.
                Auténtica cocina mexicana en cada bocado.
            </p>
        </div>

        <div class="menu-grid" id="menu-items-container">
            <?php foreach ($destacados as $i => $plato):
                $badges = array_filter(explode(',', $plato['badge'] ?? ''));
                $priceFormatted = formatPrice((float)$plato['price']);
                $priceOldFormatted = !empty($plato['price_old']) ? formatPrice((float)$plato['price_old']) : '';
            ?>
            <article class="menu-card fade-in delay-<?= min($i + 1, 5) ?>"
                     data-id="<?= e($plato['id']) ?>"
                     data-name="<?= e($plato['name']) ?>"
                     data-desc="<?= e($plato['description_short'] ?? '') ?>"
                     data-desc-long="<?= e($plato['description_long'] ?? $plato['description_short'] ?? '') ?>"
                     data-price="<?= e($priceFormatted) ?>"
                     data-price-old="<?= e($priceOldFormatted) ?>"
                     data-image="<?= e(!empty($plato['image']) ? imgUrl($plato['image']) : SITE_URL . '/assets/images/placeholder.jpg') ?>"
                     data-category="<?= e($plato['category_slug'] ?? '') ?>"
                     data-category-name="<?= e($plato['category_name'] ?? '') ?>"
                     data-badges="<?= e(implode(',', $badges)) ?>"
                     role="button"
                     tabindex="0"
                     aria-label="Ver detalles de <?= e($plato['name']) ?>"
                     onkeydown="if(event.key==='Enter')this.click()">

                <div class="menu-card-image">
                    <?php if (!empty($plato['image'])): ?>
                        <img src="<?= e(imgUrl($plato['image'])) ?>"
                             alt="<?= e($plato['alt_text'] ?: $plato['name']) ?>"
                             loading="lazy">
                    <?php else: ?>
                        <img src="<?= e(SITE_URL) ?>/assets/images/placeholder.jpg"
                             alt="<?= e($plato['name']) ?>"
                             loading="lazy">
                    <?php endif; ?>

                    <div class="menu-card-badges">
                        <?php foreach ($badges as $badge): ?>
                            <?php
                            $badgeLabels = [
                                'nuevo' => 'Nuevo',
                                'popular' => 'Popular',
                                'sin_gluten' => 'Sin Gluten',
                                'vegetariano' => 'Vegetariano',
                                'picante' => '🌶 Picante',
                            ];
                            ?>
                            <span class="badge badge-<?= e($badge) ?>">
                                <?= e($badgeLabels[$badge] ?? $badge) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>

                    <div class="menu-card-overlay">
                        <span class="card-overlay-btn">Ver Detalle</span>
                    </div>
                </div>

                <div class="menu-card-body">
                    <p class="menu-card-category"><?= e($plato['category_name'] ?? '') ?></p>
                    <h3 class="menu-card-name"><?= e($plato['name']) ?></h3>
                    <p class="menu-card-desc">
                        <?= e($plato['description_short'] ?? '') ?>
                    </p>
                    <div class="menu-card-footer">
                        <div>
                            <span class="menu-card-price"><?= e($priceFormatted) ?></span>
                            <?php if ($priceOldFormatted): ?>
                                <span class="menu-card-price-old"><?= e($priceOldFormatted) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-3">
            <a href="<?= e(SITE_URL) ?>/menu" class="btn btn-primary">
                Ver Menú Completo →
            </a>
        </div>
    </div>
</section>

<!-- Modal de detalle de plato -->
<div class="modal-overlay" id="menu-modal" role="dialog" aria-modal="true" aria-labelledby="modal-name">
    <div class="modal-content">
        <div class="modal-image">
            <img src="" alt="" id="modal-img" loading="lazy">
            <button class="modal-close" id="modal-close" aria-label="Cerrar">✕</button>
        </div>
        <div class="modal-body">
            <div class="modal-badges" id="modal-badges"></div>
            <p class="modal-category" id="modal-category"></p>
            <h3 class="modal-name" id="modal-name"></h3>
            <p class="modal-desc" id="modal-desc"></p>
            <div class="modal-price-row">
                <div>
                    <span class="modal-price" id="modal-price"></span>
                    <span class="modal-price-old" id="modal-price-old"></span>
                </div>
                <a href="<?= e(SITE_URL) ?>/reservas" class="btn btn-primary">
                    Reservar Mesa
                </a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════
     GALERÍA
════════════════════════════════════════════════════ -->
<?php if (!empty($galeria)): ?>
<section class="section galeria-section" id="galeria">
    <div class="container">
        <div class="section-header">
            <p class="section-tag">Galería</p>
            <h2 class="section-title">Momentos en La Chingada</h2>
            <p class="section-subtitle">
                Cada imagen cuenta una historia de sabor, tradición y alegría mexicana.
            </p>
        </div>

        <div class="galeria-grid fade-in">
            <?php foreach ($galeria as $i => $foto): ?>
            <div class="galeria-item">
                <img src="<?= e(imgUrl($foto['filename'])) ?>"
                     alt="<?= e($foto['alt_text'] ?: 'Galería La Chingada') ?>"
                     loading="lazy">
                <div class="galeria-item-overlay">
                    <span>🔍</span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════
     TESTIMONIOS
════════════════════════════════════════════════════ -->
<?php if (!empty($testimonios)): ?>
<section class="section testimonios-section" id="testimonios">
    <div class="container">
        <div class="section-header">
            <p class="section-tag" style="color: var(--color-secondary);">Reseñas</p>
            <h2 class="section-title" style="color:white;">Lo que Dicen Nuestros Clientes</h2>
            <p class="section-subtitle" style="color:rgba(255,255,255,.65);">
                La satisfacción de nuestros clientes es nuestra mayor recompensa.
            </p>
        </div>

        <div class="testimonios-grid">
            <?php foreach ($testimonios as $i => $t): ?>
            <div class="testimonio-card fade-in delay-<?= min($i + 1, 5) ?>">
                <div class="testimonio-stars">
                    <?= str_repeat('★', (int)($t['rating'] ?? 5)) ?>
                </div>
                <p class="testimonio-text">"<?= e($t['text'] ?? '') ?>"</p>
                <div class="testimonio-author">
                    <?php if (!empty($t['avatar'])): ?>
                        <img src="<?= e(imgUrl($t['avatar'])) ?>"
                             alt="<?= e($t['name']) ?>"
                             class="testimonio-avatar"
                             loading="lazy">
                    <?php else: ?>
                        <div class="testimonio-avatar-placeholder">
                            <?= e(mb_strtoupper(mb_substr($t['name'] ?? 'C', 0, 1))) ?>
                        </div>
                    <?php endif; ?>
                    <div class="testimonio-info">
                        <strong><?= e($t['name'] ?? '') ?></strong>
                        <span><?= e($t['origin'] ?? 'Cliente') ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════
     HORARIOS Y UBICACIÓN
════════════════════════════════════════════════════ -->
<section class="section ubicacion-section" id="ubicacion">
    <div class="container">
        <div class="section-header">
            <p class="section-tag">Visítanos</p>
            <h2 class="section-title">Horarios y Ubicación</h2>
        </div>

        <div class="ubicacion-grid">
            <div class="fade-in-left">
                <h3>¿Cómo Llegar?</h3>

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
                </div>

                <?php if (!empty($settings['restaurant_hours_text'])): ?>
                <div class="horario-list mt-3">
                    <h4 style="margin-bottom:16px; color: var(--color-primary);">Horarios de Atención</h4>
                    <?php
                    $horarioLines = explode("\n", trim($settings['restaurant_hours_text']));
                    foreach ($horarioLines as $line):
                        $line = trim($line);
                        if (empty($line)) continue;
                        $parts = explode(':', $line, 2);
                        $dia = trim($parts[0] ?? $line);
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

                <div class="mt-3">
                    <a href="<?= e(SITE_URL) ?>/reservas" class="btn btn-primary">
                        🍽 Reservar Mesa
                    </a>
                </div>
            </div>

            <div class="ubicacion-map fade-in-right">
                <?php if (!empty($settings['maps_embed_url'])): ?>
                    <iframe src="<?= e($settings['maps_embed_url']) ?>"
                            allowfullscreen=""
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            title="Ubicación de <?= e($settings['site_name'] ?? 'La Chingada') ?>">
                    </iframe>
                <?php else: ?>
                    <div style="height:400px;background:var(--color-cream);display:flex;align-items:center;justify-content:center;border-radius:var(--radius-lg);color:var(--color-text-light);">
                        <p style="text-align:center;">
                            📍 Configura el mapa en el<br>panel de administración
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════════
     CTA FINAL
════════════════════════════════════════════════════ -->
<section class="section cta-section"
         style="background: linear-gradient(135deg, var(--color-primary), var(--color-secondary)); text-align:center; padding:80px 0;">
    <div class="container fade-in">
        <p style="color:rgba(255,255,255,.8); letter-spacing:3px; text-transform:uppercase; font-size:.8rem; margin-bottom:12px;">
            ¿Listo para una experiencia única?
        </p>
        <h2 style="color:#fff; margin-bottom:16px; font-size:clamp(2rem,4vw,3rem);">
            Haz tu Reserva Hoy
        </h2>
        <p style="color:rgba(255,255,255,.85); font-size:1.1rem; max-width:500px; margin:0 auto 32px;">
            Cupos limitados. Reserva con anticipación y garantiza tu lugar en la mejor mesa.
        </p>
        <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;">
            <a href="<?= e(SITE_URL) ?>/reservas" class="btn btn-outline btn-lg">
                🍽 Reservar Mesa
            </a>
            <a href="<?= e(SITE_URL) ?>/menu" class="btn btn-lg"
               style="background:rgba(255,255,255,.15);color:#fff;border:2px solid rgba(255,255,255,.3);">
                Ver Menú
            </a>
        </div>
    </div>
</section>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>

<!-- CSS extra para stats bar y CTA -->
<style>
.stats-bar {
    background: var(--color-dark);
    padding: 30px 0;
}
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    text-align: center;
}
.stat-item strong {
    display: block;
    font-family: var(--font-title);
    font-size: clamp(1.8rem, 3vw, 2.8rem);
    font-weight: 700;
    color: var(--color-secondary);
    line-height: 1;
}
.stat-item span {
    font-size: 0.8rem;
    color: rgba(255,255,255,.55);
    text-transform: uppercase;
    letter-spacing: 1.5px;
    margin-top: 6px;
    display: block;
}
@media(max-width:640px) {
    .stats-grid { grid-template-columns: repeat(2,1fr); }
}
</style>
