<?php
$page_title = "Teacher Portal Accounts";
require_once 'includes/init.php';
require_once '../includes/db_connect.php';
require_once 'includes/teacher_helpers.php';

ensureTeacherSchema($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enable_portal'])) {
    $id = (int) $_POST['teacher_id'];
    $pass = enableTeacherPortal($pdo, $id, trim($_POST['password'] ?? '') ?: null);
    $t = getTeacherById($pdo, $id);
    $empId = $t ? $t['employee_id'] : '';
    $_SESSION['success_msg'] = "Portal enabled! Login at /teacher/ — Employee ID: {$empId} — Password: {$pass}" . ($pass === getTeacherPortalDefaultPassword() ? ' (default — teacher must change on first login)' : '');
    header('Location: teacher_portal_accounts.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enable_all_portal'])) {
    $count = enableAllTeachersPortal($pdo);
    $_SESSION['success_msg'] = "Portal enabled for {$count} active teacher(s). Default password: " . getTeacherPortalDefaultPassword();
    header('Location: teacher_portal_accounts.php');
    exit;
}

require_once 'includes/header.php';

$enabled = $pdo->query("SELECT id, employee_id, name, subject, portal_enabled FROM teachers WHERE portal_enabled = 1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$allTeachers = getAllTeachers($pdo, false);
$search = trim($_GET['q'] ?? '');
$results = [];
if ($search !== '') {
    $results = searchTeachers($pdo, $search, 20);
}
?>
<div class="content-top-bar">
    <div class="content-top-main">
        <div class="content-top-icon icon-purple"><i class="fas fa-laptop-code"></i></div>
        <div class="content-top-title">
            <h2>Teacher Portal Accounts</h2>
            <p class="content-top-breadcrumb">
                <a href="dashboard.php">Dashboard</a><i class="fas fa-chevron-right"></i>
                <a href="teachers.php">Teachers</a><i class="fas fa-chevron-right"></i>
                <span>Portal Access</span>
            </p>
        </div>
    </div>
    <div class="content-top-actions">
        <form method="POST" style="display:inline" onsubmit="return confirm('Enable portal for ALL active teachers with default password?');">
            <input type="hidden" name="enable_all_portal" value="1">
            <button type="submit" class="btn-header-action btn-header-outline"><i class="fas fa-users-cog"></i> Enable All</button>
        </form>
        <a href="../teacher/" target="_blank" class="btn-header-action btn-header-primary"><i class="fas fa-external-link-alt"></i> Open Teacher Portal</a>
    </div>
</div>

<div class="form-section-card section-mb">
    <div class="section-card-header">
        <div class="section-card-icon section-icon-school"><i class="fas fa-key"></i></div>
        <div><h4>Enable Portal Login</h4><p>Leave password empty to use default: <strong><?php echo htmlspecialchars(getTeacherPortalDefaultPassword()); ?></strong></p></div>
    </div>
    <form method="GET" class="category-add-row">
        <div class="form-field form-field-grow"><input type="text" name="q" class="form-input" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name, employee ID, subject..."></div>
        <button type="submit" class="btn-header-action btn-header-primary category-add-btn"><i class="fas fa-search"></i> Search</button>
    </form>
    <?php if ($search !== ''): ?>
    <div class="erp-search-results teacher-search-results" style="margin-top:16px">
        <?php if ($results): foreach ($results as $r): ?>
        <form method="POST" class="erp-search-item teacher-search-card" style="flex-wrap:wrap;gap:10px">
            <input type="hidden" name="enable_portal" value="1">
            <input type="hidden" name="teacher_id" value="<?php echo $r['id']; ?>">
            <div class="teacher-search-avatar"><i class="fas fa-user"></i></div>
            <div style="flex:1;min-width:180px">
                <strong><?php echo htmlspecialchars($r['name']); ?></strong>
                <span><?php echo htmlspecialchars($r['employee_id']); ?> · <?php echo htmlspecialchars($r['subject']); ?></span>
                <?php if ($r['portal_enabled'] ?? false): ?><span class="status-badge badge-active" style="margin-top:4px;display:inline-block">Enabled</span><?php endif; ?>
            </div>
            <input type="text" name="password" class="form-input" placeholder="Password (auto if empty)" style="max-width:160px">
            <button type="submit" class="btn-header-action btn-header-outline btn-sm"><?php echo !empty($r['portal_enabled']) ? 'Reset Password' : 'Enable'; ?></button>
        </form>
        <?php endforeach; else: ?>
        <div class="tab-empty-state tab-empty-pad-sm"><p>No teachers found.</p></div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php if (!$search && $allTeachers): ?>
<div class="form-section-card section-mb">
    <div class="section-card-header">
        <div class="section-card-icon section-icon-school"><i class="fas fa-users"></i></div>
        <div><h4>All Teachers</h4><p>Empty password = default <strong><?php echo htmlspecialchars(getTeacherPortalDefaultPassword()); ?></strong> (teacher changes on first login)</p></div>
    </div>
    <div class="table-container">
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Employee ID</th><th>Name</th><th>Subject</th><th>Status</th><th>Portal</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($allTeachers as $t): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($t['employee_id']); ?></strong></td>
                    <td><?php echo htmlspecialchars($t['name']); ?></td>
                    <td><?php echo htmlspecialchars($t['subject']); ?></td>
                    <td><span class="status-badge <?php echo $t['status'] === 'Active' ? 'badge-active' : 'badge-inactive'; ?>"><?php echo htmlspecialchars($t['status']); ?></span></td>
                    <td><?php echo !empty($t['portal_enabled']) ? '<span class="status-badge badge-active">Enabled</span>' : '<span class="status-badge badge-inactive">Off</span>'; ?></td>
                    <td>
                        <form method="POST" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                            <input type="hidden" name="enable_portal" value="1">
                            <input type="hidden" name="teacher_id" value="<?php echo $t['id']; ?>">
                            <input type="text" name="password" class="form-input" placeholder="Password (optional)" style="max-width:140px;padding:6px 10px;font-size:0.85rem">
                            <button type="submit" class="btn-header-action btn-header-outline btn-sm"><?php echo !empty($t['portal_enabled']) ? 'Reset' : 'Enable'; ?></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="table-container">
    <div class="table-toolbar"><strong>Portal Enabled (<?php echo count($enabled); ?>)</strong></div>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Employee ID</th><th>Name</th><th>Subject</th><th>Portal</th></tr></thead>
            <tbody>
            <?php if ($enabled): foreach ($enabled as $e): ?>
            <tr>
                <td><?php echo htmlspecialchars($e['employee_id']); ?></td>
                <td><?php echo htmlspecialchars($e['name']); ?></td>
                <td><?php echo htmlspecialchars($e['subject']); ?></td>
                <td><span class="status-badge badge-active">Active</span></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:24px">No teachers have portal access yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="notify-info-banner section-mb" style="margin-top:20px">
    <div class="notify-info-icon"><i class="fas fa-info-circle"></i></div>
    <div class="notify-info-text">
        <strong>Teacher portal URL:</strong> <code>/teacher/</code><br>
        <strong>Default login password:</strong> <code><?php echo htmlspecialchars(getTeacherPortalDefaultPassword()); ?></code><br>
        Active teachers can login with Employee ID + default password — portal auto-enables on first login.<br>
        Teachers can view timetable, mark attendance, post homework, and update their profile after login.
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
