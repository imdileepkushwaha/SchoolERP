<?php
$page_title = "Fee Structure";
require_once 'includes/init.php';
require_once '../includes/db_connect.php';
require_once 'includes/erp_helpers.php';

ensureErpSchema($pdo);
$session = getCurrentSession($pdo);
$class_options = getClassOptions($pdo);
$sessionId = $session['id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add_head') {
        $name = trim($_POST['head_name'] ?? '');
        if ($name !== '') {
            try {
                $pdo->prepare("INSERT INTO fee_heads (name) VALUES (?)")->execute([$name]);
                $_SESSION['success_msg'] = 'Fee head added.';
            } catch (PDOException $e) {
                $_SESSION['error_msg'] = 'Fee head already exists.';
            }
        }
    } elseif ($action === 'save_structure') {
        $className = trim($_POST['class_name'] ?? '');
        $amounts = $_POST['amount'] ?? [];
        foreach ($amounts as $headId => $amt) {
            $amt = (float) $amt;
            $headId = (int) $headId;
            if ($className === '' || $headId <= 0) {
                continue;
            }
            $pdo->prepare(
                "INSERT INTO fee_structures (class_name, fee_head_id, amount, session_id) VALUES (?,?,?,?)
                 ON DUPLICATE KEY UPDATE amount = VALUES(amount)"
            )->execute([$className, $headId, $amt, $sessionId]);
        }
        $_SESSION['success_msg'] = 'Fee structure saved for ' . $className;
    }
    header('Location: fees.php' . (!empty($_POST['class_name']) ? '?class=' . urlencode($_POST['class_name']) : ''));
    exit;
}

function feeHeadPresentation($name) {
    $name = (string) $name;
    if (stripos($name, 'hostel') !== false) {
        return ['icon' => 'fa-bed', 'tone' => 'purple', 'optional' => true, 'hint' => 'Charged only when hostel is allotted'];
    }
    if (stripos($name, 'transport') !== false) {
        return ['icon' => 'fa-bus', 'tone' => 'teal', 'optional' => true, 'hint' => 'Charged only when route is assigned'];
    }
    if (stripos($name, 'tuition') !== false) {
        return ['icon' => 'fa-book-open', 'tone' => 'blue', 'optional' => false, 'hint' => 'Core academic fee for all students'];
    }
    if (stripos($name, 'admission') !== false) {
        return ['icon' => 'fa-user-plus', 'tone' => 'orange', 'optional' => false, 'hint' => 'One-time or recurring admission charge'];
    }
    if (stripos($name, 'exam') !== false) {
        return ['icon' => 'fa-file-alt', 'tone' => 'indigo', 'optional' => false, 'hint' => 'Examination related fee'];
    }
    return ['icon' => 'fa-tags', 'tone' => 'slate', 'optional' => false, 'hint' => 'Standard fee head for this class'];
}

$selectedClass = trim($_GET['class'] ?? '');
require_once 'includes/header.php';
$heads = getFeeHeads($pdo);
$structure = $selectedClass ? getClassFeeStructure($pdo, $selectedClass) : [];
$amountMap = [];
foreach ($structure as $row) {
    $amountMap[$row['fee_head_id']] = $row['amount'];
}

$classSummaries = [];
$summaryStmt = $pdo->prepare(
    "SELECT fs.class_name, SUM(fs.amount) AS total, COUNT(*) AS cnt
     FROM fee_structures fs
     WHERE (fs.session_id = ? OR fs.session_id IS NULL) AND fs.amount > 0
     GROUP BY fs.class_name"
);
$summaryStmt->execute([$sessionId]);
foreach ($summaryStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $classSummaries[$row['class_name']] = $row;
}

$structureTotal = 0;
$activeHeadCount = 0;
foreach ($heads as $h) {
    $amt = (float) ($amountMap[$h['id']] ?? 0);
    $structureTotal += $amt;
    if ($amt > 0) {
        $activeHeadCount++;
    }
}
$configuredClassCount = count($classSummaries);
?>

<div class="content-top-bar">
    <div class="content-top-main">
        <div class="content-top-icon icon-orange"><i class="fas fa-file-invoice-dollar"></i></div>
        <div class="content-top-title">
            <h2>Fee Structure</h2>
            <p class="content-top-breadcrumb"><a href="dashboard.php">Dashboard</a><i class="fas fa-chevron-right"></i><span>Fees</span></p>
        </div>
    </div>
    <div class="content-top-actions">
        <a href="fee_reports.php" class="btn-header-action btn-header-outline"><i class="fas fa-chart-line"></i> Reports</a>
        <a href="fee_collect.php" class="btn-header-action btn-header-primary"><i class="fas fa-money-bill-wave"></i> Collect Fee</a>
    </div>
</div>

<div class="fs-hero">
    <div class="fs-hero-main">
        <p class="fs-hero-label"><i class="fas fa-sliders-h"></i> Fee configuration</p>
        <h3>Set class-wise fee amounts</h3>
        <p>Define fee heads once, then assign amounts per class for session <?php echo htmlspecialchars($session['name'] ?? '-'); ?>.</p>
    </div>
    <div class="fs-hero-stats">
        <div class="fs-hero-stat"><span>Fee heads</span><strong><?php echo count($heads); ?></strong></div>
        <div class="fs-hero-stat"><span>Classes set</span><strong><?php echo $configuredClassCount; ?></strong></div>
        <?php if ($selectedClass): ?>
        <div class="fs-hero-stat is-highlight"><span><?php echo htmlspecialchars($selectedClass); ?></span><strong>₹<?php echo number_format($structureTotal, 0); ?></strong></div>
        <?php else: ?>
        <div class="fs-hero-stat"><span>Session</span><strong><?php echo htmlspecialchars($session['name'] ?? '-'); ?></strong></div>
        <?php endif; ?>
    </div>
</div>

<div class="fs-quick-links">
    <a href="fee_collect.php" class="fs-quick-link"><i class="fas fa-hand-holding-usd"></i><span>Collect Fee</span></a>
    <a href="fee_reports.php" class="fs-quick-link"><i class="fas fa-chart-bar"></i><span>View Reports</span></a>
    <a href="hostel.php" class="fs-quick-link"><i class="fas fa-bed"></i><span>Hostel Allotment</span></a>
    <a href="transport.php" class="fs-quick-link"><i class="fas fa-bus"></i><span>Transport Routes</span></a>
</div>

<div class="fs-layout">
    <div class="form-section-card fs-class-card">
        <div class="fs-card-head">
            <div class="fs-card-head-icon"><i class="fas fa-school"></i></div>
            <div>
                <h4>Select Class</h4>
                <p>Choose a class to view or edit its fee structure</p>
            </div>
        </div>
        <div class="fs-class-grid">
            <?php foreach ($class_options as $c):
                $summary = $classSummaries[$c] ?? null;
                $isActive = $selectedClass === $c;
            ?>
            <a href="fees.php?class=<?php echo urlencode($c); ?>" class="fs-class-pill<?php echo $isActive ? ' is-active' : ''; ?><?php echo $summary ? ' is-configured' : ''; ?>">
                <span class="fs-class-pill-name"><?php echo htmlspecialchars($c); ?></span>
                <?php if ($summary): ?>
                <span class="fs-class-pill-amount">₹<?php echo number_format($summary['total'], 0); ?></span>
                <small><?php echo (int) $summary['cnt']; ?> head<?php echo (int) $summary['cnt'] === 1 ? '' : 's'; ?></small>
                <?php else: ?>
                <span class="fs-class-pill-empty">Not set</span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php if (!$class_options): ?>
        <div class="fs-empty-note"><i class="fas fa-info-circle"></i> No classes found. Add students or classes first.</div>
        <?php endif; ?>
    </div>

    <div class="form-section-card fs-heads-card">
        <div class="fs-card-head">
            <div class="fs-card-head-icon is-blue"><i class="fas fa-tags"></i></div>
            <div>
                <h4>Fee Heads</h4>
                <p>Reusable categories used across all classes</p>
            </div>
        </div>
        <div class="fs-head-list">
            <?php foreach ($heads as $h):
                $meta = feeHeadPresentation($h['name']);
            ?>
            <div class="fs-head-chip tone-<?php echo $meta['tone']; ?>">
                <span class="fs-head-chip-icon"><i class="fas <?php echo $meta['icon']; ?>"></i></span>
                <div class="fs-head-chip-body">
                    <strong><?php echo htmlspecialchars($h['name']); ?></strong>
                    <?php if ($meta['optional']): ?><em>Optional</em><?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <form method="POST" class="fs-add-head-form">
            <input type="hidden" name="action" value="add_head">
            <div class="fs-add-head-row">
                <input type="text" name="head_name" class="form-input" placeholder="New fee head, e.g. Lab Fee" required>
                <button type="submit" class="btn-header-action btn-header-outline"><i class="fas fa-plus"></i> Add</button>
            </div>
        </form>
    </div>
</div>

<?php if ($selectedClass): ?>
<form method="POST" class="fs-structure-form">
    <input type="hidden" name="action" value="save_structure">
    <input type="hidden" name="class_name" value="<?php echo htmlspecialchars($selectedClass); ?>">
    <div class="form-section-card fs-structure-card section-mb">
        <div class="fs-structure-head">
            <div class="fs-structure-title">
                <div class="fs-structure-icon"><i class="fas fa-edit"></i></div>
                <div>
                    <h4><?php echo htmlspecialchars($selectedClass); ?> — Fee Structure</h4>
                    <p><?php echo $activeHeadCount; ?> of <?php echo count($heads); ?> heads configured · Session <?php echo htmlspecialchars($session['name'] ?? '-'); ?></p>
                </div>
            </div>
            <div class="fs-structure-actions">
                <span class="fs-total-pill">Total ₹<?php echo number_format($structureTotal, 0); ?></span>
                <button type="submit" class="btn-header-action btn-header-primary"><i class="fas fa-save"></i> Save Structure</button>
            </div>
        </div>

        <div class="fs-fee-rows">
            <?php foreach ($heads as $h):
                $meta = feeHeadPresentation($h['name']);
                $amount = (float) ($amountMap[$h['id']] ?? 0);
            ?>
            <div class="fs-fee-row tone-<?php echo $meta['tone']; ?>">
                <div class="fs-fee-row-icon"><i class="fas <?php echo $meta['icon']; ?>"></i></div>
                <div class="fs-fee-row-info">
                    <div class="fs-fee-row-top">
                        <strong><?php echo htmlspecialchars($h['name']); ?></strong>
                        <?php if ($meta['optional']): ?>
                        <span class="fs-optional-badge"><i class="fas fa-user-check"></i> Assign-based</span>
                        <?php endif; ?>
                    </div>
                    <p><?php echo htmlspecialchars($meta['hint']); ?></p>
                </div>
                <div class="fs-fee-row-amount">
                    <label>Amount (₹)</label>
                    <input type="number" step="0.01" min="0" name="amount[<?php echo $h['id']; ?>]" class="form-input fs-amount-input" value="<?php echo htmlspecialchars(number_format($amount, 2, '.', '')); ?>" placeholder="0.00">
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="fs-structure-foot">
            <div class="fs-foot-note">
                <i class="fas fa-info-circle"></i>
                Hostel and transport amounts apply only when a student is allotted hostel or assigned a route.
            </div>
            <div class="fs-foot-total">
                <span>Class total</span>
                <strong>₹<?php echo number_format($structureTotal, 0); ?></strong>
            </div>
        </div>
    </div>
</form>
<?php else: ?>
<div class="form-section-card fs-pick-class section-mb">
    <div class="fs-pick-class-icon"><i class="fas fa-hand-pointer"></i></div>
    <h4>Select a class above</h4>
    <p>Pick a class from the grid to configure tuition, exam, hostel, transport and other fees.</p>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('.fs-structure-form');
    if (!form) return;
    var totalEl = form.querySelector('.fs-total-pill');
    var footTotal = form.querySelector('.fs-foot-total strong');
    var inputs = form.querySelectorAll('.fs-amount-input');
    function updateTotal() {
        var sum = 0;
        inputs.forEach(function (input) {
            sum += parseFloat(input.value) || 0;
        });
        var formatted = '₹' + Math.round(sum).toLocaleString('en-IN');
        if (totalEl) totalEl.textContent = 'Total ' + formatted;
        if (footTotal) footTotal.textContent = formatted;
    }
    inputs.forEach(function (input) {
        input.addEventListener('input', updateTotal);
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
