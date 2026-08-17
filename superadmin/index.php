<?php
session_start();
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/../admin/includes/db_install_helpers.php';

date_default_timezone_set('Asia/Kolkata');

$dbConnection = connectDatabase(['soft_fail' => true]);
$pdo = $dbConnection['pdo'] ?? null;
$db_active_profile = $dbConnection['profile'] ?? '';
$db_connection_mode = $dbConnection['mode'] ?? 'auto';
$db_environment = $dbConnection['environment'] ?? (isLocalEnvironment() ? 'local' : 'server');

$error = '';
$setupMessage = '';
$old_username = '';
$dbInstalled = false;

if ($pdo) {
    $dbInstalled = isDatabaseInstalled($pdo);
    if ($dbInstalled) {
        ensureSuperAdminSchema($pdo);
    }
}

if (!empty($_SESSION['sa_logged_in']) && $dbInstalled) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'db_setup') {
    if (!saCsrfVerify()) {
        $error = 'Session expired. Please try again.';
    } else {
        $setupResult = runDatabaseSetup();
        if (!empty($setupResult['ok'])) {
            $setupMessage = $setupResult['message'] ?? 'Database setup complete.';
            if (empty($setupResult['already_installed'])) {
                $setupMessage .= ' Super Admin login: superadmin / admin123. School Admin: admin / admin123.';
            }
            $dbConnection = connectDatabase(['soft_fail' => true]);
            $pdo = $dbConnection['pdo'] ?? null;
            $db_active_profile = $dbConnection['profile'] ?? '';
            if ($pdo) {
                ensureSuperAdminSchema($pdo);
                $dbInstalled = isDatabaseInstalled($pdo);
            }
        } else {
            $error = $setupResult['error'] ?? 'Database setup failed.';
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_username = trim($_POST['username'] ?? '');
    if (!saCsrfVerify()) {
        $error = 'Session expired. Please try again.';
    } elseif (!$pdo) {
        $error = 'Database is not connected. Start MySQL, then run Database Setup.';
    } elseif (!$dbInstalled) {
        $error = 'Database tables are not installed yet. Run Database Setup first.';
    } elseif (saLoginIsLocked($pdo)) {
        $error = 'Too many failed sign-ins. Wait 15 minutes and try again.';
        saLogActivity($pdo, 'login_failed', 'Locked out: ' . $old_username, ['username' => $old_username]);
    } else {
        $user = saAuthUser($pdo, $old_username, (string) ($_POST['password'] ?? ''));
        if ($user) {
            saClearLoginAttempts($pdo);
            session_regenerate_id(true);
            $_SESSION['sa_logged_in'] = true;
            $_SESSION['sa_id'] = $user['id'];
            $_SESSION['sa_username'] = $user['username'];
            $_SESSION['sa_name'] = $user['name'] ?: $user['username'];
            $_SESSION['sa_must_change'] = saUserMustChangePassword($user);
            saLogActivity($pdo, 'login', 'Signed in to Super Admin.', $user);
            header('Location: ' . (!empty($_SESSION['sa_must_change']) ? 'settings.php?tab=security' : 'dashboard.php'));
            exit;
        }
        saRecordLoginAttempt($pdo, $old_username);
        saLogActivity($pdo, 'login_failed', 'Failed sign-in for "' . $old_username . '".', ['username' => $old_username]);
        $error = 'Invalid username or password.';
    }
}

$schoolName = 'this school';
if ($pdo && $dbInstalled) {
    try {
        $school = getCurrentSaSchool($pdo);
        if ($school && !empty($school['name'])) {
            $schoolName = $school['name'];
        }
    } catch (Throwable $e) {
    }
}

$dbStatusLabel = $pdo
    ? (($db_active_profile === 'online' ? 'Online' : 'Offline') . ' DB')
    : 'Not Connected';
$dbSetupTarget = getSetupProfileKey() === 'online' ? 'Online (Server)' : 'Local (XAMPP)';
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Login | SchoolERP</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="assets/css/superadmin.css">
</head>
<body class="auth-page sa-auth">
<div class="auth-shell">
    <div class="auth-brand sa-brand" aria-hidden="false">
        <div class="auth-brand-orb auth-brand-orb-a"></div>
        <div class="auth-brand-orb auth-brand-orb-b"></div>
        <div class="auth-brand-inner">
            <div class="auth-mark">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            </div>
            <p class="auth-kicker">Platform Control</p>
            <h1 class="auth-company">Super Admin</h1>
            <p class="auth-tagline">Configure plan mode, modules and what this school's Admin, Teacher and Student panels can use.</p>
        </div>
    </div>

    <div class="auth-panel">
        <div class="auth-card">
            <div class="auth-card-head">
                <h2>Super Admin</h2>
                <p class="auth-sub"><?php echo $dbInstalled ? 'Sign in to manage this install · ' . e($schoolName) : 'First-time setup — install the database for this school'; ?></p>
            </div>

            <p class="sa-db-meta"><?php echo e($dbStatusLabel); ?> · <?php echo $db_environment === 'local' ? 'Local (XAMPP)' : 'Live Server'; ?> · Mode <?php echo e(strtoupper((string) $db_connection_mode)); ?></p>

            <?php if ($setupMessage): ?><div class="alert alert-success auth-alert"><?php echo e($setupMessage); ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-error auth-alert"><?php echo e($error); ?></div><?php endif; ?>

            <?php if (!$pdo): ?>
            <div class="alert alert-error auth-alert">MySQL is not connected. Start XAMPP MySQL (or check online credentials), then run Database Setup.</div>
            <?php endif; ?>

            <?php if (!$dbInstalled): ?>
            <div class="sa-setup-box" id="loginInstallCard">
                <p class="sa-setup-text">Create the database and all ERP tables on <strong><?php echo e($dbSetupTarget); ?></strong>. This also creates the Super Admin and School Admin logins.</p>
                <form method="post" id="dbSetupForm">
                    <?php echo saCsrfField(); ?>
                    <input type="hidden" name="action" value="db_setup">
                    <button type="submit" class="sa-setup-btn" id="dbSetupBtn">Run Database Setup</button>
                </form>
                <p class="sa-hint" style="margin-top:.85rem;margin-bottom:0">After setup, sign in here as <code>superadmin</code> / <code>admin123</code></p>
            </div>
            <?php else: ?>
            <form method="post" class="auth-form" autocomplete="off">
                <?php echo saCsrfField(); ?>
                <div class="auth-field">
                    <label for="username">Username</label>
                    <div class="auth-input">
                        <span class="auth-input-ico" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </span>
                        <input type="text" id="username" name="username" value="<?php echo e($old_username); ?>" placeholder="superadmin" required autofocus>
                    </div>
                </div>
                <div class="auth-field">
                    <label for="password">Password</label>
                    <div class="auth-input password-field">
                        <span class="auth-input-ico" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                        </span>
                        <input type="password" id="password" name="password" placeholder="Enter password" required>
                        <button type="button" class="password-toggle" data-password-toggle aria-label="Show password" title="Show password">
                            <svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-8 11-8 11 8 11 8-4 8 11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block auth-submit sa-submit">
                    <span>Sign in</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M5 12h14"/><path d="M13 6l6 6-6 6"/></svg>
                </button>
            </form>
            <p class="sa-hint">Use a strong password. Default credentials are disabled after first change.</p>
            <?php endif; ?>

            <p class="sa-hint"><a href="../admin/index.php">Open School Admin login →</a></p>
        </div>
    </div>
</div>
<script>
document.querySelectorAll('[data-password-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var wrap = btn.closest('.password-field');
        var input = wrap && wrap.querySelector('input');
        if (!input) return;
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.classList.toggle('is-visible', show);
        btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        btn.setAttribute('title', show ? 'Hide password' : 'Show password');
    });
});
var dbSetupForm = document.getElementById('dbSetupForm');
if (dbSetupForm) {
    dbSetupForm.addEventListener('submit', function () {
        var btn = document.getElementById('dbSetupBtn');
        if (!btn) return;
        btn.disabled = true;
        btn.textContent = 'Installing tables…';
    });
}
</script>
</body>
</html>
