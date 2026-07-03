<?php
session_start();
require_once '../includes/db_connect.php';
require_once 'includes/erp_helpers.php';
require_once 'includes/settings_helpers.php';
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}
ensureErpSchema($pdo);
ensureSettingsSchema($pdo);

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare(
    "SELECT fp.*, s.name, s.ad_no, s.class, s.section, s.roll, fh.name AS head_name
     FROM fee_payments fp
     INNER JOIN students s ON s.id = fp.student_id
     LEFT JOIN fee_heads fh ON fh.id = fp.fee_head_id
     WHERE fp.id = ?"
);
$stmt->execute([$id]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$p) {
    die('Receipt not found.');
}

$school = getSchoolProfile($pdo);
$logoUrl = schoolBrandingUrl($school['logo'] ?? '', 'admin');
$brandName = $school['name'] ?: 'School';
$sig = getDefaultAuthoritySignature($pdo);
$sigUrl = schoolBrandingUrl($sig['signature'] ?? '', 'admin');

if (!function_exists('amountInWords')) {
    function amountInWords($number) {
        $number = (int) round($number);
        if ($number === 0) return 'Zero';
        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
            'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
        $words = function ($n) use (&$words, $ones, $tens) {
            if ($n < 20) return $ones[$n];
            if ($n < 100) return $tens[intval($n / 10)] . ($n % 10 ? ' ' . $ones[$n % 10] : '');
            if ($n < 1000) return $ones[intval($n / 100)] . ' Hundred' . ($n % 100 ? ' ' . $words($n % 100) : '');
            return '';
        };
        $out = '';
        $crore = intval($number / 10000000); $number %= 10000000;
        $lakh = intval($number / 100000); $number %= 100000;
        $thousand = intval($number / 1000); $number %= 1000;
        $rest = $number;
        if ($crore) $out .= $words($crore) . ' Crore ';
        if ($lakh) $out .= $words($lakh) . ' Lakh ';
        if ($thousand) $out .= $words($thousand) . ' Thousand ';
        if ($rest) $out .= $words($rest);
        return trim($out);
    }
}

$section = trim($p['section'] ?? '') ?: 'A';
$autoPrint = isset($_GET['print']);
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt <?php echo htmlspecialchars($p['receipt_no']); ?> — <?php echo htmlspecialchars($brandName); ?></title>
    <?php if (!empty($school['favicon'])): ?><link rel="icon" href="<?php echo htmlspecialchars(schoolBrandingUrl($school['favicon'], 'admin')); ?>"><?php endif; ?>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; background: #eef1f6; color: #0f172a; padding: 28px 16px; }

        .rc-toolbar { max-width: 760px; margin: 0 auto 20px; display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }
        .rc-toolbar h1 { font-size: 1.15rem; font-weight: 800; color: #1e293b; display: flex; align-items: center; gap: 9px; }
        .rc-toolbar h1 i { color: #7c3aed; }
        .rc-actions { display: flex; gap: 10px; }
        .rc-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; border-radius: 11px; font-size: 0.88rem; font-weight: 700; cursor: pointer; border: 1px solid transparent; text-decoration: none; transition: all .15s; font-family: inherit; }
        .rc-btn-primary { background: linear-gradient(135deg, #7c3aed, #6d28d9); color: #fff; box-shadow: 0 8px 20px rgba(124,58,237,.3); }
        .rc-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 12px 26px rgba(124,58,237,.38); }
        .rc-btn-ghost { background: #fff; border-color: #e2e8f0; color: #334155; }
        .rc-btn-ghost:hover { border-color: #7c3aed; color: #7c3aed; }

        .rc-receipt { max-width: 760px; margin: 0 auto; background: #fff; border-radius: 20px; overflow: hidden; box-shadow: 0 24px 60px rgba(15,23,42,.12); position: relative; }
        .rc-accent { height: 6px; background: linear-gradient(90deg, #7c3aed, #a855f7, #38bdf8); }

        .rc-header { display: flex; align-items: center; gap: 18px; padding: 30px 36px 24px; border-bottom: 2px dashed #e2e8f0; position: relative; }
        .rc-logo { width: 66px; height: 66px; border-radius: 16px; background: linear-gradient(135deg, #7c3aed, #6d28d9); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.7rem; overflow: hidden; flex-shrink: 0; }
        .rc-logo img { width: 100%; height: 100%; object-fit: contain; padding: 7px; background: #fff; }
        .rc-school h2 { font-size: 1.5rem; font-weight: 800; letter-spacing: -0.02em; color: #0f172a; }
        .rc-school p { font-size: 0.82rem; color: #64748b; margin-top: 3px; line-height: 1.5; }
        .rc-school .rc-contact { display: flex; flex-wrap: wrap; gap: 4px 14px; margin-top: 6px; }
        .rc-school .rc-contact span { font-size: 0.76rem; color: #64748b; display: inline-flex; align-items: center; gap: 5px; }
        .rc-school .rc-contact i { color: #7c3aed; }
        .rc-doc-label { position: absolute; top: 30px; right: 36px; text-align: right; }
        .rc-doc-label .lbl { display: inline-block; font-size: 0.72rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: #7c3aed; background: #f3e8ff; padding: 6px 12px; border-radius: 8px; }

        .rc-meta { display: flex; justify-content: space-between; gap: 16px; flex-wrap: wrap; padding: 20px 36px; background: #faf9ff; }
        .rc-meta-item span { display: block; font-size: 0.68rem; text-transform: uppercase; letter-spacing: .05em; color: #94a3b8; font-weight: 700; margin-bottom: 3px; }
        .rc-meta-item strong { font-size: 0.98rem; color: #0f172a; font-weight: 700; }
        .rc-status { display: inline-flex; align-items: center; gap: 7px; padding: 8px 16px; border-radius: 999px; background: #ecfdf5; color: #059669; font-weight: 800; font-size: 0.85rem; border: 1.5px solid #a7f3d0; }

        .rc-body { padding: 26px 36px; }
        .rc-section-title { font-size: 0.72rem; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; font-weight: 800; margin-bottom: 12px; }
        .rc-student { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px 24px; margin-bottom: 26px; }
        .rc-field { display: flex; flex-direction: column; }
        .rc-field span { font-size: 0.72rem; color: #94a3b8; font-weight: 600; margin-bottom: 2px; }
        .rc-field strong { font-size: 0.95rem; color: #0f172a; font-weight: 700; }

        .rc-table { width: 100%; border-collapse: collapse; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
        .rc-table thead th { background: #1e1b4b; color: #fff; padding: 13px 16px; text-align: left; font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; }
        .rc-table thead th.ta-r { text-align: right; }
        .rc-table tbody td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; font-size: 0.92rem; }
        .rc-table tbody td.ta-r { text-align: right; font-weight: 700; }
        .rc-table tfoot td { padding: 15px 16px; font-weight: 800; font-size: 1.05rem; background: #faf9ff; }
        .rc-table tfoot td.ta-r { text-align: right; color: #7c3aed; }

        .rc-words { margin: 16px 0 0; padding: 12px 16px; background: #f8fafc; border-radius: 10px; font-size: 0.85rem; color: #475569; }
        .rc-words strong { color: #0f172a; }

        .rc-footer { display: flex; justify-content: space-between; align-items: flex-end; gap: 20px; padding: 30px 36px 34px; flex-wrap: wrap; }
        .rc-note { font-size: 0.76rem; color: #94a3b8; max-width: 340px; line-height: 1.6; }
        .rc-note i { color: #7c3aed; }
        .rc-sign { text-align: center; }
        .rc-sign-img { display: block; max-height: 48px; max-width: 170px; object-fit: contain; margin: 0 auto -2px; }
        .rc-sign-name { font-size: 0.82rem; color: #0f172a; font-weight: 700; }
        .rc-sign-line { width: 170px; border-top: 1.5px solid #cbd5e1; margin: 4px 0 6px; }
        .rc-sign span { font-size: 0.8rem; color: #64748b; font-weight: 600; }

        .rc-watermark { position: absolute; top: 55%; left: 50%; transform: translate(-50%, -50%) rotate(-24deg); font-size: 7rem; font-weight: 800; color: rgba(16, 185, 129, 0.06); letter-spacing: .1em; pointer-events: none; z-index: 0; }
        .rc-header, .rc-meta, .rc-body, .rc-footer { position: relative; z-index: 1; }

        @media (max-width: 620px) {
            .rc-header { flex-direction: column; text-align: center; }
            .rc-doc-label { position: static; margin-top: 10px; }
            .rc-student { grid-template-columns: 1fr; }
            .rc-school .rc-contact { justify-content: center; }
        }

        @media print {
            body { background: #fff; padding: 0; }
            .no-print { display: none !important; }
            .rc-receipt { box-shadow: none; border-radius: 0; max-width: 100%; }
            @page { margin: 12mm; }
        }
    </style>
</head>
<body>
    <div class="rc-toolbar no-print">
        <h1><i class="fas fa-receipt"></i> Fee Receipt</h1>
        <div class="rc-actions">
            <a href="fee_collect.php?student_id=<?php echo (int) $p['student_id']; ?>" class="rc-btn rc-btn-ghost"><i class="fas fa-arrow-left"></i> Back</a>
            <button type="button" class="rc-btn rc-btn-primary" onclick="window.print()"><i class="fas fa-download"></i> Download / Print</button>
        </div>
    </div>

    <div class="rc-receipt">
        <div class="rc-accent"></div>
        <div class="rc-watermark">PAID</div>

        <div class="rc-header">
            <div class="rc-logo">
                <?php if ($logoUrl): ?><img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="Logo"><?php else: ?><i class="fas fa-graduation-cap"></i><?php endif; ?>
            </div>
            <div class="rc-school">
                <h2><?php echo htmlspecialchars($brandName); ?></h2>
                <?php if (!empty($school['tagline'])): ?><p><?php echo htmlspecialchars($school['tagline']); ?></p><?php endif; ?>
                <div class="rc-contact">
                    <?php if (!empty($school['address'])): ?><span><i class="fas fa-location-dot"></i> <?php echo htmlspecialchars($school['address']); ?></span><?php endif; ?>
                    <?php if (!empty($school['phone'])): ?><span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($school['phone']); ?></span><?php endif; ?>
                    <?php if (!empty($school['email'])): ?><span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($school['email']); ?></span><?php endif; ?>
                </div>
            </div>
            <div class="rc-doc-label"><span class="lbl">Fee Receipt</span></div>
        </div>

        <div class="rc-meta">
            <div class="rc-meta-item"><span>Receipt No</span><strong><?php echo htmlspecialchars($p['receipt_no']); ?></strong></div>
            <div class="rc-meta-item"><span>Payment Date</span><strong><?php echo date('d M Y', strtotime($p['payment_date'])); ?></strong></div>
            <div class="rc-meta-item"><span>Status</span><strong class="rc-status"><i class="fas fa-circle-check"></i> Paid</strong></div>
        </div>

        <div class="rc-body">
            <p class="rc-section-title">Student Details</p>
            <div class="rc-student">
                <div class="rc-field"><span>Name</span><strong><?php echo htmlspecialchars($p['name']); ?></strong></div>
                <div class="rc-field"><span>Admission No</span><strong><?php echo htmlspecialchars($p['ad_no']); ?></strong></div>
                <div class="rc-field"><span>Class &amp; Section</span><strong>Class <?php echo htmlspecialchars($p['class']); ?> · <?php echo htmlspecialchars($section); ?></strong></div>
                <div class="rc-field"><span>Roll No</span><strong><?php echo htmlspecialchars($p['roll']); ?></strong></div>
            </div>

            <p class="rc-section-title">Payment Details</p>
            <table class="rc-table">
                <thead>
                    <tr><th>Description</th><th>Method</th><th class="ta-r">Amount</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo htmlspecialchars($p['head_name'] ?: 'General Fee'); ?></td>
                        <td><?php echo htmlspecialchars($p['payment_method']); ?></td>
                        <td class="ta-r">Rs. <?php echo number_format($p['amount'], 2); ?></td>
                    </tr>
                    <?php if (!empty($p['remarks'])): ?>
                    <tr><td colspan="3" style="color:#64748b;font-size:0.85rem"><i class="fas fa-note-sticky" style="color:#94a3b8"></i> <?php echo htmlspecialchars($p['remarks']); ?></td></tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr><td colspan="2">Total Paid</td><td class="ta-r">Rs. <?php echo number_format($p['amount'], 2); ?></td></tr>
                </tfoot>
            </table>

            <p class="rc-words"><strong>Amount in words:</strong> Rupees <?php echo amountInWords($p['amount']); ?> Only</p>
        </div>

        <div class="rc-footer">
            <p class="rc-note"><i class="fas fa-circle-info"></i> This is a computer-generated receipt and does not require a physical signature. Please retain it for your records.</p>
            <div class="rc-sign">
                <?php if ($sigUrl): ?><img class="rc-sign-img" src="<?php echo htmlspecialchars($sigUrl); ?>" alt="Signature"><?php endif; ?>
                <?php if (!empty($sig['name'])): ?><div class="rc-sign-name"><?php echo htmlspecialchars($sig['name']); ?></div><?php endif; ?>
                <div class="rc-sign-line"></div>
                <span><?php echo !empty($sig['designation']) ? htmlspecialchars($sig['designation']) : 'Authorised Signatory'; ?></span>
            </div>
        </div>
    </div>

    <?php if ($autoPrint): ?>
    <script>window.addEventListener('load', function () { window.print(); });</script>
    <?php endif; ?>
</body>
</html>
