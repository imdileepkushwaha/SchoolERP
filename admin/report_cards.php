<?php
$page_title = "Report Cards";
require_once 'includes/init.php';
require_once '../includes/db_connect.php';
require_once 'includes/erp_helpers.php';

ensureErpSchema($pdo);
$exams = $pdo->query("SELECT * FROM exams ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$examId = (int) ($_GET['exam_id'] ?? 0);
if ($examId <= 0 && $exams) {
    $examId = (int) $exams[0]['id'];
}
$exam = null;
foreach ($exams as $row) {
    if ((int) $row['id'] === $examId) {
        $exam = $row;
        break;
    }
}
$students = [];
if ($exam) {
    $stmt = $pdo->prepare("SELECT id, ad_no, name, class, section, roll FROM students WHERE status = 'Active' AND class = ? ORDER BY section, name");
    $stmt->execute([$exam['class_name']]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

require_once 'includes/header.php';
?>
<div class="content-top-bar">
    <div class="content-top-main">
        <div class="content-top-icon icon-purple"><i class="fas fa-file-alt"></i></div>
        <div class="content-top-title">
            <h2>Report Cards</h2>
            <p class="content-top-breadcrumb">
                <a href="dashboard.php">Dashboard</a>
                <i class="fas fa-chevron-right"></i>
                <span>Report Cards</span>
            </p>
        </div>
    </div>
    <div class="content-top-actions">
        <a href="exams.php" class="btn-header-action btn-header-outline"><i class="fas fa-list"></i> Manage Exams</a>
        <a href="marks.php" class="btn-header-action btn-header-primary"><i class="fas fa-pen"></i> Enter Marks</a>
    </div>
</div>

<div class="form-section-card section-mb">
    <form method="get" class="form-grid form-grid-2 form-grid-spaced">
        <div class="form-field">
            <label>Exam</label>
            <select name="exam_id" class="form-input form-select" onchange="this.form.submit()">
                <?php if (!$exams): ?>
                <option value="">No exams created</option>
                <?php else: foreach ($exams as $ex): ?>
                <option value="<?php echo (int) $ex['id']; ?>" <?php echo (int) $ex['id'] === $examId ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($ex['name'] . ' · ' . $ex['class_name']); ?>
                </option>
                <?php endforeach; endif; ?>
            </select>
        </div>
        <?php if ($exam): ?>
        <div class="form-field">
            <label>Class</label>
            <input type="text" class="form-input" value="<?php echo htmlspecialchars($exam['class_name']); ?>" readonly>
        </div>
        <?php endif; ?>
    </form>
</div>

<div class="table-container">
    <div class="table-toolbar">
        <strong><?php echo $exam ? count($students) . ' students in ' . htmlspecialchars($exam['class_name']) : 'Select an exam'; ?></strong>
        <?php if ($exam && $students): ?>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <button type="button" class="btn-header-action btn-header-outline btn-sm" id="rcSelectAll">Select all</button>
            <button type="button" class="btn-header-action btn-header-primary btn-sm" id="rcBulkPrint" disabled data-exam="<?php echo (int) $examId; ?>"><i class="fas fa-print"></i> Print selected</button>
        </div>
        <?php endif; ?>
    </div>
    <div class="table-wrapper">
        <table>
            <thead><tr><th style="width:40px"><input type="checkbox" id="rcCheckAll" title="Select all"></th><th>Adm No</th><th>Name</th><th>Section</th><th>Roll</th><th></th></tr></thead>
            <tbody>
            <?php if (!$exam): ?>
            <tr><td colspan="6">Create an exam first, then print report cards from here.</td></tr>
            <?php elseif (!$students): ?>
            <tr><td colspan="6">No active students in <?php echo htmlspecialchars($exam['class_name']); ?>.</td></tr>
            <?php else: foreach ($students as $s): ?>
            <tr>
                <td><input type="checkbox" class="rc-bulk-check" value="<?php echo (int) $s['id']; ?>"></td>
                <td><code><?php echo htmlspecialchars($s['ad_no']); ?></code></td>
                <td><strong><?php echo htmlspecialchars($s['name']); ?></strong></td>
                <td><?php echo htmlspecialchars($s['section'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($s['roll'] ?? ''); ?></td>
                <td>
                    <a href="report_card.php?student_id=<?php echo (int) $s['id']; ?>&amp;exam_id=<?php echo (int) $examId; ?>" class="btn-header-action btn-header-primary" target="_blank" rel="noopener">
                        <i class="fas fa-print"></i> Report Card
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
    var checks = Array.prototype.slice.call(document.querySelectorAll('.rc-bulk-check'));
    var all = document.getElementById('rcCheckAll');
    var btn = document.getElementById('rcBulkPrint');
    var selectAllBtn = document.getElementById('rcSelectAll');
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
            var examId = btn.getAttribute('data-exam');
            if (!ids.length || !examId) return;
            window.open('report_card.php?exam_id=' + encodeURIComponent(examId) + '&ids=' + encodeURIComponent(ids.join(',')), '_blank');
        });
    }
    sync();
})();
</script>
<?php require_once 'includes/footer.php'; ?>
