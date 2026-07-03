<?php
$page_title = 'Fees';
require_once 'includes/init.php';
$id = (int) $_SESSION['student_portal_id'];
$fee = getStudentFeeSummary($pdo, $id);
require_once 'includes/layout_header.php';

$totalDue = (float) ($fee['total_due'] ?? 0);
$totalPaid = (float) ($fee['total_paid'] ?? 0);
$balance = (float) ($fee['balance'] ?? 0);
$paidPct = $totalDue > 0 ? min(100, round($totalPaid / $totalDue * 100)) : ($totalPaid > 0 ? 100 : 0);
$feeStatus = $fee['fee_status'] ?? '';
$feeItems = $fee['fee_items'] ?? [];
?>
<div class="sp-stat-grid">
    <div class="sp-stat tone-purple"><div class="sp-stat-icon"><i class="fas fa-file-invoice"></i></div><div class="sp-stat-body"><span>Total Due</span><strong>₹<?php echo number_format($totalDue, 0); ?></strong><small>for this session</small></div></div>
    <div class="sp-stat tone-green"><div class="sp-stat-icon"><i class="fas fa-circle-check"></i></div><div class="sp-stat-body"><span>Paid</span><strong>₹<?php echo number_format($totalPaid, 0); ?></strong><small><?php echo $paidPct; ?>% cleared</small></div></div>
    <div class="sp-stat <?php echo $balance > 0 ? 'tone-red' : 'tone-green'; ?>"><div class="sp-stat-icon"><i class="fas fa-<?php echo $balance > 0 ? 'triangle-exclamation' : 'check-double'; ?>"></i></div><div class="sp-stat-body"><span>Balance</span><strong>₹<?php echo number_format($balance, 0); ?></strong><small><?php echo $balance > 0 ? 'pending payment' : 'all clear'; ?></small></div></div>
</div>

<div class="sp-grid-2 wide-first">
    <div class="sp-card">
        <div class="sp-card-head"><h3><i class="fas fa-receipt"></i> Payment History</h3></div>
        <?php if (!empty($fee['payments'])): ?>
        <div class="sp-table-wrap">
            <table class="sp-table">
                <thead><tr><th>Date</th><th>Receipt</th><th>Head</th><th class="ta-r">Amount</th><th class="ta-c">Method</th><th class="ta-c">Receipt</th></tr></thead>
                <tbody>
                <?php foreach ($fee['payments'] as $p): ?>
                <tr>
                    <td><?php echo date('d M Y', strtotime($p['payment_date'])); ?></td>
                    <td><strong><?php echo htmlspecialchars($p['receipt_no']); ?></strong></td>
                    <td><?php echo htmlspecialchars($p['head_name'] ?? 'General'); ?></td>
                    <td class="ta-r"><strong>₹<?php echo number_format($p['amount'], 0); ?></strong></td>
                    <td class="ta-c"><span class="sp-badge method"><?php echo htmlspecialchars($p['payment_method']); ?></span></td>
                    <td class="ta-c">
                        <a class="sp-receipt-btn" href="fee_receipt.php?id=<?php echo (int) $p['id']; ?>" target="_blank" title="View / Download receipt"><i class="fas fa-download"></i> Receipt</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="sp-empty"><div class="sp-empty-icon"><i class="fas fa-receipt"></i></div><strong>No payments yet</strong><p>Your payment receipts will appear here.</p></div>
        <?php endif; ?>
    </div>

    <div class="sp-card">
        <div class="sp-card-head"><h3><i class="fas fa-chart-simple"></i> Fee Overview</h3></div>
        <div class="sp-fee-progress">
            <div class="sp-fee-progress-head"><span>Paid ₹<?php echo number_format($totalPaid, 0); ?></span><strong><?php echo $paidPct; ?>%</strong></div>
            <div class="sp-fee-bar"><div class="sp-fee-bar-fill" style="width:<?php echo $paidPct; ?>%"></div></div>
            <?php if ($balance > 0): ?>
            <p class="sp-fee-note"><i class="fas fa-triangle-exclamation"></i> ₹<?php echo number_format($balance, 0); ?> is still pending. Please clear at the school office.</p>
            <?php elseif ($feeStatus === 'no_structure'): ?>
            <p class="sp-fee-note"><i class="fas fa-circle-info"></i> No fee structure has been assigned to your class yet.</p>
            <?php else: ?>
            <p class="sp-fee-note"><i class="fas fa-circle-check"></i> All your fees are fully cleared. Thank you!</p>
            <?php endif; ?>
        </div>

        <?php if (!empty($feeItems)): ?>
        <div style="margin-top:20px">
            <div class="sp-card-head"><h3 style="font-size:0.92rem"><i class="fas fa-list-ul"></i> Fee Breakdown</h3></div>
            <div class="sp-list">
                <?php foreach ($feeItems as $item): ?>
                <div class="sp-list-row" style="align-items:center">
                    <div class="sp-list-ico"><i class="fas fa-tag"></i></div>
                    <div class="sp-list-main"><strong><?php echo htmlspecialchars($item['head_name'] ?? 'Fee'); ?></strong></div>
                    <strong style="font-size:0.95rem">₹<?php echo number_format($item['amount'], 0); ?></strong>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php require_once 'includes/layout_footer.php'; ?>
