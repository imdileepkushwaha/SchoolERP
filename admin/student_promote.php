<?php
$page_title = "Promote Students";
require_once 'includes/init.php';
require_once '../includes/db_connect.php';
require_once 'includes/student_helpers.php';

ensureStudentSchema($pdo);

$class_options = getClassOptions($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['from_class'])) {
    $from_class = $_POST['from_class'];
    $to_class = getNextClass($pdo, $from_class);
    if (!$to_class) {
        $_SESSION['error_msg'] = 'No next class available for promotion.';
    } else {
        $stmt = $pdo->prepare("UPDATE students SET class=? WHERE class=? AND status='Active'");
        $stmt->execute([$to_class, $from_class]);
        $promoted = $stmt->rowCount();
        $_SESSION['success_msg'] = "$promoted student(s) promoted from $from_class to $to_class.";
        header('Location: student_promote.php');
        exit;
    }
}

require_once 'includes/header.php';

$class_counts = [];
foreach ($class_options as $cls) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE class=? AND status='Active'");
    $stmt->execute([$cls]);
    $class_counts[$cls] = (int) $stmt->fetchColumn();
}
?>
<div class="content-top-bar">
    <div class="content-top-main">
        <div class="content-top-icon icon-blue"><i class="fas fa-arrow-up"></i></div>
        <div class="content-top-title">
            <h2>Promote Students</h2>
            <p class="content-top-breadcrumb">
                <a href="dashboard.php">Dashboard</a>
                <i class="fas fa-chevron-right"></i>
                <a href="students.php">Students</a>
                <i class="fas fa-chevron-right"></i>
                <span>Promote</span>
            </p>
        </div>
    </div>
    <div class="content-top-actions">
        <a href="students.php" class="btn-header-action btn-header-outline"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
</div>

<div class="form-section-card section-mb">
    <div class="section-card-header">
        <div class="section-card-icon section-icon-school"><i class="fas fa-arrow-up"></i></div>
        <div><h4>Bulk Class Promotion</h4><p>Move all active students to the next class</p></div>
    </div>
    <form method="POST">
        <div class="form-grid form-grid-2 form-grid-spaced">
            <div class="form-field">
                <label>From Class</label>
                <select name="from_class" id="from_class" class="form-input form-select" required>
                    <option value="">Select class</option>
                    <?php foreach ($class_options as $cls):
                        $next = getNextClass($pdo, $cls);
                    ?>
                    <option value="<?php echo htmlspecialchars($cls); ?>" data-next="<?php echo htmlspecialchars($next ?? ''); ?>" data-count="<?php echo $class_counts[$cls]; ?>">
                        <?php echo htmlspecialchars($cls); ?> (<?php echo $class_counts[$cls]; ?> students)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label>To Class (auto)</label>
                <input type="text" id="to_class" class="form-input" readonly placeholder="Select source class first">
            </div>
        </div>
        <div class="form-actions-end">
            <button type="submit" class="btn-header-action btn-header-primary" onclick="return confirm('Promote all active students in this class?');"><i class="fas fa-arrow-up"></i> Promote Students</button>
        </div>
    </form>
</div>

<div class="table-container">
    <div class="table-toolbar"><strong>Class-wise Student Count</strong></div>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Class</th><th>Active Students</th><th>Next Class</th></tr></thead>
            <tbody>
                <?php foreach ($class_options as $cls): ?>
                <tr>
                    <td><?php echo htmlspecialchars($cls); ?></td>
                    <td><?php echo $class_counts[$cls]; ?></td>
                    <td><?php echo displayVal(getNextClass($pdo, $cls), 'Final class'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
document.getElementById('from_class').addEventListener('change', function () {
    var opt = this.options[this.selectedIndex];
    document.getElementById('to_class').value = opt.getAttribute('data-next') || 'No next class';
});
</script>
<?php require_once 'includes/footer.php'; ?>
