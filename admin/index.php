<?php
/**
 * Panel de Administración — Dashboard
 */

require_once dirname(__DIR__) . '/config.php';
require_once INCLUDES_PATH . '/db.php';
require_once INCLUDES_PATH . '/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

date_default_timezone_set(SITE_TIMEZONE);
requireAuth();

// Estadísticas
$stats = getDashboardStats();

// Últimas 10 reservas
$ultimasReservas = dbQuery(
    'SELECT * FROM ' . DB_PREFIX . 'reservations
     ORDER BY created_at DESC LIMIT 10'
);

// Modo mantenimiento
$maintenanceMode = getSetting('maintenance_mode') === '1';

// Toggle modo mantenimiento
if (isset($_GET['toggle_maintenance']) && verifyCsrf()) {
    $newMode = $maintenanceMode ? '0' : '1';
    setSetting('maintenance_mode', $newMode);

    if ($newMode === '1') {
        // Crear archivo flag
        touch(SITE_PATH . '/maintenance.flag');
    } else {
        // Eliminar archivo flag
        @unlink(SITE_PATH . '/maintenance.flag');
    }
    header('Location: ' . SITE_URL . '/admin/?maint=' . $newMode);
    exit;
}

adminHeader('Dashboard', 'dashboard');
?>

<!-- Flash messages -->
<?php if (isset($_GET['maint'])): ?>
<div class="alert-admin <?= $_GET['maint'] === '1' ? 'alert-admin-warning' : 'alert-admin-success' ?>">
    <?= $_GET['maint'] === '1' ? '🔧 Modo mantenimiento ACTIVADO' : '✅ Modo mantenimiento desactivado' ?>
</div>
<?php endif; ?>

<!-- Estadísticas rápidas -->
<div class="stats-cards">
    <div class="stat-card">
        <div class="stat-card-icon stat-icon-1">📅</div>
        <div class="stat-card-body">
            <div class="stat-number"><?= $stats['reservas_hoy'] ?></div>
            <div class="stat-label">Reservas Hoy</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon stat-icon-2">📊</div>
        <div class="stat-card-body">
            <div class="stat-number"><?= $stats['reservas_semana'] ?></div>
            <div class="stat-label">Esta Semana</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon stat-icon-3">🗓</div>
        <div class="stat-card-body">
            <div class="stat-number"><?= $stats['reservas_mes'] ?></div>
            <div class="stat-label">Este Mes</div>
        </div>
    </div>
    <div class="stat-card stat-card-alert">
        <div class="stat-card-icon stat-icon-4">⏳</div>
        <div class="stat-card-body">
            <div class="stat-number"><?= $stats['reservas_pendientes'] ?></div>
            <div class="stat-label">Pendientes</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon stat-icon-5">🍽</div>
        <div class="stat-card-body">
            <div class="stat-number"><?= $stats['platos_activos'] ?></div>
            <div class="stat-label">Platos Activos</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon stat-icon-6">🖼</div>
        <div class="stat-card-body">
            <div class="stat-number"><?= $stats['fotos_galeria'] ?></div>
            <div class="stat-label">Fotos Galería</div>
        </div>
    </div>
</div>

<!-- Acciones rápidas -->
<div class="dashboard-grid">

    <!-- Panel izquierdo: Últimas reservas -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3 class="card-title">📋 Últimas Reservas</h3>
            <a href="<?= e(SITE_URL) ?>/admin/reservas.php" class="card-link">Ver todas →</a>
        </div>
        <div class="card-body">
            <?php if (empty($ultimasReservas)): ?>
            <p class="empty-state">No hay reservas aún.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nombre</th>
                            <th>Fecha</th>
                            <th>Pers.</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ultimasReservas as $r): ?>
                        <tr>
                            <td><?= e($r['id']) ?></td>
                            <td>
                                <strong><?= e($r['name']) ?></strong><br>
                                <small style="color:var(--admin-muted)"><?= e($r['email']) ?></small>
                            </td>
                            <td>
                                <?= e(date('d/m/Y', strtotime($r['date']))) ?><br>
                                <small><?= e(date('g:iA', strtotime($r['time']))) ?></small>
                            </td>
                            <td><?= e($r['guests']) ?></td>
                            <td><?= reservationStatusBadge($r['status']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Panel derecho: Acciones rápidas + modo mantenimiento -->
    <div>
        <!-- Acciones rápidas -->
        <div class="dashboard-card" style="margin-bottom:24px;">
            <div class="card-header">
                <h3 class="card-title">⚡ Acciones Rápidas</h3>
            </div>
            <div class="card-body">
                <div class="quick-actions">
                    <a href="<?= e(SITE_URL) ?>/admin/menu.php?action=new_item" class="quick-action">
                        <span>➕</span> Añadir Plato
                    </a>
                    <a href="<?= e(SITE_URL) ?>/admin/galeria.php" class="quick-action">
                        <span>📸</span> Subir Fotos
                    </a>
                    <a href="<?= e(SITE_URL) ?>/admin/paginas.php" class="quick-action">
                        <span>📝</span> Editar Páginas
                    </a>
                    <a href="<?= e(SITE_URL) ?>/admin/configuracion.php" class="quick-action">
                        <span>⚙️</span> Configuración
                    </a>
                    <a href="<?= e(SITE_URL) ?>/admin/reservas.php?status=pendiente" class="quick-action">
                        <span>📅</span> Ver Pendientes
                    </a>
                    <a href="<?= e(SITE_URL) ?>/admin/reservas.php?export=csv" class="quick-action">
                        <span>📥</span> Exportar CSV
                    </a>
                </div>
            </div>
        </div>

        <!-- Modo mantenimiento -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3 class="card-title">🔧 Modo Mantenimiento</h3>
            </div>
            <div class="card-body">
                <p style="color:var(--admin-muted); font-size:.9rem; margin-bottom:16px;">
                    <?= $maintenanceMode
                        ? '⚠️ El sitio está en modo mantenimiento. Los visitantes verán la página "Volvemos pronto".'
                        : '✅ El sitio está en línea y visible para todos.' ?>
                </p>
                <a href="?toggle_maintenance=1&<?= CSRF_TOKEN_NAME ?>=<?= e(csrfToken()) ?>"
                   class="btn-admin-<?= $maintenanceMode ? 'success' : 'danger' ?>"
                   onclick="return confirm('<?= $maintenanceMode ? '¿Activar el sitio?' : '¿Poner en mantenimiento?' ?>')">
                    <?= $maintenanceMode ? '✅ Activar Sitio' : '🔧 Activar Mantenimiento' ?>
                </a>
            </div>
        </div>
    </div>

</div>

<?php adminFooter(); ?>
