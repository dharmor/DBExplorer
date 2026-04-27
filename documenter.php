<?php
/**
 * @file   documenter.php
 * @brief  Auto-generate schema documentation for all tables.
 */
require_once __DIR__ . '/includes/session.php';
requireLogin();

$dbName = $_SESSION['db_name'] ?? '';
$db = getDbConnection();
$tables = $db ? $db->getAllTables($dbName) : [];
$allSchema = [];

if ($db) {
    foreach ($tables as $t) {
        try {
            $schema = $db->getTableSchema($t, $dbName);
            $pk     = $db->getPrimaryKey($t, $dbName);
            $count  = $db->countRows($t, $dbName);
            $allSchema[] = ['name' => $t, 'schema' => $schema, 'pk' => $pk, 'rows' => $count];
        } catch (\Exception $e) {
            $allSchema[] = ['name' => $t, 'schema' => [], 'pk' => [], 'rows' => 0, 'error' => $e->getMessage()];
        }
    }
    $db->disconnect();
}

$pageTitle = 'Schema Documentation';
require_once __DIR__ . '/includes/header.php';
?>

<div class="breadcrumb">
    <a href="databases.php">Databases</a><span>&rsaquo;</span>
    <a href="tables.php"><?= e(displayDbName()) ?></a><span>&rsaquo;</span>
    <strong>Schema Docs</strong>
</div>

<div class="card">
    <h2 class="card-title">
        Schema Documentation: <?= e(displayDbName()) ?>
        <span class="title-actions">
            <a href="documenter_pdf.php" class="btn btn-sm btn-primary" data-action-link="pdf">Export PDF</a>
        </span>
    </h2>
    <p class="text-muted mb-2"><?= count($tables) ?> tables documented.</p>

    <!-- Table of contents -->
    <div class="mb-2">
        <strong>Table of Contents</strong>
        <ul class="toc-list">
            <?php foreach ($allSchema as $i => $info): ?>
                <li><a href="#tbl-<?= $i ?>"><?= e($info['name']) ?></a> <span class="text-muted">(<?= number_format($info['rows']) ?>)</span></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<?php foreach ($allSchema as $i => $info): ?>
<div class="card" id="tbl-<?= $i ?>">
    <h2 class="card-title card-title-sm">
        <?= e($info['name']) ?>
        <span class="text-muted title-meta">
            — <?= number_format($info['rows']) ?> rows
            <?php if (!empty($info['pk'])): ?>
                | PK: <?= e(implode(', ', $info['pk'])) ?>
            <?php endif; ?>
        </span>
    </h2>

    <?php if (!empty($info['error'])): ?>
        <div class="alert alert-danger"><?= e($info['error']) ?></div>
    <?php elseif (empty($info['schema'])): ?>
        <p class="text-muted">No columns.</p>
    <?php else: ?>
        <table class="schema-table">
            <thead><tr><th>Column</th><th>Type</th><th>Nullable</th><th>Key</th><th>Default</th><th>Extra</th></tr></thead>
            <tbody>
                <?php foreach ($info['schema'] as $col): ?>
                    <tr>
                        <td><strong><?= e($col['name']) ?></strong></td>
                        <td><?= e($col['type']) ?></td>
                        <td><?= $col['nullable'] ? 'YES' : 'NO' ?></td>
                        <td><?= e($col['key'] ?? '') ?></td>
                        <td><?= e($col['default'] ?? '') ?></td>
                        <td><?= e($col['extra'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php endforeach; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
