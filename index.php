<?php
/**
 * @file   index.php
 * @brief  App-level authentication — username & password.
 */
require_once __DIR__ . '/includes/session.php';

// Already logged in? Go to DB connection page
if (isAppLoggedIn()) {
    header('Location: database_select.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username and password are required.';
    } elseif (verifyAppCredentials($username, $password)) {
        $_SESSION['app_authenticated'] = true;
        $_SESSION['app_user'] = $username;
        header('Location: database_select.php');
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}

$memUsageFmt = formatBytes(memory_get_usage(true));
$memPeakFmt  = formatBytes(memory_get_peak_usage(true));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DB Explorer — Sign In</title>
    <link rel="stylesheet" href="css/colors.css">
    <link rel="stylesheet" href="css/format.css">
</head>
<body>

<header class="site-header">
    <div class="header-top">
        <div class="header-brand">
            <img src="assets/logo.png" alt="DB Explorer Logo">
            <h1>DB Explorer — Sign In</h1>
        </div>
    </div>
    <div class="header-status">
        <div class="status-left">
            <span>
                <span class="status-label">Memory:</span>
                <span class="status-value"><?= $memUsageFmt ?></span>
            </span>
        </div>
        <div class="status-right">
            <span class="status-label">Not authenticated</span>
        </div>
    </div>
</header>

<main class="main-content">
    <div class="login-wrapper">
        <div class="card login-card">
            <h2 class="card-title text-center">Sign In</h2>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" action="index.php">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" name="username" id="username" required autofocus
                           placeholder="Enter username"
                           value="<?= e($_POST['username'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" required
                           placeholder="Enter password">
                </div>

                <div class="mt-2">
                    <button type="submit" class="btn btn-primary btn-block">Sign In</button>
                </div>
            </form>
        </div>
    </div>
</main>

</body>
</html>
