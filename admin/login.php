<?php
/**
 * Panel de Administración — Login
 */

require_once dirname(__DIR__) . '/config.php';
require_once INCLUDES_PATH . '/db.php';
require_once INCLUDES_PATH . '/functions.php';
require_once __DIR__ . '/includes/auth.php';

date_default_timezone_set(SITE_TIMEZONE);

// Si ya está autenticado, redirigir al dashboard
if (isAuthenticated()) {
    header('Location: ' . SITE_URL . '/admin/');
    exit;
}

$error   = '';
$success = '';
$ip      = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// Mensajes de URL
if (isset($_GET['expired'])) {
    $error = 'Tu sesión expiró. Por favor inicia sesión nuevamente.';
}
if (isset($_GET['logout'])) {
    $success = 'Sesión cerrada correctamente.';
}

// Procesar formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $error = 'Token de seguridad inválido. Recarga la página.';
    } else {
        $username = sanitizeStr($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Por favor completa todos los campos.';
        } else {
            $result = loginUser($username, $password, $ip);

            if ($result['success']) {
                $redirect = filter_var($_GET['redirect'] ?? '', FILTER_SANITIZE_URL);
                // Validar que el redirect sea interno
                if (!$redirect || !str_starts_with($redirect, '/admin')) {
                    $redirect = SITE_URL . '/admin/';
                } else {
                    $redirect = SITE_URL . $redirect;
                }
                header("Location: {$redirect}");
                exit;
            } else {
                $error = $result['error'];
            }
        }
    }
}

$siteName = getSetting('site_name', 'La Chingada');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión — Admin <?= e($siteName) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(SITE_URL) ?>/assets/css/admin.css">
</head>
<body class="login-body">

<div class="login-wrapper">
    <div class="login-card">

        <!-- Logo / Encabezado -->
        <div class="login-header">
            <div class="login-logo">🌮</div>
            <h1 class="login-title"><?= e($siteName) ?></h1>
            <p class="login-subtitle">Panel de Administración</p>
        </div>

        <!-- Alertas -->
        <?php if ($error): ?>
        <div class="alert-admin alert-admin-danger" role="alert">
            <?= e($error) ?>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="alert-admin alert-admin-success" role="alert">
            <?= e($success) ?>
        </div>
        <?php endif; ?>

        <!-- Formulario -->
        <form method="POST" action="" novalidate>
            <?= csrfField() ?>

            <div class="form-group-admin">
                <label for="username" class="form-label-admin">
                    Usuario o Email
                </label>
                <input type="text"
                       id="username"
                       name="username"
                       class="form-control-admin"
                       placeholder="admin"
                       required
                       autocomplete="username"
                       autofocus
                       value="<?= e($_POST['username'] ?? '') ?>">
            </div>

            <div class="form-group-admin">
                <label for="password" class="form-label-admin">
                    Contraseña
                </label>
                <div class="password-wrapper">
                    <input type="password"
                           id="password"
                           name="password"
                           class="form-control-admin"
                           placeholder="••••••••"
                           required
                           autocomplete="current-password">
                    <button type="button"
                            class="password-toggle"
                            aria-label="Mostrar/ocultar contraseña"
                            onclick="togglePassword('password', this)">
                        👁
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-admin-primary" style="width:100%; margin-top:8px;">
                Iniciar Sesión
            </button>
        </form>

        <div class="login-footer">
            <a href="<?= e(SITE_URL) ?>/" target="_blank" style="color:rgba(255,255,255,.5); font-size:.85rem;">
                ← Ver sitio público
            </a>
        </div>

    </div>
</div>

<script>
function togglePassword(fieldId, btn) {
    const field = document.getElementById(fieldId);
    if (field.type === 'password') {
        field.type = 'text';
        btn.textContent = '🙈';
    } else {
        field.type = 'password';
        btn.textContent = '👁';
    }
}
</script>
</body>
</html>
