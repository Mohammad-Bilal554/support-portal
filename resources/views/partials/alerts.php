<?php
/**
 * Reusable alert partial
 * Usage: <?php include partial('alerts'); ?>
 * Or pass $alerts = [['type'=>'success','message'=>'...']]
 */
use App\Core\Session;
$session  = Session::getInstance();
$_success = $success ?? $session->getFlash('success');
$_error   = $error   ?? $session->getFlash('error');
$_warning = $warning ?? $session->getFlash('warning');
$_info    = $info    ?? $session->getFlash('info');
$_errors  = $errors  ?? $session->getFlash('errors') ?? [];
?>
<?php if ($_success): ?>
<div class="alert alert-success alert-dismissible fade show" data-auto-dismiss role="alert">
    <i class="bi bi-check-circle-fill flex-shrink-0"></i>
    <div><?= htmlspecialchars($_success) ?></div>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if ($_error): ?>
<div class="alert alert-danger alert-dismissible fade show" data-auto-dismiss role="alert">
    <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
    <div><?= htmlspecialchars($_error) ?></div>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if ($_warning): ?>
<div class="alert alert-warning alert-dismissible fade show" data-auto-dismiss role="alert">
    <i class="bi bi-exclamation-circle-fill flex-shrink-0"></i>
    <div><?= htmlspecialchars($_warning) ?></div>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if ($_info): ?>
<div class="alert alert-info alert-dismissible fade show" data-auto-dismiss role="alert">
    <i class="bi bi-info-circle-fill flex-shrink-0"></i>
    <div><?= htmlspecialchars($_info) ?></div>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if (!empty($_errors)): ?>
<div class="alert alert-danger alert-dismissible fade show" data-auto-dismiss role="alert">
    <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
    <div>
        <strong>Please fix the following errors:</strong>
        <ul class="mb-0 mt-1 ps-3">
            <?php foreach ($_errors as $field => $msgs): ?>
                <?php foreach ((array)$msgs as $msg): ?>
                    <li><?= htmlspecialchars($msg) ?></li>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </ul>
    </div>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
