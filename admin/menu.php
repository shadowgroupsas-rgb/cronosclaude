<?php
/**
 * Panel de Administración — Gestión del Menú (Categorías y Platos)
 */

require_once dirname(__DIR__) . '/config.php';
require_once INCLUDES_PATH . '/db.php';
require_once INCLUDES_PATH . '/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

date_default_timezone_set(SITE_TIMEZONE);
requireAuth();

$action  = $_GET['action']  ?? 'list';
$tab     = $_GET['tab']     ?? 'platos';
$message = '';
$error   = '';

// ── CRUD de Categorías ─────────────────────────────────────────────────────

// Crear/Editar categoría
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_category'])) {
    if (!verifyCsrf()) { $error = 'Token CSRF inválido.'; }
    else {
        $catId   = (int)($_POST['cat_id'] ?? 0);
        $catName = sanitizeStr($_POST['cat_name'] ?? '', 100);
        $catSlug = makeSlug($catName);
        $catIcon = sanitizeStr($_POST['cat_icon'] ?? '', 10);
        $catOrder= (int)($_POST['cat_order'] ?? 0);
        $catActive= isset($_POST['cat_active']) ? 1 : 0;

        if (empty($catName)) {
            $error = 'El nombre de la categoría es requerido.';
        } else {
            if ($catId > 0) {
                dbExecute(
                    'UPDATE ' . DB_PREFIX . 'menu_categories
                     SET name=?, slug=?, icon=?, sort_order=?, active=?
                     WHERE id=?',
                    [$catName, $catSlug, $catIcon, $catOrder, $catActive, $catId]
                );
                $message = 'Categoría actualizada correctamente.';
            } else {
                // Verificar slug único
                $existing = dbQueryOne('SELECT id FROM ' . DB_PREFIX . 'menu_categories WHERE slug=?', [$catSlug]);
                if ($existing) $catSlug .= '-' . time();
                dbExecute(
                    'INSERT INTO ' . DB_PREFIX . 'menu_categories (name, slug, icon, sort_order, active) VALUES (?,?,?,?,?)',
                    [$catName, $catSlug, $catIcon, $catOrder, $catActive]
                );
                $message = 'Categoría creada correctamente.';
            }
        }
        $tab = 'categorias';
    }
}

// Eliminar categoría
if (isset($_GET['delete_cat']) && verifyCsrf()) {
    $catId = (int)$_GET['delete_cat'];
    // Verificar que no tenga platos
    $hasDishes = dbQueryOne('SELECT COUNT(*) AS cnt FROM ' . DB_PREFIX . 'menu_items WHERE category_id=?', [$catId]);
    if ((int)($hasDishes['cnt'] ?? 0) > 0) {
        $error = 'No puedes eliminar una categoría que tiene platos. Mueve o elimina los platos primero.';
    } else {
        dbExecute('DELETE FROM ' . DB_PREFIX . 'menu_categories WHERE id=?', [$catId]);
        $message = 'Categoría eliminada.';
    }
    $tab = 'categorias';
}

// ── CRUD de Platos ─────────────────────────────────────────────────────────

// Crear/Editar plato
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_item'])) {
    if (!verifyCsrf()) { $error = 'Token CSRF inválido.'; }
    else {
        $itemId       = (int)($_POST['item_id'] ?? 0);
        $catId        = (int)($_POST['item_category'] ?? 0);
        $itemName     = sanitizeStr($_POST['item_name'] ?? '', 200);
        $itemDescShort= sanitizeStr($_POST['item_desc_short'] ?? '', 500);
        $itemDescLong = sanitizeStr($_POST['item_desc_long'] ?? '', 3000);
        $itemPrice    = (float)str_replace(',', '.', $_POST['item_price'] ?? '0');
        $itemPriceOld = !empty($_POST['item_price_old']) ? (float)str_replace(',', '.', $_POST['item_price_old']) : null;
        $itemAlt      = sanitizeStr($_POST['item_alt'] ?? '', 255);
        $itemOrder    = (int)($_POST['item_order'] ?? 0);
        $itemActive   = isset($_POST['item_active']) ? 1 : 0;
        $itemFeatured = isset($_POST['item_featured']) ? 1 : 0;
        $itemBadges   = array_intersect($_POST['item_badges'] ?? [], ['nuevo','popular','sin_gluten','vegetariano','picante']);

        // Manejar imagen
        $currentImage = sanitizeStr($_POST['current_image'] ?? '');
        $imageFile    = $currentImage;

        if (!empty($_FILES['item_image']['name'])) {
            $uploadResult = adminUploadImage('item_image', 'menu');
            if ($uploadResult['success']) {
                // Eliminar imagen anterior si existe
                if (!empty($currentImage)) deleteImage($currentImage);
                $imageFile = $uploadResult['filename'];
            } else {
                $error = $uploadResult['error'];
            }
        }

        if (empty($error)) {
            if (empty($itemName) || $catId === 0) {
                $error = 'Nombre y categoría son requeridos.';
            } elseif ($itemPrice <= 0) {
                $error = 'El precio debe ser mayor a 0.';
            } else {
                $badgeStr = implode(',', $itemBadges);
                $slug = makeSlug($itemName);

                if ($itemId > 0) {
                    dbExecute(
                        'UPDATE ' . DB_PREFIX . 'menu_items SET
                         category_id=?, name=?, slug=?, description_short=?,
                         description_long=?, price=?, price_old=?, image=?,
                         alt_text=?, badge=?, featured=?, active=?, sort_order=?
                         WHERE id=?',
                        [$catId, $itemName, $slug, $itemDescShort, $itemDescLong,
                         $itemPrice, $itemPriceOld, $imageFile, $itemAlt,
                         $badgeStr, $itemFeatured, $itemActive, $itemOrder, $itemId]
                    );
                    $message = 'Plato actualizado correctamente.';
                } else {
                    dbExecute(
                        'INSERT INTO ' . DB_PREFIX . 'menu_items
                         (category_id, name, slug, description_short, description_long,
                          price, price_old, image, alt_text, badge, featured, active, sort_order)
                         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
                        [$catId, $itemName, $slug, $itemDescShort, $itemDescLong,
                         $itemPrice, $itemPriceOld, $imageFile, $itemAlt,
                         $badgeStr, $itemFeatured, $itemActive, $itemOrder]
                    );
                    $message = 'Plato creado correctamente.';
                }
                $action = 'list';
            }
        }
    }
}

// Eliminar plato
if (isset($_GET['delete_item']) && verifyCsrf()) {
    $itemId = (int)$_GET['delete_item'];
    $item   = dbQueryOne('SELECT image FROM ' . DB_PREFIX . 'menu_items WHERE id=?', [$itemId]);
    if ($item && !empty($item['image'])) deleteImage($item['image']);
    dbExecute('DELETE FROM ' . DB_PREFIX . 'menu_items WHERE id=?', [$itemId]);
    $message = 'Plato eliminado.';
}

// Toggle activo/destacado
if (isset($_GET['toggle']) && isset($_GET['id']) && verifyCsrf()) {
    $toggleId    = (int)$_GET['id'];
    $toggleField = in_array($_GET['toggle'], ['active','featured']) ? $_GET['toggle'] : 'active';
    dbExecute(
        'UPDATE ' . DB_PREFIX . "menu_items SET {$toggleField} = 1 - {$toggleField} WHERE id=?",
        [$toggleId]
    );
    header('Location: ' . SITE_URL . '/admin/menu.php?message=Actualizado');
    exit;
}

// ── Cargar datos ───────────────────────────────────────────────────────────
$categorias = dbQuery('SELECT * FROM ' . DB_PREFIX . 'menu_categories ORDER BY sort_order ASC, name ASC');

$platos = dbQuery(
    'SELECT mi.*, mc.name AS cat_name
     FROM ' . DB_PREFIX . 'menu_items mi
     JOIN ' . DB_PREFIX . 'menu_categories mc ON mc.id = mi.category_id
     ORDER BY mc.sort_order ASC, mi.sort_order ASC, mi.name ASC'
);

// Editar plato específico
$editItem = null;
if ($action === 'edit_item' && !empty($_GET['id'])) {
    $editItem = dbQueryOne(
        'SELECT * FROM ' . DB_PREFIX . 'menu_items WHERE id=?',
        [(int)$_GET['id']]
    );
}

// Editar categoría específica
$editCat = null;
if (isset($_GET['edit_cat'])) {
    $editCat = dbQueryOne(
        'SELECT * FROM ' . DB_PREFIX . 'menu_categories WHERE id=?',
        [(int)$_GET['edit_cat']]
    );
    $tab = 'categorias';
}

$message = $message ?: ($_GET['message'] ?? '');
adminHeader('Gestión del Menú', 'menu');
?>

<?php if ($message): ?>
<div class="alert-admin alert-admin-success"><?= e($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert-admin alert-admin-danger"><?= e($error) ?></div>
<?php endif; ?>

<!-- Tabs -->
<div class="admin-tabs">
    <button class="admin-tab <?= $tab !== 'categorias' ? 'active' : '' ?>"
            onclick="switchTab('platos', this)">🍽 Platos</button>
    <button class="admin-tab <?= $tab === 'categorias' ? 'active' : '' ?>"
            onclick="switchTab('categorias', this)">📂 Categorías</button>
</div>

<!-- ── TAB: PLATOS ───────────────────────────────────────────────── -->
<div id="tab-platos" class="tab-content <?= $tab !== 'categorias' ? 'active' : '' ?>">

    <?php if ($action === 'new_item' || $action === 'edit_item'): ?>
    <!-- Formulario Plato -->
    <div class="admin-card">
        <div class="card-header">
            <h3 class="card-title"><?= $editItem ? 'Editar Plato' : 'Nuevo Plato' ?></h3>
            <a href="<?= e(SITE_URL) ?>/admin/menu.php" class="btn-admin-outline">← Volver</a>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <?= csrfField() ?>
                <input type="hidden" name="item_id" value="<?= (int)($editItem['id'] ?? 0) ?>">
                <input type="hidden" name="current_image" value="<?= e($editItem['image'] ?? '') ?>">

                <div class="form-grid-2">
                    <div class="form-group-admin">
                        <label class="form-label-admin">Nombre del Plato *</label>
                        <input type="text" name="item_name" class="form-control-admin" required
                               value="<?= e($editItem['name'] ?? '') ?>" placeholder="Ej: Tacos al Pastor">
                    </div>
                    <div class="form-group-admin">
                        <label class="form-label-admin">Categoría *</label>
                        <select name="item_category" class="form-control-admin" required>
                            <option value="">Seleccionar...</option>
                            <?php foreach ($categorias as $cat): ?>
                            <option value="<?= e($cat['id']) ?>"
                                    <?= (isset($editItem) && $editItem['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                <?= e($cat['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group-admin">
                    <label class="form-label-admin">Descripción corta (para las cards)</label>
                    <textarea name="item_desc_short" class="form-control-admin" rows="2"
                              placeholder="Descripción breve que aparece en la tarjeta..."><?= e($editItem['description_short'] ?? '') ?></textarea>
                </div>

                <div class="form-group-admin">
                    <label class="form-label-admin">Descripción completa (para el modal de detalle)</label>
                    <textarea name="item_desc_long" class="form-control-admin" rows="4"
                              placeholder="Descripción detallada del plato, ingredientes, historia..."><?= e($editItem['description_long'] ?? '') ?></textarea>
                </div>

                <div class="form-grid-3">
                    <div class="form-group-admin">
                        <label class="form-label-admin">Precio (COP) *</label>
                        <input type="number" name="item_price" class="form-control-admin"
                               min="0" step="100" required
                               value="<?= e($editItem['price'] ?? '') ?>"
                               placeholder="35000">
                    </div>
                    <div class="form-group-admin">
                        <label class="form-label-admin">Precio tachado (oferta, opcional)</label>
                        <input type="number" name="item_price_old" class="form-control-admin"
                               min="0" step="100"
                               value="<?= e($editItem['price_old'] ?? '') ?>"
                               placeholder="45000">
                    </div>
                    <div class="form-group-admin">
                        <label class="form-label-admin">Orden de aparición</label>
                        <input type="number" name="item_order" class="form-control-admin"
                               min="0" value="<?= e($editItem['sort_order'] ?? 0) ?>">
                    </div>
                </div>

                <!-- Imagen -->
                <div class="form-group-admin">
                    <label class="form-label-admin">Foto del plato</label>
                    <?php if (!empty($editItem['image'])): ?>
                    <div style="margin-bottom:10px;">
                        <img src="<?= e(imgUrl($editItem['image'])) ?>" alt="Imagen actual"
                             style="height:120px;object-fit:cover;border-radius:8px;">
                        <small style="display:block;color:var(--admin-muted);margin-top:4px;">Imagen actual. Selecciona una nueva para reemplazarla.</small>
                    </div>
                    <?php endif; ?>
                    <input type="file" name="item_image" class="form-control-admin"
                           accept="image/jpeg,image/png,image/gif,image/webp">
                    <small style="color:var(--admin-muted)">JPG, PNG, GIF, WebP. Máx 5MB.</small>
                </div>

                <div class="form-group-admin">
                    <label class="form-label-admin">Texto alternativo (alt) de la imagen (SEO)</label>
                    <input type="text" name="item_alt" class="form-control-admin"
                           value="<?= e($editItem['alt_text'] ?? '') ?>"
                           placeholder="Ej: Tacos al Pastor con cilantro y cebolla">
                </div>

                <!-- Badges -->
                <div class="form-group-admin">
                    <label class="form-label-admin">Badges / Etiquetas</label>
                    <div class="checkbox-group">
                        <?php
                        $currentBadges = array_filter(explode(',', $editItem['badge'] ?? ''));
                        $badgeOptions = ['nuevo'=>'🆕 Nuevo','popular'=>'🔥 Popular','sin_gluten'=>'🌾 Sin Gluten','vegetariano'=>'🥦 Vegetariano','picante'=>'🌶 Picante'];
                        foreach ($badgeOptions as $val => $label): ?>
                        <label class="checkbox-label">
                            <input type="checkbox" name="item_badges[]" value="<?= e($val) ?>"
                                   <?= in_array($val, $currentBadges) ? 'checked' : '' ?>>
                            <?= e($label) ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Opciones -->
                <div class="form-grid-3">
                    <label class="toggle-label">
                        <input type="checkbox" name="item_active" value="1"
                               <?= ($editItem['active'] ?? 1) ? 'checked' : '' ?>>
                        <span class="toggle-track"></span>
                        Plato activo (visible)
                    </label>
                    <label class="toggle-label">
                        <input type="checkbox" name="item_featured" value="1"
                               <?= ($editItem['featured'] ?? 0) ? 'checked' : '' ?>>
                        <span class="toggle-track"></span>
                        Destacado en inicio
                    </label>
                </div>

                <div style="margin-top:24px; display:flex; gap:12px;">
                    <button type="submit" name="save_item" class="btn-admin-primary">
                        💾 Guardar Plato
                    </button>
                    <a href="<?= e(SITE_URL) ?>/admin/menu.php" class="btn-admin-outline">Cancelar</a>
                </div>
            </form>
        </div>
    </div>

    <?php else: ?>
    <!-- Lista de Platos -->
    <div class="admin-card">
        <div class="card-header">
            <h3 class="card-title">Platos del Menú</h3>
            <a href="?action=new_item" class="btn-admin-primary">+ Nuevo Plato</a>
        </div>
        <div class="card-body">
            <!-- Filtro por categoría -->
            <div style="margin-bottom:16px; display:flex; gap:8px; flex-wrap:wrap;">
                <a href="?" class="filter-pill <?= !isset($_GET['cat']) ? 'active' : '' ?>">Todos</a>
                <?php foreach ($categorias as $cat): ?>
                <a href="?cat=<?= e($cat['id']) ?>"
                   class="filter-pill <?= (isset($_GET['cat']) && $_GET['cat'] == $cat['id']) ? 'active' : '' ?>">
                    <?= e($cat['name']) ?>
                </a>
                <?php endforeach; ?>
            </div>

            <?php
            $filteredPlatos = $platos;
            if (isset($_GET['cat']) && (int)$_GET['cat'] > 0) {
                $filteredPlatos = array_filter($platos, fn($p) => $p['category_id'] == (int)$_GET['cat']);
            }
            ?>

            <?php if (empty($filteredPlatos)): ?>
            <p class="empty-state">No hay platos. <a href="?action=new_item">Crear primero</a></p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Foto</th>
                            <th>Nombre</th>
                            <th>Categoría</th>
                            <th>Precio</th>
                            <th>Badges</th>
                            <th>Activo</th>
                            <th>Destacado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($filteredPlatos as $item): ?>
                        <tr>
                            <td>
                                <?php if (!empty($item['image'])): ?>
                                <img src="<?= e(imgUrl($item['image'])) ?>"
                                     alt="<?= e($item['name']) ?>"
                                     style="width:60px;height:50px;object-fit:cover;border-radius:6px;">
                                <?php else: ?>
                                <div style="width:60px;height:50px;background:var(--admin-bg);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;">🍽</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= e($item['name']) ?></strong>
                                <?php if (!empty($item['description_short'])): ?>
                                <br><small style="color:var(--admin-muted)"><?= e(mb_substr($item['description_short'], 0, 60)) ?>...</small>
                                <?php endif; ?>
                            </td>
                            <td><?= e($item['cat_name']) ?></td>
                            <td>
                                <strong><?= formatPrice((float)$item['price']) ?></strong>
                                <?php if (!empty($item['price_old'])): ?>
                                <br><small style="text-decoration:line-through;color:var(--admin-muted)"><?= formatPrice((float)$item['price_old']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php foreach (array_filter(explode(',', $item['badge'] ?? '')) as $b): ?>
                                <span class="badge-mini"><?= e($b) ?></span>
                                <?php endforeach; ?>
                            </td>
                            <td>
                                <a href="?toggle=active&id=<?= e($item['id']) ?>&<?= CSRF_TOKEN_NAME ?>=<?= e(csrfToken()) ?>"
                                   class="toggle-btn <?= $item['active'] ? 'toggle-on' : 'toggle-off' ?>">
                                    <?= $item['active'] ? '✅' : '⭕' ?>
                                </a>
                            </td>
                            <td>
                                <a href="?toggle=featured&id=<?= e($item['id']) ?>&<?= CSRF_TOKEN_NAME ?>=<?= e(csrfToken()) ?>"
                                   class="toggle-btn <?= $item['featured'] ? 'toggle-on' : 'toggle-off' ?>">
                                    <?= $item['featured'] ? '⭐' : '☆' ?>
                                </a>
                            </td>
                            <td>
                                <div style="display:flex;gap:6px;">
                                    <a href="?action=edit_item&id=<?= e($item['id']) ?>" class="btn-admin-sm">✏️</a>
                                    <a href="?delete_item=<?= e($item['id']) ?>&<?= CSRF_TOKEN_NAME ?>=<?= e(csrfToken()) ?>"
                                       class="btn-admin-sm btn-admin-sm-danger"
                                       onclick="return confirm('¿Eliminar este plato?')">🗑</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ── TAB: CATEGORÍAS ───────────────────────────────────────────── -->
<div id="tab-categorias" class="tab-content <?= $tab === 'categorias' ? 'active' : '' ?>">

    <div class="admin-grid-2">

        <!-- Formulario de categoría -->
        <div class="admin-card">
            <div class="card-header">
                <h3 class="card-title"><?= $editCat ? 'Editar Categoría' : 'Nueva Categoría' ?></h3>
                <?php if ($editCat): ?>
                <a href="?tab=categorias" class="btn-admin-outline">+ Nueva</a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="cat_id" value="<?= (int)($editCat['id'] ?? 0) ?>">

                    <div class="form-group-admin">
                        <label class="form-label-admin">Nombre *</label>
                        <input type="text" name="cat_name" class="form-control-admin" required
                               value="<?= e($editCat['name'] ?? '') ?>"
                               placeholder="Ej: Entradas">
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group-admin">
                            <label class="form-label-admin">Icono (emoji)</label>
                            <input type="text" name="cat_icon" class="form-control-admin"
                                   value="<?= e($editCat['icon'] ?? '') ?>"
                                   placeholder="🌮" maxlength="4"
                                   style="font-size:1.3rem;text-align:center;">
                        </div>
                        <div class="form-group-admin">
                            <label class="form-label-admin">Orden</label>
                            <input type="number" name="cat_order" class="form-control-admin"
                                   min="0" value="<?= e($editCat['sort_order'] ?? 0) ?>">
                        </div>
                    </div>

                    <label class="toggle-label" style="margin-bottom:20px;">
                        <input type="checkbox" name="cat_active" value="1"
                               <?= ($editCat['active'] ?? 1) ? 'checked' : '' ?>>
                        <span class="toggle-track"></span>
                        Categoría activa
                    </label>

                    <button type="submit" name="save_category" class="btn-admin-primary">
                        💾 Guardar Categoría
                    </button>
                </form>
            </div>
        </div>

        <!-- Lista de categorías -->
        <div class="admin-card">
            <div class="card-header">
                <h3 class="card-title">Categorías Existentes</h3>
            </div>
            <div class="card-body">
                <?php if (empty($categorias)): ?>
                <p class="empty-state">No hay categorías aún.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr><th>Icono</th><th>Nombre</th><th>Platos</th><th>Estado</th><th></th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categorias as $cat):
                                $catCount = (int)(dbQueryOne('SELECT COUNT(*) AS cnt FROM ' . DB_PREFIX . 'menu_items WHERE category_id=?', [$cat['id']])['cnt'] ?? 0);
                            ?>
                            <tr>
                                <td style="font-size:1.5rem;"><?= e($cat['icon'] ?? '') ?></td>
                                <td>
                                    <strong><?= e($cat['name']) ?></strong><br>
                                    <small style="color:var(--admin-muted)"><?= e($cat['slug']) ?></small>
                                </td>
                                <td><?= $catCount ?></td>
                                <td><span class="status-badge <?= $cat['active'] ? 'status-confirmed' : 'status-cancelled' ?>"><?= $cat['active'] ? 'Activa' : 'Inactiva' ?></span></td>
                                <td>
                                    <a href="?tab=categorias&edit_cat=<?= e($cat['id']) ?>" class="btn-admin-sm">✏️</a>
                                    <a href="?tab=categorias&delete_cat=<?= e($cat['id']) ?>&<?= CSRF_TOKEN_NAME ?>=<?= e(csrfToken()) ?>"
                                       class="btn-admin-sm btn-admin-sm-danger"
                                       onclick="return confirm('¿Eliminar categoría? Solo si no tiene platos.')">🗑</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script>
function switchTab(name, btn) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.admin-tab').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    btn.classList.add('active');
}
</script>

<?php adminFooter(); ?>
