<?php
/**
 * @file   query.php
 * @brief  Raw SQL query runner.
 */
require_once __DIR__ . '/includes/session.php';
requireLogin();

$dbName = $_SESSION['db_name'] ?? '';
$sql    = $_POST['sql'] ?? '';
$rows   = [];
$cols   = [];
$error  = '';
$affected = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && trim($sql) !== '') {
    $db = getDbConnection();
    if ($db) {
        try {
            $isSelect = preg_match('/^\s*(SELECT|SHOW|DESCRIBE|EXPLAIN|PRAGMA)/i', $sql);
            if ($isSelect) {
                $count = 0;
                foreach ($db->rawQuery($sql) as $row) {
                    if ($count === 0) $cols = array_keys($row);
                    $rows[] = $row;
                    $count++;
                    if ($count >= 1000) break;
                }
            } else {
                foreach ($db->rawQuery($sql) as $row) { /* consume generator */ }
                $affected = 'Query executed successfully.';
            }
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }
        $db->disconnect();
    }
}

$pageTitle = 'SQL Query';
require_once __DIR__ . '/includes/header.php';
?>

<div class="breadcrumb">
    <a href="databases.php">Databases</a><span>&rsaquo;</span>
    <a href="tables.php"><?= e(displayDbName()) ?></a><span>&rsaquo;</span>
    <strong>SQL Query</strong>
</div>

<div class="card">
    <h2 class="card-title">Run SQL Query</h2>
    <form method="post">
        <div class="form-group">
            <label for="sql">SQL (database: <?= e(displayDbName()) ?>)</label>
            <textarea name="sql" id="sql" class="sql-editor" placeholder="SELECT * FROM table_name LIMIT 100;"><?= e($sql) ?></textarea>
        </div>
        <div class="flex gap-1">
            <button type="submit" class="btn btn-primary">Execute</button>
            <button type="button" class="btn btn-secondary" id="sqlClearBtn">Clear</button>
        </div>
    </form>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<?php if ($affected): ?>
    <div class="alert alert-success"><?= e($affected) ?></div>
<?php endif; ?>

<?php if (!empty($rows)): ?>
<div class="card">
    <h2 class="card-title">Results (<?= count($rows) ?> rows)</h2>
    <div class="overflow-x">
        <table class="data-table" id="queryResults" data-sortable>
            <thead><tr>
                <?php foreach ($cols as $ci => $c): ?>
                    <th class="sortable-th" data-col="<?= $ci ?>"><?= e($c) ?> <span class="sort-arrow">&#9670;</span></th>
                <?php endforeach; ?>
            </tr></thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <?php foreach ($cols as $c): ?>
                            <td title="<?= e($row[$c] ?? '') ?>"><?= e($row[$c] ?? 'NULL') ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
