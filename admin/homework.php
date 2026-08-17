<?php
$page_title = "Homework";
require_once 'includes/init.php';
require_once '../includes/db_connect.php';
require_once 'includes/erp_helpers.php';

ensureErpSchema($pdo);
$class_options = getClassOptions($pdo);
$session = getCurrentSession($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_homework'])) {
        $pdo->prepare("INSERT INTO homework (class_name, section_name, title, description, due_date, session_id) VALUES (?,?,?,?,?,?)")
            ->execute([
                trim($_POST['class_name']), trim($_POST['section_name'] ?? 'A'),
                trim($_POST['title']), trim($_POST['description'] ?? ''),
                $_POST['due_date'] ?: null, $session['id'] ?? null,
            ]);
        $_SESSION['success_msg'] = 'Homework posted.';
    } elseif (isset($_POST['update_homework'])) {
        $pdo->prepare("UPDATE homework SET class_name=?, section_name=?, title=?, description=?, due_date=? WHERE id=?")
            ->execute([
                trim($_POST['class_name'] ?? ''),
                trim($_POST['section_name'] ?? 'A'),
                trim($_POST['title'] ?? ''),
                trim($_POST['description'] ?? ''),
                $_POST['due_date'] ?: null,
                (int) $_POST['id'],
            ]);
        $_SESSION['success_msg'] = 'Homework updated.';
    } elseif (isset($_POST['delete_homework'])) {
        $pdo->prepare("DELETE FROM homework WHERE id = ?")->execute([(int) $_POST['id']]);
        $_SESSION['success_msg'] = 'Homework deleted.';
    }
    header('Location: homework.php');
    exit;
}

require_once 'includes/header.php';
$homework = $pdo->query("SELECT * FROM homework ORDER BY id DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="content-top-bar">
    <div class="content-top-main">
        <div class="content-top-icon icon-purple"><i class="fas fa-book-open"></i></div>
        <div class="content-top-title">
            <h2>Homework</h2>
            <p class="content-top-breadcrumb"><a href="dashboard.php">Dashboard</a><i class="fas fa-chevron-right"></i><span>Homework</span></p>
        </div>
    </div>
</div>

<div class="form-section-card section-mb">
    <div class="section-card-header">
        <div class="section-card-icon section-icon-school"><i class="fas fa-paper-plane"></i></div>
        <div><h4>Post Homework</h4><p>Students see this in the student portal</p></div>
    </div>
    <form method="POST">
        <input type="hidden" name="add_homework" value="1">
        <div class="form-grid form-grid-2 form-grid-spaced">
            <div class="form-field"><label>Class</label><select name="class_name" class="form-input form-select" required><?php foreach ($class_options as $c): ?><option><?php echo htmlspecialchars($c); ?></option><?php endforeach; ?></select></div>
            <div class="form-field"><label>Section</label><input type="text" name="section_name" class="form-input" value="A"></div>
            <div class="form-field"><label>Title</label><input type="text" name="title" class="form-input" required></div>
            <div class="form-field"><label>Due Date</label><input type="date" name="due_date" class="form-input"></div>
            <div class="form-field form-field-full"><label>Description</label><textarea name="description" class="form-input form-textarea" rows="3"></textarea></div>
        </div>
        <div class="form-actions-end"><button type="submit" class="btn-header-action btn-header-primary"><i class="fas fa-paper-plane"></i> Post</button></div>
    </form>
</div>

<div class="table-container">
    <div class="table-toolbar"><strong>Recent Homework</strong></div>
    <div class="table-wrapper">
        <table><thead><tr><th>Class</th><th>Title</th><th>Due</th><th>Posted</th><th class="th-actions">Actions</th></tr></thead><tbody>
        <?php foreach ($homework as $h): ?>
        <tr>
            <td><span class="promo-next-pill"><?php echo htmlspecialchars($h['class_name'] . ' · ' . $h['section_name']); ?></span></td>
            <td><strong><?php echo htmlspecialchars($h['title']); ?></strong><?php if ($h['description']): ?><br><small style="color:#64748b"><?php echo htmlspecialchars(mb_substr($h['description'], 0, 60)); ?></small><?php endif; ?></td>
            <td><?php echo displayVal($h['due_date'], '—'); ?></td>
            <td><?php echo htmlspecialchars($h['created_at']); ?></td>
            <td>
                <div class="table-action-btns">
                    <button type="button"
                        class="action-btn edit-btn"
                        title="Edit"
                        data-erp-edit-hw
                        data-id="<?php echo (int) $h['id']; ?>"
                        data-class="<?php echo htmlspecialchars($h['class_name'], ENT_QUOTES); ?>"
                        data-section="<?php echo htmlspecialchars($h['section_name'] ?? 'A', ENT_QUOTES); ?>"
                        data-title="<?php echo htmlspecialchars($h['title'], ENT_QUOTES); ?>"
                        data-due="<?php echo htmlspecialchars($h['due_date'] ?? '', ENT_QUOTES); ?>"
                        data-description="<?php echo htmlspecialchars($h['description'] ?? '', ENT_QUOTES); ?>">
                        <i class="fas fa-pen"></i>
                    </button>
                    <form method="POST" onsubmit="return confirm('Delete?');"><input type="hidden" name="delete_homework" value="1"><input type="hidden" name="id" value="<?php echo $h['id']; ?>"><button type="submit" class="action-btn delete-btn"><i class="fas fa-trash"></i></button></form>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody></table>
    </div>
</div>

<div class="fs-modal" id="hwEditModal" aria-hidden="true">
    <div class="fs-modal-backdrop" data-hw-modal-close></div>
    <div class="fs-modal-panel" role="dialog" aria-modal="true" aria-labelledby="hwEditModalTitle">
        <div class="fs-modal-header">
            <div class="fs-modal-header-icon is-edit"><i class="fas fa-book-open"></i></div>
            <div>
                <h3 id="hwEditModalTitle">Edit Homework</h3>
                <p>Update class, title, due date and description</p>
            </div>
            <button type="button" class="fs-modal-close" data-hw-modal-close aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" class="fs-modal-form">
            <input type="hidden" name="update_homework" value="1">
            <input type="hidden" name="id" id="hwEditId" value="">
            <div class="fs-modal-body">
                <div class="form-field">
                    <label for="hwEditClass">Class</label>
                    <select name="class_name" id="hwEditClass" class="form-input form-select" required>
                        <?php foreach ($class_options as $c): ?>
                        <option value="<?php echo htmlspecialchars($c); ?>"><?php echo htmlspecialchars($c); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field">
                    <label for="hwEditSection">Section</label>
                    <input type="text" name="section_name" id="hwEditSection" class="form-input" value="A">
                </div>
                <div class="form-field">
                    <label for="hwEditTitle">Title</label>
                    <input type="text" name="title" id="hwEditTitle" class="form-input" required>
                </div>
                <div class="form-field">
                    <label for="hwEditDue">Due Date</label>
                    <input type="date" name="due_date" id="hwEditDue" class="form-input">
                </div>
                <div class="form-field">
                    <label for="hwEditDesc">Description</label>
                    <textarea name="description" id="hwEditDesc" class="form-input form-textarea" rows="3"></textarea>
                </div>
            </div>
            <div class="fs-modal-footer">
                <button type="button" class="btn-header-action btn-header-outline" data-hw-modal-close>Cancel</button>
                <button type="submit" class="btn-header-action btn-header-primary"><i class="fas fa-check"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('hwEditModal');
    if (!modal) return;
    if (modal.parentElement !== document.body) document.body.appendChild(modal);
    var idEl = document.getElementById('hwEditId');
    var classEl = document.getElementById('hwEditClass');
    var sectionEl = document.getElementById('hwEditSection');
    var titleEl = document.getElementById('hwEditTitle');
    var dueEl = document.getElementById('hwEditDue');
    var descEl = document.getElementById('hwEditDesc');
    function openModal() {
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('fs-modal-open');
        setTimeout(function () { titleEl.focus(); titleEl.select(); }, 120);
    }
    function closeModal() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('fs-modal-open');
    }
    document.querySelectorAll('[data-erp-edit-hw]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            idEl.value = btn.getAttribute('data-id') || '';
            classEl.value = btn.getAttribute('data-class') || '';
            sectionEl.value = btn.getAttribute('data-section') || 'A';
            titleEl.value = btn.getAttribute('data-title') || '';
            dueEl.value = btn.getAttribute('data-due') || '';
            descEl.value = btn.getAttribute('data-description') || '';
            openModal();
        });
    });
    modal.querySelectorAll('[data-hw-modal-close]').forEach(function (el) {
        el.addEventListener('click', closeModal);
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('is-open')) closeModal();
    });
});
</script>
<?php require_once 'includes/footer.php'; ?>
