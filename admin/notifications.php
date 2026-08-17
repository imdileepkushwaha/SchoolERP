<?php
$page_title = "SMS & WhatsApp Alerts";
require_once 'includes/init.php';
require_once '../includes/db_connect.php';
require_once 'includes/erp_helpers.php';
require_once 'includes/settings_helpers.php';

ensureErpSchema($pdo);
ensureSettingsSchema($pdo);
require_once 'includes/module_helpers.php';
assertSchoolLicenseActive($pdo);
requireModule($pdo, 'notifications');
$class_options = getClassOptions($pdo);
$smsCfg = getSmsSettings($pdo);
$waCfg = getWhatsAppSettings($pdo);
$smtpCfg = getSmtpSettings($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'fee_reminder') {
        $sent = sendFeeReminders($pdo, trim($_POST['class'] ?? ''));
        $_SESSION['success_msg'] = "Fee reminders queued/sent for $sent student(s).";
    } elseif ($action === 'attendance_alert') {
        $date = $_POST['alert_date'] ?? date('Y-m-d');
        $sent = sendAttendanceAlerts($pdo, $date, trim($_POST['class'] ?? ''));
        $_SESSION['success_msg'] = "Attendance alerts sent for $sent student(s).";
    } elseif ($action === 'birthday_wish') {
        $date = $_POST['wish_date'] ?? date('Y-m-d');
        $audience = $_POST['audience'] ?? 'all';
        if (!in_array($audience, ['all', 'students', 'teachers'], true)) {
            $audience = 'all';
        }
        $classFilter = ($audience === 'teachers') ? '' : trim($_POST['class'] ?? '');
        $sent = sendBirthdayWishes($pdo, $date, $audience, $classFilter);
        if ($sent > 0) {
            $_SESSION['success_msg'] = "Birthday wishes sent to $sent " . ($sent === 1 ? 'person' : 'people') . '.';
        } else {
            $_SESSION['error_msg'] = 'No birthday wishes sent. No matching contacts with a mobile or email.';
        }
    } elseif ($action === 'custom_message') {
        $channel = in_array($_POST['channel'] ?? '', ['SMS', 'WhatsApp', 'Email'], true) ? $_POST['channel'] : 'SMS';
        $mobile = trim($_POST['recipient'] ?? '');
        $msg = trim($_POST['message'] ?? '');
        if ($mobile && $msg) {
            queueNotification($pdo, $channel, $mobile, $msg, null, 'custom');
            $_SESSION['success_msg'] = 'Message sent via ' . $channel . '.';
        } else {
            $_SESSION['error_msg'] = 'Recipient and message are required.';
        }
    } elseif ($action === 'save_templates') {
        saveNotificationTemplates($pdo, [
            'fee_reminder' => $_POST['tpl_fee_reminder'] ?? '',
            'attendance_alert' => $_POST['tpl_attendance_alert'] ?? '',
            'birthday_wish' => $_POST['tpl_birthday_wish'] ?? '',
        ]);
        $_SESSION['success_msg'] = 'Message templates saved.';
    }
    header('Location: notifications.php');
    exit;
}

$wishDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_GET['wish_date'] ?? '')) ? $_GET['wish_date'] : date('Y-m-d');
$wishAudience = $_GET['audience'] ?? 'all';
if (!in_array($wishAudience, ['all', 'students', 'teachers'], true)) {
    $wishAudience = 'all';
}
$wishClass = ($wishAudience === 'teachers') ? '' : trim((string) ($_GET['class'] ?? ''));
$birthdayPeople = getBirthdayPeople($pdo, $wishDate, $wishAudience, $wishClass);

require_once 'includes/header.php';

$logs = $pdo->query("SELECT * FROM notification_logs ORDER BY id DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);

$statTotal = count($logs);
$statSent = 0;
$statFailed = 0;
$statSms = 0;
$statWa = 0;
foreach ($logs as $row) {
    if ($row['status'] === 'Sent') {
        $statSent++;
    } elseif ($row['status'] === 'Failed') {
        $statFailed++;
    }
    if ($row['channel'] === 'SMS') {
        $statSms++;
    } elseif ($row['channel'] === 'WhatsApp') {
        $statWa++;
    }
}

function notifyTemplateLabel($type) {
    $map = [
        'fee_reminder'       => 'Fee Reminder',
        'attendance_alert'   => 'Attendance Alert',
        'birthday_wish'      => 'Birthday Wish',
        'custom'             => 'Custom',
    ];
    return $map[$type] ?? ($type ? ucfirst(str_replace('_', ' ', $type)) : 'General');
}

function notifyChannelClass($channel) {
    $map = ['SMS' => 'notify-badge-sms', 'WhatsApp' => 'notify-badge-wa', 'Email' => 'notify-badge-email'];
    return $map[$channel] ?? 'notify-badge-default';
}

function notifyStatusClass($status) {
    $map = ['Sent' => 'badge-active', 'Failed' => 'badge-inactive', 'Queued' => 'notify-badge-queued'];
    return $map[$status] ?? 'notify-badge-default';
}
?>
<div class="content-top-bar">
    <div class="content-top-main">
        <div class="content-top-icon icon-teal"><i class="fas fa-bell"></i></div>
        <div class="content-top-title">
            <h2>Notifications</h2>
            <p class="content-top-breadcrumb">
                <a href="dashboard.php">Dashboard</a>
                <i class="fas fa-chevron-right"></i>
                <span>SMS &amp; WhatsApp</span>
            </p>
        </div>
    </div>
</div>

<?php $notifTemplates = getNotificationTemplates($pdo); ?>
<div class="form-section-card section-mb">
    <div class="section-card-header">
        <div class="section-card-icon section-icon-school"><i class="fas fa-edit"></i></div>
        <div>
            <h4>Message templates</h4>
            <p>Placeholders: {name}, {class}, {balance}, {date}, {status}, {school}</p>
        </div>
    </div>
    <form method="POST">
        <input type="hidden" name="action" value="save_templates">
        <div class="form-grid form-grid-1 form-grid-spaced">
            <div class="form-field"><label>Fee reminder</label><textarea name="tpl_fee_reminder" class="form-input form-textarea" rows="2"><?php echo htmlspecialchars($notifTemplates['fee_reminder']); ?></textarea></div>
            <div class="form-field"><label>Attendance alert</label><textarea name="tpl_attendance_alert" class="form-input form-textarea" rows="2"><?php echo htmlspecialchars($notifTemplates['attendance_alert']); ?></textarea></div>
            <div class="form-field"><label>Birthday wish</label><textarea name="tpl_birthday_wish" class="form-input form-textarea" rows="2"><?php echo htmlspecialchars($notifTemplates['birthday_wish']); ?></textarea></div>
        </div>
        <div class="form-actions-end"><button type="submit" class="btn-header-action btn-header-primary"><i class="fas fa-save"></i> Save Templates</button></div>
    </form>
</div>

<div class="notify-stats-grid">
    <div class="notify-stat-card">
        <div class="notify-stat-icon notify-stat-icon-total"><i class="fas fa-paper-plane"></i></div>
        <div class="notify-stat-body">
            <span>Recent Messages</span>
            <strong><?php echo $statTotal; ?></strong>
            <small>Last 100 logs</small>
        </div>
    </div>
    <div class="notify-stat-card">
        <div class="notify-stat-icon notify-stat-icon-sent"><i class="fas fa-check-circle"></i></div>
        <div class="notify-stat-body">
            <span>Delivered</span>
            <strong><?php echo $statSent; ?></strong>
            <small>Status: Sent</small>
        </div>
    </div>
    <div class="notify-stat-card">
        <div class="notify-stat-icon notify-stat-icon-sms"><i class="fas fa-sms"></i></div>
        <div class="notify-stat-body">
            <span>SMS</span>
            <strong><?php echo $statSms; ?></strong>
            <small><?php echo $smsCfg['enabled'] === '1' ? 'Gateway active' : 'Not configured'; ?></small>
        </div>
    </div>
    <div class="notify-stat-card">
        <div class="notify-stat-icon notify-stat-icon-wa"><i class="fab fa-whatsapp"></i></div>
        <div class="notify-stat-body">
            <span>WhatsApp</span>
            <strong><?php echo $statWa; ?></strong>
            <small><?php echo $waCfg['enabled'] === '1' ? 'Gateway active' : 'Not configured'; ?></small>
        </div>
    </div>
</div>

<?php
$gwOff = [];
if (($smtpCfg['enabled'] ?? '0') !== '1') {
    $gwOff[] = 'Email (SMTP)';
}
if (($smsCfg['enabled'] ?? '0') !== '1') {
    $gwOff[] = 'SMS';
}
if (($waCfg['enabled'] ?? '0') !== '1') {
    $gwOff[] = 'WhatsApp';
}
?>
<?php if ($gwOff): ?>
<div class="notify-warn-banner">
    <div class="notify-info-icon"><i class="fas fa-exclamation-triangle"></i></div>
    <div class="notify-info-text">
        <strong>Gateway not enabled in SuperAdmin</strong>
        <p><?php echo htmlspecialchars(implode(', ', $gwOff)); ?> <?php echo count($gwOff) === 1 ? 'is' : 'are'; ?> off. Messages will be saved in the log only until SuperAdmin turns the gateway on.</p>
    </div>
</div>
<?php endif; ?>

<div class="notify-info-banner">
    <div class="notify-info-icon"><i class="fas fa-info-circle"></i></div>
    <div class="notify-info-text">
        <strong>Before sending bulk alerts</strong>
        <p>SMS, WhatsApp and Email gateways are configured by SuperAdmin. Enabled gateways send live messages; otherwise entries are logged only.</p>
    </div>
    <div class="notify-gateway-chips">
        <span class="notify-gateway-chip <?php echo $smsCfg['enabled'] === '1' ? 'is-on' : 'is-off'; ?>"><i class="fas fa-sms"></i> SMS</span>
        <span class="notify-gateway-chip <?php echo $waCfg['enabled'] === '1' ? 'is-on' : 'is-off'; ?>"><i class="fab fa-whatsapp"></i> WhatsApp</span>
        <span class="notify-gateway-chip <?php echo $smtpCfg['enabled'] === '1' ? 'is-on' : 'is-off'; ?>"><i class="fas fa-envelope"></i> Email</span>
    </div>
</div>

<div class="notify-actions-grid">
    <div class="form-section-card notify-action-card">
        <div class="section-card-header">
            <div class="section-card-icon section-icon-bank"><i class="fas fa-file-invoice-dollar"></i></div>
            <div>
                <h4>Fee Due Reminders</h4>
                <p>Notify parents about outstanding fee balance</p>
            </div>
        </div>
        <form method="POST" class="notify-action-form">
            <input type="hidden" name="action" value="fee_reminder">
            <div class="form-field">
                <label><i class="fas fa-school"></i> Class filter</label>
                <select name="class" class="form-input form-select">
                    <option value="">All classes</option>
                    <?php foreach ($class_options as $c): ?>
                    <option value="<?php echo htmlspecialchars($c); ?>"><?php echo htmlspecialchars($c); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <ul class="notify-action-notes">
                <li><i class="fas fa-check"></i> Sends via SMS &amp; WhatsApp</li>
                <li><i class="fas fa-check"></i> Only students with due balance</li>
            </ul>
            <button type="submit" class="btn-header-action btn-header-primary notify-action-btn" onclick="return confirm('Send fee reminders to eligible parents?');">
                <i class="fas fa-paper-plane"></i> Send Fee Reminders
            </button>
        </form>
    </div>

    <div class="form-section-card notify-action-card">
        <div class="section-card-header">
            <div class="section-card-icon section-icon-medical"><i class="far fa-calendar-times"></i></div>
            <div>
                <h4>Attendance Alerts</h4>
                <p>Alert parents when student is absent or late</p>
            </div>
        </div>
        <form method="POST" class="notify-action-form">
            <input type="hidden" name="action" value="attendance_alert">
            <div class="notify-inline-fields">
                <div class="form-field">
                    <label><i class="fas fa-calendar-day"></i> Date</label>
                    <input type="date" name="alert_date" class="form-input" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-field">
                    <label><i class="fas fa-school"></i> Class</label>
                    <select name="class" class="form-input form-select">
                        <option value="">All classes</option>
                        <?php foreach ($class_options as $c): ?>
                        <option value="<?php echo htmlspecialchars($c); ?>"><?php echo htmlspecialchars($c); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <ul class="notify-action-notes">
                <li><i class="fas fa-check"></i> Absent &amp; Late status included</li>
                <li><i class="fas fa-check"></i> Uses marked attendance records</li>
            </ul>
            <button type="submit" class="btn-header-action btn-header-primary notify-action-btn" onclick="return confirm('Send attendance alerts for this date?');">
                <i class="fas fa-bell"></i> Send Attendance Alerts
            </button>
        </form>
    </div>

    <div class="form-section-card notify-action-card notify-action-card-wide notify-bday-card">
        <div class="section-card-header notify-bday-header">
            <div class="section-card-icon section-icon-bday"><i class="fas fa-cake-candles"></i></div>
            <div>
                <h4>Birthday Wishes</h4>
                <p>Send warm wishes to students and teachers on their birthday</p>
            </div>
            <span class="notify-bday-count"><?php echo count($birthdayPeople); ?> on <?php echo date('d M', strtotime($wishDate) ?: time()); ?></span>
        </div>
        <form method="POST" class="notify-action-form notify-bday-form" id="notifyBirthdayForm">
            <input type="hidden" name="action" value="birthday_wish">
            <div class="notify-bday-filters">
                <div class="form-field">
                    <label><i class="fas fa-calendar-day"></i> Date</label>
                    <input type="date" name="wish_date" id="wishDate" class="form-input" value="<?php echo htmlspecialchars($wishDate); ?>">
                </div>
                <div class="form-field">
                    <label><i class="fas fa-users"></i> Send to</label>
                    <select name="audience" id="wishAudience" class="form-input form-select">
                        <option value="all" <?php echo $wishAudience === 'all' ? 'selected' : ''; ?>>Students &amp; Teachers</option>
                        <option value="students" <?php echo $wishAudience === 'students' ? 'selected' : ''; ?>>Students only</option>
                        <option value="teachers" <?php echo $wishAudience === 'teachers' ? 'selected' : ''; ?>>Teachers only</option>
                    </select>
                </div>
                <div class="form-field" id="wishClassField">
                    <label><i class="fas fa-school"></i> Class</label>
                    <select name="class" id="wishClass" class="form-input form-select">
                        <option value="">All classes</option>
                        <?php foreach ($class_options as $c): ?>
                        <option value="<?php echo htmlspecialchars($c); ?>" <?php echo $wishClass === $c ? 'selected' : ''; ?>><?php echo htmlspecialchars($c); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-header-action btn-header-primary notify-action-btn" onclick="return confirm('Send birthday wishes to the people listed?');" <?php echo $birthdayPeople ? '' : 'disabled'; ?>>
                    <i class="fas fa-gift"></i> Send Wishes
                </button>
            </div>
            <div class="notify-bday-preview">
                <div class="notify-bday-preview-head">
                    <strong><i class="fas fa-cake-candles"></i> Birthdays on <?php echo date('d M', strtotime($wishDate) ?: time()); ?></strong>
                    <span><?php echo count($birthdayPeople); ?> found</span>
                </div>
                <?php if ($birthdayPeople): ?>
                <div class="notify-bday-grid">
                    <?php foreach ($birthdayPeople as $person):
                        $meta = $person['type'] === 'student'
                            ? trim($person['class'] . ($person['section'] !== '' ? '-' . $person['section'] : ''))
                            : 'Staff';
                        $contact = $person['mobile'] !== '' ? $person['mobile'] : ($person['email'] !== '' ? $person['email'] : 'No contact');
                    ?>
                    <article class="notify-bday-item">
                        <span class="notify-bday-avatar is-<?php echo htmlspecialchars($person['type']); ?>">
                            <i class="fas <?php echo $person['type'] === 'teacher' ? 'fa-chalkboard-user' : 'fa-user-graduate'; ?>"></i>
                        </span>
                        <div class="notify-bday-copy">
                            <strong><?php echo htmlspecialchars($person['name']); ?></strong>
                            <small><?php echo htmlspecialchars($meta !== '' ? $meta : '—'); ?></small>
                            <em><?php echo htmlspecialchars($contact); ?></em>
                        </div>
                        <span class="notify-bday-role is-<?php echo htmlspecialchars($person['type']); ?>"><?php echo $person['type'] === 'teacher' ? 'Teacher' : 'Student'; ?></span>
                    </article>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="notify-bday-empty">
                    <i class="fas fa-cake-candles"></i>
                    <p>No student or teacher birthdays on <?php echo date('d M', strtotime($wishDate) ?: time()); ?>.</p>
                </div>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="form-section-card notify-action-card notify-action-card-wide">
        <div class="section-card-header">
            <div class="section-card-icon section-icon-desc"><i class="fas fa-comment-dots"></i></div>
            <div>
                <h4>Custom Message</h4>
                <p>Send a one-off SMS, WhatsApp, or email to any recipient</p>
            </div>
        </div>
        <form method="POST" class="notify-custom-form">
            <input type="hidden" name="action" value="custom_message">
            <div class="notify-custom-grid">
                <div class="form-field">
                    <label><i class="fas fa-broadcast-tower"></i> Channel</label>
                    <select name="channel" class="form-input form-select" id="notifyChannel">
                        <option value="SMS">SMS</option>
                        <option value="WhatsApp">WhatsApp</option>
                        <option value="Email">Email</option>
                    </select>
                </div>
                <div class="form-field">
                    <label><i class="fas fa-phone"></i> <span id="notifyRecipientLabel">Mobile number</span></label>
                    <input type="text" name="recipient" class="form-input" placeholder="9876543210" required>
                </div>
                <div class="form-field notify-custom-message">
                    <label><i class="fas fa-align-left"></i> Message</label>
                    <textarea name="message" class="form-input form-textarea" rows="4" placeholder="Type your message here..." required maxlength="500"></textarea>
                    <span class="field-hint">Max 500 characters recommended for SMS</span>
                </div>
            </div>
            <div class="notify-custom-keys">
                <span class="notify-key" data-text="Dear Parent, ">Dear Parent</span>
                <span class="notify-key" data-text="Fee reminder: ">Fee reminder</span>
                <span class="notify-key" data-text="Attendance update: ">Attendance</span>
                <span class="notify-key" data-text="Happy Birthday! ">Birthday</span>
                <span class="notify-key" data-text=" - EduDash School">Sign-off</span>
            </div>
            <button type="submit" class="btn-header-action btn-header-outline notify-action-btn">
                <i class="fas fa-paper-plane"></i> Send Message
            </button>
        </form>
    </div>
</div>

<div class="table-container notify-log-table">
    <div class="table-toolbar">
        <div class="toolbar-left">
            <strong><i class="fas fa-history"></i> Notification Log</strong>
            <?php if ($statFailed > 0): ?>
            <span class="notify-failed-pill"><?php echo $statFailed; ?> failed</span>
            <?php endif; ?>
        </div>
        <div class="toolbar-right">
            <div class="toolbar-search notify-log-search">
                <i class="fas fa-search"></i>
                <input type="text" id="notifyLogSearch" placeholder="Search recipient or message...">
            </div>
        </div>
    </div>
    <div class="table-wrapper">
        <?php if ($logs): ?>
        <table>
            <thead>
                <tr>
                    <th>Date &amp; Time</th>
                    <th>Channel</th>
                    <th>Recipient</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Message</th>
                </tr>
            </thead>
            <tbody id="notifyLogBody">
                <?php foreach ($logs as $l):
                    $ts = strtotime($l['created_at']);
                ?>
                <tr class="notify-log-row">
                    <td>
                        <span class="notify-log-date"><?php echo $ts ? date('d M Y', $ts) : '-'; ?></span>
                        <span class="notify-log-time"><?php echo $ts ? date('h:i A', $ts) : ''; ?></span>
                    </td>
                    <td>
                        <span class="notify-channel-badge <?php echo notifyChannelClass($l['channel']); ?>">
                            <?php if ($l['channel'] === 'WhatsApp'): ?><i class="fab fa-whatsapp"></i><?php elseif ($l['channel'] === 'Email'): ?><i class="fas fa-envelope"></i><?php else: ?><i class="fas fa-sms"></i><?php endif; ?>
                            <?php echo htmlspecialchars($l['channel']); ?>
                        </span>
                    </td>
                    <td><code class="notify-recipient"><?php echo htmlspecialchars($l['recipient']); ?></code></td>
                    <td><?php echo htmlspecialchars(notifyTemplateLabel($l['template_type'])); ?></td>
                    <td><span class="status-badge <?php echo notifyStatusClass($l['status']); ?>"><?php echo htmlspecialchars($l['status']); ?></span></td>
                    <td class="notify-msg-cell" title="<?php echo htmlspecialchars($l['message']); ?>"><?php echo htmlspecialchars(mb_strimwidth($l['message'], 0, 90, '…')); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state empty-state-md">
            <i class="fas fa-bell-slash empty-state-icon"></i>
            <h3>No notifications yet</h3>
            <p>Send your first alert using the cards above.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    var channel = document.getElementById('notifyChannel');
    var label = document.getElementById('notifyRecipientLabel');
    if (channel && label) {
        channel.addEventListener('change', function () {
            label.textContent = this.value === 'Email' ? 'Email address' : 'Mobile number';
        });
    }

    document.querySelectorAll('.notify-key').forEach(function (key) {
        key.addEventListener('click', function () {
            var ta = document.querySelector('.notify-custom-form textarea');
            if (ta) {
                ta.value += this.getAttribute('data-text') || '';
                ta.focus();
            }
        });
    });

    var search = document.getElementById('notifyLogSearch');
    if (search) {
        search.addEventListener('input', function () {
            var q = this.value.toLowerCase();
            document.querySelectorAll('.notify-log-row').forEach(function (row) {
                row.style.display = row.textContent.toLowerCase().indexOf(q) >= 0 ? '' : 'none';
            });
        });
    }

    var wishDate = document.getElementById('wishDate');
    var wishAudience = document.getElementById('wishAudience');
    var wishClass = document.getElementById('wishClass');
    var wishClassField = document.getElementById('wishClassField');

    function syncWishClassField() {
        if (!wishAudience || !wishClassField) return;
        var hide = wishAudience.value === 'teachers';
        var filters = wishClassField.closest('.notify-bday-filters');
        wishClassField.style.display = hide ? 'none' : '';
        if (filters) filters.classList.toggle('is-no-class', hide);
        if (hide && wishClass) wishClass.value = '';
    }

    function reloadBirthdayPreview() {
        if (!wishDate || !wishAudience) return;
        var params = new URLSearchParams();
        params.set('wish_date', wishDate.value || '');
        params.set('audience', wishAudience.value || 'all');
        if (wishAudience.value !== 'teachers' && wishClass && wishClass.value) {
            params.set('class', wishClass.value);
        }
        window.location.href = 'notifications.php?' + params.toString();
    }

    syncWishClassField();
    if (wishAudience) {
        wishAudience.addEventListener('change', function () {
            syncWishClassField();
            reloadBirthdayPreview();
        });
    }
    if (wishDate) wishDate.addEventListener('change', reloadBirthdayPreview);
    if (wishClass) wishClass.addEventListener('change', reloadBirthdayPreview);
})();
</script>
<?php require_once 'includes/footer.php'; ?>
