<?php
/**
 * @file   compare.php
 * @brief  Compare schema or row counts between two tables.
 */
require_once __DIR__ . '/includes/session.php';
requireLogin();

$dbName = $_SESSION['db_name'] ?? '';
$db = getDbConnection();
$tables = $db ? $db->getAllTables($dbName) : [];

$result = null;
$table1 = $_POST['table1'] ?? '';
$table2 = $_POST['table2'] ?? '';
$mode   = $_POST['mode'] ?? 'schema';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db && $table1 && $table2) {
    try {
        if ($mode === 'schema') {
            $s1 = $db->getTableSchema($table1, $dbName);
            $s2 = $db->getTableSchema($table2, $dbName);
            $map1 = []; foreach ($s1 as $c) $map1[$c['name']] = $c;
            $map2 = []; foreach ($s2 as $c) $map2[$c['name']] = $c;
            $allCols = array_unique(array_merge(array_keys($map1), array_keys($map2)));
            sort($allCols);
            $result = ['type' => 'schema', 'cols' => $allCols, 'map1' => $map1, 'map2' => $map2];
        } else {
            $c1 = $db->countRows($table1, $dbName);
            $c2 = $db->countRows($table2, $dbName);
            $s1 = count($db->getTableSchema($table1, $dbName));
            $s2 = count($db->getTableSchema($table2, $dbName));
            $result = ['type' => 'summary', 'rows1' => $c1, 'rows2' => $c2, 'cols1' => $s1, 'cols2' => $s2];
        }
    } catch (\Exception $e) {
        setFlash('error', $e->getMessage());
    }
}
if ($db) $db->disconnect();

$pageTitle = 'Table Compare';
require_once __DIR__ . '/includes/header.php';
?>

<div class="breadcrumb">
    <a href="databases.php">Databases</a><span>&rsaquo;</span>
    <a href="tables.php"><?= e(displayDbName()) ?></a><span>&rsaquo;</span>
    <strong>Compare</strong>
</div>

<div class="card">
    <h2 class="card-title">Compare Two Tables</h2>
    <form method="post" class="flex gap-2 flex-wrap items-center mb-2">
        <div class="form-group form-group-flex-sm">
            <label>Table A</label>
            <select name="table1" required>
                <option value="">--</option>
                <?php foreach ($tables as $t): ?>
                    <option value="<?= e($t) ?>" <?= $table1===$t?'selected':'' ?>><?= e($t) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group form-group-flex-sm">
            <label>Table B</label>
            <select name="table2" required>
                <option value="">--</option>
                <?php foreach ($tables as $t): ?>
                    <option value="<?= e($t) ?>" <?= $table2===$t?'selected':'' ?>><?= e($t) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group form-group-fixed-sm">
            <label>Mode</label>
            <select name="mode">
                <option value="schema" <?= $mode==='schema'?'selected':'' ?>>Schema Diff</option>
                <option value="summary" <?= $mode==='summary'?'selected':'' ?>>Summary</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary btn-form-aligned">Compare</button>
    </form>

    <?php if ($result && $result['type'] === 'schema'): ?>
        <table class="data-table">
            <thead><tr><th>Column</th><th><?= e($table1) ?> Type</th><th><?= e($table2) ?> Type</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($result['cols'] as $col):
                $in1 = isset($result['map1'][$col]);
                $in2 = isset($result['map2'][$col]);
                $type1 = $in1 ? $result['map1'][$col]['type'] : '-';
                $type2 = $in2 ? $result['map2'][$col]['type'] : '-';
                if (!$in1) $status = 'Only in B';
                elseif (!$in2) $status = 'Only in A';
                elseif ($type1 === $type2) $status = 'Match';
                else $status = 'Type differs';
                $cls = $status === 'Match' ? '' : 'class="row-mismatch"';
            ?>
                <tr <?= $cls ?>>
                    <td><strong><?= e($col) ?></strong></td>
                    <td><?= e($type1) ?></td>
                    <td><?= e($type2) ?></td>
                    <td><?= e($status) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php elseif ($result && $result['type'] === 'summary'): ?>
        <table class="data-table">
            <thead><tr><th>Metric</th><th><?= e($table1) ?></th><th><?= e($table2) ?></th><th>Diff</th></tr></thead>
            <tbody>
                <tr><td>Row Count</td><td><?= number_format($result['rows1']) ?></td><td><?= number_format($result['rows2']) ?></td><td><?= number_format($result['rows1'] - $result['rows2']) ?></td></tr>
                <tr><td>Column Count</td><td><?= $result['cols1'] ?></td><td><?= $result['cols2'] ?></td><td><?= $result['cols1'] - $result['cols2'] ?></td></tr>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
