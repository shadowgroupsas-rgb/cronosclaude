<?php
/**
 * Panel de Administración — Gestión de Reservas
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

// ── Cambiar estado de reserva ──────────────────────────────────────────────
if (isset($_GET['set_status']) && isset($_GET['id']) && verifyCsrf()) {
    $resId     = (int)$_GET['id'];
    $newStatus = in_array($_GET['set_status'], ['pendiente','confirmada','cancelada','completada'])
                 ? $_GET['set_status'] : null;

    if ($newStatus) {
        dbExecute('UPDATE ' . DB_PREFIX . 'reservations SET status=? WHERE id=?', [$newStatus, $resId]);

        // Enviar email al cliente si se confirma o cancela
        if (in_array($newStatus, ['confirmada', 'cancelada'])) {
            $res = dbQueryOne('SELECT * FROM ' . DB_PREFIX . 'reservations WHERE id=?', [$resId]);
            if ($res) {
                $siteName = getSetting('site_name', 'La Chingada');
                $emailRestaurante = getSetting('notification_email', '');
                $dateFormatted = formatDate($res['date']);
                $timeFormatted = formatTime($res['time']);

                if ($newStatus === 'confirmada') {
                    $body = emailTemplate(
                        "¡Reserva Confirmada! - {$siteName}",
                        "<h2>¡Tu reserva está confirmada! 🎉</h2>
                        <p>Hola <strong>{$res['name']}</strong>,</p>
                        <p>Tenemos todo listo para recibirte. ¡Nos vemos pronto!</p>
                        <div class='info-box'>
                            <p><strong>Fecha:</strong> {$dateFormatted}</p>
                            <p><strong>Hora:</strong> {$timeFormatted}</p>
                            <p><strong>Personas:</strong> {$res['guests']}</p>
                        </div>
                        <p>Si necesitas hacer algún cambio, contáctanos directamente.</p>
                        <a href='" . SITE_URL . "/reservas' class='btn'>Gestionar Reserva</a>"
                    );
                    sendEmail($res['email'], "✅ Reserva Confirmada - {$siteName}", $body, $emailRestaurante);
                } else {
                    $body = emailTemplate(
                        "Reserva Cancelada - {$siteName}",
                        "<h2>Tu reserva ha sido cancelada</h2>
                        <p>Hola <strong>{$res['name']}</strong>,</p>
                        <p>Lamentamos informarte que tu reserva para el {$dateFormatted} a las {$timeFormatted} ha sido cancelada.</p>
                        <p>Si crees que es un error o deseas hacer una nueva reserva, no dudes en contactarnos.</p>
                        <a href='" . SITE_URL . "/reservas' class='btn'>Hacer Nueva Reserva</a>"
                    );
                    sendEmail($res['email'], "Reserva Cancelada - {$siteName}", $body, $emailRestaurante);
                }
            }
        }

        $message = "Estado actualizado a: {$newStatus}";
    }
}

// ── Guardar nota del admin ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_note']) && verifyCsrf()) {
    $resId = (int)($_POST['res_id'] ?? 0);
    $note  = sanitizeStr($_POST['admin_notes'] ?? '', 1000);
    dbExecute('UPDATE ' . DB_PREFIX . 'reservations SET admin_notes=? WHERE id=?', [$note, $resId]);
    $message = 'Nota guardada.';
}

// ── Eliminar reserva ───────────────────────────────────────────────────────
if (isset($_GET['delete']) && verifyCsrf()) {
    dbExecute('DELETE FROM ' . DB_PREFIX . 'reservations WHERE id=?', [(int)$_GET['delete']]);
    header('Location: ' . SITE_URL . '/admin/reservas.php?deleted=1');
    exit;
}

// ── Exportar CSV ───────────────────────────────────────────────────────────
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $reservas = dbQuery('SELECT * FROM ' . DB_PREFIX . 'reservations ORDER BY date DESC, time DESC');

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="reservas-' . date('Y-m-d') . '.csv"');
    header('Pragma: no-cache');

    $output = fopen('php://output', 'w');
    fputs($output, "\xEF\xBB\xBF"); // BOM para Excel

    fputcsv($output, ['ID','Nombre','Email','Teléfono','Fecha','Hora','Personas','Ocasión','Comentarios','Estado','Creado'], ';');

    foreach ($reservas as $r) {
        fputcsv($output, [
            $r['id'], $r['name'], $r['email'], $r['phone'],
            $r['date'], $r['time'], $r['guests'],
            $r['occasion'], $r['comments'], $r['status'],
            $r['created_at']
        ], ';');
    }

    fclose($output);
    exit;
}

// ── Filtros y paginación ───────────────────────────────────────────────────
$filterStatus = $_GET['status'] ?? '';
$filterDate   = $_GET['date']   ?? '';
$filterSearch = $_GET['search'] ?? '';
$perPage      = 20;
$currentPage  = max(1, (int)($_GET['page'] ?? 1));

$where  = [];
$params = [];

if ($filterStatus) {
    $where[]  = 'status = ?';
    $params[] = $filterStatus;
}
if ($filterDate) {
    $where[]  = 'date = ?';
    $params[] = $filterDate;
}
if ($filterSearch) {
    $where[]  = '(name LIKE ? OR email LIKE ? OR phone LIKE ?)';
    $s = "%{$filterSearch}%";
    $params[] = $s; $params[] = $s; $params[] = $s;
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total       = (int)(dbQueryOne('SELECT COUNT(*) AS cnt FROM ' . DB_PREFIX . "reservations {$whereSQL}", $params)['cnt'] ?? 0);
$pagination  = paginate($total, $perPage, $currentPage);

$reservas = dbQuery(
    'SELECT * FROM ' . DB_PREFIX . "reservations {$whereSQL}
     ORDER BY date DESC, time DESC
     LIMIT {$perPage} OFFSET {$pagination['offset']}",
    $params
);

$message = $message ?: ($_GET['deleted'] ?? null ? 'Reserva eliminada.' : '');
adminHeader('Gestión de Reservas', 'reservas');
?>

<?php if ($message): ?>
<div class="alert-admin alert-admin-success"><?= e($message) ?></div>
<?php endif; ?>

<!-- Filtros -->
<div class="admin-card" style="margin-bottom:20px;">
    <div class="card-body">
        <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
            <div class="form-group-admin" style="margin:0;flex:1;min-width:150px;">
                <label class="form-label-admin">Estado</label>
                <select name="status" class="form-control-admin" onchange="this.form.submit()">
                    <option value="">Todos</option>
                    <option value="pendiente"  <?= $filterStatus==='pendiente'  ?'selected':'' ?>>Pendiente</option>
                    <option value="confirmada" <?= $filterStatus==='confirmada' ?'selected':'' ?>>Confirmada</option>
                    <option value="cancelada"  <?= $filterStatus==='cancelada'  ?'selected':'' ?>>Cancelada</option>
                    <option value="completada" <?= $filterStatus==='completada' ?'selected':'' ?>>Completada</option>
                </select>
            </div>
            <div class="form-group-admin" style="margin:0;flex:1;min-width:150px;">
                <label class="form-label-admin">Fecha</label>
                <input type="date" name="date" class="form-control-admin"
                       value="<?= e($filterDate) ?>" onchange="this.form.submit()">
            </div>
            <div class="form-group-admin" style="margin:0;flex:2;min-width:200px;">
                <label class="form-label-admin">Buscar</label>
                <input type="text" name="search" class="form-control-admin"
                       placeholder="Nombre, email, teléfono..."
                       value="<?= e($filterSearch) ?>">
            </div>
            <button type="submit" class="btn-admin-primary">Filtrar</button>
            <a href="?" class="btn-admin-outline">Limpiar</a>
            <a href="?export=csv" class="btn-admin-outline">📥 CSV</a>
        </form>
    </div>
</div>

<!-- Lista de reservas -->
<div class="admin-card">
    <div class="card-header">
        <h3 class="card-title">Reservas (<?= $total ?>)</h3>
    </div>
    <div class="card-body">
        <?php if (empty($reservas)): ?>
        <p class="empty-state">No hay reservas que coincidan con los filtros.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Cliente</th>
                        <th>Fecha / Hora</th>
                        <th>Personas</th>
                        <th>Ocasión</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservas as $r): ?>
                    <tr>
                        <td><?= e($r['id']) ?></td>
                        <td>
                            <strong><?= e($r['name']) ?></strong><br>
                            <small><a href="mailto:<?= e($r['email']) ?>" style="color:var(--admin-muted)"><?= e($r['email']) ?></a></small><br>
                            <small style="color:var(--admin-muted)"><?= e($r['phone']) ?></small>
                        </td>
                        <td>
                            <strong><?= e(formatDate($r['date'])) ?></strong><br>
                            <span style="color:var(--admin-secondary)"><?= e(formatTime($r['time'])) ?></span>
                        </td>
                        <td style="text-align:center;"><?= e($r['guests']) ?></td>
                        <td>
                            <?= e($r['occasion'] ?: '—') ?>
                            <?php if (!empty($r['comments'])): ?>
                            <br><small style="color:var(--admin-muted)"><?= e(mb_substr($r['comments'], 0, 50)) ?>...</small>
                            <?php endif; ?>
                        </td>
                        <td><?= reservationStatusBadge($r['status']) ?></td>
                        <td>
                            <div style="display:flex;gap:4px;flex-wrap:wrap;">
                                <?php if ($r['status'] !== 'confirmada'): ?>
                                <a href="?set_status=confirmada&id=<?= e($r['id']) ?>&<?= CSRF_TOKEN_NAME ?>=<?= e(csrfToken()) ?>"
                                   class="btn-admin-sm btn-admin-sm-success"
                                   title="Confirmar">✅</a>
                                <?php endif; ?>
                                <?php if ($r['status'] !== 'cancelada'): ?>
                                <a href="?set_status=cancelada&id=<?= e($r['id']) ?>&<?= CSRF_TOKEN_NAME ?>=<?= e(csrfToken()) ?>"
                                   class="btn-admin-sm btn-admin-sm-danger"
                                   onclick="return confirm('¿Cancelar esta reserva?')"
                                   title="Cancelar">❌</a>
                                <?php endif; ?>
                                <?php if ($r['status'] !== 'completada'): ?>
                                <a href="?set_status=completada&id=<?= e($r['id']) ?>&<?= CSRF_TOKEN_NAME ?>=<?= e(csrfToken()) ?>"
                                   class="btn-admin-sm"
                                   title="Marcar completada">✔️</a>
                                <?php endif; ?>
                                <a href="?delete=<?= e($r['id']) ?>&<?= CSRF_TOKEN_NAME ?>=<?= e(csrfToken()) ?>"
                                   class="btn-admin-sm btn-admin-sm-danger"
                                   onclick="return confirm('¿Eliminar esta reserva permanentemente?')"
                                   title="Eliminar">🗑</a>
                            </div>
                        </td>
                    </tr>
                    <?php if (!empty($r['admin_notes'])): ?>
                    <tr>
                        <td colspan="7" style="padding:8px 16px; background:var(--admin-bg); font-size:.85rem;">
                            📌 <strong>Nota:</strong> <?= e($r['admin_notes']) ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <?php if ($pagination['total_pages'] > 1): ?>
        <div class="pagination">
            <?php if ($pagination['has_prev']): ?>
            <a href="?page=<?= $pagination['prev_page'] ?>&status=<?= e($filterStatus) ?>&date=<?= e($filterDate) ?>"
               class="page-btn">← Anterior</a>
            <?php endif; ?>
            <span class="page-info">
                Página <?= $pagination['current_page'] ?> de <?= $pagination['total_pages'] ?>
            </span>
            <?php if ($pagination['has_next']): ?>
            <a href="?page=<?= $pagination['next_page'] ?>&status=<?= e($filterStatus) ?>&date=<?= e($filterDate) ?>"
               class="page-btn">Siguiente →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php endif; ?>
    </div>
</div>

<?php adminFooter(); ?>
