<?php
declare(strict_types=1);

require_once __DIR__ . '/../classes/DatabaseFactory.php';

session_start();

const APP_USER = 'admin';
const APP_VERSION = '1.0.0';

function appPasswordFile(): string
{
    return __DIR__ . '/app_password.hash';
}

function defaultPasswordHash(): string
{
    return password_hash('admin', PASSWORD_BCRYPT);
}

function appPasswordHash(): string
{
    $file = appPasswordFile();
    if (is_file($file)) {
        return trim((string) file_get_contents($file));
    }

    $hash = defaultPasswordHash();
    @file_put_contents($file, $hash);
    return $hash;
}

function verifyAppCredentials(string $username, string $password): bool
{
    return hash_equals(APP_USER, $username) && password_verify($password, appPasswordHash());
}

function updateAppPassword(string $password): bool
{
    return (bool) file_put_contents(appPasswordFile(), password_hash($password, PASSWORD_BCRYPT));
}

function isAppLoggedIn(): bool
{
    return !empty($_SESSION['app_authenticated']);
}

function isLoggedIn(): bool
{
    return isAppLoggedIn() && !empty($_SESSION['db_type']) && array_key_exists('db_host', $_SESSION);
}

function requireAppLogin(): void
{
    if (!isAppLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}

function requireLogin(): void
{
    requireAppLogin();
    if (!isLoggedIn()) {
        header('Location: database_select.php');
        exit;
    }
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function formatBytes(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $value = (float) $bytes;
    foreach ($units as $unit) {
        if ($value < 1024 || $unit === end($units)) {
            return number_format($value, $unit === 'B' ? 0 : 2) . ' ' . $unit;
        }
        $value /= 1024;
    }
    return $bytes . ' B';
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function getFlash(): array
{
    $flash = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flash;
}

function displayDbName(): string
{
    return basename((string) ($_SESSION['db_name'] ?? $_SESSION['db_host'] ?? 'Database'));
}

function getDbConnection(): ?DatabaseConnection
{
    if (!isLoggedIn()) {
        return null;
    }

    return DatabaseFactory::create(
        (string) $_SESSION['db_type'],
        (string) $_SESSION['db_host'],
        (string) ($_SESSION['db_user'] ?? ''),
        (string) ($_SESSION['db_pass'] ?? ''),
        $_SESSION['db_name'] ?? null,
        $_SESSION['db_port'] ?? null
    );
}

function jsonResponse(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload, JSON_PRETTY_PRINT);
    exit;
}
