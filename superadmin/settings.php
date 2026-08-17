<?php
$page_title = 'Settings';
require_once 'includes/init.php';
require_once __DIR__ . '/../admin/includes/settings_helpers.php';
require_once __DIR__ . '/../admin/includes/db_settings_helpers.php';

ensureSettingsSchema($pdo);

$tab = $_GET['tab'] ?? 'overview';
$allowedTabs = ['overview', 'smtp', 'sms', 'whatsapp', 'database', 'backup', 'security', 'activity'];
if (!in_array($tab, $allowedTabs, true)) {
    $tab = 'overview';
}

$smtp = getSmtpSettings($pdo);
$sms = getSmsSettings($pdo);
$whatsapp = getWhatsAppSettings($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $tab = in_array($_POST['tab'] ?? '', $allowedTabs, true) ? $_POST['tab'] : $tab;
    saRequireCsrf('settings.php?tab=' . urlencode($tab));

    if ($action === 'save_smtp') {
        saveSettingsGroup($pdo, [
            'smtp_enabled'    => isset($_POST['smtp_enabled']) ? '1' : '0',
            'smtp_host'       => $_POST['smtp_host'] ?? '',
            'smtp_port'       => $_POST['smtp_port'] ?? '587',
            'smtp_encryption' => $_POST['smtp_encryption'] ?? 'tls',
            'smtp_username'   => $_POST['smtp_username'] ?? '',
            'smtp_password'   => $_POST['smtp_password'] ?? '',
            'smtp_from_email' => $_POST['smtp_from_email'] ?? '',
            'smtp_from_name'  => $_POST['smtp_from_name'] ?? '',
        ], ['smtp_password']);
        saLogActivity($pdo, 'smtp_saved', 'Email SMTP settings updated.');
        saFlash('success', 'Email SMTP settings saved.');
    } elseif ($action === 'test_smtp') {
        $testCfg = [
            'host'       => trim($_POST['smtp_host'] ?? $smtp['host']),
            'port'       => trim($_POST['smtp_port'] ?? $smtp['port']),
            'encryption' => trim($_POST['smtp_encryption'] ?? $smtp['encryption']),
            'username'   => trim($_POST['smtp_username'] ?? $smtp['username']),
            'password'   => trim($_POST['smtp_password'] ?? '') !== '' ? trim($_POST['smtp_password']) : $smtp['password'],
            'from_email' => trim($_POST['smtp_from_email'] ?? $smtp['from_email']),
            'from_name'  => trim($_POST['smtp_from_name'] ?? $smtp['from_name']),
        ];
        $testTo = trim($_POST['test_email'] ?? $testCfg['from_email']);
        $err = '';
        if (sendSmtpEmail($testCfg, $testTo, 'EduDash SMTP Test', '<p>Your SMTP configuration is working correctly.</p><p>Sent at ' . date('Y-m-d H:i:s') . '</p>', $err)) {
            saLogActivity($pdo, 'smtp_tested', 'Test email sent to ' . $testTo);
            saFlash('success', 'Test email sent to ' . $testTo);
        } else {
            saLogActivity($pdo, 'smtp_tested', 'SMTP test failed: ' . $err);
            saFlash('error', 'SMTP test failed: ' . $err);
        }
    } elseif ($action === 'save_sms') {
        saveSettingsGroup($pdo, [
            'sms_enabled'   => isset($_POST['sms_enabled']) ? '1' : '0',
            'sms_provider'  => $_POST['sms_provider'] ?? 'MSG91',
            'sms_api_key'   => $_POST['sms_api_key'] ?? '',
            'sms_sender_id' => $_POST['sms_sender_id'] ?? '',
            'sms_route'     => $_POST['sms_route'] ?? '4',
            'sms_api_url'   => $_POST['sms_api_url'] ?? '',
        ], ['sms_api_key']);
        saLogActivity($pdo, 'sms_saved', 'SMS / MSG settings updated.');
        saFlash('success', 'SMS settings saved.');
    } elseif ($action === 'test_sms') {
        $mobile = trim($_POST['test_mobile'] ?? '');
        if (trim($_POST['sms_api_key'] ?? '') !== '') {
            setSetting($pdo, 'sms_api_key', trim($_POST['sms_api_key']));
        }
        foreach (['sms_provider', 'sms_sender_id', 'sms_route', 'sms_api_url'] as $k) {
            if (isset($_POST[$k]) && $_POST[$k] !== '') {
                setSetting($pdo, $k, trim($_POST[$k]));
            }
        }
        setSetting($pdo, 'sms_enabled', '1');
        $result = dispatchSms($pdo, $mobile, 'EduDash SMS test — your MSG/SMS setup is working. ' . date('H:i:s'));
        saLogActivity($pdo, 'sms_tested', $result['ok'] ? 'Test SMS sent to ' . $mobile : 'SMS test failed');
        saFlash($result['ok'] ? 'success' : 'error', $result['ok'] ? 'Test SMS sent to ' . $mobile : $result['error']);
    } elseif ($action === 'save_whatsapp') {
        saveSettingsGroup($pdo, [
            'whatsapp_enabled'         => isset($_POST['whatsapp_enabled']) ? '1' : '0',
            'whatsapp_provider'        => $_POST['whatsapp_provider'] ?? 'Meta Cloud API',
            'whatsapp_api_token'       => $_POST['whatsapp_api_token'] ?? '',
            'whatsapp_phone_id'        => $_POST['whatsapp_phone_id'] ?? '',
            'whatsapp_business_number' => $_POST['whatsapp_business_number'] ?? '',
            'whatsapp_api_url'         => $_POST['whatsapp_api_url'] ?? '',
        ], ['whatsapp_api_token']);
        saLogActivity($pdo, 'whatsapp_saved', 'WhatsApp settings updated.');
        saFlash('success', 'WhatsApp settings saved.');
    } elseif ($action === 'test_whatsapp') {
        $mobile = trim($_POST['test_mobile'] ?? '');
        if (trim($_POST['whatsapp_api_token'] ?? '') !== '') {
            setSetting($pdo, 'whatsapp_api_token', trim($_POST['whatsapp_api_token']));
        }
        foreach (['whatsapp_provider', 'whatsapp_phone_id', 'whatsapp_business_number', 'whatsapp_api_url'] as $k) {
            if (isset($_POST[$k]) && $_POST[$k] !== '') {
                setSetting($pdo, $k, trim($_POST[$k]));
            }
        }
        setSetting($pdo, 'whatsapp_enabled', '1');
        $result = dispatchWhatsApp($pdo, $mobile, 'EduDash WhatsApp test — your setup is working. ' . date('H:i:s'));
        saLogActivity($pdo, 'whatsapp_tested', $result['ok'] ? 'Test WhatsApp sent to ' . $mobile : 'WhatsApp test failed');
        saFlash($result['ok'] ? 'success' : 'error', $result['ok'] ? 'Test WhatsApp message sent to ' . $mobile : $result['error']);
    } elseif ($action === 'save_database') {
        $config = buildDatabaseSettingsFromPost($_POST);
        if (!saveDbProfilesConfig($config)) {
            saFlash('error', 'Could not save database settings. Check write permission on the includes/ folder.');
        } else {
            saLogActivity($pdo, 'database_saved', 'Online / offline database settings updated.');
            saFlash('success', 'Database settings saved. Refresh the page to apply the new connection.');
        }
    } elseif ($action === 'test_db_online' || $action === 'test_db_offline') {
        $profileKey = $action === 'test_db_online' ? 'online' : 'offline';
        $test = testDbProfile(databaseProfileFromPost($_POST, $profileKey));
        if ($test['ok']) {
            saLogActivity($pdo, 'database_tested', ucfirst($profileKey) . ' database connected (' . $test['latency_ms'] . ' ms).');
            saFlash('success', ucfirst($profileKey) . ' database connected successfully (' . $test['latency_ms'] . ' ms).');
        } else {
            saLogActivity($pdo, 'database_tested', ucfirst($profileKey) . ' connection failed.');
            saFlash('error', ucfirst($profileKey) . ' connection failed: ' . $test['error']);
        }
    } elseif ($action === 'change_password') {
        $tab = 'security';
        $errors = saChangeSuperAdminPassword(
            $pdo,
            (int) ($_SESSION['sa_id'] ?? 0),
            (string) ($_POST['current_password'] ?? ''),
            (string) ($_POST['new_password'] ?? ''),
            (string) ($_POST['confirm_password'] ?? '')
        );
        if ($errors) {
            saFlash('error', implode(' ', $errors));
        } else {
            saFlash('success', 'Password changed successfully. Use the new password on your next login.');
        }
    } elseif ($action === 'download_backup') {
        $sql = saDumpDatabase($pdo);
        saLogActivity($pdo, 'backup_downloaded', 'SQL backup downloaded.');
        $filename = 'schoolerp-backup-' . date('Ymd-His') . '.sql';
        header('Content-Type: application/sql; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($sql));
        echo $sql;
        exit;
    } elseif ($action === 'restore_backup') {
        $tab = 'backup';
        if (empty($_POST['confirm_restore'])) {
            saFlash('error', 'Tick the confirmation box before restoring a backup.');
        } elseif (empty($_FILES['backup_file']['tmp_name']) || !is_uploaded_file($_FILES['backup_file']['tmp_name'])) {
            saFlash('error', 'Choose a .sql backup file to restore.');
        } else {
            $sql = (string) file_get_contents($_FILES['backup_file']['tmp_name']);
            if (trim($sql) === '') {
                saFlash('error', 'The uploaded file is empty.');
            } else {
                try {
                    saRestoreDatabase($pdo, $sql);
                    saLogActivity($pdo, 'backup_restored', 'Database restored from uploaded SQL file.');
                    saFlash('success', 'Database restored. Sign in again if the session was reset.');
                } catch (Throwable $e) {
                    saFlash('error', 'Restore failed: ' . $e->getMessage());
                }
            }
        }
    }

    header('Location: settings.php?tab=' . urlencode($tab));
    exit;
}

$smtp = getSmtpSettings($pdo);
$sms = getSmsSettings($pdo);
$whatsapp = getWhatsAppSettings($pdo);
$dbSettings = getDatabaseSettingsForm();
$dbActiveProfile = $db_active_profile ?? 'offline';
$dbConnectionMode = $db_connection_mode ?? 'offline';
$smtpOn = ($smtp['enabled'] ?? '0') === '1';
$smsOn = ($sms['enabled'] ?? '0') === '1';
$waOn = ($whatsapp['enabled'] ?? '0') === '1';
$schoolProfile = getSchoolProfile($pdo);
$schoolLogoUrl = schoolBrandingUrl($schoolProfile['logo'] ?? '', 'portal');
$schoolLogoLightUrl = schoolBrandingUrl($schoolProfile['logo_light'] ?? '', 'portal');
$schoolIconUrl = schoolBrandingUrl($schoolProfile['logo_icon'] ?? '', 'portal');
$planLabel = saPlanLabel($school['plan'] ?? 'Custom');
$licenseDays = saLicenseDaysLeft($school);
$licenseOk = ($school['status'] ?? '') !== 'Suspended'
    && (empty($school['starts_at']) || $school['starts_at'] <= date('Y-m-d'))
    && (empty($school['expires_at']) || $school['expires_at'] >= date('Y-m-d'));
$moduleKeys = getSchoolModuleKeys($pdo, (int) $school['id']);
$moduleTotal = count(getErpModuleCatalog());

$saAccountName = (string) ($_SESSION['sa_name'] ?? 'Super Admin');
$saAccountUser = (string) ($_SESSION['sa_username'] ?? 'superadmin');
$saMustChange = !empty($_SESSION['sa_must_change']);
$saLastLogin = null;
$activityLogs = [];
$activityFilter = $_GET['filter'] ?? 'all';
if (!in_array($activityFilter, ['all', 'auth', 'settings', 'features'], true)) {
    $activityFilter = 'all';
}
$activityPage = max(1, (int) ($_GET['p'] ?? 1));
$activityPerPage = 50;
$activityTotal = 0;
$activityPages = 1;

if ($tab === 'activity' && !empty($_GET['export'])) {
    saStreamActivityCsv($pdo, $activityFilter);
}

if ($tab === 'security') {
    try {
        $saLastLogin = $pdo->query("SELECT * FROM sa_activity_logs WHERE action = 'login' ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) {
        $saLastLogin = null;
    }
}

if ($tab === 'activity') {
    $activityTotal = saCountActivityLogs($pdo, $activityFilter);
    $activityPages = max(1, (int) ceil($activityTotal / $activityPerPage));
    if ($activityPage > $activityPages) {
        $activityPage = $activityPages;
    }
    $activityLogs = saGetActivityLogs($pdo, $activityPerPage, ($activityPage - 1) * $activityPerPage, $activityFilter);
}

$eyeBtn = '<button type="button" class="password-toggle" data-password-toggle aria-label="Show password" title="Show password">'
    . '<svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>'
    . '<svg class="eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>'
    . '</button>';

require_once 'includes/layout_header.php';
?>
<section class="sa-hero">
    <div>
        <span class="sa-hero-kicker">Platform</span>
        <h1>Settings</h1>
        <p>You control license, modules, gateways and backups for this install. School staff edit name, logos and day-to-day data in Admin.</p>
    </div>
</section>

<div class="settings-layout sa-settings">
    <aside class="settings-nav">
        <div class="settings-nav-group">
            <span class="settings-nav-label">This school</span>
            <a href="settings.php?tab=overview" class="settings-nav-item <?php echo $tab === 'overview' ? 'active' : ''; ?>">
                <span class="sni-ico blue">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                </span>
                School overview
            </a>
        </div>
        <div class="settings-nav-group">
            <span class="settings-nav-label">Messaging</span>
            <a href="settings.php?tab=smtp" class="settings-nav-item <?php echo $tab === 'smtp' ? 'active' : ''; ?>">
                <span class="sni-ico red">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                </span>
                Email SMTP
            </a>
            <a href="settings.php?tab=sms" class="settings-nav-item <?php echo $tab === 'sms' ? 'active' : ''; ?>">
                <span class="sni-ico pink">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                </span>
                SMS / MSG
            </a>
            <a href="settings.php?tab=whatsapp" class="settings-nav-item <?php echo $tab === 'whatsapp' ? 'active' : ''; ?>">
                <span class="sni-ico teal">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/></svg>
                </span>
                WhatsApp
            </a>
        </div>
        <div class="settings-nav-group">
            <span class="settings-nav-label">Infrastructure</span>
            <a href="settings.php?tab=database" class="settings-nav-item <?php echo $tab === 'database' ? 'active' : ''; ?>">
                <span class="sni-ico orange">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
                </span>
                Online &amp; Offline DB
            </a>
            <a href="settings.php?tab=backup" class="settings-nav-item <?php echo $tab === 'backup' ? 'active' : ''; ?>">
                <span class="sni-ico green">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                </span>
                Backup &amp; Restore
            </a>
        </div>
        <div class="settings-nav-group">
            <span class="settings-nav-label">Account</span>
            <a href="settings.php?tab=security" class="settings-nav-item <?php echo $tab === 'security' ? 'active' : ''; ?>">
                <span class="sni-ico violet">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                </span>
                Security
            </a>
            <a href="settings.php?tab=activity" class="settings-nav-item <?php echo $tab === 'activity' ? 'active' : ''; ?>">
                <span class="sni-ico slate">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                </span>
                Activity Log
            </a>
        </div>
    </aside>

    <section class="settings-main">
        <?php if ($tab === 'overview'):
            $overviewLogo = $schoolLogoLightUrl ?: $schoolLogoUrl;
            $licenseText = !$licenseOk
                ? 'Blocked'
                : ($licenseDays === null
                    ? 'Active · no expiry'
                    : ($licenseDays < 0 ? 'Expired' : ('Active · ' . (int) $licenseDays . ' days left')));
        ?>
        <div class="settings-card">
            <div class="settings-card-head">
                <div class="settings-title-block">
                    <span class="settings-title-ico blue">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                    </span>
                    <div>
                        <h2>This school</h2>
                        <p>Platform snapshot for this install. Name, logos and contact are edited in School Admin.</p>
                    </div>
                </div>
                <span class="status-pill <?php echo $licenseOk ? 'online' : 'offline'; ?>"><?php echo $licenseOk ? 'Access OK' : 'Blocked'; ?></span>
            </div>

            <div class="settings-section">
                <div class="sa-overview-hero">
                    <div class="sa-brand-preview">
                        <?php if ($overviewLogo): ?>
                        <img class="sa-brand-preview-logo" src="<?php echo e($overviewLogo); ?>" alt="">
                        <?php elseif ($schoolIconUrl): ?>
                        <img class="sa-brand-preview-logo" src="<?php echo e($schoolIconUrl); ?>" alt="">
                        <?php endif; ?>
                        <span>This install</span>
                        <strong><?php echo e($schoolProfile['name'] ?: $school['name']); ?></strong>
                        <em><?php echo e($schoolProfile['tagline'] ?: 'School ERP'); ?></em>
                    </div>
                    <div class="sa-overview-facts">
                        <div><span>Plan</span><strong><?php echo e($planLabel); ?></strong></div>
                        <div><span>License</span><strong><?php echo e($licenseText); ?></strong></div>
                        <div><span>Modules</span><strong><?php echo count($moduleKeys); ?> / <?php echo (int) $moduleTotal; ?> on</strong></div>
                        <div><span>Phone</span><strong><?php echo e($schoolProfile['phone'] ?: ($school['phone'] ?? '—')); ?></strong></div>
                        <div><span>Email</span><strong><?php echo e($schoolProfile['email'] ?: ($school['email'] ?? '—')); ?></strong></div>
                        <div><span>Address</span><strong><?php echo e($schoolProfile['address'] ?: '—'); ?></strong></div>
                    </div>
                </div>
            </div>

            <div class="settings-section">
                <div class="settings-section-head">
                    <span class="ssh-ico blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg></span>
                    <h3>What Super Admin controls</h3>
                </div>
                <div class="sa-overview-links">
                    <a href="features.php">
                        <strong>Plan &amp; Features</strong>
                        <small>Which modules Admin, Teacher and Student can use</small>
                    </a>
                    <a href="license.php">
                        <strong>School License</strong>
                        <small>Active / Suspended, start date and expiry</small>
                    </a>
                    <a href="settings.php?tab=smtp">
                        <strong>Email, SMS, WhatsApp</strong>
                        <small>Gateways School Admin cannot change</small>
                    </a>
                    <a href="settings.php?tab=backup">
                        <strong>Backup &amp; Restore</strong>
                        <small>SQL dump for this install</small>
                    </a>
                    <a href="settings.php?tab=database">
                        <strong>Online &amp; Offline DB</strong>
                        <small>Cloud and local XAMPP connection</small>
                    </a>
                    <a href="settings.php?tab=security">
                        <strong>Security &amp; activity</strong>
                        <small>Super Admin password and audit log</small>
                    </a>
                </div>
            </div>

            <div class="settings-section">
                <div class="settings-info">
                    <span class="si-ico">i</span>
                    <p>
                        School name, logos, tagline and address are set in
                        <a href="../admin/settings.php?tab=school" target="_blank" rel="noopener">School Admin → Settings</a>.
                        Open the public <a href="../index.php" target="_blank" rel="noopener">website</a> to check how it looks.
                    </p>
                </div>
            </div>
        </div>

        <?php elseif ($tab === 'smtp'): ?>
        <form method="post" class="settings-card" autocomplete="off">
            <?php echo saCsrfField(); ?>
            <input type="hidden" name="tab" value="smtp">
            <div class="settings-card-head">
                <div class="settings-title-block">
                    <span class="settings-title-ico blue">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    </span>
                    <div>
                        <h2>Email SMTP Setup</h2>
                        <p>Outgoing mail for receipts, alerts and notifications.</p>
                    </div>
                </div>
                <span class="status-pill <?php echo $smtpOn ? 'online' : 'offline'; ?>"><?php echo $smtpOn ? 'Enabled' : 'Disabled'; ?></span>
            </div>
            <div class="settings-section">
                <div class="settings-section-head">
                    <span class="ssh-ico blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2"/></svg></span>
                    <h3>Connection</h3>
                </div>
                <div class="settings-fields two">
                    <div class="form-group" style="grid-column:1/-1">
                        <label class="sa-check-label">
                            <input type="checkbox" name="smtp_enabled" value="1" <?php echo $smtpOn ? 'checked' : ''; ?>>
                            Enable SMTP email
                        </label>
                    </div>
                    <div class="form-group">
                        <label>SMTP Host</label>
                        <input type="text" name="smtp_host" value="<?php echo e($smtp['host']); ?>" placeholder="smtp.gmail.com">
                    </div>
                    <div class="form-group">
                        <label>Port</label>
                        <input type="number" name="smtp_port" value="<?php echo e($smtp['port']); ?>" placeholder="587">
                    </div>
                    <div class="form-group">
                        <label>Encryption</label>
                        <select name="smtp_encryption">
                            <option value="tls" <?php echo $smtp['encryption'] === 'tls' ? 'selected' : ''; ?>>TLS</option>
                            <option value="ssl" <?php echo $smtp['encryption'] === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                            <option value="none" <?php echo $smtp['encryption'] === 'none' ? 'selected' : ''; ?>>None</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="smtp_username" value="<?php echo e($smtp['username']); ?>" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <div class="password-field">
                            <input type="password" name="smtp_password" value="" placeholder="<?php echo $smtp['password'] !== '' ? 'Leave blank to keep current' : 'SMTP password'; ?>" autocomplete="new-password">
                            <?php echo $eyeBtn; ?>
                        </div>
                        <?php if ($smtp['password'] !== ''): ?><span class="sa-field-hint">Saved: <?php echo e(saMaskSecret($smtp['password'])); ?></span><?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="settings-section">
                <div class="settings-section-head">
                    <span class="ssh-ico teal"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
                    <h3>From identity</h3>
                </div>
                <div class="settings-fields two">
                    <div class="form-group">
                        <label>From Name</label>
                        <input type="text" name="smtp_from_name" value="<?php echo e($smtp['from_name']); ?>">
                    </div>
                    <div class="form-group">
                        <label>From Email</label>
                        <input type="email" name="smtp_from_email" value="<?php echo e($smtp['from_email']); ?>" placeholder="noreply@school.com">
                    </div>
                </div>
            </div>
            <div class="settings-card-foot sa-settings-foot">
                <button type="submit" name="action" value="save_smtp" class="btn btn-primary">Save SMTP settings</button>
                <div class="sa-test-row">
                    <input type="email" name="test_email" value="<?php echo e($smtp['from_email']); ?>" placeholder="Test recipient">
                    <button type="submit" name="action" value="test_smtp" class="btn btn-outline" formnovalidate>Send test</button>
                </div>
            </div>
        </form>

        <?php elseif ($tab === 'sms'): ?>
        <form method="post" class="settings-card" autocomplete="off">
            <?php echo saCsrfField(); ?>
            <input type="hidden" name="tab" value="sms">
            <div class="settings-card-head">
                <div class="settings-title-block">
                    <span class="settings-title-ico pink">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                    </span>
                    <div>
                        <h2>SMS / MSG Setup</h2>
                        <p>MSG91 or a custom gateway for fee and attendance alerts.</p>
                    </div>
                </div>
                <span class="status-pill <?php echo $smsOn ? 'online' : 'offline'; ?>"><?php echo $smsOn ? 'Enabled' : 'Disabled'; ?></span>
            </div>
            <div class="settings-section">
                <div class="settings-section-head">
                    <span class="ssh-ico pink"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg></span>
                    <h3>Provider &amp; credentials</h3>
                </div>
                <div class="settings-fields two">
                    <div class="form-group" style="grid-column:1/-1">
                        <label class="sa-check-label">
                            <input type="checkbox" name="sms_enabled" value="1" <?php echo $smsOn ? 'checked' : ''; ?>>
                            Enable SMS notifications
                        </label>
                    </div>
                    <div class="form-group">
                        <label>Provider</label>
                        <select name="sms_provider">
                            <option value="MSG91" <?php echo $sms['provider'] === 'MSG91' ? 'selected' : ''; ?>>MSG91</option>
                            <option value="Custom" <?php echo $sms['provider'] === 'Custom' ? 'selected' : ''; ?>>Custom</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Auth Key / API Key</label>
                        <div class="password-field">
                            <input type="password" name="sms_api_key" value="" placeholder="<?php echo $sms['api_key'] !== '' ? 'Leave blank to keep current' : 'MSG91 auth key'; ?>" autocomplete="new-password">
                            <?php echo $eyeBtn; ?>
                        </div>
                        <?php if ($sms['api_key'] !== ''): ?><span class="sa-field-hint">Saved: <?php echo e(saMaskSecret($sms['api_key'])); ?></span><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label>Sender ID</label>
                        <input type="text" name="sms_sender_id" value="<?php echo e($sms['sender_id']); ?>" maxlength="6" placeholder="EDUDSH">
                    </div>
                    <div class="form-group">
                        <label>Route</label>
                        <input type="text" name="sms_route" value="<?php echo e($sms['route']); ?>" placeholder="4">
                    </div>
                    <div class="form-group" style="grid-column:1/-1">
                        <label>Custom API URL (optional)</label>
                        <input type="text" name="sms_api_url" value="<?php echo e($sms['api_url']); ?>" placeholder="https://api.example.com/sms?mobile={mobile}&amp;msg={message}&amp;key={api_key}">
                    </div>
                </div>
            </div>
            <div class="settings-card-foot sa-settings-foot">
                <button type="submit" name="action" value="save_sms" class="btn btn-primary">Save SMS settings</button>
                <div class="sa-test-row">
                    <input type="text" name="test_mobile" placeholder="9876543210">
                    <button type="submit" name="action" value="test_sms" class="btn btn-outline" formnovalidate>Send test</button>
                </div>
            </div>
        </form>

        <?php elseif ($tab === 'whatsapp'): ?>
        <form method="post" class="settings-card" autocomplete="off">
            <?php echo saCsrfField(); ?>
            <input type="hidden" name="tab" value="whatsapp">
            <div class="settings-card-head">
                <div class="settings-title-block">
                    <span class="settings-title-ico teal">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/></svg>
                    </span>
                    <div>
                        <h2>WhatsApp Setup</h2>
                        <p>Meta Cloud API or custom webhook for WhatsApp Business messages.</p>
                    </div>
                </div>
                <span class="status-pill <?php echo $waOn ? 'online' : 'offline'; ?>"><?php echo $waOn ? 'Enabled' : 'Disabled'; ?></span>
            </div>
            <div class="settings-section">
                <div class="settings-section-head">
                    <span class="ssh-ico teal"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M12 1v2M12 21v2"/></svg></span>
                    <h3>Provider &amp; credentials</h3>
                </div>
                <div class="settings-fields two">
                    <div class="form-group" style="grid-column:1/-1">
                        <label class="sa-check-label">
                            <input type="checkbox" name="whatsapp_enabled" value="1" <?php echo $waOn ? 'checked' : ''; ?>>
                            Enable WhatsApp notifications
                        </label>
                    </div>
                    <div class="form-group">
                        <label>Provider</label>
                        <select name="whatsapp_provider">
                            <option value="Meta Cloud API" <?php echo $whatsapp['provider'] === 'Meta Cloud API' ? 'selected' : ''; ?>>Meta Cloud API</option>
                            <option value="Custom" <?php echo $whatsapp['provider'] === 'Custom' ? 'selected' : ''; ?>>Custom</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Business Number</label>
                        <input type="text" name="whatsapp_business_number" value="<?php echo e($whatsapp['business_number']); ?>" placeholder="+91XXXXXXXXXX">
                    </div>
                    <div class="form-group">
                        <label>Phone Number ID</label>
                        <input type="text" name="whatsapp_phone_id" value="<?php echo e($whatsapp['phone_id']); ?>" placeholder="Meta Phone Number ID">
                    </div>
                    <div class="form-group">
                        <label>Permanent Access Token</label>
                        <div class="password-field">
                            <input type="password" name="whatsapp_api_token" value="" placeholder="<?php echo $whatsapp['api_token'] !== '' ? 'Leave blank to keep current' : 'WhatsApp API token'; ?>" autocomplete="new-password">
                            <?php echo $eyeBtn; ?>
                        </div>
                        <?php if ($whatsapp['api_token'] !== ''): ?><span class="sa-field-hint">Saved: <?php echo e(saMaskSecret($whatsapp['api_token'])); ?></span><?php endif; ?>
                    </div>
                    <div class="form-group" style="grid-column:1/-1">
                        <label>Custom API URL (optional)</label>
                        <input type="text" name="whatsapp_api_url" value="<?php echo e($whatsapp['api_url']); ?>" placeholder="https://your-api.com/wa?to={mobile}&amp;text={message}&amp;token={token}">
                    </div>
                </div>
                <div class="settings-info">
                    <span class="si-ico">i</span>
                    <p>School Admin can send alerts from Notifications. Live delivery only works when this gateway is enabled here.</p>
                </div>
            </div>
            <div class="settings-card-foot sa-settings-foot">
                <button type="submit" name="action" value="save_whatsapp" class="btn btn-primary">Save WhatsApp settings</button>
                <div class="sa-test-row">
                    <input type="text" name="test_mobile" placeholder="919876543210">
                    <button type="submit" name="action" value="test_whatsapp" class="btn btn-outline" formnovalidate>Send test</button>
                </div>
            </div>
        </form>

        <?php elseif ($tab === 'database'): ?>
        <form method="post" class="settings-card" autocomplete="off" id="saDbForm">
            <?php echo saCsrfField(); ?>
            <input type="hidden" name="tab" value="database">
            <div class="settings-card-head">
                <div class="settings-title-block">
                    <span class="settings-title-ico orange">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
                    </span>
                    <div>
                        <h2>Online &amp; Offline Database</h2>
                        <p>Cloud MySQL and local XAMPP. First-time table install is on the Super Admin login page.</p>
                    </div>
                </div>
                <span class="status-pill <?php echo $dbActiveProfile === 'online' ? 'online' : 'offline'; ?>">
                    <?php echo $dbActiveProfile === 'online' ? 'Online' : 'Offline'; ?>
                </span>
            </div>
            <div class="settings-section">
                <div class="sa-db-status <?php echo $dbActiveProfile === 'online' ? 'is-online' : 'is-offline'; ?>">
                    <strong><?php echo $dbActiveProfile === 'online' ? 'Connected to Online Database' : 'Connected to Offline Database'; ?></strong>
                    <small>Mode: <?php echo e(ucfirst($dbConnectionMode)); ?></small>
                </div>
                <div class="sa-form-grid" style="margin-bottom:1.15rem">
                    <div class="form-group span-2">
                        <label>Connection mode</label>
                        <div class="sa-mode-grid">
                            <label class="sa-mode-opt <?php echo $dbSettings['mode'] === 'auto' ? 'is-on' : ''; ?>">
                                <input type="radio" name="db_mode" value="auto" <?php echo $dbSettings['mode'] === 'auto' ? 'checked' : ''; ?>>
                                <strong>Auto</strong>
                                <small>Online when available, then offline</small>
                            </label>
                            <label class="sa-mode-opt <?php echo $dbSettings['mode'] === 'online' ? 'is-on' : ''; ?>">
                                <input type="radio" name="db_mode" value="online" <?php echo $dbSettings['mode'] === 'online' ? 'checked' : ''; ?>>
                                <strong>Online only</strong>
                                <small>Always use cloud server</small>
                            </label>
                            <label class="sa-mode-opt <?php echo $dbSettings['mode'] === 'offline' ? 'is-on' : ''; ?>">
                                <input type="radio" name="db_mode" value="offline" <?php echo $dbSettings['mode'] === 'offline' ? 'checked' : ''; ?>>
                                <strong>Offline only</strong>
                                <small>Always use local XAMPP</small>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="sa-db-profiles">
                    <div class="sa-db-card">
                        <h3>Online Database</h3>
                        <p>Hosting / VPS / cloud MySQL</p>
                        <div class="settings-fields two">
                            <div class="form-group" style="grid-column:1/-1"><label>Label</label><input type="text" name="db_online_label" value="<?php echo e($dbSettings['online']['label']); ?>"></div>
                            <div class="form-group"><label>Host</label><input type="text" name="db_online_host" value="<?php echo e($dbSettings['online']['host']); ?>" placeholder="db.yourhost.com"></div>
                            <div class="form-group"><label>Port</label><input type="number" name="db_online_port" value="<?php echo (int) $dbSettings['online']['port']; ?>"></div>
                            <div class="form-group"><label>Database</label><input type="text" name="db_online_dbname" value="<?php echo e($dbSettings['online']['dbname']); ?>"></div>
                            <div class="form-group"><label>Username</label><input type="text" name="db_online_username" value="<?php echo e($dbSettings['online']['username']); ?>"></div>
                            <div class="form-group" style="grid-column:1/-1">
                                <label>Password</label>
                                <div class="password-field">
                                    <input type="password" name="db_online_password" value="" placeholder="<?php echo $dbSettings['online']['password'] !== '' ? 'Leave blank to keep current' : 'Database password'; ?>" autocomplete="new-password">
                                    <?php echo $eyeBtn; ?>
                                </div>
                            </div>
                        </div>
                        <button type="submit" name="action" value="test_db_online" class="btn btn-outline" formnovalidate>Test online connection</button>
                    </div>
                    <div class="sa-db-card">
                        <h3>Offline Database</h3>
                        <p>Local XAMPP / WAMP on this computer</p>
                        <div class="settings-fields two">
                            <div class="form-group" style="grid-column:1/-1"><label>Label</label><input type="text" name="db_offline_label" value="<?php echo e($dbSettings['offline']['label']); ?>"></div>
                            <div class="form-group"><label>Host</label><input type="text" name="db_offline_host" value="<?php echo e($dbSettings['offline']['host']); ?>" placeholder="localhost"></div>
                            <div class="form-group"><label>Port</label><input type="number" name="db_offline_port" value="<?php echo (int) $dbSettings['offline']['port']; ?>"></div>
                            <div class="form-group"><label>Database</label><input type="text" name="db_offline_dbname" value="<?php echo e($dbSettings['offline']['dbname']); ?>"></div>
                            <div class="form-group"><label>Username</label><input type="text" name="db_offline_username" value="<?php echo e($dbSettings['offline']['username']); ?>"></div>
                            <div class="form-group" style="grid-column:1/-1">
                                <label>Password</label>
                                <div class="password-field">
                                    <input type="password" name="db_offline_password" value="" placeholder="<?php echo $dbSettings['offline']['password'] !== '' ? 'Leave blank to keep current' : 'Usually empty on XAMPP'; ?>" autocomplete="new-password">
                                    <?php echo $eyeBtn; ?>
                                </div>
                            </div>
                        </div>
                        <button type="submit" name="action" value="test_db_offline" class="btn btn-outline" formnovalidate>Test offline connection</button>
                    </div>
                </div>
                <div class="settings-info">
                    <span class="si-ico">i</span>
                    <p>Saved in <code>includes/db_profiles.local.php</code>. On a local PC Auto prefers offline; on the live server it prefers online. If that fails, it falls back to the other profile.</p>
                </div>
            </div>
            <div class="settings-card-foot">
                <button type="submit" name="action" value="save_database" class="btn btn-primary">Save database settings</button>
            </div>
        </form>

        <?php elseif ($tab === 'backup'): ?>
        <div class="settings-card">
            <div class="settings-card-head">
                <div class="settings-title-block">
                    <span class="settings-title-ico orange">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    </span>
                    <div>
                        <h2>Backup &amp; Restore</h2>
                        <p>Download a full SQL dump of this install, or restore from a previous dump.</p>
                    </div>
                </div>
            </div>
            <div class="settings-section sa-backup-grid">
                <div class="sa-backup-card">
                    <h3>Download backup</h3>
                    <p>Exports every table in the active database as a .sql file. Keep it somewhere safe off this server.</p>
                    <form method="post">
                        <?php echo saCsrfField(); ?>
                        <input type="hidden" name="tab" value="backup">
                        <button type="submit" name="action" value="download_backup" class="btn btn-primary">Download SQL backup</button>
                    </form>
                </div>
                <div class="sa-backup-card is-warn">
                    <h3>Restore backup</h3>
                    <p>This replaces current data with the uploaded dump. Sign-ins and settings will match the backup.</p>
                    <form method="post" enctype="multipart/form-data">
                        <?php echo saCsrfField(); ?>
                        <input type="hidden" name="tab" value="backup">
                        <div class="form-group">
                            <label>SQL file</label>
                            <input type="file" name="backup_file" accept=".sql,text/plain,application/sql" required>
                        </div>
                        <label class="sa-check-label">
                            <input type="checkbox" name="confirm_restore" value="1" required>
                            I understand this overwrites the current database.
                        </label>
                        <button type="submit" name="action" value="restore_backup" class="btn btn-outline" onclick="return confirm('Restore this backup and overwrite the current database?');">Restore now</button>
                    </form>
                </div>
            </div>
            <div class="settings-section">
                <div class="settings-info">
                    <span class="si-ico">i</span>
                    <p>Restore only files created from this Backup page. Activity older than 90 days is removed automatically from the log, not from SQL backups.</p>
                </div>
            </div>
        </div>

        <?php elseif ($tab === 'security'): ?>
        <div class="settings-card sa-security-card">
            <div class="settings-card-head">
                <div class="settings-title-block">
                    <span class="settings-title-ico violet">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                    </span>
                    <div>
                        <h2>Security</h2>
                        <p>Change the Super Admin password for this install.</p>
                    </div>
                </div>
            </div>
            <div class="settings-section sa-security-body">
                <?php if ($saMustChange): ?>
                <div class="sa-force-banner">This account still uses the default password. Choose a new one to unlock Super Admin.</div>
                <?php endif; ?>
                <div class="sa-sec-account">
                    <span class="sa-sec-avatar"><?php echo e(strtoupper(substr($saAccountName, 0, 1))); ?></span>
                    <div class="sa-sec-copy">
                        <strong><?php echo e($saAccountName); ?></strong>
                        <small>@<?php echo e($saAccountUser); ?></small>
                    </div>
                    <?php if ($saLastLogin):
                        $loginTs = strtotime((string) $saLastLogin['created_at']);
                    ?>
                    <div class="sa-sec-last">
                        <span>Last login</span>
                        <strong><?php echo $loginTs ? date('d M Y, h:i A', $loginTs) : e($saLastLogin['created_at']); ?></strong>
                        <small><?php echo e($saLastLogin['ip_address'] ?: 'IP unknown'); ?></small>
                    </div>
                    <?php endif; ?>
                </div>

                <form method="post" class="sa-password-form" autocomplete="off">
                    <?php echo saCsrfField(); ?>
                    <input type="hidden" name="tab" value="security">
                    <input type="hidden" name="action" value="change_password">

                    <div class="sa-password-panel">
                        <div class="sa-password-panel-head">
                            <span class="ssh-ico violet"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg></span>
                            <div>
                                <h3>Change password</h3>
                                <p>Enter your current password, then choose a new one.</p>
                            </div>
                        </div>

                        <div class="sa-pass-wrap">
                            <div class="form-group">
                                <label for="saCurrentPassword">Current password</label>
                                <div class="password-field">
                                    <input type="password" id="saCurrentPassword" name="current_password" required autocomplete="current-password" placeholder="Enter current password">
                                    <?php echo $eyeBtn; ?>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="saNewPassword">New password</label>
                                <div class="password-field">
                                    <input type="password" id="saNewPassword" name="new_password" required minlength="6" autocomplete="new-password" placeholder="At least 6 characters">
                                    <?php echo $eyeBtn; ?>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="saConfirmPassword">Confirm new password</label>
                                <div class="password-field">
                                    <input type="password" id="saConfirmPassword" name="confirm_password" required minlength="6" autocomplete="new-password" placeholder="Re-enter new password">
                                    <?php echo $eyeBtn; ?>
                                </div>
                            </div>
                        </div>

                        <div class="sa-pass-tips">
                            <strong>Password tips</strong>
                            <ul>
                                <li>Use at least 6 characters.</li>
                                <li>Do not reuse the default password.</li>
                                <li>You stay signed in on this browser after changing.</li>
                            </ul>
                        </div>

                        <div class="sa-password-actions">
                            <button type="submit" class="btn btn-primary" onclick="return confirm('Change your Super Admin password?');">Update password</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php else: ?>
        <?php
            $activityQs = 'tab=activity' . ($activityFilter !== 'all' ? '&amp;filter=' . urlencode($activityFilter) : '');
        ?>
        <div class="settings-card">
            <div class="settings-card-head">
                <div class="settings-title-block">
                    <span class="settings-title-ico slate">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    </span>
                    <div>
                        <h2>Activity Log</h2>
                        <p>Sign-ins, password changes, settings, features, backups and license updates. Entries older than 90 days are removed.</p>
                    </div>
                </div>
                <span class="status-pill offline"><?php echo (int) $activityTotal; ?> records</span>
            </div>
            <div class="settings-section sa-activity-toolbar">
                <div class="sa-activity-filters">
                    <a href="settings.php?tab=activity" class="<?php echo $activityFilter === 'all' ? 'is-on' : ''; ?>">All</a>
                    <a href="settings.php?tab=activity&amp;filter=auth" class="<?php echo $activityFilter === 'auth' ? 'is-on' : ''; ?>">Sign-in</a>
                    <a href="settings.php?tab=activity&amp;filter=settings" class="<?php echo $activityFilter === 'settings' ? 'is-on' : ''; ?>">Settings</a>
                    <a href="settings.php?tab=activity&amp;filter=features" class="<?php echo $activityFilter === 'features' ? 'is-on' : ''; ?>">Features</a>
                </div>
                <div class="sa-activity-tools">
                    <input type="search" id="saActivitySearch" placeholder="Search this page...">
                    <a class="btn btn-outline btn-sm" href="settings.php?<?php echo $activityQs; ?>&amp;export=1">Export CSV</a>
                </div>
            </div>
            <?php if ($activityLogs): ?>
            <div class="sa-activity-wrap">
                <table class="sa-activity-table">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>Action</th>
                            <th>User</th>
                            <th>Details</th>
                            <th>IP</th>
                            <th>Browser</th>
                        </tr>
                    </thead>
                    <tbody id="saActivityBody">
                        <?php foreach ($activityLogs as $row):
                            [$label, $tone] = saActivityActionMeta((string) ($row['action'] ?? ''));
                            $ts = strtotime((string) ($row['created_at'] ?? ''));
                            $ua = (string) ($row['user_agent'] ?? '');
                            $uaShort = saShortUserAgent($ua);
                        ?>
                        <tr class="sa-activity-row">
                            <td>
                                <span class="sa-activity-date"><?php echo $ts ? date('d M Y', $ts) : '—'; ?></span>
                                <span class="sa-activity-time"><?php echo $ts ? date('h:i A', $ts) : ''; ?></span>
                            </td>
                            <td><span class="sa-act-badge tone-<?php echo e($tone); ?>"><?php echo e($label); ?></span></td>
                            <td><?php echo e($row['username'] ?: '—'); ?></td>
                            <td class="sa-activity-details"><?php echo e($row['details'] ?: '—'); ?></td>
                            <td><code><?php echo e($row['ip_address'] ?: '—'); ?></code></td>
                            <td class="sa-activity-ua" title="<?php echo e($ua); ?>"><?php echo e($uaShort !== '' ? $uaShort : '—'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($activityPages > 1): ?>
            <div class="sa-activity-pager">
                <?php if ($activityPage > 1): ?>
                <a href="settings.php?<?php echo $activityQs; ?>&amp;p=<?php echo $activityPage - 1; ?>">Previous</a>
                <?php endif; ?>
                <span>Page <?php echo (int) $activityPage; ?> of <?php echo (int) $activityPages; ?></span>
                <?php if ($activityPage < $activityPages): ?>
                <a href="settings.php?<?php echo $activityQs; ?>&amp;p=<?php echo $activityPage + 1; ?>">Next</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php else: ?>
            <div class="settings-section">
                <div class="settings-info">
                    <span class="si-ico">i</span>
                    <p>No activity yet. Sign-ins, password changes and settings saves will appear here.</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </section>
</div>
<script>
document.querySelectorAll('[data-password-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var wrap = btn.closest('.password-field');
        var input = wrap ? wrap.querySelector('input') : null;
        if (!input) return;
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.classList.toggle('is-visible', show);
    });
});
document.querySelectorAll('.sa-mode-opt input[type="radio"]').forEach(function (input) {
    input.addEventListener('change', function () {
        document.querySelectorAll('.sa-mode-opt').forEach(function (el) { el.classList.remove('is-on'); });
        if (input.checked && input.closest('.sa-mode-opt')) {
            input.closest('.sa-mode-opt').classList.add('is-on');
        }
    });
});
var activitySearch = document.getElementById('saActivitySearch');
if (activitySearch) {
    activitySearch.addEventListener('input', function () {
        var q = this.value.toLowerCase();
        document.querySelectorAll('.sa-activity-row').forEach(function (row) {
            row.style.display = row.textContent.toLowerCase().indexOf(q) >= 0 ? '' : 'none';
        });
    });
}
</script>
<?php require_once 'includes/layout_footer.php'; ?>
