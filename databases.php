<?php
/**
 * @file   databases.php
 * @brief  List databases; user picks one to continue.
 */
require_once __DIR__ . '/includes/session.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['database'])) {
    $_SESSION['db_name'] = $_POST['database'];
    header('Location: tables.php');
    exit;
}

$db = getDbConnection();
$databases = [];
$error = '';
if ($db) {
    try {
        $databases = $db->getAllDatabases();
        $db->disconnect();
    } catch (\Exception $e) {
        $error = 'Could not list databases: ' . $e->getMessage();
    }
}

$pageTitle = 'Select Database';
require_once __DIR__ . '/includes/header.php';
?>

<div class="breadcrumb">
    <a href="database_select.php">Login</a><span>&rsaquo;</span><strong>Databases</strong>
</div>

<div class="card">
    <h2 class="card-title">Choose a Database</h2>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>
    <?php if (empty($databases)): ?>
        <p class="text-muted">No databases found on this server.</p>
    <?php else: ?>
        <form method="post" action="databases.php">
            <div class="form-group">
                <label for="database">Database</label>
                <select name="database" id="database" required>
                    <option value="">— Select a database —</option>
                    <?php foreach ($databases as $dbName): ?>
                        <option value="<?= e($dbName) ?>"><?= e(basename($dbName)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Open Database</button>
        </form>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
