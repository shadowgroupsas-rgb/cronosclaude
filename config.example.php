<?php
/**
 * Archivo de configuración de ejemplo para La Chingada Restaurant
 * Copia este archivo como config.php y llena con tus datos reales
 * NUNCA subas config.php a un repositorio público
 */

// ── Base de datos ──────────────────────────────────────────────────────────
define('DB_HOST',   'localhost');
define('DB_NAME',   'nombre_base_de_datos');
define('DB_USER',   'usuario_db');
define('DB_PASS',   'contraseña_db');
define('DB_PREFIX', 'lc_');      // Prefijo de tablas
define('DB_CHARSET','utf8mb4');

// ── URLs y rutas del sitio ─────────────────────────────────────────────────
define('SITE_URL',      'https://tudominio.com');   // Sin barra final
define('SITE_PATH',     __DIR__);                    // Ruta absoluta raíz
define('UPLOADS_PATH',  SITE_PATH  . '/uploads');    // Ruta de uploads
define('UPLOADS_URL',   SITE_URL   . '/uploads');    // URL de uploads
define('ADMIN_PATH',    SITE_PATH  . '/admin');      // Ruta admin
define('INCLUDES_PATH', SITE_PATH  . '/includes');   // Ruta includes

// ── Configuración de sesiones ──────────────────────────────────────────────
define('SESSION_NAME',     'lc_session');
define('SESSION_LIFETIME', 7200);   // 2 horas en segundos
define('SESSION_SECURE',   false);  // true si usas HTTPS
define('SESSION_HTTPONLY',  true);

// ── Seguridad ──────────────────────────────────────────────────────────────
define('CSRF_TOKEN_NAME', 'lc_csrf_token');
define('LOGIN_MAX_ATTEMPTS', 5);        // Máximo intentos de login
define('LOGIN_BLOCK_MINUTES', 15);      // Minutos de bloqueo tras exceder intentos

// ── Imágenes ───────────────────────────────────────────────────────────────
define('IMG_MAX_SIZE',   5242880);  // 5MB en bytes
define('IMG_MAX_WIDTH',  2000);     // Ancho máximo en px
define('IMG_MAX_HEIGHT', 2000);     // Alto máximo en px
define('IMG_QUALITY',    85);       // Calidad JPEG (0-100)

// ── Email ──────────────────────────────────────────────────────────────────
define('MAIL_FROM',    'noreply@tudominio.com');
define('MAIL_FROM_NAME', 'La Chingada Restaurant');
// define('MAIL_HOST',   'smtp.gmail.com');  // Descomenta para SMTP
// define('MAIL_PORT',   587);
// define('MAIL_USER',   'tu@gmail.com');
// define('MAIL_PASS',   'app_password');

// ── Timezone ───────────────────────────────────────────────────────────────
define('SITE_TIMEZONE', 'America/Bogota');

// ── Modo debug (desactivar en producción) ─────────────────────────────────
define('DEBUG_MODE', false);
