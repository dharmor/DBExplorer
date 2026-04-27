<?php
/**
 * @file   edit.php
 * @brief  Form to update an existing row (pre-filled via PK).
 */
require_once __DIR__ . '/includes/session.php';
requireLogin();

$table  = $_GET['table'] ?? $_POST['table'] ?? '';
$pk     = $_GET['pk']    ?? $_POST['pk']    ?? [];
$dbName = $_SESSION['db_name'] ?? '';
$page   = max(1, (int) ($_GET['page'] ?? $_POST['page'] ?? 1));
$search = trim($_GET['search'] ?? $_POST['search'] ?? '');
if ($table === '' || empty($pk) || $dbName === '') { header('Location: tables.php'); exit; }

$returnParams = ['table' => $table];
if ($page > 1) { $returnParams['page'] = $page; }
if ($search !== '') { $returnParams['search'] = $search; }
$returnUrl = 'view_table.php?' . http_build_query($returnParams);

$db = getDbConnection();
if (!$db) { setFlash('error', 'Connection failed.'); header('Location: tables.php'); exit; }

try { $schema = $db->getTableSchema($table, $dbName); }
catch (\Exception $e) { setFlash('error', $e->getMessage()); $db->disconnect(); header('Location: tables.php'); exit; }

$error = '';

// Handle POST update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['col'])) {
    $data = [];
    foreach ($schema as $col) {
        $name = $col['name'];
        if (array_key_exists($name, $pk)) continue;
        if (isset($_POST['col'][$name])) {
            $val = $_POST['col'][$name];
            $data[$name] = ($val === '' && $col['nullable']) ? null : $val;
        }
    }
    try {
        $affected = $db->update($table, $data, $pk, $dbName);
        $db->disconnect();
        setFlash('success', "Row updated. ({$affected} affected)");
        header('Location: ' . $returnUrl); exit;
    } catch (\Exception $e) { $error = 'Update failed: ' . $e->getMessage(); }
}

// Fetch current row
try {
    $wClauses = [];
    foreach (array_keys($pk) as $c) { $wClauses[] = '"' . str_replace('"', '""', $c) . '" = ?'; }
    $sql = 'SELECT * FROM "' . str_replace('"', '""', $table) . '" WHERE ' . implode(' AND ', $wClauses);
    $row = null;
    foreach ($db->rawQuery($sql, array_values($pk)) as $r) { $row = $r; break; }
    $db->disconnect();
} catch (\Exception $e) {
    setFlash('error', 'Could not fetch row: ' . $e->getMessage());
    $db->disconnect();
    header('Location: ' . $returnUrl); exit;
}

if (empty($row)) { setFlash('error', 'Row not found.'); header('Location: ' . $returnUrl); exit; }

$pageTitle = e($table) . ' — Edit';
require_once __DIR__ . '/includes/header.php';
?>

<div class="breadcrumb">
    <a href="databases.php">Databases</a><span>&rsaquo;</span>
    <a href="tables.php"><?= e(displayDbName()) ?></a><span>&rsaquo;</span>
    <a href="<?= e($returnUrl) ?>"><?= e($table) ?></a><span>&rsaquo;</span>
    <strong>Edit</strong>
</div>

<div class="card">
    <h2 class="card-title">Edit Row in <?= e($table) ?></h2>
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

    <form method="post" action="edit.php">
        <input type="hidden" name="table" value="<?= e($table) ?>">
        <input type="hidden" name="page" value="<?= $page ?>">
        <input type="hidden" name="search" value="<?= e($search) ?>">
        <?php foreach ($pk as $col => $val): ?>
            <input type="hidden" name="pk[<?= e($col) ?>]" value="<?= e($val) ?>">
        <?php endforeach; ?>

        <?php foreach ($schema as $col): ?>
            <?php $name = $col['name']; $isPk = array_key_exists($name, $pk); $value = $row[$name] ?? ''; ?>
            <div class="form-group">
                <label for="col_<?= e($name) ?>">
                    <?= e($name) ?>
                    <span class="text-muted title-meta">
                        (<?= e($col['type']) ?>)<?php if ($isPk): ?> — primary key<?php endif; ?>
                    </span>
                </label>
                <?php if ($isPk): ?>
                    <input type="text" value="<?= e($value) ?>" disabled class="input-disabled">
                <?php else: ?>
                    <input type="text" name="col[<?= e($name) ?>]" id="col_<?= e($name) ?>"
                           value="<?= e($value) ?>" <?= !$col['nullable'] ? 'required' : '' ?>>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <div class="mt-2 flex gap-1">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="<?= e($returnUrl) ?>" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
