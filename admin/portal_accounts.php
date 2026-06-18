<?php
$page_title = "Student Portal Accounts";
require_once 'includes/init.php';
require_once '../includes/db_connect.php';
require_once 'includes/erp_helpers.php';

ensureErpSchema($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['enable_portal'])) {
        $id = (int) $_POST['student_id'];
        $pass = enableStudentPortal($pdo, $id, trim($_POST['password'] ?? '') ?: null);
        $_SESSION['success_msg'] = "Portal enabled. Login: student admission no, password: $pass";
    } elseif (isset($_POST['add_homework'])) {
        $pdo->prepare("INSERT INTO homework (class_name, section_name, title, description, due_date, session_id) VALUES (?,?,?,?,?,?)")
            ->execute([
                trim($_POST['class_name']), trim($_POST['section_name'] ?? 'A'),
                trim($_POST['title']), trim($_POST['description'] ?? ''),
                $_POST['due_date'] ?: null, getCurrentSession($pdo)['id'] ?? null,
            ]);
        $_SESSION['success_msg'] = 'Homework posted.';
    }
    header('Location: portal_accounts.php');
    exit;
}

require_once 'includes/header.php';
$class_options = getClassOptions($pdo);
$enabled = $pdo->query("SELECT id, ad_no, name, class, portal_enabled FROM students WHERE portal_enabled = 1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$homework = $pdo->query("SELECT * FROM homework ORDER BY id DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
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
        <div class="content-top-title"><h2>Student Portal</h2><p class="content-top-breadcrumb"><a href="dashboard.php">Dashboard</a><i class="fas fa-chevron-right"></i><span>Portal</span></p></div>
    </div>
    <div class="content-top-actions"><a href="../portal/" target="_blank" class="btn-header-action btn-header-primary"><i class="fas fa-external-link-alt"></i> Open Portal</a></div>
</div>

<div class="form-section-card section-mb">
    <h4>Enable Portal Login</h4>
    <form method="GET" class="category-add-row"><div class="form-field form-field-grow"><input type="text" name="q" class="form-input" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search student"></div><button type="submit" class="btn-header-action btn-header-outline">Search</button></form>
    <?php foreach ($results as $r): ?>
    <form method="POST" class="erp-cert-issue-row">
        <input type="hidden" name="enable_portal" value="1">
        <input type="hidden" name="student_id" value="<?php echo $r['id']; ?>">
        <span><?php echo htmlspecialchars($r['name']); ?> (<?php echo htmlspecialchars($r['ad_no']); ?>) <?php echo $r['portal_enabled'] ? '✓ Enabled' : ''; ?></span>
        <input type="text" name="password" class="form-input" placeholder="Password (auto if empty)">
        <button type="submit" class="btn-header-action btn-header-outline btn-sm"><?php echo $r['portal_enabled'] ? 'Reset' : 'Enable'; ?></button>
    </form>
    <?php endforeach; ?>
</div>

<div class="form-section-card section-mb">
    <h4>Post Homework (visible in portal)</h4>
    <form method="POST" class="category-add-row erp-filter-row-4">
        <input type="hidden" name="add_homework" value="1">
        <div class="form-field"><select name="class_name" class="form-input form-select" required><?php foreach ($class_options as $c): ?><option><?php echo htmlspecialchars($c); ?></option><?php endforeach; ?></select></div>
        <div class="form-field"><input type="text" name="section_name" class="form-input" value="A" placeholder="Section"></div>
        <div class="form-field"><input type="text" name="title" class="form-input" placeholder="Title" required></div>
        <div class="form-field"><input type="date" name="due_date" class="form-input"></div>
        <div class="form-field form-field-full"><textarea name="description" class="form-input form-textarea" rows="2" placeholder="Description"></textarea></div>
        <button type="submit" class="btn-header-action btn-header-primary">Post Homework</button>
    </form>
</div>

<div class="details-grid">
    <div class="table-container"><div class="table-toolbar"><strong>Portal Enabled (<?php echo count($enabled); ?>)</strong></div>
    <table><thead><tr><th>Adm No</th><th>Name</th><th>Class</th></tr></thead><tbody>
    <?php foreach ($enabled as $e): ?><tr><td><?php echo htmlspecialchars($e['ad_no']); ?></td><td><?php echo htmlspecialchars($e['name']); ?></td><td><?php echo htmlspecialchars($e['class']); ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <div class="table-container"><div class="table-toolbar"><strong>Recent Homework</strong></div>
    <table><thead><tr><th>Class</th><th>Title</th><th>Due</th></tr></thead><tbody>
    <?php foreach ($homework as $h): ?><tr><td><?php echo htmlspecialchars($h['class_name'] . ' (' . $h['section_name'] . ')'); ?></td><td><?php echo htmlspecialchars($h['title']); ?></td><td><?php echo displayVal($h['due_date']); ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
</div>
<?php require_once 'includes/footer.php'; ?>
