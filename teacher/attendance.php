<?php
$page_title = 'Mark Attendance';
$page_subtitle = 'Record daily attendance for your classes';
require_once 'includes/init.php';

$session = getCurrentSession($pdo);
$classes = getTeacherClassesTaught($pdo, $teacherId);
$class = trim($_GET['class'] ?? $_POST['class'] ?? '');
$section = trim($_GET['section'] ?? $_POST['section'] ?? 'A');
$date = trim($_GET['date'] ?? $_POST['date'] ?? date('Y-m-d'));
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    $class = trim($_POST['class']);
    $section = trim($_POST['section']);
    if (!teacherCanAccessClass($pdo, $teacherId, $class, $section)) {
        $error = 'You are not assigned to this class.';
    } else {
        $statuses = $_POST['status'] ?? [];
        $clean = [];
        foreach ($statuses as $sid => $st) {
            if (in_array($st, ['Present', 'Absent', 'Late', 'Half Day'], true)) {
                $clean[(int) $sid] = $st;
            }
        }
        saveAttendance($pdo, $_POST['date'], $class, $section, $clean, $session['id'] ?? null);
        $message = count($clean) . ' attendance record(s) saved successfully.';
        $date = $_POST['date'];
    }
}

$students = [];
$existing = [];
if ($class && teacherCanAccessClass($pdo, $teacherId, $class, $section)) {
    $students = getStudentsByClassSection($pdo, $class, $section);
    if ($students) {
        $ids = array_column($students, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT student_id, status FROM attendance_records WHERE attendance_date = ? AND student_id IN ($placeholders)");
        $stmt->execute(array_merge([$date], $ids));
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $existing[$r['student_id']] = $r['status'];
        }
    }
}

require_once 'includes/layout_header.php';
?>

<?php if ($message): ?><div class="tp-alert-success" style="margin-bottom:20px"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="tp-alert-error" style="margin-bottom:20px"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<div class="tp-card" style="margin-bottom:20px">
    <form method="GET" class="tp-form-grid">
        <div class="tp-field"><label>Date</label><input type="date" name="date" value="<?php echo htmlspecialchars($date); ?>" required></div>
        <div class="tp-field"><label>Class</label>
            <select name="class" required>
                <option value="">Select class</option>
                <?php foreach ($classes as $c): ?>
                <option value="<?php echo htmlspecialchars($c['class_name']); ?>" data-section="<?php echo htmlspecialchars($c['section_name']); ?>" <?php echo $class === $c['class_name'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($c['class_name'] . ' (' . $c['section_name'] . ')'); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="tp-field"><label>Section</label>
            <input type="text" name="section" value="<?php echo htmlspecialchars($section); ?>" readonly>
        </div>
        <div class="tp-field" style="display:flex;align-items:flex-end">
            <button type="submit" class="tp-btn tp-btn-primary"><i class="fas fa-search"></i> Load Students</button>
        </div>
    </form>
</div>

<?php if ($class && !$students && !$error): ?>
<div class="tp-card"><div class="tp-empty"><i class="fas fa-user-slash"></i><p>No active students in this class.</p></div></div>
<?php elseif ($students): ?>
<form method="POST">
    <input type="hidden" name="save_attendance" value="1">
    <input type="hidden" name="date" value="<?php echo htmlspecialchars($date); ?>">
    <input type="hidden" name="class" value="<?php echo htmlspecialchars($class); ?>">
    <input type="hidden" name="section" value="<?php echo htmlspecialchars($section); ?>">
    <div class="tp-card">
        <div class="tp-card-head">
            <h3><i class="far fa-calendar-check"></i> <?php echo count($students); ?> Students — <?php echo htmlspecialchars($class); ?> (<?php echo htmlspecialchars($section); ?>)</h3>
            <button type="submit" class="tp-btn tp-btn-primary"><i class="fas fa-save"></i> Save Attendance</button>
        </div>
        <div class="tp-table-wrap">
            <table class="tp-table">
                <thead><tr><th>Roll</th><th>Name</th><th>Admission No</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($students as $s): $st = $existing[$s['id']] ?? 'Present'; ?>
                <tr>
                    <td><?php echo htmlspecialchars($s['roll']); ?></td>
                    <td><strong><?php echo htmlspecialchars($s['name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($s['ad_no']); ?></td>
                    <td>
                        <select name="status[<?php echo $s['id']; ?>]" style="padding:8px;border-radius:8px;border:1px solid var(--tp-border)">
                            <?php foreach (['Present', 'Absent', 'Late', 'Half Day'] as $opt): ?>
                            <option value="<?php echo $opt; ?>" <?php echo $st === $opt ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</form>
<?php elseif (!$classes): ?>
<div class="tp-card"><div class="tp-empty"><i class="fas fa-school"></i><p>No classes assigned. Contact admin to set up your timetable.</p></div></div>
<?php endif; ?>

<script>
(function () {
    var cls = document.querySelector('select[name="class"]');
    var sec = document.querySelector('input[name="section"]');
    if (cls && sec) {
        cls.addEventListener('change', function () {
            var opt = this.options[this.selectedIndex];
            sec.value = opt ? (opt.getAttribute('data-section') || 'A') : 'A';
        });
        if (cls.value) cls.dispatchEvent(new Event('change'));
    }
})();
</script>

<?php require_once 'includes/layout_footer.php'; ?>
