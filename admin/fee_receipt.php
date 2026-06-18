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
$stmt = $pdo->prepare(
    "SELECT fp.*, s.name, s.ad_no, s.class, s.section, s.roll, fh.name AS head_name
     FROM fee_payments fp INNER JOIN students s ON s.id = fp.student_id
     LEFT JOIN fee_heads fh ON fh.id = fp.fee_head_id WHERE fp.id = ?"
);
$stmt->execute([$id]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$p) die('Receipt not found.');
?><!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><title>Receipt <?php echo htmlspecialchars($p['receipt_no']); ?></title>
<link rel="stylesheet" href="assets/css/admin.css">
<style>@media print{.no-print{display:none}}body{padding:24px;max-width:700px;margin:0 auto}.receipt-box{border:2px solid #059669;border-radius:12px;padding:24px}</style>
</head><body>
<div class="no-print" style="margin-bottom:16px"><button onclick="window.print()" class="btn-header-action btn-header-primary">Print</button> <a href="fee_collect.php?student_id=<?php echo (int)$p['student_id']; ?>" class="btn-header-action btn-header-outline">Back</a></div>
<div class="receipt-box">
    <h2 style="text-align:center;color:#059669;margin:0 0 8px">EduDash School</h2>
    <p style="text-align:center;margin:0 0 20px">Fee Payment Receipt</p>
    <table style="width:100%;margin-bottom:16px">
        <tr><td><strong>Receipt No:</strong> <?php echo htmlspecialchars($p['receipt_no']); ?></td><td style="text-align:right"><strong>Date:</strong> <?php echo htmlspecialchars($p['payment_date']); ?></td></tr>
    </table>
    <p><strong>Student:</strong> <?php echo htmlspecialchars($p['name']); ?> (<?php echo htmlspecialchars($p['ad_no']); ?>)</p>
    <p><strong>Class:</strong> <?php echo htmlspecialchars($p['class']); ?> (<?php echo htmlspecialchars($p['section'] ?? 'A'); ?>) · Roll <?php echo htmlspecialchars($p['roll']); ?></p>
    <p><strong>Fee Head:</strong> <?php echo displayVal($p['head_name'], 'General'); ?></p>
    <p style="font-size:1.5rem;margin:20px 0"><strong>Amount Paid: Rs. <?php echo number_format($p['amount'], 2); ?></strong></p>
    <p><strong>Method:</strong> <?php echo htmlspecialchars($p['payment_method']); ?></p>
    <?php if ($p['remarks']): ?><p><strong>Remarks:</strong> <?php echo htmlspecialchars($p['remarks']); ?></p><?php endif; ?>
    <p style="margin-top:40px;text-align:right">Authorized Signatory</p>
</div>
</body></html>
