<?php
$page_title = "Website Enquiries";
require_once 'includes/init.php';
require_once '../includes/db_connect.php';
require_once 'includes/erp_helpers.php';

ensureErpSchema($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_status') {
        $pdo->prepare("UPDATE admission_enquiries SET status = ? WHERE id = ? AND class_sought = 'Website Contact'")
            ->execute([$_POST['status'] ?? 'New', (int) ($_POST['id'] ?? 0)]);
        $_SESSION['success_msg'] = 'Status updated.';
        adminLogActivity($pdo, 'enquiry_status', 'Website enquiry #' . (int) ($_POST['id'] ?? 0));
    } elseif ($action === 'delete_enquiry') {
        $pdo->prepare("DELETE FROM admission_enquiries WHERE id = ? AND class_sought = 'Website Contact'")
            ->execute([(int) ($_POST['id'] ?? 0)]);
        $_SESSION['success_msg'] = 'Enquiry deleted.';
        adminLogActivity($pdo, 'enquiry_deleted', 'Website enquiry #' . (int) ($_POST['id'] ?? 0));
    }
    header('Location: website_enquiries.php');
    exit;
}

require_once 'includes/header.php';
$enquiries = $pdo->query("SELECT * FROM admission_enquiries WHERE class_sought = 'Website Contact' ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$newCount = count(array_filter($enquiries, fn($e) => ($e['status'] ?? '') === 'New'));
?>
<div class="content-top-bar">
    <div class="content-top-main">
        <div class="content-top-icon icon-orange"><i class="fas fa-globe"></i></div>
        <div class="content-top-title">
            <h2>Website Enquiries</h2>
            <p class="content-top-breadcrumb">
                <a href="dashboard.php">Dashboard</a>
                <i class="fas fa-chevron-right"></i>
                <span>Homepage contact form</span>
            </p>
        </div>
    </div>
</div>

<div class="cls-stat-strip">
    <div class="cls-stat-card"><div class="cls-stat-icon"><i class="fas fa-inbox"></i></div><div><span>New</span><strong><?php echo $newCount; ?></strong></div></div>
    <div class="cls-stat-card"><div class="cls-stat-icon cls-stat-blue"><i class="fas fa-list"></i></div><div><span>Total</span><strong><?php echo count($enquiries); ?></strong></div></div>
</div>

<div class="table-container">
    <div class="table-toolbar"><strong>Messages from the public website</strong></div>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Date</th><th>Name</th><th>Mobile</th><th>Email</th><th>Message</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php if (!$enquiries): ?>
            <tr><td colspan="7">No website messages yet.</td></tr>
            <?php else: foreach ($enquiries as $e): ?>
            <tr>
                <td><?php echo date('d M Y h:i A', strtotime($e['created_at'])); ?></td>
                <td><strong><?php echo htmlspecialchars($e['student_name']); ?></strong></td>
                <td><a href="tel:<?php echo htmlspecialchars(preg_replace('/\s+/', '', (string) $e['mobile'])); ?>"><?php echo htmlspecialchars($e['mobile']); ?></a></td>
                <td><?php echo !empty($e['email']) ? '<a href="mailto:' . htmlspecialchars($e['email']) . '">' . htmlspecialchars($e['email']) . '</a>' : '—'; ?></td>
                <td><?php echo htmlspecialchars($e['message'] ?: '—'); ?></td>
                <td>
                    <form method="POST">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="id" value="<?php echo (int) $e['id']; ?>">
                        <select name="status" class="form-input form-select" style="padding:4px 8px;font-size:0.8rem" onchange="this.form.submit()">
                            <?php foreach (['New', 'Contacted', 'Converted', 'Closed'] as $st): ?>
                            <option <?php echo ($e['status'] ?? '') === $st ? 'selected' : ''; ?>><?php echo $st; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </td>
                <td>
                    <form method="POST" onsubmit="return confirm('Delete this message?');">
                        <input type="hidden" name="action" value="delete_enquiry">
                        <input type="hidden" name="id" value="<?php echo (int) $e['id']; ?>">
                        <button type="submit" class="sig-btn sig-btn-danger"><i class="fas fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
