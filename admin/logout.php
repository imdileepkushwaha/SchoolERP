<?php
// admin/logout.php
session_start();
require_once __DIR__ . '/includes/admin_helpers.php';
require_once __DIR__ . '/../includes/db_connection.php';

$dbConnection = connectDatabase(['soft_fail' => true]);
$pdo = $dbConnection['pdo'] ?? null;
if (!empty($_SESSION['admin_logged_in']) && $pdo) {
    try {
        ensureAdminAuthSchema($pdo);
        adminLogActivity($pdo, 'logout', 'Signed out.');
    } catch (Throwable $e) {
    }
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();

header('Location: index.php');
exit;
