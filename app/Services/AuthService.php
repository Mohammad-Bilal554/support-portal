<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Core\Session;
use App\Models\User;

/**
 * AuthService
 *
 * Handles all authentication business logic:
 * login, logout, password reset token lifecycle,
 * session management, and brute-force protection.
 */
class AuthService
{
    private Database $db;
    private Session  $session;
    private Logger   $logger;

    public function __construct()
    {
        $this->db      = Database::getInstance();
        $this->session = Session::getInstance();
        $this->logger  = Logger::getInstance();
    }

    // ----------------------------------------------------------------
    // Login
    // ----------------------------------------------------------------

    /**
     * Attempt login with email + password.
     *
     * @return array{success: bool, message: string, user?: array}
     */
    public function attempt(string $email, string $password, string $ip): array
    {
        $email = strtolower(trim($email));

        // 1. Brute-force check
        if (User::isLockedOut($email, $ip)) {
            $this->logger->warning("Login locked out: {$email} from {$ip}");
            return [
                'success' => false,
                'message' => 'Too many failed attempts. Please wait 15 minutes and try again.',
            ];
        }

        // 2. Find user
        $user = User::findByEmail($email);

        if (! $user) {
            User::incrementLoginAttempts($email, $ip);
            $this->logger->warning("Login failed (user not found): {$email}");
            return ['success' => false, 'message' => 'Invalid email or password.'];
        }

        // 3. Check password
        if (! User::verifyPassword($password, $user['password'])) {
            User::incrementLoginAttempts($email, $ip);
            $attempts = User::getLoginAttempts($email, $ip);
            $remaining = max(0, 5 - $attempts);
            $this->logger->warning("Login failed (wrong password): {$email}");
            return [
                'success' => false,
                'message' => "Invalid email or password. {$remaining} attempt(s) remaining.",
            ];
        }

        // 4. Check active
        if (! $user['is_active']) {
            $this->logger->warning("Login failed (inactive): {$email}");
            return ['success' => false, 'message' => 'Your account has been deactivated. Please contact support.'];
        }

        // 5. Success — set up session
        User::clearLoginAttempts($email, $ip);
        User::updateLastLogin((int) $user['id']);

        // Regenerate session ID to prevent fixation
        $this->session->regenerate();

        // Store user in session (never store password)
        $safeUser = $this->sanitizeUserForSession($user);
        $this->session->setUser($safeUser);

        // Log activity
        $this->logActivity((int) $user['id'], 'login', 'user', (int) $user['id'], 'User logged in', $ip);

        $this->logger->info("Login success: {$email} (role: {$user['role']})");

        return ['success' => true, 'message' => 'Login successful.', 'user' => $safeUser];
    }

    // ----------------------------------------------------------------
    // Logout
    // ----------------------------------------------------------------

    public function logout(string $ip = ''): void
    {
        $userId = $this->session->getUserId();

        if ($userId) {
            $this->logActivity($userId, 'logout', 'user', $userId, 'User logged out', $ip);
            $this->logger->info("User #{$userId} logged out.");
        }

        $this->session->destroy();
    }

    // ----------------------------------------------------------------
    // Forgot Password
    // ----------------------------------------------------------------

    /**
     * Generate a password reset token and return it.
     * Returns null if user not found (but don't reveal that to the caller).
     */
    public function createPasswordResetToken(string $email): ?string
    {
        $email = strtolower(trim($email));
        $user  = User::findByEmail($email);

        if (! $user) {
            // Timing-safe: still return null, caller should show generic message
            $this->logger->info("Password reset requested for unknown email: {$email}");
            return null;
        }

        // Invalidate old tokens
        $this->db->query(
            "UPDATE password_resets SET used = 1 WHERE email = ?",
            [$email]
        );

        // Create new token
        $token     = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour

        $this->db->insert('password_resets', [
            'email'      => $email,
            'token'      => hash('sha256', $token), // store hash, send raw
            'expires_at' => $expiresAt,
            'used'       => 0,
        ]);

        $this->logger->info("Password reset token created for: {$email}");

        return $token; // raw token (sent in email)
    }

    // ----------------------------------------------------------------
    // Reset Password
    // ----------------------------------------------------------------

    /**
     * Validate token and reset password.
     *
     * @return array{success: bool, message: string}
     */
    public function resetPassword(string $token, string $newPassword): array
    {
        $hashedToken = hash('sha256', $token);

        $record = $this->db->fetchOne(
            "SELECT * FROM password_resets
             WHERE token = ? AND used = 0 AND expires_at > NOW()
             LIMIT 1",
            [$hashedToken]
        );

        if (! $record) {
            return ['success' => false, 'message' => 'This password reset link is invalid or has expired.'];
        }

        $user = User::findByEmail($record['email']);

        if (! $user) {
            return ['success' => false, 'message' => 'User not found.'];
        }

        // Update password
        User::updateById((int) $user['id'], [
            'password' => User::hashPassword($newPassword),
        ]);

        // Invalidate the token
        $this->db->update('password_resets', ['used' => 1], ['token' => $hashedToken]);

        // Clear any login lockouts
        User::clearLoginAttempts($record['email'], '');

        $this->logActivity(
            (int) $user['id'],
            'password_reset',
            'user',
            (int) $user['id'],
            'Password was reset'
        );

        $this->logger->info("Password reset success for: {$record['email']}");

        return ['success' => true, 'message' => 'Password has been reset successfully.'];
    }

    /**
     * Validate a token is real and unexpired (for showing the reset form).
     */
    public function validateResetToken(string $token): bool
    {
        $hashedToken = hash('sha256', $token);

        $record = $this->db->fetchOne(
            "SELECT id FROM password_resets
             WHERE token = ? AND used = 0 AND expires_at > NOW()
             LIMIT 1",
            [$hashedToken]
        );

        return $record !== null;
    }

    // ----------------------------------------------------------------
    // Refresh session user (after profile update)
    // ----------------------------------------------------------------

    public function refreshSessionUser(): void
    {
        $userId = $this->session->getUserId();
        if (! $userId) return;

        $user = User::find($userId);
        if ($user) {
            $this->session->setUser($this->sanitizeUserForSession($user));
        }
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    private function sanitizeUserForSession(array $user): array
    {
        unset($user['password']);
        return $user;
    }

    private function logActivity(
        int    $userId,
        string $action,
        string $entityType,
        int    $entityId,
        string $description,
        string $ip = ''
    ): void {
        try {
            $this->db->insert('activity_logs', [
                'user_id'     => $userId,
                'action'      => $action,
                'entity_type' => $entityType,
                'entity_id'   => $entityId,
                'description' => $description,
                'ip_address'  => $ip ?: ($_SERVER['REMOTE_ADDR'] ?? ''),
                'user_agent'  => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to log activity: ' . $e->getMessage());
        }
    }
}
