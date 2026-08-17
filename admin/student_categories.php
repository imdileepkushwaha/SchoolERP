<?php
$page_title = "Student Categories";
require_once 'includes/init.php';
require_once '../includes/db_connect.php';
require_once 'includes/student_helpers.php';

ensureStudentSchema($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        if ($name !== '') {
            try {
                $discount = max(0, min(100, (float) ($_POST['discount_percent'] ?? 0)));
                $stmt = $pdo->prepare("INSERT INTO student_categories (name, description, discount_percent) VALUES (?, ?, ?)");
                $stmt->execute([$name, $desc, $discount]);
                $_SESSION['success_msg'] = 'Category added.';
            } catch (PDOException $e) {
                $_SESSION['error_msg'] = 'Category already exists or could not be saved.';
            }
        }
    } elseif ($action === 'update' && isset($_POST['id'])) {
        $id = (int) $_POST['id'];
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $discount = max(0, min(100, (float) ($_POST['discount_percent'] ?? 0)));
        $status = $_POST['status'] ?? 'Active';
        if ($name !== '') {
            $stmt = $pdo->prepare("UPDATE student_categories SET name=?, description=?, discount_percent=?, status=? WHERE id=?");
            $stmt->execute([$name, $desc, $discount, $status, $id]);
            $_SESSION['success_msg'] = 'Category updated.';
        }
    } elseif ($action === 'delete' && isset($_POST['id'])) {
        $id = (int) $_POST['id'];
        $pdo->prepare("DELETE FROM student_categories WHERE id=?")->execute([$id]);
        $_SESSION['success_msg'] = 'Category deleted.';
    }
    header('Location: student_categories.php');
    exit;
}

require_once 'includes/header.php';

$categories = $pdo->query("SELECT * FROM student_categories ORDER BY name ASC")->fetchAll();
?>
<div class="content-top-bar">
    <div class="content-top-main">
        <div class="content-top-icon icon-purple"><i class="fas fa-tags"></i></div>
        <div class="content-top-title">
            <h2>Student Categories</h2>
            <p class="content-top-breadcrumb">
                <a href="dashboard.php">Dashboard</a>
                <i class="fas fa-chevron-right"></i>
                <a href="students.php">Students</a>
                <i class="fas fa-chevron-right"></i>
                <span>Categories</span>
            </p>
        </div>
    </div>
</div>

<div class="form-section-card section-mb">
    <div class="section-card-header">
        <div class="section-card-icon section-icon-school"><i class="fas fa-plus"></i></div>
        <div><h4>Add Category</h4></div>
    </div>
    <form method="POST" class="category-add-form">
        <input type="hidden" name="action" value="add">
        <div class="category-add-row" style="grid-template-columns: 1.2fr 1.5fr 110px auto">
            <div class="form-field">
                <label>Category Name</label>
                <input type="text" name="name" class="form-input" placeholder="e.g. EWS" required>
            </div>
            <div class="form-field">
                <label>Description</label>
                <input type="text" name="description" class="form-input" placeholder="Optional">
            </div>
            <div class="form-field">
                <label>Discount %</label>
                <input type="number" name="discount_percent" class="form-input" min="0" max="100" step="0.01" value="0" placeholder="0">
            </div>
            <div class="form-field category-add-btn-wrap">
                <label aria-hidden="true">&nbsp;</label>
                <button type="submit" class="btn-header-action btn-header-primary category-add-btn"><i class="fas fa-plus"></i> Add Category</button>
            </div>
        </div>
    </form>
</div>

<div class="table-container">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Discount %</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $i => $cat): ?>
                <tr>
                    <td><?php echo $i + 1; ?></td>
                    <td><strong><?php echo htmlspecialchars($cat['name']); ?></strong></td>
                    <td><?php echo displayVal($cat['description'] ?? '', '—'); ?></td>
                    <td><?php echo htmlspecialchars((string) ($cat['discount_percent'] ?? '0')); ?>%</td>
                    <td><span class="promo-next-pill"><?php echo htmlspecialchars($cat['status']); ?></span></td>
                    <td>
                        <div class="table-action-btns">
                            <button type="button" class="action-btn edit-btn" title="Edit"
                                data-erp-edit-category
                                data-id="<?php echo (int) $cat['id']; ?>"
                                data-name="<?php echo htmlspecialchars($cat['name'], ENT_QUOTES); ?>"
                                data-description="<?php echo htmlspecialchars($cat['description'] ?? '', ENT_QUOTES); ?>"
                                data-discount="<?php echo htmlspecialchars((string) ($cat['discount_percent'] ?? '0'), ENT_QUOTES); ?>"
                                data-status="<?php echo htmlspecialchars($cat['status'], ENT_QUOTES); ?>">
                                <i class="fas fa-pen"></i>
                            </button>
                            <button type="submit" form="cat-delete-<?php echo $cat['id']; ?>" class="action-btn delete-btn" title="Delete" onclick="return confirm('Delete this category?');"><i class="fas fa-trash"></i></button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php foreach ($categories as $cat): ?>
<form id="cat-delete-<?php echo $cat['id']; ?>" method="POST" class="hidden-form">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
</form>
<?php endforeach; ?>

<div class="fs-modal" id="categoryEditModal" aria-hidden="true">
    <div class="fs-modal-backdrop" data-category-modal-close></div>
    <div class="fs-modal-panel" role="dialog" aria-modal="true" aria-labelledby="categoryEditModalTitle">
        <div class="fs-modal-header">
            <div class="fs-modal-header-icon is-edit"><i class="fas fa-tags"></i></div>
            <div>
                <h3 id="categoryEditModalTitle">Edit Category</h3>
                <p>Update name, discount and status</p>
            </div>
            <button type="button" class="fs-modal-close" data-category-modal-close aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" class="fs-modal-form">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="categoryEditId" value="">
            <div class="fs-modal-body">
                <div class="form-field"><label for="categoryEditName">Category Name</label><input type="text" name="name" id="categoryEditName" class="form-input" required></div>
                <div class="form-field"><label for="categoryEditDesc">Description</label><input type="text" name="description" id="categoryEditDesc" class="form-input"></div>
                <div class="form-field"><label for="categoryEditDiscount">Discount %</label><input type="number" name="discount_percent" id="categoryEditDiscount" class="form-input" min="0" max="100" step="0.01"></div>
                <div class="form-field"><label for="categoryEditStatus">Status</label>
                    <select name="status" id="categoryEditStatus" class="form-input form-select">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="fs-modal-footer">
                <button type="button" class="btn-header-action btn-header-outline" data-category-modal-close>Cancel</button>
                <button type="submit" class="btn-header-action btn-header-primary"><i class="fas fa-check"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('categoryEditModal');
    if (!modal) return;
    if (modal.parentElement !== document.body) document.body.appendChild(modal);
    function openModal() {
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('fs-modal-open');
        setTimeout(function () {
            var el = document.getElementById('categoryEditName');
            if (el) { el.focus(); el.select(); }
        }, 120);
    }
    function closeModal() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('fs-modal-open');
    }
    document.querySelectorAll('[data-erp-edit-category]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('categoryEditId').value = btn.getAttribute('data-id') || '';
            document.getElementById('categoryEditName').value = btn.getAttribute('data-name') || '';
            document.getElementById('categoryEditDesc').value = btn.getAttribute('data-description') || '';
            document.getElementById('categoryEditDiscount').value = btn.getAttribute('data-discount') || '0';
            document.getElementById('categoryEditStatus').value = btn.getAttribute('data-status') || 'Active';
            openModal();
        });
    });
    modal.querySelectorAll('[data-category-modal-close]').forEach(function (el) {
        el.addEventListener('click', closeModal);
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('is-open')) closeModal();
    });
});
</script>
<?php require_once 'includes/footer.php'; ?>
