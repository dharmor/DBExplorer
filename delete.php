<?php
/**
 * @file   delete.php
 * @brief  Delete a row by primary key, redirect back.
 */
require_once __DIR__ . '/includes/session.php';
requireLogin();

$table  = $_GET['table'] ?? '';
$pk     = $_GET['pk']    ?? [];
$dbName = $_SESSION['db_name'] ?? '';
$page   = max(1, (int) ($_GET['page'] ?? 1));
$search = trim($_GET['search'] ?? '');
if ($table === '' || empty($pk) || $dbName === '') { header('Location: tables.php'); exit; }

$returnParams = ['table' => $table];
if ($page > 1) { $returnParams['page'] = $page; }
if ($search !== '') { $returnParams['search'] = $search; }
$returnUrl = 'view_table.php?' . http_build_query($returnParams);

$db = getDbConnection();
if (!$db) { setFlash('error', 'Connection failed.'); header('Location: ' . $returnUrl); exit; }

try {
    $affected = $db->delete($table, $pk, $dbName);
    $db->disconnect();
    if ($affected > 0) { setFlash('success', "Row deleted. ({$affected} affected)"); }
    else               { setFlash('info', 'No rows matched.'); }
} catch (\Exception $e) { setFlash('error', 'Delete failed: ' . $e->getMessage()); }

header('Location: ' . $returnUrl);
exit;
