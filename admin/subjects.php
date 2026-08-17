<?php
$page_title = "Subjects";
require_once 'includes/init.php';
require_once '../includes/db_connect.php';
require_once 'includes/erp_helpers.php';

ensureErpSchema($pdo);
$class_options = getClassOptions($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add_subject') {
        $name = trim($_POST['name'] ?? '');
        $code = trim($_POST['code'] ?? '');
        $class = trim($_POST['class_name'] ?? '') ?: null;
        if ($name !== '') {
            try {
                $pdo->prepare("INSERT INTO subjects (name, code, class_name) VALUES (?,?,?)")->execute([$name, $code ?: null, $class]);
                $_SESSION['success_msg'] = 'Subject added.';
            } catch (PDOException $e) {
                $_SESSION['error_msg'] = 'Subject already exists for this class.';
            }
        }
    } elseif ($action === 'update_subject' && isset($_POST['id'])) {
        $name = trim($_POST['name'] ?? '');
        $code = trim($_POST['code'] ?? '');
        $class = trim($_POST['class_name'] ?? '') ?: null;
        if ($name !== '') {
            try {
                $pdo->prepare("UPDATE subjects SET name=?, code=?, class_name=? WHERE id=?")
                    ->execute([$name, $code ?: null, $class, (int) $_POST['id']]);
                $_SESSION['success_msg'] = 'Subject updated.';
            } catch (PDOException $e) {
                $_SESSION['error_msg'] = 'Subject already exists for this class.';
            }
        }
    } elseif ($action === 'delete_subject') {
        $pdo->prepare("DELETE FROM subjects WHERE id = ?")->execute([(int) $_POST['id']]);
        $_SESSION['success_msg'] = 'Subject deleted.';
    }
    header('Location: subjects.php');
    exit;
}

require_once 'includes/header.php';
$subjects = $pdo->query("SELECT * FROM subjects ORDER BY class_name IS NULL DESC, class_name, name")->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="content-top-bar">
    <div class="content-top-main">
        <div class="content-top-icon icon-blue"><i class="fas fa-book"></i></div>
        <div class="content-top-title">
            <h2>Subjects Master</h2>
            <p class="content-top-breadcrumb"><a href="dashboard.php">Dashboard</a><i class="fas fa-chevron-right"></i><span>Subjects</span></p>
        </div>
    </div>
</div>

<div class="form-section-card section-mb">
    <div class="section-card-header">
        <div class="section-card-icon section-icon-school"><i class="fas fa-plus"></i></div>
        <div><h4>Add Subject</h4><p>Leave class empty for all classes</p></div>
    </div>
    <form method="POST" class="category-add-row">
        <input type="hidden" name="action" value="add_subject">
        <div class="form-field"><label>Name</label><input type="text" name="name" class="form-input" required placeholder="Mathematics"></div>
        <div class="form-field"><label>Code</label><input type="text" name="code" class="form-input" placeholder="MATH"></div>
        <div class="form-field"><label>Class (optional)</label><select name="class_name" class="form-input form-select"><option value="">All Classes</option><?php foreach ($class_options as $c): ?><option><?php echo htmlspecialchars($c); ?></option><?php endforeach; ?></select></div>
        <div class="form-field category-add-btn-wrap"><label>&nbsp;</label><button type="submit" class="btn-header-action btn-header-primary category-add-btn"><i class="fas fa-plus"></i> Add</button></div>
    </form>
</div>

<div class="table-container">
    <div class="table-toolbar"><strong>Subjects (<?php echo count($subjects); ?>)</strong></div>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Name</th><th>Code</th><th>Class</th><th class="th-actions">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($subjects as $s): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($s['name']); ?></strong></td>
                <td><?php echo displayVal($s['code']); ?></td>
                <td><?php echo $s['class_name'] ? htmlspecialchars($s['class_name']) : '<span class="promo-next-pill">All Classes</span>'; ?></td>
                <td>
                    <div class="table-action-btns">
                        <button type="button"
                            class="action-btn edit-btn"
                            title="Edit"
                            data-erp-edit-subject
                            data-id="<?php echo (int) $s['id']; ?>"
                            data-name="<?php echo htmlspecialchars($s['name'], ENT_QUOTES); ?>"
                            data-code="<?php echo htmlspecialchars($s['code'] ?? '', ENT_QUOTES); ?>"
                            data-class="<?php echo htmlspecialchars($s['class_name'] ?? '', ENT_QUOTES); ?>">
                            <i class="fas fa-pen"></i>
                        </button>
                        <form method="POST" onsubmit="return confirm('Delete this subject?');">
                            <input type="hidden" name="action" value="delete_subject">
                            <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                            <button type="submit" class="action-btn delete-btn" title="Delete"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="fs-modal" id="subjectEditModal" aria-hidden="true">
    <div class="fs-modal-backdrop" data-subject-modal-close></div>
    <div class="fs-modal-panel" role="dialog" aria-modal="true" aria-labelledby="subjectEditModalTitle">
        <div class="fs-modal-header">
            <div class="fs-modal-header-icon is-edit"><i class="fas fa-book"></i></div>
            <div>
                <h3 id="subjectEditModalTitle">Edit Subject</h3>
                <p>Update subject name, code and class</p>
            </div>
            <button type="button" class="fs-modal-close" data-subject-modal-close aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" class="fs-modal-form" id="subjectEditForm">
            <input type="hidden" name="action" value="update_subject">
            <input type="hidden" name="id" id="subjectEditId" value="">
            <div class="fs-modal-body">
                <div class="form-field">
                    <label for="subjectEditName">Name</label>
                    <input type="text" name="name" id="subjectEditName" class="form-input" placeholder="Mathematics" required>
                </div>
                <div class="form-field">
                    <label for="subjectEditCode">Code</label>
                    <input type="text" name="code" id="subjectEditCode" class="form-input" placeholder="MATH">
                </div>
                <div class="form-field">
                    <label for="subjectEditClass">Class</label>
                    <select name="class_name" id="subjectEditClass" class="form-input form-select">
                        <option value="">All Classes</option>
                        <?php foreach ($class_options as $c): ?>
                        <option value="<?php echo htmlspecialchars($c); ?>"><?php echo htmlspecialchars($c); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="fs-modal-field-hint"><i class="fas fa-lightbulb"></i> Choose <strong>All Classes</strong> if this subject is school-wide.</p>
                </div>
            </div>
            <div class="fs-modal-footer">
                <button type="button" class="btn-header-action btn-header-outline" data-subject-modal-close>Cancel</button>
                <button type="submit" class="btn-header-action btn-header-primary"><i class="fas fa-check"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('subjectEditModal');
    if (!modal) return;
    if (modal.parentElement !== document.body) {
        document.body.appendChild(modal);
    }
    var idEl = document.getElementById('subjectEditId');
    var nameEl = document.getElementById('subjectEditName');
    var codeEl = document.getElementById('subjectEditCode');
    var classEl = document.getElementById('subjectEditClass');

    function openModal() {
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('fs-modal-open');
        setTimeout(function () { if (nameEl) { nameEl.focus(); nameEl.select(); } }, 120);
    }
    function closeModal() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('fs-modal-open');
    }

    document.querySelectorAll('[data-erp-edit-subject]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            idEl.value = btn.getAttribute('data-id') || '';
            nameEl.value = btn.getAttribute('data-name') || '';
            codeEl.value = btn.getAttribute('data-code') || '';
            classEl.value = btn.getAttribute('data-class') || '';
            openModal();
        });
    });

    modal.querySelectorAll('[data-subject-modal-close]').forEach(function (el) {
        el.addEventListener('click', closeModal);
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('is-open')) closeModal();
    });
});
</script>
<?php require_once 'includes/footer.php'; ?>
