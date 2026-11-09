<?php
$roleLabels = ['admin' => 'Administrator', 'doctor' => 'Doctor', 'nurse' => 'Nurse', 'receptionist' => 'Reception'];
$navItems = [
    'dashboard' => ['Overview', '⌂', null],
    'patients' => ['Patients', '◉', 'patients.view'],
    'appointments' => ['Appointments', '▦', 'appointments.view'],
    'reminders' => ['Reminders', '◷', 'reminders.view'],
    'forum' => ['Team board', '✳', 'forum.view'],
    'messages' => ['Messages', '✉', 'messages.view'],
    'team' => ['Team access', '♙', 'staff.manage'],
    'activity' => ['Activity log', '↗', 'activity.view'],
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f7f8">
    <title><?= e($pageTitles[$page]) ?> · Careflow</title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="app-body">
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="index.php"><span class="brand-mark">+</span><span>careflow<span class="brand-period">.</span></span></a>
        <div class="workspace-label">WORKSPACE</div>
        <nav class="main-nav" aria-label="Main navigation">
            <?php foreach ($navItems as $key => [$label, $icon, $permission]): ?>
                <?php if ($permission === null || can($permission)): ?>
                    <a class="nav-link <?= $page === $key ? 'active' : '' ?>" href="index.php?page=<?= e($key) ?>"><span class="nav-icon"><?= e($icon) ?></span><span><?= e($label) ?></span><?php if ($key === 'appointments' && $counts['appointments'] > 0): ?><span class="nav-count"><?= $counts['appointments'] ?></span><?php endif; ?><?php if ($key === 'messages' && $unreadMessages > 0): ?><span class="nav-count"><?= $unreadMessages ?></span><?php endif; ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-spacer"></div>
        <div class="privacy-card"><span class="privacy-icon">✓</span><div><strong>Care, kept private.</strong><p>Only your team can access this workspace.</p></div></div>
        <div class="sidebar-user">
            <div class="avatar"><?= e(mb_strtoupper(mb_substr($user['name'], 0, 1))) ?></div>
            <div class="user-meta"><strong><?= e($user['name']) ?></strong><span><?= e($roleLabels[$user['role']] ?? $user['role']) ?></span></div>
            <a class="signout" href="logout.php" aria-label="Sign out" title="Sign out">↗</a>
        </div>
    </aside>
    <main class="main-content">
        <header class="topbar">
            <button type="button" class="mobile-menu" id="menuToggle" aria-label="Open navigation">☰</button>
            <div class="breadcrumb"><span>Careflow</span><span class="crumb-separator">/</span><strong><?= e($pageTitles[$page]) ?></strong></div>
            <div class="topbar-right"><span class="today-chip"><span class="status-dot"></span> Workspace active</span><span class="top-date"><?= e(date('D, M j')) ?></span></div>
        </header>
        <section class="page-content">
            <div class="page-heading">
                <div><p class="eyebrow"><?= e(date('l, F j')) ?></p><h1><?= e($pageTitles[$page]) ?></h1><p class="page-description"><?= e($pageDescriptions[$page]) ?></p></div>
                <?php if ($page === 'patients' && can('patients.edit')): ?><button class="button button-primary" type="button" data-open-dialog="patientDialog"><span>＋</span> Add patient</button><?php endif; ?>
                <?php if ($page === 'appointments' && can('appointments.edit')): ?><button class="button button-primary" type="button" data-open-dialog="appointmentDialog"><span>＋</span> New appointment</button><?php endif; ?>
                <?php if ($page === 'reminders' && can('reminders.edit')): ?><button class="button button-primary" type="button" data-open-dialog="reminderDialog"><span>＋</span> Add reminder</button><?php endif; ?>
                <?php if ($page === 'team' && can('staff.manage')): ?><button class="button button-primary" type="button" data-open-dialog="userDialog"><span>＋</span> Add team member</button><?php endif; ?>
            </div>
            <?php if ($flashMessage): ?><div class="notice notice-<?= e($flashMessage['type']) ?>" role="status"><?= e($flashMessage['message']) ?><button type="button" class="notice-close" aria-label="Dismiss">×</button></div><?php endif; ?>
