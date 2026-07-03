<?php
$page_title = "Student Portal Accounts";
require_once 'includes/init.php';
require_once '../includes/db_connect.php';
require_once 'includes/erp_helpers.php';

ensureErpSchema($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enable_portal'])) {
    $id = (int) $_POST['student_id'];
    $pass = enableStudentPortal($pdo, $id, trim($_POST['password'] ?? '') ?: null);
    $stmt = $pdo->prepare("SELECT ad_no, name FROM students WHERE id = ?");
    $stmt->execute([$id]);
    $s = $stmt->fetch(PDO::FETCH_ASSOC);
    $adNo = $s ? $s['ad_no'] : '';
    $_SESSION['success_msg'] = "Portal enabled! Login at /portal/ — Admission No: {$adNo} — Password: {$pass}";
    header('Location: portal_accounts.php');
    exit;
}

require_once 'includes/header.php';
$enabled = $pdo->query("SELECT id, ad_no, name, class, portal_enabled FROM students WHERE portal_enabled = 1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$totalActive = (int) $pdo->query("SELECT COUNT(*) FROM students WHERE status='Active'")->fetchColumn();
$search = trim($_GET['q'] ?? '');
$results = [];
if ($search !== '') {
    $stmt = $pdo->prepare("SELECT id, ad_no, name, class, portal_enabled FROM students WHERE name LIKE ? OR ad_no LIKE ? LIMIT 15");
    $like = '%' . $search . '%';
    $stmt->execute([$like, $like]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<div class="content-top-bar">
    <div class="content-top-main">
        <div class="content-top-icon icon-blue"><i class="fas fa-laptop"></i></div>
        <div class="content-top-title">
            <h2>Student Portal</h2>
            <p class="content-top-breadcrumb">
                <a href="dashboard.php">Dashboard</a>
                <i class="fas fa-chevron-right"></i>
                <a href="students.php">Students</a>
                <i class="fas fa-chevron-right"></i>
                <span>Portal Access</span>
            </p>
        </div>
    </div>
    <div class="content-top-actions">
        <a href="homework.php" class="btn-header-action btn-header-outline"><i class="fas fa-book-open"></i> Homework</a>
        <a href="../portal/" target="_blank" class="btn-header-action btn-header-primary"><i class="fas fa-external-link-alt"></i> Open Portal</a>
    </div>
</div>

<div class="cls-stat-strip">
    <div class="cls-stat-card"><div class="cls-stat-icon cls-stat-green"><i class="fas fa-user-check"></i></div><div><span>Portal Enabled</span><strong><?php echo count($enabled); ?></strong></div></div>
    <div class="cls-stat-card"><div class="cls-stat-icon"><i class="fas fa-users"></i></div><div><span>Active Students</span><strong><?php echo $totalActive; ?></strong></div></div>
    <div class="cls-stat-card"><div class="cls-stat-icon cls-stat-blue"><i class="fas fa-link"></i></div><div><span>Portal URL</span><strong style="font-size:0.95rem">/portal/</strong></div></div>
</div>

<div class="form-section-card section-mb">
    <div class="section-card-header">
        <div class="section-card-icon section-icon-school"><i class="fas fa-key"></i></div>
        <div><h4>Enable Portal Login</h4><p>Leave password empty to auto-generate an 8-character password</p></div>
    </div>
    <form method="GET" class="category-add-row">
        <div class="form-field form-field-grow"><label>Search student</label><input type="text" name="q" class="form-input" value="<?php echo htmlspecialchars($search); ?>" placeholder="Name or admission number..."></div>
        <div class="form-field category-add-btn-wrap"><label>&nbsp;</label><button type="submit" class="btn-header-action btn-header-primary category-add-btn"><i class="fas fa-search"></i> Search</button></div>
    </form>
    <?php if ($search !== ''): ?>
    <div class="erp-search-results student-search-results">
        <?php if ($results): foreach ($results as $r): ?>
        <form method="POST" class="erp-search-item student-search-card student-portal-card">
            <input type="hidden" name="enable_portal" value="1">
            <input type="hidden" name="student_id" value="<?php echo $r['id']; ?>">
            <div class="student-search-main">
                <div class="student-search-avatar"><i class="fas fa-user-graduate"></i></div>
                <div class="student-search-info">
                    <strong><?php echo htmlspecialchars($r['name']); ?></strong>
                    <span><?php echo htmlspecialchars($r['ad_no']); ?></span>
                    <div class="student-search-meta">
                        <span class="student-search-class-pill"><i class="fas fa-school"></i> Class <?php echo htmlspecialchars($r['class']); ?></span>
                        <?php if ($r['portal_enabled']): ?><span class="status-badge badge-active">Portal Enabled</span><?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="student-search-actions">
                <span class="student-search-actions-label">Portal password</span>
                <input type="text" name="password" class="form-input student-portal-pass" placeholder="Auto if empty">
                <button type="submit" class="btn-header-action btn-header-primary btn-sm"><i class="fas fa-key"></i> <?php echo $r['portal_enabled'] ? 'Reset' : 'Enable'; ?></button>
            </div>
        </form>
        <?php endforeach; else: ?>
        <div class="tab-empty-state tab-empty-pad-sm"><div class="tab-empty-icon"><i class="fas fa-search"></i></div><h3>No students found</h3></div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<div class="table-container">
    <div class="table-toolbar"><strong>Portal Enabled (<?php echo count($enabled); ?>)</strong></div>
    <div class="table-wrapper">
        <table><thead><tr><th>Adm No</th><th>Name</th><th>Class</th><th>Status</th></tr></thead><tbody>
        <?php if ($enabled): foreach ($enabled as $e): ?>
        <tr><td><strong><?php echo htmlspecialchars($e['ad_no']); ?></strong></td><td><?php echo htmlspecialchars($e['name']); ?></td><td><?php echo htmlspecialchars($e['class']); ?></td><td><span class="status-badge badge-active">Active</span></td></tr>
        <?php endforeach; else: ?>
        <tr><td colspan="4" class="table-empty-cell">No students have portal access yet.</td></tr>
        <?php endif; ?>
        </tbody></table>
    </div>
</div>

<div class="notify-info-banner section-mb" style="margin-top:20px">
    <div class="notify-info-icon"><i class="fas fa-info-circle"></i></div>
    <div class="notify-info-text">
        <strong>Student portal URL:</strong> <code>/portal/</code> — Login with admission number + password.<br>
        Post homework from <a href="homework.php" class="teal-link">Homework module</a>.
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
