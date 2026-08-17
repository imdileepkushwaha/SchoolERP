<?php
// admin/includes/header.php
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/settings_helpers.php';
require_once __DIR__ . '/module_helpers.php';
ensureSuperAdminSchema($pdo);
ensureAdminAuthSchema($pdo);
assertSchoolLicenseActive($pdo);
requirePageModule($pdo);

$page_title = $page_title ?? 'Dashboard';
$adminDisplayName = trim((string) ($_SESSION['admin_name'] ?? ''));
if ($adminDisplayName === '') {
    $adminDisplayName = (string) ($_SESSION['admin_username'] ?? 'Admin');
}
$admin_name = htmlspecialchars($adminDisplayName);
$adminInitials = adminInitials($adminDisplayName);
$adminRoleName = adminRoleLabel(adminRole());
$schoolBrand = getSchoolProfile($pdo);
$brandLogoUrl = schoolSidebarLogoUrl($schoolBrand, 'admin');
$brandFaviconUrl = schoolBrandingUrl($schoolBrand['favicon'] ?? '', 'admin');
$brandTitle = $schoolBrand['name'] ?: 'EduDash';
$adminEmail = trim((string) ($schoolBrand['email'] ?? '')) ?: 'School Admin';
$headerAlerts = [];
try {
    if (adminCanAccessModule($pdo, 'teachers')) {
        $n = (int) $pdo->query("SELECT COUNT(*) FROM leave_requests WHERE status='Pending'")->fetchColumn();
        if ($n > 0) {
            $headerAlerts[] = ['label' => $n . ' pending leave request' . ($n === 1 ? '' : 's'), 'url' => 'leave_requests.php'];
        }
    }
    if (adminCanAccessModule($pdo, 'students')) {
        $n = (int) $pdo->query("SELECT COUNT(*) FROM admission_enquiries WHERE status='New' AND IFNULL(class_sought,'') <> 'Website Contact'")->fetchColumn();
        if ($n > 0) {
            $headerAlerts[] = ['label' => $n . ' new admission enquir' . ($n === 1 ? 'y' : 'ies'), 'url' => 'admission_enquiries.php'];
        }
    }
    if (adminCanAccessModule($pdo, 'website')) {
        $n = (int) $pdo->query("SELECT COUNT(*) FROM admission_enquiries WHERE status='New' AND class_sought='Website Contact'")->fetchColumn();
        if ($n > 0) {
            $headerAlerts[] = ['label' => $n . ' website message' . ($n === 1 ? '' : 's'), 'url' => 'website_enquiries.php'];
        }
    }
} catch (Throwable $e) {
}
$headerAlertCount = count($headerAlerts);
$inboxUrl = adminCanAccessModule($pdo, 'website') ? 'website_enquiries.php' : (adminCanAccessModule($pdo, 'students') ? 'admission_enquiries.php' : 'dashboard.php');
$csrfToken = adminCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - <?php echo htmlspecialchars($brandTitle); ?></title>
    <?php if ($brandFaviconUrl): ?><link rel="icon" href="<?php echo htmlspecialchars($brandFaviconUrl); ?>"><?php endif; ?>
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
                <div class="search-bar" id="globalSearchWrap">
                    <i class="fas fa-search"></i>
                    <input type="text" id="globalSearchInput" placeholder="Search students, teachers, pages..." autocomplete="off">
                    <kbd class="search-shortcut">Ctrl K</kbd>
                    <div class="global-search-dropdown" id="globalSearchDropdown" hidden></div>
                </div>
            </div>

            <div class="header-actions">
                <?php
                $dbBadgeOnline = ($db_active_profile ?? 'offline') === 'online';
                $dbBadgeMode = ucfirst($db_connection_mode ?? 'offline');
                ?>
                <span class="db-header-badge <?php echo $dbBadgeOnline ? 'is-online' : 'is-offline'; ?>" title="Database: <?php echo $dbBadgeOnline ? 'Online' : 'Offline'; ?> · Mode <?php echo htmlspecialchars($dbBadgeMode); ?>">
                    <i class="fas fa-database"></i>
                    <span><?php echo $dbBadgeOnline ? 'Online' : 'Offline'; ?></span>
                </span>
                <div class="header-alerts" data-dropdown>
                    <button type="button" class="header-icon-btn" data-dropdown-toggle aria-label="Alerts">
                        <i class="far fa-bell"></i>
                        <?php if ($headerAlertCount): ?><span class="notification-dot"><?php echo $headerAlertCount > 9 ? '9+' : $headerAlertCount; ?></span><?php endif; ?>
                    </button>
                    <div class="header-alerts-menu" data-dropdown-menu hidden>
                        <?php if ($headerAlerts): foreach ($headerAlerts as $al): ?>
                        <a href="<?php echo htmlspecialchars($al['url']); ?>"><?php echo htmlspecialchars($al['label']); ?></a>
                        <?php endforeach; else: ?>
                        <p>No new alerts</p>
                        <?php endif; ?>
                    </div>
                </div>
                <a href="<?php echo htmlspecialchars($inboxUrl); ?>" class="header-icon-btn" aria-label="Messages">
                    <i class="far fa-envelope"></i>
                </a>

                <div class="header-user" id="headerUserMenu">
                    <button type="button" class="header-user-trigger" id="headerUserTrigger" onclick="return toggleUserMenu(event)">
                        <span class="header-user-avatar"><?php echo $adminInitials; ?></span>
                        <div class="header-user-info">
                            <span class="header-user-name"><?php echo $admin_name; ?></span>
                            <span class="header-user-role"><?php echo htmlspecialchars($adminRoleName); ?></span>
                        </div>
                        <i class="fas fa-chevron-down header-user-chevron"></i>
                    </button>
                    <div class="header-user-dropdown">
                        <div class="dropdown-header">
                            <span class="header-user-avatar sm"><?php echo $adminInitials; ?></span>
                            <div>
                                <strong><?php echo $admin_name; ?></strong>
                                <span><?php echo htmlspecialchars($adminEmail); ?></span>
                            </div>
                        </div>
                        <ul class="dropdown-menu">
                            <li><a href="profile.php"><i class="fas fa-user"></i> My Profile</a></li>
                            <li><a href="settings.php?tab=password"><i class="fas fa-lock"></i> Password</a></li>
                            <?php if (adminCanManageSchool()): ?>
                            <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                            <?php endif; ?>
                            <?php if (moduleEnabled($pdo, 'website')): ?>
                            <li><a href="../index.php" target="_blank" rel="noopener"><i class="fas fa-globe"></i> View Website</a></li>
                            <?php endif; ?>
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
            var searchWrap = document.getElementById('globalSearchWrap');
            if (searchWrap && !searchWrap.contains(e.target)) {
                var dd = document.getElementById('globalSearchDropdown');
                if (dd) dd.hidden = true;
            }
        });

        (function () {
            var input = document.getElementById('globalSearchInput');
            var dropdown = document.getElementById('globalSearchDropdown');
            if (!input || !dropdown) return;
            var timer = null;
            function render(items) {
                if (!items.length) {
                    dropdown.innerHTML = '<div class="global-search-empty">No results</div>';
                    dropdown.hidden = false;
                    return;
                }
                dropdown.innerHTML = items.map(function (it) {
                    return '<a class="global-search-item" href="' + it.url + '"><i class="fas ' + it.icon + '"></i><div><strong>' + it.title + '</strong><span>' + it.type + ' · ' + it.meta + '</span></div></a>';
                }).join('');
                dropdown.hidden = false;
            }
            input.addEventListener('input', function () {
                clearTimeout(timer);
                var q = input.value.trim();
                if (q.length < 2) { dropdown.hidden = true; return; }
                timer = setTimeout(function () {
                    fetch('search.php?q=' + encodeURIComponent(q))
                        .then(function (r) { return r.json(); })
                        .then(function (d) { render(d.results || []); })
                        .catch(function () {
                            dropdown.innerHTML = '<div class="global-search-empty">Search unavailable</div>';
                            dropdown.hidden = false;
                        });
                }, 250);
            });
            document.addEventListener('keydown', function (e) {
                if ((e.ctrlKey || e.metaKey) && e.key === 'k') { e.preventDefault(); input.focus(); }
            });
        })();

        (function () {
            function ready(fn) {
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', fn);
                } else {
                    fn();
                }
            }
            ready(function () {
                var tok = <?php echo json_encode($csrfToken); ?>;
                document.querySelectorAll('form[method="post"], form[method="POST"]').forEach(function (form) {
                    if (form.querySelector('input[name="admin_csrf"]')) return;
                    var i = document.createElement('input');
                    i.type = 'hidden';
                    i.name = 'admin_csrf';
                    i.value = tok;
                    form.appendChild(i);
                });
                document.querySelectorAll('[data-dropdown]').forEach(function (wrap) {
                    var btn = wrap.querySelector('[data-dropdown-toggle]');
                    var menu = wrap.querySelector('[data-dropdown-menu]');
                    if (!btn || !menu) return;
                    btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        var willOpen = menu.hidden;
                        document.querySelectorAll('[data-dropdown-menu]').forEach(function (m) { m.hidden = true; });
                        menu.hidden = !willOpen;
                    });
                });
                document.addEventListener('click', function () {
                    document.querySelectorAll('[data-dropdown-menu]').forEach(function (m) { m.hidden = true; });
                });
            });
        })();
        </script>

        <div class="admin-content">
            <?php if (!empty($_SESSION['admin_must_change'])): ?>
            <div class="admin-force-banner">Change the default admin password before using the rest of the panel.</div>
            <?php endif; ?>
