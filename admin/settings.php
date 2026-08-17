<?php
$page_title = "Settings";
require_once 'includes/init.php';
require_once '../includes/db_connect.php';
require_once 'includes/settings_helpers.php';

ensureSettingsSchema($pdo);
ensureAdminAuthSchema($pdo);

$canManage = adminCanManageSchool();
$activeTab = $_GET['tab'] ?? ($canManage ? 'school' : 'password');
$allowedTabs = $canManage
    ? ['school', 'signatures', 'gallery', 'staff', 'activity', 'password']
    : ['password'];
$movedTabs = ['email', 'sms', 'whatsapp', 'database'];
if (in_array($activeTab, $movedTabs, true)) {
    $_SESSION['error_msg'] = 'Email, SMS, WhatsApp and Database settings are managed in SuperAdmin.';
    header('Location: settings.php?tab=' . ($canManage ? 'school' : 'password'));
    exit;
}
if (!in_array($activeTab, $allowedTabs, true)) {
    $activeTab = $canManage ? 'school' : 'password';
}

$school = getSchoolProfile($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $manageActions = ['save_school', 'save_signature', 'delete_signature', 'default_signature', 'add_gallery', 'delete_gallery', 'save_staff', 'delete_staff'];
    if (in_array($action, $manageActions, true) && !$canManage) {
        $_SESSION['error_msg'] = 'Your login role cannot change school settings.';
        header('Location: settings.php?tab=password');
        exit;
    }

    if ($action === 'save_school') {
        $profile = [
            'name' => $_POST['school_name'] ?? '',
            'tagline' => $_POST['school_tagline'] ?? '',
            'address' => $_POST['school_address'] ?? '',
            'phone' => $_POST['school_phone'] ?? '',
            'email' => $_POST['school_email'] ?? '',
            'website' => $_POST['school_website'] ?? '',
            'principal' => $_POST['school_principal'] ?? '',
            'affiliation' => $_POST['school_affiliation'] ?? '',
        ];
        $current = getSchoolProfile($pdo);
        $profile['logo'] = $current['logo'];
        $profile['logo_light'] = $current['logo_light'];
        $profile['logo_icon'] = $current['logo_icon'];
        $profile['favicon'] = $current['favicon'];

        if (!empty($_POST['remove_logo'])) {
            deleteSchoolBrandingFile($profile['logo']);
            $profile['logo'] = '';
        } elseif (!empty($_FILES['school_logo']['name'])) {
            $uploaded = uploadSchoolBrandingFile($_FILES['school_logo'], 'logo');
            if ($uploaded === false) {
                $_SESSION['error_msg'] = 'Logo upload failed. Use JPG, PNG, WEBP or GIF (max 2MB).';
                header('Location: settings.php?tab=school');
                exit;
            }
            if ($uploaded) {
                deleteSchoolBrandingFile($current['logo']);
                $profile['logo'] = $uploaded;
            }
        }

        if (!empty($_POST['remove_logo_light'])) {
            deleteSchoolBrandingFile($profile['logo_light']);
            $profile['logo_light'] = '';
        } elseif (!empty($_FILES['school_logo_light']['name'])) {
            $uploaded = uploadSchoolBrandingFile($_FILES['school_logo_light'], 'logo_light');
            if ($uploaded === false) {
                $_SESSION['error_msg'] = 'Light logo upload failed. Use JPG, PNG, WEBP or GIF (max 2MB).';
                header('Location: settings.php?tab=school');
                exit;
            }
            if ($uploaded) {
                deleteSchoolBrandingFile($current['logo_light']);
                $profile['logo_light'] = $uploaded;
            }
        }

        if (!empty($_POST['remove_logo_icon'])) {
            deleteSchoolBrandingFile($profile['logo_icon']);
            $profile['logo_icon'] = '';
        } elseif (!empty($_FILES['school_logo_icon']['name'])) {
            $uploaded = uploadSchoolBrandingFile($_FILES['school_logo_icon'], 'logo_icon');
            if ($uploaded === false) {
                $_SESSION['error_msg'] = 'Logo icon upload failed. Use ICO, PNG or JPG (max 512KB).';
                header('Location: settings.php?tab=school');
                exit;
            }
            if ($uploaded) {
                deleteSchoolBrandingFile($current['logo_icon']);
                $profile['logo_icon'] = $uploaded;
            }
        }

        if (!empty($_POST['remove_favicon'])) {
            deleteSchoolBrandingFile($profile['favicon']);
            $profile['favicon'] = '';
        } elseif (!empty($_FILES['school_favicon']['name'])) {
            $uploaded = uploadSchoolBrandingFile($_FILES['school_favicon'], 'favicon');
            if ($uploaded === false) {
                $_SESSION['error_msg'] = 'Favicon upload failed. Use ICO, PNG or JPG (max 512KB).';
                header('Location: settings.php?tab=school');
                exit;
            }
            if ($uploaded) {
                deleteSchoolBrandingFile($current['favicon']);
                $profile['favicon'] = $uploaded;
            }
        }

        saveSchoolProfile($pdo, $profile);
        $_SESSION['success_msg'] = 'School profile saved.';
        header('Location: settings.php?tab=school');
        exit;
    }

    if ($action === 'save_signature') {
        $sigId = (int) ($_POST['sig_id'] ?? 0);
        $name = trim($_POST['sig_name'] ?? '');
        $designation = trim($_POST['sig_designation'] ?? '');
        if ($name === '' || $designation === '') {
            $_SESSION['error_msg'] = 'Signatory name and designation are required.';
            header('Location: settings.php?tab=signatures');
            exit;
        }
        $data = [
            'name' => $name,
            'designation' => $designation,
            'sort_order' => (int) ($_POST['sig_sort_order'] ?? 0),
            'status' => ($_POST['sig_status'] ?? 'Active') === 'Inactive' ? 'Inactive' : 'Active',
        ];
        $existing = $sigId ? getAuthoritySignatureById($pdo, $sigId) : null;
        if (!empty($_FILES['sig_image']['name'])) {
            $uploaded = uploadSignatureFile($_FILES['sig_image']);
            if ($uploaded === false) {
                $_SESSION['error_msg'] = 'Signature upload failed. Use a transparent PNG, JPG or WEBP (max 2MB).';
                header('Location: settings.php?tab=signatures');
                exit;
            }
            if ($uploaded) {
                if ($existing && !empty($existing['signature'])) {
                    deleteSchoolBrandingFile($existing['signature']);
                }
                $data['signature'] = $uploaded;
            }
        }
        $savedId = saveAuthoritySignature($pdo, $sigId, $data);
        if (!empty($_POST['sig_make_default'])) {
            setDefaultAuthoritySignature($pdo, $savedId);
        }
        $_SESSION['success_msg'] = $sigId ? 'Signatory updated.' : 'Signatory added.';
        header('Location: settings.php?tab=signatures');
        exit;
    }

    if ($action === 'delete_signature') {
        deleteAuthoritySignature($pdo, (int) ($_POST['sig_id'] ?? 0));
        $_SESSION['success_msg'] = 'Signatory removed.';
        header('Location: settings.php?tab=signatures');
        exit;
    }

    if ($action === 'default_signature') {
        setDefaultAuthoritySignature($pdo, (int) ($_POST['sig_id'] ?? 0));
        $_SESSION['success_msg'] = 'Default signatory updated.';
        header('Location: settings.php?tab=signatures');
        exit;
    }

    if ($action === 'add_gallery') {
        $title = trim((string) ($_POST['gallery_title'] ?? ''));
        $uploaded = uploadGalleryFile($_FILES['gallery_image'] ?? []);
        if ($uploaded === false || $uploaded === null) {
            $_SESSION['error_msg'] = 'Upload a JPG, PNG, WEBP or GIF image (max 2MB).';
            header('Location: settings.php?tab=gallery');
            exit;
        }
        $items = getWebsiteGallery($pdo);
        $items[] = ['path' => $uploaded, 'title' => $title !== '' ? $title : 'Gallery'];
        saveWebsiteGallery($pdo, $items);
        adminLogActivity($pdo, 'gallery_added', $title);
        $_SESSION['success_msg'] = 'Gallery photo added.';
        header('Location: settings.php?tab=gallery');
        exit;
    }

    if ($action === 'delete_gallery') {
        $path = (string) ($_POST['gallery_path'] ?? '');
        $items = [];
        foreach (getWebsiteGallery($pdo) as $item) {
            if ($item['path'] === $path) {
                deleteGalleryFile($path);
                continue;
            }
            $items[] = $item;
        }
        saveWebsiteGallery($pdo, $items);
        adminLogActivity($pdo, 'gallery_deleted', $path);
        $_SESSION['success_msg'] = 'Photo removed.';
        header('Location: settings.php?tab=gallery');
        exit;
    }

    if ($action === 'save_staff') {
        $staffId = (int) ($_POST['staff_id'] ?? 0);
        $username = trim((string) ($_POST['username'] ?? ''));
        $name = trim((string) ($_POST['name'] ?? ''));
        $role = (string) ($_POST['role'] ?? 'receptionist');
        $status = ($_POST['status'] ?? 'Active') === 'Inactive' ? 'Inactive' : 'Active';
        $password = (string) ($_POST['password'] ?? '');
        if (!isset(adminRoles()[$role])) {
            $role = 'receptionist';
        }
        if ($username === '' || $name === '') {
            $_SESSION['error_msg'] = 'Name and username are required.';
            header('Location: settings.php?tab=staff');
            exit;
        }
        $dup = $pdo->prepare('SELECT id FROM admin_users WHERE username = ? AND id <> ?');
        $dup->execute([$username, $staffId]);
        if ($dup->fetch()) {
            $_SESSION['error_msg'] = 'That username is already in use.';
            header('Location: settings.php?tab=staff');
            exit;
        }
        $selfId = (int) $_SESSION['admin_id'];
        if ($staffId === $selfId) {
            $role = 'admin';
            $status = 'Active';
        }
        if ($staffId) {
            $existing = $pdo->prepare('SELECT * FROM admin_users WHERE id = ?');
            $existing->execute([$staffId]);
            $row = $existing->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                $_SESSION['error_msg'] = 'Staff account not found.';
                header('Location: settings.php?tab=staff');
                exit;
            }
            $adminCount = (int) $pdo->query("SELECT COUNT(*) FROM admin_users WHERE role = 'admin' AND status = 'Active'")->fetchColumn();
            if (($row['role'] ?? '') === 'admin' && ($row['status'] ?? '') === 'Active' && ($role !== 'admin' || $status === 'Inactive') && $adminCount <= 1) {
                $_SESSION['error_msg'] = 'Keep at least one active Full Admin account.';
                header('Location: settings.php?tab=staff');
                exit;
            }
            $pdo->prepare('UPDATE admin_users SET username = ?, name = ?, role = ?, status = ? WHERE id = ?')
                ->execute([$username, $name, $role, $status, $staffId]);
            if ($password !== '') {
                if (strlen($password) < 6 || strcasecmp($password, 'admin123') === 0) {
                    $_SESSION['error_msg'] = 'New password must be at least 6 characters and not the default.';
                    header('Location: settings.php?tab=staff');
                    exit;
                }
                $pdo->prepare('UPDATE admin_users SET password = ?, must_change_password = 1 WHERE id = ?')
                    ->execute([password_hash($password, PASSWORD_DEFAULT), $staffId]);
            }
            if ($staffId === $selfId) {
                $_SESSION['admin_username'] = $username;
                $_SESSION['admin_name'] = $name;
            }
            adminLogActivity($pdo, 'staff_updated', $username);
            $_SESSION['success_msg'] = 'Staff account updated.';
        } else {
            if (strlen($password) < 6 || strcasecmp($password, 'admin123') === 0) {
                $_SESSION['error_msg'] = 'Password must be at least 6 characters and not the default.';
                header('Location: settings.php?tab=staff');
                exit;
            }
            try {
                $pdo->prepare('INSERT INTO admin_users (username, password, name, role, status, must_change_password) VALUES (?,?,?,?,?,1)')
                    ->execute([$username, password_hash($password, PASSWORD_DEFAULT), $name, $role, $status]);
            } catch (Throwable $e) {
                $_SESSION['error_msg'] = 'Could not create the account. Ask SuperAdmin to check the database.';
                header('Location: settings.php?tab=staff');
                exit;
            }
            adminLogActivity($pdo, 'staff_created', $username);
            $_SESSION['success_msg'] = 'Staff account created. They must change the password on first login.';
        }
        header('Location: settings.php?tab=staff');
        exit;
    }

    if ($action === 'delete_staff') {
        $staffId = (int) ($_POST['staff_id'] ?? 0);
        if ($staffId === (int) $_SESSION['admin_id']) {
            $_SESSION['error_msg'] = 'You cannot delete your own account.';
            header('Location: settings.php?tab=staff');
            exit;
        }
        $existing = $pdo->prepare('SELECT * FROM admin_users WHERE id = ?');
        $existing->execute([$staffId]);
        $row = $existing->fetch(PDO::FETCH_ASSOC);
        $adminCount = (int) $pdo->query("SELECT COUNT(*) FROM admin_users WHERE role = 'admin' AND status = 'Active'")->fetchColumn();
        if ($row && ($row['role'] ?? '') === 'admin' && ($row['status'] ?? '') === 'Active' && $adminCount <= 1) {
            $_SESSION['error_msg'] = 'Keep at least one active Full Admin account.';
            header('Location: settings.php?tab=staff');
            exit;
        }
        $pdo->prepare('DELETE FROM admin_users WHERE id = ?')->execute([$staffId]);
        adminLogActivity($pdo, 'staff_deleted', $row['username'] ?? ('#' . $staffId));
        $_SESSION['success_msg'] = 'Staff account removed.';
        header('Location: settings.php?tab=staff');
        exit;
    }

    if ($action === 'change_password') {
        $errors = changeAdminPassword(
            $pdo,
            (int) $_SESSION['admin_id'],
            $_POST['current_password'] ?? '',
            $_POST['new_password'] ?? '',
            $_POST['confirm_password'] ?? ''
        );
        if ($errors) {
            $_SESSION['error_msg'] = implode(' ', $errors);
        } else {
            $_SESSION['success_msg'] = 'Password changed successfully.';
        }
        header('Location: settings.php?tab=password');
        exit;
    }

}

require_once 'includes/header.php';
$school = getSchoolProfile($pdo);
$logoPreviewUrl = schoolBrandingUrl($school['logo'] ?? '', 'admin');
$logoLightPreviewUrl = schoolBrandingUrl($school['logo_light'] ?? '', 'admin');
$logoIconPreviewUrl = schoolBrandingUrl($school['logo_icon'] ?? '', 'admin');
$faviconPreviewUrl = schoolBrandingUrl($school['favicon'] ?? '', 'admin');
$signatures = getAuthoritySignatures($pdo);
$editSig = null;
if ($activeTab === 'signatures' && !empty($_GET['edit'])) {
    $editSig = getAuthoritySignatureById($pdo, (int) $_GET['edit']);
}
$galleryItems = $canManage ? getWebsiteGallery($pdo) : [];
$staffUsers = [];
$editStaff = null;
if ($canManage && $activeTab === 'staff') {
    $staffUsers = $pdo->query('SELECT id, username, name, role, status, must_change_password, created_at FROM admin_users ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($_GET['edit'])) {
        foreach ($staffUsers as $su) {
            if ((int) $su['id'] === (int) $_GET['edit']) {
                $editStaff = $su;
                break;
            }
        }
    }
}
$activityPage = max(1, (int) ($_GET['p'] ?? 1));
$activityPer = 50;
$activityTotal = 0;
$activityLogs = [];
if ($canManage && $activeTab === 'activity') {
    $activityTotal = adminCountActivityLogs($pdo);
    $activityPages = max(1, (int) ceil($activityTotal / $activityPer));
    if ($activityPage > $activityPages) {
        $activityPage = $activityPages;
    }
    $activityLogs = adminGetActivityLogs($pdo, $activityPer, ($activityPage - 1) * $activityPer);
}
?>
<div class="content-top-bar">
    <div class="content-top-main">
        <div class="content-top-icon icon-purple"><i class="fas fa-cog"></i></div>
        <div class="content-top-title">
            <h2>Settings</h2>
            <p class="content-top-breadcrumb">
                <a href="dashboard.php">Dashboard</a>
                <i class="fas fa-chevron-right"></i>
                <span>Settings</span>
            </p>
        </div>
    </div>
</div>

<div class="settings-layout">
    <aside class="settings-vtabs" role="tablist">
        <?php if ($canManage): ?>
        <a href="settings.php?tab=school" class="settings-vtab <?php echo $activeTab === 'school' ? 'active' : ''; ?>">
            <span class="settings-vtab-icon"><i class="fas fa-school"></i></span>
            <span class="settings-vtab-text"><strong>School Profile</strong><small>Name, address &amp; branding</small></span>
        </a>
        <a href="settings.php?tab=signatures" class="settings-vtab <?php echo $activeTab === 'signatures' ? 'active' : ''; ?>">
            <span class="settings-vtab-icon"><i class="fas fa-signature"></i></span>
            <span class="settings-vtab-text"><strong>Signatures</strong><small>Principal &amp; authorities</small></span>
        </a>
        <a href="settings.php?tab=gallery" class="settings-vtab <?php echo $activeTab === 'gallery' ? 'active' : ''; ?>">
            <span class="settings-vtab-icon"><i class="fas fa-images"></i></span>
            <span class="settings-vtab-text"><strong>Website Gallery</strong><small>Photos on the homepage</small></span>
        </a>
        <a href="settings.php?tab=staff" class="settings-vtab <?php echo $activeTab === 'staff' ? 'active' : ''; ?>">
            <span class="settings-vtab-icon"><i class="fas fa-user-shield"></i></span>
            <span class="settings-vtab-text"><strong>Staff Logins</strong><small>Admin users &amp; roles</small></span>
        </a>
        <a href="settings.php?tab=activity" class="settings-vtab <?php echo $activeTab === 'activity' ? 'active' : ''; ?>">
            <span class="settings-vtab-icon"><i class="fas fa-clipboard-list"></i></span>
            <span class="settings-vtab-text"><strong>Activity Log</strong><small>Sign-ins and changes</small></span>
        </a>
        <?php endif; ?>
        <a href="settings.php?tab=password" class="settings-vtab <?php echo $activeTab === 'password' ? 'active' : ''; ?>">
            <span class="settings-vtab-icon"><i class="fas fa-lock"></i></span>
            <span class="settings-vtab-text"><strong>Change Password</strong><small>Your account security</small></span>
        </a>
    </aside>

    <div class="settings-panels">
        <?php if ($activeTab === 'school'): ?>
        <div class="settings-panel active">
            <div class="settings-panel-head">
                <h3>School Profile</h3>
                <p>Used on report cards, ID cards, certificates and dashboard.</p>
            </div>
            <form method="POST" class="settings-form" enctype="multipart/form-data">
                <input type="hidden" name="action" value="save_school">
                <div class="settings-branding-block">
                    <h4><i class="fas fa-palette"></i> Logos</h4>
                    <p>Drop an image or choose a file. Shown on the public website, admin sidebar, logins, certificates and browser tab.</p>
                    <?php
                    $logoSlots = [
                        [
                            'name' => 'school_logo',
                            'remove' => 'remove_logo',
                            'url' => $logoPreviewUrl,
                            'title' => 'School logo',
                            'desc' => 'Main mark for light backgrounds, certificates and the homepage header.',
                            'hint' => 'PNG, JPG, WEBP or GIF · max 2MB',
                            'accept' => 'image/png,image/jpeg,image/webp,image/gif',
                            'tone' => 'light',
                            'shape' => 'wide',
                        ],
                        [
                            'name' => 'school_logo_light',
                            'remove' => 'remove_logo_light',
                            'url' => $logoLightPreviewUrl,
                            'title' => 'Light logo',
                            'desc' => 'White or light version for dark headers, hero and footer.',
                            'hint' => 'PNG with transparency recommended · max 2MB',
                            'accept' => 'image/png,image/jpeg,image/webp,image/gif',
                            'tone' => 'dark',
                            'shape' => 'wide',
                        ],
                        [
                            'name' => 'school_logo_icon',
                            'remove' => 'remove_logo_icon',
                            'url' => $logoIconPreviewUrl,
                            'title' => 'Logo icon',
                            'desc' => 'Compact square mark for the navbar and mobile menu.',
                            'hint' => 'Square PNG or ICO · max 512KB',
                            'accept' => 'image/png,image/jpeg,image/webp,image/gif,image/x-icon,image/vnd.microsoft.icon,.ico',
                            'tone' => 'light',
                            'shape' => 'icon',
                        ],
                        [
                            'name' => 'school_favicon',
                            'remove' => 'remove_favicon',
                            'url' => $faviconPreviewUrl,
                            'title' => 'Favicon',
                            'desc' => 'Small icon in the browser tab. 32×32 or 64×64 works best.',
                            'hint' => 'ICO or PNG · max 512KB',
                            'accept' => 'image/png,image/jpeg,image/x-icon,image/vnd.microsoft.icon,.ico',
                            'tone' => 'light',
                            'shape' => 'favicon',
                        ],
                    ];
                    ?>
                    <div class="sa-logo-grid">
                        <?php foreach ($logoSlots as $slot):
                            $hasImg = $slot['url'] !== '';
                        ?>
                        <article class="sa-logo-card tone-<?php echo htmlspecialchars($slot['tone']); ?> shape-<?php echo htmlspecialchars($slot['shape']); ?><?php echo $hasImg ? ' has-image' : ''; ?>" data-logo-card data-original="<?php echo htmlspecialchars($slot['url']); ?>">
                            <div class="sa-logo-stage">
                                <div class="sa-logo-frame" data-logo-frame>
                                    <?php if ($hasImg): ?>
                                    <img src="<?php echo htmlspecialchars($slot['url']); ?>" alt="<?php echo htmlspecialchars($slot['title']); ?>">
                                    <?php else: ?>
                                    <span class="sa-logo-empty">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                        Drop image here
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <span class="sa-logo-status" data-logo-status><?php echo $hasImg ? 'Current' : 'Not set'; ?></span>
                            </div>
                            <div class="sa-logo-body">
                                <div class="sa-logo-copy">
                                    <strong><?php echo htmlspecialchars($slot['title']); ?></strong>
                                    <p><?php echo htmlspecialchars($slot['desc']); ?></p>
                                </div>
                                <span class="sa-logo-hint"><?php echo htmlspecialchars($slot['hint']); ?></span>
                                <div class="sa-logo-actions">
                                    <label class="sa-logo-pick">
                                        <input type="file" class="sa-logo-file" name="<?php echo htmlspecialchars($slot['name']); ?>" accept="<?php echo htmlspecialchars($slot['accept']); ?>" data-logo-input>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                        Choose image
                                    </label>
                                    <span class="sa-logo-filename" data-logo-name>No file chosen</span>
                                </div>
                                <?php if ($hasImg): ?>
                                <label class="sa-logo-remove">
                                    <input type="checkbox" name="<?php echo htmlspecialchars($slot['remove']); ?>" value="1" data-logo-remove>
                                    Remove current image
                                </label>
                                <?php endif; ?>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="form-grid form-grid-2">
                    <div class="form-field"><label>School Name</label><input type="text" name="school_name" class="form-input" value="<?php echo htmlspecialchars($school['name']); ?>" required></div>
                    <div class="form-field"><label>Tagline</label><input type="text" name="school_tagline" class="form-input" value="<?php echo htmlspecialchars($school['tagline']); ?>"></div>
                    <div class="form-field form-field-full"><label>Address</label><textarea name="school_address" class="form-input form-textarea" rows="2"><?php echo htmlspecialchars($school['address']); ?></textarea></div>
                    <div class="form-field"><label>Phone</label><input type="text" name="school_phone" class="form-input" value="<?php echo htmlspecialchars($school['phone']); ?>"></div>
                    <div class="form-field"><label>Email</label><input type="email" name="school_email" class="form-input" value="<?php echo htmlspecialchars($school['email']); ?>"></div>
                    <div class="form-field"><label>Website</label><input type="text" name="school_website" class="form-input" value="<?php echo htmlspecialchars($school['website']); ?>"></div>
                    <div class="form-field"><label>Principal Name</label><input type="text" name="school_principal" class="form-input" value="<?php echo htmlspecialchars($school['principal']); ?>"></div>
                    <div class="form-field"><label>Affiliation</label><input type="text" name="school_affiliation" class="form-input" value="<?php echo htmlspecialchars($school['affiliation']); ?>" placeholder="CBSE"></div>
                </div>
                <div class="settings-form-actions"><button type="submit" class="btn-header-action btn-header-primary"><i class="fas fa-save"></i> Save Profile</button></div>
            </form>
        </div>
        <?php endif; ?>

        <?php if ($activeTab === 'signatures'): ?>
        <div class="settings-panel active">
            <div class="settings-panel-head">
                <h3>Authorized Signatories</h3>
                <p>Upload signatures for the Principal and other authorities. These appear on ID cards, certificates and fee receipts. The <strong>default</strong> signatory is used wherever a single signature is needed.</p>
            </div>

            <div class="sig-list">
                <?php if (empty($signatures)): ?>
                <div class="sig-empty"><i class="fas fa-signature"></i><p>No signatories added yet. Add the Principal's signature below to get started.</p></div>
                <?php else: foreach ($signatures as $sig): ?>
                <?php $sigUrl = schoolBrandingUrl($sig['signature'] ?? '', 'admin'); ?>
                <div class="sig-item<?php echo (int) $sig['is_default'] === 1 ? ' is-default' : ''; ?>">
                    <div class="sig-item-preview">
                        <?php if ($sigUrl): ?><img src="<?php echo htmlspecialchars($sigUrl); ?>" alt="Signature"><?php else: ?><span class="sig-noimg"><i class="fas fa-image"></i> No image</span><?php endif; ?>
                    </div>
                    <div class="sig-item-info">
                        <strong><?php echo htmlspecialchars($sig['name']); ?>
                            <?php if ((int) $sig['is_default'] === 1): ?><span class="sig-badge-default"><i class="fas fa-star"></i> Default</span><?php endif; ?>
                            <?php if ($sig['status'] === 'Inactive'): ?><span class="sig-badge-off">Inactive</span><?php endif; ?>
                        </strong>
                        <span><?php echo htmlspecialchars($sig['designation']); ?></span>
                    </div>
                    <div class="sig-item-actions">
                        <?php if ((int) $sig['is_default'] !== 1 && $sig['status'] !== 'Inactive'): ?>
                        <form method="POST"><input type="hidden" name="action" value="default_signature"><input type="hidden" name="sig_id" value="<?php echo (int) $sig['id']; ?>"><button type="submit" class="sig-btn" title="Set as default"><i class="fas fa-star"></i> Set default</button></form>
                        <?php endif; ?>
                        <a href="settings.php?tab=signatures&edit=<?php echo (int) $sig['id']; ?>" class="sig-btn"><i class="fas fa-pen"></i> Edit</a>
                        <form method="POST" onsubmit="return confirm('Remove this signatory?');"><input type="hidden" name="action" value="delete_signature"><input type="hidden" name="sig_id" value="<?php echo (int) $sig['id']; ?>"><button type="submit" class="sig-btn sig-btn-danger"><i class="fas fa-trash"></i></button></form>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>

            <div class="settings-branding-block">
                <h4><i class="fas fa-<?php echo $editSig ? 'pen' : 'plus'; ?>"></i> <?php echo $editSig ? 'Edit Signatory' : 'Add New Signatory'; ?></h4>
                <p>Use a clear signature image on a transparent or white background for best print results.</p>
                <form method="POST" class="settings-form" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="save_signature">
                    <input type="hidden" name="sig_id" value="<?php echo $editSig ? (int) $editSig['id'] : 0; ?>">
                    <?php
                    $editSigUrl = $editSig ? schoolBrandingUrl($editSig['signature'] ?? '', 'admin') : '';
                    $hasSigImg = $editSigUrl !== '';
                    ?>
                    <div class="sa-logo-grid">
                        <article class="sa-logo-card tone-light shape-wide<?php echo $hasSigImg ? ' has-image' : ''; ?>" data-logo-card data-original="<?php echo htmlspecialchars($editSigUrl); ?>">
                            <div class="sa-logo-stage">
                                <div class="sa-logo-frame" data-logo-frame>
                                    <?php if ($hasSigImg): ?>
                                    <img src="<?php echo htmlspecialchars($editSigUrl); ?>" alt="Signature">
                                    <?php else: ?>
                                    <span class="sa-logo-empty">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                        Drop signature here
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <span class="sa-logo-status" data-logo-status><?php echo $hasSigImg ? 'Current' : 'Not set'; ?></span>
                            </div>
                            <div class="sa-logo-body">
                                <div class="sa-logo-copy">
                                    <strong>Signature image</strong>
                                    <p>Transparent PNG looks best on ID cards, certificates and receipts.</p>
                                </div>
                                <span class="sa-logo-hint">PNG, JPG or WEBP · max 2MB<?php echo $editSig ? ' · leave blank to keep current' : ''; ?></span>
                                <div class="sa-logo-actions">
                                    <label class="sa-logo-pick">
                                        <input type="file" class="sa-logo-file" name="sig_image" accept="image/png,image/jpeg,image/webp" data-logo-input<?php echo $editSig ? '' : ' required'; ?>>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                        Choose image
                                    </label>
                                    <span class="sa-logo-filename" data-logo-name>No file chosen</span>
                                </div>
                            </div>
                        </article>
                    </div>
                    <div class="form-grid form-grid-2">
                        <div class="form-field"><label>Signatory Name</label><input type="text" name="sig_name" class="form-input" value="<?php echo htmlspecialchars($editSig['name'] ?? $school['principal'] ?? ''); ?>" placeholder="e.g. Dr. A. Sharma" required></div>
                        <div class="form-field"><label>Designation</label><input type="text" name="sig_designation" class="form-input" value="<?php echo htmlspecialchars($editSig['designation'] ?? 'Principal'); ?>" placeholder="e.g. Principal / Vice Principal / Exam Controller" required></div>
                        <div class="form-field"><label>Display Order</label><input type="number" name="sig_sort_order" class="form-input" value="<?php echo (int) ($editSig['sort_order'] ?? 0); ?>"></div>
                        <div class="form-field"><label>Status</label><select name="sig_status" class="form-input form-select"><option value="Active" <?php echo (($editSig['status'] ?? 'Active') === 'Active') ? 'selected' : ''; ?>>Active</option><option value="Inactive" <?php echo (($editSig['status'] ?? '') === 'Inactive') ? 'selected' : ''; ?>>Inactive</option></select></div>
                    </div>
                    <label class="settings-toggle"><input type="checkbox" name="sig_make_default" value="1" <?php echo ($editSig && (int) $editSig['is_default'] === 1) ? 'checked' : (empty($signatures) ? 'checked' : ''); ?>><span>Set as default signatory (used on ID cards &amp; receipts)</span></label>
                    <div class="settings-form-actions">
                        <?php if ($editSig): ?><a href="settings.php?tab=signatures" class="btn-header-action btn-header-outline"><i class="fas fa-times"></i> Cancel</a><?php endif; ?>
                        <button type="submit" class="btn-header-action btn-header-primary"><i class="fas fa-save"></i> <?php echo $editSig ? 'Update Signatory' : 'Add Signatory'; ?></button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($activeTab === 'gallery'): ?>
        <div class="settings-panel active">
            <div class="settings-panel-head">
                <h3>Website Gallery</h3>
                <p>These photos appear on the public homepage. If none are uploaded, the site keeps the default sample images.</p>
            </div>
            <div class="gallery-admin-grid">
                <?php if (!$galleryItems): ?>
                <div class="sig-empty"><i class="fas fa-images"></i><p>No gallery photos yet. Add the first one below.</p></div>
                <?php else: foreach ($galleryItems as $g):
                    $gUrl = websiteGalleryUrl($g['path'], 'admin');
                ?>
                <article class="gallery-admin-card">
                    <img src="<?php echo htmlspecialchars($gUrl); ?>" alt="<?php echo htmlspecialchars($g['title']); ?>">
                    <div>
                        <strong><?php echo htmlspecialchars($g['title']); ?></strong>
                        <form method="POST" onsubmit="return confirm('Remove this photo?');">
                            <input type="hidden" name="action" value="delete_gallery">
                            <input type="hidden" name="gallery_path" value="<?php echo htmlspecialchars($g['path']); ?>">
                            <button type="submit" class="sig-btn sig-btn-danger"><i class="fas fa-trash"></i> Remove</button>
                        </form>
                    </div>
                </article>
                <?php endforeach; endif; ?>
            </div>
            <div class="settings-branding-block">
                <h4><i class="fas fa-plus"></i> Add photo</h4>
                <form method="POST" class="settings-form" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_gallery">
                    <div class="sa-logo-grid">
                        <article class="sa-logo-card tone-light shape-wide" data-logo-card data-original="">
                            <div class="sa-logo-stage">
                                <div class="sa-logo-frame" data-logo-frame>
                                    <span class="sa-logo-empty">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                        Drop photo here
                                    </span>
                                </div>
                                <span class="sa-logo-status" data-logo-status>Not set</span>
                            </div>
                            <div class="sa-logo-body">
                                <div class="sa-logo-copy">
                                    <strong>Gallery photo</strong>
                                    <p>Shown in the School Memories section on the homepage.</p>
                                </div>
                                <span class="sa-logo-hint">JPG, PNG, WEBP or GIF · max 2MB</span>
                                <div class="sa-logo-actions">
                                    <label class="sa-logo-pick">
                                        <input type="file" class="sa-logo-file" name="gallery_image" accept="image/jpeg,image/png,image/webp,image/gif" data-logo-input required>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                        Choose image
                                    </label>
                                    <span class="sa-logo-filename" data-logo-name>No file chosen</span>
                                </div>
                            </div>
                        </article>
                    </div>
                    <div class="form-field"><label>Caption</label><input type="text" name="gallery_title" class="form-input" placeholder="e.g. Annual Day"></div>
                    <div class="settings-form-actions"><button type="submit" class="btn-header-action btn-header-primary"><i class="fas fa-upload"></i> Add to gallery</button></div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($activeTab === 'staff'):
            $roleMeta = [
                'admin' => ['icon' => 'fa-crown', 'tone' => 'emerald', 'desc' => 'Every enabled module'],
                'accountant' => ['icon' => 'fa-calculator', 'tone' => 'amber', 'desc' => 'Fees & staff salary'],
                'academic' => ['icon' => 'fa-book-open', 'tone' => 'sky', 'desc' => 'Academic, exams, attendance'],
                'receptionist' => ['icon' => 'fa-headset', 'tone' => 'violet', 'desc' => 'Students & website enquiries'],
            ];
            $staffActive = count(array_filter($staffUsers, fn($u) => ($u['status'] ?? '') === 'Active'));
            $staffMustChange = count(array_filter($staffUsers, fn($u) => !empty($u['must_change_password'])));
            $isEditingSelf = $editStaff && (int) $editStaff['id'] === (int) $_SESSION['admin_id'];
        ?>
        <div class="settings-panel active">
            <div class="settings-panel-head staff-panel-head">
                <div>
                    <h3>Staff Logins</h3>
                    <p>Extra school-admin accounts with role-based access. Full Admin sees every enabled module.</p>
                </div>
                <?php if (!$editStaff): ?>
                <a href="#staff-form" class="btn-header-action btn-header-primary"><i class="fas fa-user-plus"></i> Add login</a>
                <?php endif; ?>
            </div>

            <div class="staff-stat-strip">
                <div class="staff-stat">
                    <span class="staff-stat-ico is-total"><i class="fas fa-users"></i></span>
                    <div><em>Total logins</em><strong><?php echo count($staffUsers); ?></strong></div>
                </div>
                <div class="staff-stat">
                    <span class="staff-stat-ico is-active"><i class="fas fa-check-circle"></i></span>
                    <div><em>Active</em><strong><?php echo $staffActive; ?></strong></div>
                </div>
                <div class="staff-stat">
                    <span class="staff-stat-ico is-warn"><i class="fas fa-key"></i></span>
                    <div><em>Must change password</em><strong><?php echo $staffMustChange; ?></strong></div>
                </div>
            </div>

            <div class="staff-role-guide">
                <?php foreach (adminRoles() as $rk => $rm):
                    $meta = $roleMeta[$rk] ?? ['icon' => 'fa-user', 'tone' => 'slate', 'desc' => ''];
                ?>
                <div class="staff-role-chip tone-<?php echo htmlspecialchars($meta['tone']); ?>">
                    <i class="fas <?php echo htmlspecialchars($meta['icon']); ?>"></i>
                    <div>
                        <strong><?php echo htmlspecialchars($rm['label']); ?></strong>
                        <span><?php echo htmlspecialchars($meta['desc']); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="staff-list-head">
                <h4>Accounts</h4>
                <span><?php echo count($staffUsers); ?> login<?php echo count($staffUsers) === 1 ? '' : 's'; ?></span>
            </div>

            <?php if (!$staffUsers): ?>
            <div class="staff-empty">
                <i class="fas fa-user-shield"></i>
                <p>No staff logins yet. Create the first account below.</p>
            </div>
            <?php else: ?>
            <div class="staff-card-list">
                <?php foreach ($staffUsers as $su):
                    $display = trim((string) ($su['name'] ?? '')) ?: (string) $su['username'];
                    $roleKey = (string) ($su['role'] ?? 'admin');
                    $meta = $roleMeta[$roleKey] ?? ['icon' => 'fa-user', 'tone' => 'slate', 'desc' => ''];
                    $isYou = (int) $su['id'] === (int) $_SESSION['admin_id'];
                    $isActive = ($su['status'] ?? 'Active') === 'Active';
                    $isEditing = $editStaff && (int) $editStaff['id'] === (int) $su['id'];
                ?>
                <article class="staff-card<?php echo $isEditing ? ' is-editing' : ''; ?><?php echo !$isActive ? ' is-inactive' : ''; ?>">
                    <div class="staff-card-main">
                        <span class="staff-avatar tone-<?php echo htmlspecialchars($meta['tone']); ?>"><?php echo htmlspecialchars(adminInitials($display)); ?></span>
                        <div class="staff-card-info">
                            <div class="staff-card-title">
                                <strong><?php echo htmlspecialchars($display); ?></strong>
                                <?php if ($isYou): ?><span class="staff-pill is-you">You</span><?php endif; ?>
                                <?php if (!empty($su['must_change_password'])): ?><span class="staff-pill is-warn">Password pending</span><?php endif; ?>
                            </div>
                            <div class="staff-card-meta">
                                <span><i class="fas fa-at"></i> <?php echo htmlspecialchars($su['username']); ?></span>
                                <span class="staff-role-tag tone-<?php echo htmlspecialchars($meta['tone']); ?>">
                                    <i class="fas <?php echo htmlspecialchars($meta['icon']); ?>"></i>
                                    <?php echo htmlspecialchars(adminRoleLabel($roleKey)); ?>
                                </span>
                                <span class="staff-status <?php echo $isActive ? 'is-on' : 'is-off'; ?>">
                                    <i class="fas fa-circle"></i> <?php echo htmlspecialchars($su['status'] ?? 'Active'); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="staff-card-actions">
                        <a href="settings.php?tab=staff&amp;edit=<?php echo (int) $su['id']; ?>#staff-form" class="staff-action-btn" title="Edit">
                            <i class="fas fa-pen"></i><span>Edit</span>
                        </a>
                        <?php if (!$isYou): ?>
                        <form method="POST" onsubmit="return confirm('Delete this login permanently?');">
                            <input type="hidden" name="action" value="delete_staff">
                            <input type="hidden" name="staff_id" value="<?php echo (int) $su['id']; ?>">
                            <button type="submit" class="staff-action-btn is-danger" title="Delete">
                                <i class="fas fa-trash"></i><span>Delete</span>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="staff-form-card" id="staff-form">
                <div class="staff-form-head">
                    <div class="staff-form-head-icon">
                        <i class="fas fa-<?php echo $editStaff ? 'user-edit' : 'user-plus'; ?>"></i>
                    </div>
                    <div>
                        <h4><?php echo $editStaff ? 'Edit account' : 'Add staff login'; ?></h4>
                        <p><?php echo $editStaff
                            ? 'Update details for <strong>' . htmlspecialchars(trim((string) ($editStaff['name'] ?? '')) ?: (string) $editStaff['username']) . '</strong>.'
                            : 'They will sign in at the School Admin login page.'; ?></p>
                    </div>
                </div>
                <form method="POST" class="settings-form">
                    <input type="hidden" name="action" value="save_staff">
                    <input type="hidden" name="staff_id" value="<?php echo $editStaff ? (int) $editStaff['id'] : 0; ?>">
                    <div class="form-grid form-grid-2">
                        <div class="form-field">
                            <label>Display name</label>
                            <input type="text" name="name" class="form-input" value="<?php echo htmlspecialchars($editStaff['name'] ?? ''); ?>" placeholder="e.g. Priya Sharma" required>
                        </div>
                        <div class="form-field">
                            <label>Username</label>
                            <input type="text" name="username" class="form-input" value="<?php echo htmlspecialchars($editStaff['username'] ?? ''); ?>" placeholder="e.g. priya" required autocomplete="off">
                        </div>
                        <div class="form-field">
                            <label>Role</label>
                            <select name="role" class="form-input form-select" <?php echo $isEditingSelf ? 'disabled' : ''; ?>>
                                <?php foreach (adminRoles() as $rk => $rm): ?>
                                <option value="<?php echo htmlspecialchars($rk); ?>" <?php echo (($editStaff['role'] ?? 'receptionist') === $rk) ? 'selected' : ''; ?>><?php echo htmlspecialchars($rm['label']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($isEditingSelf): ?>
                            <input type="hidden" name="role" value="admin">
                            <span class="field-hint">You cannot change your own role.</span>
                            <?php endif; ?>
                        </div>
                        <div class="form-field">
                            <label>Status</label>
                            <select name="status" class="form-input form-select" <?php echo $isEditingSelf ? 'disabled' : ''; ?>>
                                <option value="Active" <?php echo (($editStaff['status'] ?? 'Active') === 'Active') ? 'selected' : ''; ?>>Active</option>
                                <option value="Inactive" <?php echo (($editStaff['status'] ?? '') === 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                            <?php if ($isEditingSelf): ?>
                            <input type="hidden" name="status" value="Active">
                            <span class="field-hint">You cannot deactivate your own account.</span>
                            <?php endif; ?>
                        </div>
                        <div class="form-field form-field-full">
                            <label><?php echo $editStaff ? 'New password <span class="staff-optional">(optional)</span>' : 'Password'; ?></label>
                            <input type="password" name="password" class="form-input" <?php echo $editStaff ? '' : 'required'; ?> minlength="6" autocomplete="new-password" placeholder="<?php echo $editStaff ? 'Leave blank to keep current' : 'Minimum 6 characters'; ?>">
                            <span class="field-hint"><?php echo $editStaff ? 'A new password forces a change on their next login.' : 'They must change this password after first login.'; ?></span>
                        </div>
                    </div>
                    <div class="settings-form-actions staff-form-actions">
                        <?php if ($editStaff): ?><a href="settings.php?tab=staff" class="btn-header-action btn-header-outline"><i class="fas fa-times"></i> Cancel</a><?php endif; ?>
                        <button type="submit" class="btn-header-action btn-header-primary"><i class="fas fa-save"></i> <?php echo $editStaff ? 'Update account' : 'Create login'; ?></button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($activeTab === 'activity'): ?>
        <div class="settings-panel active">
            <div class="settings-panel-head">
                <h3>Activity Log</h3>
                <p>Recent admin sign-ins and changes. Records older than 90 days are removed automatically.</p>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>When</th><th>User</th><th>Action</th><th>Details</th><th>IP</th></tr></thead>
                    <tbody>
                    <?php if (!$activityLogs): ?>
                    <tr><td colspan="5">No activity yet.</td></tr>
                    <?php else: foreach ($activityLogs as $log): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($log['created_at'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($log['username'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars(str_replace('_', ' ', (string) ($log['action'] ?? ''))); ?></td>
                        <td><?php echo htmlspecialchars($log['details'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($log['ip_address'] ?? ''); ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if (($activityPages ?? 1) > 1): ?>
            <div class="settings-form-actions">
                <?php if ($activityPage > 1): ?><a class="btn-header-action btn-header-outline" href="settings.php?tab=activity&amp;p=<?php echo $activityPage - 1; ?>">Previous</a><?php endif; ?>
                <span>Page <?php echo $activityPage; ?> of <?php echo $activityPages; ?></span>
                <?php if ($activityPage < $activityPages): ?><a class="btn-header-action btn-header-outline" href="settings.php?tab=activity&amp;p=<?php echo $activityPage + 1; ?>">Next</a><?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($activeTab === 'password'): ?>
        <div class="settings-panel active">
            <div class="settings-panel-head">
                <h3>Change Password</h3>
                <p>Update your admin login password. You must enter your current password to confirm.</p>
            </div>
            <?php if (!empty($_SESSION['admin_must_change'])): ?>
            <div class="admin-force-banner">You are still using the default password. Choose a new one to continue.</div>
            <?php endif; ?>
            <form method="POST" class="settings-form settings-form-narrow">
                <input type="hidden" name="action" value="change_password">
                <div class="form-grid form-grid-1">
                    <div class="form-field">
                        <label><i class="fas fa-lock"></i> Current Password</label>
                        <input type="password" name="current_password" class="form-input" required autocomplete="current-password">
                    </div>
                    <div class="form-field">
                        <label><i class="fas fa-key"></i> New Password</label>
                        <input type="password" name="new_password" class="form-input" required minlength="6" autocomplete="new-password">
                        <span class="field-hint">Minimum 6 characters</span>
                    </div>
                    <div class="form-field">
                        <label><i class="fas fa-check-double"></i> Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-input" required minlength="6" autocomplete="new-password">
                    </div>
                </div>
                <div class="settings-form-actions">
                    <button type="submit" class="btn-header-action btn-header-primary" onclick="return confirm('Change your admin password?');"><i class="fas fa-shield-alt"></i> Update Password</button>
                </div>
            </form>
            <div class="settings-info-box">
                <i class="fas fa-info-circle"></i>
                <div>
                    <strong>Logged in as:</strong> <?php echo htmlspecialchars($_SESSION['admin_username']); ?><br>
                    After changing password, you will stay logged in on this device. Use the new password on your next login.
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php if (in_array($activeTab, ['school', 'signatures', 'gallery'], true)): ?>
<script>
document.querySelectorAll('[data-logo-card]').forEach(function (card) {
    var input = card.querySelector('[data-logo-input]');
    var frame = card.querySelector('[data-logo-frame]');
    var nameEl = card.querySelector('[data-logo-name]');
    var statusEl = card.querySelector('[data-logo-status]');
    var remove = card.querySelector('[data-logo-remove]');
    if (!input || !frame) return;
    var emptyHtml = '<span class="sa-logo-empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>Drop image here</span>';

    function setPreview(file) {
        if (!file || !file.type || file.type.indexOf('image/') !== 0) return;
        var url = URL.createObjectURL(file);
        frame.innerHTML = '<img src="' + url + '" alt="">';
        card.classList.add('has-file', 'has-image');
        card.classList.remove('is-remove');
        nameEl.textContent = file.name;
        if (statusEl) statusEl.textContent = 'New file';
        if (remove) remove.checked = false;
    }

    input.addEventListener('change', function () {
        setPreview(input.files && input.files[0]);
    });
    ['dragenter', 'dragover'].forEach(function (evt) {
        card.addEventListener(evt, function (e) {
            e.preventDefault();
            card.classList.add('is-drag');
        });
    });
    ['dragleave', 'drop'].forEach(function (evt) {
        card.addEventListener(evt, function () {
            card.classList.remove('is-drag');
        });
    });
    card.addEventListener('drop', function (e) {
        e.preventDefault();
        var file = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
        if (!file) return;
        try {
            var dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;
        } catch (err) {}
        setPreview(file);
    });
    if (remove) {
        remove.addEventListener('change', function () {
            card.classList.toggle('is-remove', remove.checked);
            if (remove.checked) {
                input.value = '';
                card.classList.remove('has-file');
                nameEl.textContent = 'No file chosen';
                if (statusEl) statusEl.textContent = 'Will remove';
                var original = card.getAttribute('data-original') || '';
                frame.innerHTML = original
                    ? '<img src="' + original + '" alt="">'
                    : emptyHtml;
            } else if (statusEl) {
                statusEl.textContent = card.classList.contains('has-image') ? 'Current' : 'Not set';
            }
        });
    }
});
</script>
<?php endif; ?>
<?php require_once 'includes/footer.php'; ?>
