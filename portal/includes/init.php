<?php
// portal/includes/init.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['student_portal_id']) || !$_SESSION['student_portal_id']) {
    header('Location: index.php');
    exit;
}
