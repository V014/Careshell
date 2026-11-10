<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

if (current_user()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $statement = database()->prepare('SELECT id, name, email, password_hash, role, status FROM users WHERE email = ? LIMIT 1');
    $statement->execute([$email]);
    $user = $statement->fetch();
    if ($user && $user['status'] === 'active' && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        unset($user['password_hash']);
        $_SESSION['user'] = $user;
        audit('signed in', 'account', (int) $user['id']);
        header('Location: index.php');
        exit;
    }
    $error = 'That email and password combination was not recognized.';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in · Careshell</title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="auth-page">
<main class="auth-card">
    <a class="brand auth-brand" href="login.php"><span class="brand-mark">+</span><span>careshell<span class="brand-period">.</span></span></a>
    <div class="auth-heading"><p class="eyebrow">YOUR CLINIC, IN SYNC</p><h1>Welcome back.</h1><p>Sign in to your care team workspace.</p></div>
    <?php if ($error !== ''): ?><div class="notice notice-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="stack-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label>Work email<input type="email" name="email" autocomplete="username" required autofocus value="<?= e($_POST['email'] ?? '') ?>"></label>
        <label>Password<input type="password" name="password" autocomplete="current-password" required></label>
        <button class="button button-primary button-wide" type="submit">Sign in <span>→</span></button>
    </form>
    <p class="setup-note">Secure access for authorized care team members.</p>
</main>
</body>
</html>
