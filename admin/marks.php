<?php
$page_title = "Enter Marks";
require_once 'includes/init.php';
require_once '../includes/db_connect.php';
require_once 'includes/erp_helpers.php';

ensureErpSchema($pdo);
$examId = (int) ($_GET['exam_id'] ?? $_POST['exam_id'] ?? 0);
$exam = null;
if ($examId) {
    $stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
    $stmt->execute([$examId]);
    $exam = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_marks']) && $exam) {
    $marks = $_POST['marks'] ?? [];
    $stmt = $pdo->prepare(
        "INSERT INTO student_marks (student_id, exam_subject_id, marks_obtained, grade) VALUES (?,?,?,?)
         ON DUPLICATE KEY UPDATE marks_obtained = VALUES(marks_obtained), grade = VALUES(grade)"
    );
    $delStmt = $pdo->prepare("DELETE FROM student_marks WHERE student_id = ? AND exam_subject_id = ?");
    $subjects = $pdo->prepare("SELECT id, max_marks FROM exam_subjects WHERE exam_id = ?");
    $subjects->execute([$examId]);
    $maxMap = [];
    foreach ($subjects->fetchAll(PDO::FETCH_ASSOC) as $s) {
        $maxMap[$s['id']] = $s['max_marks'];
    }
    foreach ($marks as $studentId => $subMarks) {
        foreach ($subMarks as $subId => $obtained) {
            $obtained = trim((string) $obtained);
            if ($obtained === '') {
                $delStmt->execute([(int) $studentId, (int) $subId]);
                continue;
            }
            $code = strtoupper($obtained);
            if ($code === 'AB' || $code === 'NA') {
                $stmt->execute([(int) $studentId, (int) $subId, null, $code]);
                continue;
            }
            if (!is_numeric($obtained)) {
                continue;
            }
            $grade = calculateGrade((float) $obtained, $maxMap[$subId] ?? 100);
            $stmt->execute([(int) $studentId, (int) $subId, (float) $obtained, $grade]);
        }
    }
    $_SESSION['success_msg'] = 'Marks saved.';
    header('Location: marks.php?exam_id=' . $examId);
    exit;
}

require_once 'includes/header.php';
$exams = $pdo->query("SELECT * FROM exams WHERE status='Active' ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$subjects = [];
$students = [];
$existing = [];
if ($exam) {
    $stmt = $pdo->prepare("SELECT * FROM exam_subjects WHERE exam_id = ? ORDER BY subject_name");
    $stmt->execute([$examId]);
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $students = getStudentsByClassSection($pdo, $exam['class_name']);
    if ($subjects && $students) {
        $stmt = $pdo->prepare("SELECT * FROM student_marks WHERE exam_subject_id IN (" . implode(',', array_column($subjects, 'id')) . ")");
        $stmt->execute();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $m) {
            $existing[$m['student_id']][$m['exam_subject_id']] = $m;
        }
    }
}
?>
<div class="content-top-bar">
    <div class="content-top-main">
        <div class="content-top-icon icon-purple"><i class="fas fa-pen"></i></div>
        <div class="content-top-title"><h2>Enter Marks</h2><p class="content-top-breadcrumb"><a href="exams.php">Exams</a><i class="fas fa-chevron-right"></i><span>Marks</span></p></div>
    </div>
</div>

<div class="form-section-card section-mb">
    <form method="GET" class="category-add-row">
        <div class="form-field form-field-grow"><label>Select Exam</label>
            <select name="exam_id" class="form-input form-select" onchange="this.form.submit()">
                <option value="">Choose exam</option>
                <?php foreach ($exams as $e): ?>
                <option value="<?php echo $e['id']; ?>" <?php echo $examId === (int)$e['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($e['name'] . ' — ' . $e['class_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<?php if ($exam && $subjects && $students): ?>
<form method="POST">
    <input type="hidden" name="save_marks" value="1">
    <input type="hidden" name="exam_id" value="<?php echo $examId; ?>">
    <div class="table-container">
        <div class="table-toolbar"><strong><?php echo htmlspecialchars($exam['name']); ?></strong>
            <button type="submit" class="btn-header-action btn-header-primary"><i class="fas fa-save"></i> Save Marks</button>
        </div>
        <div class="table-wrapper table-scroll-x">
            <table>
                <thead><tr><th>Student</th><th>Roll</th><?php foreach ($subjects as $sub): ?><th><?php echo htmlspecialchars($sub['subject_name']); ?> /<?php echo $sub['max_marks']; ?></th><?php endforeach; ?><th>Report</th></tr></thead>
                <tbody>
                <?php foreach ($students as $s): ?>
                <tr>
                    <td><?php echo htmlspecialchars($s['name']); ?></td>
                    <td><?php echo htmlspecialchars($s['roll']); ?></td>
                    <?php foreach ($subjects as $sub):
                        $row = $existing[$s['id']][$sub['id']] ?? null;
                        $val = '';
                        if ($row) {
                            $g = strtoupper(trim((string) ($row['grade'] ?? '')));
                            if ($g === 'AB' || $g === 'NA') {
                                $val = $g;
                            } elseif ($row['marks_obtained'] !== null && $row['marks_obtained'] !== '') {
                                $val = $row['marks_obtained'];
                            }
                        }
                    ?>
                    <td><input type="text" name="marks[<?php echo $s['id']; ?>][<?php echo $sub['id']; ?>]" class="form-input table-inline-input-sm" value="<?php echo htmlspecialchars((string) $val); ?>" placeholder="marks / AB / NA"></td>
                    <?php endforeach; ?>
                    <td><a href="report_card.php?student_id=<?php echo $s['id']; ?>&exam_id=<?php echo $examId; ?>" target="_blank" class="teal-link">PDF</a></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</form>
<?php elseif ($exam && !$subjects): ?>
<div class="tab-empty-state"><p>Add subjects to this exam first on the <a href="exams.php">Exams</a> page.</p></div>
<?php endif; ?>
<?php require_once 'includes/footer.php'; ?>
