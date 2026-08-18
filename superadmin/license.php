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
$daysLeft = saLicenseDaysLeft($school);
$startsOn = trim((string) ($school['starts_at'] ?? ''));
$expiresOn = trim((string) ($school['expires_at'] ?? ''));
$ok = ($status !== 'Suspended')
    && ($startsOn === '' || $startsOn <= date('Y-m-d'))
    && ($expiresOn === '' || $expiresOn >= date('Y-m-d'));

$fmtDate = static function (?string $value): string {
    $value = trim((string) $value);
    if ($value === '') {
        return '—';
    }
    $ts = strtotime($value);
    return $ts ? date('d M Y', $ts) : $value;
};

if ($status === 'Suspended') {
    $accessLabel = 'Blocked';
    $accessHint = 'Admin and portals cannot sign in';
} elseif ($startsOn !== '' && $startsOn > date('Y-m-d')) {
    $accessLabel = 'Not started';
    $accessHint = 'Access begins on ' . $fmtDate($startsOn);
} elseif ($daysLeft !== null && $daysLeft < 0) {
    $accessLabel = 'Expired';
    $accessHint = 'Expired on ' . $fmtDate($expiresOn);
} else {
    $accessLabel = 'Access OK';
    $accessHint = 'Admin, Teacher and Student portals can sign in';
}

if ($daysLeft === null) {
    $daysLabel = 'No expiry';
    $daysHint = 'Open-ended license';
    $daysTone = 'ok';
} elseif ($daysLeft < 0) {
    $daysLabel = abs($daysLeft) . ' day' . (abs($daysLeft) === 1 ? '' : 's') . ' overdue';
    $daysHint = 'Renew to restore access';
    $daysTone = 'bad';
} elseif ($daysLeft <= 14) {
    $daysLabel = $daysLeft . ' day' . ($daysLeft === 1 ? '' : 's') . ' left';
    $daysHint = 'Renew soon';
    $daysTone = 'warn';
} else {
    $daysLabel = $daysLeft . ' day' . ($daysLeft === 1 ? '' : 's') . ' left';
    $daysHint = 'Valid until ' . $fmtDate($expiresOn);
    $daysTone = 'ok';
}

if ($startsOn !== '' && $expiresOn !== '') {
    $periodLabel = $fmtDate($startsOn) . ' → ' . $fmtDate($expiresOn);
} elseif ($expiresOn !== '') {
    $periodLabel = 'Until ' . $fmtDate($expiresOn);
} elseif ($startsOn !== '') {
    $periodLabel = 'From ' . $fmtDate($startsOn);
} else {
    $periodLabel = 'Open-ended';
}
?>
<section class="sa-hero">
    <div>
        <span class="sa-hero-kicker">Access control</span>
        <h1>School License</h1>
        <p>This SuperAdmin controls one school on this server. Suspend or set an expiry date. SuperAdmin always stays available.</p>
    </div>
    <div class="sa-hero-actions">
        <a href="features.php" class="btn btn-primary">Plan &amp; features</a>
        <a href="dashboard.php" class="btn-ghost">Dashboard</a>
    </div>
</section>

<?php if ($status === 'Suspended'): ?>
<div class="sa-license-banner is-expired">
    This school is <strong>suspended</strong>. School Admin and portals are blocked until you set status to Active.
</div>
<?php elseif ($daysLeft !== null && $daysLeft < 0): ?>
<div class="sa-license-banner is-expired">
    License expired on <?php echo e($expiresOn); ?>. School Admin and portals are blocked until you renew.
</div>
<?php elseif ($daysLeft !== null && $daysLeft <= 14): ?>
<div class="sa-license-banner is-soon">
    License expires in <strong><?php echo (int) $daysLeft; ?> day<?php echo $daysLeft === 1 ? '' : 's'; ?></strong> (<?php echo e($expiresOn); ?>). A reminder is sent once a day when email or SMS is configured.
</div>
<?php endif; ?>

<div class="sa-license-page">
    <div class="sa-license-stats">
        <article class="sa-lic-stat <?php echo $ok ? 'is-ok' : 'is-bad'; ?>">
            <span class="sa-lic-stat-ico" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
            </span>
            <span class="sa-lic-stat-label">License</span>
            <strong><?php echo e($status); ?></strong>
            <em><?php echo $ok ? '<span class="sa-chip on">ACCESS OK</span>' : '<span class="sa-chip off">BLOCKED</span>'; ?></em>
        </article>
        <article class="sa-lic-stat">
            <span class="sa-lic-stat-ico sa-lic-stat-ico-access" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            </span>
            <span class="sa-lic-stat-label">Portals</span>
            <strong><?php echo e($accessLabel); ?></strong>
            <em><?php echo e($accessHint); ?></em>
        </article>
        <article class="sa-lic-stat">
            <span class="sa-lic-stat-ico sa-lic-stat-ico-cal" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            </span>
            <span class="sa-lic-stat-label">Period</span>
            <strong><?php echo e($periodLabel); ?></strong>
            <em>Start and expiry for this install</em>
        </article>
        <article class="sa-lic-stat is-<?php echo e($daysTone); ?>">
            <span class="sa-lic-stat-ico" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
            </span>
            <span class="sa-lic-stat-label">Time left</span>
            <strong><?php echo e($daysLabel); ?></strong>
            <em><?php echo e($daysHint); ?></em>
        </article>
    </div>

    <form method="post">
        <?php echo saCsrfField(); ?>

        <div class="sa-panel">
            <div class="sa-panel-head">
                <div>
                    <h2>Access control</h2>
                    <p>Status and dates decide whether Admin and portals can sign in</p>
                </div>
                <?php echo $ok ? '<span class="sa-chip on">ACCESS OK</span>' : '<span class="sa-chip off">BLOCKED</span>'; ?>
            </div>
            <div class="sa-panel-body">
                <div class="sa-lic-section-title">
                    <span aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    </span>
                    Status
                </div>
                <div class="sa-lic-opts">
                    <label class="sa-lic-opt is-good <?php echo $status === 'Active' ? 'is-on' : ''; ?>">
                        <input type="radio" name="status" value="Active" <?php echo $status === 'Active' ? 'checked' : ''; ?>>
                        <span class="sa-lic-opt-ico" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><path d="M22 4L12 14.01l-3-3"/></svg>
                        </span>
                        <span class="sa-lic-opt-copy">
                            <strong>Active</strong>
                            <small>Admin, Teacher and Student portals can sign in</small>
                        </span>
                    </label>
                    <label class="sa-lic-opt is-bad <?php echo $status === 'Suspended' ? 'is-on' : ''; ?>">
                        <input type="radio" name="status" value="Suspended" <?php echo $status === 'Suspended' ? 'checked' : ''; ?>>
                        <span class="sa-lic-opt-ico" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>
                        </span>
                        <span class="sa-lic-opt-copy">
                            <strong>Suspended</strong>
                            <small>Block Admin and portals immediately. SuperAdmin stays open.</small>
                        </span>
                    </label>
                </div>

                <div class="sa-lic-section-title">
                    <span aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    </span>
                    License period
                </div>
                <div class="sa-lic-period">
                    <div class="form-group">
                        <label for="starts_at">Starts on</label>
                        <input type="date" id="starts_at" name="starts_at" value="<?php echo e($school['starts_at'] ?? ''); ?>">
                        <span class="sa-field-hint">Leave empty to start immediately.</span>
                    </div>
                    <span class="sa-lic-period-join" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 12h14"/><path d="M13 6l6 6-6 6"/></svg>
                    </span>
                    <div class="form-group">
                        <label for="expires_at">Expires on</label>
                        <input type="date" id="expires_at" name="expires_at" value="<?php echo e($school['expires_at'] ?? ''); ?>">
                        <span class="sa-field-hint">Leave empty for no expiry.</span>
                    </div>
                </div>
                <div class="sa-note sa-lic-note">SuperAdmin login is never blocked by this license. Only School Admin, Teacher and Student portals follow these dates.</div>
            </div>
        </div>

        <div class="sa-panel">
            <div class="sa-panel-head">
                <div>
                    <h2>School identity</h2>
                    <p>Used on this license record and for expiry reminders</p>
                </div>
            </div>
            <div class="sa-panel-body">
                <div class="sa-form-grid">
                    <div class="form-group span-2">
                        <label for="lic_name">School name</label>
                        <input type="text" id="lic_name" name="name" value="<?php echo e($school['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="lic_code">School code</label>
                        <input type="text" id="lic_code" name="code" value="<?php echo e($school['code'] ?? ''); ?>" placeholder="SCHOOL01">
                    </div>
                    <div class="form-group">
                        <label for="lic_city">City</label>
                        <input type="text" id="lic_city" name="city" value="<?php echo e($school['city'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="lic_contact">Contact person</label>
                        <input type="text" id="lic_contact" name="contact_name" value="<?php echo e($school['contact_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="lic_phone">Phone</label>
                        <input type="text" id="lic_phone" name="phone" value="<?php echo e($school['phone'] ?? ''); ?>">
                    </div>
                    <div class="form-group span-2">
                        <label for="lic_email">Email</label>
                        <input type="email" id="lic_email" name="email" value="<?php echo e($school['email'] ?? ''); ?>" placeholder="For expiry reminders">
                    </div>
                    <div class="form-group span-2">
                        <label for="lic_notes">Notes</label>
                        <textarea id="lic_notes" name="notes" rows="3" placeholder="Internal notes for this school license"><?php echo e($school['notes'] ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="sa-form-actions">
                    <button type="submit" class="btn btn-primary">Save license</button>
                    <a href="dashboard.php" class="btn btn-outline">Back to dashboard</a>
                </div>
            </div>
        </div>
    </form>
</div>
<script>
document.querySelectorAll('.sa-lic-opt input[type="radio"]').forEach(function (input) {
    input.addEventListener('change', function () {
        document.querySelectorAll('.sa-lic-opt').forEach(function (el) { el.classList.remove('is-on'); });
        if (input.checked && input.closest('.sa-lic-opt')) {
            input.closest('.sa-lic-opt').classList.add('is-on');
        }
    });
});
</script>
<?php require_once 'includes/layout_footer.php'; ?>
