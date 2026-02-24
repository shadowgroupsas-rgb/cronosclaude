<?php
/**
 * Encabezado global del sitio
 * Requiere que $currentPage esté definido antes de incluir este archivo
 * Requiere que $seoPage esté definido para los meta tags
 */

// Cargar configuraciones globales
$siteConfig = getSettings([
    'site_name', 'site_logo', 'site_slogan',
    'whatsapp_number', 'whatsapp_message',
    'nav_link_1_text', 'nav_link_1_url',
    'maintenance_mode', 'favicon',
    'color_primary', 'color_secondary',
]);

$siteName    = $siteConfig['site_name'] ?: 'La Chingada';
$siteSlogan  = $siteConfig['site_slogan'] ?: 'Sabores de México en Cartagena';
$colorPrimary   = $siteConfig['color_primary']   ?: '#C0392B';
$colorSecondary = $siteConfig['color_secondary'] ?: '#E67E22';
$currentPage = $currentPage ?? 'home';
$seoPage     = $seoPage ?? $currentPage;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <?php renderSeoHead($seoPage, $seoOverrides ?? []); ?>
    <?php renderRestaurantSchema(); ?>

    <!-- Favicon -->
    <?php if (!empty($siteConfig['favicon'])): ?>
    <link rel="icon" type="image/x-icon" href="<?= e(imgUrl($siteConfig['favicon'])) ?>">
    <?php else: ?>
    <link rel="icon" href="<?= e(SITE_URL) ?>/assets/images/favicon.ico" type="image/x-icon">
    <?php endif; ?>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,400&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- CSS Principal -->
    <link rel="stylesheet" href="<?= e(SITE_URL) ?>/assets/css/style.css">

    <!-- Variables CSS dinámicas (colores editables desde admin) -->
    <style>
        :root {
            --color-primary: <?= e($colorPrimary) ?>;
            --color-secondary: <?= e($colorSecondary) ?>;
        }
    </style>

    <!-- Alpine.js para interactividad -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body>

<!-- Google Tag Manager (noscript) -->
<?php if (!empty($siteConfig['gtm_id'])): ?>
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?= e($siteConfig['gtm_id']) ?>"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════
     NAVBAR PRINCIPAL
════════════════════════════════════════════════════ -->
<nav class="navbar" id="navbar" x-data="{ menuOpen: false }" :class="{ 'scrolled': scrolled }" x-init="
    let scrolled = false;
    window.addEventListener('scroll', () => { scrolled = window.scrollY > 80 });
">
    <div class="navbar-container">

        <!-- Logo -->
        <a href="<?= e(SITE_URL) ?>/" class="navbar-logo" aria-label="<?= e($siteName) ?> - Inicio">
            <?php if (!empty($siteConfig['site_logo'])): ?>
                <img src="<?= e(imgUrl($siteConfig['site_logo'])) ?>"
                     alt="<?= e($siteName) ?>" class="logo-img">
            <?php else: ?>
                <span class="logo-text">
                    <span class="logo-la">La</span>
                    <span class="logo-name">Chingada</span>
                </span>
            <?php endif; ?>
        </a>

        <!-- Menú de navegación desktop -->
        <ul class="navbar-menu" role="navigation" aria-label="Menú principal">
            <li>
                <a href="<?= e(SITE_URL) ?>/"
                   class="nav-link <?= $currentPage === 'home' ? 'active' : '' ?>"
                   aria-current="<?= $currentPage === 'home' ? 'page' : 'false' ?>">
                   Inicio
                </a>
            </li>
            <li>
                <a href="<?= e(SITE_URL) ?>/menu"
                   class="nav-link <?= $currentPage === 'menu' ? 'active' : '' ?>"
                   aria-current="<?= $currentPage === 'menu' ? 'page' : 'false' ?>">
                   Menú
                </a>
            </li>
            <li>
                <a href="<?= e(SITE_URL) ?>/contacto"
                   class="nav-link <?= $currentPage === 'contacto' ? 'active' : '' ?>"
                   aria-current="<?= $currentPage === 'contacto' ? 'page' : 'false' ?>">
                   Nosotros
                </a>
            </li>
            <li>
                <a href="<?= e(SITE_URL) ?>/contacto#ubicacion"
                   class="nav-link">
                   Ubicación
                </a>
            </li>
        </ul>

        <!-- Botón de reserva destacado -->
        <a href="<?= e(SITE_URL) ?>/reservas"
           class="btn-reserva <?= $currentPage === 'reservas' ? 'active' : '' ?>"
           aria-label="Hacer una reserva">
            <span class="btn-reserva-icon">🍽</span>
            Reservar Mesa
        </a>

        <!-- Hamburguesa mobile -->
        <button class="navbar-toggle"
                @click="menuOpen = !menuOpen"
                :aria-expanded="menuOpen"
                aria-label="Abrir menú de navegación"
                aria-controls="mobile-menu">
            <span class="hamburger" :class="{ 'is-open': menuOpen }">
                <span></span><span></span><span></span>
            </span>
        </button>
    </div>

    <!-- Menú mobile -->
    <div class="navbar-mobile"
         id="mobile-menu"
         :class="{ 'is-open': menuOpen }"
         @click.outside="menuOpen = false">
        <ul role="navigation" aria-label="Menú móvil">
            <li><a href="<?= e(SITE_URL) ?>/" @click="menuOpen = false"
                   class="<?= $currentPage === 'home' ? 'active' : '' ?>">Inicio</a></li>
            <li><a href="<?= e(SITE_URL) ?>/menu" @click="menuOpen = false"
                   class="<?= $currentPage === 'menu' ? 'active' : '' ?>">Menú</a></li>
            <li><a href="<?= e(SITE_URL) ?>/contacto" @click="menuOpen = false"
                   class="<?= $currentPage === 'contacto' ? 'active' : '' ?>">Nosotros</a></li>
            <li><a href="<?= e(SITE_URL) ?>/contacto#ubicacion" @click="menuOpen = false">Ubicación</a></li>
            <li>
                <a href="<?= e(SITE_URL) ?>/reservas" @click="menuOpen = false"
                   class="btn-reserva-mobile">
                   🍽 Reservar Mesa
                </a>
            </li>
        </ul>
    </div>
</nav>
<!-- FIN NAVBAR -->
