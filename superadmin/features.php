<?php
$page_title = 'Plan & Features';
require_once 'includes/init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    saRequireCsrf('features.php');
    $action = $_POST['action'] ?? 'save';
    if ($action === 'preset') {
        $preset = strtolower(trim((string) ($_POST['preset'] ?? '')));
        if (saApplyPreset($pdo, $preset)) {
            saLogActivity($pdo, 'preset_applied', 'Applied preset: ' . ucfirst($preset));
            saFlash('success', 'Preset applied. School Admin menus will update immediately.');
        } else {
            saFlash('error', 'Unknown preset.');
        }
    } else {
        $modules = $_POST['modules'] ?? [];
        if (!is_array($modules)) {
            $modules = [];
        }
        saSaveThisSchoolModules($pdo, $modules);
        saLogActivity($pdo, 'features_saved', 'Custom module configuration saved.');
        saFlash('success', 'Features saved. Disabled modules are hidden from School Admin and portals.');
    }
    header('Location: features.php');
    exit;
}

$presets = saErpPresets();
$currentPreset = saActivePresetKey($school);
if ($currentPreset === 'custom') {
    $actualKeys = getSchoolModuleKeys($pdo, (int) $school['id']);
    $base = saMatchPresetKey($actualKeys) ?: saClosestPresetKey($actualKeys);
    if ($base) {
        $pdo->prepare('UPDATE sa_schools SET plan = ? WHERE id = ?')->execute([ucfirst($base), (int) $school['id']]);
        $school['plan'] = ucfirst($base);
        $currentPreset = $base;
    }
}
$presetModified = saPresetIsModified($pdo, $school);
$enabled = getSchoolModuleKeys($pdo, (int) $school['id']);
$catalog = getErpModuleCatalog();

$switch = static function (string $name, string $key, string $label, string $hint, bool $on): string {
    $id = 'sw_' . preg_replace('/[^a-z0-9_]/i', '', $key);
    $checked = $on ? ' checked' : '';
    $state = $on ? 'is-on' : '';
    return '<label class="sa-switch ' . $state . '" for="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '">'
        . '<span class="sa-switch-copy">'
        . '<strong>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</strong>'
        . '<small>' . htmlspecialchars($hint, ENT_QUOTES, 'UTF-8') . '</small>'
        . '</span>'
        . '<span class="sa-switch-control">'
        . '<input type="checkbox" name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '"' . $checked . '>'
        . '<span class="sa-switch-track" aria-hidden="true"><span class="sa-switch-thumb"></span></span>'
        . '<span class="sa-switch-state">' . ($on ? 'ON' : 'OFF') . '</span>'
        . '</span>'
        . '</label>';
};

$secIco = static function (string $d): string {
    return '<span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">' . $d . '</svg></span>';
};

require_once 'includes/layout_header.php';
?>
<section class="sa-hero">
    <div>
        <span class="sa-hero-kicker">School package</span>
        <h1>Plan &amp; Features</h1>
        <p>Apply a ready preset in one click, or fine-tune modules for this school install.</p>
    </div>
</section>

<div class="sa-panel">
    <div class="sa-panel-head">
        <div>
            <h2>Quick presets</h2>
            <p>One-click templates for common school types</p>
        </div>
    </div>
    <div class="sa-panel-body">
        <div class="sa-preset-grid">
            <?php foreach ($presets as $key => $p):
                $isActive = $currentPreset === $key;
            ?>
            <form method="post" class="sa-preset-card tone-<?php echo e($p['tone']); ?><?php echo $isActive ? ' is-active' : ''; ?><?php echo $isActive && $presetModified ? ' is-modified' : ''; ?>">
                <?php echo saCsrfField(); ?>
                <input type="hidden" name="action" value="preset">
                <input type="hidden" name="preset" value="<?php echo e($key); ?>">
                <div class="sa-preset-top">
                    <span class="sa-preset-ico" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><?php echo $p['ico']; ?></svg>
                    </span>
                    <?php if ($isActive && $presetModified): ?>
                    <span class="sa-preset-badge is-modified"><span class="sa-preset-badge-dot"></span> Modified</span>
                    <?php elseif ($isActive): ?>
                    <span class="sa-preset-badge"><span class="sa-preset-badge-dot"></span> Active</span>
                    <?php else: ?>
                    <span class="sa-preset-badge is-idle">Preset</span>
                    <?php endif; ?>
                </div>
                <div class="sa-preset-body">
                    <strong><?php echo e($p['label']); ?></strong>
                    <p><?php echo e($p['description']); ?></p>
                    <div class="sa-preset-tags">
                        <?php foreach ($p['tags'] as $tag): ?>
                        <span><?php echo e($tag); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="sa-preset-foot">
                    <button type="submit" class="sa-preset-btn<?php echo $isActive ? ' is-active' : ''; ?>">
                        <?php if ($isActive && $presetModified): ?>
                            Currently active · customized
                        <?php elseif ($isActive): ?>
                            Currently active
                        <?php else: ?>
                            Apply preset
                        <?php endif; ?>
                    </button>
                </div>
            </form>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<form method="post" class="sa-panel" id="saFeaturesForm">
    <?php echo saCsrfField(); ?>
    <input type="hidden" name="action" value="save">
    <div class="sa-panel-head">
        <div>
            <h2>Custom configuration</h2>
            <p>Toggle modules below and Save. The selected preset stays active and shows <strong>Modified</strong> if switches differ.</p>
        </div>
        <?php if ($currentPreset !== 'custom' && $presetModified): ?>
        <span class="sa-chip warn"><?php echo e(saPlanLabel($currentPreset)); ?> · Modified</span>
        <?php elseif ($currentPreset !== 'custom'): ?>
        <span class="sa-chip on"><?php echo e(saPlanLabel($currentPreset)); ?> · Active</span>
        <?php else: ?>
        <span class="sa-chip">Custom</span>
        <?php endif; ?>
    </div>
    <div class="sa-panel-body">
        <h3 class="sa-section-title"><?php echo $secIco('<path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>'); ?> Core school ERP</h3>
        <div class="sa-switch-grid">
            <?php foreach ($catalog as $key => $meta):
                if (($meta['group'] ?? '') !== 'core') {
                    continue;
                }
                echo $switch('modules[]', $key, $meta['label'], $meta['description'], in_array($key, $enabled, true));
            endforeach; ?>
        </div>

        <h3 class="sa-section-title"><?php echo $secIco('<path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/>'); ?> Add-on modules</h3>
        <div class="sa-switch-grid">
            <?php foreach ($catalog as $key => $meta):
                if (($meta['group'] ?? '') !== 'addon') {
                    continue;
                }
                echo $switch('modules[]', $key, $meta['label'], $meta['description'], in_array($key, $enabled, true));
            endforeach; ?>
        </div>

        <div class="sa-form-actions">
            <button type="submit" class="btn btn-primary">Save configuration</button>
            <a href="dashboard.php" class="btn btn-outline">Back to dashboard</a>
        </div>
    </div>
</form>
<script>
(function () {
    document.querySelectorAll('.sa-switch input[type="checkbox"]').forEach(function (input) {
        input.addEventListener('change', function () {
            var row = input.closest('.sa-switch');
            var state = row && row.querySelector('.sa-switch-state');
            if (!row) return;
            row.classList.toggle('is-on', input.checked);
            if (state) state.textContent = input.checked ? 'ON' : 'OFF';
        });
    });
})();
</script>
<?php require_once 'includes/layout_footer.php'; ?>
