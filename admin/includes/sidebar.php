<?php
// admin/includes/sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);
$sidebarName = htmlspecialchars(trim((string) ($_SESSION['admin_name'] ?? '')) ?: (string) ($_SESSION['admin_username'] ?? 'Admin'));
$sidebarInitials = adminInitials(trim((string) ($_SESSION['admin_name'] ?? '')) ?: (string) ($_SESSION['admin_username'] ?? 'A'));
$sidebarRole = adminRoleLabel(adminRole());

$student_pages = ['students.php', 'student_add.php', 'student_edit.php', 'student_view.php', 'student_suspend.php', 'student_categories.php', 'student_import.php', 'student_promote.php', 'student_promote_advanced.php', 'student_id_card.php', 'id_cards.php', 'student_documents.php', 'classes.php', 'portal_accounts.php', 'admission_enquiries.php', 'website_enquiries.php'];
$academic_pages = ['academic_sessions.php', 'subjects.php', 'class_timetable.php', 'notices.php', 'homework.php'];
$attendance_pages = ['attendance.php', 'attendance_report.php'];
$fee_pages = ['fees.php', 'fee_collect.php', 'fee_receipt.php', 'fee_reports.php', 'teacher_salary.php'];
$exam_pages = ['exams.php', 'marks.php', 'report_card.php', 'report_cards.php', 'exam_analytics.php'];
$teacher_pages = ['teachers.php', 'teacher_add.php', 'teacher_edit.php', 'teacher_view.php', 'teacher_timetable.php', 'teacher_portal_accounts.php', 'teacher_attendance.php', 'leave_requests.php', 'teacher_salary.php'];
$settings_pages = ['settings.php', 'profile.php'];
$mod = static function ($key) use ($pdo) {
    return adminCanAccessModule($pdo, $key);
};
$canSalary = adminCanSalary($pdo);
$showSalaryInTeachers = $canSalary && $mod('teachers');
$showSalaryInFees = $canSalary && !$mod('teachers') && $mod('fees');
$showSalaryStandalone = $canSalary && !$mod('teachers') && !$mod('fees');
?>
<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-brand">
        <div class="sidebar-logo-icon<?php echo $brandLogoUrl ? ' has-logo' : ''; ?>">
            <?php if ($brandLogoUrl): ?>
            <img src="<?php echo htmlspecialchars($brandLogoUrl); ?>" alt="<?php echo htmlspecialchars($brandTitle); ?>" class="sidebar-logo-img">
            <?php else: ?>
            <i class="fas fa-graduation-cap"></i>
            <?php endif; ?>
        </div>
        <div class="sidebar-brand-text">
            <h2><?php echo htmlspecialchars($brandTitle); ?></h2>
            <span>Admin Panel</span>
        </div>
    </div>

    <div class="sidebar-user">
        <div class="sidebar-user-avatar">
            <span class="header-user-avatar sidebar-initials"><?php echo $sidebarInitials; ?></span>
            <span class="online-badge"></span>
        </div>
        <div class="sidebar-user-info">
            <h4><?php echo $sidebarName; ?></h4>
            <p><?php echo htmlspecialchars($sidebarRole); ?></p>
        </div>
    </div>

    <nav class="sidebar-nav">
        <p class="sidebar-nav-label">Main Menu</p>
        <ul class="sidebar-menu">
            <li class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                <a href="dashboard.php">
                    <span class="menu-icon-wrap"><i class="fas fa-home menu-icon"></i></span>
                    <span class="menu-text">Dashboard</span>
                </a>
            </li>
            <?php if ($mod('students')): ?>
            <li class="has-submenu <?php echo (in_array($current_page, $student_pages)) ? 'active open' : ''; ?>">
                <a href="#" class="submenu-toggle" onclick="return toggleSubmenu(this, event)">
                    <span class="menu-icon-wrap"><i class="fas fa-user-graduate menu-icon"></i></span>
                    <span class="menu-text">Students</span>
                    <i class="fas fa-chevron-down chevron"></i>
                </a>
                <ul class="submenu">
                    <li class="<?php echo ($current_page == 'student_add.php') ? 'active' : ''; ?>"><a href="student_add.php">Add New Student</a></li>
                    <li class="<?php echo ($current_page == 'students.php') ? 'active' : ''; ?>"><a href="students.php">Student List</a></li>
                    <li class="<?php echo ($current_page == 'classes.php') ? 'active' : ''; ?>"><a href="classes.php">Classes & Sections</a></li>
                    <li class="<?php echo ($current_page == 'id_cards.php' || $current_page == 'student_id_card.php') ? 'active' : ''; ?>"><a href="id_cards.php">ID Cards</a></li>
                    <li class="<?php echo ($current_page == 'student_documents.php') ? 'active' : ''; ?>"><a href="student_documents.php">Documents</a></li>
                    <li class="<?php echo ($current_page == 'student_promote.php') ? 'active' : ''; ?>"><a href="student_promote.php">Promote (Bulk)</a></li>
                    <li class="<?php echo ($current_page == 'student_promote_advanced.php') ? 'active' : ''; ?>"><a href="student_promote_advanced.php">Promote (Selected)</a></li>
                    <li class="<?php echo ($current_page == 'student_import.php') ? 'active' : ''; ?>"><a href="student_import.php">Import</a></li>
                    <li class="<?php echo ($current_page == 'student_categories.php') ? 'active' : ''; ?>"><a href="student_categories.php">Categories</a></li>
                    <li class="<?php echo ($current_page == 'student_suspend.php') ? 'active' : ''; ?>"><a href="student_suspend.php">Suspend</a></li>
                    <?php if ($mod('student_portal')): ?>
                    <li class="<?php echo ($current_page == 'portal_accounts.php') ? 'active' : ''; ?>"><a href="portal_accounts.php">Student Portal</a></li>
                    <?php endif; ?>
                    <li class="<?php echo ($current_page == 'admission_enquiries.php') ? 'active' : ''; ?>"><a href="admission_enquiries.php">Admission Enquiries</a></li>
                    <?php if ($mod('website')): ?>
                    <li class="<?php echo ($current_page == 'website_enquiries.php') ? 'active' : ''; ?>"><a href="website_enquiries.php">Website Enquiries</a></li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php elseif ($mod('website')): ?>
            <li class="<?php echo ($current_page == 'website_enquiries.php') ? 'active' : ''; ?>">
                <a href="website_enquiries.php">
                    <span class="menu-icon-wrap"><i class="fas fa-globe menu-icon"></i></span>
                    <span class="menu-text">Website Enquiries</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if ($mod('academic')): ?>
            <li class="has-submenu <?php echo (in_array($current_page, $academic_pages)) ? 'active open' : ''; ?>">
                <a href="#" class="submenu-toggle" onclick="return toggleSubmenu(this, event)">
                    <span class="menu-icon-wrap"><i class="fas fa-book-open menu-icon"></i></span>
                    <span class="menu-text">Academic</span>
                    <i class="fas fa-chevron-down chevron"></i>
                </a>
                <ul class="submenu">
                    <li class="<?php echo ($current_page == 'academic_sessions.php') ? 'active' : ''; ?>"><a href="academic_sessions.php">Sessions</a></li>
                    <li class="<?php echo ($current_page == 'subjects.php') ? 'active' : ''; ?>"><a href="subjects.php">Subjects</a></li>
                    <li class="<?php echo ($current_page == 'class_timetable.php') ? 'active' : ''; ?>"><a href="class_timetable.php">Class Timetable</a></li>
                    <li class="<?php echo ($current_page == 'notices.php') ? 'active' : ''; ?>"><a href="notices.php">Notice Board</a></li>
                    <li class="<?php echo ($current_page == 'homework.php') ? 'active' : ''; ?>"><a href="homework.php">Homework</a></li>
                </ul>
            </li>
            <?php endif; ?>
            <?php if ($mod('attendance')): ?>
            <li class="has-submenu <?php echo (in_array($current_page, $attendance_pages)) ? 'active open' : ''; ?>">
                <a href="#" class="submenu-toggle" onclick="return toggleSubmenu(this, event)">
                    <span class="menu-icon-wrap"><i class="far fa-calendar-check menu-icon"></i></span>
                    <span class="menu-text">Attendance</span>
                    <i class="fas fa-chevron-down chevron"></i>
                </a>
                <ul class="submenu">
                    <li class="<?php echo ($current_page == 'attendance.php') ? 'active' : ''; ?>"><a href="attendance.php">Mark Attendance</a></li>
                    <li class="<?php echo ($current_page == 'attendance_report.php') ? 'active' : ''; ?>"><a href="attendance_report.php">Monthly Report</a></li>
                </ul>
            </li>
            <?php endif; ?>
            <?php if ($mod('fees')): ?>
            <li class="has-submenu <?php echo (in_array($current_page, $fee_pages)) ? 'active open' : ''; ?>">
                <a href="#" class="submenu-toggle" onclick="return toggleSubmenu(this, event)">
                    <span class="menu-icon-wrap"><i class="fas fa-file-invoice-dollar menu-icon"></i></span>
                    <span class="menu-text">Fees</span>
                    <i class="fas fa-chevron-down chevron"></i>
                </a>
                <ul class="submenu">
                    <li class="<?php echo ($current_page == 'fees.php') ? 'active' : ''; ?>"><a href="fees.php">Fee Structure</a></li>
                    <li class="<?php echo ($current_page == 'fee_collect.php') ? 'active' : ''; ?>"><a href="fee_collect.php">Collect Fee</a></li>
                    <li class="<?php echo ($current_page == 'fee_reports.php') ? 'active' : ''; ?>"><a href="fee_reports.php">Fee Reports</a></li>
                    <?php if ($showSalaryInFees): ?>
                    <li class="<?php echo ($current_page == 'teacher_salary.php') ? 'active' : ''; ?>"><a href="teacher_salary.php">Staff Salary</a></li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php endif; ?>
            <?php if ($mod('exams')): ?>
            <li class="has-submenu <?php echo (in_array($current_page, $exam_pages)) ? 'active open' : ''; ?>">
                <a href="#" class="submenu-toggle" onclick="return toggleSubmenu(this, event)">
                    <span class="menu-icon-wrap"><i class="far fa-edit menu-icon"></i></span>
                    <span class="menu-text">Examinations</span>
                    <i class="fas fa-chevron-down chevron"></i>
                </a>
                <ul class="submenu">
                    <li class="<?php echo ($current_page == 'exams.php') ? 'active' : ''; ?>"><a href="exams.php">Manage Exams</a></li>
                    <li class="<?php echo ($current_page == 'marks.php') ? 'active' : ''; ?>"><a href="marks.php">Enter Marks</a></li>
                    <li class="<?php echo ($current_page == 'report_cards.php' || $current_page == 'report_card.php') ? 'active' : ''; ?>"><a href="report_cards.php">Report Cards</a></li>
                    <li class="<?php echo ($current_page == 'exam_analytics.php') ? 'active' : ''; ?>"><a href="exam_analytics.php">Result Analytics</a></li>
                </ul>
            </li>
            <?php endif; ?>
            <?php if ($mod('certificates')): ?>
            <li class="<?php echo ($current_page == 'certificates.php' || $current_page == 'certificate_print.php') ? 'active' : ''; ?>">
                <a href="certificates.php">
                    <span class="menu-icon-wrap"><i class="fas fa-certificate menu-icon"></i></span>
                    <span class="menu-text">Certificates</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if ($mod('transport')): ?>
            <li class="<?php echo ($current_page == 'transport.php') ? 'active' : ''; ?>">
                <a href="transport.php">
                    <span class="menu-icon-wrap"><i class="fas fa-bus menu-icon"></i></span>
                    <span class="menu-text">Transport</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if ($mod('hostel')): ?>
            <li class="<?php echo ($current_page == 'hostel.php') ? 'active' : ''; ?>">
                <a href="hostel.php">
                    <span class="menu-icon-wrap"><i class="fas fa-bed menu-icon"></i></span>
                    <span class="menu-text">Hostel</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if ($mod('library')): ?>
            <li class="<?php echo ($current_page == 'library.php') ? 'active' : ''; ?>">
                <a href="library.php">
                    <span class="menu-icon-wrap"><i class="fas fa-book menu-icon"></i></span>
                    <span class="menu-text">Library</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if ($showSalaryStandalone): ?>
            <li class="<?php echo ($current_page == 'teacher_salary.php') ? 'active' : ''; ?>">
                <a href="teacher_salary.php">
                    <span class="menu-icon-wrap"><i class="fas fa-money-check-alt menu-icon"></i></span>
                    <span class="menu-text">Staff Salary</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if ($mod('notifications')): ?>
            <li class="<?php echo ($current_page == 'notifications.php') ? 'active' : ''; ?>">
                <a href="notifications.php">
                    <span class="menu-icon-wrap"><i class="fas fa-bell menu-icon"></i></span>
                    <span class="menu-text">SMS / WhatsApp</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if ($mod('teachers')): ?>
            <li class="has-submenu <?php echo (in_array($current_page, $teacher_pages)) ? 'active open' : ''; ?>">
                <a href="#" class="submenu-toggle" onclick="return toggleSubmenu(this, event)">
                    <span class="menu-icon-wrap"><i class="fas fa-chalkboard-teacher menu-icon"></i></span>
                    <span class="menu-text">Teachers</span>
                    <i class="fas fa-chevron-down chevron"></i>
                </a>
                <ul class="submenu submenu-tree">
                    <li class="<?php echo ($current_page == 'teacher_add.php') ? 'active' : ''; ?>"><a href="teacher_add.php">Add New Teacher</a></li>
                    <li class="<?php echo ($current_page == 'teachers.php') ? 'active' : ''; ?>"><a href="teachers.php">Teacher List</a></li>
                    <li class="<?php echo ($current_page == 'teacher_timetable.php') ? 'active' : ''; ?>"><a href="teacher_timetable.php">Teacher Timetable</a></li>
                    <?php if ($mod('teacher_portal')): ?>
                    <li class="<?php echo ($current_page == 'teacher_portal_accounts.php') ? 'active' : ''; ?>"><a href="teacher_portal_accounts.php">Teacher Portal</a></li>
                    <?php endif; ?>
                    <li class="<?php echo ($current_page == 'teacher_attendance.php') ? 'active' : ''; ?>"><a href="teacher_attendance.php">Teacher Attendance</a></li>
                    <li class="<?php echo ($current_page == 'leave_requests.php') ? 'active' : ''; ?>"><a href="leave_requests.php">Leave Requests</a></li>
                    <?php if ($showSalaryInTeachers): ?>
                    <li class="<?php echo ($current_page == 'teacher_salary.php') ? 'active' : ''; ?>"><a href="teacher_salary.php">Staff Salary</a></li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php endif; ?>
            <li class="<?php echo (in_array($current_page, $settings_pages, true)) ? 'active' : ''; ?>">
                <a href="<?php echo adminCanManageSchool() ? 'settings.php' : 'settings.php?tab=password'; ?>">
                    <span class="menu-icon-wrap"><i class="fas fa-cog menu-icon"></i></span>
                    <span class="menu-text">Settings</span>
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
