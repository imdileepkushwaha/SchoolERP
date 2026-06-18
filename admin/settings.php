<?php
$page_title = "Settings";
require_once 'includes/init.php';
require_once '../includes/db_connect.php';
require_once 'includes/settings_helpers.php';

ensureSettingsSchema($pdo);

$activeTab = $_GET['tab'] ?? 'email';
$allowedTabs = ['email', 'sms', 'whatsapp', 'password'];
if (!in_array($activeTab, $allowedTabs, true)) {
    $activeTab = 'email';
}

$smtp = getSmtpSettings($pdo);
$sms = getSmsSettings($pdo);
$whatsapp = getWhatsAppSettings($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

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
        $_SESSION['success_msg'] = 'Email SMTP settings saved.';
        header('Location: settings.php?tab=email');
        exit;
    }

    if ($action === 'test_smtp') {
        $testCfg = [
            'host'        => trim($_POST['smtp_host'] ?? $smtp['host']),
            'port'        => trim($_POST['smtp_port'] ?? $smtp['port']),
            'encryption'  => trim($_POST['smtp_encryption'] ?? $smtp['encryption']),
            'username'    => trim($_POST['smtp_username'] ?? $smtp['username']),
            'password'    => trim($_POST['smtp_password'] ?? '') !== '' ? trim($_POST['smtp_password']) : $smtp['password'],
            'from_email'  => trim($_POST['smtp_from_email'] ?? $smtp['from_email']),
            'from_name'   => trim($_POST['smtp_from_name'] ?? $smtp['from_name']),
        ];
        $testTo = trim($_POST['test_email'] ?? $testCfg['from_email']);
        $err = '';
        $ok = sendSmtpEmail($testCfg, $testTo, 'EduDash SMTP Test', '<p>Your SMTP configuration is working correctly.</p><p>Sent at ' . date('Y-m-d H:i:s') . '</p>', $err);
        if ($ok) {
            $_SESSION['success_msg'] = 'Test email sent to ' . $testTo;
        } else {
            $_SESSION['error_msg'] = 'SMTP test failed: ' . $err;
        }
        header('Location: settings.php?tab=email');
        exit;
    }

    if ($action === 'save_sms') {
        saveSettingsGroup($pdo, [
            'sms_enabled'   => isset($_POST['sms_enabled']) ? '1' : '0',
            'sms_provider'  => $_POST['sms_provider'] ?? 'MSG91',
            'sms_api_key'   => $_POST['sms_api_key'] ?? '',
            'sms_sender_id' => $_POST['sms_sender_id'] ?? '',
            'sms_route'     => $_POST['sms_route'] ?? '4',
            'sms_api_url'   => $_POST['sms_api_url'] ?? '',
        ], ['sms_api_key']);
        $_SESSION['success_msg'] = 'SMS settings saved.';
        header('Location: settings.php?tab=sms');
        exit;
    }

    if ($action === 'test_sms') {
        $mobile = trim($_POST['test_mobile'] ?? '');
        $msg = 'EduDash SMS test — your MSG/SMS setup is working. ' . date('H:i:s');
        if (trim($_POST['sms_api_key'] ?? '') !== '') {
            setSetting($pdo, 'sms_api_key', trim($_POST['sms_api_key']));
        }
        foreach (['sms_provider', 'sms_sender_id', 'sms_route', 'sms_api_url'] as $k) {
            if (isset($_POST[$k]) && $_POST[$k] !== '') {
                setSetting($pdo, $k, trim($_POST[$k]));
            }
        }
        setSetting($pdo, 'sms_enabled', '1');
        $result = dispatchSms($pdo, $mobile, $msg);
        if ($result['ok']) {
            $_SESSION['success_msg'] = 'Test SMS sent to ' . $mobile;
        } else {
            $_SESSION['error_msg'] = $result['error'];
        }
        header('Location: settings.php?tab=sms');
        exit;
    }

    if ($action === 'save_whatsapp') {
        saveSettingsGroup($pdo, [
            'whatsapp_enabled'         => isset($_POST['whatsapp_enabled']) ? '1' : '0',
            'whatsapp_provider'        => $_POST['whatsapp_provider'] ?? 'Meta Cloud API',
            'whatsapp_api_token'       => $_POST['whatsapp_api_token'] ?? '',
            'whatsapp_phone_id'        => $_POST['whatsapp_phone_id'] ?? '',
            'whatsapp_business_number' => $_POST['whatsapp_business_number'] ?? '',
            'whatsapp_api_url'         => $_POST['whatsapp_api_url'] ?? '',
        ], ['whatsapp_api_token']);
        $_SESSION['success_msg'] = 'WhatsApp settings saved.';
        header('Location: settings.php?tab=whatsapp');
        exit;
    }

    if ($action === 'test_whatsapp') {
        $mobile = trim($_POST['test_mobile'] ?? '');
        $msg = 'EduDash WhatsApp test — your setup is working. ' . date('H:i:s');
        if (trim($_POST['whatsapp_api_token'] ?? '') !== '') {
            setSetting($pdo, 'whatsapp_api_token', trim($_POST['whatsapp_api_token']));
        }
        foreach (['whatsapp_provider', 'whatsapp_phone_id', 'whatsapp_business_number', 'whatsapp_api_url'] as $k) {
            if (isset($_POST[$k]) && $_POST[$k] !== '') {
                setSetting($pdo, $k, trim($_POST[$k]));
            }
        }
        setSetting($pdo, 'whatsapp_enabled', '1');
        $result = dispatchWhatsApp($pdo, $mobile, $msg);
        if ($result['ok']) {
            $_SESSION['success_msg'] = 'Test WhatsApp message sent to ' . $mobile;
        } else {
            $_SESSION['error_msg'] = $result['error'];
        }
        header('Location: settings.php?tab=whatsapp');
        exit;
    }

    if ($action === 'change_password') {
        $errors = changeAdminPassword(
            $pdo,
            (int) $_SESSION['admin_id'],
            $_POST['current_password'] ?? '',
            $_POST['new_password'] ?? '',
            $_POST['confirm_password'] ?? ''
        );
        if ($errors) {
            $_SESSION['error_msg'] = implode(' ', $errors);
        } else {
            $_SESSION['success_msg'] = 'Password changed successfully.';
        }
        header('Location: settings.php?tab=password');
        exit;
    }
}

require_once 'includes/header.php';
$smtp = getSmtpSettings($pdo);
$sms = getSmsSettings($pdo);
$whatsapp = getWhatsAppSettings($pdo);
?>
<div class="content-top-bar">
    <div class="content-top-main">
        <div class="content-top-icon icon-purple"><i class="fas fa-cog"></i></div>
        <div class="content-top-title">
            <h2>Settings</h2>
            <p class="content-top-breadcrumb">
                <a href="dashboard.php">Dashboard</a>
                <i class="fas fa-chevron-right"></i>
                <span>Settings</span>
            </p>
        </div>
    </div>
</div>

<div class="settings-layout">
    <aside class="settings-vtabs" role="tablist">
        <a href="settings.php?tab=email" class="settings-vtab <?php echo $activeTab === 'email' ? 'active' : ''; ?>">
            <span class="settings-vtab-icon"><i class="fas fa-envelope"></i></span>
            <span class="settings-vtab-text"><strong>Email SMTP</strong><small>Outgoing mail server</small></span>
        </a>
        <a href="settings.php?tab=sms" class="settings-vtab <?php echo $activeTab === 'sms' ? 'active' : ''; ?>">
            <span class="settings-vtab-icon"><i class="fas fa-sms"></i></span>
            <span class="settings-vtab-text"><strong>SMS / MSG</strong><small>MSG91 &amp; SMS gateway</small></span>
        </a>
        <a href="settings.php?tab=whatsapp" class="settings-vtab <?php echo $activeTab === 'whatsapp' ? 'active' : ''; ?>">
            <span class="settings-vtab-icon"><i class="fab fa-whatsapp"></i></span>
            <span class="settings-vtab-text"><strong>WhatsApp</strong><small>Business API setup</small></span>
        </a>
        <a href="settings.php?tab=password" class="settings-vtab <?php echo $activeTab === 'password' ? 'active' : ''; ?>">
            <span class="settings-vtab-icon"><i class="fas fa-lock"></i></span>
            <span class="settings-vtab-text"><strong>Change Password</strong><small>Admin account security</small></span>
        </a>
    </aside>

    <div class="settings-panels">
        <?php if ($activeTab === 'email'): ?>
        <div class="settings-panel active">
            <div class="settings-panel-head">
                <h3>Email SMTP Setup</h3>
                <p>Configure outgoing email for receipts, alerts, and notifications.</p>
            </div>
            <form method="POST" class="settings-form">
                <input type="hidden" name="action" value="save_smtp">
                <label class="settings-toggle">
                    <input type="checkbox" name="smtp_enabled" value="1" <?php echo $smtp['enabled'] === '1' ? 'checked' : ''; ?>>
                    <span>Enable SMTP email</span>
                </label>
                <div class="form-grid form-grid-2">
                    <div class="form-field"><label>SMTP Host</label><input type="text" name="smtp_host" class="form-input" value="<?php echo htmlspecialchars($smtp['host']); ?>" placeholder="smtp.gmail.com"></div>
                    <div class="form-field"><label>SMTP Port</label><input type="number" name="smtp_port" class="form-input" value="<?php echo htmlspecialchars($smtp['port']); ?>"></div>
                    <div class="form-field"><label>Encryption</label><select name="smtp_encryption" class="form-input form-select"><option value="tls" <?php echo $smtp['encryption'] === 'tls' ? 'selected' : ''; ?>>TLS</option><option value="ssl" <?php echo $smtp['encryption'] === 'ssl' ? 'selected' : ''; ?>>SSL</option><option value="none" <?php echo $smtp['encryption'] === 'none' ? 'selected' : ''; ?>>None</option></select></div>
                    <div class="form-field"><label>Username</label><input type="text" name="smtp_username" class="form-input" value="<?php echo htmlspecialchars($smtp['username']); ?>"></div>
                    <div class="form-field"><label>Password</label><input type="password" name="smtp_password" class="form-input" placeholder="<?php echo $smtp['password'] ? '•••••••• (leave blank to keep)' : 'SMTP password'; ?>" autocomplete="new-password"></div>
                    <div class="form-field"><label>From Email</label><input type="email" name="smtp_from_email" class="form-input" value="<?php echo htmlspecialchars($smtp['from_email']); ?>" placeholder="noreply@school.com"></div>
                    <div class="form-field"><label>From Name</label><input type="text" name="smtp_from_name" class="form-input" value="<?php echo htmlspecialchars($smtp['from_name']); ?>"></div>
                </div>
                <div class="settings-form-actions">
                    <button type="submit" class="btn-header-action btn-header-primary"><i class="fas fa-save"></i> Save SMTP Settings</button>
                </div>
            </form>
            <div class="settings-test-box">
                <h4><i class="fas fa-paper-plane"></i> Send Test Email</h4>
                <form method="POST" class="category-add-row">
                    <input type="hidden" name="action" value="test_smtp">
                    <input type="hidden" name="smtp_host" value="<?php echo htmlspecialchars($smtp['host']); ?>">
                    <input type="hidden" name="smtp_port" value="<?php echo htmlspecialchars($smtp['port']); ?>">
                    <input type="hidden" name="smtp_encryption" value="<?php echo htmlspecialchars($smtp['encryption']); ?>">
                    <input type="hidden" name="smtp_username" value="<?php echo htmlspecialchars($smtp['username']); ?>">
                    <input type="hidden" name="smtp_from_email" value="<?php echo htmlspecialchars($smtp['from_email']); ?>">
                    <input type="hidden" name="smtp_from_name" value="<?php echo htmlspecialchars($smtp['from_name']); ?>">
                    <div class="form-field form-field-grow"><label>Test recipient</label><input type="email" name="test_email" class="form-input" value="<?php echo htmlspecialchars($smtp['from_email']); ?>" required></div>
                    <div class="form-field category-add-btn-wrap"><label>&nbsp;</label><button type="submit" class="btn-header-action btn-header-outline category-add-btn">Send Test</button></div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($activeTab === 'sms'): ?>
        <div class="settings-panel active">
            <div class="settings-panel-head">
                <h3>SMS / MSG Setup</h3>
                <p>Connect MSG91 or a custom SMS gateway for fee and attendance alerts.</p>
            </div>
            <form method="POST" class="settings-form">
                <input type="hidden" name="action" value="save_sms">
                <label class="settings-toggle">
                    <input type="checkbox" name="sms_enabled" value="1" <?php echo $sms['enabled'] === '1' ? 'checked' : ''; ?>>
                    <span>Enable SMS notifications</span>
                </label>
                <div class="form-grid form-grid-2">
                    <div class="form-field"><label>Provider</label><select name="sms_provider" class="form-input form-select"><option value="MSG91" <?php echo $sms['provider'] === 'MSG91' ? 'selected' : ''; ?>>MSG91</option><option value="Custom" <?php echo $sms['provider'] === 'Custom' ? 'selected' : ''; ?>>Custom</option></select></div>
                    <div class="form-field"><label>Auth Key / API Key</label><input type="password" name="sms_api_key" class="form-input" placeholder="<?php echo $sms['api_key'] ? '••••••••' : 'Your MSG91 auth key'; ?>"></div>
                    <div class="form-field"><label>Sender ID</label><input type="text" name="sms_sender_id" class="form-input" value="<?php echo htmlspecialchars($sms['sender_id']); ?>" maxlength="6" placeholder="EDUDSH"></div>
                    <div class="form-field"><label>Route</label><input type="text" name="sms_route" class="form-input" value="<?php echo htmlspecialchars($sms['route']); ?>" placeholder="4"></div>
                    <div class="form-field form-field-full"><label>Custom API URL (optional)</label><input type="text" name="sms_api_url" class="form-input" value="<?php echo htmlspecialchars($sms['api_url']); ?>" placeholder="https://api.example.com/sms?mobile={mobile}&msg={message}&key={api_key}"></div>
                </div>
                <div class="settings-form-actions">
                    <button type="submit" class="btn-header-action btn-header-primary"><i class="fas fa-save"></i> Save SMS Settings</button>
                </div>
            </form>
            <div class="settings-test-box">
                <h4><i class="fas fa-mobile-alt"></i> Send Test SMS</h4>
                <form method="POST" class="category-add-row">
                    <input type="hidden" name="action" value="test_sms">
                    <input type="hidden" name="sms_provider" value="<?php echo htmlspecialchars($sms['provider']); ?>">
                    <input type="hidden" name="sms_sender_id" value="<?php echo htmlspecialchars($sms['sender_id']); ?>">
                    <input type="hidden" name="sms_route" value="<?php echo htmlspecialchars($sms['route']); ?>">
                    <input type="hidden" name="sms_api_url" value="<?php echo htmlspecialchars($sms['api_url']); ?>">
                    <div class="form-field form-field-grow"><label>Mobile number</label><input type="text" name="test_mobile" class="form-input" placeholder="9876543210" required></div>
                    <div class="form-field category-add-btn-wrap"><label>&nbsp;</label><button type="submit" class="btn-header-action btn-header-outline category-add-btn">Send Test</button></div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($activeTab === 'whatsapp'): ?>
        <div class="settings-panel active">
            <div class="settings-panel-head">
                <h3>WhatsApp Setup</h3>
                <p>Meta Cloud API or custom webhook for WhatsApp Business messages.</p>
            </div>
            <form method="POST" class="settings-form">
                <input type="hidden" name="action" value="save_whatsapp">
                <label class="settings-toggle">
                    <input type="checkbox" name="whatsapp_enabled" value="1" <?php echo $whatsapp['enabled'] === '1' ? 'checked' : ''; ?>>
                    <span>Enable WhatsApp notifications</span>
                </label>
                <div class="form-grid form-grid-2">
                    <div class="form-field"><label>Provider</label><select name="whatsapp_provider" class="form-input form-select"><option value="Meta Cloud API" <?php echo $whatsapp['provider'] === 'Meta Cloud API' ? 'selected' : ''; ?>>Meta Cloud API</option><option value="Custom" <?php echo $whatsapp['provider'] === 'Custom' ? 'selected' : ''; ?>>Custom</option></select></div>
                    <div class="form-field"><label>Business Number</label><input type="text" name="whatsapp_business_number" class="form-input" value="<?php echo htmlspecialchars($whatsapp['business_number']); ?>" placeholder="+91XXXXXXXXXX"></div>
                    <div class="form-field"><label>Phone Number ID</label><input type="text" name="whatsapp_phone_id" class="form-input" value="<?php echo htmlspecialchars($whatsapp['phone_id']); ?>" placeholder="Meta Phone Number ID"></div>
                    <div class="form-field"><label>Permanent Access Token</label><input type="password" name="whatsapp_api_token" class="form-input" placeholder="<?php echo $whatsapp['api_token'] ? '••••••••' : 'WhatsApp API token'; ?>"></div>
                    <div class="form-field form-field-full"><label>Custom API URL (optional)</label><input type="text" name="whatsapp_api_url" class="form-input" value="<?php echo htmlspecialchars($whatsapp['api_url']); ?>" placeholder="https://your-api.com/wa?to={mobile}&text={message}&token={token}"></div>
                </div>
                <div class="settings-form-actions">
                    <button type="submit" class="btn-header-action btn-header-primary"><i class="fas fa-save"></i> Save WhatsApp Settings</button>
                </div>
            </form>
            <div class="settings-test-box">
                <h4><i class="fab fa-whatsapp"></i> Send Test Message</h4>
                <form method="POST" class="category-add-row">
                    <input type="hidden" name="action" value="test_whatsapp">
                    <input type="hidden" name="whatsapp_provider" value="<?php echo htmlspecialchars($whatsapp['provider']); ?>">
                    <input type="hidden" name="whatsapp_phone_id" value="<?php echo htmlspecialchars($whatsapp['phone_id']); ?>">
                    <input type="hidden" name="whatsapp_business_number" value="<?php echo htmlspecialchars($whatsapp['business_number']); ?>">
                    <input type="hidden" name="whatsapp_api_url" value="<?php echo htmlspecialchars($whatsapp['api_url']); ?>">
                    <div class="form-field form-field-grow"><label>Mobile (with country code)</label><input type="text" name="test_mobile" class="form-input" placeholder="919876543210" required></div>
                    <div class="form-field category-add-btn-wrap"><label>&nbsp;</label><button type="submit" class="btn-header-action btn-header-outline category-add-btn">Send Test</button></div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($activeTab === 'password'): ?>
        <div class="settings-panel active">
            <div class="settings-panel-head">
                <h3>Change Password</h3>
                <p>Update your admin login password. You must enter your current password to confirm.</p>
            </div>
            <form method="POST" class="settings-form settings-form-narrow">
                <input type="hidden" name="action" value="change_password">
                <div class="form-grid form-grid-1">
                    <div class="form-field">
                        <label><i class="fas fa-lock"></i> Current Password</label>
                        <input type="password" name="current_password" class="form-input" required autocomplete="current-password">
                    </div>
                    <div class="form-field">
                        <label><i class="fas fa-key"></i> New Password</label>
                        <input type="password" name="new_password" class="form-input" required minlength="6" autocomplete="new-password">
                        <span class="field-hint">Minimum 6 characters</span>
                    </div>
                    <div class="form-field">
                        <label><i class="fas fa-check-double"></i> Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-input" required minlength="6" autocomplete="new-password">
                    </div>
                </div>
                <div class="settings-form-actions">
                    <button type="submit" class="btn-header-action btn-header-primary" onclick="return confirm('Change your admin password?');"><i class="fas fa-shield-alt"></i> Update Password</button>
                </div>
            </form>
            <div class="settings-info-box">
                <i class="fas fa-info-circle"></i>
                <div>
                    <strong>Logged in as:</strong> <?php echo htmlspecialchars($_SESSION['admin_username']); ?><br>
                    After changing password, you will stay logged in on this device. Use the new password on your next login.
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
