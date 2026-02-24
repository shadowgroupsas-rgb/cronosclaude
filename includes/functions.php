<?php
/**
 * Funciones de utilidad general para La Chingada Restaurant
 */

// ── Configuración del sitio ────────────────────────────────────────────────

/**
 * Obtiene un valor de configuración de la base de datos
 */
function getSetting(string $key, string $default = ''): string {
    static $cache = [];

    if (!isset($cache[$key])) {
        $row = dbQueryOne(
            'SELECT setting_value FROM ' . DB_PREFIX . 'settings WHERE setting_key = ?',
            [$key]
        );
        $cache[$key] = $row ? (string)$row['setting_value'] : $default;
    }
    return $cache[$key];
}

/**
 * Guarda o actualiza un valor de configuración
 */
function setSetting(string $key, string $value): void {
    dbExecute(
        'INSERT INTO ' . DB_PREFIX . 'settings (setting_key, setting_value)
         VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)',
        [$key, $value]
    );
}

/**
 * Obtiene múltiples settings de una vez (más eficiente)
 */
function getSettings(array $keys): array {
    if (empty($keys)) return [];

    $placeholders = implode(',', array_fill(0, count($keys), '?'));
    $rows = dbQuery(
        'SELECT setting_key, setting_value FROM ' . DB_PREFIX . 'settings
         WHERE setting_key IN (' . $placeholders . ')',
        $keys
    );

    $result = array_fill_keys($keys, '');
    foreach ($rows as $row) {
        $result[$row['setting_key']] = $row['setting_value'];
    }
    return $result;
}

// ── Seguridad ──────────────────────────────────────────────────────────────

/**
 * Escapa output HTML para prevenir XSS
 */
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Genera un token CSRF y lo guarda en sesión
 */
function csrfToken(): string {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Verifica el token CSRF de un formulario POST
 */
function verifyCsrf(): bool {
    $token = $_POST[CSRF_TOKEN_NAME] ?? '';
    $sessionToken = $_SESSION[CSRF_TOKEN_NAME] ?? '';

    if (empty($token) || empty($sessionToken)) {
        return false;
    }
    return hash_equals($sessionToken, $token);
}

/**
 * Retorna el campo hidden del CSRF para formularios
 */
function csrfField(): string {
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . e(csrfToken()) . '">';
}

/**
 * Verifica el CSRF y termina el script si es inválido
 */
function requireCsrf(): void {
    if (!verifyCsrf()) {
        http_response_code(403);
        die(json_encode(['error' => 'Token CSRF inválido']));
    }
}

// ── Sanitización ───────────────────────────────────────────────────────────

/**
 * Sanitiza un string de texto plano
 */
function sanitizeStr(string $str, int $maxLen = 255): string {
    return mb_substr(trim(strip_tags($str)), 0, $maxLen);
}

/**
 * Sanitiza un email
 */
function sanitizeEmail(string $email): string {
    return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
}

/**
 * Valida si un email es válido
 */
function isValidEmail(string $email): bool {
    return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Genera un slug URL-friendly desde un texto
 */
function makeSlug(string $str): string {
    // Reemplazar caracteres especiales del español
    $str = mb_strtolower($str, 'UTF-8');
    $str = str_replace(['á','é','í','ó','ú','ü','ñ'], ['a','e','i','o','u','u','n'], $str);
    $str = preg_replace('/[^a-z0-9\s-]/', '', $str);
    $str = preg_replace('/[\s-]+/', '-', $str);
    return trim($str, '-');
}

// ── Imágenes ───────────────────────────────────────────────────────────────

/**
 * Sube y procesa una imagen
 * Retorna el nombre del archivo guardado o false en error
 */
function uploadImage(array $file, string $subfolder = ''): string|false {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    // Validar tamaño
    if ($file['size'] > IMG_MAX_SIZE) {
        return false;
    }

    // Validar tipo MIME real (no confiar en el del cliente)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($mimeType, $allowedTypes)) {
        return false;
    }

    // Extensión según MIME real
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];
    $ext = $extensions[$mimeType];

    // Nombre único y aleatorio
    $filename = bin2hex(random_bytes(16)) . '.' . $ext;

    // Ruta de destino
    $destDir = UPLOADS_PATH . ($subfolder ? '/' . trim($subfolder, '/') : '');
    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }

    $destPath = $destDir . '/' . $filename;

    // Procesar y optimizar imagen con GD
    if (extension_loaded('gd')) {
        $optimized = optimizeImage($file['tmp_name'], $destPath, $mimeType);
        if (!$optimized) {
            return false;
        }
    } else {
        // Fallback sin GD: mover directamente
        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            return false;
        }
    }

    return ($subfolder ? trim($subfolder, '/') . '/' : '') . $filename;
}

/**
 * Optimiza una imagen con GD Library
 */
function optimizeImage(string $src, string $dest, string $mimeType): bool {
    // Crear imagen fuente según tipo
    $image = match($mimeType) {
        'image/jpeg' => @imagecreatefromjpeg($src),
        'image/png'  => @imagecreatefrompng($src),
        'image/gif'  => @imagecreatefromgif($src),
        'image/webp' => @imagecreatefromwebp($src),
        default      => false
    };

    if (!$image) return false;

    // Obtener dimensiones originales
    $origW = imagesx($image);
    $origH = imagesy($image);

    // Redimensionar si excede el máximo
    if ($origW > IMG_MAX_WIDTH || $origH > IMG_MAX_HEIGHT) {
        $ratio = min(IMG_MAX_WIDTH / $origW, IMG_MAX_HEIGHT / $origH);
        $newW  = (int)($origW * $ratio);
        $newH  = (int)($origH * $ratio);

        $resized = imagecreatetruecolor($newW, $newH);

        // Preservar transparencia para PNG
        if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
            imagecolortransparent($resized, imagecolorallocatealpha($resized, 0, 0, 0, 127));
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
        }

        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        imagedestroy($image);
        $image = $resized;
    }

    // Guardar imagen optimizada
    $success = match($mimeType) {
        'image/jpeg' => imagejpeg($image, $dest, IMG_QUALITY),
        'image/png'  => imagepng($image, $dest, 7),
        'image/gif'  => imagegif($image, $dest),
        'image/webp' => imagewebp($image, $dest, IMG_QUALITY),
        default      => false
    };

    imagedestroy($image);
    return $success;
}

/**
 * Elimina un archivo de imagen del sistema
 */
function deleteImage(string $filename): bool {
    if (empty($filename)) return false;
    $path = UPLOADS_PATH . '/' . $filename;
    if (file_exists($path) && is_file($path)) {
        return unlink($path);
    }
    return false;
}

/**
 * Retorna la URL completa de una imagen subida
 */
function imgUrl(string $filename, string $default = ''): string {
    if (empty($filename)) {
        return $default ?: SITE_URL . '/assets/images/placeholder.jpg';
    }
    return UPLOADS_URL . '/' . ltrim($filename, '/');
}

// ── Email ──────────────────────────────────────────────────────────────────

/**
 * Envía un email usando mail() nativo o PHPMailer si está disponible
 */
function sendEmail(string $to, string $subject, string $body, string $replyTo = ''): bool {
    $from     = MAIL_FROM;
    $fromName = MAIL_FROM_NAME;

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: {$fromName} <{$from}>\r\n";
    if ($replyTo) {
        $headers .= "Reply-To: {$replyTo}\r\n";
    }
    $headers .= "X-Mailer: PHP/" . phpversion();

    return mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);
}

/**
 * Template base para emails HTML
 */
function emailTemplate(string $title, string $content): string {
    $siteName = getSetting('site_name', 'La Chingada');
    $siteUrl  = SITE_URL;

    return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$title}</title>
<style>
  body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 0; }
  .container { max-width: 600px; margin: 20px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
  .header { background: linear-gradient(135deg, #C0392B, #E67E22); padding: 30px; text-align: center; }
  .header h1 { color: #fff; margin: 0; font-size: 24px; letter-spacing: 2px; }
  .header p { color: rgba(255,255,255,0.9); margin: 5px 0 0; font-size: 14px; }
  .body { padding: 30px; color: #333; line-height: 1.6; }
  .body h2 { color: #C0392B; border-bottom: 2px solid #E67E22; padding-bottom: 10px; }
  .info-box { background: #FAF0E6; border-left: 4px solid #E67E22; padding: 15px 20px; margin: 20px 0; border-radius: 0 4px 4px 0; }
  .info-box p { margin: 5px 0; }
  .info-box strong { color: #C0392B; }
  .btn { display: inline-block; background: #C0392B; color: #fff !important; padding: 12px 30px; text-decoration: none; border-radius: 4px; margin: 15px 0; font-weight: bold; }
  .footer { background: #2C1810; color: #aaa; padding: 20px; text-align: center; font-size: 12px; }
  .footer a { color: #E67E22; text-decoration: none; }
</style>
</head>
<body>
<div class="container">
  <div class="header">
    <h1>🌮 {$siteName}</h1>
    <p>Sabores auténticos de México en Cartagena</p>
  </div>
  <div class="body">
    {$content}
  </div>
  <div class="footer">
    <p>© {$siteName} · Cartagena, Colombia</p>
    <p><a href="{$siteUrl}">{$siteUrl}</a></p>
    <p style="font-size:11px;color:#666;">Este es un mensaje automático, por favor no responder directamente a este correo.</p>
  </div>
</div>
</body>
</html>
HTML;
}

// ── Formato ────────────────────────────────────────────────────────────────

/**
 * Formatea un precio en pesos colombianos
 */
function formatPrice(float $price): string {
    return '$' . number_format($price, 0, ',', '.');
}

/**
 * Formatea una fecha en español
 */
function formatDate(string $date): string {
    $ts = strtotime($date);
    $dias  = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
    $meses = ['enero','febrero','marzo','abril','mayo','junio',
              'julio','agosto','septiembre','octubre','noviembre','diciembre'];

    return $dias[date('w', $ts)] . ', ' . date('j', $ts) . ' de ' .
           $meses[date('n', $ts) - 1] . ' de ' . date('Y', $ts);
}

/**
 * Formatea una hora (HH:MM)
 */
function formatTime(string $time): string {
    return date('g:i A', strtotime($time));
}

// ── Paginación ─────────────────────────────────────────────────────────────

/**
 * Genera datos de paginación
 */
function paginate(int $total, int $perPage, int $currentPage): array {
    $totalPages = max(1, (int)ceil($total / $perPage));
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;

    return [
        'total'        => $total,
        'per_page'     => $perPage,
        'current_page' => $currentPage,
        'total_pages'  => $totalPages,
        'offset'       => $offset,
        'has_prev'     => $currentPage > 1,
        'has_next'     => $currentPage < $totalPages,
        'prev_page'    => $currentPage - 1,
        'next_page'    => $currentPage + 1,
    ];
}

// ── Respuestas JSON ────────────────────────────────────────────────────────

/**
 * Envía una respuesta JSON y termina el script
 */
function jsonResponse(array $data, int $statusCode = 200): never {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Respuesta JSON de éxito
 */
function jsonSuccess(array $data = [], string $message = 'OK'): never {
    jsonResponse(['success' => true, 'message' => $message] + $data);
}

/**
 * Respuesta JSON de error
 */
function jsonError(string $message, int $code = 400): never {
    jsonResponse(['success' => false, 'error' => $message], $code);
}

// ── Flash messages ─────────────────────────────────────────────────────────

/**
 * Establece un mensaje flash en sesión
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/**
 * Obtiene y limpia los mensajes flash
 */
function getFlash(): array {
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/**
 * Muestra los mensajes flash como HTML
 */
function showFlash(): string {
    $messages = getFlash();
    if (empty($messages)) return '';

    $html = '';
    foreach ($messages as $msg) {
        $type = e($msg['type']);
        $text = e($msg['message']);
        $html .= "<div class=\"alert alert-{$type}\" role=\"alert\">{$text}</div>";
    }
    return $html;
}

// ── Inicialización ─────────────────────────────────────────────────────────

/**
 * Inicializa la sesión con configuración segura
 */
function initSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'secure'   => SESSION_SECURE,
            'httponly' => SESSION_HTTPONLY,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}
