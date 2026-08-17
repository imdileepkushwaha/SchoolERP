<?php
$page_title = "Staff Salary";
require_once 'includes/init.php';
require_once '../includes/db_connect.php';
require_once 'includes/erp_helpers.php';

ensureErpSchema($pdo);
ensureAdminAuthSchema($pdo);

if (!adminCanSalary($pdo)) {
    $_SESSION['error_msg'] = 'You do not have access to salary.';
    header('Location: dashboard.php');
    exit;
}

$month = (string) ($_GET['month'] ?? $_POST['pay_month'] ?? date('Y-m'));
if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    $month = date('Y-m');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $teacherId = (int) ($_POST['teacher_id'] ?? 0);
    $payMonth = preg_match('/^\d{4}-\d{2}$/', (string) ($_POST['pay_month'] ?? '')) ? $_POST['pay_month'] : $month;
    if ($action === 'mark_paid' && $teacherId > 0) {
        $amount = (float) ($_POST['amount'] ?? 0);
        $remarks = trim((string) ($_POST['remarks'] ?? ''));
        $pdo->prepare(
            "INSERT INTO teacher_salary_payments (teacher_id, pay_month, amount, paid_on, remarks)
             VALUES (?,?,?,?,?)
             ON DUPLICATE KEY UPDATE amount = VALUES(amount), paid_on = VALUES(paid_on), remarks = VALUES(remarks)"
        )->execute([$teacherId, $payMonth, $amount, date('Y-m-d'), $remarks]);
        adminLogActivity($pdo, 'salary_paid', 'Teacher #' . $teacherId . ' · ' . $payMonth);
        $_SESSION['success_msg'] = 'Salary marked as paid.';
    } elseif ($action === 'unmark_paid' && $teacherId > 0) {
        $pdo->prepare('DELETE FROM teacher_salary_payments WHERE teacher_id = ? AND pay_month = ?')
            ->execute([$teacherId, $payMonth]);
        adminLogActivity($pdo, 'salary_unpaid', 'Teacher #' . $teacherId . ' · ' . $payMonth);
        $_SESSION['success_msg'] = 'Payment removed.';
    }
    header('Location: teacher_salary.php?month=' . urlencode($payMonth));
    exit;
}

$teachers = $pdo->query("SELECT id, name, employee_id, subject, salary, status FROM teachers WHERE status = 'Active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$paidStmt = $pdo->prepare('SELECT * FROM teacher_salary_payments WHERE pay_month = ?');
$paidStmt->execute([$month]);
$paidMap = [];
foreach ($paidStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $paidMap[(int) $row['teacher_id']] = $row;
}
$paidCount = count($paidMap);
$paidTotal = 0;
foreach ($paidMap as $row) {
    $paidTotal += (float) $row['amount'];
}

require_once 'includes/header.php';
$monthLabel = date('F Y', strtotime($month . '-01'));
?>
<div class="content-top-bar">
    <div class="content-top-main">
        <div class="content-top-icon icon-teal"><i class="fas fa-money-check-alt"></i></div>
        <div class="content-top-title">
            <h2>Staff Salary</h2>
            <p class="content-top-breadcrumb">
                <a href="dashboard.php">Dashboard</a>
                <i class="fas fa-chevron-right"></i>
                <span><?php echo htmlspecialchars($monthLabel); ?></span>
            </p>
        </div>
    </div>
</div>

<div class="cls-stat-strip">
    <div class="cls-stat-card"><div class="cls-stat-icon"><i class="fas fa-users"></i></div><div><span>Active staff</span><strong><?php echo count($teachers); ?></strong></div></div>
    <div class="cls-stat-card"><div class="cls-stat-icon cls-stat-green"><i class="fas fa-check"></i></div><div><span>Paid this month</span><strong><?php echo $paidCount; ?></strong></div></div>
    <div class="cls-stat-card"><div class="cls-stat-icon cls-stat-blue"><i class="fas fa-rupee-sign"></i></div><div><span>Paid total</span><strong>₹<?php echo number_format($paidTotal, 0); ?></strong></div></div>
</div>

<div class="form-section-card section-mb">
    <form method="get" class="form-grid form-grid-3">
        <div class="form-field">
            <label>Pay month</label>
            <input type="month" name="month" class="form-input" value="<?php echo htmlspecialchars($month); ?>" onchange="this.form.submit()">
        </div>
    </form>
</div>

<div class="table-container">
    <div class="table-toolbar"><strong>Mark monthly salary as paid</strong></div>
    <div class="table-wrapper">
        <table class="salary-table">
            <thead><tr><th>Staff</th><th>Employee ID</th><th>Subject</th><th>Monthly salary</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php if (!$teachers): ?>
            <tr><td colspan="6">No active teachers found.</td></tr>
            <?php else: foreach ($teachers as $t):
                $paid = $paidMap[(int) $t['id']] ?? null;
                $amount = $paid ? (float) $paid['amount'] : (float) ($t['salary'] ?? 0);
            ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($t['name']); ?></strong></td>
                <td><?php echo htmlspecialchars($t['employee_id'] ?? '—'); ?></td>
                <td><?php echo htmlspecialchars($t['subject'] ?? '—'); ?></td>
                <td>₹<?php echo number_format((float) ($t['salary'] ?? 0), 0); ?></td>
                <td>
                    <?php if ($paid): ?>
                    <span class="status-badge badge-active">Paid <?php echo date('d M', strtotime($paid['paid_on'] ?: 'now')); ?></span>
                    <?php else: ?>
                    <span class="status-badge badge-inactive">Unpaid</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($paid): ?>
                    <form method="POST" onsubmit="return confirm('Remove this payment record?');">
                        <input type="hidden" name="action" value="unmark_paid">
                        <input type="hidden" name="teacher_id" value="<?php echo (int) $t['id']; ?>">
                        <input type="hidden" name="pay_month" value="<?php echo htmlspecialchars($month); ?>">
                        <button type="submit" class="btn-header-action btn-header-outline">Undo</button>
                    </form>
                    <?php else: ?>
                    <form method="POST" class="salary-pay-form">
                        <input type="hidden" name="action" value="mark_paid">
                        <input type="hidden" name="teacher_id" value="<?php echo (int) $t['id']; ?>">
                        <input type="hidden" name="pay_month" value="<?php echo htmlspecialchars($month); ?>">
                        <input type="number" step="0.01" name="amount" class="form-input" value="<?php echo htmlspecialchars((string) $amount); ?>" style="width:110px">
                        <input type="text" name="remarks" class="form-input" placeholder="Remarks" style="width:140px">
                        <button type="submit" class="btn-header-action btn-header-primary">Mark paid</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
