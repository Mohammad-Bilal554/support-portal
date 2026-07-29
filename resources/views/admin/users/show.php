<?php
/**
 * User Profile / Show View (for viewing a user's full profile + ticket history)
 */
use App\Models\User;
$title = 'User Profile';
ob_start();
?>

<div class="page-header">
    <div>
        <h1 class="page-title">User Profile</h1>
        <p class="page-subtitle"><?= htmlspecialchars(User::fullName($editUser)) ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('admin/users/' . $editUser['id']) ?>" class="btn btn-primary">
            <i class="bi bi-pencil-fill me-1"></i>Edit User
        </a>
        <a href="<?= url('admin/users') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Profile Card -->
    <div class="col-lg-4">
        <div class="card text-center mb-4">
            <div class="card-body py-4">
                <img src="<?= htmlspecialchars(User::avatarUrl($editUser)) ?>"
                     width="90" height="90"
                     style="border-radius:50%;object-fit:cover;border:3px solid var(--border-color);margin-bottom:1rem;">
                <h5 class="fw-bold mb-1"><?= htmlspecialchars(User::fullName($editUser)) ?></h5>
                <div class="mb-2"><?= role_badge($editUser['role']) ?></div>
                <div style="font-size:.825rem;color:var(--text-muted);"><?= htmlspecialchars($editUser['email']) ?></div>
                <?php if ($editUser['phone']): ?>
                <div style="font-size:.825rem;color:var(--text-muted);"><?= htmlspecialchars($editUser['phone']) ?></div>
                <?php endif; ?>
                <div class="mt-3">
                    <?php if ($editUser['is_active']): ?>
                    <span class="badge" style="background:#dcfce7;color:#166534;">Active</span>
                    <?php else: ?>
                    <span class="badge" style="background:#fee2e2;color:#991b1b;">Inactive</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Details -->
        <div class="card">
            <div class="card-header"><span>Account Details</span></div>
            <div class="card-body p-0">
                <?php foreach ([
                    ['Company',       $editUser['company_name'] ?? '—', 'bi-building'],
                    ['Last Login',    $editUser['last_login'] ? time_ago($editUser['last_login']) : 'Never', 'bi-clock'],
                    ['Member Since',  format_date($editUser['created_at']), 'bi-calendar'],
                    ['Email Verified',$editUser['email_verified'] ? 'Yes' : 'No', 'bi-envelope-check'],
                ] as [$label, $val, $icon]): ?>
                <div class="d-flex align-items-center gap-3 px-3 py-2 border-bottom" style="font-size:.83rem;">
                    <i class="bi <?= $icon ?> text-primary" style="width:16px;"></i>
                    <span class="text-muted flex-fill"><?= $label ?></span>
                    <strong><?= htmlspecialchars((string)$val) ?></strong>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Ticket History -->
    <div class="col-lg-8">
        <div class="table-card">
            <div class="card-header">
                <span><i class="bi bi-ticket-perforated me-2 text-primary"></i>Ticket History</span>
                <span class="badge bg-secondary"><?= count($tickets ?? []) ?></span>
            </div>
            <?php if (empty($tickets)): ?>
            <div class="empty-state">
                <div class="empty-state-icon"><i class="bi bi-ticket-perforated"></i></div>
                <h6>No tickets yet</h6>
                <p>This user hasn't created any tickets.</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $t): ?>
                        <tr style="cursor:pointer;" onclick="window.location='<?= url('tickets/'.$t['id']) ?>'">
                            <td><code style="font-size:.75rem;"><?= htmlspecialchars($t['ticket_number']) ?></code></td>
                            <td style="max-width:200px;" class="text-truncate"><?= htmlspecialchars($t['subject']) ?></td>
                            <td><?= status_badge($t['status']) ?></td>
                            <td><?= priority_badge($t['priority']) ?></td>
                            <td style="font-size:.8rem;color:var(--text-muted);"><?= time_ago($t['created_at']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
ob_start();
require base_path('resources/views/layouts/app.php');
echo ob_get_clean();
