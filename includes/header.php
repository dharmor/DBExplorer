<?php
$pageTitle ??= 'DBExplorer';
$flashMessages = getFlash();
$memUsageFmt = formatBytes(memory_get_usage(true));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - DBExplorer</title>
    <link rel="stylesheet" href="css/colors.css">
    <link rel="stylesheet" href="css/format.css">
</head>
<body>
<header class="site-header">
    <div class="header-top">
        <div class="header-brand">
            <div class="logo-mark">DB</div>
            <h1><?= e($pageTitle) ?></h1>
        </div>
        <nav class="header-nav">
            <?php if (isAppLoggedIn()): ?>
                <a href="database_select.php">Connect</a>
                <?php if (isLoggedIn()): ?>
                    <a href="databases.php">Databases</a>
                    <a href="tables.php">Tables</a>
                    <a href="query.php">SQL</a>
                    <a href="documenter.php">Docs</a>
                <?php endif; ?>
                <a href="change_password.php">Password</a>
                <a href="about.php">About</a>
                <a href="logout.php">Logout</a>
            <?php endif; ?>
        </nav>
    </div>
    <div class="header-status">
        <span><span class="status-label">Memory:</span> <span class="status-value"><?= e($memUsageFmt) ?></span></span>
        <span><span class="status-label">User:</span> <span class="status-value"><?= e($_SESSION['app_user'] ?? 'Guest') ?></span></span>
    </div>
</header>
<main class="main-content">
<?php foreach ($flashMessages as $flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
<?php endforeach; ?>
