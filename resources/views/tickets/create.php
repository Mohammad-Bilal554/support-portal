<?php
use App\Core\Session;
use App\Core\Csrf;
use App\Models\User;

$session   = Session::getInstance();
$errors    = $session->getFlash('errors') ?? [];
$old       = $session->getFlash('old')    ?? [];
$csrfToken = Csrf::getToken();
$user      = $authUser;
$title     = 'New Ticket';
ob_start();
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Create New Ticket</h1>
        <p class="page-subtitle">Describe your issue and we'll get back to you as soon as possible.</p>
    </div>
    <a href="<?= url('tickets') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
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

<form action="<?= url('tickets') ?>" method="POST" enctype="multipart/form-data" novalidate>
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

    <div class="row g-4">

        <!-- Left: Main fields -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <span><i class="bi bi-ticket-perforated-fill me-2 text-primary"></i>Ticket Details</span>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Subject <span class="text-danger">*</span></label>
                        <input type="text" name="subject"
                               class="form-control <?= isset($errors['subject']) ? 'is-invalid' : '' ?>"
                               value="<?= htmlspecialchars($old['subject'] ?? '') ?>"
                               placeholder="Brief description of the issue…"
                               autofocus required>
                        <?php if (isset($errors['subject'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['subject'][0]) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea name="description" rows="8"
                                  class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>"
                                  placeholder="Please describe the issue in detail. Include steps to reproduce, error messages, and any relevant information…"
                                  required><?= htmlspecialchars($old['description'] ?? '') ?></textarea>
                        <?php if (isset($errors['description'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['description'][0]) ?></div>
                        <?php endif; ?>
                        <div style="font-size:.75rem;color:var(--text-muted);margin-top:.4rem;">
                            Minimum 10 characters. The more detail you provide, the faster we can help.
                        </div>
                    </div>

                    <!-- Attachments -->
                    <div class="mb-0">
                        <label class="form-label">Attachments</label>
                        <div class="attachment-drop-zone" id="dropZone"
                             style="border:2px dashed var(--border-color);border-radius:10px;padding:1.5rem;text-align:center;cursor:pointer;transition:all .2s;">
                            <i class="bi bi-cloud-upload" style="font-size:1.75rem;color:var(--text-muted);display:block;margin-bottom:.5rem;"></i>
                            <div style="font-size:.875rem;color:var(--text-secondary);">
                                Drag & drop files here, or <label for="attachments" style="color:var(--primary);cursor:pointer;font-weight:500;">browse</label>
                            </div>
                            <div style="font-size:.75rem;color:var(--text-muted);margin-top:.3rem;">
                                PDF, DOC, XLS, PNG, JPG, ZIP – Max 10MB each
                            </div>
                            <input type="file" name="attachments[]" id="attachments"
                                   multiple class="d-none"
                                   accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.gif,.zip,.txt,.csv">
                        </div>
                        <div id="fileList" class="mt-2"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Meta -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <span><i class="bi bi-sliders me-2 text-primary"></i>Ticket Settings</span>
                </div>
                <div class="card-body">

                    <div class="mb-3">
                        <label class="form-label">Priority <span class="text-danger">*</span></label>
                        <select name="priority" class="form-select" required>
                            <?php foreach (\App\Models\Ticket::PRIORITIES as $val => $label): ?>
                            <option value="<?= $val ?>"
                                <?= ($old['priority'] ?? 'medium') === $val ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">Select Category…</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"
                                <?= ($old['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if (in_array($user['role'], ['super_admin', 'employee'])): ?>
                    <div class="mb-3">
                        <label class="form-label">Assign To</label>
                        <select name="assigned_to" class="form-select">
                            <option value="">Unassigned</option>
                            <?php foreach ($employees as $emp): ?>
                            <option value="<?= $emp['id'] ?>"
                                <?= ($old['assigned_to'] ?? '') == $emp['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars(User::fullName($emp)) ?>
                                (<?= ucfirst($emp['role']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Company</label>
                        <select name="company_id" class="form-select">
                            <option value="">No Company</option>
                            <?php foreach ($companies as $c): ?>
                            <option value="<?= $c['id'] ?>"
                                <?= ($old['company_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Due Date</label>
                        <input type="date" name="due_date" class="form-control"
                               value="<?= htmlspecialchars($old['due_date'] ?? '') ?>"
                               min="<?= date('Y-m-d') ?>">
                    </div>
                    <?php endif; ?>

                </div>
            </div>

            <!-- Priority Guide -->
            <div class="card">
                <div class="card-header">
                    <span><i class="bi bi-info-circle me-2 text-primary"></i>Priority Guide</span>
                </div>
                <div class="card-body p-0">
                    <?php foreach ([
                        ['critical','#1e293b','text-white','System down, data loss risk'],
                        ['high',    '#fef2f2','text-danger','Major feature broken'],
                        ['medium',  '#fffbeb','text-warning','Feature degraded'],
                        ['low',     '#f0fdf4','text-success','Minor issue or question'],
                    ] as [$p, $bg, $tc, $desc]): ?>
                    <div class="d-flex align-items-center gap-3 px-3 py-2 border-bottom"
                         style="background:<?= $bg ?>;font-size:.8rem;">
                        <?= priority_badge($p) ?>
                        <span style="color:var(--text-secondary);"><?= $desc ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-3 mt-2">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-ticket-perforated-fill me-1"></i>Submit Ticket
        </button>
        <a href="<?= url('tickets') ?>" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>

<script>
// Drag & drop + file list
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('attachments');
const fileList  = document.getElementById('fileList');

dropZone.addEventListener('click', () => fileInput.click());
dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.style.borderColor = 'var(--primary)'; dropZone.style.background = 'var(--primary-light)'; });
dropZone.addEventListener('dragleave', () => { dropZone.style.borderColor = 'var(--border-color)'; dropZone.style.background = ''; });
dropZone.addEventListener('drop', e => {
    e.preventDefault();
    dropZone.style.borderColor = 'var(--border-color)'; dropZone.style.background = '';
    const dt = new DataTransfer();
    [...fileInput.files, ...e.dataTransfer.files].forEach(f => dt.items.add(f));
    fileInput.files = dt.files;
    renderFileList(fileInput.files);
});
fileInput.addEventListener('change', () => renderFileList(fileInput.files));

function renderFileList(files) {
    const icons = { pdf:'bi-file-earmark-pdf text-danger', doc:'bi-file-earmark-word text-primary',
                    xls:'bi-file-earmark-excel text-success', png:'bi-file-earmark-image text-info',
                    jpg:'bi-file-earmark-image text-info', jpeg:'bi-file-earmark-image text-info',
                    zip:'bi-file-earmark-zip text-warning' };
    let html = '';
    [...files].forEach((f, i) => {
        const ext  = f.name.split('.').pop().toLowerCase();
        const icon = icons[ext] || 'bi-file-earmark';
        const size = f.size < 1024*1024 ? (f.size/1024).toFixed(1)+'KB' : (f.size/1024/1024).toFixed(1)+'MB';
        html += `<div class="d-flex align-items-center gap-2 p-2 border rounded mb-1" style="font-size:.82rem;">
            <i class="bi ${icon} fs-5"></i>
            <span class="flex-fill text-truncate">${f.name}</span>
            <span class="text-muted">${size}</span>
        </div>`;
    });
    fileList.innerHTML = html;
}
</script>

<?php
$content = ob_get_clean();
ob_start();
require base_path('resources/views/layouts/app.php');
echo ob_get_clean();
