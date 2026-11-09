<?php
declare(strict_types=1);

session_start();
$config = require __DIR__ . '/config.php';
$error = '';
$complete = false;
if (empty($_SESSION['setup_csrf'])) {
    $_SESSION['setup_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');
    if ($submittedToken === '' || !hash_equals($_SESSION['setup_csrf'], $submittedToken)) {
        http_response_code(419);
        exit('Your setup session expired. Reload the page and try again.');
    }

    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $confirmation = (string) ($_POST['password_confirmation'] ?? '');

    if (strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a name and a valid email address.';
    } elseif (strlen($password) < 12) {
        $error = 'Choose a password with at least 12 characters.';
    } elseif ($password !== $confirmation) {
        $error = 'The passwords do not match.';
    } else {
        try {
            $server = new PDO(
                sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $config['host'], $config['port']),
                $config['username'],
                $config['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $databaseName = str_replace('`', '``', $config['database']);
            $server->exec("CREATE DATABASE IF NOT EXISTS `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo = new PDO(
                sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $config['host'], $config['port'], $config['database']),
                $config['username'],
                $config['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
            );
            $schema = file_get_contents(__DIR__ . '/database.sql');
            if ($schema === false) {
                throw new RuntimeException('Could not read database.sql.');
            }
            foreach (array_filter(array_map('trim', explode(';', $schema))) as $statement) {
                $pdo->exec($statement);
            }
            if ((int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0) {
                throw new RuntimeException('This database already has users. Sign in instead.');
            }
            $statement = $pdo->prepare(
                'INSERT INTO users (name, email, password_hash, role, status) VALUES (?, ?, ?, ?, ?)'
            );
            $statement->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), 'admin', 'active']);
            $userId = (int) $pdo->lastInsertId();
            $pdo->prepare(
                'INSERT INTO activity_logs (user_id, user_name, action, entity, details) VALUES (?, ?, ?, ?, ?)'
            )->execute([$userId, $name, 'created', 'account', 'Initial administrator setup']);
            $complete = true;
        } catch (Throwable $exception) {
            $error = $exception->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Set up Careflow</title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="auth-page">
<main class="auth-card">
    <a class="brand auth-brand" href="install.php"><span class="brand-mark">+</span><span>careflow<span class="brand-period">.</span></span></a>
    <?php if ($complete): ?>
        <div class="auth-heading"><p class="eyebrow">YOU'RE ALL SET</p><h1>Your workspace is ready.</h1><p>Sign in with the administrator account you just created.</p></div>
        <a class="button button-primary button-wide" href="login.php">Go to sign in <span>→</span></a>
    <?php else: ?>
        <div class="auth-heading"><p class="eyebrow">FIRST-TIME SETUP</p><h1>Create your workspace</h1><p>Connect to MySQL and create the first administrator account.</p></div>
        <?php if ($error !== ''): ?><div class="notice notice-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        <form method="post" class="stack-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['setup_csrf'], ENT_QUOTES, 'UTF-8') ?>">
            <label>Full name<input name="name" autocomplete="name" required minlength="2" value="<?= htmlspecialchars((string) ($_POST['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
            <label>Work email<input type="email" name="email" autocomplete="email" required value="<?= htmlspecialchars((string) ($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
            <label>Password<input type="password" name="password" autocomplete="new-password" required minlength="12"><small>Use at least 12 characters.</small></label>
            <label>Confirm password<input type="password" name="password_confirmation" autocomplete="new-password" required minlength="12"></label>
            <button class="button button-primary button-wide" type="submit">Create workspace <span>→</span></button>
        </form>
        <p class="setup-note">Database: <?= htmlspecialchars($config['database'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($config['host'], ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
</main>
</body>
</html>
