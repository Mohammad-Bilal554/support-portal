<?php
use App\Core\Session;
use App\Core\Csrf;
use App\Models\User;
use App\Models\Ticket;

/** @var array $authUser */
/** @var array $ticket */
/** @var array $employees */
/** @var array $conversations */
/** @var array $attachments */
/** @var array $statusHistory */
/** @var array $categories */
/** @var array $transitions */

$session       = Session::getInstance();
$csrfToken     = Csrf::getToken();
$user          = $authUser ?? [];
$t             = $ticket ?? [];
$employees     = $employees ?? [];
$conversations = $conversations ?? [];
$attachments   = $attachments ?? [];
$statusHistory = $statusHistory ?? [];
$categories    = $categories ?? [];
$transitions   = $transitions ?? [];
$isStaff       = in_array($user['role'] ?? '', ['super_admin', 'employee']);
$title         = $t['ticket_number'] ?? 'Ticket';
ob_start();
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
            <code style="font-size:.85rem;background:#f1f5f9;padding:.25rem .6rem;border-radius:6px;color:var(--text-muted);">
                <?= htmlspecialchars($t['ticket_number']) ?>
            </code>
            <?= status_badge($t['status']) ?>
            <?= priority_badge($t['priority']) ?>
            <?php if ($t['category_name']): ?>
            <span class="badge rounded-pill"
                  style="background:<?= htmlspecialchars($t['category_color']??'#6c757d') ?>22;
                         color:<?= htmlspecialchars($t['category_color']??'#6c757d') ?>;
                         border:1px solid <?= htmlspecialchars($t['category_color']??'#6c757d') ?>44;
                         font-size:.72rem;">
                <?= htmlspecialchars($t['category_name']) ?>
            </span>
            <?php endif; ?>
        </div>
        <h1 class="page-title" style="font-size:1.2rem;"><?= htmlspecialchars($t['subject']) ?></h1>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if ($isStaff): ?>
        <a href="<?= url('tickets/' . $t['id'] . '/edit') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-pencil-fill me-1"></i>Edit
        </a>
        <?php endif; ?>
        <?php if (in_array($user['role'], ['super_admin'])): ?>
        <button class="btn btn-outline-danger btn-sm"
                data-confirm="Delete ticket <?= htmlspecialchars($t['ticket_number']) ?>? This cannot be undone."
                data-action="<?= url('tickets/' . $t['id']) ?>"
                data-method="DELETE">
            <i class="bi bi-trash-fill me-1"></i>Delete
        </button>
        <?php endif; ?>
        <a href="<?= url('tickets') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>
</div>

<div class="row g-4">

    <!-- ── Left: Conversation Thread ─────────────────────────── -->
    <div class="col-lg-8">

        <!-- Original description -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <img src="<?= htmlspecialchars(User::avatarUrl(['first_name'=>$t['creator_first'],'last_name'=>$t['creator_last'],'avatar'=>$t['creator_avatar']??null])) ?>"
                         width="40" height="40"
                         style="border-radius:50%;object-fit:cover;border:2px solid var(--border-color);">
                    <div>
                        <div style="font-weight:600;font-size:.9rem;">
                            <?= htmlspecialchars(trim(($t['creator_first']??'').' '.($t['creator_last']??''))) ?>
                            <span class="badge bg-primary ms-1" style="font-size:.62rem;">Reporter</span>
                        </div>
                        <div style="font-size:.775rem;color:var(--text-muted);">
                            <?= format_datetime($t['created_at']) ?>
                        </div>
                    </div>
                </div>
                <div style="font-size:.9rem;line-height:1.75;color:var(--text-primary);">
                    <?= nl2br(htmlspecialchars($t['description'])) ?>
                </div>

                <!-- Ticket-level attachments -->
                <?php if (!empty($attachments)): ?>
                <div class="mt-3 pt-3 border-top">
                    <div style="font-size:.8rem;font-weight:600;color:var(--text-secondary);margin-bottom:.5rem;">
                        <i class="bi bi-paperclip me-1"></i>Attachments
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($attachments as $att): ?>
                        <?php
                            $ext  = strtolower(pathinfo($att['original_name'], PATHINFO_EXTENSION));
                            $isImg = in_array($ext, ['jpg','jpeg','png','gif','webp']);
                            $icons = ['pdf'=>'bi-file-earmark-pdf text-danger','doc'=>'bi-file-earmark-word text-primary','docx'=>'bi-file-earmark-word text-primary','xls'=>'bi-file-earmark-excel text-success','xlsx'=>'bi-file-earmark-excel text-success','zip'=>'bi-file-earmark-zip text-warning'];
                            $icon  = $icons[$ext] ?? 'bi-file-earmark text-secondary';
                        ?>
                        <a href="<?= url('tickets/attachments/' . $att['id'] . '/download') ?>"
                           class="d-flex align-items-center gap-2 p-2 border rounded text-decoration-none"
                           style="font-size:.78rem;max-width:200px;background:#f8fafc;transition:all .15s;"
                           title="Download <?= htmlspecialchars($att['original_name']) ?>">
                            <i class="bi <?= $icon ?> fs-5 flex-shrink-0"></i>
                            <div style="min-width:0;">
                                <div class="text-truncate" style="color:var(--text-primary);font-weight:500;">
                                    <?= htmlspecialchars($att['original_name']) ?>
                                </div>
                                <div style="color:var(--text-muted);"><?= format_bytes($att['file_size']) ?></div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Conversation Timeline -->
        <?php if (!empty($conversations)): ?>
        <div class="timeline mb-3" id="conversationList">
            <?php foreach ($conversations as $conv): ?>
            <div class="timeline-item" id="reply-<?= $conv['id'] ?>">
                <div class="timeline-icon <?= $conv['is_internal'] ? 'bg-warning bg-opacity-25' : 'bg-primary bg-opacity-10' ?>">
                    <img src="<?= htmlspecialchars(User::avatarUrl(['first_name'=>$conv['first_name'],'last_name'=>$conv['last_name'],'avatar'=>$conv['avatar']??null])) ?>"
                         width="36" height="36"
                         style="border-radius:50%;object-fit:cover;">
                </div>
                <div class="timeline-content <?= $conv['is_internal'] ? 'internal' : '' ?>">
                    <div class="timeline-meta">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="timeline-author">
                                <?= htmlspecialchars(trim(($conv['first_name']??'').' '.($conv['last_name']??''))) ?>
                            </span>
                            <?php if ($conv['is_internal']): ?>
                            <span class="badge bg-warning text-dark" style="font-size:.63rem;">
                                <i class="bi bi-lock-fill me-1"></i>Internal Note
                            </span>
                            <?php endif; ?>
                            <span class="timeline-time" title="<?= htmlspecialchars($conv['created_at']) ?>">
                                <?= time_ago($conv['created_at']) ?>
                            </span>
                        </div>
                        <?php if ($isStaff || (int)$conv['user_id'] === (int)$user['id']): ?>
                        <button class="btn btn-sm btn-icon text-muted ms-auto"
                                onclick="deleteReply(<?= $conv['id'] ?>, this)"
                                title="Delete reply"
                                style="font-size:.8rem;">
                            <i class="bi bi-trash"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                    <div style="font-size:.875rem;line-height:1.7;">
                        <?= nl2br(htmlspecialchars($conv['message'])) ?>
                    </div>

                    <!-- Reply attachments -->
                    <?php if (!empty($conv['attachments'])): ?>
                    <div class="d-flex flex-wrap gap-2 mt-2 pt-2 border-top">
                        <?php foreach ($conv['attachments'] as $att): ?>
                        <a href="<?= url('tickets/attachments/' . $att['id'] . '/download') ?>"
                           class="d-flex align-items-center gap-1 p-1 border rounded text-decoration-none"
                           style="font-size:.75rem;background:#f8fafc;">
                            <i class="bi bi-paperclip text-muted"></i>
                            <span class="text-truncate" style="max-width:140px;color:var(--text-primary);">
                                <?= htmlspecialchars($att['original_name']) ?>
                            </span>
                            <span style="color:var(--text-muted);"><?= format_bytes((int)$att['file_size']) ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Reply Form -->
        <?php if (!in_array($t['status'], ['closed'])): ?>
        <div class="card" id="replyCard">
            <div class="card-header">
                <span><i class="bi bi-reply-fill me-2 text-primary"></i>
                    <?= $isStaff ? 'Add Reply or Internal Note' : 'Add Reply' ?>
                </span>
            </div>
            <div class="card-body">
                <form id="replyForm" enctype="multipart/form-data">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                    <?php if ($isStaff): ?>
                    <!-- Internal/Public toggle -->
                    <div class="d-flex gap-2 mb-3">
                        <button type="button" class="btn btn-sm btn-primary" id="btnPublic"
                                onclick="setReplyType(false)">
                            <i class="bi bi-chat-fill me-1"></i>Public Reply
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-warning" id="btnInternal"
                                onclick="setReplyType(true)">
                            <i class="bi bi-lock-fill me-1"></i>Internal Note
                        </button>
                    </div>
                    <input type="hidden" name="is_internal" id="isInternal" value="0">
                    <?php endif; ?>

                    <div class="mb-3">
                        <textarea name="message" id="replyMessage" rows="4"
                                  class="form-control"
                                  placeholder="Type your reply here…"></textarea>
                    </div>

                    <!-- Attach files to reply -->
                    <div class="mb-3">
                        <label for="replyFiles" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-paperclip me-1"></i>Attach Files
                        </label>
                        <input type="file" name="attachments[]" id="replyFiles"
                               multiple class="d-none"
                               accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.gif,.zip,.txt">
                        <div id="replyFileList" class="mt-2 d-flex flex-wrap gap-2"></div>
                    </div>

                    <div class="d-flex gap-2 align-items-center">
                        <button type="submit" class="btn btn-primary" id="replySubmitBtn">
                            <i class="bi bi-send-fill me-1"></i>Send Reply
                        </button>
                        <span id="replySpinner" class="d-none">
                            <span class="spinner-border spinner-border-sm me-2"></span>Sending…
                        </span>
                    </div>
                </form>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle-fill me-2"></i>
            This ticket is closed. Reopen it to add a reply.
        </div>
        <?php endif; ?>
    </div>

    <!-- ── Right: Ticket Info Sidebar ───────────────────────── -->
    <div class="col-lg-4">

        <!-- Status Change -->
        <?php if (!empty($transitions)): ?>
        <div class="card mb-3">
            <div class="card-header">
                <span><i class="bi bi-arrow-repeat me-2 text-primary"></i>Change Status</span>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($transitions as $status): ?>
                    <?php
                        $btnColors = [
                            'open' => 'btn-outline-danger',
                            'assigned' => 'btn-outline-warning',
                            'in_progress' => 'btn-outline-primary',
                            'waiting_for_client' => 'btn-outline-info',
                            'resolved' => 'btn-outline-success',
                            'closed' => 'btn-outline-secondary',
                        ];
                        $btnColor = $btnColors[$status] ?? 'btn-outline-secondary';
                    ?>
                    <button class="btn btn-sm <?= $btnColor ?>"
                            onclick="changeStatus('<?= $status ?>')">
                        <?= htmlspecialchars(Ticket::STATUSES[$status] ?? ucfirst($status)) ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Assign Ticket (staff only) -->
        <?php if ($isStaff): ?>
        <div class="card mb-3">
            <div class="card-header">
                <span><i class="bi bi-person-check-fill me-2 text-primary"></i>Assignment</span>
            </div>
            <div class="card-body">
                <?php if ($t['assignee_first']): ?>
                <div class="d-flex align-items-center gap-2 mb-3">
                    <img src="<?= htmlspecialchars(User::avatarUrl(['first_name'=>$t['assignee_first'],'last_name'=>$t['assignee_last'],'avatar'=>$t['assignee_avatar']??null])) ?>"
                         width="34" height="34"
                         style="border-radius:50%;object-fit:cover;border:2px solid var(--border-color);">
                    <div>
                        <div style="font-size:.85rem;font-weight:600;">
                            <?= htmlspecialchars(trim($t['assignee_first'].' '.$t['assignee_last'])) ?>
                        </div>
                        <div style="font-size:.75rem;color:var(--text-muted);">Currently assigned</div>
                    </div>
                </div>
                <?php endif; ?>
                <div class="d-flex gap-2">
                    <select id="assigneeSelect" class="form-select form-select-sm">
                        <option value="">Select employee…</option>
                        <?php foreach ($employees as $emp): ?>
                        <option value="<?= $emp['id'] ?>"
                            <?= ($t['assigned_to'] ?? 0) == $emp['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars(User::fullName($emp)) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-sm btn-primary" onclick="assignTicket()" style="white-space:nowrap;">
                        <i class="bi bi-check-lg"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Ticket Details -->
        <div class="card mb-3">
            <div class="card-header">
                <span><i class="bi bi-info-circle-fill me-2 text-primary"></i>Ticket Details</span>
            </div>
            <div class="card-body p-0">
                <?php
                $details = [
                    ['Status',    status_badge($t['status']),   'bi-circle-fill', false],
                    ['Priority',  priority_badge($t['priority']),'bi-flag-fill',   false],
                    ['Created',   format_datetime($t['created_at']), 'bi-calendar', true],
                    ['Updated',   time_ago($t['updated_at'] ?? $t['created_at']), 'bi-clock', true],
                    ['Reporter',  htmlspecialchars(trim(($t['creator_first']??'').' '.($t['creator_last']??''))), 'bi-person', true],
                    ['Company',   htmlspecialchars($t['company_name'] ?? '–'), 'bi-building', true],
                    ['Due Date',  $t['due_date'] ? format_date($t['due_date']) : '<span class="text-muted">Not set</span>', 'bi-calendar-event', false],
                ];
                foreach ($details as [$label, $val, $icon, $escape]):
                ?>
                <div class="d-flex align-items-center gap-3 px-3 py-2 border-bottom" style="font-size:.82rem;">
                    <i class="bi <?= $icon ?> text-primary" style="width:16px;flex-shrink:0;"></i>
                    <span class="text-muted" style="width:70px;flex-shrink:0;"><?= $label ?></span>
                    <div class="flex-fill"><?= $val ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Status History -->
        <?php if (!empty($statusHistory)): ?>
        <div class="card">
            <div class="card-header">
                <span><i class="bi bi-clock-history me-2 text-primary"></i>History</span>
            </div>
            <div class="card-body p-0">
                <?php foreach (array_reverse($statusHistory) as $h): ?>
                <div class="px-3 py-2 border-bottom" style="font-size:.78rem;">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <?php if ($h['old_status']): ?>
                        <span style="background:#f1f5f9;padding:.15rem .4rem;border-radius:4px;color:var(--text-muted);">
                            <?= htmlspecialchars(Ticket::STATUSES[$h['old_status']] ?? $h['old_status']) ?>
                        </span>
                        <i class="bi bi-arrow-right text-muted" style="font-size:.65rem;"></i>
                        <?php endif; ?>
                        <span style="background:#dbeafe;color:#1d4ed8;padding:.15rem .4rem;border-radius:4px;">
                            <?= htmlspecialchars(Ticket::STATUSES[$h['new_status']] ?? $h['new_status']) ?>
                        </span>
                    </div>
                    <div class="d-flex justify-content-between mt-1" style="color:var(--text-muted);">
                        <span><?= htmlspecialchars(trim(($h['first_name']??'').' '.($h['last_name']??''))) ?></span>
                        <span><?= time_ago($h['created_at']) ?></span>
                    </div>
                    <?php if ($h['note']): ?>
                    <div style="color:var(--text-secondary);margin-top:.2rem;font-style:italic;">
                        <?= htmlspecialchars($h['note']) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<!-- Status Change Modal -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h6 class="modal-title fw-bold">Change Status</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-0">
                <p class="text-muted mb-3" style="font-size:.85rem;">
                    Change to: <strong id="statusLabel"></strong>
                </p>
                <label class="form-label" style="font-size:.85rem;">Note (optional)</label>
                <textarea id="statusNote" class="form-control" rows="3"
                          placeholder="Reason for status change…"></textarea>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary" id="confirmStatusBtn">Confirm</button>
            </div>
        </div>
    </div>
</div>

<script>
const TICKET_ID  = <?= $t['id'] ?>;
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;

// ── Reply Form (AJAX) ─────────────────────────────────────────────
const replyForm = document.getElementById('replyForm');
if (replyForm) {
    replyForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const msg = document.getElementById('replyMessage').value.trim();
        const files = document.getElementById('replyFiles')?.files;
        if (!msg && (!files || files.length === 0)) {
            SupportPortal.showToast('Please write a message or attach a file.', 'warning');
            return;
        }
        const btn = document.getElementById('replySubmitBtn');
        const spin = document.getElementById('replySpinner');
        btn.classList.add('d-none');
        spin.classList.remove('d-none');

        const fd = new FormData(this);
        try {
            const res  = await fetch(`<?= url('tickets/' . $t['id'] . '/reply') ?>`, {
                method: 'POST',
                headers: { 'X-Requested-With':'XMLHttpRequest', 'X-CSRF-Token':CSRF_TOKEN },
                body: fd,
            });
            const data = await res.json();
            if (data.success) {
                document.getElementById('replyMessage').value = '';
                document.getElementById('replyFileList').innerHTML = '';
                if (document.getElementById('replyFiles')) document.getElementById('replyFiles').value = '';
                const list = document.getElementById('conversationList') ||
                    (() => { const d = document.createElement('div'); d.id='conversationList'; d.className='timeline mb-3'; replyForm.closest('.card').before(d); return d; })();
                list.insertAdjacentHTML('beforeend', data.html || '');
                SupportPortal.showToast('Reply sent successfully.', 'success');
                list.lastElementChild?.scrollIntoView({ behavior:'smooth', block:'center' });
            } else {
                SupportPortal.showToast(data.message || 'Failed to send reply.', 'danger');
            }
        } catch(err) {
            SupportPortal.showToast('Network error. Please try again.', 'danger');
        } finally {
            btn.classList.remove('d-none');
            spin.classList.add('d-none');
        }
    });
}

// ── Internal / Public toggle ──────────────────────────────────────
function setReplyType(internal) {
    document.getElementById('isInternal').value = internal ? '1' : '0';
    const card = document.getElementById('replyCard');
    const ta   = document.getElementById('replyMessage');
    if (internal) {
        card.style.borderColor = '#ffc107';
        ta.style.background    = '#fffbeb';
        ta.placeholder         = 'Internal note – only visible to staff…';
        document.getElementById('btnInternal').className = 'btn btn-sm btn-warning';
        document.getElementById('btnPublic').className   = 'btn btn-sm btn-outline-primary';
    } else {
        card.style.borderColor = '';
        ta.style.background    = '';
        ta.placeholder         = 'Type your reply here…';
        document.getElementById('btnPublic').className   = 'btn btn-sm btn-primary';
        document.getElementById('btnInternal').className = 'btn btn-sm btn-outline-warning';
    }
}

// ── Status change ─────────────────────────────────────────────────
let pendingStatus = null;

function getStatusModal() {
    const modalEl = document.getElementById('statusModal');
    if (!modalEl || typeof bootstrap === 'undefined') return null;
    return bootstrap.Modal.getOrCreateInstance(modalEl);
}

function changeStatus(status) {
    pendingStatus = status;
    const labels = <?= json_encode(Ticket::STATUSES) ?>;
    document.getElementById('statusLabel').textContent = labels[status] || status;
    document.getElementById('statusNote').value = '';
    const modal = getStatusModal();
    if (modal) modal.show();
}

document.addEventListener('DOMContentLoaded', () => {
    const confirmBtn = document.getElementById('confirmStatusBtn');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', async () => {
            if (!pendingStatus) return;
            const note = document.getElementById('statusNote').value;
            const modal = getStatusModal();
            if (modal) modal.hide();
            try {
                const res  = await fetch(`<?= url('tickets/' . $t['id'] . '/status') ?>`, {
                    method: 'POST',
                    headers: { 'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-Token':CSRF_TOKEN },
                    body: JSON.stringify({ status: pendingStatus, note }),
                });
                const data = await res.json();
                if (data.success) {
                    SupportPortal.showToast(data.message, 'success');
                    location.reload();
                } else {
                    SupportPortal.showToast(data.message, 'danger');
                }
            } catch(err) {
                SupportPortal.showToast('Network error.', 'danger');
            }
        });
    }
});

// ── Assign ticket ─────────────────────────────────────────────────
async function assignTicket() {
    const assigneeId = document.getElementById('assigneeSelect')?.value;
    if (!assigneeId) { SupportPortal.showToast('Please select an employee.', 'warning'); return; }
    try {
        const res  = await fetch(`<?= url('tickets/' . $t['id'] . '/assign') ?>`, {
            method: 'POST',
            headers: { 'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-Token':CSRF_TOKEN },
            body: JSON.stringify({ assigned_to: parseInt(assigneeId) }),
        });
        const data = await res.json();
        if (data.success) {
            SupportPortal.showToast(data.message, 'success');
            setTimeout(() => location.reload(), 900);
        } else {
            SupportPortal.showToast(data.message, 'danger');
        }
    } catch(err) {
        SupportPortal.showToast('Network error.', 'danger');
    }
}

// ── Delete reply ──────────────────────────────────────────────────
async function deleteReply(convId, btn) {
    SupportPortal.confirm('Delete this reply? This cannot be undone.', async () => {
        try {
            const res  = await fetch(`<?= url('tickets/conversations') ?>/${convId}`, {
                method: 'DELETE',
                headers: { 'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-Token':CSRF_TOKEN },
            });
            const data = await res.json();
            if (data.success) {
                document.getElementById('reply-' + convId)?.remove();
                SupportPortal.showToast('Reply deleted.', 'success');
            } else {
                SupportPortal.showToast(data.message, 'danger');
            }
        } catch(err) {
            SupportPortal.showToast('Network error.', 'danger');
        }
    }, { title:'Delete Reply', confirmText:'Delete', type:'danger' });
}

// ── Reply file preview ────────────────────────────────────────────
document.getElementById('replyFiles')?.addEventListener('change', function() {
    const list = document.getElementById('replyFileList');
    list.innerHTML = '';
    [...this.files].forEach(f => {
        const size = f.size < 1024*1024 ? (f.size/1024).toFixed(1)+'KB' : (f.size/1024/1024).toFixed(1)+'MB';
        list.insertAdjacentHTML('beforeend',
            `<div class="d-flex align-items-center gap-2 border rounded p-1" style="font-size:.75rem;">
                <i class="bi bi-paperclip text-muted"></i>
                <span class="text-truncate" style="max-width:120px;">${f.name}</span>
                <span class="text-muted">${size}</span>
            </div>`);
    });
});
</script>

<?php
$content = ob_get_clean();
ob_start();
require base_path('resources/views/layouts/app.php');
echo ob_get_clean();
