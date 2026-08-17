<?php
$page_title = "My Profile";
require_once 'includes/init.php';
require_once '../includes/db_connect.php';
require_once 'includes/settings_helpers.php';

ensureAdminAuthSchema($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_profile') {
    $name = trim((string) ($_POST['name'] ?? ''));
    if ($name === '') {
        $_SESSION['error_msg'] = 'Display name is required.';
    } else {
        $pdo->prepare('UPDATE admin_users SET name = ? WHERE id = ?')->execute([$name, (int) $_SESSION['admin_id']]);
        $_SESSION['admin_name'] = $name;
        adminLogActivity($pdo, 'profile_updated', 'Updated display name.');
        $_SESSION['success_msg'] = 'Profile saved.';
    }
    header('Location: profile.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM admin_users WHERE id = ?');
$stmt->execute([(int) $_SESSION['admin_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
$school = getSchoolProfile($pdo);

require_once 'includes/header.php';
$displayName = trim((string) ($user['name'] ?? '')) ?: (string) ($user['username'] ?? '');
?>
<div class="content-top-bar">
    <div class="content-top-main">
        <div class="content-top-icon icon-purple"><i class="fas fa-user"></i></div>
        <div class="content-top-title">
            <h2>My Profile</h2>
            <p class="content-top-breadcrumb">
                <a href="dashboard.php">Dashboard</a>
                <i class="fas fa-chevron-right"></i>
                <span>Profile</span>
            </p>
        </div>
    </div>
</div>

<div class="form-section-card settings-form-narrow">
    <div class="profile-card-head">
        <span class="header-user-avatar lg"><?php echo adminInitials($displayName); ?></span>
        <div>
            <h3><?php echo htmlspecialchars($displayName); ?></h3>
            <p><?php echo htmlspecialchars(adminRoleLabel((string) ($user['role'] ?? 'admin'))); ?> · <?php echo htmlspecialchars($school['email'] ?: 'No school email set'); ?></p>
        </div>
    </div>
    <form method="POST" class="settings-form">
        <input type="hidden" name="action" value="save_profile">
        <div class="form-grid form-grid-1">
            <div class="form-field">
                <label>Display name</label>
                <input type="text" name="name" class="form-input" value="<?php echo htmlspecialchars($displayName); ?>" required>
            </div>
            <div class="form-field">
                <label>Username</label>
                <input type="text" class="form-input" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" readonly>
            </div>
            <div class="form-field">
                <label>Role</label>
                <input type="text" class="form-input" value="<?php echo htmlspecialchars(adminRoleLabel((string) ($user['role'] ?? 'admin'))); ?>" readonly>
            </div>
        </div>
        <div class="settings-form-actions">
            <button type="submit" class="btn-header-action btn-header-primary"><i class="fas fa-save"></i> Save Profile</button>
            <a href="settings.php?tab=password" class="btn-header-action btn-header-outline"><i class="fas fa-lock"></i> Change Password</a>
        </div>
    </form>
</div>
<?php require_once 'includes/footer.php'; ?>
