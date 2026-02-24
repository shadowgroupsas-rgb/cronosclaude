<?php
require_once dirname(__DIR__) . '/config.php';
require_once INCLUDES_PATH . '/db.php';
require_once INCLUDES_PATH . '/functions.php';
require_once __DIR__ . '/includes/auth.php';

logoutUser();
header('Location: ' . SITE_URL . '/admin/login.php?logout=1');
exit;
