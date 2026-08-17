<?php
// admin/includes/module_helpers.php — license / feature flags per school

require_once __DIR__ . '/settings_helpers.php';

function getErpModuleCatalog(): array {
    return [
        'students' => [
            'label' => 'Students',
            'group' => 'core',
            'icon' => 'fa-user-graduate',
            'description' => 'Admissions, list, classes, documents, promote',
        ],
        'academic' => [
            'label' => 'Academic',
            'group' => 'core',
            'icon' => 'fa-book-open',
            'description' => 'Sessions, subjects, timetable, notices, homework',
        ],
        'attendance' => [
            'label' => 'Attendance',
            'group' => 'core',
            'icon' => 'fa-calendar-check',
            'description' => 'Daily marking and monthly reports',
        ],
        'fees' => [
            'label' => 'Fees',
            'group' => 'core',
            'icon' => 'fa-file-invoice-dollar',
            'description' => 'Fee structure, collect, receipts, reports',
        ],
        'exams' => [
            'label' => 'Examinations',
            'group' => 'core',
            'icon' => 'fa-edit',
            'description' => 'Exams, marks, report cards, analytics',
        ],
        'teachers' => [
            'label' => 'Teachers',
            'group' => 'core',
            'icon' => 'fa-chalkboard-teacher',
            'description' => 'Staff profiles, timetable, leave, attendance',
        ],
        'certificates' => [
            'label' => 'Certificates',
            'group' => 'addon',
            'icon' => 'fa-certificate',
            'description' => 'TC, bonafide and character certificates',
        ],
        'transport' => [
            'label' => 'Transport',
            'group' => 'addon',
            'icon' => 'fa-bus',
            'description' => 'Vehicles, routes, stops, student assignment',
        ],
        'hostel' => [
            'label' => 'Hostel',
            'group' => 'addon',
            'icon' => 'fa-bed',
            'description' => 'Hostels, rooms and student allotment',
        ],
        'library' => [
            'label' => 'Library',
            'group' => 'addon',
            'icon' => 'fa-book',
            'description' => 'Books, issue and return',
        ],
        'notifications' => [
            'label' => 'SMS / WhatsApp',
            'group' => 'addon',
            'icon' => 'fa-bell',
            'description' => 'Parent and student messaging',
        ],
        'student_portal' => [
            'label' => 'Student Portal',
            'group' => 'addon',
            'icon' => 'fa-laptop',
            'description' => 'Student login for results, fees and homework',
        ],
        'teacher_portal' => [
            'label' => 'Teacher Portal',
            'group' => 'addon',
            'icon' => 'fa-chalkboard',
            'description' => 'Teacher login for classes and attendance',
        ],
        'website' => [
            'label' => 'School Website',
            'group' => 'addon',
            'icon' => 'fa-globe',
            'description' => 'Public homepage and contact enquiries',
        ],
    ];
}

function getDefaultErpModuleKeys(): array {
    return array_keys(getErpModuleCatalog());
}

function getCoreErpModuleKeys(): array {
    $keys = [];
    foreach (getErpModuleCatalog() as $key => $meta) {
        if (($meta['group'] ?? '') === 'core') {
            $keys[] = $key;
        }
    }
    return $keys;
}

function ensureSuperAdminSchema($pdo): void {
    static $done = false;
    if ($done) {
        return;
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS `superadmin_users` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `username` varchar(50) NOT NULL,
        `password` varchar(255) NOT NULL,
        `name` varchar(100) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `username` (`username`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `sa_schools` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(150) NOT NULL,
        `code` varchar(40) DEFAULT NULL,
        `contact_name` varchar(100) DEFAULT NULL,
        `phone` varchar(30) DEFAULT NULL,
        `email` varchar(120) DEFAULT NULL,
        `city` varchar(80) DEFAULT NULL,
        `plan` varchar(40) NOT NULL DEFAULT 'Custom',
        `status` enum('Active','Suspended') NOT NULL DEFAULT 'Active',
        `starts_at` date DEFAULT NULL,
        `expires_at` date DEFAULT NULL,
        `is_current` tinyint(1) NOT NULL DEFAULT 0,
        `notes` text DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `code` (`code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `sa_school_modules` (
        `school_id` int(11) NOT NULL,
        `module_key` varchar(50) NOT NULL,
        PRIMARY KEY (`school_id`,`module_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `sa_activity_logs` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) DEFAULT NULL,
        `username` varchar(50) DEFAULT NULL,
        `action` varchar(80) NOT NULL,
        `details` varchar(500) DEFAULT NULL,
        `ip_address` varchar(45) DEFAULT NULL,
        `user_agent` varchar(255) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `action` (`action`),
        KEY `created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `sa_login_attempts` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `ip_address` varchar(45) DEFAULT NULL,
        `username` varchar(50) DEFAULT NULL,
        `attempted_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `ip_time` (`ip_address`,`attempted_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    try {
        $pdo->exec("ALTER TABLE `superadmin_users` ADD COLUMN `must_change_password` tinyint(1) NOT NULL DEFAULT 0");
    } catch (Throwable $e) {
    }

    foreach ([
        "ALTER TABLE `sa_schools` ADD COLUMN `contact_name` varchar(100) DEFAULT NULL",
        "ALTER TABLE `sa_schools` ADD COLUMN `phone` varchar(30) DEFAULT NULL",
        "ALTER TABLE `sa_schools` ADD COLUMN `email` varchar(120) DEFAULT NULL",
        "ALTER TABLE `sa_schools` ADD COLUMN `city` varchar(80) DEFAULT NULL",
        "ALTER TABLE `sa_schools` ADD COLUMN `notes` text DEFAULT NULL",
        "ALTER TABLE `sa_schools` ADD COLUMN `starts_at` date DEFAULT NULL",
        "ALTER TABLE `sa_activity_logs` ADD COLUMN `user_agent` varchar(255) DEFAULT NULL",
    ] as $alterSql) {
        try {
            $pdo->exec($alterSql);
        } catch (Throwable $e) {
        }
    }

    try {
        ensureSettingsSchema($pdo);
    } catch (Throwable $e) {
    }

    $done = true;

    $count = (int) $pdo->query('SELECT COUNT(*) FROM superadmin_users')->fetchColumn();
    if ($count === 0) {
        $hash = password_hash('admin123', PASSWORD_BCRYPT);
        $pdo->prepare('INSERT INTO superadmin_users (username, password, name, must_change_password) VALUES (?,?,?,1)')
            ->execute(['superadmin', $hash, 'Super Admin']);
    }

    $schoolCount = (int) $pdo->query('SELECT COUNT(*) FROM sa_schools')->fetchColumn();
    if ($schoolCount === 0) {
        $schoolName = 'EduDash School';
        try {
            if (function_exists('getSetting')) {
                $fromSettings = trim((string) getSetting($pdo, 'school_name', ''));
                if ($fromSettings !== '') {
                    $schoolName = $fromSettings;
                }
            }
        } catch (Throwable $e) {
        }

        $pdo->prepare(
            'INSERT INTO sa_schools (name, code, plan, status, starts_at, is_current) VALUES (?,?,?,?,CURDATE(),1)'
        )->execute([$schoolName, 'SCHOOL01', 'Full', 'Active']);
        $schoolId = (int) $pdo->lastInsertId();
        saveSchoolModules($pdo, $schoolId, getDefaultErpModuleKeys());
    }
}

function saveSchoolModules($pdo, int $schoolId, array $moduleKeys): void {
    $catalog = getErpModuleCatalog();
    $clean = [];
    foreach ($moduleKeys as $key) {
        $key = trim((string) $key);
        if ($key !== '' && isset($catalog[$key])) {
            $clean[$key] = $key;
        }
    }
    if (!$clean) {
        $clean = array_fill_keys(getCoreErpModuleKeys(), true);
        $clean = array_keys($clean);
    } else {
        $clean = array_values($clean);
    }

    $pdo->prepare('DELETE FROM sa_school_modules WHERE school_id = ?')->execute([$schoolId]);
    $stmt = $pdo->prepare('INSERT INTO sa_school_modules (school_id, module_key) VALUES (?,?)');
    foreach ($clean as $key) {
        $stmt->execute([$schoolId, $key]);
    }

    $current = getCurrentSaSchool($pdo);
    if ($current && (int) $current['id'] === $schoolId && function_exists('setSetting')) {
        setSetting($pdo, 'enabled_modules', implode(',', $clean));
    }

    clearEnabledModuleKeysCache();
}

function getSchoolModuleKeys($pdo, int $schoolId): array {
    $stmt = $pdo->prepare('SELECT module_key FROM sa_school_modules WHERE school_id = ?');
    $stmt->execute([$schoolId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
}

function getCurrentSaSchool($pdo): ?array {
    ensureSuperAdminSchema($pdo);
    $row = $pdo->query('SELECT * FROM sa_schools WHERE is_current = 1 ORDER BY id DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        return $row;
    }
    $row = $pdo->query('SELECT * FROM sa_schools ORDER BY id ASC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function clearEnabledModuleKeysCache(): void {
    getEnabledModuleKeys(null, true);
}

function getEnabledModuleKeys($pdo, bool $reset = false): array {
    static $cache = null;
    if ($reset) {
        $cache = null;
        return [];
    }
    if ($cache !== null) {
        return $cache;
    }

    try {
        ensureSuperAdminSchema($pdo);
        $school = getCurrentSaSchool($pdo);
        if ($school) {
            $keys = getSchoolModuleKeys($pdo, (int) $school['id']);
            $cache = $keys ?: getCoreErpModuleKeys();
            return $cache;
        }
    } catch (Throwable $e) {
    }

    $cache = getDefaultErpModuleKeys();
    return $cache;
}

function moduleEnabled($pdo, string $key): bool {
    return in_array($key, getEnabledModuleKeys($pdo), true);
}

function erpPageModule(string $page): ?string {
    $map = [
        'students.php' => 'students', 'student_add.php' => 'students', 'student_edit.php' => 'students',
        'student_view.php' => 'students', 'student_suspend.php' => 'students', 'student_categories.php' => 'students',
        'student_import.php' => 'students', 'student_import_sample.php' => 'students',
        'student_promote.php' => 'students', 'student_promote_advanced.php' => 'students',
        'student_id_card.php' => 'students', 'student_documents.php' => 'students', 'student_export.php' => 'students',
        'student_delete.php' => 'students', 'classes.php' => 'students',
        'admission_enquiries.php' => 'students', 'portal_accounts.php' => 'student_portal',
        'academic_sessions.php' => 'academic', 'subjects.php' => 'academic', 'class_timetable.php' => 'academic',
        'notices.php' => 'academic', 'homework.php' => 'academic',
        'attendance.php' => 'attendance', 'attendance_report.php' => 'attendance',
        'fees.php' => 'fees', 'fee_collect.php' => 'fees', 'fee_receipt.php' => 'fees', 'fee_reports.php' => 'fees',
        'exams.php' => 'exams', 'marks.php' => 'exams', 'report_card.php' => 'exams', 'exam_analytics.php' => 'exams',
        'certificates.php' => 'certificates', 'certificate_print.php' => 'certificates',
        'transport.php' => 'transport', 'hostel.php' => 'hostel', 'library.php' => 'library',
        'notifications.php' => 'notifications',
        'teachers.php' => 'teachers', 'teacher_add.php' => 'teachers', 'teacher_edit.php' => 'teachers',
        'teacher_view.php' => 'teachers', 'teacher_timetable.php' => 'teachers', 'teacher_delete.php' => 'teachers',
        'teacher_portal_accounts.php' => 'teacher_portal', 'teacher_attendance.php' => 'teachers',
        'leave_requests.php' => 'teachers',
        'id_cards.php' => 'students', 'report_cards.php' => 'exams',
        'website_enquiries.php' => 'website',
    ];
    return $map[$page] ?? null;
}

function requirePageModule($pdo): void {
    $page = basename($_SERVER['PHP_SELF'] ?? '');
    if ($page === 'teacher_salary.php') {
        if (function_exists('adminCanSalary') && !adminCanSalary($pdo)) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['error_msg'] = 'You do not have access to salary.';
            header('Location: dashboard.php');
            exit;
        }
        return;
    }
    $mod = erpPageModule($page);
    if ($mod) {
        requireModule($pdo, $mod);
    }
}

function requireModule($pdo, string $key, string $redirect = 'dashboard.php'): void {
    $ok = moduleEnabled($pdo, $key);
    if ($ok && function_exists('adminCanAccessModule')) {
        $ok = adminCanAccessModule($pdo, $key);
    }
    if ($ok) {
        return;
    }
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['error_msg'] = moduleEnabled($pdo, $key)
        ? 'Your login role cannot open this section.'
        : 'This module is not enabled for your school. Contact SuperAdmin.';
    header('Location: ' . $redirect);
    exit;
}

function getCurrentSchoolLicenseStatus($pdo): array {
    try {
        $school = getCurrentSaSchool($pdo);
    } catch (Throwable $e) {
        return ['ok' => true, 'school' => null, 'message' => ''];
    }
    if (!$school) {
        return ['ok' => true, 'school' => null, 'message' => ''];
    }
    if (($school['status'] ?? '') === 'Suspended') {
        return ['ok' => false, 'school' => $school, 'message' => 'This school account is suspended. Contact SuperAdmin.'];
    }
    if (!empty($school['starts_at']) && $school['starts_at'] > date('Y-m-d')) {
        return ['ok' => false, 'school' => $school, 'message' => 'School license has not started yet. Contact SuperAdmin.'];
    }
    if (!empty($school['expires_at']) && $school['expires_at'] < date('Y-m-d')) {
        return ['ok' => false, 'school' => $school, 'message' => 'School license has expired. Contact SuperAdmin.'];
    }
    return ['ok' => true, 'school' => $school, 'message' => ''];
}

function assertSchoolLicenseActive($pdo): void {
    $status = getCurrentSchoolLicenseStatus($pdo);
    if ($status['ok']) {
        return;
    }
    http_response_code(403);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>License</title></head><body style="font-family:Inter,sans-serif;padding:40px;text-align:center;color:#334155">';
    echo '<h2>Access paused</h2><p>' . htmlspecialchars($status['message']) . '</p></body></html>';
    exit;
}

function assertPortalModuleEnabled($pdo, string $key, string $label = 'This portal'): void {
    ensureSuperAdminSchema($pdo);
    $status = getCurrentSchoolLicenseStatus($pdo);
    if (!$status['ok']) {
        http_response_code(403);
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Access</title></head><body style="font-family:Inter,sans-serif;padding:40px;text-align:center;color:#334155">';
        echo '<h2>Access paused</h2><p>' . htmlspecialchars($status['message']) . '</p></body></html>';
        exit;
    }
    if (moduleEnabled($pdo, $key)) {
        return;
    }
    http_response_code(403);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Access</title></head><body style="font-family:Inter,sans-serif;padding:40px;text-align:center;color:#334155">';
    echo '<h2>' . htmlspecialchars($label) . ' is not enabled</h2><p>Please contact the school office or SuperAdmin.</p></body></html>';
    exit;
}
