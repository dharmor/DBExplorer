<?php
/**
 * @file   database_select.php
 * @brief  Login — select server type, enter credentials.
 */
require_once __DIR__ . '/includes/session.php';
requireAppLogin();

if (isLoggedIn()) { header('Location: databases.php'); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['db_type']  ?? '';
    $host = trim($_POST['host'] ?? '');
    $user = $_POST['username']  ?? '';
    $pass = $_POST['password']  ?? '';
    $port = !empty($_POST['port']) ? (int) $_POST['port'] : null;

    if (!array_key_exists($type, DatabaseFactory::DRIVERS)) {
        $error = 'Please select a valid database server type.';
    } elseif ($host === '') {
        $error = $type === 'sqlite'
            ? 'Please enter the full path to the SQLite database file.'
            : 'Hostname is required.';
    } elseif ($type === 'sqlite' && !file_exists($host)) {
        $error = "SQLite file not found: {$host}";
    } else {
        try {
            $db = DatabaseFactory::create($type, $host, $user, $pass, null, $port);
            $db->disconnect();
            $_SESSION['db_type'] = $type;
            $_SESSION['db_host'] = $host;
            $_SESSION['db_user'] = $user;
            $_SESSION['db_pass'] = $pass;
            $_SESSION['db_port'] = $port;
            $_SESSION['db_name'] = null;
            header('Location: databases.php');
            exit;
        } catch (\Exception $e) {
            $error = 'Connection failed: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'DB Manager — Database Login';
require_once __DIR__ . '/includes/header.php';
?>

<div class="login-wrapper">
    <div class="card login-card">
        <h2 class="card-title text-center">Connect to Database Server</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="database_select.php">
            <div class="form-group">
                <label for="db_type">Server Type</label>
                <select name="db_type" id="db_type" required>
                    <option value="">— Select —</option>
                    <?php foreach (DatabaseFactory::DRIVERS as $key => $label): ?>
                        <option value="<?= e($key) ?>"
                            <?= (($_POST['db_type'] ?? '') === $key) ? 'selected' : '' ?>>
                            <?= e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" id="grp-host">
                <label for="host" id="host-label">Host <span class="text-muted">(or file path for SQLite)</span></label>
                <input type="text" name="host" id="host" placeholder="localhost" required
                       value="<?= e($_POST['host'] ?? '') ?>">
            </div>

            <div class="form-group" id="grp-port">
                <label for="port">Port <span class="text-muted">(leave blank for default)</span></label>
                <input type="number" name="port" id="port" placeholder="Auto"
                       value="<?= e($_POST['port'] ?? '') ?>">
            </div>

            <div class="form-group" id="grp-user">
                <label for="username">Username</label>
                <input type="text" name="username" id="username"
                       value="<?= e($_POST['username'] ?? '') ?>">
            </div>

            <div class="form-group" id="grp-pass">
                <label for="password">Password</label>
                <input type="password" name="password" id="password">
            </div>

            <div class="mt-2">
                <button type="submit" class="btn btn-primary btn-block">Connect</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
