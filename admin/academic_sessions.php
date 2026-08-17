<?php
$page_title = "Academic Sessions";
require_once 'includes/init.php';
require_once '../includes/db_connect.php';
require_once 'includes/erp_helpers.php';

ensureErpSchema($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add_session') {
        $name = trim($_POST['name'] ?? '');
        $start = $_POST['start_date'] ?? null;
        $end = $_POST['end_date'] ?? null;
        if ($name !== '') {
            try {
                $pdo->prepare("INSERT INTO academic_sessions (name, start_date, end_date, is_current) VALUES (?,?,?,0)")
                    ->execute([$name, $start ?: null, $end ?: null]);
                $_SESSION['success_msg'] = 'Session added.';
            } catch (PDOException $e) {
                $_SESSION['error_msg'] = 'Session name already exists.';
            }
        }
    } elseif ($action === 'update_session' && isset($_POST['id'])) {
        $name = trim($_POST['name'] ?? '');
        $start = $_POST['start_date'] ?? null;
        $end = $_POST['end_date'] ?? null;
        if ($name !== '') {
            try {
                $pdo->prepare("UPDATE academic_sessions SET name=?, start_date=?, end_date=? WHERE id=?")
                    ->execute([$name, $start ?: null, $end ?: null, (int) $_POST['id']]);
                $_SESSION['success_msg'] = 'Session updated.';
            } catch (PDOException $e) {
                $_SESSION['error_msg'] = 'Session name already exists.';
            }
        }
    } elseif ($action === 'set_current') {
        $id = (int) $_POST['id'];
        $pdo->exec("UPDATE academic_sessions SET is_current = 0");
        $pdo->prepare("UPDATE academic_sessions SET is_current = 1 WHERE id = ?")->execute([$id]);
        $_SESSION['success_msg'] = 'Current session updated.';
    } elseif ($action === 'delete_session') {
        $id = (int) $_POST['id'];
        $cur = getCurrentSession($pdo);
        if ($cur && (int) $cur['id'] === $id) {
            $_SESSION['error_msg'] = 'Cannot delete the current session.';
        } else {
            $pdo->prepare("DELETE FROM academic_sessions WHERE id = ?")->execute([$id]);
            $_SESSION['success_msg'] = 'Session deleted.';
        }
    }
    header('Location: academic_sessions.php');
    exit;
}

require_once 'includes/header.php';
$sessions = getAllSessions($pdo);
$current = getCurrentSession($pdo);
?>
<div class="content-top-bar">
    <div class="content-top-main">
        <div class="content-top-icon icon-teal"><i class="fas fa-calendar-alt"></i></div>
        <div class="content-top-title">
            <h2>Academic Sessions</h2>
            <p class="content-top-breadcrumb"><a href="dashboard.php">Dashboard</a><i class="fas fa-chevron-right"></i><span>Sessions</span></p>
        </div>
    </div>
</div>

<div class="form-section-card section-mb">
    <div class="section-card-header">
        <div class="section-card-icon section-icon-school"><i class="fas fa-plus"></i></div>
        <div><h4>Add Session</h4><p>e.g. 2025-26 (April to March)</p></div>
    </div>
    <form method="POST">
        <input type="hidden" name="action" value="add_session">
        <div class="form-grid form-grid-3 form-grid-spaced">
            <div class="form-field"><label>Session Name</label><input type="text" name="name" class="form-input" placeholder="2025-26" required></div>
            <div class="form-field"><label>Start Date</label><input type="date" name="start_date" class="form-input"></div>
            <div class="form-field"><label>End Date</label><input type="date" name="end_date" class="form-input"></div>
        </div>
        <div class="form-actions-end"><button type="submit" class="btn-header-action btn-header-primary"><i class="fas fa-save"></i> Add Session</button></div>
    </form>
</div>

<div class="table-container">
    <div class="table-toolbar"><strong>All Sessions</strong><span class="toolbar-meta">Current: <?php echo htmlspecialchars($current['name'] ?? '—'); ?></span></div>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Session</th><th>Start</th><th>End</th><th>Status</th><th class="th-actions">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($sessions as $s): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($s['name']); ?></strong></td>
                <td><?php echo displayVal($s['start_date']); ?></td>
                <td><?php echo displayVal($s['end_date']); ?></td>
                <td><?php echo $s['is_current'] ? '<span class="status-badge badge-active">Current</span>' : '<span class="status-badge badge-inactive">Inactive</span>'; ?></td>
                <td>
                    <div class="table-action-btns">
                        <button type="button"
                            class="action-btn edit-btn"
                            title="Edit"
                            data-erp-edit-session
                            data-id="<?php echo (int) $s['id']; ?>"
                            data-name="<?php echo htmlspecialchars($s['name'], ENT_QUOTES); ?>"
                            data-start="<?php echo htmlspecialchars($s['start_date'] ?? '', ENT_QUOTES); ?>"
                            data-end="<?php echo htmlspecialchars($s['end_date'] ?? '', ENT_QUOTES); ?>">
                            <i class="fas fa-pen"></i>
                        </button>
                        <?php if (!$s['is_current']): ?>
                        <form method="POST"><input type="hidden" name="action" value="set_current"><input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                        <button type="submit" class="btn-header-action btn-header-outline btn-sm">Set Current</button></form>
                        <form method="POST" onsubmit="return confirm('Delete session?');"><input type="hidden" name="action" value="delete_session"><input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                        <button type="submit" class="action-btn delete-btn" title="Delete"><i class="fas fa-trash"></i></button></form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="fs-modal" id="sessionEditModal" aria-hidden="true">
    <div class="fs-modal-backdrop" data-session-modal-close></div>
    <div class="fs-modal-panel" role="dialog" aria-modal="true" aria-labelledby="sessionEditModalTitle">
        <div class="fs-modal-header">
            <div class="fs-modal-header-icon is-edit"><i class="fas fa-calendar-alt"></i></div>
            <div>
                <h3 id="sessionEditModalTitle">Edit Session</h3>
                <p>Update session name and date range</p>
            </div>
            <button type="button" class="fs-modal-close" data-session-modal-close aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" class="fs-modal-form" id="sessionEditForm">
            <input type="hidden" name="action" value="update_session">
            <input type="hidden" name="id" id="sessionEditId" value="">
            <div class="fs-modal-body">
                <div class="form-field">
                    <label for="sessionEditName">Session Name</label>
                    <input type="text" name="name" id="sessionEditName" class="form-input" placeholder="2025-26" required>
                </div>
                <div class="form-field">
                    <label for="sessionEditStart">Start Date</label>
                    <input type="date" name="start_date" id="sessionEditStart" class="form-input">
                </div>
                <div class="form-field">
                    <label for="sessionEditEnd">End Date</label>
                    <input type="date" name="end_date" id="sessionEditEnd" class="form-input">
                </div>
            </div>
            <div class="fs-modal-footer">
                <button type="button" class="btn-header-action btn-header-outline" data-session-modal-close>Cancel</button>
                <button type="submit" class="btn-header-action btn-header-primary"><i class="fas fa-check"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('sessionEditModal');
    if (!modal) return;
    if (modal.parentElement !== document.body) {
        document.body.appendChild(modal);
    }
    var idEl = document.getElementById('sessionEditId');
    var nameEl = document.getElementById('sessionEditName');
    var startEl = document.getElementById('sessionEditStart');
    var endEl = document.getElementById('sessionEditEnd');

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

    document.querySelectorAll('[data-erp-edit-session]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            idEl.value = btn.getAttribute('data-id') || '';
            nameEl.value = btn.getAttribute('data-name') || '';
            startEl.value = btn.getAttribute('data-start') || '';
            endEl.value = btn.getAttribute('data-end') || '';
            openModal();
        });
    });

    modal.querySelectorAll('[data-session-modal-close]').forEach(function (el) {
        el.addEventListener('click', closeModal);
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('is-open')) closeModal();
    });
});
</script>
<?php require_once 'includes/footer.php'; ?>
