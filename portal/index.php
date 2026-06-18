<?php
session_start();
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../admin/includes/erp_helpers.php';

if (isset($_SESSION['student_portal_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ensureErpSchema($pdo);
    $student = authenticateStudentPortal($pdo, $_POST['ad_no'] ?? '', $_POST['password'] ?? '');
    if ($student) {
        $_SESSION['student_portal_id'] = $student['id'];
        $_SESSION['student_portal_name'] = $student['name'];
        header('Location: dashboard.php');
        exit;
    }
    $error = 'Invalid admission number or password.';
}
?><!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Student Portal — EduDash</title>
<link rel="stylesheet" href="../admin/assets/css/admin.css">
<style>body{background:linear-gradient(135deg,#ecfdf5,#f0f9ff);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}.portal-login{background:#fff;border-radius:20px;padding:40px;max-width:400px;width:100%;box-shadow:0 20px 50px rgba(0,0,0,.08)}.portal-login h1{text-align:center;color:#059669;margin:0 0 8px}.portal-login p{text-align:center;color:#64748b;margin:0 0 24px}</style>
</head><body>
<div class="portal-login">
    <h1><i class="fas fa-graduation-cap"></i> EduDash</h1>
    <p>Student Portal Login</p>
    <?php if ($error): ?><div class="alert-box-error" style="margin-bottom:16px"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <form method="POST">
        <div class="form-field"><label>Admission No</label><input type="text" name="ad_no" class="form-input" required autofocus></div>
        <div class="form-field"><label>Password</label><input type="password" name="password" class="form-input" required></div>
        <button type="submit" class="btn-header-action btn-header-primary" style="width:100%;justify-content:center;margin-top:12px">Login</button>
    </form>
</div>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</body></html>
