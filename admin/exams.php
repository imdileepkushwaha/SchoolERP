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
                trim($_POST['name']),
                trim($_POST['exam_type'] ?? 'Term'),
                trim($_POST['class_name']),
                $session['id'] ?? null,
                $_POST['start_date'] ?: null,
                $_POST['end_date'] ?: null,
            ]);
        $_SESSION['success_msg'] = 'Exam created.';
    } elseif ($action === 'add_subject' && isset($_POST['exam_id'])) {
        $pdo->prepare("INSERT INTO exam_subjects (exam_id, subject_name, max_marks) VALUES (?,?,?)")
            ->execute([(int) $_POST['exam_id'], trim($_POST['subject_name']), (int) ($_POST['max_marks'] ?? 100)]);
        $_SESSION['success_msg'] = 'Subject added.';
    } elseif ($action === 'update_exam' && isset($_POST['id'])) {
        $pdo->prepare('UPDATE exams SET name=?, exam_type=?, class_name=?, start_date=?, end_date=? WHERE id=?')
            ->execute([
                trim($_POST['name'] ?? ''),
                trim($_POST['exam_type'] ?? 'Term'),
                trim($_POST['class_name'] ?? ''),
                $_POST['start_date'] ?: null,
                $_POST['end_date'] ?: null,
                (int) $_POST['id'],
            ]);
        $_SESSION['success_msg'] = 'Exam updated.';
    } elseif ($action === 'update_exam_subject' && isset($_POST['id'])) {
        $pdo->prepare('UPDATE exam_subjects SET subject_name=?, max_marks=? WHERE id=?')
            ->execute([
                trim($_POST['subject_name'] ?? ''),
                (int) ($_POST['max_marks'] ?? 100),
                (int) $_POST['id'],
            ]);
        $_SESSION['success_msg'] = 'Subject updated.';
    } elseif ($action === 'delete_subject' && isset($_POST['id'])) {
        $subId = (int) $_POST['id'];
        $pdo->prepare('DELETE FROM student_marks WHERE exam_subject_id = ?')->execute([$subId]);
        $pdo->prepare('DELETE FROM exam_subjects WHERE id = ?')->execute([$subId]);
        $_SESSION['success_msg'] = 'Subject deleted.';
    } elseif ($action === 'delete_exam' && isset($_POST['id'])) {
        $examId = (int) $_POST['id'];
        $pdo->prepare(
            'DELETE sm FROM student_marks sm
             INNER JOIN exam_subjects es ON es.id = sm.exam_subject_id
             WHERE es.exam_id = ?'
        )->execute([$examId]);
        $pdo->prepare('DELETE FROM exam_subjects WHERE exam_id = ?')->execute([$examId]);
        $pdo->prepare('DELETE FROM exams WHERE id = ?')->execute([$examId]);
        $_SESSION['success_msg'] = 'Exam deleted.';
    }
    header('Location: exams.php');
    exit;
}

function examTypeMeta($type) {
    switch ($type) {
        case 'Unit Test':
            return ['icon' => 'fa-clipboard-list', 'tone' => 'blue', 'label' => 'Unit Test'];
        case 'Annual':
            return ['icon' => 'fa-graduation-cap', 'tone' => 'purple', 'label' => 'Annual'];
        case 'Pre-Board':
            return ['icon' => 'fa-school', 'tone' => 'orange', 'label' => 'Pre-Board'];
        default:
            return ['icon' => 'fa-file-alt', 'tone' => 'teal', 'label' => 'Term Exam'];
    }
}

require_once 'includes/header.php';
$exams = $pdo->query(
    "SELECT e.*, (SELECT COUNT(*) FROM exam_subjects es WHERE es.exam_id = e.id) AS subject_count
     FROM exams e ORDER BY e.id DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$subjectsByExam = [];
foreach ($pdo->query("SELECT * FROM exam_subjects ORDER BY subject_name")->fetchAll(PDO::FETCH_ASSOC) as $sub) {
    $subjectsByExam[$sub['exam_id']][] = $sub;
}

$totalSubjects = array_sum(array_column($exams, 'subject_count'));
$classesUsed = count(array_unique(array_column($exams, 'class_name')));
$typeCounts = [];
foreach ($exams as $e) {
    $type = $e['exam_type'] ?: 'Term';
    $typeCounts[$type] = ($typeCounts[$type] ?? 0) + 1;
}
?>

<div class="content-top-bar">
    <div class="content-top-main">
        <div class="content-top-icon icon-purple"><i class="far fa-edit"></i></div>
        <div class="content-top-title">
            <h2>Examinations</h2>
            <p class="content-top-breadcrumb">
                <a href="dashboard.php">Dashboard</a><i class="fas fa-chevron-right"></i><span>Exams</span>
            </p>
        </div>
    </div>
    <div class="content-top-actions">
        <a href="exam_analytics.php" class="btn-header-action btn-header-outline"><i class="fas fa-chart-pie"></i> Analytics</a>
        <a href="marks.php" class="btn-header-action btn-header-primary"><i class="fas fa-pen"></i> Enter Marks</a>
    </div>
</div>

<div class="exm-hero">
    <div class="exm-hero-main">
        <p class="exm-hero-label"><i class="fas fa-clipboard-check"></i> Exam management</p>
        <h3>Create exams &amp; configure subjects</h3>
        <p>Session <?php echo htmlspecialchars($session['name'] ?? '—'); ?> · Set up papers, max marks, then enter results.</p>
    </div>
    <div class="exm-hero-stats">
        <div class="exm-hero-stat"><span>Exams</span><strong><?php echo count($exams); ?></strong></div>
        <div class="exm-hero-stat"><span>Subjects</span><strong><?php echo $totalSubjects; ?></strong></div>
        <div class="exm-hero-stat"><span>Classes</span><strong><?php echo $classesUsed; ?></strong></div>
        <div class="exm-hero-stat"><span>Session</span><strong style="font-size:0.88rem"><?php echo htmlspecialchars($session['name'] ?? '—'); ?></strong></div>
    </div>
</div>

<div class="exm-type-strip">
    <div class="exm-type-card tone-teal"><i class="fas fa-file-alt"></i><strong>Term</strong><span>Mid / half-yearly exams</span></div>
    <div class="exm-type-card tone-blue"><i class="fas fa-clipboard-list"></i><strong>Unit Test</strong><span>Short assessments</span></div>
    <div class="exm-type-card tone-purple"><i class="fas fa-graduation-cap"></i><strong>Annual</strong><span>Final board-style exams</span></div>
    <div class="exm-type-card tone-orange"><i class="fas fa-school"></i><strong>Pre-Board</strong><span>Practice before boards</span></div>
</div>

<div class="form-section-card exm-create-card section-mb">
    <div class="exm-card-head">
        <div class="exm-card-head-icon"><i class="fas fa-plus-circle"></i></div>
        <div>
            <h4>Create New Exam</h4>
            <p>Add an exam for a class, then attach subjects with max marks</p>
        </div>
    </div>
    <form method="POST" class="exm-create-form">
        <input type="hidden" name="action" value="add_exam">
        <div class="form-grid form-grid-2 form-grid-spaced">
            <div class="form-field">
                <label><i class="fas fa-pen"></i> Exam Name</label>
                <input type="text" name="name" class="form-input" placeholder="Half Yearly 2026" required>
            </div>
            <div class="form-field">
                <label><i class="fas fa-tag"></i> Exam Type</label>
                <select name="exam_type" class="form-input form-select">
                    <option>Term</option>
                    <option>Unit Test</option>
                    <option>Annual</option>
                    <option>Pre-Board</option>
                </select>
            </div>
            <div class="form-field">
                <label><i class="fas fa-school"></i> Class</label>
                <select name="class_name" class="form-input form-select" required>
                    <?php foreach ($class_options as $c): ?>
                    <option value="<?php echo htmlspecialchars($c); ?>"><?php echo htmlspecialchars($c); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label><i class="fas fa-calendar-alt"></i> Start Date</label>
                <input type="date" name="start_date" class="form-input">
            </div>
            <div class="form-field">
                <label><i class="fas fa-calendar-check"></i> End Date</label>
                <input type="date" name="end_date" class="form-input">
            </div>
        </div>
        <div class="exm-create-actions">
            <button type="submit" class="btn-header-action btn-header-primary"><i class="fas fa-plus"></i> Create Exam</button>
        </div>
    </form>
</div>

<?php if ($exams): ?>
<div class="exm-list-head">
    <h4><i class="fas fa-list"></i> All Examinations</h4>
    <span><?php echo count($exams); ?> exam<?php echo count($exams) === 1 ? '' : 's'; ?></span>
</div>
<div class="exm-list">
    <?php foreach ($exams as $e):
        $meta = examTypeMeta($e['exam_type']);
        $subjects = $subjectsByExam[$e['id']] ?? [];
        $dateRange = '';
        if ($e['start_date']) {
            $dateRange = date('d M Y', strtotime($e['start_date']));
            if ($e['end_date'] && $e['end_date'] !== $e['start_date']) {
                $dateRange .= ' → ' . date('d M Y', strtotime($e['end_date']));
            }
        }
    ?>
    <div class="form-section-card exm-exam-card">
        <div class="exm-exam-head">
            <div class="exm-exam-icon tone-<?php echo $meta['tone']; ?>"><i class="fas <?php echo $meta['icon']; ?>"></i></div>
            <div class="exm-exam-title">
                <div class="exm-exam-top">
                    <strong><?php echo htmlspecialchars($e['name']); ?></strong>
                    <span class="exm-type-badge tone-<?php echo $meta['tone']; ?>"><?php echo htmlspecialchars($e['exam_type']); ?></span>
                    <span class="exm-class-pill"><i class="fas fa-school"></i> <?php echo htmlspecialchars($e['class_name']); ?></span>
                </div>
                <div class="exm-exam-meta">
                    <?php if ($dateRange): ?><span><i class="fas fa-calendar"></i> <?php echo $dateRange; ?></span><?php endif; ?>
                    <span><i class="fas fa-book"></i> <?php echo (int) $e['subject_count']; ?> subject<?php echo (int) $e['subject_count'] === 1 ? '' : 's'; ?></span>
                    <?php if ($e['status'] === 'Inactive'): ?><span class="exm-inactive-badge">Inactive</span><?php endif; ?>
                </div>
            </div>
            <div class="exm-exam-actions">
                <a href="marks.php?exam_id=<?php echo (int) $e['id']; ?>" class="exm-action-btn is-primary"><i class="fas fa-pen"></i> Enter Marks</a>
                <a href="exam_analytics.php?exam_id=<?php echo (int) $e['id']; ?>" class="exm-action-btn"><i class="fas fa-chart-bar"></i> Analytics</a>
                <button type="button" class="action-btn edit-btn" title="Edit"
                    data-erp-edit-exam
                    data-id="<?php echo (int) $e['id']; ?>"
                    data-name="<?php echo htmlspecialchars($e['name'], ENT_QUOTES); ?>"
                    data-type="<?php echo htmlspecialchars($e['exam_type'] ?? 'Term', ENT_QUOTES); ?>"
                    data-class="<?php echo htmlspecialchars($e['class_name'], ENT_QUOTES); ?>"
                    data-start="<?php echo htmlspecialchars($e['start_date'] ?? '', ENT_QUOTES); ?>"
                    data-end="<?php echo htmlspecialchars($e['end_date'] ?? '', ENT_QUOTES); ?>">
                    <i class="fas fa-pen"></i>
                </button>
                <form method="POST" style="margin:0" onsubmit="return confirm('Delete this exam, its subjects and all marks?');">
                    <input type="hidden" name="action" value="delete_exam">
                    <input type="hidden" name="id" value="<?php echo (int) $e['id']; ?>">
                    <button type="submit" class="exm-action-btn" style="color:#b91c1c" title="Delete exam"><i class="fas fa-trash"></i> Delete</button>
                </form>
            </div>
        </div>

        <div class="exm-subjects-section">
            <p class="exm-subjects-label">Subjects &amp; max marks</p>
            <?php if ($subjects): ?>
            <div class="exm-subject-chips">
                <?php foreach ($subjects as $sub): ?>
                <span class="exm-subject-chip" style="border-radius:12px">
                    <?php echo htmlspecialchars($sub['subject_name']); ?>
                    <em>/ <?php echo (int) $sub['max_marks']; ?></em>
                    <button type="button" class="action-btn" title="Edit subject" style="width:22px;height:22px;margin-left:4px"
                        data-erp-edit-exam-sub
                        data-id="<?php echo (int) $sub['id']; ?>"
                        data-name="<?php echo htmlspecialchars($sub['subject_name'], ENT_QUOTES); ?>"
                        data-max="<?php echo (int) $sub['max_marks']; ?>">
                        <i class="fas fa-pen"></i>
                    </button>
                    <form method="POST" style="display:inline;margin:0" onsubmit="return confirm('Delete this subject and its marks?');">
                        <input type="hidden" name="action" value="delete_subject">
                        <input type="hidden" name="id" value="<?php echo (int) $sub['id']; ?>">
                        <button type="submit" class="action-btn delete-btn" title="Delete subject" style="width:22px;height:22px;margin-left:2px"><i class="fas fa-times"></i></button>
                    </form>
                </span>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="exm-no-subjects"><i class="fas fa-info-circle"></i> No subjects yet — add at least one to enter marks.</p>
            <?php endif; ?>

            <form method="POST" class="exm-add-subject-form">
                <input type="hidden" name="action" value="add_subject">
                <input type="hidden" name="exam_id" value="<?php echo (int) $e['id']; ?>">
                <input type="text" name="subject_name" class="form-input" placeholder="Subject name, e.g. Mathematics" required>
                <input type="number" name="max_marks" class="form-input exm-marks-input" value="100" min="1" max="1000" title="Max marks">
                <button type="submit" class="btn-header-action btn-header-outline"><i class="fas fa-plus"></i> Add Subject</button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="form-section-card exm-empty section-mb">
    <div class="exm-empty-icon"><i class="far fa-edit"></i></div>
    <h4>No examinations yet</h4>
    <p>Create your first exam using the form above, then add subjects for each paper.</p>
</div>
<?php endif; ?>

<div class="fs-modal" id="examEditModal" aria-hidden="true">
    <div class="fs-modal-backdrop" data-exam-modal-close></div>
    <div class="fs-modal-panel" role="dialog" aria-modal="true" aria-labelledby="examEditModalTitle">
        <div class="fs-modal-header">
            <div class="fs-modal-header-icon is-edit"><i class="fas fa-clipboard-list"></i></div>
            <div>
                <h3 id="examEditModalTitle">Edit Exam</h3>
                <p>Update exam name, type, class and dates</p>
            </div>
            <button type="button" class="fs-modal-close" data-exam-modal-close aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" class="fs-modal-form">
            <input type="hidden" name="action" value="update_exam">
            <input type="hidden" name="id" id="examEditId" value="">
            <div class="fs-modal-body">
                <div class="form-field"><label for="examEditName">Exam Name</label><input type="text" name="name" id="examEditName" class="form-input" required></div>
                <div class="form-field"><label for="examEditType">Exam Type</label>
                    <select name="exam_type" id="examEditType" class="form-input form-select">
                        <?php foreach (['Term', 'Unit Test', 'Annual', 'Pre-Board'] as $t): ?>
                        <option value="<?php echo $t; ?>"><?php echo $t; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field"><label for="examEditClass">Class</label>
                    <select name="class_name" id="examEditClass" class="form-input form-select" required>
                        <?php foreach ($class_options as $c): ?>
                        <option value="<?php echo htmlspecialchars($c); ?>"><?php echo htmlspecialchars($c); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field"><label for="examEditStart">Start Date</label><input type="date" name="start_date" id="examEditStart" class="form-input"></div>
                <div class="form-field"><label for="examEditEnd">End Date</label><input type="date" name="end_date" id="examEditEnd" class="form-input"></div>
            </div>
            <div class="fs-modal-footer">
                <button type="button" class="btn-header-action btn-header-outline" data-exam-modal-close>Cancel</button>
                <button type="submit" class="btn-header-action btn-header-primary"><i class="fas fa-check"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

<div class="fs-modal" id="examSubEditModal" aria-hidden="true">
    <div class="fs-modal-backdrop" data-exam-sub-modal-close></div>
    <div class="fs-modal-panel" role="dialog" aria-modal="true" aria-labelledby="examSubEditModalTitle">
        <div class="fs-modal-header">
            <div class="fs-modal-header-icon is-edit"><i class="fas fa-book"></i></div>
            <div>
                <h3 id="examSubEditModalTitle">Edit Exam Subject</h3>
                <p>Update subject name and max marks</p>
            </div>
            <button type="button" class="fs-modal-close" data-exam-sub-modal-close aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" class="fs-modal-form">
            <input type="hidden" name="action" value="update_exam_subject">
            <input type="hidden" name="id" id="examSubEditId" value="">
            <div class="fs-modal-body">
                <div class="form-field"><label for="examSubEditName">Subject Name</label><input type="text" name="subject_name" id="examSubEditName" class="form-input" required></div>
                <div class="form-field"><label for="examSubEditMax">Max Marks</label><input type="number" name="max_marks" id="examSubEditMax" class="form-input" min="1" max="1000" required></div>
            </div>
            <div class="fs-modal-footer">
                <button type="button" class="btn-header-action btn-header-outline" data-exam-sub-modal-close>Cancel</button>
                <button type="submit" class="btn-header-action btn-header-primary"><i class="fas fa-check"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    function wireModal(modalId, openAttr, closeAttr, fillFn, focusId) {
        var modal = document.getElementById(modalId);
        if (!modal) return;
        if (modal.parentElement !== document.body) document.body.appendChild(modal);
        function openModal() {
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('fs-modal-open');
            var focusEl = document.getElementById(focusId);
            if (focusEl) setTimeout(function () { focusEl.focus(); if (focusEl.select) focusEl.select(); }, 120);
        }
        function closeModal() {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            if (!document.querySelector('.fs-modal.is-open')) document.body.classList.remove('fs-modal-open');
        }
        document.querySelectorAll('[' + openAttr + ']').forEach(function (btn) {
            btn.addEventListener('click', function () { fillFn(btn); openModal(); });
        });
        modal.querySelectorAll('[' + closeAttr + ']').forEach(function (el) {
            el.addEventListener('click', closeModal);
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modal.classList.contains('is-open')) closeModal();
        });
    }
    wireModal('examEditModal', 'data-erp-edit-exam', 'data-exam-modal-close', function (btn) {
        document.getElementById('examEditId').value = btn.getAttribute('data-id') || '';
        document.getElementById('examEditName').value = btn.getAttribute('data-name') || '';
        document.getElementById('examEditType').value = btn.getAttribute('data-type') || 'Term';
        document.getElementById('examEditClass').value = btn.getAttribute('data-class') || '';
        document.getElementById('examEditStart').value = btn.getAttribute('data-start') || '';
        document.getElementById('examEditEnd').value = btn.getAttribute('data-end') || '';
    }, 'examEditName');
    wireModal('examSubEditModal', 'data-erp-edit-exam-sub', 'data-exam-sub-modal-close', function (btn) {
        document.getElementById('examSubEditId').value = btn.getAttribute('data-id') || '';
        document.getElementById('examSubEditName').value = btn.getAttribute('data-name') || '';
        document.getElementById('examSubEditMax').value = btn.getAttribute('data-max') || '100';
    }, 'examSubEditName');
});
</script>
<?php require_once 'includes/footer.php'; ?>
