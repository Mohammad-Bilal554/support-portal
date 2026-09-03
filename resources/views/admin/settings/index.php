<?php
/**
 * Portal Settings View
 */
use App\Core\Session;
use App\Core\Csrf;
use App\Models\Setting;

$session   = Session::getInstance();
$csrfToken = Csrf::getToken();
$title     = 'Portal Settings';
ob_start();

$tabLabels = [
    'general'       => ['General',       'bi-gear-fill'],
    'notifications' => ['Notifications', 'bi-bell-fill'],
    'tickets'       => ['Tickets',       'bi-ticket-perforated-fill'],
    'security'      => ['Security',      'bi-shield-lock-fill'],
];
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Portal Settings</h1>
        <p class="page-subtitle">Configure your support portal preferences.</p>
    </div>
</div>

<div class="row g-4">

    <!-- Tab Sidebar -->
    <div class="col-lg-3">
        <div class="card">
            <div class="card-body p-2">
                <nav class="d-flex flex-column gap-1">
                    <?php foreach ($tabLabels as $key => [$label, $icon]): ?>
                    <a href="<?= url('admin/settings?tab=' . $key) ?>"
                       class="btn text-start <?= $tab === $key ? 'btn-primary' : 'btn-outline-secondary border-0' ?>"
                       style="font-size:.875rem;">
                        <i class="bi <?= $icon ?> me-2"></i><?= $label ?>
                    </a>
                    <?php endforeach; ?>
                </nav>
            </div>
        </div>

        <!-- Info card -->
        <div class="card mt-3">
            <div class="card-body" style="font-size:.82rem;">
                <p class="fw-semibold mb-2"><i class="bi bi-info-circle text-primary me-1"></i>About Settings</p>
                <p class="text-muted mb-0">
                    Changes take effect immediately. Some settings (e.g. session lifetime)
                    apply on next login.
                </p>
            </div>
        </div>
    </div>

    <!-- Settings Form -->
    <div class="col-lg-9">
        <form action="<?= url('admin/settings') ?>" method="POST" novalidate>
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="_tab" value="<?= htmlspecialchars($tab) ?>">

            <div class="card">
                <div class="card-header">
                    <?php [$label, $icon] = $tabLabels[$tab]; ?>
                    <span><i class="bi <?= $icon ?> me-2 text-primary"></i><?= $label ?> Settings</span>
                </div>
                <div class="card-body">

                    <?php foreach ($groups[$tab] as $key => $def): ?>
                    <?php $currentVal = $settings[$key] ?? $def['value']; ?>

                    <div class="row align-items-center mb-4 pb-4 border-bottom">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold mb-1" for="setting_<?= $key ?>">
                                <?= htmlspecialchars($def['label']) ?>
                            </label>
                            <?php if ($def['type'] === 'boolean'): ?>
                            <div class="text-muted" style="font-size:.78rem;">Enable or disable this feature.</div>
                            <?php elseif ($def['type'] === 'number'): ?>
                            <div class="text-muted" style="font-size:.78rem;">Enter a numeric value.</div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-8">

                            <?php if ($def['type'] === 'boolean'): ?>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox"
                                       name="<?= $key ?>" id="setting_<?= $key ?>"
                                       value="1"
                                       <?= $currentVal ? 'checked' : '' ?>>
                                <label class="form-check-label" for="setting_<?= $key ?>">
                                    <?= $currentVal ? 'Enabled' : 'Disabled' ?>
                                </label>
                            </div>

                            <?php elseif ($def['type'] === 'select'): ?>
                            <select name="<?= $key ?>" id="setting_<?= $key ?>" class="form-select" style="max-width:250px;">
                                <?php foreach ($def['options'] as $optVal => $optLabel): ?>
                                <option value="<?= $optVal ?>" <?= $currentVal === $optVal ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($optLabel) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>

                            <?php elseif ($def['type'] === 'number'): ?>
                            <input type="number" name="<?= $key ?>" id="setting_<?= $key ?>"
                                   class="form-control" style="max-width:150px;"
                                   value="<?= htmlspecialchars((string)$currentVal) ?>"
                                   min="0">

                            <?php elseif ($def['type'] === 'email'): ?>
                            <input type="email" name="<?= $key ?>" id="setting_<?= $key ?>"
                                   class="form-control" style="max-width:350px;"
                                   value="<?= htmlspecialchars((string)$currentVal) ?>">

                            <?php elseif ($def['type'] === 'textarea'): ?>
                            <textarea name="<?= $key ?>" id="setting_<?= $key ?>"
                                      class="form-control" rows="3"><?= htmlspecialchars((string)$currentVal) ?></textarea>

                            <?php else: ?>
                            <input type="text" name="<?= $key ?>" id="setting_<?= $key ?>"
                                   class="form-control" style="max-width:350px;"
                                   value="<?= htmlspecialchars((string)$currentVal) ?>">
                            <?php endif; ?>

                        </div>
                    </div>
                    <?php endforeach; ?>

                </div>
                <div class="card-footer d-flex justify-content-between align-items-center">
                    <span class="text-muted" style="font-size:.8rem;">
                        <i class="bi bi-clock me-1"></i>Last saved: <?= date('d M Y, H:i') ?>
                    </span>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Save <?= $label ?> Settings
                    </button>
                </div>
            </div>
        </form>

        <!-- Danger Zone (General tab only) -->
        <?php if ($tab === 'general'): ?>
        <div class="card mt-4 border-danger">
            <div class="card-header" style="background:#fef2f2;color:#991b1b;">
                <span><i class="bi bi-exclamation-triangle-fill me-2"></i>Danger Zone</span>
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h6 class="fw-bold mb-1">Clear Activity Logs</h6>
                        <p class="text-muted mb-0" style="font-size:.83rem;">
                            Permanently delete all activity log entries older than 90 days.
                            This cannot be undone.
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <button type="button"
                                class="btn btn-outline-danger btn-sm"
                                data-confirm="Clear all activity logs older than 90 days? This cannot be undone."
                                data-action="<?= url('admin/settings/clear-logs') ?>"
                                data-method="POST">
                            <i class="bi bi-trash-fill me-1"></i>Clear Old Logs
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<script>
// Update toggle labels dynamically
document.querySelectorAll('.form-check-input[type="checkbox"]').forEach(cb => {
    const label = cb.nextElementSibling;
    if (label) {
        cb.addEventListener('change', () => {
            label.textContent = cb.checked ? 'Enabled' : 'Disabled';
        });
    }
});
</script>

<?php
$content = ob_get_clean();
ob_start();
require base_path('resources/views/layouts/app.php');
echo ob_get_clean();
