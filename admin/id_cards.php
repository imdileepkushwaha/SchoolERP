<?php
$page_title = "ID Cards";
require_once 'includes/init.php';
require_once '../includes/db_connect.php';
require_once 'includes/erp_helpers.php';

ensureErpSchema($pdo);
$class_options = getClassOptions($pdo);
$filterClass = trim((string) ($_GET['class'] ?? ''));
$filterSection = trim((string) ($_GET['section'] ?? ''));
$section_options = $filterClass !== '' ? getSectionOptions($pdo, $filterClass) : [];

$sql = "SELECT id, ad_no, name, class, section, roll, status FROM students WHERE status = 'Active'";
$params = [];
if ($filterClass !== '') {
    $sql .= " AND class = ?";
    $params[] = $filterClass;
}
if ($filterSection !== '') {
    $sql .= " AND section = ?";
    $params[] = $filterSection;
}
$sql .= " ORDER BY class, section, name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>
<div class="content-top-bar">
    <div class="content-top-main">
        <div class="content-top-icon"><i class="fas fa-id-card"></i></div>
        <div class="content-top-title">
            <h2>Student ID Cards</h2>
            <p class="content-top-breadcrumb">
                <a href="dashboard.php">Dashboard</a>
                <i class="fas fa-chevron-right"></i>
                <span>ID Cards</span>
            </p>
        </div>
    </div>
</div>

<div class="form-section-card section-mb">
    <form method="get" class="form-grid form-grid-3 form-grid-spaced">
        <div class="form-field">
            <label>Class</label>
            <select name="class" class="form-input form-select" onchange="this.form.submit()">
                <option value="">All classes</option>
                <?php foreach ($class_options as $c): ?>
                <option value="<?php echo htmlspecialchars($c); ?>" <?php echo $filterClass === $c ? 'selected' : ''; ?>><?php echo htmlspecialchars($c); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-field">
            <label>Section</label>
            <select name="section" class="form-input form-select" onchange="this.form.submit()" <?php echo $filterClass === '' ? 'disabled' : ''; ?>>
                <option value="">All sections</option>
                <?php foreach ($section_options as $sec): ?>
                <option value="<?php echo htmlspecialchars($sec); ?>" <?php echo $filterSection === $sec ? 'selected' : ''; ?>><?php echo htmlspecialchars($sec); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-field" style="align-self:end">
            <a href="id_cards.php" class="btn-header-action btn-header-outline">Reset</a>
        </div>
    </form>
</div>

<div class="table-container">
    <div class="table-toolbar">
        <strong><?php echo count($students); ?> student<?php echo count($students) === 1 ? '' : 's'; ?></strong>
        <?php if ($students): ?>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <button type="button" class="btn-header-action btn-header-outline btn-sm" id="idSelectAll">Select all</button>
            <button type="button" class="btn-header-action btn-header-primary btn-sm" id="idBulkPrint" disabled><i class="fas fa-print"></i> Print selected</button>
        </div>
        <?php endif; ?>
    </div>
    <div class="table-wrapper">
        <table>
            <thead><tr><th style="width:40px"><input type="checkbox" id="idCheckAll" title="Select all"></th><th>Adm No</th><th>Name</th><th>Class</th><th>Section</th><th>Roll</th><th></th></tr></thead>
            <tbody>
            <?php if (!$students): ?>
            <tr><td colspan="7">No active students match this filter.</td></tr>
            <?php else: foreach ($students as $s): ?>
            <tr>
                <td><input type="checkbox" class="id-bulk-check" value="<?php echo (int) $s['id']; ?>"></td>
                <td><code><?php echo htmlspecialchars($s['ad_no']); ?></code></td>
                <td><strong><?php echo htmlspecialchars($s['name']); ?></strong></td>
                <td><?php echo htmlspecialchars($s['class']); ?></td>
                <td><?php echo htmlspecialchars($s['section'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($s['roll'] ?? ''); ?></td>
                <td>
                    <a href="student_id_card.php?id=<?php echo (int) $s['id']; ?>" class="btn-header-action btn-header-primary" target="_blank" rel="noopener">
                        <i class="fas fa-print"></i> Print ID
                    </a>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
(function () {
    var checks = Array.prototype.slice.call(document.querySelectorAll('.id-bulk-check'));
    var all = document.getElementById('idCheckAll');
    var btn = document.getElementById('idBulkPrint');
    var selectAllBtn = document.getElementById('idSelectAll');
    function sync() {
        var n = checks.filter(function (c) { return c.checked; }).length;
        if (btn) btn.disabled = n === 0;
        if (all) all.checked = checks.length > 0 && n === checks.length;
    }
    checks.forEach(function (c) { c.addEventListener('change', sync); });
    if (all) {
        all.addEventListener('change', function () {
            checks.forEach(function (c) { c.checked = all.checked; });
            sync();
        });
    }
    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function () {
            checks.forEach(function (c) { c.checked = true; });
            sync();
        });
    }
    if (btn) {
        btn.addEventListener('click', function () {
            var ids = checks.filter(function (c) { return c.checked; }).map(function (c) { return c.value; });
            if (!ids.length) return;
            window.open('student_id_card.php?ids=' + encodeURIComponent(ids.join(',')), '_blank');
        });
    }
    sync();
})();
</script>
<?php require_once 'includes/footer.php'; ?>
