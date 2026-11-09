<?php
declare(strict_types=1);

function database(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = require dirname(__DIR__) . '/config.php';
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $config['host'],
        $config['port'],
        $config['database']
    );
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!current_user()) {
        header('Location: login.php');
        exit;
    }
}

function can(string $permission): bool
{
    $role = current_user()['role'] ?? '';
    $permissions = [
        'admin' => ['patients.view', 'patients.edit', 'appointments.view', 'appointments.edit', 'reminders.view', 'reminders.edit', 'staff.manage', 'activity.view'],
        'doctor' => ['patients.view', 'patients.edit', 'appointments.view', 'appointments.edit', 'reminders.view', 'reminders.edit'],
        'nurse' => ['patients.view', 'patients.edit', 'appointments.view', 'reminders.view', 'reminders.edit'],
        'receptionist' => ['patients.view', 'patients.edit', 'appointments.view', 'appointments.edit', 'reminders.view', 'reminders.edit'],
    ];
    return in_array($permission, $permissions[$role] ?? [], true);
}

function require_permission(string $permission): void
{
    if (!can($permission)) {
        http_response_code(403);
        exit('You do not have permission to access this page.');
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $submitted = (string) ($_POST['csrf_token'] ?? '');
    if ($submitted === '' || !hash_equals(csrf_token(), $submitted)) {
        http_response_code(419);
        exit('Your session expired. Refresh the page and try again.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function take_flash(): ?array
{
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $message;
}

function audit(string $action, string $entity, ?int $entityId = null, ?string $details = null): void
{
    $user = current_user();
    database()->prepare(
        'INSERT INTO activity_logs (user_id, user_name, action, entity, entity_id, details, ip_address)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    )->execute([
        $user['id'] ?? null,
        $user['name'] ?? 'System',
        $action,
        $entity,
        $entityId,
        $details,
        substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
    ]);
}

function redirect(string $page): never
{
    header('Location: index.php?page=' . rawurlencode($page));
    exit;
}

function post_string(string $key, int $maxLength = 5000): string
{
    return mb_substr(trim((string) ($_POST[$key] ?? '')), 0, $maxLength);
}

function valid_date(string $value): bool
{
    if ($value === '') {
        return true;
    }
    $date = DateTime::createFromFormat('Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
}

function format_date(?string $value, string $format = 'M j, Y'): string
{
    if (!$value) {
        return '—';
    }
    $time = strtotime($value);
    return $time === false ? e($value) : date($format, $time);
}
