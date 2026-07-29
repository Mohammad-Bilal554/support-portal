<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Models\User;

class UserService
{
    private Database $db;
    private Logger   $logger;

    public function __construct()
    {
        $this->db     = Database::getInstance();
        $this->logger = Logger::getInstance();
    }

    // ── Create ────────────────────────────────────────────────────

    public function create(array $data, ?array $avatarFile = null): array
    {
        // Check email uniqueness
        if (User::emailExists($data['email'])) {
            return ['success' => false, 'message' => 'Email address is already taken.'];
        }

        $insertData = [
            'first_name'     => trim($data['first_name']),
            'last_name'      => trim($data['last_name']),
            'email'          => strtolower(trim($data['email'])),
            'password'       => User::hashPassword($data['password']),
            'role'           => $data['role'],
            'phone'          => $data['phone'] ?? null,
            'company_id'     => !empty($data['company_id']) ? (int)$data['company_id'] : null,
            'is_active'      => isset($data['is_active']) ? (int)(bool)$data['is_active'] : 1,
            'email_verified' => 1,
        ];

        // Handle avatar upload
        if ($avatarFile && $avatarFile['error'] === UPLOAD_ERR_OK) {
            $avatarResult = $this->uploadAvatar($avatarFile);
            if ($avatarResult['success']) {
                $insertData['avatar'] = $avatarResult['filename'];
            }
        }

        $id = User::create($insertData);

        $this->logActivity(
            auth_id() ?? $id,
            'user_created', 'user', $id,
            "User created: {$insertData['email']} (role: {$insertData['role']})"
        );

        $this->logger->info("User created: #{$id} {$insertData['email']}");

        return ['success' => true, 'message' => 'User created successfully.', 'id' => $id];
    }

    // ── Update ────────────────────────────────────────────────────

    public function update(int $id, array $data, ?array $avatarFile = null): array
    {
        $user = User::find($id);
        if (!$user) {
            return ['success' => false, 'message' => 'User not found.'];
        }

        // Check email uniqueness (excluding this user)
        if (!empty($data['email']) && User::emailExists($data['email'], $id)) {
            return ['success' => false, 'message' => 'Email address is already taken.'];
        }

        $updateData = array_filter([
            'first_name' => !empty($data['first_name']) ? trim($data['first_name']) : null,
            'last_name'  => !empty($data['last_name'])  ? trim($data['last_name'])  : null,
            'email'      => !empty($data['email'])       ? strtolower(trim($data['email'])) : null,
            'role'       => $data['role']       ?? null,
            'phone'      => $data['phone']      ?? null,
            'company_id' => !empty($data['company_id']) ? (int)$data['company_id'] : null,
            'is_active'  => isset($data['is_active']) ? (int)(bool)$data['is_active'] : null,
        ], fn($v) => $v !== null);

        // Update password only if provided
        if (!empty($data['password'])) {
            $updateData['password'] = User::hashPassword($data['password']);
        }

        // Handle avatar upload
        if ($avatarFile && $avatarFile['error'] === UPLOAD_ERR_OK) {
            $avatarResult = $this->uploadAvatar($avatarFile, $user['avatar'] ?? null);
            if ($avatarResult['success']) {
                $updateData['avatar'] = $avatarResult['filename'];
            }
        }

        // Handle avatar removal
        if (!empty($data['remove_avatar']) && !empty($user['avatar'])) {
            $this->deleteAvatar($user['avatar']);
            $updateData['avatar'] = null;
        }

        User::updateById($id, $updateData);

        $this->logActivity(
            auth_id() ?? $id,
            'user_updated', 'user', $id,
            "User updated: #{$id}"
        );

        $this->logger->info("User updated: #{$id}");

        return ['success' => true, 'message' => 'User updated successfully.'];
    }

    // ── Delete ────────────────────────────────────────────────────

    public function delete(int $id): array
    {
        $user = User::find($id);
        if (!$user) {
            return ['success' => false, 'message' => 'User not found.'];
        }

        // Prevent self-deletion
        if ($id === auth_id()) {
            return ['success' => false, 'message' => 'You cannot delete your own account.'];
        }

        // Delete avatar file if exists
        if (!empty($user['avatar'])) {
            $this->deleteAvatar($user['avatar']);
        }

        // Soft delete: just deactivate (keeps ticket history intact)
        User::updateById($id, ['is_active' => 0]);

        $this->logActivity(
            auth_id(),
            'user_deleted', 'user', $id,
            "User deactivated: {$user['email']}"
        );

        $this->logger->info("User deactivated: #{$id} {$user['email']}");

        return ['success' => true, 'message' => 'User deactivated successfully.'];
    }

    // ── Toggle active ─────────────────────────────────────────────

    public function toggleActive(int $id): array
    {
        $user = User::find($id);
        if (!$user) {
            return ['success' => false, 'message' => 'User not found.'];
        }

        if ($id === auth_id()) {
            return ['success' => false, 'message' => 'You cannot deactivate your own account.'];
        }

        $newStatus = $user['is_active'] ? 0 : 1;
        User::updateById($id, ['is_active' => $newStatus]);

        $action = $newStatus ? 'activated' : 'deactivated';

        $this->logActivity(
            auth_id(),
            'user_' . $action, 'user', $id,
            "User {$action}: {$user['email']}"
        );

        return [
            'success'   => true,
            'message'   => "User {$action} successfully.",
            'is_active' => $newStatus,
        ];
    }

    // ── Avatar upload ─────────────────────────────────────────────

    private function uploadAvatar(array $file, ?string $oldAvatar = null): array
    {
        $allowed   = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize   = 2 * 1024 * 1024; // 2MB
        $uploadDir = base_path('public/assets/uploads/avatars');

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if (!in_array($file['type'], $allowed)) {
            return ['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, WEBP allowed.'];
        }

        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => 'File too large. Maximum 2MB allowed.'];
        }

        // Delete old avatar
        if ($oldAvatar) {
            $this->deleteAvatar($oldAvatar);
        }

        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = uniqid('avatar_', true) . '.' . $ext;
        $destPath = $uploadDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            return ['success' => false, 'message' => 'Failed to upload file.'];
        }

        return ['success' => true, 'filename' => $filename];
    }

    private function deleteAvatar(string $filename): void
    {
        $path = base_path('public/assets/uploads/avatars/' . $filename);
        if (file_exists($path)) {
            unlink($path);
        }
    }

    // ── Activity log ──────────────────────────────────────────────

    private function logActivity(int $userId, string $action, string $entityType, int $entityId, string $desc): void
    {
        try {
            $this->db->insert('activity_logs', [
                'user_id'     => $userId,
                'action'      => $action,
                'entity_type' => $entityType,
                'entity_id'   => $entityId,
                'description' => $desc,
                'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent'  => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Activity log failed: ' . $e->getMessage());
        }
    }
}
