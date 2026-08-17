<?php
session_start();
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/includes/helpers.php';

if (!empty($_SESSION['sa_logged_in'])) {
    saLogActivity($pdo, 'logout', 'Signed out of Super Admin.');
}

$_SESSION = [];
session_destroy();
header('Location: index.php');
exit;
