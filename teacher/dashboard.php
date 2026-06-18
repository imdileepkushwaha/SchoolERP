<?php
$page_title = 'Dashboard';
$page_subtitle = 'Overview of your teaching schedule';
require_once 'includes/init.php';

$classes = getTeacherClassesTaught($pdo, $teacherId);
$periodCount = countTeacherWeeklyPeriods($pdo, $teacherId);
$todaySlots = getTeacherTodaySchedule($pdo, $teacherId);
$dayName = date('l');
$periodDefaults = defaultPeriodTimes();

require_once 'includes/layout_header.php';
$pwd_changed = isset($_GET['pwd_changed']);
?>

<?php if ($pwd_changed): ?>
<div class="tp-alert-success" style="margin-bottom:20px"><i class="fas fa-check-circle"></i> Password updated successfully. Welcome to your dashboard!</div>
<?php endif; ?>

<div class="tp-stat-grid">
    <div class="tp-stat-card">
        <div class="tp-stat-icon blue"><i class="fas fa-clock"></i></div>
        <div><span>Weekly Periods</span><strong><?php echo $periodCount; ?></strong></div>
    </div>
    <div class="tp-stat-card">
        <div class="tp-stat-icon green"><i class="fas fa-users"></i></div>
        <div><span>Classes</span><strong><?php echo count($classes); ?></strong></div>
    </div>
    <div class="tp-stat-card">
        <div class="tp-stat-icon orange"><i class="fas fa-book"></i></div>
        <div><span>Subject</span><strong style="font-size:1rem"><?php echo htmlspecialchars($teacher['subject']); ?></strong></div>
    </div>
    <div class="tp-stat-card">
        <div class="tp-stat-icon purple"><i class="fas fa-calendar-day"></i></div>
        <div><span>Today</span><strong><?php echo $dayName; ?></strong></div>
    </div>
</div>

<div class="tp-grid-2">
    <div class="tp-card">
        <div class="tp-card-head">
            <h3><i class="fas fa-sun"></i> Today's Schedule</h3>
            <a href="timetable.php" class="tp-card-link">Full timetable →</a>
        </div>
        <div class="tp-schedule-list">
            <?php
            $hasToday = false;
            for ($p = 1; $p <= 8; $p++):
                $slot = $todaySlots[$p] ?? null;
                $def = $periodDefaults[$p];
                $filled = $slot && (trim($slot['class_name'] ?? '') !== '' || trim($slot['subject_name'] ?? '') !== '');
                if ($filled) $hasToday = true;
            ?>
            <div class="tp-schedule-item <?php echo $filled ? '' : 'is-free'; ?>">
                <div class="tp-period-num">P<?php echo $p; ?></div>
                <div class="tp-schedule-body">
                    <?php if ($filled): ?>
                    <strong><?php echo htmlspecialchars($slot['subject_name'] ?: $teacher['subject']); ?></strong>
                    <span><?php echo htmlspecialchars($slot['class_name']); ?><?php echo $slot['section_name'] ? ' · Sec ' . htmlspecialchars($slot['section_name']) : ''; ?><?php echo $slot['room_no'] ? ' · Room ' . htmlspecialchars($slot['room_no']) : ''; ?></span>
                    <?php else: ?>
                    <strong style="color:#94a3b8">Free Period</strong>
                    <span>No class scheduled</span>
                    <?php endif; ?>
                </div>
                <div class="tp-schedule-time"><?php echo $def[0]; ?> – <?php echo $def[1]; ?></div>
            </div>
            <?php endfor; ?>
        </div>
    </div>

    <div class="tp-card">
        <div class="tp-card-head">
            <h3><i class="fas fa-users-class"></i> My Classes</h3>
            <a href="my-classes.php" class="tp-card-link">View all →</a>
        </div>
        <?php if ($classes): ?>
        <div class="tp-class-grid">
            <?php foreach (array_slice($classes, 0, 6) as $c):
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE class = ? AND section = ? AND status = 'Active'");
                $stmt->execute([$c['class_name'], $c['section_name']]);
                $studentCount = (int) $stmt->fetchColumn();
            ?>
            <div class="tp-class-chip">
                <strong><?php echo htmlspecialchars($c['class_name']); ?> (<?php echo htmlspecialchars($c['section_name']); ?>)</strong>
                <span><?php echo htmlspecialchars($c['subject_name'] ?: $teacher['subject']); ?> · <?php echo $studentCount; ?> students</span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="tp-empty"><i class="fas fa-school"></i><p>No classes assigned yet.<br>Contact admin to set up your timetable.</p></div>
        <?php endif; ?>
    </div>
</div>

<div class="tp-grid-2">
    <div class="tp-card">
        <div class="tp-card-head"><h3><i class="fas fa-bolt"></i> Quick Actions</h3></div>
        <div style="display:flex;flex-wrap:wrap;gap:10px">
            <a href="attendance.php" class="tp-btn tp-btn-primary"><i class="far fa-calendar-check"></i> Mark Attendance</a>
            <a href="homework.php" class="tp-btn tp-btn-outline"><i class="fas fa-book-open"></i> Post Homework</a>
            <a href="profile.php" class="tp-btn tp-btn-outline"><i class="fas fa-user"></i> View Profile</a>
            <a href="change-password.php" class="tp-btn tp-btn-outline"><i class="fas fa-lock"></i> Change Password</a>
        </div>
    </div>
    <div class="tp-card">
        <div class="tp-card-head"><h3><i class="fas fa-id-badge"></i> Profile Summary</h3></div>
        <div class="tp-detail-grid">
            <div class="tp-detail-item"><label>Employee ID</label><span><?php echo htmlspecialchars($teacher['employee_id']); ?></span></div>
            <div class="tp-detail-item"><label>Mobile</label><span><?php echo htmlspecialchars($teacher['phone']); ?></span></div>
            <div class="tp-detail-item"><label>Email</label><span><?php echo displayVal($teacher['email']); ?></span></div>
            <div class="tp-detail-item"><label>Class Teacher</label><span><?php echo $teacher['class_assigned'] ? htmlspecialchars($teacher['class_assigned'] . ' (' . ($teacher['section_assigned'] ?: 'A') . ')') : '—'; ?></span></div>
        </div>
    </div>
</div>

<?php require_once 'includes/layout_footer.php'; ?>
