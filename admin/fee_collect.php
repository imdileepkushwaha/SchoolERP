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
    if ($headId) {
        $headStmt = $pdo->prepare("SELECT name FROM fee_heads WHERE id = ?");
        $headStmt->execute([$headId]);
        $headName = $headStmt->fetchColumn();
        if ($headName && !feeHeadAppliesToStudent($pdo, $studentId, $headName)) {
            $_SESSION['error_msg'] = 'This fee head is not applicable — assign hostel or transport to the student first.';
            header('Location: fee_collect.php?student_id=' . $studentId);
            exit;
        }
    }
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
    $stmt = $pdo->prepare("SELECT id, ad_no, name, class, section, roll FROM students WHERE status='Active' AND (name LIKE ? OR ad_no LIKE ? OR roll LIKE ?) LIMIT 20");
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
    <?php if ($search !== ''): ?>
    <div class="student-search-results-head">
        <span><i class="fas fa-search"></i> <?php echo count($searchResults); ?> result<?php echo count($searchResults) === 1 ? '' : 's'; ?> for &ldquo;<?php echo htmlspecialchars($search); ?>&rdquo;</span>
        <?php if ($searchResults): ?><small>Select a student to collect fee</small><?php endif; ?>
    </div>
    <?php endif; ?>
    <?php if ($searchResults): ?>
    <div class="erp-search-results student-search-results">
        <?php foreach ($searchResults as $r):
            $initials = '';
            foreach (preg_split('/\s+/', trim($r['name'])) as $part) {
                if ($part !== '') {
                    $initials .= strtoupper($part[0]);
                }
            }
            $initials = substr($initials, 0, 2) ?: 'S';
            $section = trim($r['section'] ?? '') ?: 'A';
        ?>
        <a href="fee_collect.php?student_id=<?php echo $r['id']; ?>" class="erp-search-item student-search-card student-search-link student-search-fee">
            <div class="student-search-main">
                <div class="student-search-avatar is-initials"><?php echo htmlspecialchars($initials); ?></div>
                <div class="student-search-info">
                    <strong><?php echo htmlspecialchars($r['name']); ?></strong>
                    <div class="student-search-id-row">
                        <span class="student-search-id-chip"><i class="fas fa-id-card"></i> <?php echo htmlspecialchars($r['ad_no']); ?></span>
                        <span class="student-search-id-chip"><i class="fas fa-hashtag"></i> Roll <?php echo htmlspecialchars($r['roll']); ?></span>
                    </div>
                    <div class="student-search-meta">
                        <span class="student-search-class-pill"><i class="fas fa-school"></i> Class <?php echo htmlspecialchars($r['class']); ?> (<?php echo htmlspecialchars($section); ?>)</span>
                    </div>
                </div>
            </div>
            <div class="student-search-link-action">
                <span class="student-search-link-label">Collect Fee</span>
                <span class="student-search-go"><i class="fas fa-money-bill-wave"></i></span>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php elseif ($search !== ''): ?>
    <div class="student-search-empty">
        <div class="student-search-empty-icon"><i class="fas fa-user-slash"></i></div>
        <h4>No students found</h4>
        <p>Try a different name, admission number, or roll number.</p>
    </div>
    <?php endif; ?>
</div>

<?php if ($student && $feeSummary):
    $initials = '';
    foreach (preg_split('/\s+/', trim($student['name'])) as $part) {
        if ($part !== '') {
            $initials .= strtoupper($part[0]);
        }
    }
    $initials = substr($initials, 0, 2) ?: 'S';
    $section = trim($student['section'] ?? '') ?: 'A';
    $paidPct = $feeSummary['total_due'] > 0
        ? min(100, (int) round(($feeSummary['total_paid'] / $feeSummary['total_due']) * 100))
        : ($feeSummary['total_paid'] > 0 ? 100 : 0);
    $isCleared = ($feeSummary['fee_status'] ?? '') === 'cleared';
    $noFeeStructure = ($feeSummary['fee_status'] ?? '') === 'no_structure';
    $paymentCount = count($feeSummary['payments']);
    $applicableHeads = $feeSummary['fee_items'] ?? [];
?>
<div class="fc-student-hero">
    <div class="fc-student-hero-main">
        <div class="fc-student-avatar"><?php echo htmlspecialchars($initials); ?></div>
        <div>
            <p class="fc-student-hero-label"><i class="fas fa-user-graduate"></i> Collecting fee for</p>
            <h3><?php echo htmlspecialchars($student['name']); ?></h3>
            <div class="fc-student-hero-chips">
                <span class="fc-student-chip"><i class="fas fa-id-card"></i> <?php echo htmlspecialchars($student['ad_no']); ?></span>
                <span class="fc-student-chip"><i class="fas fa-hashtag"></i> Roll <?php echo htmlspecialchars($student['roll']); ?></span>
                <span class="fc-student-chip"><i class="fas fa-school"></i> Class <?php echo htmlspecialchars($student['class']); ?> (<?php echo htmlspecialchars($section); ?>)</span>
                <?php if ($isCleared): ?>
                <span class="fc-student-chip is-success"><i class="fas fa-check-circle"></i> Fully paid</span>
                <?php elseif ($noFeeStructure): ?>
                <span class="fc-student-chip is-warning"><i class="fas fa-info-circle"></i> No fee assigned</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="fc-student-hero-actions">
        <a href="fee_collect.php" class="fc-hero-btn"><i class="fas fa-search"></i> Change Student</a>
        <a href="student_view.php?id=<?php echo (int) $studentId; ?>" class="fc-hero-btn is-solid"><i class="fas fa-user"></i> View Profile</a>
    </div>
</div>

<div class="fc-fee-stat-strip">
    <div class="fc-fee-stat is-due">
        <div class="fc-fee-stat-icon"><i class="fas fa-file-invoice-dollar"></i></div>
        <div><span>Total Due</span><strong>₹<?php echo number_format($feeSummary['total_due'], 2); ?></strong></div>
    </div>
    <div class="fc-fee-stat is-paid">
        <div class="fc-fee-stat-icon"><i class="fas fa-check-circle"></i></div>
        <div><span>Paid</span><strong>₹<?php echo number_format($feeSummary['total_paid'], 2); ?></strong></div>
    </div>
    <div class="fc-fee-stat is-balance<?php echo $isCleared ? ' is-clear' : ''; ?>">
        <div class="fc-fee-stat-icon"><i class="fas fa-<?php echo $isCleared ? 'smile' : 'wallet'; ?>"></i></div>
        <div><span>Balance</span><strong>₹<?php echo number_format($feeSummary['balance'], 2); ?></strong></div>
    </div>
    <div class="fc-fee-stat is-history">
        <div class="fc-fee-stat-icon"><i class="fas fa-receipt"></i></div>
        <div><span>Payments</span><strong><?php echo $paymentCount; ?></strong></div>
    </div>
</div>

<div class="fc-collect-layout">
    <div class="form-section-card fc-summary-panel">
        <div class="fc-summary-head">
            <h4><i class="fas fa-chart-pie"></i> Fee Overview</h4>
            <span class="fc-paid-badge"><?php echo $paidPct; ?>% paid</span>
        </div>
        <div class="fc-progress-wrap">
            <div class="fc-progress-bar"><div class="fc-progress-fill" style="width:<?php echo $paidPct; ?>%"></div></div>
            <div class="fc-progress-labels">
                <span>Paid ₹<?php echo number_format($feeSummary['total_paid'], 0); ?></span>
                <span>Due ₹<?php echo number_format($feeSummary['total_due'], 0); ?></span>
            </div>
        </div>
        <?php if ($isCleared): ?>
        <div class="fc-cleared-note"><i class="fas fa-check-circle"></i> All fees cleared for this student.</div>
        <?php elseif ($noFeeStructure): ?>
        <div class="fc-no-structure-note"><i class="fas fa-exclamation-triangle"></i> No fee structure for <strong><?php echo htmlspecialchars($student['class']); ?></strong>. <a href="fees.php">Set up fees</a> or collect an advance payment below.</div>
        <?php else: ?>
        <div class="fc-balance-alert"><i class="fas fa-exclamation-circle"></i> Outstanding balance: <strong>₹<?php echo number_format($feeSummary['balance'], 2); ?></strong></div>
        <?php endif; ?>
        <?php if ($session): ?>
        <p class="fc-session-note"><i class="fas fa-calendar-alt"></i> Session: <?php echo htmlspecialchars($session['name']); ?></p>
        <?php endif; ?>
        <?php if ($applicableHeads): ?>
        <div class="fc-fee-breakdown">
            <p class="fc-fee-breakdown-title">Applicable fees</p>
            <?php foreach ($applicableHeads as $item): ?>
            <div class="fc-fee-breakdown-row">
                <span><?php echo htmlspecialchars($item['head_name']); ?></span>
                <strong>₹<?php echo number_format($item['amount'], 0); ?></strong>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if (!$feeSummary['has_hostel'] || !$feeSummary['has_transport']): ?>
        <p class="fc-optional-fee-note">
            <i class="fas fa-info-circle"></i>
            <?php if (!$feeSummary['has_hostel'] && !$feeSummary['has_transport']): ?>
            Hostel &amp; transport fees excluded — not assigned to this student.
            <?php elseif (!$feeSummary['has_hostel']): ?>
            Hostel fee excluded — no active hostel allotment.
            <?php else: ?>
            Transport fee excluded — not assigned to any route.
            <?php endif; ?>
        </p>
        <?php endif; ?>
    </div>

    <div class="form-section-card fc-collect-form-card">
        <div class="fc-form-head">
            <div class="fc-form-head-icon"><i class="fas fa-money-bill-wave"></i></div>
            <div>
                <h4>Record Payment</h4>
                <p>Enter amount and payment details to generate receipt</p>
            </div>
        </div>
        <form method="POST" class="fc-collect-form">
            <input type="hidden" name="collect_fee" value="1">
            <input type="hidden" name="student_id" value="<?php echo $studentId; ?>">
            <div class="form-grid form-grid-2 form-grid-spaced">
                <div class="form-field">
                    <label><i class="fas fa-tags"></i> Fee Head</label>
                    <select name="fee_head_id" class="form-input form-select">
                        <option value="">General / Mixed</option>
                        <?php foreach ($applicableHeads as $item): ?>
                        <option value="<?php echo (int) $item['fee_head_id']; ?>"><?php echo htmlspecialchars($item['head_name']); ?> (₹<?php echo number_format($item['amount'], 0); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field">
                    <label><i class="fas fa-rupee-sign"></i> Amount (₹)</label>
                    <input type="number" step="0.01" min="0.01" name="amount" class="form-input fc-amount-input" placeholder="0.00" <?php echo !$isCleared ? 'value="' . htmlspecialchars(number_format($feeSummary['balance'], 2, '.', '')) . '"' : ''; ?> required>
                </div>
                <div class="form-field">
                    <label><i class="fas fa-credit-card"></i> Payment Method</label>
                    <select name="payment_method" class="form-input form-select">
                        <option>Cash</option>
                        <option>UPI</option>
                        <option>Card</option>
                        <option>Bank Transfer</option>
                        <option>Cheque</option>
                    </select>
                </div>
                <div class="form-field">
                    <label><i class="fas fa-comment"></i> Remarks</label>
                    <input type="text" name="remarks" class="form-input" placeholder="Optional note">
                </div>
            </div>
            <div class="form-actions-end">
                <button type="submit" class="btn-header-action btn-header-primary fc-submit-btn"><i class="fas fa-receipt"></i> Collect &amp; Print Receipt</button>
            </div>
        </form>
    </div>
</div>

<?php if ($feeSummary['payments']): ?>
<div class="form-section-card fc-history-card section-mb">
    <div class="fc-history-head">
        <h4><i class="fas fa-history"></i> Payment History</h4>
        <span class="fc-history-count"><?php echo $paymentCount; ?> transaction<?php echo $paymentCount === 1 ? '' : 's'; ?></span>
    </div>
    <div class="table-wrapper">
        <table class="fc-history-table">
            <thead><tr><th>Date</th><th>Receipt</th><th>Fee Head</th><th>Amount</th><th>Method</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($feeSummary['payments'] as $p): ?>
            <tr>
                <td><span class="fc-date-cell"><i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($p['payment_date'])); ?></span></td>
                <td><code class="fc-receipt-code"><?php echo htmlspecialchars($p['receipt_no']); ?></code></td>
                <td><?php echo displayVal($p['head_name'], 'General'); ?></td>
                <td><strong class="fc-amount-cell">₹<?php echo number_format($p['amount'], 2); ?></strong></td>
                <td><span class="fc-method-badge fc-method-<?php echo strtolower(preg_replace('/\s+/', '-', $p['payment_method'])); ?>"><?php echo htmlspecialchars($p['payment_method']); ?></span></td>
                <td><a href="fee_receipt.php?id=<?php echo $p['id']; ?>" class="fc-receipt-link" target="_blank"><i class="fas fa-print"></i> Receipt</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
<div class="fc-no-payments section-mb">
    <i class="fas fa-receipt"></i>
    <p>No payments recorded yet. Collect the first fee using the form above.</p>
</div>
<?php endif; ?>
<?php endif; ?>
<?php require_once 'includes/footer.php'; ?>
