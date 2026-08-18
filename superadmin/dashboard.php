<?php
$page_title = 'Dashboard';
require_once 'includes/init.php';

$catalog = getErpModuleCatalog();
$enabled = getSchoolModuleKeys($pdo, (int) $school['id']);
$planKey = saActivePresetKey($school);
$planLabel = saPlanLabel($school['plan'] ?? 'Custom');
$licenseOk = ($school['status'] ?? '') !== 'Suspended'
    && (empty($school['starts_at']) || $school['starts_at'] <= date('Y-m-d'))
    && (empty($school['expires_at']) || $school['expires_at'] >= date('Y-m-d'));
$daysLeft = saLicenseDaysLeft($school);
$stats = saDashboardStats($pdo);
$onCount = 0;
$rows = [];
foreach ($catalog as $key => $meta) {
    $on = in_array($key, $enabled, true);
    if ($on) {
        $onCount++;
    }
    $rows[] = [$meta['label'], $on];
}

require_once 'includes/layout_header.php';
$profile = getSchoolProfile($pdo);
$dashLogo = schoolBrandingUrl(($profile['logo_light'] ?? '') ?: ($profile['logo'] ?? ''), 'portal');
?>
<section class="sa-hero">
    <div>
        <span class="sa-hero-kicker">Control center</span>
        <h1>Super Admin</h1>
        <p>You decide the plan and modules. School Admin, Teacher and Student panels only show what you enable here.</p>
    </div>
    <div class="sa-hero-actions">
        <a href="features.php" class="btn btn-primary">Configure features</a>
        <a href="settings.php" class="btn-ghost">Settings</a>
    </div>
</section>

<?php if ($daysLeft !== null && $daysLeft <= 14): ?>
<div class="sa-license-banner <?php echo $daysLeft < 0 ? 'is-expired' : 'is-soon'; ?>">
    <?php if ($daysLeft < 0): ?>
    License expired on <?php echo e($school['expires_at']); ?>. School Admin and portals are blocked until you renew.
    <?php else: ?>
    License expires in <strong><?php echo (int) $daysLeft; ?> day<?php echo $daysLeft === 1 ? '' : 's'; ?></strong> (<?php echo e($school['expires_at']); ?>). A reminder is sent once a day when email or SMS is configured.
    <?php endif; ?>
    <a href="license.php">Update license</a>
</div>
<?php endif; ?>

<div class="sa-overview-hero sa-dash-school">
    <div class="sa-brand-preview">
        <?php if ($dashLogo): ?>
        <img class="sa-brand-preview-logo" src="<?php echo e($dashLogo); ?>" alt="">
        <?php endif; ?>
        <span>This school</span>
        <strong><?php echo e($profile['name'] ?: $school['name']); ?></strong>
        <em><?php echo e($profile['tagline'] ?: $planLabel . ' plan'); ?></em>
    </div>
    <div class="sa-overview-facts">
        <p class="sa-overview-facts-kicker">School details</p>
        <?php
        $dashPhone = trim((string) ($profile['phone'] ?: ($school['phone'] ?? '')));
        $dashEmail = trim((string) ($profile['email'] ?: ($school['email'] ?? '')));
        ?>
        <div class="sa-fact <?php echo $licenseOk ? 'is-ok' : 'is-blocked'; ?>">
            <span class="sa-fact-ico" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
            </span>
            <div class="sa-fact-body">
                <span>License</span>
                <strong><span class="sa-chip <?php echo $licenseOk ? 'on' : 'off'; ?>"><?php echo $licenseOk ? 'Access OK' : 'Blocked'; ?></span></strong>
            </div>
        </div>
        <div class="sa-fact">
            <span class="sa-fact-ico sa-fact-ico-phone" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.13.96.36 1.9.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0122 16.92z"/></svg>
            </span>
            <div class="sa-fact-body">
                <span>Contact</span>
                <strong><?php if ($dashPhone): ?><a href="tel:<?php echo e(preg_replace('/\s+/', '', $dashPhone)); ?>"><?php echo e($dashPhone); ?></a><?php else: ?>—<?php endif; ?></strong>
            </div>
        </div>
        <div class="sa-fact sa-fact-wide">
            <span class="sa-fact-ico sa-fact-ico-email" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><path d="M22 6l-10 7L2 6"/></svg>
            </span>
            <div class="sa-fact-body">
                <span>Email</span>
                <strong><?php if ($dashEmail): ?><a href="mailto:<?php echo e($dashEmail); ?>"><?php echo e($dashEmail); ?></a><?php else: ?>—<?php endif; ?></strong>
            </div>
        </div>
        <div class="sa-overview-actions">
            <a href="license.php" class="btn btn-primary btn-sm">Manage license</a>
            <a href="features.php" class="btn btn-outline btn-sm">Plan &amp; features</a>
            <a href="../admin/settings.php?tab=school" class="btn btn-outline btn-sm" target="_blank" rel="noopener">Edit profile in Admin</a>
        </div>
    </div>
</div>

<div class="sa-stat-grid">
    <article class="sa-stat">
        <span class="sa-stat-ico" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
        </span>
        <span class="sa-stat-label">School</span>
        <strong><?php echo e($school['name']); ?></strong>
    </article>
    <article class="sa-stat">
        <span class="sa-stat-ico" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/></svg>
        </span>
        <span class="sa-stat-label">Plan</span>
        <strong><?php echo e($planLabel); ?></strong>
    </article>
    <article class="sa-stat">
        <span class="sa-stat-ico" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
        </span>
        <span class="sa-stat-label">License</span>
        <strong><?php
            if (!$licenseOk) {
                echo 'Blocked';
            } elseif ($daysLeft === null) {
                echo 'Active · no expiry';
            } elseif ($daysLeft < 0) {
                echo 'Expired';
            } else {
                echo 'Active · ' . (int) $daysLeft . ' day' . ($daysLeft === 1 ? '' : 's') . ' left';
            }
        ?></strong>
    </article>
    <article class="sa-stat">
        <span class="sa-stat-ico" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
        </span>
        <span class="sa-stat-label">Active modules</span>
        <strong><?php echo (int) $onCount; ?> / <?php echo count($catalog); ?></strong>
    </article>
</div>

<div class="sa-stat-grid sa-stat-ops">
    <article class="sa-stat">
        <span class="sa-stat-label">Students</span>
        <strong><?php echo number_format((int) $stats['students']); ?></strong>
    </article>
    <article class="sa-stat">
        <span class="sa-stat-label">Teachers</span>
        <strong><?php echo number_format((int) $stats['teachers']); ?></strong>
    </article>
    <article class="sa-stat">
        <span class="sa-stat-label">Fees this month</span>
        <strong>₹<?php echo number_format((float) $stats['fee_month'], 2); ?></strong>
    </article>
    <article class="sa-stat">
        <span class="sa-stat-label">Gateways</span>
        <strong class="sa-gateway-line">
            <span class="sa-chip <?php echo $stats['smtp'] ? 'on' : 'off'; ?>">SMTP <?php echo $stats['smtp'] ? 'ON' : 'OFF'; ?></span>
            <span class="sa-chip <?php echo $stats['sms'] ? 'on' : 'off'; ?>">SMS <?php echo $stats['sms'] ? 'ON' : 'OFF'; ?></span>
            <span class="sa-chip <?php echo $stats['whatsapp'] ? 'on' : 'off'; ?>">WA <?php echo $stats['whatsapp'] ? 'ON' : 'OFF'; ?></span>
        </strong>
    </article>
</div>

<div class="sa-panel">
    <div class="sa-panel-head">
        <div>
            <h2>Module status</h2>
            <p>Live flags for this school install</p>
        </div>
        <a href="features.php" class="btn btn-outline btn-sm">Edit</a>
    </div>
    <div class="sa-panel-body">
        <div class="sa-mod-grid">
            <?php foreach ($rows as [$label, $on]): ?>
            <div class="sa-mod">
                <span class="sa-mod-name"><?php echo e($label); ?></span>
                <?php echo $on ? '<span class="sa-chip on">ON</span>' : '<span class="sa-chip off">OFF</span>'; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="sa-note">School Admin cannot change these. Disabled modules stay hidden in the sidebar and blocked by URL.</div>
    </div>
</div>

<div class="sa-quick-links">
    <a class="sa-quick-link" href="features.php">
        <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/></svg></span>
        <div>Plan &amp; Features<small>Presets &amp; toggles</small></div>
    </a>
    <a class="sa-quick-link" href="license.php">
        <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg></span>
        <div>License<small>Active / Suspended</small></div>
    </a>
    <a class="sa-quick-link" href="settings.php">
        <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 01-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg></span>
        <div>Settings<small>Gateways, backup, security</small></div>
    </a>
    <a class="sa-quick-link" href="../admin/index.php" target="_blank" rel="noopener">
        <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span>
        <div>Open Admin<small>School panel</small></div>
    </a>
    <a class="sa-quick-link" href="../teacher/index.php" target="_blank" rel="noopener">
        <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/></svg></span>
        <div>Teacher Portal<small>If enabled</small></div>
    </a>
    <a class="sa-quick-link" href="../portal/index.php" target="_blank" rel="noopener">
        <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
        <div>Student Portal<small>If enabled</small></div>
    </a>
</div>
<?php require_once 'includes/layout_footer.php'; ?>
