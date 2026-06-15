<?php
// admin/includes/sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);
$admin_name = htmlspecialchars(ucfirst($_SESSION['admin_username']));
$avatar_url = 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['admin_username']) . '&background=059669&color=fff&bold=true';
?>
<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-brand">
        <div class="sidebar-logo-icon">
            <i class="fas fa-graduation-cap"></i>
        </div>
        <div class="sidebar-brand-text">
            <h2>EduDash</h2>
            <span>Admin Panel</span>
        </div>
    </div>

    <div class="sidebar-user">
        <div class="sidebar-user-avatar">
            <img src="<?php echo $avatar_url; ?>" alt="<?php echo $admin_name; ?>">
            <span class="online-badge"></span>
        </div>
        <div class="sidebar-user-info">
            <h4><?php echo $admin_name; ?></h4>
            <p>Administrator</p>
        </div>
    </div>

    <nav class="sidebar-nav">
        <p class="sidebar-nav-label">Main Menu</p>
        <ul class="sidebar-menu">
            <li class="has-submenu <?php echo ($current_page == 'dashboard.php') ? 'active open' : ''; ?>">
                <a href="dashboard.php" class="submenu-toggle" onclick="return toggleSubmenu(this, event)">
                    <span class="menu-icon-wrap"><i class="fas fa-home menu-icon"></i></span>
                    <span class="menu-text">Dashboard</span>
                    <i class="fas fa-chevron-down chevron"></i>
                </a>
                <ul class="submenu">
                    <li class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>"><a href="dashboard.php">School</a></li>
                    <li><a href="#">Student</a></li>
                    <li><a href="#">Teacher</a></li>
                    <li><a href="#">Parent</a></li>
                    <li><a href="#">LMS</a></li>
                </ul>
            </li>
            <li class="has-submenu <?php echo (in_array($current_page, ['students.php', 'student_add.php', 'student_edit.php', 'student_view.php'])) ? 'active open' : ''; ?>">
                <a href="#" class="submenu-toggle" onclick="return toggleSubmenu(this, event)">
                    <span class="menu-icon-wrap"><i class="fas fa-user-graduate menu-icon"></i></span>
                    <span class="menu-text">Students</span>
                    <i class="fas fa-chevron-down chevron"></i>
                </a>
                <ul class="submenu">
                    <li class="<?php echo ($current_page == 'students.php') ? 'active' : ''; ?>"><a href="students.php">Student List</a></li>
                    <li class="<?php echo ($current_page == 'student_add.php') ? 'active' : ''; ?>"><a href="student_add.php">Add Student</a></li>
                </ul>
            </li>
            <li class="<?php echo ($current_page == 'teachers.php') ? 'active' : ''; ?>">
                <a href="teachers.php">
                    <span class="menu-icon-wrap"><i class="fas fa-chalkboard-teacher menu-icon"></i></span>
                    <span class="menu-text">Teachers</span>
                    <i class="fas fa-chevron-right chevron"></i>
                </a>
            </li>
            <li>
                <a href="#">
                    <span class="menu-icon-wrap"><i class="far fa-user-circle menu-icon"></i></span>
                    <span class="menu-text">Guardian</span>
                    <i class="fas fa-chevron-right chevron"></i>
                </a>
            </li>
            <li>
                <a href="#">
                    <span class="menu-icon-wrap"><i class="fas fa-list-ul menu-icon"></i></span>
                    <span class="menu-text">Classes</span>
                    <i class="fas fa-chevron-right chevron"></i>
                </a>
            </li>
            <li>
                <a href="#">
                    <span class="menu-icon-wrap"><i class="far fa-edit menu-icon"></i></span>
                    <span class="menu-text">Examinations</span>
                    <i class="fas fa-chevron-right chevron"></i>
                </a>
            </li>
            <li>
                <a href="#">
                    <span class="menu-icon-wrap"><i class="fas fa-file-invoice-dollar menu-icon"></i></span>
                    <span class="menu-text">Fees Collection</span>
                    <i class="fas fa-chevron-right chevron"></i>
                </a>
            </li>
            <li>
                <a href="#">
                    <span class="menu-icon-wrap"><i class="far fa-calendar-check menu-icon"></i></span>
                    <span class="menu-text">Attendance</span>
                    <i class="fas fa-chevron-right chevron"></i>
                </a>
            </li>
            <li>
                <a href="#">
                    <span class="menu-icon-wrap"><i class="far fa-clock menu-icon"></i></span>
                    <span class="menu-text">Leaves</span>
                    <i class="fas fa-chevron-right chevron"></i>
                </a>
            </li>
            <li>
                <a href="#">
                    <span class="menu-icon-wrap"><i class="fas fa-certificate menu-icon"></i></span>
                    <span class="menu-text">Certificate</span>
                </a>
            </li>
            <li>
                <a href="#">
                    <span class="menu-icon-wrap"><i class="fas fa-book menu-icon"></i></span>
                    <span class="menu-text">Library</span>
                    <i class="fas fa-chevron-right chevron"></i>
                </a>
            </li>
            <li>
                <a href="#">
                    <span class="menu-icon-wrap"><i class="fas fa-coins menu-icon"></i></span>
                    <span class="menu-text">Accounts</span>
                    <i class="fas fa-chevron-right chevron"></i>
                </a>
            </li>
            <li>
                <a href="#">
                    <span class="menu-icon-wrap"><i class="fas fa-users-cog menu-icon"></i></span>
                    <span class="menu-text">HRM</span>
                    <i class="fas fa-chevron-right chevron"></i>
                </a>
            </li>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <a href="logout.php" class="sidebar-logout">
            <span class="menu-icon-wrap"><i class="fas fa-sign-out-alt menu-icon"></i></span>
            <span class="menu-text">Logout</span>
        </a>
    </div>
</aside>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<script>
function toggleSubmenu(link, e) {
    if (e) e.preventDefault();

    var item = link.parentElement;
    if (!item) return false;

    var isOpen = item.classList.contains('open');
    var items = document.querySelectorAll('.sidebar-menu > li.has-submenu');
    var i;

    for (i = 0; i < items.length; i++) {
        items[i].classList.remove('open');
    }

    if (!isOpen) {
        item.classList.add('open');
    }

    return false;
}
</script>
