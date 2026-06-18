<?php
$page_title = "Examinations";
require_once 'includes/init.php';
require_once '../includes/db_connect.php';
require_once 'includes/erp_helpers.php';

ensureErpSchema($pdo);
$session = getCurrentSession($pdo);
$class_options = getClassOptions($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add_exam') {
        $pdo->prepare("INSERT INTO exams (name, exam_type, class_name, session_id, start_date, end_date) VALUES (?,?,?,?,?,?)")
            ->execute([
                trim($_POST['name']), trim($_POST['exam_type'] ?? 'Term'),
                trim($_POST['class_name']), $session['id'] ?? null,
                $_POST['start_date'] ?: null, $_POST['end_date'] ?: null,
            ]);
        $_SESSION['success_msg'] = 'Exam created.';
    } elseif ($action === 'add_subject' && isset($_POST['exam_id'])) {
        $pdo->prepare("INSERT INTO exam_subjects (exam_id, subject_name, max_marks) VALUES (?,?,?)")
            ->execute([(int)$_POST['exam_id'], trim($_POST['subject_name']), (int)($_POST['max_marks'] ?? 100)]);
        $_SESSION['success_msg'] = 'Subject added.';
    }
    header('Location: exams.php');
    exit;
}

require_once 'includes/header.php';
$exams = $pdo->query("SELECT e.*, (SELECT COUNT(*) FROM exam_subjects es WHERE es.exam_id = e.id) AS subject_count FROM exams e ORDER BY e.id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="content-top-bar">
    <div class="content-top-main">
        <div class="content-top-icon icon-purple"><i class="far fa-edit"></i></div>
        <div class="content-top-title"><h2>Examinations</h2><p class="content-top-breadcrumb"><a href="dashboard.php">Dashboard</a><i class="fas fa-chevron-right"></i><span>Exams</span></p></div>
    </div>
    <div class="content-top-actions"><a href="marks.php" class="btn-header-action btn-header-primary"><i class="fas fa-pen"></i> Enter Marks</a></div>
</div>

<div class="form-section-card section-mb">
    <form method="POST" class="category-add-form">
        <input type="hidden" name="action" value="add_exam">
        <div class="category-add-row erp-filter-row-4">
            <div class="form-field"><label>Exam Name</label><input type="text" name="name" class="form-input" placeholder="Half Yearly 2025" required></div>
            <div class="form-field"><label>Type</label><select name="exam_type" class="form-input form-select"><option>Term</option><option>Unit Test</option><option>Annual</option><option>Pre-Board</option></select></div>
            <div class="form-field"><label>Class</label><select name="class_name" class="form-input form-select" required><?php foreach ($class_options as $c): ?><option value="<?php echo htmlspecialchars($c); ?>"><?php echo htmlspecialchars($c); ?></option><?php endforeach; ?></select></div>
            <div class="form-field category-add-btn-wrap"><label>&nbsp;</label><button type="submit" class="btn-header-action btn-header-primary category-add-btn"><i class="fas fa-plus"></i> Create Exam</button></div>
        </div>
    </form>
</div>

<div class="table-container">
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Exam</th><th>Class</th><th>Type</th><th>Subjects</th><th>Add Subject</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($exams as $e): ?>
            <tr>
                <td><?php echo htmlspecialchars($e['name']); ?></td>
                <td><?php echo htmlspecialchars($e['class_name']); ?></td>
                <td><?php echo htmlspecialchars($e['exam_type']); ?></td>
                <td><?php echo (int)$e['subject_count']; ?></td>
                <td>
                    <form method="POST" class="section-add-inline">
                        <input type="hidden" name="action" value="add_subject">
                        <input type="hidden" name="exam_id" value="<?php echo $e['id']; ?>">
                        <input type="text" name="subject_name" class="form-input section-add-input" placeholder="Math" required>
                        <input type="number" name="max_marks" class="form-input table-inline-input-sm" value="100" min="1">
                        <button type="submit" class="btn-header-action btn-header-outline btn-sm"><i class="fas fa-plus"></i></button>
                    </form>
                </td>
                <td><a href="marks.php?exam_id=<?php echo $e['id']; ?>" class="teal-link">Enter Marks</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
