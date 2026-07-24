<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Session
 *
 * Typed wrapper around PHP sessions with:
 * - Flash messages
 * - CSRF token storage
 * - Auth user caching
 * - Regeneration on privilege change
 */
class Session
{
    private static ?Session $instance = null;

    public static function getInstance(): static
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    // ----------------------------------------------------------------
    // Core get/set/delete
    // ----------------------------------------------------------------

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function all(): array
    {
        return $_SESSION ?? [];
    }

    // ----------------------------------------------------------------
    // Flash messages (survive exactly one redirect)
    // ----------------------------------------------------------------

    public function setFlash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public function getFlash(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    public function hasFlash(string $key): bool
    {
        return isset($_SESSION['_flash'][$key]);
    }

    public function flash(string $key, mixed $value): void
    {
        $this->setFlash($key, $value);
    }

    // ----------------------------------------------------------------
    // Convenience flash methods
    // ----------------------------------------------------------------

    public function success(string $message): void
    {
        $this->setFlash('success', $message);
    }

    public function error(string $message): void
    {
        $this->setFlash('error', $message);
    }

    public function warning(string $message): void
    {
        $this->setFlash('warning', $message);
    }

    public function info(string $message): void
    {
        $this->setFlash('info', $message);
    }

    // ----------------------------------------------------------------
    // Auth user
    // ----------------------------------------------------------------

    public function setUser(array $user): void
    {
        $this->set('auth_user', $user);
    }

    public function getUser(): ?array
    {
        return $this->get('auth_user');
    }

    public function isLoggedIn(): bool
    {
        return $this->has('auth_user');
    }

    public function getUserId(): ?int
    {
        $user = $this->getUser();
        return $user ? (int) $user['id'] : null;
    }

    public function getUserRole(): ?string
    {
        $user = $this->getUser();
        return $user['role'] ?? null;
    }

    // ----------------------------------------------------------------
    // Security
    // ----------------------------------------------------------------

    /**
     * Regenerate session ID.
     * Call on login/logout/privilege change to prevent session fixation.
     */
    public function regenerate(bool $deleteOld = true): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id($deleteOld);
        }
    }

    public function destroy(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    // ----------------------------------------------------------------
    // Old input (for form re-population after validation failure)
    // ----------------------------------------------------------------

    public function setOldInput(array $data): void
    {
        $this->setFlash('_old_input', $data);
    }

    public function getOldInput(string $key = null, mixed $default = null): mixed
    {
        $old = $this->getFlash('_old_input', []);

        if ($key === null) {
            return $old;
        }

        return $old[$key] ?? $default;
    }
}
