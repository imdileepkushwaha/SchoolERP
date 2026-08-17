<?php
// admin/teacher_delete.php — POST only
require_once 'includes/init.php';
require_once '../includes/db_connect.php';
require_once 'includes/teacher_helpers.php';
require_once 'includes/module_helpers.php';

assertSchoolLicenseActive($pdo);
requireModule($pdo, 'teachers');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: teachers.php');
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
if ($id > 0) {
    try {
        $pdo->prepare('DELETE FROM teacher_timetable WHERE teacher_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM teachers WHERE id = ?')->execute([$id]);
        if (function_exists('adminLogActivity')) {
            adminLogActivity($pdo, 'teacher_deleted', 'Teacher #' . $id);
        }
        $_SESSION['success_msg'] = 'Teacher deleted successfully.';
    } catch (PDOException $e) {
        $_SESSION['error_msg'] = 'Failed to delete teacher.';
    }
}

header('Location: teachers.php');
exit;
