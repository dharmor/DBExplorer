<?php
/**
 * @file   change_password.php
 * @brief  Allow the authenticated admin user to change the app login password.
 */
require_once __DIR__ . '/includes/session.php';
requireAppLogin();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
        $errors[] = 'All password fields are required.';
    } elseif (!verifyAppCredentials(APP_USER, $currentPassword)) {
        $errors[] = 'Current password is incorrect.';
    } elseif (strlen($newPassword) < 8) {
        $errors[] = 'New password must be at least 8 characters.';
    } elseif ($newPassword !== $confirmPassword) {
        $errors[] = 'New password and confirmation do not match.';
    }

    if (!$errors) {
        if (setAppPassword($newPassword)) {
            setFlash('success', 'Admin password updated.');
            header('Location: change_password.php');
            exit;
        }

        $errors[] = 'Password could not be saved. Check file permissions.';
    }
}

$pageTitle = 'Change Admin Password';
require_once __DIR__ . '/includes/header.php';
?>

<div class="breadcrumb">
    <a href="database_select.php">Connection</a><span>&rsaquo;</span>
    <strong>Admin Password</strong>
</div>

<div class="card">
    <h2 class="card-title">Change Admin Password</h2>
    <p class="text-muted mb-2">Update the password used for the DB Explorer admin sign-in.</p>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endforeach; ?>

    <form method="post" action="change_password.php">
        <div class="form-group">
            <label for="current_password">Current Password</label>
            <input type="password" name="current_password" id="current_password" required autocomplete="current-password">
        </div>

        <div class="form-group">
            <label for="new_password">New Password</label>
            <input type="password" name="new_password" id="new_password" required minlength="8" autocomplete="new-password">
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirm New Password</label>
            <input type="password" name="confirm_password" id="confirm_password" required minlength="8" autocomplete="new-password">
        </div>

        <div class="mt-2">
            <button type="submit" class="btn btn-primary">Update Password</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
