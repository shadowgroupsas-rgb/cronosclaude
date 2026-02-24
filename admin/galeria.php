<?php
/**
 * Panel de Administración — Gestión de Galería
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

// ── Subida múltiple de fotos ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_photos'])) {
    if (!verifyCsrf()) { $error = 'Token CSRF inválido.'; }
    else {
        $album      = sanitizeStr($_POST['album'] ?? 'general', 50);
        $altDefault = sanitizeStr($_POST['alt_default'] ?? '', 255);
        $uploaded   = 0;
        $errors     = [];

        // Subida múltiple — $_FILES['photos'] puede tener múltiples archivos
        $photos = $_FILES['photos'] ?? [];
        if (!empty($photos['name']) && is_array($photos['name'])) {
            $count = count($photos['name']);
            for ($i = 0; $i < $count; $i++) {
                if ($photos['error'][$i] !== UPLOAD_ERR_OK) continue;

                $singleFile = [
                    'name'     => $photos['name'][$i],
                    'type'     => $photos['type'][$i],
                    'tmp_name' => $photos['tmp_name'][$i],
                    'error'    => $photos['error'][$i],
                    'size'     => $photos['size'][$i],
                ];

                $filename = uploadImage($singleFile, 'galeria');
                if ($filename !== false) {
                    dbExecute(
                        'INSERT INTO ' . DB_PREFIX . 'gallery (filename, alt_text, album, active) VALUES (?,?,?,1)',
                        [$filename, $altDefault ?: "Foto galería La Chingada", $album]
                    );
                    $uploaded++;
                } else {
                    $errors[] = "Archivo #{$i}: formato/tamaño inválido";
                }
            }
        }

        if ($uploaded > 0) {
            $message = "{$uploaded} foto(s) subida(s) correctamente.";
        }
        if (!empty($errors)) {
            $error = implode(', ', $errors);
        }
    }
}

// ── Editar foto ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_photo'])) {
    if (!verifyCsrf()) { $error = 'Token CSRF inválido.'; }
    else {
        $photoId   = (int)($_POST['photo_id'] ?? 0);
        $altText   = sanitizeStr($_POST['alt_text'] ?? '', 255);
        $album     = sanitizeStr($_POST['album'] ?? 'general', 50);
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $active    = isset($_POST['active']) ? 1 : 0;

        dbExecute(
            'UPDATE ' . DB_PREFIX . 'gallery SET alt_text=?, album=?, sort_order=?, active=? WHERE id=?',
            [$altText, $album, $sortOrder, $active, $photoId]
        );
        $message = 'Foto actualizada.';
    }
}

// ── Eliminar foto ──────────────────────────────────────────────────────────
if (isset($_GET['delete']) && verifyCsrf()) {
    $photoId = (int)$_GET['delete'];
    $photo   = dbQueryOne('SELECT filename FROM ' . DB_PREFIX . 'gallery WHERE id=?', [$photoId]);
    if ($photo) {
        deleteImage($photo['filename']);
        dbExecute('DELETE FROM ' . DB_PREFIX . 'gallery WHERE id=?', [$photoId]);
    }
    header('Location: ' . SITE_URL . '/admin/galeria.php?deleted=1');
    exit;
}

// ── Toggle activo ──────────────────────────────────────────────────────────
if (isset($_GET['toggle']) && isset($_GET['id']) && verifyCsrf()) {
    dbExecute(
        'UPDATE ' . DB_PREFIX . 'gallery SET active = 1 - active WHERE id=?',
        [(int)$_GET['id']]
    );
    header('Location: ' . SITE_URL . '/admin/galeria.php');
    exit;
}

// ── Obtener álbumes únicos ─────────────────────────────────────────────────
$albums = dbQuery('SELECT DISTINCT album FROM ' . DB_PREFIX . 'gallery ORDER BY album ASC');
$filterAlbum = $_GET['album'] ?? '';

// ── Cargar fotos ───────────────────────────────────────────────────────────
$where  = $filterAlbum ? 'WHERE album = ?' : '';
$params = $filterAlbum ? [$filterAlbum] : [];
$fotos  = dbQuery(
    'SELECT * FROM ' . DB_PREFIX . "gallery {$where} ORDER BY sort_order ASC, id DESC",
    $params
);

$message = $message ?: ($_GET['deleted'] ?? null ? 'Foto eliminada.' : '');

// Foto a editar
$editPhoto = null;
if (isset($_GET['edit'])) {
    $editPhoto = dbQueryOne('SELECT * FROM ' . DB_PREFIX . 'gallery WHERE id=?', [(int)$_GET['edit']]);
}

adminHeader('Galería de Fotos', 'galeria');
?>

<?php if ($message): ?>
<div class="alert-admin alert-admin-success"><?= e($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert-admin alert-admin-danger"><?= e($error) ?></div>
<?php endif; ?>

<div class="admin-grid-2" style="align-items:start;">

    <!-- Panel de subida -->
    <div>
        <!-- Subir fotos -->
        <div class="admin-card" style="margin-bottom:20px;">
            <div class="card-header">
                <h3 class="card-title">📸 Subir Fotos</h3>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" id="upload-form">
                    <?= csrfField() ?>

                    <div class="form-group-admin">
                        <label class="form-label-admin">Fotos (múltiple selección)</label>
                        <div class="drop-zone" id="drop-zone"
                             onclick="document.getElementById('photo-input').click()">
                            <span class="drop-zone-icon">🖼</span>
                            <p>Haz clic o arrastra tus fotos aquí</p>
                            <small>JPG, PNG, WebP · Máx 5MB por foto</small>
                            <div id="preview-area" style="display:none;"></div>
                        </div>
                        <input type="file" id="photo-input" name="photos[]"
                               accept="image/jpeg,image/png,image/gif,image/webp"
                               multiple style="display:none"
                               onchange="previewFiles(this)">
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group-admin">
                            <label class="form-label-admin">Álbum / Sección</label>
                            <input type="text" name="album" class="form-control-admin"
                                   value="general" placeholder="general"
                                   list="album-list">
                            <datalist id="album-list">
                                <?php foreach ($albums as $a): ?>
                                <option value="<?= e($a['album']) ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="form-group-admin">
                            <label class="form-label-admin">Alt text por defecto (SEO)</label>
                            <input type="text" name="alt_default" class="form-control-admin"
                                   placeholder="Galería La Chingada">
                        </div>
                    </div>

                    <button type="submit" name="upload_photos" class="btn-admin-primary">
                        📤 Subir Fotos
                    </button>
                </form>
            </div>
        </div>

        <!-- Editar foto -->
        <?php if ($editPhoto): ?>
        <div class="admin-card">
            <div class="card-header">
                <h3 class="card-title">✏️ Editar Foto</h3>
                <a href="?" class="btn-admin-outline">Cancelar</a>
            </div>
            <div class="card-body">
                <img src="<?= e(imgUrl($editPhoto['filename'])) ?>"
                     alt="<?= e($editPhoto['alt_text']) ?>"
                     style="width:100%;height:200px;object-fit:cover;border-radius:8px;margin-bottom:16px;">
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="photo_id" value="<?= e($editPhoto['id']) ?>">

                    <div class="form-group-admin">
                        <label class="form-label-admin">Alt text (SEO)</label>
                        <input type="text" name="alt_text" class="form-control-admin"
                               value="<?= e($editPhoto['alt_text']) ?>">
                    </div>
                    <div class="form-grid-2">
                        <div class="form-group-admin">
                            <label class="form-label-admin">Álbum</label>
                            <input type="text" name="album" class="form-control-admin"
                                   value="<?= e($editPhoto['album']) ?>" list="album-list">
                        </div>
                        <div class="form-group-admin">
                            <label class="form-label-admin">Orden</label>
                            <input type="number" name="sort_order" class="form-control-admin"
                                   min="0" value="<?= e($editPhoto['sort_order']) ?>">
                        </div>
                    </div>
                    <label class="toggle-label" style="margin-bottom:16px;">
                        <input type="checkbox" name="active" value="1" <?= $editPhoto['active'] ? 'checked' : '' ?>>
                        <span class="toggle-track"></span>
                        Foto activa (visible en el sitio)
                    </label>
                    <button type="submit" name="edit_photo" class="btn-admin-primary">💾 Guardar</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Galería de fotos existentes -->
    <div class="admin-card">
        <div class="card-header">
            <h3 class="card-title">Fotos (<?= count($fotos) ?>)</h3>

            <!-- Filtro por álbum -->
            <?php if (count($albums) > 1): ?>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                <a href="?" class="filter-pill <?= !$filterAlbum ? 'active' : '' ?>">Todos</a>
                <?php foreach ($albums as $a): ?>
                <a href="?album=<?= urlencode($a['album']) ?>"
                   class="filter-pill <?= $filterAlbum===$a['album'] ? 'active' : '' ?>">
                    <?= e($a['album']) ?>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if (empty($fotos)): ?>
            <p class="empty-state">No hay fotos. Sube las primeras imágenes.</p>
            <?php else: ?>
            <div class="gallery-admin-grid">
                <?php foreach ($fotos as $foto): ?>
                <div class="gallery-admin-item <?= !$foto['active'] ? 'inactive' : '' ?>">
                    <img src="<?= e(imgUrl($foto['filename'])) ?>"
                         alt="<?= e($foto['alt_text']) ?>"
                         loading="lazy">
                    <div class="gallery-admin-overlay">
                        <div class="gallery-admin-actions">
                            <a href="?edit=<?= e($foto['id']) ?>" class="btn-admin-sm" title="Editar">✏️</a>
                            <a href="?toggle=1&id=<?= e($foto['id']) ?>&<?= CSRF_TOKEN_NAME ?>=<?= e(csrfToken()) ?>"
                               class="btn-admin-sm" title="<?= $foto['active'] ? 'Ocultar' : 'Mostrar' ?>">
                               <?= $foto['active'] ? '👁' : '🙈' ?>
                            </a>
                            <a href="?delete=<?= e($foto['id']) ?>&<?= CSRF_TOKEN_NAME ?>=<?= e(csrfToken()) ?>"
                               class="btn-admin-sm btn-admin-sm-danger"
                               onclick="return confirm('¿Eliminar esta foto?')"
                               title="Eliminar">🗑</a>
                        </div>
                    </div>
                    <?php if (!$foto['active']): ?>
                    <div class="gallery-inactive-badge">Oculta</div>
                    <?php endif; ?>
                    <div class="gallery-alt-text" title="<?= e($foto['alt_text']) ?>">
                        <?= e(mb_substr($foto['alt_text'], 0, 30)) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function previewFiles(input) {
    const previewArea = document.getElementById('preview-area');
    const dropZone    = document.getElementById('drop-zone');
    previewArea.innerHTML = '';

    if (input.files && input.files.length > 0) {
        previewArea.style.display = 'flex';
        previewArea.style.flexWrap = 'wrap';
        previewArea.style.gap = '8px';
        previewArea.style.marginTop = '12px';

        Array.from(input.files).forEach(file => {
            const reader = new FileReader();
            const img = document.createElement('img');
            img.style.cssText = 'width:80px;height:80px;object-fit:cover;border-radius:6px;';
            reader.onload = e => img.src = e.target.result;
            reader.readAsDataURL(file);
            previewArea.appendChild(img);
        });
    }
}

// Drag & drop
const dropZone = document.getElementById('drop-zone');
const photoInput = document.getElementById('photo-input');

dropZone.addEventListener('dragover', e => {
    e.preventDefault();
    dropZone.classList.add('drag-over');
});

dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));

dropZone.addEventListener('drop', e => {
    e.preventDefault();
    dropZone.classList.remove('drag-over');
    const dt = e.dataTransfer;
    if (dt.files.length > 0) {
        photoInput.files = dt.files;
        previewFiles(photoInput);
    }
});
</script>

<?php adminFooter(); ?>
