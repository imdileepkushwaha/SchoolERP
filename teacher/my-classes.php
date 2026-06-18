<?php
$page_title = 'My Classes';
$page_subtitle = 'Classes assigned to you';
require_once 'includes/init.php';

$classes = getTeacherClassesTaught($pdo, $teacherId);

require_once 'includes/layout_header.php';
?>

<?php if ($classes): ?>
<div class="tp-class-grid" style="grid-template-columns:repeat(auto-fill,minmax(280px,1fr))">
    <?php foreach ($classes as $c):
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE class = ? AND section = ? AND status = 'Active'");
        $stmt->execute([$c['class_name'], $c['section_name']]);
        $studentCount = (int) $stmt->fetchColumn();

        $attStmt = $pdo->prepare(
            "SELECT status, COUNT(*) AS cnt FROM attendance_records
             WHERE class_name = ? AND section_name = ? AND attendance_date = ?
             GROUP BY status"
        );
        $attStmt->execute([$c['class_name'], $c['section_name'], date('Y-m-d')]);
        $todayAtt = ['Present' => 0, 'Absent' => 0];
        foreach ($attStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $todayAtt[$row['status']] = (int) $row['cnt'];
        }
    ?>
    <div class="tp-card">
        <div class="tp-card-head">
            <h3><i class="fas fa-school"></i> <?php echo htmlspecialchars($c['class_name']); ?> (<?php echo htmlspecialchars($c['section_name']); ?>)</h3>
        </div>
        <div class="tp-detail-grid" style="grid-template-columns:1fr;margin-bottom:16px">
            <div class="tp-detail-item"><label>Subject</label><span><?php echo htmlspecialchars($c['subject_name'] ?: $teacher['subject']); ?></span></div>
            <div class="tp-detail-item"><label>Students</label><span><?php echo $studentCount; ?> active</span></div>
            <div class="tp-detail-item"><label>Today's Attendance</label><span><?php echo $todayAtt['Present']; ?> present · <?php echo $todayAtt['Absent']; ?> absent</span></div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a href="attendance.php?class=<?php echo urlencode($c['class_name']); ?>&section=<?php echo urlencode($c['section_name']); ?>" class="tp-btn tp-btn-primary tp-btn-sm"><i class="far fa-calendar-check"></i> Attendance</a>
            <a href="homework.php?class=<?php echo urlencode($c['class_name']); ?>&section=<?php echo urlencode($c['section_name']); ?>" class="tp-btn tp-btn-outline"><i class="fas fa-book"></i> Homework</a>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="tp-card">
    <div class="tp-empty">
        <i class="fas fa-users"></i>
        <p>No classes found.<br>Your timetable or class assignment has not been configured yet.</p>
    </div>
</div>
<?php endif; ?>

<?php require_once 'includes/layout_footer.php'; ?>
