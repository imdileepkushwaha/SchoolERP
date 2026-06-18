<?php
$page_title = 'Change Password';
$page_subtitle = 'Update your portal login password';
require_once 'includes/init.php';

$required = isset($_GET['required']) || $tp_must_change;
$message = '';
$error = '';
$defaultPass = getTeacherPortalDefaultPassword();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!password_verify($current, $teacher['portal_password'])) {
        $error = 'Current password is incorrect.';
    } elseif (strlen($new) < 6) {
        $error = 'New password must be at least 6 characters.';
    } elseif ($new === $defaultPass) {
        $error = 'Please choose a different password than the default.';
    } elseif ($new !== $confirm) {
        $error = 'New passwords do not match.';
    } else {
        updateTeacherPortalPassword($pdo, $teacherId, $new);
        $teacher = getTeacherById($pdo, $teacherId);
        $tp_must_change = false;
        if ($required) {
            header('Location: dashboard.php?pwd_changed=1');
            exit;
        }
        $message = 'Password updated successfully.';
    }
}

require_once 'includes/layout_header.php';
?>

<?php if ($required && !$message): ?>
<div class="tp-alert-error" style="margin-bottom:20px;display:flex;align-items:flex-start;gap:10px">
    <i class="fas fa-shield-halved" style="margin-top:2px"></i>
    <div>
        <strong>Please change your default password</strong>
        <p style="margin:6px 0 0;font-size:0.88rem;opacity:0.95">For security, set a new password before using the portal. Current default: <code><?php echo htmlspecialchars($defaultPass); ?></code></p>
    </div>
</div>
<?php endif; ?>

<div class="tp-card" style="max-width:480px">
    <div class="tp-card-head"><h3><i class="fas fa-lock"></i> <?php echo $required ? 'Set New Password' : 'Update Password'; ?></h3></div>
    <?php if ($message): ?><div class="tp-alert-success" style="margin-bottom:16px"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="tp-alert-error" style="margin-bottom:16px"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <form method="POST" class="tp-form-grid" style="grid-template-columns:1fr">
        <div class="tp-field">
            <label>Current Password</label>
            <input type="password" name="current_password" placeholder="<?php echo $required ? 'Enter default: ' . htmlspecialchars($defaultPass) : ''; ?>" required>
        </div>
        <div class="tp-field"><label>New Password</label><input type="password" name="new_password" minlength="6" placeholder="At least 6 characters" required></div>
        <div class="tp-field"><label>Confirm New Password</label><input type="password" name="confirm_password" minlength="6" required></div>
        <div><button type="submit" class="tp-btn tp-btn-primary"><i class="fas fa-save"></i> <?php echo $required ? 'Save & Continue' : 'Update Password'; ?></button></div>
    </form>
</div>

<?php require_once 'includes/layout_footer.php'; ?>
