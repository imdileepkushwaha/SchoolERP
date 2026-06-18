<?php
require_once 'includes/init.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../admin/includes/erp_helpers.php';

ensureErpSchema($pdo);
$id = (int) $_SESSION['student_portal_id'];
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$student) {
    session_destroy();
    header('Location: index.php');
    exit;
}

$fee = getStudentFeeSummary($pdo, $id);
$attendance = getStudentAttendanceSummary($pdo, $id, (int)date('Y'), (int)date('n'));
$hwStmt = $pdo->prepare("SELECT * FROM homework WHERE class_name = ? AND section_name = ? ORDER BY due_date DESC LIMIT 10");
$hwStmt->execute([$student['class'], $student['section'] ?? 'A']);
$homework = $hwStmt->fetchAll(PDO::FETCH_ASSOC);
$exams = $pdo->prepare("SELECT * FROM exams WHERE class_name = ? AND status='Active' ORDER BY id DESC LIMIT 5");
$exams->execute([$student['class']]);
$examList = $exams->fetchAll(PDO::FETCH_ASSOC);
$docs = getStudentDocuments($pdo, $id);
$name = htmlspecialchars($student['name']);
?><!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Dashboard — <?php echo $name; ?></title>
<link rel="stylesheet" href="../admin/assets/css/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>body{background:#f8fafc;margin:0}.portal-wrap{max-width:1100px;margin:0 auto;padding:20px}.portal-header{background:#fff;border-radius:16px;padding:20px 24px;display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;box-shadow:0 2px 8px rgba(0,0,0,.04)}.portal-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px}.portal-card{background:#fff;border-radius:16px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,.04)}.portal-card h3{margin:0 0 12px;font-size:1rem;color:#334155}</style>
</head><body>
<div class="portal-wrap">
    <div class="portal-header">
        <div><h1 style="margin:0;font-size:1.25rem">Welcome, <?php echo $name; ?></h1>
        <p style="margin:4px 0 0;color:#64748b">Class <?php echo htmlspecialchars($student['class']); ?> (<?php echo htmlspecialchars($student['section'] ?? 'A'); ?>) · <?php echo htmlspecialchars($student['ad_no']); ?></p></div>
        <a href="logout.php" class="btn-header-action btn-header-outline"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="portal-grid">
        <div class="portal-card">
            <h3><i class="fas fa-user"></i> My Profile</h3>
            <p><strong>Roll:</strong> <?php echo htmlspecialchars($student['roll']); ?></p>
            <p><strong>Mobile:</strong> <?php echo displayVal($student['mobile']); ?></p>
            <p><strong>Email:</strong> <?php echo displayVal($student['email']); ?></p>
            <p><strong>Category:</strong> <?php echo htmlspecialchars($student['category']); ?></p>
        </div>
        <div class="portal-card">
            <h3><i class="far fa-calendar-check"></i> Attendance (This Month)</h3>
            <p>Present: <strong><?php echo $attendance['summary']['Present']; ?></strong> · Absent: <strong><?php echo $attendance['summary']['Absent']; ?></strong></p>
            <p>Late: <?php echo $attendance['summary']['Late']; ?> · Half Day: <?php echo $attendance['summary']['Half Day']; ?></p>
        </div>
        <div class="portal-card">
            <h3><i class="fas fa-file-invoice-dollar"></i> Fees</h3>
            <?php if ($fee): ?>
            <p>Due: Rs. <?php echo number_format($fee['total_due'], 2); ?></p>
            <p>Paid: Rs. <?php echo number_format($fee['total_paid'], 2); ?></p>
            <p>Balance: <strong style="color:#dc2626">Rs. <?php echo number_format($fee['balance'], 2); ?></strong></p>
            <?php endif; ?>
        </div>
        <div class="portal-card">
            <h3><i class="fas fa-book"></i> Homework</h3>
            <?php if ($homework): foreach ($homework as $h): ?>
            <div style="margin-bottom:10px;padding-bottom:10px;border-bottom:1px solid #e2e8f0">
                <strong><?php echo htmlspecialchars($h['title']); ?></strong>
                <?php if ($h['due_date']): ?><br><small>Due: <?php echo htmlspecialchars($h['due_date']); ?></small><?php endif; ?>
                <?php if ($h['description']): ?><p style="margin:4px 0 0;font-size:.9rem"><?php echo nl2br(htmlspecialchars($h['description'])); ?></p><?php endif; ?>
            </div>
            <?php endforeach; else: ?><p style="color:#94a3b8">No homework posted.</p><?php endif; ?>
        </div>
        <div class="portal-card">
            <h3><i class="far fa-edit"></i> Exam Results</h3>
            <?php if ($examList): foreach ($examList as $ex):
                $marks = getStudentMarksForExam($pdo, $id, $ex['id']);
                if (!$marks) continue;
            ?>
            <p><strong><?php echo htmlspecialchars($ex['name']); ?></strong></p>
            <ul style="margin:0 0 12px;padding-left:18px;font-size:.9rem">
            <?php foreach ($marks as $m): if ($m['marks_obtained'] === null) continue; ?>
                <li><?php echo htmlspecialchars($m['subject_name']); ?>: <?php echo $m['marks_obtained']; ?>/<?php echo $m['max_marks']; ?> (<?php echo $m['grade']; ?>)</li>
            <?php endforeach; ?>
            </ul>
            <?php endforeach; else: ?><p style="color:#94a3b8">No results yet.</p><?php endif; ?>
        </div>
        <div class="portal-card">
            <h3><i class="fas fa-folder"></i> Documents</h3>
            <?php if ($docs): foreach ($docs as $d): ?>
            <p><a href="../admin/<?php echo htmlspecialchars($d['file_path']); ?>" target="_blank" class="teal-link"><?php echo htmlspecialchars($d['doc_type']); ?></a></p>
            <?php endforeach; else: ?><p style="color:#94a3b8">No documents.</p><?php endif; ?>
        </div>
    </div>
</div>
</body></html>
