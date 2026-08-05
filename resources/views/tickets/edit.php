<?php
use App\Core\Session;
use App\Core\Csrf;
use App\Models\User;
use App\Models\Ticket;

$session   = Session::getInstance();
$errors    = $session->getFlash('errors') ?? [];
$csrfToken = Csrf::getToken();
$t         = $ticket;
$user      = $authUser;
$title     = 'Edit Ticket';
ob_start();
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Edit Ticket</h1>
        <p class="page-subtitle">
            <code style="font-size:.85rem;background:#f1f5f9;padding:.2rem .5rem;border-radius:5px;">
                <?= htmlspecialchars($t['ticket_number']) ?>
            </code>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('tickets/' . $t['id']) ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-eye-fill me-1"></i>View Ticket
        </a>
        <a href="<?= url('tickets') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger mb-4">
    <i class="bi bi-exclamation-triangle-fill me-2 flex-shrink-0"></i>
    <div>
        <strong>Please fix the following:</strong>
        <ul class="mb-0 mt-1 ps-3">
            <?php foreach ($errors as $field => $msgs): ?>
                <?php foreach ((array)$msgs as $msg): ?>
                    <li style="font-size:.875rem;"><?= htmlspecialchars($msg) ?></li>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>

<form action="<?= url('tickets/' . $t['id']) ?>" method="POST" novalidate>
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

    <div class="row g-4">

        <!-- Left: Main Fields -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <span><i class="bi bi-ticket-perforated-fill me-2 text-primary"></i>Ticket Details</span>
                    <div class="d-flex gap-2">
                        <?= status_badge($t['status']) ?>
                        <?= priority_badge($t['priority']) ?>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Subject <span class="text-danger">*</span></label>
                        <input type="text" name="subject"
                               class="form-control <?= isset($errors['subject']) ? 'is-invalid' : '' ?>"
                               value="<?= htmlspecialchars($t['subject']) ?>"
                               required autofocus>
                        <?php if (isset($errors['subject'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['subject'][0]) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea name="description" rows="10"
                                  class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>"
                                  required><?= htmlspecialchars($t['description']) ?></textarea>
                        <?php if (isset($errors['description'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['description'][0]) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Settings -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <span><i class="bi bi-sliders me-2 text-primary"></i>Settings</span>
                </div>
                <div class="card-body">

                    <div class="mb-3">
                        <label class="form-label">Priority <span class="text-danger">*</span></label>
                        <select name="priority" class="form-select" required>
                            <?php foreach (Ticket::PRIORITIES as $val => $label): ?>
                            <option value="<?= $val ?>" <?= $t['priority'] === $val ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">No Category</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"
                                <?= $t['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Due Date</label>
                        <input type="date" name="due_date" class="form-control"
                               value="<?= htmlspecialchars($t['due_date'] ?? '') ?>">
                    </div>

                    <!-- Info block -->
                    <div style="background:#f8fafc;border-radius:8px;padding:.85rem;font-size:.8rem;">
                        <?php foreach ([
                            ['Reporter', htmlspecialchars(trim(($t['creator_first']??'').' '.($t['creator_last']??'')))],
                            ['Assignee', $t['assignee_first'] ? htmlspecialchars(trim($t['assignee_first'].' '.$t['assignee_last'])) : '<span class="text-muted">Unassigned</span>'],
                            ['Company',  htmlspecialchars($t['company_name'] ?? '—')],
                            ['Created',  format_date($t['created_at'])],
                        ] as [$lbl, $val]): ?>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted"><?= $lbl ?></span>
                            <strong><?= $val ?></strong>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-3 mt-1">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg me-1"></i>Save Changes
        </button>
        <a href="<?= url('tickets/' . $t['id']) ?>" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>

<?php
$content = ob_get_clean();
ob_start();
require base_path('resources/views/layouts/app.php');
echo ob_get_clean();
