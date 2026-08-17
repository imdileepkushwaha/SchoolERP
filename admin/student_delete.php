<?php
// admin/student_delete.php — POST only
require_once 'includes/init.php';
require_once '../includes/db_connect.php';
require_once 'includes/module_helpers.php';

assertSchoolLicenseActive($pdo);
requireModule($pdo, 'students');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: students.php');
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
if ($id > 0) {
    try {
        $pdo->prepare('DELETE FROM students WHERE id = ?')->execute([$id]);
        if (function_exists('adminLogActivity')) {
            adminLogActivity($pdo, 'student_deleted', 'Student #' . $id);
        }
        $_SESSION['success_msg'] = 'Student deleted successfully!';
    } catch (PDOException $e) {
        $_SESSION['error_msg'] = 'Failed to delete student. Please try again.';
    }
}

header('Location: students.php');
exit;
