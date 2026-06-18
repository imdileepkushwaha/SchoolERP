<?php
$page_title = 'Homework';
$page_subtitle = 'Post and manage homework for your classes';
require_once 'includes/init.php';

$session = getCurrentSession($pdo);
$classes = getTeacherClassesTaught($pdo, $teacherId);
$class = trim($_GET['class'] ?? $_POST['class_name'] ?? '');
$section = trim($_GET['section'] ?? $_POST['section_name'] ?? 'A');
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_homework'])) {
    $class = trim($_POST['class_name']);
    $section = trim($_POST['section_name'] ?? 'A');
    $title = trim($_POST['title'] ?? '');
    if (!teacherCanAccessClass($pdo, $teacherId, $class, $section)) {
        $error = 'You cannot post homework for this class.';
    } elseif ($title === '') {
        $error = 'Title is required.';
    } else {
        $pdo->prepare("INSERT INTO homework (class_name, section_name, title, description, due_date, session_id) VALUES (?,?,?,?,?,?)")
            ->execute([
                $class, $section, $title,
                trim($_POST['description'] ?? ''),
                $_POST['due_date'] ?: null,
                $session['id'] ?? null,
            ]);
        $message = 'Homework posted successfully.';
    }
}

$myHomework = [];
if ($classes) {
    $conditions = [];
    $params = [];
    foreach ($classes as $c) {
        $conditions[] = '(class_name = ? AND section_name = ?)';
        $params[] = $c['class_name'];
        $params[] = $c['section_name'];
    }
    $sql = "SELECT * FROM homework WHERE " . implode(' OR ', $conditions) . " ORDER BY id DESC LIMIT 30";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $myHomework = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

require_once 'includes/layout_header.php';
?>

<?php if ($message): ?><div class="tp-alert-success" style="margin-bottom:20px"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="tp-alert-error" style="margin-bottom:20px"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<div class="tp-grid-2">
    <div class="tp-card">
        <div class="tp-card-head"><h3><i class="fas fa-plus-circle"></i> Post New Homework</h3></div>
        <?php if ($classes): ?>
        <form method="POST" class="tp-form-grid" style="grid-template-columns:1fr">
            <input type="hidden" name="add_homework" value="1">
            <div class="tp-field"><label>Class</label>
                <select name="class_name" id="hwClass" required>
                    <?php foreach ($classes as $c): ?>
                    <option value="<?php echo htmlspecialchars($c['class_name']); ?>" data-section="<?php echo htmlspecialchars($c['section_name']); ?>" <?php echo $class === $c['class_name'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($c['class_name'] . ' (' . $c['section_name'] . ')'); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <input type="hidden" name="section_name" id="hwSection" value="<?php echo htmlspecialchars($section ?: ($classes[0]['section_name'] ?? 'A')); ?>">
            <div class="tp-field"><label>Title</label><input type="text" name="title" placeholder="e.g. Chapter 5 exercises" required></div>
            <div class="tp-field"><label>Due Date</label><input type="date" name="due_date"></div>
            <div class="tp-field"><label>Description</label><textarea name="description" rows="4" placeholder="Instructions for students..."></textarea></div>
            <div><button type="submit" class="tp-btn tp-btn-primary"><i class="fas fa-paper-plane"></i> Post Homework</button></div>
        </form>
        <?php else: ?>
        <div class="tp-empty"><p>No classes assigned yet.</p></div>
        <?php endif; ?>
    </div>

    <div class="tp-card">
        <div class="tp-card-head"><h3><i class="fas fa-list"></i> Recent Homework</h3></div>
        <?php if ($myHomework): ?>
        <div class="tp-schedule-list">
            <?php foreach ($myHomework as $h): ?>
            <div class="tp-schedule-item">
                <div class="tp-period-num" style="background:#7c3aed;font-size:0.7rem">HW</div>
                <div class="tp-schedule-body">
                    <strong><?php echo htmlspecialchars($h['title']); ?></strong>
                    <span><?php echo htmlspecialchars($h['class_name'] . ' (' . $h['section_name'] . ')'); ?><?php echo $h['due_date'] ? ' · Due ' . date('d M Y', strtotime($h['due_date'])) : ''; ?></span>
                    <?php if ($h['description']): ?><p style="margin:6px 0 0;font-size:0.85rem;color:#64748b"><?php echo nl2br(htmlspecialchars($h['description'])); ?></p><?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="tp-empty"><i class="fas fa-book"></i><p>No homework posted yet.</p></div>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    var cls = document.getElementById('hwClass');
    var sec = document.getElementById('hwSection');
    if (cls && sec) {
        function sync() {
            var opt = cls.options[cls.selectedIndex];
            sec.value = opt ? (opt.getAttribute('data-section') || 'A') : 'A';
        }
        cls.addEventListener('change', sync);
        sync();
    }
})();
</script>

<?php require_once 'includes/layout_footer.php'; ?>
