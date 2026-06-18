<?php
// Expects: $page_title, $page_subtitle (optional)
$page_title = $page_title ?? 'Dashboard';
$page_subtitle = $page_subtitle ?? '';
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> — Teacher Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/teacher-portal.css">
</head>
<body class="tp-body">
<div class="tp-overlay" id="tpOverlay"></div>
<div class="tp-wrapper">
    <aside class="tp-sidebar" id="tpSidebar">
        <div class="tp-brand">
            <div class="tp-brand-icon"><i class="fas fa-chalkboard-teacher"></i></div>
            <div>
                <h2>EduDash</h2>
                <span>Teacher Portal</span>
            </div>
        </div>
        <div class="tp-user-card">
            <img src="<?php echo htmlspecialchars($tp_photo); ?>" alt="<?php echo $tp_name; ?>">
            <div>
                <strong><?php echo $tp_name; ?></strong>
                <small><?php echo $tp_emp_id; ?></small>
            </div>
        </div>
        <nav class="tp-nav">
            <p class="tp-nav-label">Main</p>
            <a href="dashboard.php" class="<?php echo tp_nav_active('dashboard'); ?>"><i class="fas fa-home"></i> Dashboard</a>
            <a href="profile.php" class="<?php echo tp_nav_active('profile'); ?>"><i class="fas fa-user"></i> My Profile</a>
            <a href="timetable.php" class="<?php echo tp_nav_active('timetable'); ?>"><i class="fas fa-calendar-alt"></i> Timetable</a>
            <p class="tp-nav-label">Teaching</p>
            <a href="my-classes.php" class="<?php echo tp_nav_active('my-classes'); ?>"><i class="fas fa-users"></i> My Classes</a>
            <a href="attendance.php" class="<?php echo tp_nav_active('attendance'); ?>"><i class="far fa-calendar-check"></i> Mark Attendance</a>
            <a href="homework.php" class="<?php echo tp_nav_active('homework'); ?>"><i class="fas fa-book-open"></i> Homework</a>
            <p class="tp-nav-label">Account</p>
            <a href="change-password.php" class="<?php echo tp_nav_active('change-password'); ?>"><i class="fas fa-lock"></i> Change Password</a>
        </nav>
        <div class="tp-sidebar-footer">
            <a href="logout.php" class="tp-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </aside>
    <div class="tp-main">
        <header class="tp-topbar">
            <div style="display:flex;align-items:center;gap:12px">
                <button type="button" class="tp-menu-toggle" id="tpMenuToggle" aria-label="Menu"><i class="fas fa-bars"></i></button>
                <div>
                    <h1><?php echo htmlspecialchars($page_title); ?></h1>
                    <?php if ($page_subtitle): ?><p><?php echo htmlspecialchars($page_subtitle); ?></p><?php endif; ?>
                </div>
            </div>
            <div class="tp-topbar-actions">
                <a href="profile.php" class="tp-btn tp-btn-outline"><i class="fas fa-user"></i> Profile</a>
            </div>
        </header>
        <main class="tp-content">
