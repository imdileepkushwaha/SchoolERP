<?php
$page_title = "Certificates";
require_once 'includes/init.php';
require_once '../includes/db_connect.php';
require_once 'includes/erp_helpers.php';

ensureErpSchema($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['issue_cert'])) {
    $studentId = (int) $_POST['student_id'];
    $type = $_POST['cert_type'] ?? 'Bonafide';
    if (!in_array($type, ['TC', 'Bonafide', 'Character'], true)) $type = 'Bonafide';
    $purpose = trim($_POST['purpose'] ?? '');
    $certNo = generateCertificateNo($pdo, $type);
    $pdo->prepare("INSERT INTO certificates (student_id, cert_type, certificate_no, issue_date, purpose) VALUES (?,?,?,CURDATE(),?)")
        ->execute([$studentId, $type, $certNo, $purpose]);
    $_SESSION['success_msg'] = $type . ' certificate issued: ' . $certNo;
    header('Location: certificate_print.php?id=' . $pdo->lastInsertId());
    exit;
}

require_once 'includes/header.php';
$search = trim($_GET['q'] ?? '');
$results = [];
if ($search !== '') {
    $stmt = $pdo->prepare("SELECT id, ad_no, name, class FROM students WHERE name LIKE ? OR ad_no LIKE ? LIMIT 15");
    $like = '%' . $search . '%';
    $stmt->execute([$like, $like]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
$issued = $pdo->query(
    "SELECT c.*, s.name, s.ad_no, s.class FROM certificates c INNER JOIN students s ON s.id = c.student_id ORDER BY c.id DESC LIMIT 50"
)->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="content-top-bar">
    <div class="content-top-main">
        <div class="content-top-icon icon-teal"><i class="fas fa-certificate"></i></div>
        <div class="content-top-title"><h2>Certificates</h2><p class="content-top-breadcrumb"><a href="dashboard.php">Dashboard</a><i class="fas fa-chevron-right"></i><span>Certificates</span></p></div>
    </div>
</div>

<div class="form-section-card section-mb">
    <form method="GET" class="category-add-row">
        <div class="form-field form-field-grow"><label>Find Student</label><input type="text" name="q" class="form-input" value="<?php echo htmlspecialchars($search); ?>" placeholder="Name or Admission No"></div>
        <div class="form-field category-add-btn-wrap"><label>&nbsp;</label><button type="submit" class="btn-header-action btn-header-primary category-add-btn">Search</button></div>
    </form>
    <?php foreach ($results as $r): ?>
    <form method="POST" class="erp-cert-issue-row">
        <input type="hidden" name="issue_cert" value="1">
        <input type="hidden" name="student_id" value="<?php echo $r['id']; ?>">
        <span><?php echo htmlspecialchars($r['name']); ?> (<?php echo htmlspecialchars($r['ad_no']); ?>) — <?php echo htmlspecialchars($r['class']); ?></span>
        <select name="cert_type" class="form-input form-select"><option>Bonafide</option><option>TC</option><option>Character</option></select>
        <input type="text" name="purpose" class="form-input" placeholder="Purpose (optional)">
        <button type="submit" class="btn-header-action btn-header-outline btn-sm">Issue</button>
    </form>
    <?php endforeach; ?>
</div>

<div class="table-container">
    <div class="table-toolbar"><strong>Recently Issued</strong></div>
    <div class="table-wrapper">
        <table><thead><tr><th>Cert No</th><th>Type</th><th>Student</th><th>Date</th><th></th></tr></thead><tbody>
        <?php foreach ($issued as $c): ?>
        <tr>
            <td><?php echo htmlspecialchars($c['certificate_no']); ?></td>
            <td><?php echo htmlspecialchars($c['cert_type']); ?></td>
            <td><?php echo htmlspecialchars($c['name']); ?> (<?php echo htmlspecialchars($c['ad_no']); ?>)</td>
            <td><?php echo htmlspecialchars($c['issue_date']); ?></td>
            <td><a href="certificate_print.php?id=<?php echo $c['id']; ?>" target="_blank" class="teal-link">Print</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody></table>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
