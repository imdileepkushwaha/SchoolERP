<?php
$page_title = "Collect Fee";
require_once 'includes/init.php';
require_once '../includes/db_connect.php';
require_once 'includes/erp_helpers.php';

ensureErpSchema($pdo);
$session = getCurrentSession($pdo);
$heads = getFeeHeads($pdo);
$student = null;
$feeSummary = null;
$studentId = (int) ($_GET['student_id'] ?? $_POST['student_id'] ?? 0);

if ($studentId) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$studentId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($student) {
        $feeSummary = getStudentFeeSummary($pdo, $studentId);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['collect_fee']) && $student) {
    $amount = (float) ($_POST['amount'] ?? 0);
    $headId = (int) ($_POST['fee_head_id'] ?? 0) ?: null;
    $method = trim($_POST['payment_method'] ?? 'Cash');
    $remarks = trim($_POST['remarks'] ?? '');
    if ($amount > 0) {
        $receipt = generateReceiptNo($pdo);
        $pdo->prepare(
            "INSERT INTO fee_payments (student_id, fee_head_id, amount, payment_date, payment_method, receipt_no, session_id, remarks) VALUES (?,?,?,CURDATE(),?,?,?,?)"
        )->execute([$studentId, $headId, $amount, $method, $receipt, $session['id'] ?? null, $remarks]);
        $_SESSION['success_msg'] = 'Fee collected. Receipt: ' . $receipt;
        header('Location: fee_receipt.php?id=' . $pdo->lastInsertId());
        exit;
    }
    $_SESSION['error_msg'] = 'Enter a valid amount.';
    header('Location: fee_collect.php?student_id=' . $studentId);
    exit;
}

require_once 'includes/header.php';
$search = trim($_GET['q'] ?? '');
$searchResults = [];
if ($search !== '') {
    $stmt = $pdo->prepare("SELECT id, ad_no, name, class, roll FROM students WHERE status='Active' AND (name LIKE ? OR ad_no LIKE ? OR roll LIKE ?) LIMIT 20");
    $like = '%' . $search . '%';
    $stmt->execute([$like, $like, $like]);
    $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<div class="content-top-bar">
    <div class="content-top-main">
        <div class="content-top-icon icon-orange"><i class="fas fa-money-bill-wave"></i></div>
        <div class="content-top-title">
            <h2>Collect Fee</h2>
            <p class="content-top-breadcrumb"><a href="fees.php">Fees</a><i class="fas fa-chevron-right"></i><span>Collect</span></p>
        </div>
    </div>
    <div class="content-top-actions"><a href="fees.php" class="btn-header-action btn-header-outline"><i class="fas fa-arrow-left"></i> Fee Structure</a></div>
</div>

<div class="form-section-card section-mb">
    <form method="GET" class="category-add-row">
        <div class="form-field form-field-grow"><label>Search Student</label><input type="text" name="q" class="form-input" placeholder="Name, Admission No, or Roll" value="<?php echo htmlspecialchars($search); ?>"></div>
        <div class="form-field category-add-btn-wrap"><label>&nbsp;</label><button type="submit" class="btn-header-action btn-header-primary category-add-btn"><i class="fas fa-search"></i> Search</button></div>
    </form>
    <?php if ($searchResults): ?>
    <div class="erp-search-results">
        <?php foreach ($searchResults as $r): ?>
        <a href="fee_collect.php?student_id=<?php echo $r['id']; ?>" class="erp-search-item">
            <?php echo htmlspecialchars($r['name']); ?> — <?php echo htmlspecialchars($r['ad_no']); ?> (<?php echo htmlspecialchars($r['class']); ?>)
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php if ($student && $feeSummary): ?>
<div class="details-grid">
    <div class="form-section-card form-section-flush">
        <div class="section-card-header"><div class="section-card-icon section-icon-school"><i class="fas fa-user"></i></div><div><h4><?php echo htmlspecialchars($student['name']); ?></h4><p><?php echo htmlspecialchars($student['ad_no']); ?> · Class <?php echo htmlspecialchars($student['class']); ?></p></div></div>
        <div class="erp-fee-summary">
            <div><span>Total Due</span><strong>Rs. <?php echo number_format($feeSummary['total_due'], 2); ?></strong></div>
            <div><span>Paid</span><strong>Rs. <?php echo number_format($feeSummary['total_paid'], 2); ?></strong></div>
            <div><span>Balance</span><strong class="text-danger">Rs. <?php echo number_format($feeSummary['balance'], 2); ?></strong></div>
        </div>
    </div>
    <div class="form-section-card form-section-flush">
        <form method="POST">
            <input type="hidden" name="collect_fee" value="1">
            <input type="hidden" name="student_id" value="<?php echo $studentId; ?>">
            <div class="form-grid form-grid-1">
                <div class="form-field"><label>Fee Head</label><select name="fee_head_id" class="form-input form-select"><option value="">General / Mixed</option><?php foreach ($heads as $h): ?><option value="<?php echo $h['id']; ?>"><?php echo htmlspecialchars($h['name']); ?></option><?php endforeach; ?></select></div>
                <div class="form-field"><label>Amount (Rs.)</label><input type="number" step="0.01" min="0.01" name="amount" class="form-input" required></div>
                <div class="form-field"><label>Payment Method</label><select name="payment_method" class="form-input form-select"><option>Cash</option><option>UPI</option><option>Card</option><option>Bank Transfer</option><option>Cheque</option></select></div>
                <div class="form-field"><label>Remarks</label><input type="text" name="remarks" class="form-input"></div>
            </div>
            <div class="form-actions-end"><button type="submit" class="btn-header-action btn-header-primary"><i class="fas fa-receipt"></i> Collect & Print Receipt</button></div>
        </form>
    </div>
</div>

<?php if ($feeSummary['payments']): ?>
<div class="table-container section-mb">
    <div class="table-toolbar"><strong>Payment History</strong></div>
    <div class="table-wrapper">
        <table><thead><tr><th>Date</th><th>Receipt</th><th>Head</th><th>Amount</th><th>Method</th><th></th></tr></thead><tbody>
        <?php foreach ($feeSummary['payments'] as $p): ?>
        <tr>
            <td><?php echo htmlspecialchars($p['payment_date']); ?></td>
            <td><?php echo htmlspecialchars($p['receipt_no']); ?></td>
            <td><?php echo displayVal($p['head_name'], 'General'); ?></td>
            <td>Rs. <?php echo number_format($p['amount'], 2); ?></td>
            <td><?php echo htmlspecialchars($p['payment_method']); ?></td>
            <td><a href="fee_receipt.php?id=<?php echo $p['id']; ?>" class="teal-link" target="_blank">Receipt</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody></table>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>
<?php require_once 'includes/footer.php'; ?>
