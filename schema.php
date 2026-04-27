<?php
/**
 * @file   schema.php
 * @brief  Display column schema for a table.
 */
require_once __DIR__ . '/includes/session.php';
requireLogin();

$table  = $_GET['table'] ?? '';
$dbName = $_SESSION['db_name'] ?? '';
if ($table === '' || $dbName === '') { header('Location: tables.php'); exit; }

$db = getDbConnection();
if (!$db) { setFlash('error', 'Connection failed.'); header('Location: tables.php'); exit; }

try {
    $schema = $db->getTableSchema($table, $dbName);
    $db->disconnect();
} catch (\Exception $e) {
    setFlash('error', $e->getMessage());
    header('Location: tables.php'); exit;
}

$pageTitle = e($table) . ' — Schema';
require_once __DIR__ . '/includes/header.php';
?>

<div class="breadcrumb">
    <a href="databases.php">Databases</a><span>&rsaquo;</span>
    <a href="tables.php"><?= e(displayDbName()) ?></a><span>&rsaquo;</span>
    <a href="view_table.php?table=<?= urlencode($table) ?>"><?= e($table) ?></a><span>&rsaquo;</span>
    <strong>Schema</strong>
</div>

<div class="card">
    <div class="flex justify-between items-center mb-2">
        <h2 class="card-title-plain">Schema: <?= e($table) ?></h2>
        <div class="flex gap-1">
            <a href="view_table.php?table=<?= urlencode($table) ?>" class="btn btn-sm btn-primary">View Data</a>
            <a href="tables.php" class="btn btn-sm btn-secondary">Back to Tables</a>
        </div>
    </div>
    <table class="schema-table">
        <thead><tr><th>#</th><th>Column</th><th>Type</th><th>Nullable</th><th>Key</th><th>Default</th><th>Extra</th></tr></thead>
        <tbody>
            <?php foreach ($schema as $i => $col): ?>
                <tr>
                    <td><?= $i+1 ?></td>
                    <td><strong><?= e($col['name']) ?></strong></td>
                    <td><?= e($col['type']) ?></td>
                    <td><?= $col['nullable'] ? 'YES' : 'NO' ?></td>
                    <td><?= e($col['key']) ?></td>
                    <td><?= e($col['default'] ?? 'NULL') ?></td>
                    <td><?= e($col['extra']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
