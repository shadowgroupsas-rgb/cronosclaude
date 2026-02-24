<?php
/**
 * Autenticación del panel de administración
 * Protección con sesiones PHP, CSRF, y rate limiting
 */

// Bootstrap si no está cargado
if (!defined('DB_PREFIX')) {
    require_once dirname(__DIR__, 2) . '/config.php';
}
if (!function_exists('db')) {
    require_once dirname(__DIR__, 2) . '/includes/db.php';
}
if (!function_exists('getSetting')) {
    require_once dirname(__DIR__, 2) . '/includes/functions.php';
}

// ── Inicializar sesión ─────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/admin',
        'secure'   => SESSION_SECURE,
        'httponly' => SESSION_HTTPONLY,
        'samesite' => 'Strict',
    ]);
    session_start();
}

/**
 * Verifica si el usuario está autenticado
 * Redirige al login si no lo está
 */
function requireAuth(): void {
    if (!isAuthenticated()) {
        $redirect = urlencode($_SERVER['REQUEST_URI'] ?? '/admin/');
        header("Location: " . SITE_URL . "/admin/login.php?redirect={$redirect}");
        exit;
    }

    // Verificar que la sesión no haya expirado
    if (isset($_SESSION['last_activity'])) {
        $elapsed = time() - $_SESSION['last_activity'];
        if ($elapsed > SESSION_LIFETIME) {
            logoutUser();
            header("Location: " . SITE_URL . "/admin/login.php?expired=1");
            exit;
        }
    }
    $_SESSION['last_activity'] = time();
}

/**
 * Retorna true si el usuario está autenticado
 */
function isAuthenticated(): bool {
    return !empty($_SESSION['admin_id']) && !empty($_SESSION['admin_username']);
}

/**
 * Intenta autenticar un usuario
 * Retorna array con éxito/error
 */
function loginUser(string $username, string $password, string $ip): array {
    // Verificar rate limiting
    if (isRateLimited($ip)) {
        $minutes = LOGIN_BLOCK_MINUTES;
        return ['success' => false, 'error' => "Demasiados intentos fallidos. Espera {$minutes} minutos."];
    }

    // Buscar usuario
    $user = dbQueryOne(
        'SELECT * FROM ' . DB_PREFIX . 'admin_users WHERE username = ? OR email = ?',
        [$username, $username]
    );

    if (!$user || !password_verify($password, $user['password_hash'])) {
        recordLoginAttempt($ip);
        return ['success' => false, 'error' => 'Usuario o contraseña incorrectos.'];
    }

    // Login exitoso — limpiar intentos fallidos
    clearLoginAttempts($ip);

    // Regenerar ID de sesión (previene fixation)
    session_regenerate_id(true);

    // Guardar datos de sesión
    $_SESSION['admin_id']       = $user['id'];
    $_SESSION['admin_username'] = $user['username'];
    $_SESSION['admin_email']    = $user['email'];
    $_SESSION['last_activity']  = time();
    $_SESSION['force_pw_change']= (bool)$user['force_password_change'];

    // Actualizar último login
    dbExecute(
        'UPDATE ' . DB_PREFIX . 'admin_users SET last_login = NOW() WHERE id = ?',
        [$user['id']]
    );

    return ['success' => true, 'force_pw_change' => (bool)$user['force_password_change']];
}

/**
 * Cierra la sesión del usuario
 */
function logoutUser(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

/**
 * Verifica el rate limiting de login por IP
 */
function isRateLimited(string $ip): bool {
    $windowStart = date('Y-m-d H:i:s', time() - (LOGIN_BLOCK_MINUTES * 60));
    $count = dbQueryOne(
        'SELECT COUNT(*) AS cnt FROM ' . DB_PREFIX . 'login_attempts
         WHERE ip_address = ? AND attempted_at > ?',
        [$ip, $windowStart]
    );
    return ($count['cnt'] ?? 0) >= LOGIN_MAX_ATTEMPTS;
}

/**
 * Registra un intento de login fallido
 */
function recordLoginAttempt(string $ip): void {
    dbExecute(
        'INSERT INTO ' . DB_PREFIX . 'login_attempts (ip_address) VALUES (?)',
        [$ip]
    );
}

/**
 * Limpia intentos de login para una IP
 */
function clearLoginAttempts(string $ip): void {
    dbExecute(
        'DELETE FROM ' . DB_PREFIX . 'login_attempts WHERE ip_address = ?',
        [$ip]
    );
}

/**
 * Cambia la contraseña del admin actual
 */
function changeAdminPassword(string $newPassword): bool {
    $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
    $affected = dbExecute(
        'UPDATE ' . DB_PREFIX . 'admin_users
         SET password_hash = ?, force_password_change = 0 WHERE id = ?',
        [$hash, $_SESSION['admin_id']]
    );
    if ($affected > 0) {
        $_SESSION['force_pw_change'] = false;
    }
    return $affected > 0;
}

/**
 * Obtiene el usuario admin actual de la base de datos
 */
function getCurrentAdmin(): ?array {
    if (empty($_SESSION['admin_id'])) return null;
    return dbQueryOne(
        'SELECT id, username, email, last_login FROM ' . DB_PREFIX . 'admin_users WHERE id = ?',
        [$_SESSION['admin_id']]
    );
}
