<?php
// admin/includes/init.php — session & auth only (no HTML output)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/admin_helpers.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    adminRequireCsrf();
}

$page = basename($_SERVER['PHP_SELF'] ?? '');
$tab = (string) ($_GET['tab'] ?? $_POST['tab'] ?? '');
$postAction = (string) ($_POST['action'] ?? '');
if (!empty($_SESSION['admin_must_change'])) {
    $ok = $page === 'logout.php'
        || ($page === 'settings.php' && ($tab === 'password' || $postAction === 'change_password'));
    if (!$ok) {
        $_SESSION['error_msg'] = 'Change the default admin password before continuing.';
        header('Location: settings.php?tab=password');
        exit;
    }
}

// Enforce license + role/module gates BEFORE any page mutation handlers run.
$adminAccessExempt = ['index.php', 'logout.php', 'db_setup.php'];
if (!in_array($page, $adminAccessExempt, true)) {
    require_once __DIR__ . '/../../includes/db_connect.php';
    require_once __DIR__ . '/module_helpers.php';
    ensureSuperAdminSchema($pdo);
    ensureAdminAuthSchema($pdo);
    assertSchoolLicenseActive($pdo);
    requirePageModule($pdo);
}
