<?php
/**
 * Funciones específicas del panel de administración
 */

/**
 * Verifica el CSRF y termina con error JSON si es inválido (para peticiones AJAX)
 */
function adminRequireCsrf(): void {
    if (!verifyCsrf()) {
        jsonError('Token de seguridad inválido. Recarga la página.', 403);
    }
}

/**
 * Obtiene los datos de estadísticas para el dashboard
 */
function getDashboardStats(): array {
    $today      = date('Y-m-d');
    $weekStart  = date('Y-m-d', strtotime('monday this week'));
    $monthStart = date('Y-m-01');

    $stats = [];

    // Reservas de hoy
    $stats['reservas_hoy'] = (int)(dbQueryOne(
        'SELECT COUNT(*) AS cnt FROM ' . DB_PREFIX . 'reservations WHERE DATE(created_at) = ?',
        [$today]
    )['cnt'] ?? 0);

    // Reservas esta semana
    $stats['reservas_semana'] = (int)(dbQueryOne(
        'SELECT COUNT(*) AS cnt FROM ' . DB_PREFIX . 'reservations
         WHERE created_at >= ?',
        [$weekStart]
    )['cnt'] ?? 0);

    // Reservas este mes
    $stats['reservas_mes'] = (int)(dbQueryOne(
        'SELECT COUNT(*) AS cnt FROM ' . DB_PREFIX . 'reservations
         WHERE created_at >= ?',
        [$monthStart]
    )['cnt'] ?? 0);

    // Reservas pendientes
    $stats['reservas_pendientes'] = (int)(dbQueryOne(
        'SELECT COUNT(*) AS cnt FROM ' . DB_PREFIX . 'reservations WHERE status = ?',
        ['pendiente']
    )['cnt'] ?? 0);

    // Total platos activos
    $stats['platos_activos'] = (int)(dbQueryOne(
        'SELECT COUNT(*) AS cnt FROM ' . DB_PREFIX . 'menu_items WHERE active = 1'
    )['cnt'] ?? 0);

    // Total fotos en galería
    $stats['fotos_galeria'] = (int)(dbQueryOne(
        'SELECT COUNT(*) AS cnt FROM ' . DB_PREFIX . 'gallery WHERE active = 1'
    )['cnt'] ?? 0);

    return $stats;
}

/**
 * Sube una imagen desde $_FILES con validación completa
 * Wrapper con manejo de errores y mensajes
 */
function adminUploadImage(string $fileKey, string $subfolder = 'general'): array {
    if (empty($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => false, 'error' => 'No se seleccionó ningún archivo'];
    }

    if ($_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
        $errMsgs = [
            UPLOAD_ERR_INI_SIZE   => 'El archivo excede el tamaño máximo del servidor',
            UPLOAD_ERR_FORM_SIZE  => 'El archivo excede el tamaño máximo del formulario',
            UPLOAD_ERR_PARTIAL    => 'El archivo se subió parcialmente',
            UPLOAD_ERR_NO_TMP_DIR => 'Falta carpeta temporal',
            UPLOAD_ERR_CANT_WRITE => 'No se puede escribir en el disco',
            UPLOAD_ERR_EXTENSION  => 'Subida bloqueada por extensión PHP',
        ];
        return ['success' => false, 'error' => $errMsgs[$_FILES[$fileKey]['error']] ?? 'Error al subir archivo'];
    }

    $filename = uploadImage($_FILES[$fileKey], $subfolder);

    if ($filename === false) {
        return ['success' => false, 'error' => 'Tipo de archivo no permitido o tamaño excede 5MB. Solo imágenes JPG, PNG, GIF, WebP.'];
    }

    return ['success' => true, 'filename' => $filename];
}

/**
 * Genera el HTML del header del admin
 */
function adminHeader(string $pageTitle, string $activePage = ''): void {
    $siteName = getSetting('site_name', 'La Chingada');
    $admin    = getCurrentAdmin();
    $forceChange = $_SESSION['force_pw_change'] ?? false;
    $installWarning = file_exists(SITE_PATH . '/install.php');
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — Admin <?= e($siteName) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(SITE_URL) ?>/assets/css/admin.css">
</head>
<body>

<div class="admin-wrapper">

    <!-- Sidebar -->
    <aside class="admin-sidebar" id="admin-sidebar">
        <div class="sidebar-header">
            <a href="<?= e(SITE_URL) ?>/admin/" class="sidebar-logo">
                <span class="sidebar-logo-emoji">🌮</span>
                <div>
                    <span class="sidebar-logo-name"><?= e($siteName) ?></span>
                    <span class="sidebar-logo-sub">Panel Admin</span>
                </div>
            </a>
        </div>

        <nav class="sidebar-nav" aria-label="Navegación admin">
            <ul>
                <li class="nav-section-title">Principal</li>
                <li>
                    <a href="<?= e(SITE_URL) ?>/admin/"
                       class="<?= $activePage === 'dashboard' ? 'active' : '' ?>">
                        <span class="nav-icon">📊</span>
                        Dashboard
                    </a>
                </li>
                <li class="nav-section-title">Contenido</li>
                <li>
                    <a href="<?= e(SITE_URL) ?>/admin/paginas.php"
                       class="<?= $activePage === 'paginas' ? 'active' : '' ?>">
                        <span class="nav-icon">📝</span>
                        Páginas
                    </a>
                </li>
                <li>
                    <a href="<?= e(SITE_URL) ?>/admin/menu.php"
                       class="<?= $activePage === 'menu' ? 'active' : '' ?>">
                        <span class="nav-icon">🍽</span>
                        Menú
                    </a>
                </li>
                <li>
                    <a href="<?= e(SITE_URL) ?>/admin/galeria.php"
                       class="<?= $activePage === 'galeria' ? 'active' : '' ?>">
                        <span class="nav-icon">🖼</span>
                        Galería
                    </a>
                </li>
                <li class="nav-section-title">Operaciones</li>
                <li>
                    <a href="<?= e(SITE_URL) ?>/admin/reservas.php"
                       class="<?= $activePage === 'reservas' ? 'active' : '' ?>">
                        <span class="nav-icon">📅</span>
                        Reservas
                        <?php
                        $pending = (int)(dbQueryOne('SELECT COUNT(*) AS cnt FROM ' . DB_PREFIX . 'reservations WHERE status = ?', ['pendiente'])['cnt'] ?? 0);
                        if ($pending > 0): ?>
                        <span class="nav-badge"><?= $pending ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-section-title">Configuración</li>
                <li>
                    <a href="<?= e(SITE_URL) ?>/admin/configuracion.php"
                       class="<?= $activePage === 'configuracion' ? 'active' : '' ?>">
                        <span class="nav-icon">⚙️</span>
                        General
                    </a>
                </li>
                <li>
                    <a href="<?= e(SITE_URL) ?>/admin/seo.php"
                       class="<?= $activePage === 'seo' ? 'active' : '' ?>">
                        <span class="nav-icon">🔍</span>
                        SEO & Pixels
                    </a>
                </li>
            </ul>
        </nav>

        <div class="sidebar-footer">
            <a href="<?= e(SITE_URL) ?>/" target="_blank" class="sidebar-view-site">
                👁 Ver Sitio
            </a>
            <a href="<?= e(SITE_URL) ?>/admin/logout.php" class="sidebar-logout">
                🚪 Cerrar Sesión
            </a>
        </div>
    </aside>

    <!-- Área de contenido principal -->
    <div class="admin-main">

        <!-- Topbar -->
        <header class="admin-topbar">
            <button class="sidebar-toggle" id="sidebar-toggle" aria-label="Abrir/cerrar menú">
                <span></span><span></span><span></span>
            </button>
            <h1 class="topbar-title"><?= e($pageTitle) ?></h1>
            <div class="topbar-actions">
                <span class="topbar-user">
                    👤 <?= e($admin['username'] ?? 'Admin') ?>
                </span>
            </div>
        </header>

        <!-- Alertas globales -->
        <div class="admin-alerts">
            <?php if ($installWarning): ?>
            <div class="alert-admin alert-admin-danger">
                ⚠️ <strong>Seguridad:</strong> El archivo <code>install.php</code> aún existe.
                <a href="<?= e(SITE_URL) ?>/admin/configuracion.php?delete_install=1" onclick="return confirm('¿Eliminar install.php?')">
                    Eliminarlo ahora
                </a>
            </div>
            <?php endif; ?>

            <?php if ($forceChange): ?>
            <div class="alert-admin alert-admin-warning">
                🔐 Por seguridad, debes <a href="<?= e(SITE_URL) ?>/admin/configuracion.php#cambiar-contrasena">cambiar tu contraseña</a> antes de continuar.
            </div>
            <?php endif; ?>
        </div>

        <div class="admin-content">
    <?php
}

/**
 * Genera el HTML del footer del admin
 */
function adminFooter(): void {
    ?>
        </div><!-- /admin-content -->
    </div><!-- /admin-main -->
</div><!-- /admin-wrapper -->

<script src="<?= e(SITE_URL) ?>/assets/js/admin.js"></script>
</body>
</html>
    <?php
}

/**
 * Formatea un estado de reserva en HTML con badge de color
 */
function reservationStatusBadge(string $status): string {
    $badges = [
        'pendiente'  => ['label' => 'Pendiente',  'class' => 'status-pending'],
        'confirmada' => ['label' => 'Confirmada', 'class' => 'status-confirmed'],
        'cancelada'  => ['label' => 'Cancelada',  'class' => 'status-cancelled'],
        'completada' => ['label' => 'Completada', 'class' => 'status-completed'],
    ];
    $b = $badges[$status] ?? ['label' => $status, 'class' => 'status-pending'];
    return "<span class=\"status-badge {$b['class']}\">{$b['label']}</span>";
}
