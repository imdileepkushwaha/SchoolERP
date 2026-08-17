<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['sa_logged_in'])) {
    header('Location: index.php');
    exit;
}
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/helpers.php';
ensureSuperAdminSchema($pdo);
$school = saThisSchool($pdo);
saMaybeSendLicenseReminder($pdo, $school);

$page = basename($_SERVER['PHP_SELF']);
$tab = (string) ($_GET['tab'] ?? $_POST['tab'] ?? '');
$postAction = (string) ($_POST['action'] ?? '');
if (!empty($_SESSION['sa_must_change'])) {
    $ok = $page === 'logout.php'
        || ($page === 'settings.php' && ($tab === 'security' || $postAction === 'change_password'));
    if (!$ok) {
        saFlash('error', 'Change the default Super Admin password before continuing.');
        header('Location: settings.php?tab=security');
        exit;
    }
}
