<?php
/**
 * @file   insert.php
 * @brief  Form to insert a new row (auto-built from schema).
 */
require_once __DIR__ . '/includes/session.php';
requireLogin();

$table  = $_GET['table'] ?? $_POST['table'] ?? '';
$dbName = $_SESSION['db_name'] ?? '';
$page   = max(1, (int) ($_GET['page'] ?? $_POST['page'] ?? 1));
$search = trim($_GET['search'] ?? $_POST['search'] ?? '');
if ($table === '' || $dbName === '') { header('Location: tables.php'); exit; }

$returnParams = ['table' => $table];
if ($page > 1) { $returnParams['page'] = $page; }
if ($search !== '') { $returnParams['search'] = $search; }
$returnUrl = 'view_table.php?' . http_build_query($returnParams);

$db = getDbConnection();
if (!$db) { setFlash('error', 'Connection failed.'); header('Location: tables.php'); exit; }

try { $schema = $db->getTableSchema($table, $dbName); }
catch (\Exception $e) { setFlash('error', $e->getMessage()); $db->disconnect(); header('Location: tables.php'); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [];
    foreach ($schema as $col) {
        $name = $col['name'];
        if (stripos($col['extra'], 'auto_increment') !== false) continue;
        if (isset($_POST['col'][$name])) {
            $val = $_POST['col'][$name];
            $data[$name] = ($val === '' && $col['nullable']) ? null : $val;
        }
    }
    try {
        $id = $db->insert($table, $data, $dbName);
        $db->disconnect();
        setFlash('success', "Row inserted." . ($id ? " ID: {$id}" : ''));
        header('Location: ' . $returnUrl); exit;
    } catch (\Exception $e) { $error = 'Insert failed: ' . $e->getMessage(); }
}
$db->disconnect();

$pageTitle = e($table) . ' — Insert';
require_once __DIR__ . '/includes/header.php';
?>

<div class="breadcrumb">
    <a href="databases.php">Databases</a><span>&rsaquo;</span>
    <a href="tables.php"><?= e(displayDbName()) ?></a><span>&rsaquo;</span>
    <a href="<?= e($returnUrl) ?>"><?= e($table) ?></a><span>&rsaquo;</span>
    <strong>Insert</strong>
</div>

<div class="card">
    <h2 class="card-title">Insert into <?= e($table) ?></h2>
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

    <form method="post" action="insert.php">
        <input type="hidden" name="table" value="<?= e($table) ?>">
        <input type="hidden" name="page" value="<?= $page ?>">
        <input type="hidden" name="search" value="<?= e($search) ?>">
        <?php foreach ($schema as $col): ?>
            <?php $isAuto = (stripos($col['extra'], 'auto_increment') !== false); ?>
            <div class="form-group">
                <label for="col_<?= e($col['name']) ?>">
                    <?= e($col['name']) ?>
                    <span class="text-muted title-meta">
                        (<?= e($col['type']) ?>)
                        <?php if (!$col['nullable']): ?> — required<?php endif; ?>
                        <?php if ($isAuto): ?> — auto<?php endif; ?>
                    </span>
                </label>
                <?php if ($isAuto): ?>
                    <input type="text" disabled placeholder="Auto-generated" class="input-disabled">
                <?php else: ?>
                    <input type="text" name="col[<?= e($col['name']) ?>]" id="col_<?= e($col['name']) ?>"
                           value="<?= e($_POST['col'][$col['name']] ?? '') ?>"
                           <?= !$col['nullable'] ? 'required' : '' ?>
                           placeholder="<?= e($col['default'] ?? '') ?>">
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <div class="mt-2 flex gap-1">
            <button type="submit" class="btn btn-success">Insert Row</button>
            <a href="<?= e($returnUrl) ?>" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
