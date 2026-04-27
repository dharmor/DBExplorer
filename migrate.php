<?php
/**
 * @file   migrate.php
 * @brief  Migrate data between tables (same or different databases on same server).
 */
require_once __DIR__ . '/includes/session.php';
requireLogin();

$dbName = $_SESSION['db_name'] ?? '';
$db = getDbConnection();
$allDbs = $db ? $db->getAllDatabases() : [];
$tables = $db ? $db->getAllTables($dbName) : [];

$srcTable = $_POST['src_table'] ?? '';
$dstDb    = $_POST['dst_db']    ?? $dbName;
$dstTable = $_POST['dst_table'] ?? '';
$migrated = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db && $srcTable && $dstTable) {
    try {
        $count = 0;
        $batchSize = 500;
        $offset = 0;

        while (true) {
            $batch = [];
            foreach ($db->getTableData($srcTable, $dbName, $batchSize, $offset) as $row) {
                $batch[] = $row;
            }
            if (empty($batch)) break;

            foreach ($batch as $row) {
                $db->insert($dstTable, $row, $dstDb);
                $count++;
            }
            $offset += $batchSize;

            if (count($batch) < $batchSize) break;
        }

        $migrated = $count;
        setFlash('success', "Migrated {$count} rows from {$srcTable} to {$dstDb}.{$dstTable}");
    } catch (\Exception $e) {
        setFlash('error', $e->getMessage());
    }
}
if ($db) $db->disconnect();

$pageTitle = 'Data Migration';
require_once __DIR__ . '/includes/header.php';
?>

<div class="breadcrumb">
    <a href="databases.php">Databases</a><span>&rsaquo;</span>
    <a href="tables.php"><?= e(displayDbName()) ?></a><span>&rsaquo;</span>
    <strong>Migrate</strong>
</div>

<div class="card">
    <h2 class="card-title">Migrate Table Data</h2>
    <p class="text-muted mb-2">Copy rows from a source table into a destination table on the same server. The destination table must already exist with compatible columns.</p>

    <form method="post">
        <div class="flex gap-2 flex-wrap">
            <div class="form-group form-group-flex">
                <label>Source Table (<?= e(displayDbName()) ?>)</label>
                <select name="src_table" required>
                    <option value="">--</option>
                    <?php foreach ($tables as $t): ?>
                        <option value="<?= e($t) ?>" <?= $srcTable===$t?'selected':'' ?>><?= e($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group form-group-flex">
                <label>Destination Database</label>
                <select name="dst_db">
                    <?php foreach ($allDbs as $d): ?>
                        <option value="<?= e($d) ?>" <?= $dstDb===$d?'selected':'' ?>><?= e($d) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group form-group-flex">
                <label>Destination Table</label>
                <input type="text" name="dst_table" value="<?= e($dstTable) ?>" required placeholder="table_name">
            </div>
        </div>
        <button type="submit" class="btn btn-primary" data-confirm="This will INSERT rows into the destination. Continue?">Migrate</button>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
