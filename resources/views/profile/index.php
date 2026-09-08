<?php
/**
 * User Profile View
 */
use App\Core\Csrf;
use App\Core\Session;
use App\Models\User;

/** @var array $user */
$session   = Session::getInstance();
$csrfToken = Csrf::getToken();
$errors    = $session->getFlash('errors', []);
$old       = $session->getFlash('old', []);
$u         = $user ?? $authUser ?? [];
$fullName  = User::fullName($u);
$avatarUrl = User::avatarUrl($u);
$title     = 'My Profile';
ob_start();
?>

<div class="page-header">
    <div>
        <h1 class="page-title">My Profile</h1>
        <p class="page-subtitle">Manage your account information and preferences.</p>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: User Summary Card -->
    <div class="col-lg-4">
        <div class="card text-center p-4">
            <div class="position-relative d-inline-block mx-auto mb-3">
                <img src="<?= htmlspecialchars($avatarUrl) ?>"
                     alt="<?= htmlspecialchars($fullName) ?>"
                     class="rounded-circle shadow-sm"
                     width="110" height="110" style="object-fit:cover;border:3px solid var(--border-color);">
            </div>

            <h5 class="fw-bold mb-1"><?= htmlspecialchars($fullName) ?></h5>
            <p class="text-muted small mb-2"><?= htmlspecialchars($u['email']) ?></p>
            <div>
                <?= role_badge($u['role']) ?>
            </div>

            <hr class="my-3">

            <div class="text-start small text-muted d-flex flex-column gap-2">
                <div>
                    <i class="bi bi-building me-2 text-primary"></i>
                    <strong>Company:</strong> <?= htmlspecialchars($u['company_name'] ?? 'None / Portal Staff') ?>
                </div>
                <div>
                    <i class="bi bi-calendar-event me-2 text-primary"></i>
                    <strong>Member Since:</strong> <?= htmlspecialchars(date('d M Y', strtotime($u['created_at']))) ?>
                </div>
                <?php if (!empty($u['last_login'])): ?>
                <div>
                    <i class="bi bi-clock-history me-2 text-primary"></i>
                    <strong>Last Login:</strong> <?= htmlspecialchars(format_datetime($u['last_login'])) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Profile Edit Form -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <span><i class="bi bi-person-gear me-2 text-primary"></i>Account Details</span>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= url('profile') ?>" enctype="multipart/form-data">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                    <div class="row g-3">
                        <!-- First Name -->
                        <div class="col-md-6">
                            <label class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control <?= isset($errors['first_name']) ? 'is-invalid' : '' ?>"
                                   value="<?= htmlspecialchars($old['first_name'] ?? $u['first_name']) ?>" required>
                            <?php if (isset($errors['first_name'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['first_name']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Last Name -->
                        <div class="col-md-6">
                            <label class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control <?= isset($errors['last_name']) ? 'is-invalid' : '' ?>"
                                   value="<?= htmlspecialchars($old['last_name'] ?? $u['last_name']) ?>" required>
                            <?php if (isset($errors['last_name'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['last_name']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Email -->
                        <div class="col-md-6">
                            <label class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                                   value="<?= htmlspecialchars($old['email'] ?? $u['email']) ?>" required>
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['email']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Phone -->
                        <div class="col-md-6">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>"
                                   value="<?= htmlspecialchars($old['phone'] ?? $u['phone'] ?? '') ?>"
                                   placeholder="+1 555-0199">
                            <?php if (isset($errors['phone'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['phone']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Avatar Upload -->
                        <div class="col-12">
                            <label class="form-label">Profile Avatar</label>
                            <input type="file" name="avatar" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp">
                            <div class="form-text">Allowed: JPG, PNG, GIF, WEBP. Max size 2MB.</div>
                            <?php if (!empty($u['avatar'])): ?>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="remove_avatar" value="1" id="removeAvatar">
                                <label class="form-check-label text-danger small" for="removeAvatar">
                                    Remove current avatar
                                </label>
                            </div>
                            <?php endif; ?>
                        </div>

                        <hr class="my-3">

                        <!-- Password Change Header -->
                        <div class="col-12">
                            <h6 class="fw-bold mb-1">Change Password</h6>
                            <p class="text-muted small mb-0">Leave password fields blank if you do not wish to change your password.</p>
                        </div>

                        <!-- New Password -->
                        <div class="col-md-6">
                            <label class="form-label">New Password</label>
                            <input type="password" name="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                                   placeholder="Minimum 8 characters" autocomplete="new-password">
                            <?php if (isset($errors['password'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['password']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12 text-end mt-4">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-check-lg me-1"></i> Save Changes
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/app.php');
