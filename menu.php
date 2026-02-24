<?php
/**
 * La Chingada Restaurant — Página de Menú Interactivo
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

$currentPage = 'menu';
$seoPage     = 'menu';

// ── Cargar categorías y platos ─────────────────────────────────────────────
$categorias = dbQuery(
    'SELECT * FROM ' . DB_PREFIX . 'menu_categories
     WHERE active = 1 ORDER BY sort_order ASC, name ASC'
);

$platos = dbQuery(
    'SELECT mi.*, mc.name AS category_name, mc.slug AS category_slug
     FROM ' . DB_PREFIX . 'menu_items mi
     JOIN ' . DB_PREFIX . 'menu_categories mc ON mc.id = mi.category_id
     WHERE mi.active = 1 AND mc.active = 1
     ORDER BY mc.sort_order ASC, mi.sort_order ASC, mi.name ASC'
);

// Agrupar platos por categoría
$platosPorCategoria = [];
foreach ($platos as $plato) {
    $platosPorCategoria[$plato['category_slug']][] = $plato;
}

$badgeLabels = [
    'nuevo'        => ['label' => 'Nuevo',       'class' => 'badge-nuevo'],
    'popular'      => ['label' => 'Popular',      'class' => 'badge-popular'],
    'sin_gluten'   => ['label' => 'Sin Gluten',   'class' => 'badge-sin_gluten'],
    'vegetariano'  => ['label' => 'Vegetariano',  'class' => 'badge-vegetariano'],
    'picante'      => ['label' => '🌶 Picante',   'class' => 'badge-picante'],
];

require_once INCLUDES_PATH . '/header.php';
?>

<main class="menu-page">

    <!-- Hero del menú -->
    <div class="menu-hero">
        <div class="container">
            <p class="section-tag" style="color:var(--color-secondary);">🌮 Carta</p>
            <h1>Nuestro Menú</h1>
            <p style="color:rgba(255,255,255,.75); max-width:550px; margin:12px auto 0; font-size:1.05rem;">
                Sabores auténticos de México, preparados con amor y los mejores ingredientes.
            </p>

            <!-- Buscador -->
            <div class="menu-search-bar">
                <input type="search"
                       id="menu-search"
                       placeholder="Buscar tacos, burritos, enchiladas..."
                       autocomplete="off"
                       aria-label="Buscar platos">
                <span class="menu-search-icon" aria-hidden="true">🔍</span>
            </div>
        </div>
    </div>

    <!-- Filtros de categoría -->
    <nav class="menu-filters" aria-label="Filtrar por categoría">
        <div class="container">
            <div class="menu-filters-scroll" role="tablist">
                <button class="filter-btn active"
                        data-filter="all"
                        role="tab"
                        aria-selected="true"
                        aria-controls="menu-items-container">
                    🍽 Todos
                    <span class="filter-count"><?= count($platos) ?></span>
                </button>

                <?php foreach ($categorias as $cat):
                    $count = count($platosPorCategoria[$cat['slug']] ?? []);
                    if ($count === 0) continue;
                ?>
                <button class="filter-btn"
                        data-filter="<?= e($cat['slug']) ?>"
                        role="tab"
                        aria-selected="false"
                        aria-controls="menu-items-container">
                    <?= !empty($cat['icon']) ? e($cat['icon']) . ' ' : '' ?>
                    <?= e($cat['name']) ?>
                    <span class="filter-count"><?= $count ?></span>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
    </nav>

    <!-- Items del menú -->
    <section class="menu-items-section" aria-live="polite">
        <div class="container">

            <div id="menu-items-container">
                <?php foreach ($categorias as $cat):
                    $platosDeCategoria = $platosPorCategoria[$cat['slug']] ?? [];
                    if (empty($platosDeCategoria)) continue;
                ?>

                <div class="menu-category-group fade-in" data-category="<?= e($cat['slug']) ?>">
                    <h2 class="menu-category-title" id="cat-<?= e($cat['slug']) ?>">
                        <?= !empty($cat['icon']) ? '<span aria-hidden="true">' . e($cat['icon']) . '</span>' : '' ?>
                        <?= e($cat['name']) ?>
                    </h2>

                    <div class="menu-grid" style="margin-bottom: 60px;">
                        <?php foreach ($platosDeCategoria as $i => $plato):
                            $badges = array_filter(explode(',', $plato['badge'] ?? ''));
                            $priceFormatted    = formatPrice((float)$plato['price']);
                            $priceOldFormatted = !empty($plato['price_old']) ? formatPrice((float)$plato['price_old']) : '';
                        ?>
                        <article class="menu-card"
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
                                    <?php foreach ($badges as $badge):
                                        $b = $badgeLabels[$badge] ?? ['label' => $badge, 'class' => ''];
                                    ?>
                                    <span class="badge <?= e($b['class']) ?>"><?= e($b['label']) ?></span>
                                    <?php endforeach; ?>
                                </div>

                                <div class="menu-card-overlay">
                                    <span class="card-overlay-btn">Ver Detalle</span>
                                </div>
                            </div>

                            <div class="menu-card-body">
                                <p class="menu-card-category"><?= e($plato['category_name'] ?? '') ?></p>
                                <h3 class="menu-card-name"><?= e($plato['name']) ?></h3>
                                <p class="menu-card-desc"><?= e($plato['description_short'] ?? '') ?></p>
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
                </div>

                <?php endforeach; ?>
            </div>

            <!-- Mensaje sin resultados -->
            <div id="no-results" class="no-results" style="display:none;" aria-live="polite">
                <p style="font-size:3rem; margin-bottom:16px;">🔍</p>
                <h3>No encontramos resultados</h3>
                <p>Intenta con otro término de búsqueda o explora nuestras categorías.</p>
            </div>

        </div>
    </section>

</main>

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
            <h2 class="modal-name" id="modal-name"></h2>
            <p class="modal-desc" id="modal-desc"></p>
            <div class="modal-price-row">
                <div>
                    <span class="modal-price" id="modal-price"></span>
                    <span class="modal-price-old" id="modal-price-old"></span>
                </div>
                <a href="<?= e(SITE_URL) ?>/reservas" class="btn btn-primary">
                    🍽 Reservar Mesa
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
