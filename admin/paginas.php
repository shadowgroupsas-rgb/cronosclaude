<?php
/**
 * Panel de Administración — Editor de Páginas (Secciones de Contenido)
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
$activePage = $_GET['page'] ?? 'home';

// ── Guardar sección ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_section'])) {
    if (!verifyCsrf()) {
        $error = 'Token CSRF inválido.';
    } else {
        $page    = sanitizeStr($_POST['section_page'] ?? '', 50);
        $section = sanitizeStr($_POST['section_name'] ?? '', 100);

        // Obtener contenido actual
        $row = dbQueryOne(
            'SELECT content FROM ' . DB_PREFIX . 'page_sections WHERE page=? AND section=?',
            [$page, $section]
        );
        $content = $row ? json_decode($row['content'], true) ?: [] : [];

        // Procesar campos del formulario
        $fields = $_POST['fields'] ?? [];
        foreach ($fields as $key => $value) {
            $key   = sanitizeStr($key, 100);
            $value = is_string($value) ? sanitizeStr($value, 5000) : '';
            $content[$key] = $value;
        }

        // Procesar imágenes subidas
        $imageFields = $_POST['image_fields'] ?? [];
        foreach ($imageFields as $fieldKey) {
            $fieldKey = sanitizeStr($fieldKey, 100);
            if (!empty($_FILES["img_{$fieldKey}"]['name'])) {
                // Eliminar imagen anterior si existe
                if (!empty($content[$fieldKey])) deleteImage($content[$fieldKey]);

                $result = adminUploadImage("img_{$fieldKey}", 'paginas');
                if ($result['success']) {
                    $content[$fieldKey] = $result['filename'];
                } else {
                    $error = "Error al subir imagen '{$fieldKey}': " . $result['error'];
                    break;
                }
            }
        }

        if (empty($error)) {
            dbExecute(
                'INSERT INTO ' . DB_PREFIX . 'page_sections (page, section, content)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE content = VALUES(content)',
                [$page, $section, json_encode($content, JSON_UNESCAPED_UNICODE)]
            );
            $message = 'Sección guardada correctamente.';
            $activePage = $page;
        }
    }
}

// ── Gestión de testimonios ─────────────────────────────────────────────────
// (usamos tabla separada para los testimonios)

// Agregar/Editar testimonio
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_testimonio'])) {
    if (!verifyCsrf()) { $error = 'Token CSRF inválido.'; }
    else {
        $tId     = (int)($_POST['t_id'] ?? 0);
        $tName   = sanitizeStr($_POST['t_name'] ?? '', 100);
        $tText   = sanitizeStr($_POST['t_text'] ?? '', 500);
        $tOrigin = sanitizeStr($_POST['t_origin'] ?? '', 100);
        $tRating = max(1, min(5, (int)($_POST['t_rating'] ?? 5)));
        $tActive = isset($_POST['t_active']) ? 1 : 0;
        $tOrder  = (int)($_POST['t_order'] ?? 0);

        if (empty($tName) || empty($tText)) {
            $error = 'Nombre y texto del testimonio son requeridos.';
        } else {
            // Avatar opcional
            $currentAvatar = sanitizeStr($_POST['current_avatar'] ?? '');
            $avatarFile    = $currentAvatar;

            if (!empty($_FILES['t_avatar']['name'])) {
                if (!empty($currentAvatar)) deleteImage($currentAvatar);
                $result = adminUploadImage('t_avatar', 'testimonios');
                $avatarFile = $result['success'] ? $result['filename'] : $currentAvatar;
            }

            if ($tId > 0) {
                dbExecute(
                    'UPDATE ' . DB_PREFIX . 'testimonios SET name=?,text=?,origin=?,rating=?,avatar=?,active=?,sort_order=? WHERE id=?',
                    [$tName, $tText, $tOrigin, $tRating, $avatarFile, $tActive, $tOrder, $tId]
                );
            } else {
                dbExecute(
                    'INSERT INTO ' . DB_PREFIX . 'testimonios (name,text,origin,rating,avatar,active,sort_order) VALUES (?,?,?,?,?,?,?)',
                    [$tName, $tText, $tOrigin, $tRating, $avatarFile, $tActive, $tOrder]
                );
            }
            $message = 'Testimonio guardado.';
        }
    }
    $activePage = 'home';
}

if (isset($_GET['delete_testimonio']) && verifyCsrf()) {
    $tId = (int)$_GET['delete_testimonio'];
    $t   = dbQueryOne('SELECT avatar FROM ' . DB_PREFIX . 'testimonios WHERE id=?', [$tId]);
    if ($t && !empty($t['avatar'])) deleteImage($t['avatar']);
    dbExecute('DELETE FROM ' . DB_PREFIX . 'testimonios WHERE id=?', [$tId]);
    header('Location: ' . SITE_URL . '/admin/paginas.php?page=home&msg=Testimonio+eliminado');
    exit;
}

// ── Cargar datos ───────────────────────────────────────────────────────────
function getSection(string $page, string $section): array {
    $row = dbQueryOne(
        'SELECT content FROM ' . DB_PREFIX . 'page_sections WHERE page=? AND section=?',
        [$page, $section]
    );
    return $row ? (json_decode($row['content'], true) ?: []) : [];
}

$heroData     = getSection('home', 'hero');
$historiaData = getSection('home', 'historia');
$horariosData = getSection('home', 'horarios');

$testimonios  = dbQuery('SELECT * FROM ' . DB_PREFIX . 'testimonios ORDER BY sort_order ASC, name ASC');

$editTestimonio = null;
if (isset($_GET['edit_t'])) {
    $editTestimonio = dbQueryOne('SELECT * FROM ' . DB_PREFIX . 'testimonios WHERE id=?', [(int)$_GET['edit_t']]);
    $activePage = 'home';
}

$message = $message ?: ($_GET['msg'] ?? '');
adminHeader('Editor de Páginas', 'paginas');
?>

<?php if ($message): ?>
<div class="alert-admin alert-admin-success"><?= e($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert-admin alert-admin-danger"><?= e($error) ?></div>
<?php endif; ?>

<!-- Navegación de páginas -->
<div class="admin-tabs" style="margin-bottom:24px;">
    <button class="admin-tab <?= $activePage==='home' ? 'active':'' ?>"
            onclick="setActivePage('home', this)">🏠 Inicio</button>
    <button class="admin-tab <?= $activePage==='menu' ? 'active':'' ?>"
            onclick="setActivePage('menu', this)">🍽 Menú</button>
    <button class="admin-tab <?= $activePage==='reservas' ? 'active':'' ?>"
            onclick="setActivePage('reservas', this)">📅 Reservas</button>
    <button class="admin-tab <?= $activePage==='contacto' ? 'active':'' ?>"
            onclick="setActivePage('contacto', this)">📞 Contacto</button>
</div>

<!-- ── PÁGINA: INICIO ────────────────────────────────────────────── -->
<div id="page-home" class="page-content <?= $activePage==='home' ? 'active':'' ?>">

    <!-- SECCIÓN HERO -->
    <div class="admin-card" style="margin-bottom:20px;">
        <div class="card-header">
            <h3 class="card-title">🦸 Sección Hero (Portada)</h3>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <?= csrfField() ?>
                <input type="hidden" name="section_page" value="home">
                <input type="hidden" name="section_name" value="hero">
                <input type="hidden" name="image_fields[]" value="bg_image">

                <div class="form-grid-2">
                    <div>
                        <div class="form-group-admin">
                            <label class="form-label-admin">Texto pequeño superior (tag)</label>
                            <input type="text" name="fields[tag]" class="form-control-admin"
                                   value="<?= e($heroData['tag'] ?? '') ?>"
                                   placeholder="🌮 Restaurante Mexicano · Cartagena">
                        </div>
                        <div class="form-group-admin">
                            <label class="form-label-admin">Título principal (H1)</label>
                            <input type="text" name="fields[headline]" class="form-control-admin"
                                   value="<?= e($heroData['headline'] ?? '') ?>"
                                   placeholder="Sabores Auténticos">
                        </div>
                        <div class="form-group-admin">
                            <label class="form-label-admin">Palabra destacada (en cursiva y naranja)</label>
                            <input type="text" name="fields[headline_highlight]" class="form-control-admin"
                                   value="<?= e($heroData['headline_highlight'] ?? '') ?>"
                                   placeholder="de México">
                        </div>
                        <div class="form-group-admin">
                            <label class="form-label-admin">Subtítulo / Descripción</label>
                            <textarea name="fields[subtitle]" class="form-control-admin" rows="3"
                                      placeholder="Descripción breve del restaurante..."><?= e($heroData['subtitle'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group-admin">
                            <label class="form-label-admin">URL de Video de fondo (opcional)</label>
                            <input type="url" name="fields[video_url]" class="form-control-admin"
                                   value="<?= e($heroData['video_url'] ?? '') ?>"
                                   placeholder="https://...video.mp4">
                            <small style="color:var(--admin-muted)">Si hay video, tiene prioridad sobre la imagen. Formatos: MP4.</small>
                        </div>
                    </div>
                    <div>
                        <div class="form-group-admin">
                            <label class="form-label-admin">Imagen de fondo del hero</label>
                            <?php if (!empty($heroData['bg_image'])): ?>
                            <img src="<?= e(imgUrl($heroData['bg_image'])) ?>" alt="Hero actual"
                                 style="width:100%;height:180px;object-fit:cover;border-radius:8px;margin-bottom:10px;">
                            <?php endif; ?>
                            <input type="file" name="img_bg_image" class="form-control-admin"
                                   accept="image/*">
                        </div>
                        <div class="form-group-admin">
                            <label class="form-label-admin">Alt text de la imagen (SEO)</label>
                            <input type="text" name="fields[bg_image_alt]" class="form-control-admin"
                                   value="<?= e($heroData['bg_image_alt'] ?? '') ?>"
                                   placeholder="Fotografía del interior del restaurante">
                        </div>
                        <div class="form-group-admin">
                            <label class="form-label-admin">Años de experiencia (para el badge)</label>
                            <input type="number" name="fields[years]" class="form-control-admin"
                                   value="<?= e($heroData['years'] ?? '5') ?>" min="1" max="100">
                        </div>
                    </div>
                </div>

                <button type="submit" name="save_section" class="btn-admin-primary">💾 Guardar Hero</button>
            </form>
        </div>
    </div>

    <!-- SECCIÓN HISTORIA -->
    <div class="admin-card" style="margin-bottom:20px;">
        <div class="card-header">
            <h3 class="card-title">📖 Sección "Nuestra Historia"</h3>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <?= csrfField() ?>
                <input type="hidden" name="section_page" value="home">
                <input type="hidden" name="section_name" value="historia">
                <input type="hidden" name="image_fields[]" value="image">

                <div class="form-grid-2">
                    <div>
                        <div class="form-group-admin">
                            <label class="form-label-admin">Título de la sección</label>
                            <input type="text" name="fields[title]" class="form-control-admin"
                                   value="<?= e($historiaData['title'] ?? '') ?>"
                                   placeholder="De México al Caribe con Amor">
                        </div>
                        <div class="form-group-admin">
                            <label class="form-label-admin">Párrafo 1</label>
                            <textarea name="fields[text_1]" class="form-control-admin" rows="4"
                                      placeholder="Historia del restaurante..."><?= e($historiaData['text_1'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group-admin">
                            <label class="form-label-admin">Párrafo 2</label>
                            <textarea name="fields[text_2]" class="form-control-admin" rows="4"><?= e($historiaData['text_2'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group-admin">
                            <label class="form-label-admin">Párrafo 3 (opcional)</label>
                            <textarea name="fields[text_3]" class="form-control-admin" rows="3"><?= e($historiaData['text_3'] ?? '') ?></textarea>
                        </div>
                    </div>
                    <div>
                        <div class="form-group-admin">
                            <label class="form-label-admin">Imagen de la historia</label>
                            <?php if (!empty($historiaData['image'])): ?>
                            <img src="<?= e(imgUrl($historiaData['image'])) ?>" alt="Historia"
                                 style="width:100%;height:200px;object-fit:cover;border-radius:8px;margin-bottom:10px;">
                            <?php endif; ?>
                            <input type="file" name="img_image" class="form-control-admin" accept="image/*">
                        </div>
                        <div class="form-group-admin">
                            <label class="form-label-admin">Alt text de la imagen</label>
                            <input type="text" name="fields[image_alt]" class="form-control-admin"
                                   value="<?= e($historiaData['image_alt'] ?? '') ?>">
                        </div>
                        <div class="form-group-admin">
                            <label class="form-label-admin">Años de experiencia</label>
                            <input type="number" name="fields[years]" class="form-control-admin"
                                   value="<?= e($historiaData['years'] ?? '5') ?>">
                        </div>
                    </div>
                </div>

                <button type="submit" name="save_section" class="btn-admin-primary">💾 Guardar Historia</button>
            </form>
        </div>
    </div>

    <!-- TESTIMONIOS -->
    <div class="admin-card">
        <div class="card-header">
            <h3 class="card-title">⭐ Testimonios / Reseñas</h3>
        </div>
        <div class="card-body">
            <div class="admin-grid-2">
                <!-- Formulario testimonio -->
                <div>
                    <h4 style="margin-bottom:16px;"><?= $editTestimonio ? 'Editar Testimonio' : 'Agregar Testimonio' ?></h4>
                    <form method="POST" enctype="multipart/form-data">
                        <?= csrfField() ?>
                        <input type="hidden" name="t_id" value="<?= (int)($editTestimonio['id'] ?? 0) ?>">
                        <input type="hidden" name="current_avatar" value="<?= e($editTestimonio['avatar'] ?? '') ?>">

                        <div class="form-group-admin">
                            <label class="form-label-admin">Nombre *</label>
                            <input type="text" name="t_name" class="form-control-admin" required
                                   value="<?= e($editTestimonio['name'] ?? '') ?>" placeholder="María García">
                        </div>
                        <div class="form-group-admin">
                            <label class="form-label-admin">Texto de la reseña *</label>
                            <textarea name="t_text" class="form-control-admin" rows="4" required
                                      placeholder="Lo mejor que he probado..."><?= e($editTestimonio['text'] ?? '') ?></textarea>
                        </div>
                        <div class="form-grid-2">
                            <div class="form-group-admin">
                                <label class="form-label-admin">Origen / Ciudad</label>
                                <input type="text" name="t_origin" class="form-control-admin"
                                       value="<?= e($editTestimonio['origin'] ?? '') ?>" placeholder="Bogotá, Colombia">
                            </div>
                            <div class="form-group-admin">
                                <label class="form-label-admin">Calificación (1-5)</label>
                                <select name="t_rating" class="form-control-admin">
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <option value="<?= $i ?>" <?= ($editTestimonio['rating'] ?? 5) == $i ? 'selected' : '' ?>>
                                        <?= str_repeat('★', $i) . str_repeat('☆', 5-$i) ?>
                                    </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group-admin">
                            <label class="form-label-admin">Foto del cliente (opcional)</label>
                            <?php if (!empty($editTestimonio['avatar'])): ?>
                            <img src="<?= e(imgUrl($editTestimonio['avatar'])) ?>" alt="Avatar"
                                 style="width:50px;height:50px;border-radius:50%;object-fit:cover;margin-bottom:8px;">
                            <?php endif; ?>
                            <input type="file" name="t_avatar" class="form-control-admin" accept="image/*">
                        </div>
                        <div class="form-grid-2">
                            <div class="form-group-admin">
                                <label class="form-label-admin">Orden</label>
                                <input type="number" name="t_order" class="form-control-admin"
                                       min="0" value="<?= e($editTestimonio['sort_order'] ?? 0) ?>">
                            </div>
                            <label class="toggle-label" style="align-self:flex-end;margin-bottom:8px;">
                                <input type="checkbox" name="t_active" value="1"
                                       <?= ($editTestimonio['active'] ?? 1) ? 'checked' : '' ?>>
                                <span class="toggle-track"></span>
                                Visible
                            </label>
                        </div>
                        <button type="submit" name="save_testimonio" class="btn-admin-primary">
                            💾 Guardar Testimonio
                        </button>
                        <?php if ($editTestimonio): ?>
                        <a href="?page=home" class="btn-admin-outline" style="margin-left:8px;">Cancelar</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Lista de testimonios -->
                <div>
                    <?php if (empty($testimonios)): ?>
                    <p class="empty-state">No hay testimonios aún.</p>
                    <?php else: ?>
                    <?php foreach ($testimonios as $t): ?>
                    <div style="background:var(--admin-bg);border-radius:8px;padding:14px;margin-bottom:12px;display:flex;gap:12px;align-items:flex-start;">
                        <?php if (!empty($t['avatar'])): ?>
                        <img src="<?= e(imgUrl($t['avatar'])) ?>" alt="<?= e($t['name']) ?>"
                             style="width:40px;height:40px;border-radius:50%;object-fit:cover;flex-shrink:0;">
                        <?php else: ?>
                        <div style="width:40px;height:40px;border-radius:50%;background:var(--admin-primary);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;flex-shrink:0;">
                            <?= mb_strtoupper(mb_substr($t['name'], 0, 1)) ?>
                        </div>
                        <?php endif; ?>
                        <div style="flex:1;min-width:0;">
                            <strong style="font-size:.9rem;"><?= e($t['name']) ?></strong>
                            <span style="color:var(--admin-muted);font-size:.8rem;margin-left:6px;"><?= e($t['origin'] ?? '') ?></span>
                            <div style="color:#f59e0b;font-size:.9rem;"><?= str_repeat('★', (int)$t['rating']) ?></div>
                            <p style="font-size:.85rem;color:var(--admin-muted);margin:4px 0 0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                <?= e($t['text']) ?>
                            </p>
                        </div>
                        <div style="display:flex;gap:4px;flex-shrink:0;">
                            <a href="?page=home&edit_t=<?= e($t['id']) ?>" class="btn-admin-sm">✏️</a>
                            <a href="?page=home&delete_testimonio=<?= e($t['id']) ?>&<?= CSRF_TOKEN_NAME ?>=<?= e(csrfToken()) ?>"
                               class="btn-admin-sm btn-admin-sm-danger"
                               onclick="return confirm('¿Eliminar?')">🗑</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── Otras páginas: placeholder ────────────────────────────────── -->
<?php foreach (['menu','reservas','contacto'] as $pg): ?>
<div id="page-<?= $pg ?>" class="page-content <?= $activePage===$pg ? 'active':'' ?>">
    <div class="admin-card">
        <div class="card-header">
            <h3 class="card-title">📄 Página: <?= ucfirst($pg) ?></h3>
        </div>
        <div class="card-body">
            <p style="color:var(--admin-muted); margin-bottom:20px;">
                El contenido de esta página se gestiona desde las secciones de Configuración General y el Menú.
                Puedes editar los textos SEO en la sección <a href="<?= e(SITE_URL) ?>/admin/seo.php">SEO & Pixels</a>.
            </p>
            <a href="<?= e(SITE_URL) ?>/<?= $pg ?>" target="_blank" class="btn-admin-outline">
                👁 Ver página pública
            </a>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script>
function setActivePage(name, btn) {
    document.querySelectorAll('.page-content').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.admin-tab').forEach(b => b.classList.remove('active'));
    document.getElementById('page-' + name).classList.add('active');
    btn.classList.add('active');
}

// Activar según URL
const activeParam = new URLSearchParams(window.location.search).get('page');
if (activeParam) {
    const btn = document.querySelector(`.admin-tab[onclick*="${activeParam}"]`);
    if (btn) setActivePage(activeParam, btn);
}
</script>

<?php adminFooter(); ?>
