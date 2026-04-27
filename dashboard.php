<?php
/**
 * @file   dashboard.php
 * @brief  Database dashboard — overview of all tables with row counts.
 */
require_once __DIR__ . '/includes/session.php';
requireLogin();

$dbName = $_SESSION['db_name'] ?? '';
$db = getDbConnection();
$tables = $db ? $db->getAllTables($dbName) : [];
$stats = [];
$totalRows = 0;
$totalCols = 0;

if ($db) {
    foreach ($tables as $t) {
        try {
            $rows = $db->countRows($t, $dbName);
            $cols = count($db->getTableSchema($t, $dbName));
            $stats[] = ['name' => $t, 'rows' => $rows, 'cols' => $cols];
            $totalRows += $rows;
            $totalCols += $cols;
        } catch (\Exception $e) {
            $stats[] = ['name' => $t, 'rows' => -1, 'cols' => 0, 'error' => $e->getMessage()];
        }
    }
    $db->disconnect();
}

$pageTitle = 'Database Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<div class="breadcrumb">
    <a href="databases.php">Databases</a><span>&rsaquo;</span>
    <a href="tables.php"><?= e(displayDbName()) ?></a><span>&rsaquo;</span>
    <strong>Dashboard</strong>
</div>

<!-- Summary stats -->
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-value"><?= count($tables) ?></div>
        <div class="stat-label">Tables</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= number_format($totalRows) ?></div>
        <div class="stat-label">Total Rows</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= number_format($totalCols) ?></div>
        <div class="stat-label">Total Columns</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= e(DatabaseFactory::DRIVERS[$_SESSION['db_type']] ?? $_SESSION['db_type']) ?></div>
        <div class="stat-label">Server Type</div>
    </div>
</div>

<!-- Table breakdown -->
<div class="card">
    <h2 class="card-title">Table Overview</h2>
    <table class="data-table" id="dashTable" data-sortable>
        <thead><tr>
            <th class="sortable-th" data-col="0"># <span class="sort-arrow">&#9670;</span></th>
            <th class="sortable-th" data-col="1">Table <span class="sort-arrow">&#9670;</span></th>
            <th class="sortable-th" data-col="2">Rows <span class="sort-arrow">&#9670;</span></th>
            <th class="sortable-th" data-col="3">Columns <span class="sort-arrow">&#9670;</span></th>
            <th>Actions</th>
        </tr></thead>
        <tbody>
            <?php foreach ($stats as $i => $s): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><a href="view_table.php?table=<?= urlencode($s['name']) ?>"><strong><?= e($s['name']) ?></strong></a></td>
                    <td><?= $s['rows'] >= 0 ? number_format($s['rows']) : '<span class="text-muted">error</span>' ?></td>
                    <td><?= $s['cols'] ?></td>
                    <td class="flex gap-1">
                        <a href="view_table.php?table=<?= urlencode($s['name']) ?>" class="btn btn-sm btn-primary">Data</a>
                        <a href="schema.php?table=<?= urlencode($s['name']) ?>" class="btn btn-sm btn-info">Schema</a>
                        <a href="export.php" class="btn btn-sm btn-secondary">Export</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
