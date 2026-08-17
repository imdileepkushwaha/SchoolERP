<?php
require_once 'includes/init.php';
require_once '../includes/db_connect.php';
require_once 'includes/erp_helpers.php';
require_once 'includes/settings_helpers.php';
require_once 'includes/teacher_helpers.php';
assertSchoolLicenseActive($pdo);
requireModule($pdo, 'exams');
ensureErpSchema($pdo);
ensureSettingsSchema($pdo);

$school = getSchoolProfile($pdo);
$logoUrl = schoolBrandingUrl($school['logo'] ?? '', 'admin');
$examId = (int) ($_GET['exam_id'] ?? 0);

$ids = [];
if (!empty($_GET['ids'])) {
    foreach (explode(',', (string) $_GET['ids']) as $part) {
        $id = (int) trim($part);
        if ($id > 0) {
            $ids[] = $id;
        }
    }
} elseif (!empty($_GET['student_id'])) {
    $ids[] = (int) $_GET['student_id'];
}
$ids = array_values(array_unique($ids));

$stmt = $pdo->prepare('SELECT * FROM exams WHERE id = ?');
$stmt->execute([$examId]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$exam || !$ids) {
    die('Invalid report card request.');
}

$principalSig = getDefaultAuthoritySignature($pdo);
$principalSigUrl = schoolBrandingUrl($principalSig['signature'] ?? '', 'admin');
$principalName = $principalSig['name'] ?? ($school['principal'] ?? '');
$principalRole = $principalSig['designation'] ?? 'Principal';

$cards = [];
foreach ($ids as $studentId) {
    $stmt = $pdo->prepare('SELECT * FROM students WHERE id = ?');
    $stmt->execute([$studentId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$student) {
        continue;
    }
    $marks = getStudentMarksForExam($pdo, $studentId, $examId);
    $totalObt = 0;
    $totalMax = 0;
    foreach ($marks as $m) {
        $totalObt += (float) ($m['marks_obtained'] ?? 0);
        $totalMax += (int) $m['max_marks'];
    }
    $pct = $totalMax ? round($totalObt / $totalMax * 100, 1) : 0;
    $section = trim($student['section'] ?? '') ?: 'A';
    $classTeacher = getClassTeacherForClass($pdo, $student['class'], $section);
    $classTeacherSigUrl = ($classTeacher && !empty($classTeacher['signature'])) ? schoolBrandingUrl($classTeacher['signature'], 'admin') : '';
    $cards[] = [
        'student' => $student,
        'marks' => $marks,
        'totalObt' => $totalObt,
        'totalMax' => $totalMax,
        'pct' => $pct,
        'classTeacherName' => $classTeacher['name'] ?? '',
        'classTeacherSigUrl' => $classTeacherSigUrl,
    ];
}
if (!$cards) {
    die('No students found for report cards.');
}

$schoolAddress = trim((string) ($school['address'] ?? ''));
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Report Card<?php echo count($cards) === 1 ? ' — ' . htmlspecialchars($cards[0]['student']['name']) : 's'; ?></title>
<?php if (!empty($school['favicon'])): ?><link rel="icon" href="<?php echo htmlspecialchars(schoolBrandingUrl($school['favicon'], 'admin')); ?>"><?php endif; ?>
<link rel="stylesheet" href="assets/css/admin.css">
<style>
@media print {
    .no-print { display: none !important; }
    .rc-page { page-break-after: always; box-shadow: none !important; border-color: #334155 !important; }
    .rc-page:last-child { page-break-after: auto; }
    body { padding: 0 !important; background: #fff !important; }
}
body { padding: 24px; max-width: 860px; margin: 0 auto; background: #f8fafc; color: #0f172a; font-family: Georgia, 'Times New Roman', serif; }
.rc-page { background: #fff; border: 2px solid #334155; border-radius: 12px; padding: 28px 32px; margin-bottom: 28px; box-shadow: 0 8px 24px rgba(15,23,42,.06); }
.rc-brand { display: flex; align-items: center; gap: 16px; justify-content: center; text-align: left; margin-bottom: 8px; }
.rc-logo { width: 72px; height: 72px; border-radius: 50%; border: 2px solid #cbd5e1; overflow: hidden; display: flex; align-items: center; justify-content: center; background: #f1f5f9; flex-shrink: 0; color: #047857; font-size: 1.8rem; }
.rc-logo img { width: 100%; height: 100%; object-fit: contain; padding: 8px; }
.rc-brand-text h1 { margin: 0; font-size: 1.55rem; letter-spacing: .02em; }
.rc-brand-text p { margin: 4px 0 0; color: #475569; font-size: .92rem; }
.rc-title { text-align: center; margin: 16px 0 4px; font-size: 1.2rem; text-transform: uppercase; letter-spacing: .08em; }
.rc-sub { text-align: center; margin: 0 0 16px; color: #64748b; }
.rc-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 20px; margin: 16px 0 8px; font-size: .98rem; }
.rc-meta strong { color: #334155; }
table.rc-marks { width: 100%; border-collapse: collapse; margin: 18px 0 8px; font-size: .95rem; }
table.rc-marks th, table.rc-marks td { border: 1px solid #cbd5e1; padding: 9px 10px; }
table.rc-marks th { background: #f1f5f9; text-align: left; }
table.rc-marks .num { text-align: center; }
.rc-sigs { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 48px; text-align: center; }
.rc-sig-box { min-height: 90px; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; }
.rc-sig-box img { max-height: 52px; max-width: 160px; object-fit: contain; margin-bottom: 4px; }
.rc-sig-line { width: 180px; border-top: 1px solid #94a3b8; margin: 0 auto 6px; }
.rc-sig-box span { font-size: .85rem; color: #475569; }
.rc-sig-box strong { font-size: .9rem; }
</style>
</head>
<body>
<div class="no-print" style="margin-bottom:16px;display:flex;gap:10px;flex-wrap:wrap">
    <button onclick="window.print()" class="btn-header-action btn-header-primary"><i class="fas fa-print"></i> Print / Save PDF</button>
    <button onclick="window.close()" class="btn-header-action btn-header-outline">Close</button>
    <span style="align-self:center;color:#64748b;font-family:system-ui,sans-serif;font-size:.9rem"><?php echo count($cards); ?> report card<?php echo count($cards) === 1 ? '' : 's'; ?></span>
</div>

<?php foreach ($cards as $card):
    $student = $card['student'];
?>
<div class="rc-page">
    <div class="rc-brand">
        <div class="rc-logo">
            <?php if ($logoUrl): ?><img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="Logo"><?php else: ?><i class="fas fa-graduation-cap"></i><?php endif; ?>
        </div>
        <div class="rc-brand-text">
            <h1><?php echo htmlspecialchars($school['name'] ?: 'School'); ?></h1>
            <?php if (!empty($school['tagline'])): ?><p><?php echo htmlspecialchars($school['tagline']); ?></p><?php endif; ?>
            <?php if ($schoolAddress !== ''): ?><p><?php echo htmlspecialchars($schoolAddress); ?></p><?php endif; ?>
            <?php if (!empty($school['phone']) || !empty($school['email'])): ?>
            <p><?php echo htmlspecialchars(trim(($school['phone'] ?? '') . (!empty($school['phone']) && !empty($school['email']) ? ' · ' : '') . ($school['email'] ?? ''))); ?></p>
            <?php endif; ?>
        </div>
    </div>
    <h2 class="rc-title">Report Card</h2>
    <p class="rc-sub"><?php echo htmlspecialchars($exam['name']); ?><?php echo !empty($exam['exam_type']) ? ' · ' . htmlspecialchars($exam['exam_type']) : ''; ?></p>
    <hr>
    <div class="rc-meta">
        <div><strong>Name:</strong> <?php echo htmlspecialchars($student['name']); ?></div>
        <div><strong>Admission No:</strong> <?php echo htmlspecialchars($student['ad_no']); ?></div>
        <div><strong>Class:</strong> <?php echo htmlspecialchars($student['class']); ?> (<?php echo htmlspecialchars($student['section'] ?? 'A'); ?>)</div>
        <div><strong>Roll:</strong> <?php echo htmlspecialchars((string) ($student['roll'] ?? '—')); ?></div>
    </div>
    <table class="rc-marks">
        <thead>
            <tr>
                <th>Subject</th>
                <th class="num">Max</th>
                <th class="num">Obtained</th>
                <th class="num">Grade</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($card['marks'] as $m): ?>
        <tr>
            <td><?php echo htmlspecialchars($m['subject_name']); ?></td>
            <td class="num"><?php echo (int) $m['max_marks']; ?></td>
            <td class="num"><?php echo displayVal($m['marks_obtained']); ?></td>
            <td class="num"><?php echo displayVal($m['grade']); ?></td>
        </tr>
        <?php endforeach; ?>
        <tr style="font-weight:bold">
            <td>Total</td>
            <td class="num"><?php echo $card['totalMax']; ?></td>
            <td class="num"><?php echo $card['totalObt']; ?></td>
            <td class="num"><?php echo $card['pct']; ?>%</td>
        </tr>
        </tbody>
    </table>
    <div class="rc-sigs">
        <div class="rc-sig-box">
            <?php if ($card['classTeacherSigUrl']): ?><img src="<?php echo htmlspecialchars($card['classTeacherSigUrl']); ?>" alt=""><?php endif; ?>
            <div class="rc-sig-line"></div>
            <strong><?php echo htmlspecialchars($card['classTeacherName'] ?: 'Class Teacher'); ?></strong>
            <span>Class Teacher</span>
        </div>
        <div class="rc-sig-box">
            <?php if ($principalSigUrl): ?><img src="<?php echo htmlspecialchars($principalSigUrl); ?>" alt=""><?php endif; ?>
            <div class="rc-sig-line"></div>
            <strong><?php echo htmlspecialchars($principalName ?: $principalRole); ?></strong>
            <span><?php echo htmlspecialchars($principalRole); ?></span>
        </div>
    </div>
</div>
<?php endforeach; ?>
</body>
</html>
