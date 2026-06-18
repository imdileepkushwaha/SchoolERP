<?php
session_start();
require_once '../includes/db_connect.php';
require_once 'includes/erp_helpers.php';
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}
ensureErpSchema($pdo);
$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT c.*, s.* FROM certificates c INNER JOIN students s ON s.id = c.student_id WHERE c.id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) die('Certificate not found.');
$type = $row['cert_type'];
?><!DOCTYPE html>
<html><head><meta charset="UTF-8"><title><?php echo htmlspecialchars($type); ?> Certificate</title>
<style>@media print{.no-print{display:none}}body{font-family:Georgia,serif;padding:40px;max-width:800px;margin:0 auto;line-height:1.7}.cert{border:3px double #059669;padding:40px;min-height:500px}</style>
</head><body>
<div class="no-print" style="margin-bottom:16px"><button onclick="window.print()">Print</button></div>
<div class="cert">
    <h2 style="text-align:center;margin:0 0 8px">EduDash School</h2>
    <p style="text-align:center;margin:0 0 30px"><strong><?php
        echo $type === 'TC' ? 'Transfer Certificate' : ($type === 'Character' ? 'Character Certificate' : 'Bonafide Certificate');
    ?></strong></p>
    <p style="text-align:right">Certificate No: <strong><?php echo htmlspecialchars($row['certificate_no']); ?></strong><br>Date: <?php echo date('d M Y', strtotime($row['issue_date'])); ?></p>
    <?php if ($type === 'TC'): ?>
    <p>This is to certify that <strong><?php echo htmlspecialchars($row['name']); ?></strong>, Admission No. <strong><?php echo htmlspecialchars($row['ad_no']); ?></strong>, son/daughter of ——, was a student of this institution in Class <strong><?php echo htmlspecialchars($row['class']); ?></strong> (Section <?php echo htmlspecialchars($row['section'] ?? 'A'); ?>).</p>
    <p>Roll No: <?php echo htmlspecialchars($row['roll']); ?>. Date of Birth: <?php echo formatDobDisplay($row['dob']); ?>.</p>
    <p>He/She has paid all dues and is hereby granted Transfer Certificate from this school.</p>
    <?php elseif ($type === 'Character'): ?>
    <p>This is to certify that <strong><?php echo htmlspecialchars($row['name']); ?></strong>, Admission No. <?php echo htmlspecialchars($row['ad_no']); ?>, studying in Class <?php echo htmlspecialchars($row['class']); ?>, bears a <strong>good moral character</strong> and has conducted himself/herself satisfactorily during the period of study in this institution.</p>
    <?php else: ?>
    <p>This is to certify that <strong><?php echo htmlspecialchars($row['name']); ?></strong>, Admission No. <strong><?php echo htmlspecialchars($row['ad_no']); ?></strong>, Roll No. <?php echo htmlspecialchars($row['roll']); ?>, is a <strong>bonafide student</strong> of this school, studying in Class <strong><?php echo htmlspecialchars($row['class']); ?></strong>, Section <?php echo htmlspecialchars($row['section'] ?? 'A'); ?>.</p>
    <?php endif; ?>
    <?php if ($row['purpose']): ?><p><strong>Purpose:</strong> <?php echo htmlspecialchars($row['purpose']); ?></p><?php endif; ?>
    <p style="margin-top:60px">Principal &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; School Seal</p>
</div>
</body></html>
