<?php
// JSON endpoint — keep the output clean so a stray notice can never corrupt the response.
ini_set('display_errors', '0');
require_once 'includes/init.php';
require_once '../includes/db_connect.php';
require_once 'includes/student_helpers.php';
require_once 'includes/teacher_helpers.php';
require_once 'includes/class_helpers.php';

header('Content-Type: application/json');
$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) {
    echo json_encode(['results' => []]);
    exit;
}
$like = '%' . $q . '%';
$results = [];

$stmt = $pdo->prepare("SELECT id, ad_no, name, class FROM students WHERE status='Active' AND (name LIKE ? OR ad_no LIKE ?) LIMIT 5");
$stmt->execute([$like, $like]);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $results[] = ['type' => 'Student', 'title' => $r['name'], 'meta' => $r['ad_no'] . ' · ' . $r['class'], 'url' => 'student_view.php?id=' . $r['id'], 'icon' => 'fa-user-graduate'];
}

$stmt = $pdo->prepare("SELECT id, employee_id, name, subject FROM teachers WHERE status='Active' AND (name LIKE ? OR employee_id LIKE ?) LIMIT 5");
$stmt->execute([$like, $like]);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $results[] = ['type' => 'Teacher', 'title' => $r['name'], 'meta' => $r['employee_id'] . ' · ' . $r['subject'], 'url' => 'teacher_view.php?id=' . $r['id'], 'icon' => 'fa-chalkboard-teacher'];
}

ensureClassSchema($pdo);
$stmt = $pdo->prepare("SELECT name FROM school_classes WHERE name LIKE ? AND status='Active' LIMIT 3");
$stmt->execute([$like]);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $results[] = ['type' => 'Class', 'title' => $r['name'], 'meta' => 'Classes & Sections', 'url' => 'classes.php', 'icon' => 'fa-school'];
}

echo json_encode(['results' => $results]);
