<?php
$page_title = 'School License';
require_once 'includes/init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    saRequireCsrf('license.php');
    saSaveThisSchoolLicense($pdo, $_POST);
    $status = (($_POST['status'] ?? 'Active') === 'Suspended') ? 'Suspended' : 'Active';
    $expires = trim((string) ($_POST['expires_at'] ?? ''));
    saLogActivity($pdo, 'license_updated', 'Status ' . $status . ($expires !== '' ? ', expires ' . $expires : ', no expiry'));
    saFlash('success', 'School license updated. Admin and portal access follows this status.');
    header('Location: license.php');
    exit;
}

require_once 'includes/layout_header.php';
$status = $school['status'] ?? 'Active';
$ok = $status !== 'Suspended' && (empty($school['expires_at']) || $school['expires_at'] >= date('Y-m-d'));
?>
<section class="sa-hero">
    <div>
        <span class="sa-hero-kicker">Access control</span>
        <h1>School License</h1>
        <p>This SuperAdmin controls one school on this server. Suspend or set an expiry date. SuperAdmin always stays available.</p>
    </div>
</section>

<form method="post" class="sa-panel">
    <?php echo saCsrfField(); ?>
    <div class="sa-panel-head">
        <div>
            <h2>License status</h2>
            <p>Controls School Admin, Teacher and Student portal access</p>
        </div>
        <?php echo $ok ? '<span class="sa-chip on">ACCESS OK</span>' : '<span class="sa-chip off">BLOCKED</span>'; ?>
    </div>
    <div class="sa-panel-body">
        <div class="sa-form-grid">
            <div class="form-group span-2">
                <label>School name</label>
                <input type="text" name="name" value="<?php echo e($school['name']); ?>" required>
            </div>
            <div class="form-group">
                <label>School code</label>
                <input type="text" name="code" value="<?php echo e($school['code'] ?? ''); ?>" placeholder="SCHOOL01">
            </div>
            <div class="form-group">
                <label>City</label>
                <input type="text" name="city" value="<?php echo e($school['city'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Contact person</label>
                <input type="text" name="contact_name" value="<?php echo e($school['contact_name'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" value="<?php echo e($school['phone'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo e($school['email'] ?? ''); ?>" placeholder="For expiry reminders">
            </div>
            <div class="form-group span-2">
                <label>Notes</label>
                <textarea name="notes" rows="2"><?php echo e($school['notes'] ?? ''); ?></textarea>
            </div>
            <div class="form-group span-2">
                <label>Status</label>
                <div class="sa-mode-grid">
                    <label class="sa-mode-opt <?php echo $status === 'Active' ? 'is-on' : ''; ?>">
                        <input type="radio" name="status" value="Active" <?php echo $status === 'Active' ? 'checked' : ''; ?>>
                        <strong>Active</strong>
                        <small>Admin &amp; portals can sign in</small>
                    </label>
                    <label class="sa-mode-opt <?php echo $status === 'Suspended' ? 'is-on' : ''; ?>">
                        <input type="radio" name="status" value="Suspended" <?php echo $status === 'Suspended' ? 'checked' : ''; ?>>
                        <strong>Suspended</strong>
                        <small>Block Admin &amp; portals immediately</small>
                    </label>
                </div>
            </div>
            <div class="form-group">
                <label>Starts on</label>
                <input type="date" name="starts_at" value="<?php echo e($school['starts_at'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Expires on</label>
                <input type="date" name="expires_at" value="<?php echo e($school['expires_at'] ?? ''); ?>">
                <span class="sa-field-hint">Leave empty for no expiry.</span>
            </div>
        </div>
        <div class="sa-form-actions">
            <button type="submit" class="btn btn-primary">Save license</button>
            <a href="dashboard.php" class="btn btn-outline">Back</a>
        </div>
    </div>
</form>
<script>
document.querySelectorAll('.sa-mode-opt input[type="radio"]').forEach(function (input) {
    input.addEventListener('change', function () {
        document.querySelectorAll('.sa-mode-opt').forEach(function (el) { el.classList.remove('is-on'); });
        if (input.checked && input.closest('.sa-mode-opt')) {
            input.closest('.sa-mode-opt').classList.add('is-on');
        }
    });
});
</script>
<?php require_once 'includes/layout_footer.php'; ?>
