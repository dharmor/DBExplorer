<?php
/**
 * @file   tables.php
 * @brief  List all tables in the selected database, with sorting.
 */
require_once __DIR__ . '/includes/session.php';
requireLogin();

if (empty($_SESSION['db_name'])) { header('Location: databases.php'); exit; }

$db = getDbConnection();
$tables = [];
$error = '';
if ($db) {
    try {
        $tables = $db->getAllTables($_SESSION['db_name']);
        $db->disconnect();
    } catch (\Exception $e) {
        $error = 'Could not list tables: ' . $e->getMessage();
    }
}

$pageTitle = 'Tables — ' . displayDbName();
require_once __DIR__ . '/includes/header.php';
?>

<div class="breadcrumb">
    <a href="databases.php">Databases</a><span>&rsaquo;</span>
    <strong><?= e(displayDbName()) ?></strong>
</div>

<div class="card">
    <h2 class="card-title">Tables in <?= e(displayDbName()) ?></h2>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>
    <?php if (empty($tables)): ?>
        <p class="text-muted">No tables found in this database.</p>
    <?php else: ?>
        <div class="mb-2">
            <input type="text" id="tableFilter" placeholder="Filter tables..." class="search-input search-filter">
        </div>
        <table class="data-table" id="tablesListTable" data-sortable>
            <thead><tr>
                <th class="sortable-th" data-col="0"># <span class="sort-arrow">&#9670;</span></th>
                <th class="sortable-th" data-col="1">Table Name <span class="sort-arrow">&#9670;</span></th>
                <th>Actions</th>
            </tr></thead>
            <tbody>
                <?php foreach ($tables as $i => $tbl): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><a href="view_table.php?table=<?= urlencode($tbl) ?>"><strong><?= e($tbl) ?></strong></a></td>
                        <td class="flex gap-1">
                            <a href="view_table.php?table=<?= urlencode($tbl) ?>" class="btn btn-sm btn-primary">View Data</a>
                            <a href="schema.php?table=<?= urlencode($tbl) ?>" class="btn btn-sm btn-info">Schema</a>
                            <a href="insert.php?table=<?= urlencode($tbl) ?>" class="btn btn-sm btn-success">Insert</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
