<?php
$page_title = $page_title ?? 'Dashboard';
$sa_name = (string) ($_SESSION['sa_name'] ?? 'Super Admin');
$sa_user = (string) ($_SESSION['sa_username'] ?? 'superadmin');
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$planKey = saActivePresetKey($school);
$planLabel = saPlanLabel($school['plan'] ?? 'Custom');
$enabledKeys = getSchoolModuleKeys($pdo, (int) $school['id']);

$saIco = static function (string $path): string {
    return '<span class="nav-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">' . $path . '</svg></span>';
};
$icoDash = $saIco('<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>');
$icoFeat = $saIco('<path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/>');
$icoLicense = $saIco('<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/><circle cx="12" cy="16" r="1"/>');
$icoSettings = $saIco('<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 01-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>');
$icoAdmin = $saIco('<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>');
$icoPortal = $saIco('<path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>');
$icoTeacher = $saIco('<path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>');
$icoSite = $saIco('<circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15 15 0 010 20M12 2a15 15 0 000 20"/>');
$flash = saGetFlash();
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($page_title); ?> | Super Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="assets/css/superadmin.css">
</head>
<body class="sa-body">
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="app sa-app">
    <aside class="sidebar sa-sidebar" id="sidebar">
        <div class="sidebar-brand sa-side-brand">
            <div class="sa-brand-row">
                <span class="sa-brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </span>
                <div class="sa-brand-copy">
                    <div class="brand-text">Super Admin</div>
                    <span class="sa-brand-sub">Platform control</span>
                </div>
            </div>
            <div class="browse-card sa-client-card">
                <div class="browse-card-text">
                    <span class="browse-label">This school</span>
                    <strong class="browse-nav"><?php echo e($school['name']); ?></strong>
                    <span class="sa-plan-chip"><?php echo e($planLabel); ?> plan</span>
                </div>
                <span class="browse-pill sa-pill">SA</span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-head">
                    <span class="nav-section-label">Control</span>
                    <span class="nav-section-line"></span>
                </div>
                <a href="dashboard.php" class="nav-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                    <span class="nav-link-left"><?php echo $icoDash; ?><span class="nav-label">Dashboard</span></span>
                </a>
                <a href="features.php" class="nav-link <?php echo $currentPage === 'features' ? 'active' : ''; ?>">
                    <span class="nav-link-left"><?php echo $icoFeat; ?><span class="nav-label">Plan &amp; Features</span></span>
                </a>
                <a href="license.php" class="nav-link <?php echo $currentPage === 'license' ? 'active' : ''; ?>">
                    <span class="nav-link-left"><?php echo $icoLicense; ?><span class="nav-label">School License</span></span>
                </a>
                <a href="settings.php" class="nav-link <?php echo $currentPage === 'settings' ? 'active' : ''; ?>">
                    <span class="nav-link-left"><?php echo $icoSettings; ?><span class="nav-label">Settings</span></span>
                </a>
            </div>
            <div class="nav-section">
                <div class="nav-section-head">
                    <span class="nav-section-label">Open panels</span>
                    <span class="nav-section-line"></span>
                </div>
                <a href="../admin/index.php" class="nav-link" target="_blank" rel="noopener">
                    <span class="nav-link-left"><?php echo $icoAdmin; ?><span class="nav-label">School Admin</span></span>
                    <span class="sa-ext" aria-hidden="true">↗</span>
                </a>
                <a href="../teacher/index.php" class="nav-link" target="_blank" rel="noopener">
                    <span class="nav-link-left"><?php echo $icoTeacher; ?><span class="nav-label">Teacher Portal</span></span>
                    <span class="sa-ext" aria-hidden="true">↗</span>
                </a>
                <a href="../portal/index.php" class="nav-link" target="_blank" rel="noopener">
                    <span class="nav-link-left"><?php echo $icoPortal; ?><span class="nav-label">Student Portal</span></span>
                    <span class="sa-ext" aria-hidden="true">↗</span>
                </a>
                <a href="../index.php" class="nav-link" target="_blank" rel="noopener">
                    <span class="nav-link-left"><?php echo $icoSite; ?><span class="nav-label">Website</span></span>
                    <span class="sa-ext" aria-hidden="true">↗</span>
                </a>
            </div>
        </nav>

        <div class="sidebar-footer sa-side-foot">
            <div class="sa-side-user">
                <span class="sa-side-avatar"><?php echo e(strtoupper(substr($sa_name, 0, 1))); ?></span>
                <div>
                    <strong><?php echo e($sa_name); ?></strong>
                    <small>@<?php echo e($sa_user); ?></small>
                </div>
            </div>
            <a href="logout.php" class="logout-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Logout
            </a>
        </div>
    </aside>

    <div class="main">
        <header class="topbar sa-topbar">
            <div class="sa-top-left">
                <button class="menu-toggle" id="menuToggle" type="button" aria-label="Menu">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <div class="sa-top-title">
                    <span class="sa-topbar-eyebrow">Platform owner</span>
                    <strong><?php echo e($page_title); ?></strong>
                </div>
            </div>
            <div class="topbar-right sa-top-right">
                <div class="sa-top-meta" title="This school">
                    <span class="sa-top-meta-label">School</span>
                    <strong><?php echo e($school['name']); ?></strong>
                </div>
                <div class="topbar-dropdown sa-account" data-dropdown>
                    <button type="button" class="user-pill sa-user-pill" data-dropdown-toggle aria-expanded="false" aria-haspopup="true">
                        <span class="user-avatar"><?php echo e(strtoupper(substr($sa_name, 0, 1))); ?><span class="online-dot"></span></span>
                        <span class="user-meta">
                            <strong><?php echo e($sa_name); ?></strong>
                            <small>Super Admin</small>
                        </span>
                        <svg class="user-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div class="dropdown-menu dropdown-user sa-user-menu" data-dropdown-menu>
                        <div class="dropdown-user-head">
                            <span class="user-avatar sm"><?php echo e(strtoupper(substr($sa_name, 0, 1))); ?></span>
                            <div>
                                <strong><?php echo e($sa_name); ?></strong>
                                <small>@<?php echo e($sa_user); ?></small>
                                <em class="sa-user-role">Platform owner</em>
                            </div>
                        </div>
                        <div class="sa-user-links">
                            <a href="dashboard.php" class="dropdown-item<?php echo $currentPage === 'dashboard' ? ' is-active' : ''; ?>">
                                <span class="sa-dd-ico"><?php echo $icoDash; ?></span>
                                <span class="sa-dd-copy"><strong>Dashboard</strong><small>Overview &amp; module status</small></span>
                            </a>
                            <a href="features.php" class="dropdown-item<?php echo $currentPage === 'features' ? ' is-active' : ''; ?>">
                                <span class="sa-dd-ico"><?php echo $icoFeat; ?></span>
                                <span class="sa-dd-copy"><strong>Plan &amp; Features</strong><small>Presets and module switches</small></span>
                            </a>
                            <a href="license.php" class="dropdown-item<?php echo $currentPage === 'license' ? ' is-active' : ''; ?>">
                                <span class="sa-dd-ico"><?php echo $icoLicense; ?></span>
                                <span class="sa-dd-copy"><strong>School License</strong><small>Access, start and expiry</small></span>
                            </a>
                            <a href="settings.php" class="dropdown-item<?php echo $currentPage === 'settings' ? ' is-active' : ''; ?>">
                                <span class="sa-dd-ico"><?php echo $icoSettings; ?></span>
                                <span class="sa-dd-copy"><strong>Settings</strong><small>Gateways, backup, security</small></span>
                            </a>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a href="logout.php" class="sa-user-logout">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                            Sign out
                        </a>
                    </div>
                </div>
            </div>
        </header>
        <main class="content">
            <?php if (!empty($_SESSION['sa_must_change'])): ?>
            <div class="sa-force-banner">Change the default Super Admin password before using the rest of the panel.</div>
            <?php endif; ?>
            <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type'] === 'success' ? 'success' : 'error'; ?>"><?php echo e($flash['message']); ?></div>
            <?php endif; ?>
