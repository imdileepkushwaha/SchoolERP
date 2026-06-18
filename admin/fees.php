<?php
$page_title = "Fee Structure";
require_once 'includes/init.php';
require_once '../includes/db_connect.php';
require_once 'includes/erp_helpers.php';

ensureErpSchema($pdo);
$session = getCurrentSession($pdo);
$class_options = getClassOptions($pdo);

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
        $sid = $session['id'] ?? null;
        foreach ($amounts as $headId => $amt) {
            $amt = (float) $amt;
            $headId = (int) $headId;
            if ($className === '' || $headId <= 0) continue;
            $pdo->prepare(
                "INSERT INTO fee_structures (class_name, fee_head_id, amount, session_id) VALUES (?,?,?,?)
                 ON DUPLICATE KEY UPDATE amount = VALUES(amount)"
            )->execute([$className, $headId, $amt, $sid]);
        }
        $_SESSION['success_msg'] = 'Fee structure saved for ' . $className;
    }
    header('Location: fees.php' . (!empty($_POST['class_name']) ? '?class=' . urlencode($_POST['class_name']) : ''));
    exit;
}

$selectedClass = trim($_GET['class'] ?? '');
require_once 'includes/header.php';
$heads = getFeeHeads($pdo);
$structure = $selectedClass ? getClassFeeStructure($pdo, $selectedClass) : [];
$amountMap = [];
foreach ($structure as $row) {
    $amountMap[$row['fee_head_id']] = $row['amount'];
}
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
        <a href="fee_collect.php" class="btn-header-action btn-header-primary"><i class="fas fa-money-bill"></i> Collect Fee</a>
    </div>
</div>

<div class="form-section-card section-mb">
    <form method="POST" class="category-add-form">
        <input type="hidden" name="action" value="add_head">
        <div class="category-add-row">
            <div class="form-field"><label>New Fee Head</label><input type="text" name="head_name" class="form-input" placeholder="e.g. Lab Fee" required></div>
            <div class="form-field category-add-btn-wrap"><label>&nbsp;</label><button type="submit" class="btn-header-action btn-header-outline category-add-btn"><i class="fas fa-plus"></i> Add Head</button></div>
        </div>
    </form>
</div>

<div class="form-section-card section-mb">
    <form method="GET" class="category-add-row erp-filter-row">
        <div class="form-field"><label>Class</label>
            <select name="class" class="form-input form-select" onchange="this.form.submit()">
                <option value="">Select class to set fees</option>
                <?php foreach ($class_options as $c): ?>
                <option value="<?php echo htmlspecialchars($c); ?>" <?php echo $selectedClass === $c ? 'selected' : ''; ?>><?php echo htmlspecialchars($c); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-field"><label>Session</label><input type="text" class="form-input" readonly value="<?php echo htmlspecialchars($session['name'] ?? '-'); ?>"></div>
    </form>
</div>

<?php if ($selectedClass): ?>
<form method="POST">
    <input type="hidden" name="action" value="save_structure">
    <input type="hidden" name="class_name" value="<?php echo htmlspecialchars($selectedClass); ?>">
    <div class="table-container">
        <div class="table-toolbar"><strong>Fee amounts for <?php echo htmlspecialchars($selectedClass); ?></strong>
            <button type="submit" class="btn-header-action btn-header-primary"><i class="fas fa-save"></i> Save Structure</button>
        </div>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Fee Head</th><th>Amount (Rs.)</th></tr></thead>
                <tbody>
                <?php foreach ($heads as $h): ?>
                <tr>
                    <td><?php echo htmlspecialchars($h['name']); ?></td>
                    <td><input type="number" step="0.01" min="0" name="amount[<?php echo $h['id']; ?>]" class="form-input table-inline-input" value="<?php echo htmlspecialchars($amountMap[$h['id']] ?? '0'); ?>"></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</form>
<?php endif; ?>
<?php require_once 'includes/footer.php'; ?>
