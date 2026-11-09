<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');

require_once __DIR__ . '/functions.php';

try {
    $pdo = database();
    $installed = (bool) $pdo->query("SHOW TABLES LIKE 'users'")->fetchColumn();
} catch (Throwable $exception) {
    http_response_code(500);
    exit('Database connection failed. Check config.php and ensure MySQL is running.');
}

if (!$installed && basename((string) ($_SERVER['SCRIPT_NAME'] ?? '')) !== 'install.php') {
    header('Location: install.php');
    exit;
}

if ($installed && basename((string) ($_SERVER['SCRIPT_NAME'] ?? '')) !== 'install.php') {
    require_login();
}
