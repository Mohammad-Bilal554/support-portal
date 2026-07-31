<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Models\Company;

class CompanyService
{
    private Database $db;
    private Logger   $logger;

    public function __construct()
    {
        $this->db     = Database::getInstance();
        $this->logger = Logger::getInstance();
    }

    // ── Create ────────────────────────────────────────────────────

    public function create(array $data, ?array $logoFile = null): array
    {
        if (Company::emailExists($data['email'] ?? '')) {
            return ['success' => false, 'message' => 'A company with this email already exists.'];
        }

        $insertData = [
            'name'      => trim($data['name']),
            'email'     => strtolower(trim($data['email'] ?? '')),
            'phone'     => trim($data['phone']   ?? ''),
            'address'   => trim($data['address'] ?? ''),
            'website'   => trim($data['website'] ?? ''),
            'is_active' => isset($data['is_active']) ? (int)(bool)$data['is_active'] : 1,
        ];

        if ($logoFile && $logoFile['error'] === UPLOAD_ERR_OK) {
            $result = $this->uploadLogo($logoFile);
            if ($result['success']) {
                $insertData['logo'] = $result['filename'];
            }
        }

        $id = Company::create($insertData);

        $this->logActivity(
            auth_id(),
            'company_created', 'company', (int)$id,
            "Company created: {$insertData['name']}"
        );

        $this->logger->info("Company created: #{$id} {$insertData['name']}");

        return ['success' => true, 'message' => 'Company created successfully.', 'id' => $id];
    }

    // ── Update ────────────────────────────────────────────────────

    public function update(int $id, array $data, ?array $logoFile = null): array
    {
        $company = Company::find($id);
        if (!$company) {
            return ['success' => false, 'message' => 'Company not found.'];
        }

        if (!empty($data['email']) && Company::emailExists($data['email'], $id)) {
            return ['success' => false, 'message' => 'A company with this email already exists.'];
        }

        $updateData = [
            'name'      => trim($data['name']    ?? $company['name']),
            'email'     => strtolower(trim($data['email']   ?? $company['email'])),
            'phone'     => trim($data['phone']   ?? ''),
            'address'   => trim($data['address'] ?? ''),
            'website'   => trim($data['website'] ?? ''),
            'is_active' => isset($data['is_active']) ? (int)(bool)$data['is_active'] : $company['is_active'],
        ];

        if ($logoFile && $logoFile['error'] === UPLOAD_ERR_OK) {
            $result = $this->uploadLogo($logoFile, $company['logo'] ?? null);
            if ($result['success']) {
                $updateData['logo'] = $result['filename'];
            }
        }

        if (!empty($data['remove_logo']) && !empty($company['logo'])) {
            $this->deleteLogo($company['logo']);
            $updateData['logo'] = null;
        }

        Company::updateById($id, $updateData);

        $this->logActivity(
            auth_id(),
            'company_updated', 'company', $id,
            "Company updated: #{$id} {$updateData['name']}"
        );

        return ['success' => true, 'message' => 'Company updated successfully.'];
    }

    // ── Delete ────────────────────────────────────────────────────

    public function delete(int $id): array
    {
        $company = Company::find($id);
        if (!$company) {
            return ['success' => false, 'message' => 'Company not found.'];
        }

        // Check for active users
        $userCount = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM users WHERE company_id = ? AND is_active = 1",
            [$id]
        );

        if ($userCount > 0) {
            return [
                'success' => false,
                'message' => "Cannot delete company with {$userCount} active user(s). Reassign or deactivate users first.",
            ];
        }

        // Delete logo
        if (!empty($company['logo'])) {
            $this->deleteLogo($company['logo']);
        }

        // Soft delete — deactivate instead of hard delete
        Company::updateById($id, ['is_active' => 0]);

        $this->logActivity(
            auth_id(),
            'company_deleted', 'company', $id,
            "Company deactivated: {$company['name']}"
        );

        return ['success' => true, 'message' => 'Company deactivated successfully.'];
    }

    // ── Toggle ────────────────────────────────────────────────────

    public function toggleActive(int $id): array
    {
        $company = Company::find($id);
        if (!$company) {
            return ['success' => false, 'message' => 'Company not found.'];
        }

        $newStatus = $company['is_active'] ? 0 : 1;
        Company::updateById($id, ['is_active' => $newStatus]);

        $action = $newStatus ? 'activated' : 'deactivated';

        $this->logActivity(
            auth_id(),
            'company_' . $action, 'company', $id,
            "Company {$action}: {$company['name']}"
        );

        return [
            'success'   => true,
            'message'   => "Company {$action} successfully.",
            'is_active' => $newStatus,
        ];
    }

    // ── Logo upload ───────────────────────────────────────────────

    private function uploadLogo(array $file, ?string $oldLogo = null): array
    {
        $allowed   = ['image/jpeg','image/png','image/gif','image/webp','image/svg+xml'];
        $maxSize   = 2 * 1024 * 1024; // 2MB
        $uploadDir = base_path('public/assets/uploads/logos');

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if (!in_array($file['type'], $allowed)) {
            return ['success' => false, 'message' => 'Invalid file type. JPG, PNG, WEBP, SVG allowed.'];
        }

        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => 'File too large. Maximum 2MB.'];
        }

        if ($oldLogo) {
            $this->deleteLogo($oldLogo);
        }

        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = 'logo_' . uniqid('', true) . '.' . $ext;
        $dest     = $uploadDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return ['success' => false, 'message' => 'Failed to upload logo.'];
        }

        return ['success' => true, 'filename' => $filename];
    }

    private function deleteLogo(string $filename): void
    {
        $path = base_path('public/assets/uploads/logos/' . $filename);
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
