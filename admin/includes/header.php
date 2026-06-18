<?php
// admin/includes/header.php
require_once __DIR__ . '/init.php';

$page_title = $page_title ?? 'Dashboard';
$admin_name = htmlspecialchars(ucfirst($_SESSION['admin_username']));
$avatar_url = 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['admin_username']) . '&background=059669&color=fff&bold=true';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - EduDash</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
</head>
<body>
<div class="admin-wrapper">
    <?php include 'sidebar.php'; ?>

    <main class="admin-main">
        <header class="admin-header">
            <div class="header-left">
                <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="header-breadcrumb">
                    <span class="breadcrumb-root">Admin</span>
                    <i class="fas fa-chevron-right"></i>
                    <span class="breadcrumb-current"><?php echo htmlspecialchars($page_title); ?></span>
                </div>
            </div>

            <div class="header-center">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search students, teachers, classes...">
                    <kbd class="search-shortcut">Ctrl K</kbd>
                </div>
            </div>

            <div class="header-actions">
                <button type="button" class="header-icon-btn" aria-label="Notifications">
                    <i class="far fa-bell"></i>
                    <span class="notification-dot"></span>
                </button>
                <button type="button" class="header-icon-btn" aria-label="Messages">
                    <i class="far fa-envelope"></i>
                </button>

                <div class="header-user" id="headerUserMenu">
                    <button type="button" class="header-user-trigger" id="headerUserTrigger" onclick="return toggleUserMenu(event)">
                        <img src="<?php echo $avatar_url; ?>" alt="<?php echo $admin_name; ?>">
                        <div class="header-user-info">
                            <span class="header-user-name"><?php echo $admin_name; ?></span>
                            <span class="header-user-role">Administrator</span>
                        </div>
                        <i class="fas fa-chevron-down header-user-chevron"></i>
                    </button>
                    <div class="header-user-dropdown">
                        <div class="dropdown-header">
                            <img src="<?php echo $avatar_url; ?>" alt="<?php echo $admin_name; ?>">
                            <div>
                                <strong><?php echo $admin_name; ?></strong>
                                <span>admin@schoolerp.com</span>
                            </div>
                        </div>
                        <ul class="dropdown-menu">
                            <li><a href="#"><i class="fas fa-user"></i> My Profile</a></li>
                            <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                            <li><a href="../index.php"><i class="fas fa-globe"></i> View Website</a></li>
                        </ul>
                        <div class="dropdown-footer">
                            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <script>
        function toggleUserMenu(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }

            var menu = document.getElementById('headerUserMenu');
            if (!menu) return false;

            menu.classList.toggle('open');
            return false;
        }

        document.addEventListener('click', function (e) {
            var menu = document.getElementById('headerUserMenu');
            if (menu && !menu.contains(e.target)) {
                menu.classList.remove('open');
            }
        });
        </script>

        <div class="admin-content">
