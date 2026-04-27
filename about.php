<?php
/**
 * @file   about.php
 * @brief  Application information page.
 */
require_once __DIR__ . '/includes/session.php';
requireAppLogin();

$appVersion = '1.5';
$backUrl = $_SERVER['HTTP_REFERER'] ?? 'database_select.php';
if (!str_starts_with($backUrl, '/') && !preg_match('/^https?:\/\/' . preg_quote($_SERVER['HTTP_HOST'] ?? '', '/') . '/i', $backUrl)) {
    $backUrl = 'database_select.php';
}
$pageTitle = 'About DB Explorer';
require_once __DIR__ . '/includes/header.php';
?>

<div class="breadcrumb">
    <a href="database_select.php">Connection</a><span>&rsaquo;</span>
    <strong>About</strong>
</div>

<div class="card">
    <h2 class="card-title">
        About DB Explorer
        <span class="title-actions">
            <a class="btn btn-sm btn-secondary" href="<?= e($backUrl) ?>">Back</a>
        </span>
    </h2>
    <p class="text-muted mb-2">
        DB Explorer is a database browsing and developer utility for inspecting schemas,
        viewing table data, exporting records, generating APIs, and generating model classes.
    </p>

    <p class="text-muted mb-2">
        The current version supports PHP, Python, JavaScript, TypeScript, C#, Java, Go,
        Ruby, Kotlin, Swift, and Rust languages.
    </p>

    <div class="schema-grid">
        <div>
            <strong>Application</strong>
            <div class="text-muted">DB Explorer</div>
        </div>
        <div>
            <strong>Version</strong>
            <div class="text-muted"><?= e($appVersion) ?></div>
        </div>
    </div>

    <div class="mt-2">
        <a class="btn btn-primary" href="https://www.buymeacoffee.com/davidharmor" target="_blank" rel="noopener">
            Buy Me a Coffee
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
