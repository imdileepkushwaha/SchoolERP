<?php
// superadmin/includes/helpers.php

require_once __DIR__ . '/../../admin/includes/settings_helpers.php';
require_once __DIR__ . '/../../admin/includes/module_helpers.php';

function e($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function saFlash(string $type, string $message): void {
    $_SESSION['sa_flash'] = ['type' => $type, 'message' => $message];
}

function saGetFlash(): ?array {
    $flash = $_SESSION['sa_flash'] ?? null;
    unset($_SESSION['sa_flash']);
    return is_array($flash) ? $flash : null;
}

function saAuthUser($pdo, string $username, string $password): ?array {
    ensureSuperAdminSchema($pdo);
    $stmt = $pdo->prepare('SELECT * FROM superadmin_users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($password, $user['password'])) {
        return $user;
    }
    return null;
}

function saThisSchool($pdo): array {
    ensureSuperAdminSchema($pdo);
    $school = getCurrentSaSchool($pdo);
    if ($school) {
        if (empty($school['is_current'])) {
            $pdo->exec('UPDATE sa_schools SET is_current = 0');
            $pdo->prepare('UPDATE sa_schools SET is_current = 1 WHERE id = ?')->execute([(int) $school['id']]);
            $school['is_current'] = 1;
        }
        return $school;
    }

    $name = 'EduDash School';
    try {
        $fromSettings = trim((string) getSetting($pdo, 'school_name', ''));
        if ($fromSettings !== '') {
            $name = $fromSettings;
        }
    } catch (Throwable $e) {
    }

    $pdo->prepare(
        'INSERT INTO sa_schools (name, code, plan, status, starts_at, is_current) VALUES (?,?,?,?,CURDATE(),1)'
    )->execute([$name, 'SCHOOL01', 'Full', 'Active']);
    $id = (int) $pdo->lastInsertId();
    saveSchoolModules($pdo, $id, getDefaultErpModuleKeys());
    return getCurrentSaSchool($pdo) ?: [
        'id' => $id,
        'name' => $name,
        'plan' => 'Full',
        'status' => 'Active',
        'is_current' => 1,
    ];
}

function saErpPresets(): array {
    $core = getCoreErpModuleKeys();
    $standard = array_values(array_unique(array_merge($core, [
        'certificates', 'student_portal', 'teacher_portal', 'website', 'notifications',
    ])));
    $campus = array_values(array_unique(array_merge($standard, ['transport', 'hostel'])));
    $full = getDefaultErpModuleKeys();

    return [
        'core' => [
            'label' => 'Core School',
            'description' => 'Students, academic, attendance, fees, exams and teachers only.',
            'modules' => $core,
            'tone' => 'slate',
            'tags' => ['School', 'No add-ons'],
            'ico' => '<path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>',
        ],
        'standard' => [
            'label' => 'Standard',
            'description' => 'Core plus certificates, portals, website and SMS.',
            'modules' => $standard,
            'tone' => 'sky',
            'tags' => ['Portals', 'Certificates', 'SMS'],
            'ico' => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
        ],
        'campus' => [
            'label' => 'Campus',
            'description' => 'Standard plus transport and hostel for boarding schools.',
            'modules' => $campus,
            'tone' => 'violet',
            'tags' => ['Transport', 'Hostel'],
            'ico' => '<path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><path d="M9 22V12h6v10"/>',
        ],
        'full' => [
            'label' => 'Full ERP',
            'description' => 'Every module including library, transport and hostel.',
            'modules' => $full,
            'tone' => 'rose',
            'tags' => ['Library', 'All modules'],
            'ico' => '<path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/>',
        ],
    ];
}

function saPlanLabel(string $plan): string {
    $map = [
        'core' => 'Core',
        'standard' => 'Standard',
        'campus' => 'Campus',
        'full' => 'Full',
        'custom' => 'Custom',
    ];
    $key = strtolower($plan);
    return $map[$key] ?? ($plan !== '' ? $plan : 'Custom');
}

function saNormalizeModuleKeys(array $keys): array {
    $catalog = getErpModuleCatalog();
    $clean = [];
    foreach ($keys as $key) {
        $key = trim((string) $key);
        if ($key !== '' && isset($catalog[$key])) {
            $clean[$key] = $key;
        }
    }
    $clean = array_values($clean);
    sort($clean, SORT_STRING);
    return $clean;
}

function saMatchPresetKey(array $moduleKeys): ?string {
    $have = saNormalizeModuleKeys($moduleKeys);
    foreach (saErpPresets() as $key => $preset) {
        if (saNormalizeModuleKeys($preset['modules']) === $have) {
            return $key;
        }
    }
    return null;
}

function saClosestPresetKey(array $moduleKeys): ?string {
    $have = saNormalizeModuleKeys($moduleKeys);
    if (!$have) {
        return null;
    }
    $best = null;
    $bestScore = -1.0;
    foreach (saErpPresets() as $key => $preset) {
        $want = saNormalizeModuleKeys($preset['modules']);
        $inter = count(array_intersect($have, $want));
        $union = count(array_unique(array_merge($have, $want)));
        $score = $union > 0 ? ($inter / $union) : 0;
        if ($score > $bestScore) {
            $bestScore = $score;
            $best = $key;
        }
    }
    return $best;
}

function saActivePresetKey(array $school): string {
    $plan = strtolower(trim((string) ($school['plan'] ?? '')));
    $presets = saErpPresets();
    return isset($presets[$plan]) ? $plan : 'custom';
}

function saPresetIsModified($pdo, array $school): bool {
    $key = saActivePresetKey($school);
    if ($key === 'custom') {
        return false;
    }
    $expected = saErpPresets()[$key]['modules'];
    $actual = getSchoolModuleKeys($pdo, (int) $school['id']);
    return saNormalizeModuleKeys($expected) !== saNormalizeModuleKeys($actual);
}

function saApplyPreset($pdo, string $preset): bool {
    $presets = saErpPresets();
    if (!isset($presets[$preset])) {
        return false;
    }
    $school = saThisSchool($pdo);
    $label = ucfirst($preset);
    $pdo->prepare('UPDATE sa_schools SET plan = ? WHERE id = ?')->execute([$label, (int) $school['id']]);
    saveSchoolModules($pdo, (int) $school['id'], $presets[$preset]['modules']);
    return true;
}

function saSaveThisSchoolModules($pdo, array $moduleKeys): void {
    $school = saThisSchool($pdo);
    $cleanKeys = saNormalizeModuleKeys($moduleKeys);
    $matched = saMatchPresetKey($cleanKeys);
    if ($matched !== null) {
        $plan = ucfirst($matched);
    } else {
        $currentKey = saActivePresetKey($school);
        if ($currentKey !== 'custom') {
            $plan = ucfirst($currentKey);
        } else {
            $closest = saClosestPresetKey($cleanKeys);
            $plan = $closest ? ucfirst($closest) : 'Custom';
        }
    }
    $pdo->prepare('UPDATE sa_schools SET plan = ? WHERE id = ?')->execute([$plan, (int) $school['id']]);
    saveSchoolModules($pdo, (int) $school['id'], $cleanKeys);
}

function saSaveThisSchoolLicense($pdo, array $data): void {
    $school = saThisSchool($pdo);
    $status = (($data['status'] ?? 'Active') === 'Suspended') ? 'Suspended' : 'Active';
    $expires = trim((string) ($data['expires_at'] ?? ''));
    $starts = trim((string) ($data['starts_at'] ?? ''));
    $name = trim((string) ($data['name'] ?? ''));
    if ($name === '') {
        $name = $school['name'];
    }
    $code = trim((string) ($data['code'] ?? ''));
    $contact = trim((string) ($data['contact_name'] ?? ''));
    $phone = trim((string) ($data['phone'] ?? ''));
    $email = trim((string) ($data['email'] ?? ''));
    $city = trim((string) ($data['city'] ?? ''));
    $notes = trim((string) ($data['notes'] ?? ''));
    $pdo->prepare(
        'UPDATE sa_schools SET name=?, code=?, contact_name=?, phone=?, email=?, city=?, notes=?, status=?, starts_at=?, expires_at=?, is_current=1 WHERE id=?'
    )->execute([
        $name,
        $code !== '' ? $code : null,
        $contact !== '' ? $contact : null,
        $phone !== '' ? $phone : null,
        $email !== '' ? $email : null,
        $city !== '' ? $city : null,
        $notes !== '' ? $notes : null,
        $status,
        $starts !== '' ? $starts : null,
        $expires !== '' ? $expires : null,
        (int) $school['id'],
    ]);
    if (function_exists('setSetting')) {
        setSetting($pdo, 'school_name', $name);
        if ($phone !== '') {
            setSetting($pdo, 'school_phone', $phone);
        }
        if ($email !== '') {
            setSetting($pdo, 'school_email', $email);
        }
        if ($city !== '' && function_exists('getSetting')) {
            $addr = trim((string) getSetting($pdo, 'school_address', ''));
            if ($addr === '') {
                setSetting($pdo, 'school_address', $city);
            }
        }
    }
}

function saModuleGroups(): array {
    return [
        'core' => 'Core school ERP',
        'addon' => 'Add-on modules',
    ];
}

function saMaskSecret($value): string {
    $value = (string) $value;
    if ($value === '') {
        return '';
    }
    $len = strlen($value);
    if ($len <= 4) {
        return str_repeat('•', $len);
    }
    return str_repeat('•', $len - 4) . substr($value, -4);
}

function saClientIp(): string {
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR']);
        $cand = trim($parts[0]);
        if (filter_var($cand, FILTER_VALIDATE_IP)) {
            $ip = $cand;
        }
    }
    return substr($ip, 0, 45);
}

function saLogActivity($pdo, string $action, string $details = '', ?array $actor = null): void {
    try {
        $userId = isset($actor['id']) ? (int) $actor['id'] : (int) ($_SESSION['sa_id'] ?? 0);
        $username = trim((string) ($actor['username'] ?? ($_SESSION['sa_username'] ?? '')));
        $ua = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
        $stmt = $pdo->prepare(
            'INSERT INTO sa_activity_logs (user_id, username, action, details, ip_address, user_agent) VALUES (?,?,?,?,?,?)'
        );
        $stmt->execute([
            $userId > 0 ? $userId : null,
            $username !== '' ? $username : null,
            substr($action, 0, 80),
            substr($details, 0, 500),
            saClientIp() ?: null,
            $ua !== '' ? $ua : null,
        ]);
    } catch (Throwable $e) {
    }
}

function saActivityActionMeta(string $action): array {
    $map = [
        'login' => ['Login', 'ok'],
        'login_failed' => ['Failed login', 'warn'],
        'logout' => ['Logout', 'muted'],
        'password_changed' => ['Password changed', 'ok'],
        'smtp_saved' => ['SMTP saved', 'info'],
        'smtp_tested' => ['SMTP test', 'info'],
        'sms_saved' => ['SMS saved', 'info'],
        'sms_tested' => ['SMS test', 'info'],
        'whatsapp_saved' => ['WhatsApp saved', 'info'],
        'whatsapp_tested' => ['WhatsApp test', 'info'],
        'database_saved' => ['Database saved', 'info'],
        'database_tested' => ['Database test', 'info'],
        'preset_applied' => ['Preset applied', 'ok'],
        'features_saved' => ['Features saved', 'ok'],
        'license_updated' => ['License updated', 'warn'],
        'license_reminder' => ['License reminder', 'warn'],
        'website_saved' => ['Website saved', 'info'],
        'backup_downloaded' => ['Backup downloaded', 'info'],
        'backup_restored' => ['Backup restored', 'warn'],
    ];
    return $map[$action] ?? [ucwords(str_replace('_', ' ', $action)), 'muted'];
}

function saGetActivityLogs($pdo, int $limit = 200, int $offset = 0, string $filter = 'all'): array {
    saPruneActivityLogs($pdo);
    try {
        $limit = max(1, min(500, $limit));
        $offset = max(0, $offset);
        $sql = 'SELECT * FROM sa_activity_logs';
        $params = [];
        $actions = saActivityFilterActions($filter);
        if ($actions) {
            $in = implode(',', array_fill(0, count($actions), '?'));
            $sql .= " WHERE action IN ($in)";
            $params = $actions;
        }
        $sql .= ' ORDER BY id DESC LIMIT ' . $limit . ' OFFSET ' . $offset;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function saCountActivityLogs($pdo, string $filter = 'all'): int {
    try {
        $sql = 'SELECT COUNT(*) FROM sa_activity_logs';
        $params = [];
        $actions = saActivityFilterActions($filter);
        if ($actions) {
            $in = implode(',', array_fill(0, count($actions), '?'));
            $sql .= " WHERE action IN ($in)";
            $params = $actions;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

function saActivityFilterActions(string $filter): ?array {
    $groups = [
        'auth' => ['login', 'login_failed', 'logout', 'password_changed'],
        'settings' => ['smtp_saved', 'smtp_tested', 'sms_saved', 'sms_tested', 'whatsapp_saved', 'whatsapp_tested', 'database_saved', 'database_tested', 'website_saved', 'backup_downloaded', 'backup_restored'],
        'features' => ['preset_applied', 'features_saved', 'license_updated', 'license_reminder'],
    ];
    return $groups[$filter] ?? null;
}

function saPruneActivityLogs($pdo, int $days = 90): void {
    try {
        $days = max(1, min(3650, $days));
        $pdo->exec('DELETE FROM sa_activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ' . $days . ' DAY)');
    } catch (Throwable $e) {
    }
}

function saShortUserAgent(string $ua): string {
    $ua = trim($ua);
    if ($ua === '') {
        return '';
    }
    if (preg_match('/(Edg|OPR|Chrome|Firefox|Safari|MSIE|Trident)\/[\d.]+/', $ua, $m)) {
        return $m[0];
    }
    return strlen($ua) > 48 ? substr($ua, 0, 45) . '…' : $ua;
}

function saStreamActivityCsv($pdo, string $filter = 'all'): void {
    $rows = saGetActivityLogs($pdo, 5000, 0, $filter);
    $filename = 'sa-activity-' . date('Ymd-His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['When', 'Action', 'User', 'Details', 'IP', 'User agent']);
    foreach ($rows as $row) {
        fputcsv($out, [
            $row['created_at'] ?? '',
            $row['action'] ?? '',
            $row['username'] ?? '',
            $row['details'] ?? '',
            $row['ip_address'] ?? '',
            $row['user_agent'] ?? '',
        ]);
    }
    fclose($out);
    exit;
}

function saChangeSuperAdminPassword($pdo, int $userId, string $current, string $new, string $confirm): array {
    $errors = [];
    if (strlen($new) < 6) {
        $errors[] = 'New password must be at least 6 characters.';
    }
    if ($new !== $confirm) {
        $errors[] = 'New password and confirmation do not match.';
    }
    if (strcasecmp($new, 'admin123') === 0) {
        $errors[] = 'Please choose a password other than the default.';
    }
    if ($errors) {
        return $errors;
    }

    $stmt = $pdo->prepare('SELECT * FROM superadmin_users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user || !password_verify($current, $user['password'])) {
        return ['Current password is incorrect.'];
    }

    $hash = password_hash($new, PASSWORD_DEFAULT);
    try {
        $pdo->prepare('UPDATE superadmin_users SET password = ?, must_change_password = 0 WHERE id = ?')->execute([$hash, $userId]);
    } catch (Throwable $e) {
        $pdo->prepare('UPDATE superadmin_users SET password = ? WHERE id = ?')->execute([$hash, $userId]);
    }
    unset($_SESSION['sa_must_change']);
    saLogActivity($pdo, 'password_changed', 'Super Admin password was updated.');
    return [];
}

function saCsrfToken(): string {
    if (empty($_SESSION['sa_csrf']) || !is_string($_SESSION['sa_csrf'])) {
        $_SESSION['sa_csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['sa_csrf'];
}

function saCsrfField(): string {
    return '<input type="hidden" name="sa_csrf" value="' . htmlspecialchars(saCsrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

function saCsrfVerify(): bool {
    $tok = (string) ($_POST['sa_csrf'] ?? '');
    return $tok !== '' && hash_equals(saCsrfToken(), $tok);
}

function saRequireCsrf(string $redirect = 'dashboard.php'): void {
    if (saCsrfVerify()) {
        return;
    }
    saFlash('error', 'Session expired. Please try again.');
    header('Location: ' . $redirect);
    exit;
}

function saLoginIsLocked($pdo): bool {
    try {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM sa_login_attempts WHERE ip_address = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)'
        );
        $stmt->execute([saClientIp()]);
        return (int) $stmt->fetchColumn() >= 8;
    } catch (Throwable $e) {
        return false;
    }
}

function saRecordLoginAttempt($pdo, string $username): void {
    try {
        $pdo->prepare('INSERT INTO sa_login_attempts (ip_address, username) VALUES (?,?)')->execute([saClientIp(), substr($username, 0, 50)]);
        $pdo->exec('DELETE FROM sa_login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 1 DAY)');
    } catch (Throwable $e) {
    }
}

function saClearLoginAttempts($pdo): void {
    try {
        $pdo->prepare('DELETE FROM sa_login_attempts WHERE ip_address = ?')->execute([saClientIp()]);
    } catch (Throwable $e) {
    }
}

function saUserMustChangePassword(array $user): bool {
    if (!empty($user['must_change_password'])) {
        return true;
    }
    return password_verify('admin123', (string) ($user['password'] ?? ''));
}

function saMaybeSendLicenseReminder($pdo, array $school): void {
    $expires = trim((string) ($school['expires_at'] ?? ''));
    if ($expires === '' || ($school['status'] ?? '') === 'Suspended') {
        return;
    }
    $days = (int) floor((strtotime($expires) - strtotime(date('Y-m-d'))) / 86400);
    if ($days < 0 || $days > 14) {
        return;
    }
    $sent = function_exists('getSetting') ? (string) getSetting($pdo, 'sa_license_reminder_date', '') : '';
    if ($sent === date('Y-m-d')) {
        return;
    }
    $name = (string) ($school['name'] ?? 'School');
    $toEmail = trim((string) ($school['email'] ?? ''));
    if ($toEmail === '' && function_exists('getSetting')) {
        $toEmail = trim((string) getSetting($pdo, 'school_email', ''));
    }
    $phone = trim((string) ($school['phone'] ?? ''));
    if ($phone === '' && function_exists('getSetting')) {
        $phone = trim((string) getSetting($pdo, 'school_phone', ''));
    }
    $msg = "License for {$name} expires in {$days} day(s) on {$expires}. Please renew from SuperAdmin.";
    $ok = false;
    if ($toEmail !== '' && function_exists('sendEmailViaSettings')) {
        $err = '';
        $ok = sendEmailViaSettings($pdo, $toEmail, 'School license expiring', '<p>' . htmlspecialchars($msg) . '</p>', $err) || $ok;
    }
    if ($phone !== '' && function_exists('dispatchSms')) {
        $sms = dispatchSms($pdo, $phone, $msg);
        $ok = !empty($sms['ok']) || $ok;
    }
    if (function_exists('setSetting')) {
        setSetting($pdo, 'sa_license_reminder_date', date('Y-m-d'));
    }
    saLogActivity($pdo, 'license_reminder', $msg . ($ok ? ' Reminder sent.' : ' Banner only — no gateway recipient.'));
}

function saLicenseDaysLeft(array $school): ?int {
    $expires = trim((string) ($school['expires_at'] ?? ''));
    if ($expires === '') {
        return null;
    }
    return (int) floor((strtotime($expires) - strtotime(date('Y-m-d'))) / 86400);
}

function saDashboardStats($pdo): array {
    $students = 0;
    $teachers = 0;
    $feeMonth = 0.0;
    try {
        $students = (int) $pdo->query("SELECT COUNT(*) FROM students WHERE status='Active'")->fetchColumn();
    } catch (Throwable $e) {
    }
    try {
        $teachers = (int) $pdo->query("SELECT COUNT(*) FROM teachers WHERE status='Active'")->fetchColumn();
    } catch (Throwable $e) {
    }
    try {
        $monthStart = date('Y-m-01');
        $stmt = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM fee_payments WHERE payment_date >= ?');
        $stmt->execute([$monthStart]);
        $feeMonth = (float) $stmt->fetchColumn();
    } catch (Throwable $e) {
    }
    $smtp = function_exists('getSmtpSettings') ? getSmtpSettings($pdo) : ['enabled' => '0'];
    $sms = function_exists('getSmsSettings') ? getSmsSettings($pdo) : ['enabled' => '0'];
    $wa = function_exists('getWhatsAppSettings') ? getWhatsAppSettings($pdo) : ['enabled' => '0'];
    return [
        'students' => $students,
        'teachers' => $teachers,
        'fee_month' => $feeMonth,
        'smtp' => ($smtp['enabled'] ?? '0') === '1',
        'sms' => ($sms['enabled'] ?? '0') === '1',
        'whatsapp' => ($wa['enabled'] ?? '0') === '1',
    ];
}

function saDumpDatabase($pdo): string {
    $out = "-- SchoolERP SuperAdmin backup " . date('c') . "\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n";
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) ?: [];
    foreach ($tables as $table) {
        $table = (string) $table;
        $create = $pdo->query('SHOW CREATE TABLE `' . str_replace('`', '``', $table) . '`')->fetch(PDO::FETCH_ASSOC);
        $out .= 'DROP TABLE IF EXISTS `' . str_replace('`', '``', $table) . "`;\n";
        $out .= ($create['Create Table'] ?? '') . ";\n\n";
        $rows = $pdo->query('SELECT * FROM `' . str_replace('`', '``', $table) . '`');
        while ($row = $rows->fetch(PDO::FETCH_ASSOC)) {
            $cols = [];
            $vals = [];
            foreach ($row as $col => $val) {
                $cols[] = '`' . str_replace('`', '``', (string) $col) . '`';
                $vals[] = $val === null ? 'NULL' : $pdo->quote((string) $val);
            }
            $out .= 'INSERT INTO `' . str_replace('`', '``', $table) . '` (' . implode(',', $cols) . ') VALUES (' . implode(',', $vals) . ");\n";
        }
        $out .= "\n";
    }
    $out .= "SET FOREIGN_KEY_CHECKS=1;\n";
    return $out;
}

function saRestoreDatabase($pdo, string $sql): void {
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    $parts = preg_split('/;[\r\n]+/', $sql) ?: [];
    foreach ($parts as $part) {
        $stmt = trim($part);
        if ($stmt === '' || strpos($stmt, '--') === 0) {
            continue;
        }
        $pdo->exec($stmt);
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
}
