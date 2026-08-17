<?php
// admin/includes/admin_helpers.php

function adminClientIp(): string {
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

function adminCsrfToken(): string {
    if (empty($_SESSION['admin_csrf']) || !is_string($_SESSION['admin_csrf'])) {
        $_SESSION['admin_csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['admin_csrf'];
}

function adminCsrfField(): string {
    return '<input type="hidden" name="admin_csrf" value="' . htmlspecialchars(adminCsrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

function adminCsrfVerify(): bool {
    $tok = (string) ($_POST['admin_csrf'] ?? '');
    return $tok !== '' && hash_equals(adminCsrfToken(), $tok);
}

function adminRequireCsrf(): void {
    if (adminCsrfVerify()) {
        return;
    }
    $_SESSION['error_msg'] = 'Session expired. Please try again.';
    $ref = (string) ($_SERVER['HTTP_REFERER'] ?? 'dashboard.php');
    $path = parse_url($ref, PHP_URL_PATH) ?: '';
    $base = basename($path);
    header('Location: ' . ($base !== '' ? $base : 'dashboard.php'));
    exit;
}

function ensureAdminAuthSchema($pdo): void {
    static $done = false;
    if ($done || !$pdo) {
        return;
    }
    $done = true;
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `admin_login_attempts` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `ip_address` varchar(45) DEFAULT NULL,
            `username` varchar(50) DEFAULT NULL,
            `attempted_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `ip_time` (`ip_address`,`attempted_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $pdo->exec("CREATE TABLE IF NOT EXISTS `admin_activity_logs` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) DEFAULT NULL,
            `username` varchar(50) DEFAULT NULL,
            `action` varchar(80) NOT NULL,
            `details` varchar(500) DEFAULT NULL,
            `ip_address` varchar(45) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $pdo->exec("CREATE TABLE IF NOT EXISTS `teacher_salary_payments` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `teacher_id` int(11) NOT NULL,
            `pay_month` char(7) NOT NULL,
            `amount` decimal(12,2) NOT NULL DEFAULT 0,
            `paid_on` date DEFAULT NULL,
            `remarks` varchar(200) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `teacher_month` (`teacher_id`,`pay_month`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $e) {
    }
    foreach ([
        "ALTER TABLE `admin_users` ADD COLUMN `name` varchar(100) DEFAULT NULL",
        "ALTER TABLE `admin_users` ADD COLUMN `role` varchar(30) NOT NULL DEFAULT 'admin'",
        "ALTER TABLE `admin_users` ADD COLUMN `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active'",
        "ALTER TABLE `admin_users` ADD COLUMN `must_change_password` tinyint(1) NOT NULL DEFAULT 0",
    ] as $sql) {
        try {
            $pdo->exec($sql);
        } catch (Throwable $e) {
        }
    }
}

function adminRoles(): array {
    return [
        'admin' => ['label' => 'Full Admin', 'modules' => null],
        'accountant' => ['label' => 'Accountant', 'modules' => ['fees']],
        'academic' => ['label' => 'Academic', 'modules' => ['academic', 'exams', 'attendance', 'certificates']],
        'receptionist' => ['label' => 'Receptionist', 'modules' => ['students', 'website']],
    ];
}

function adminRoleLabel(string $role): string {
    return adminRoles()[$role]['label'] ?? ucfirst($role);
}

function adminRole(): string {
    $role = (string) ($_SESSION['admin_role'] ?? 'admin');
    return isset(adminRoles()[$role]) ? $role : 'admin';
}

function adminIsFullAdmin(): bool {
    return adminRole() === 'admin';
}

function adminCanAccessModule($pdo, string $key): bool {
    if (function_exists('moduleEnabled') && !moduleEnabled($pdo, $key)) {
        return false;
    }
    $mods = adminRoles()[adminRole()]['modules'] ?? null;
    if ($mods === null) {
        return true;
    }
    return in_array($key, $mods, true);
}

function adminCanManageSchool(): bool {
    return adminIsFullAdmin();
}

function adminCanSalary($pdo): bool {
    $role = adminRole();
    if ($role !== 'admin' && $role !== 'accountant') {
        return false;
    }
    return !function_exists('moduleEnabled')
        || moduleEnabled($pdo, 'teachers')
        || moduleEnabled($pdo, 'fees');
}

function adminMustChangePassword(array $user): bool {
    if (!empty($user['must_change_password'])) {
        return true;
    }
    return password_verify('admin123', (string) ($user['password'] ?? ''));
}

function adminLoginIsLocked($pdo): bool {
    try {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM admin_login_attempts WHERE ip_address = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)'
        );
        $stmt->execute([adminClientIp()]);
        return (int) $stmt->fetchColumn() >= 8;
    } catch (Throwable $e) {
        return false;
    }
}

function adminRecordLoginAttempt($pdo, string $username): void {
    try {
        $pdo->prepare('INSERT INTO admin_login_attempts (ip_address, username) VALUES (?,?)')->execute([
            adminClientIp(),
            substr($username, 0, 50),
        ]);
        $pdo->exec('DELETE FROM admin_login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 1 DAY)');
    } catch (Throwable $e) {
    }
}

function adminClearLoginAttempts($pdo): void {
    try {
        $pdo->prepare('DELETE FROM admin_login_attempts WHERE ip_address = ?')->execute([adminClientIp()]);
    } catch (Throwable $e) {
    }
}

function adminLogActivity($pdo, string $action, string $details = ''): void {
    try {
        $pdo->prepare(
            'INSERT INTO admin_activity_logs (user_id, username, action, details, ip_address) VALUES (?,?,?,?,?)'
        )->execute([
            (int) ($_SESSION['admin_id'] ?? 0) ?: null,
            $_SESSION['admin_username'] ?? null,
            substr($action, 0, 80),
            substr($details, 0, 500),
            adminClientIp() ?: null,
        ]);
    } catch (Throwable $e) {
    }
}

function adminGetActivityLogs($pdo, int $limit = 50, int $offset = 0): array {
    try {
        $pdo->exec('DELETE FROM admin_activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)');
        $limit = max(1, min(200, $limit));
        $offset = max(0, $offset);
        return $pdo->query(
            'SELECT * FROM admin_activity_logs ORDER BY id DESC LIMIT ' . $limit . ' OFFSET ' . $offset
        )->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function adminCountActivityLogs($pdo): int {
    try {
        return (int) $pdo->query('SELECT COUNT(*) FROM admin_activity_logs')->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

function adminInitials(?string $name): string {
    $name = trim((string) $name);
    if ($name === '') {
        return 'A';
    }
    $parts = preg_split('/\s+/', $name) ?: [$name];
    $out = strtoupper(substr($parts[0], 0, 1));
    if (isset($parts[1])) {
        $out .= strtoupper(substr($parts[1], 0, 1));
    }
    return $out;
}

function getWebsiteGallery($pdo): array {
    if (!function_exists('getSetting')) {
        return [];
    }
    $raw = (string) getSetting($pdo, 'website_gallery', '[]');
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return [];
    }
    $out = [];
    foreach ($data as $row) {
        $path = trim((string) ($row['path'] ?? ''));
        $title = trim((string) ($row['title'] ?? ''));
        if ($path !== '') {
            $out[] = ['path' => $path, 'title' => $title !== '' ? $title : 'Gallery'];
        }
    }
    return $out;
}

function saveWebsiteGallery($pdo, array $items): void {
    if (function_exists('setSetting')) {
        setSetting($pdo, 'website_gallery', json_encode(array_values($items)));
    }
}

function uploadGalleryFile(array $file) {
    if (empty($file['name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    if (!isset($allowed[$mime]) || ($file['size'] ?? 0) > 2 * 1024 * 1024) {
        return false;
    }
    $dir = __DIR__ . '/../uploads/gallery/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $name = 'gallery_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $allowed[$mime];
    if (move_uploaded_file($file['tmp_name'], $dir . $name)) {
        return 'uploads/gallery/' . $name;
    }
    return false;
}

function deleteGalleryFile(string $path): void {
    $path = str_replace(['..', '\\'], '', ltrim($path, '/'));
    if (strpos($path, 'uploads/gallery/') !== 0) {
        return;
    }
    $full = __DIR__ . '/../' . $path;
    if (is_file($full)) {
        @unlink($full);
    }
}

function websiteGalleryUrl(string $path, string $context = 'admin'): string {
    $path = ltrim($path, '/');
    if ($path === '' || preg_match('#^https?://#i', $path)) {
        return $path;
    }
    if ($context === 'public') {
        return 'admin/' . $path;
    }
    if (function_exists('schoolBrandingUrl')) {
        return schoolBrandingUrl($path, $context);
    }
    return $path;
}
