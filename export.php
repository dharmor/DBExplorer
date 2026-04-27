<?php
/**
 * @file   export.php
 * @brief  Export table data to CSV, JSON, or SQL format.
 */
require_once __DIR__ . '/includes/session.php';
requireLogin();

$dbName = $_SESSION['db_name'] ?? '';
$db = getDbConnection();
$tables = $db ? $db->getAllTables($dbName) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $table  = $_POST['table'] ?? '';
    $format = $_POST['format'] ?? 'csv';

    if (!$db || $table === '') {
        setFlash('error', 'Select a table.');
        header('Location: export.php');
        exit;
    }

    try {
        $schema = $db->getTableSchema($table, $dbName);
        $cols = array_column($schema, 'name');

        switch ($format) {
            case 'json':
                header('Content-Type: application/json');
                header("Content-Disposition: attachment; filename=\"{$table}.json\"");
                echo '[';
                $first = true;
                foreach ($db->getTableData($table, $dbName, 100000, 0) as $row) {
                    echo ($first ? '' : ',') . "\n" . json_encode($row, JSON_UNESCAPED_UNICODE);
                    $first = false;
                }
                echo "\n]";
                break;

            case 'sql':
                header('Content-Type: text/sql');
                header("Content-Disposition: attachment; filename=\"{$table}.sql\"");
                foreach ($db->getTableData($table, $dbName, 100000, 0) as $row) {
                    $vals = [];
                    foreach ($row as $v) {
                        $vals[] = $v === null ? 'NULL' : "'" . addslashes($v) . "'";
                    }
                    echo "INSERT INTO `{$table}` (`" . implode('`, `', $cols) . "`) VALUES (" . implode(', ', $vals) . ");\n";
                }
                break;

            default: // csv
                header('Content-Type: text/csv');
                header("Content-Disposition: attachment; filename=\"{$table}.csv\"");
                $out = fopen('php://output', 'w');
                fputcsv($out, $cols);
                foreach ($db->getTableData($table, $dbName, 100000, 0) as $row) {
                    fputcsv($out, array_values($row));
                }
                fclose($out);
        }
        $db->disconnect();
        exit;
    } catch (\Exception $e) {
        setFlash('error', $e->getMessage());
        header('Location: export.php');
        exit;
    }
}

if ($db) $db->disconnect();

$pageTitle = 'Data Export';
require_once __DIR__ . '/includes/header.php';
?>

<div class="breadcrumb">
    <a href="databases.php">Databases</a><span>&rsaquo;</span>
    <a href="tables.php"><?= e(displayDbName()) ?></a><span>&rsaquo;</span>
    <strong>Export</strong>
</div>

<div class="card">
    <h2 class="card-title">Export Table Data</h2>
    <form method="post">
        <div class="form-group">
            <label for="table">Table</label>
            <select name="table" id="table" required>
                <option value="">-- Select --</option>
                <?php foreach ($tables as $t): ?>
                    <option value="<?= e($t) ?>"><?= e($t) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="format">Format</label>
            <select name="format" id="format">
                <option value="csv">CSV</option>
                <option value="json">JSON</option>
                <option value="sql">SQL INSERT Statements</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Export</button>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
