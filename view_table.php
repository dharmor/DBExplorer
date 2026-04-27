<?php
/**
 * @file   view_table.php
 * @brief  Display table data with pagination, sorting, search, edit/delete.
 */
require_once __DIR__ . '/includes/session.php';
requireLogin();

$table   = $_GET['table'] ?? '';
$dbName  = $_SESSION['db_name'] ?? '';
if ($table === '' || $dbName === '') { header('Location: tables.php'); exit; }

$db = getDbConnection();
if (!$db) { setFlash('error', 'Connection failed.'); header('Location: tables.php'); exit; }

$perPage = 50;
$page    = max(1, (int) ($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;
$search  = trim($_GET['search'] ?? '');

$returnParams = ['table' => $table];
if ($page > 1) { $returnParams['page'] = $page; }
if ($search !== '') { $returnParams['search'] = $search; }
$returnQuery = http_build_query($returnParams);

try {
    $totalRows = $db->countRows($table, $dbName);
    $schema    = $db->getTableSchema($table, $dbName);
    $pkCols    = $db->getPrimaryKey($table, $dbName);

    if ($search !== '') {
        $rows = [];
        foreach ($db->searchTable($table, $dbName, $search, $perPage, $offset) as $row) {
            $rows[] = $row;
        }
        $totalRows = count($rows);
        $totalPages = 1;
    } else {
        $rows = [];
        foreach ($db->getTableData($table, $dbName, $perPage, $offset) as $row) {
            $rows[] = $row;
        }
        $totalPages = max(1, (int) ceil($totalRows / $perPage));
    }
    $db->disconnect();
} catch (\Exception $e) {
    setFlash('error', $e->getMessage());
    header('Location: tables.php'); exit;
}

$columns = array_column($schema, 'name');

$pageTitle = e($table) . ' — Data';
require_once __DIR__ . '/includes/header.php';
?>

<div class="breadcrumb">
    <a href="databases.php">Databases</a><span>&rsaquo;</span>
    <a href="tables.php"><?= e(displayDbName()) ?></a><span>&rsaquo;</span>
    <strong><?= e($table) ?></strong>
</div>

<div class="card">
    <div class="flex justify-between items-center mb-2">
        <h2 class="card-title-plain">
            <?= e($table) ?>
            <span class="text-muted title-count">(<?= number_format($totalRows) ?> rows)</span>
        </h2>
        <div class="flex gap-1">
            <a href="schema.php?table=<?= urlencode($table) ?>" class="btn btn-sm btn-info">Show Schema</a>
            <a href="insert.php?<?= $returnQuery ?>" class="btn btn-sm btn-success">Insert Row</a>
            <a href="tables.php" class="btn btn-sm btn-secondary">Back to Tables</a>
        </div>
    </div>

    <form method="get" class="flex gap-1 items-center mb-2 search-form">
        <input type="hidden" name="table" value="<?= e($table) ?>">
        <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search all columns..." class="search-input">
        <button type="submit" class="btn btn-sm btn-primary">Search</button>
        <?php if ($search !== ''): ?>
            <a href="view_table.php?table=<?= urlencode($table) ?>" class="btn btn-sm btn-secondary">Clear</a>
        <?php endif; ?>
    </form>

    <?php if (empty($rows)): ?>
        <p class="text-muted mt-2">No data<?= $search !== '' ? ' matching "' . e($search) . '"' : '' ?>.</p>
    <?php else: ?>
        <div class="overflow-x">
            <table class="data-table" id="sortableTable" data-sortable>
                <thead><tr>
                    <?php foreach ($columns as $ci => $col): ?>
                        <th class="sortable-th" data-col="<?= $ci ?>"><?= e($col) ?> <span class="sort-arrow">&#9670;</span></th>
                    <?php endforeach; ?>
                    <th>Actions</th>
                </tr></thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <?php foreach ($columns as $col): ?>
                                <td title="<?= e($row[$col] ?? '') ?>"><?= e($row[$col] ?? 'NULL') ?></td>
                            <?php endforeach; ?>
                            <td class="flex gap-1 cell-actions">
                                <?php
                                $pkQ = [];
                                foreach ($pkCols as $pk) { $pkQ['pk['.$pk.']'] = $row[$pk] ?? ''; }
                                $qs = http_build_query(array_merge($returnParams, $pkQ));
                                ?>
                                <a href="edit.php?<?= $qs ?>" class="btn btn-sm btn-primary">Edit</a>
                                <a href="delete.php?<?= $qs ?>" class="btn btn-sm btn-danger"
                                   data-confirm="Delete this row?">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1 && $search === ''): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?table=<?= urlencode($table) ?>&page=<?= $page-1 ?>">&laquo; Prev</a>
                <?php endif; ?>
                <?php for ($p = max(1,$page-3); $p <= min($totalPages,$page+3); $p++): ?>
                    <?php if ($p === $page): ?>
                        <span class="active"><?= $p ?></span>
                    <?php else: ?>
                        <a href="?table=<?= urlencode($table) ?>&page=<?= $p ?>"><?= $p ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="?table=<?= urlencode($table) ?>&page=<?= $page+1 ?>">Next &raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
