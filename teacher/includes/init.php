<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (!isset($_SESSION['teacher_portal_id']) || !$_SESSION['teacher_portal_id']) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../admin/includes/teacher_helpers.php';
require_once __DIR__ . '/../../admin/includes/erp_helpers.php';

ensureTeacherSchema($pdo);
ensureErpSchema($pdo);

$teacherId = (int) $_SESSION['teacher_portal_id'];
$teacher = getTeacherById($pdo, $teacherId);

if (!$teacher || !isTeacherActive($teacher) || !isTeacherPortalEnabled($teacher)) {
    session_destroy();
    header('Location: index.php');
    exit;
}

$tp_page = basename($_SERVER['PHP_SELF'], '.php');
$tp_name = htmlspecialchars($teacher['name']);
$tp_photo = getTeacherPortalPhotoUrl($teacher);
$tp_emp_id = htmlspecialchars($teacher['employee_id']);

function tp_nav_active($page) {
    global $tp_page;
    return $tp_page === $page ? ' active' : '';
}

$tp_must_change = teacherMustChangePassword($teacher);
if ($tp_must_change && $tp_page !== 'change-password' && $tp_page !== 'logout') {
    header('Location: change-password.php?required=1');
    exit;
}
